<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

/**
 * Pulls the media images referenced by URL in the bulk upload sheet.
 *
 * The preview screen only probes each link — cheap, cached and capped — so the
 * admin sees dead or oversized images before anything is written. The real
 * download happens on publish, once the records exist.
 *
 * Limits mirror the Add Media form so a sheet can never smuggle in a file the
 * form itself would have rejected.
 */
class MediaImageFetcher
{
    /** 600KB per gallery image, 5MB per panorama — same as the Add Media form. */
    public const MAX_GALLERY_KB = 600;

    public const MAX_PANORAMA_KB = 5120;

    /** "You can upload a maximum of 10 images per media." */
    public const MAX_GALLERY_IMAGES = 10;

    /** Distinct links probed per preview; beyond this only the syntax is checked. */
    public const PROBE_LIMIT = 200;

    private const TIMEOUT = 10;

    private const MIME_EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/jpg' => 'jpg',
        'image/pjpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    /** "<url>|<maxKb>" => error message or null, so a repeated link costs one request. */
    private array $probeCache = [];

    private int $probes = 0;

    /**
     * Split a comma / semicolon / newline separated cell into individual links.
     *
     * Whitespace is NOT a separator: a local Windows path such as
     * "C:\Users\Jane Doe\site 1.jpg" contains spaces and must survive intact.
     * Web links never contain spaces, so commas / newlines are enough.
     *
     * "-" is dropped: the export writes it into empty cells, and an exported
     * sheet is expected to import straight back.
     *
     * @return array<int,string>
     */
    public static function splitUrls($value): array
    {
        if ($value === null || trim((string) $value) === '') {
            return [];
        }

        $urls = preg_split('/[,;\r\n]+/', trim((string) $value));

        return array_values(array_filter(
            array_map('trim', $urls),
            fn ($url) => $url !== '' && $url !== '-'
        ));
    }

    /**
     * A local file path on the server's own disk, rather than a web link:
     * a Windows drive path (C:\... or C:/...) or a UNC share (\\host\share).
     */
    public static function isLocalPath(string $value): bool
    {
        return (bool) preg_match('#^[a-zA-Z]:[\\\\/]#', $value)
            || str_starts_with($value, '\\\\');
    }

    /**
     * Check a link without downloading it.
     *
     * @return string|null human readable problem, or null when the link looks good
     */
    public function probe(string $url, int $maxKb): ?string
    {
        $cacheKey = $url . '|' . $maxKb;

        if (array_key_exists($cacheKey, $this->probeCache)) {
            return $this->probeCache[$cacheKey];
        }

        // A local file path is checked on disk, never over the network.
        if (self::isLocalPath($url)) {
            return $this->probeCache[$cacheKey] = $this->inspectLocalFile($url, $maxKb);
        }

        $syntax = $this->syntaxError($url);
        if ($syntax !== null) {
            return $this->probeCache[$cacheKey] = $syntax;
        }

        // Past the cap the sheet is simply too large to probe politely; the
        // download on publish still reports anything broken.
        if ($this->probes >= self::PROBE_LIMIT) {
            return $this->probeCache[$cacheKey] = null;
        }

        $this->probes++;

        return $this->probeCache[$cacheKey] = $this->inspect($url, $maxKb);
    }

    /**
     * Download a link and store it, returning the stored file name.
     *
     * @throws RuntimeException with a message fit for the admin to read
     */
    public function download(string $url, int $maxKb): string
    {
        // A local file path is copied off the server's disk, not fetched.
        if (self::isLocalPath($url)) {
            return $this->storeLocalFile($url, $maxKb);
        }

        $syntax = $this->syntaxError($url);
        if ($syntax !== null) {
            throw new RuntimeException($syntax);
        }

        try {
            $response = Http::timeout(self::TIMEOUT)->withOptions(['stream' => false])->get($url);
        } catch (Throwable $e) {
            throw new RuntimeException('could not be reached (' . $e->getMessage() . ')');
        }

        if ($response->status() >= 400) {
            throw new RuntimeException('URL returned ' . $response->status());
        }

        $body = $response->body();

        if ($body === '') {
            throw new RuntimeException('the file is empty');
        }

        if (strlen($body) > $maxKb * 1024) {
            throw new RuntimeException(
                'is ' . $this->readableSize(strlen($body)) . ', the limit is ' . $maxKb . 'KB'
            );
        }

        // Trust the bytes over the headers — a wrong Content-Type is common.
        return $this->storeImageBody($body);
    }

    /**
     * Validate and store an image the sheet referenced by a local file path.
     *
     * @throws RuntimeException with a message fit for the admin to read
     */
    private function storeLocalFile(string $path, int $maxKb): string
    {
        $problem = $this->localPathProblem($path, $maxKb);
        if ($problem !== null) {
            throw new RuntimeException($problem);
        }

        $body = @file_get_contents($path);
        if ($body === false || $body === '') {
            throw new RuntimeException('local file is empty or could not be read');
        }

        return $this->storeImageBody($body);
    }

    /**
     * Turn validated image bytes into a stored file, returning its name.
     *
     * @throws RuntimeException
     */
    private function storeImageBody(string $body): string
    {
        $dimensions = @getimagesizefromstring($body);
        if ($dimensions === false) {
            throw new RuntimeException('is not an image');
        }

        $extension = self::MIME_EXTENSIONS[strtolower($dimensions['mime'] ?? '')] ?? null;
        if ($extension === null) {
            throw new RuntimeException(
                'is a ' . ($dimensions['mime'] ?? 'unknown') . ' file — only JPG, PNG and WebP are allowed'
            );
        }

        $fileName = time() . '_' . uniqid() . '.' . $extension;
        $path = config('fileConstants.IMAGE_ADD') . '/' . $fileName;

        Storage::disk('public')->put($path, $body);

        if (!Storage::disk('public')->exists($path)) {
            throw new RuntimeException('could not be saved to storage');
        }

        return $fileName;
    }

    /* =====================================================================
     |  INTERNALS
     ===================================================================== */

    /**
     * Validate a local file path without reading the whole file into memory.
     *
     * @return string|null human readable problem, or null when the file looks good
     */
    private function inspectLocalFile(string $path, int $maxKb): ?string
    {
        return $this->localPathProblem($path, $maxKb);
    }

    /**
     * The disk-side checks shared by the preview probe and the publish download.
     */
    private function localPathProblem(string $path, int $maxKb): ?string
    {
        if (!config('fileConstants.IMAGE_IMPORT_ALLOW_LOCAL_PATHS')) {
            return 'is a local file path, which this server does not allow';
        }

        if (!is_file($path)) {
            return 'local file was not found on the server disk (' . $path . ')';
        }

        if (!is_readable($path)) {
            return 'local file cannot be read by the server';
        }

        $size = @filesize($path);
        if ($size !== false && $size > $maxKb * 1024) {
            return 'is ' . $this->readableSize((int) $size) . ', the limit is ' . $maxKb . 'KB';
        }

        $dimensions = @getimagesize($path);
        if ($dimensions === false) {
            return 'is not an image';
        }

        $extension = self::MIME_EXTENSIONS[strtolower($dimensions['mime'] ?? '')] ?? null;
        if ($extension === null) {
            return 'is a ' . ($dimensions['mime'] ?? 'unknown') . ' file — only JPG, PNG and WebP are allowed';
        }

        return null;
    }

    /**
     * Everything that can be judged without touching the network.
     */
    private function syntaxError(string $url): ?string
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return 'is not a valid URL';
        }

        $parts = parse_url($url);
        $scheme = strtolower($parts['scheme'] ?? '');

        if (!in_array($scheme, ['http', 'https'], true)) {
            return 'must start with http:// or https://';
        }

        $host = $parts['host'] ?? '';
        if ($host === '') {
            return 'is missing a host name';
        }

        if (!config('fileConstants.IMAGE_IMPORT_ALLOW_PRIVATE_HOSTS') && $this->isPrivateHost($host)) {
            return 'points at a private or local address, which is not allowed';
        }

        return null;
    }

    /**
     * HEAD the link, falling back to a one byte GET for servers that refuse HEAD.
     */
    private function inspect(string $url, int $maxKb): ?string
    {
        try {
            $response = Http::timeout(self::TIMEOUT)->head($url);

            // Plenty of CDNs answer HEAD with 403/405 but serve GET fine.
            if (in_array($response->status(), [403, 405, 501], true)) {
                $response = Http::timeout(self::TIMEOUT)
                    ->withHeaders(['Range' => 'bytes=0-0'])
                    ->get($url);
            }
        } catch (Throwable $e) {
            return 'could not be reached (' . $e->getMessage() . ')';
        }

        if ($response->status() >= 400) {
            return 'URL returned ' . $response->status();
        }

        $contentType = strtolower(trim(explode(';', (string) $response->header('Content-Type'))[0]));

        if ($contentType !== '' && !isset(self::MIME_EXTENSIONS[$contentType])) {
            return str_starts_with($contentType, 'image/')
                ? 'is a ' . $contentType . ' file — only JPG, PNG and WebP are allowed'
                : 'is not an image (server reported ' . $contentType . ')';
        }

        // Absent on chunked responses, so this is a best effort early warning.
        $length = (int) $response->header('Content-Length');
        if ($length > 0 && $length > $maxKb * 1024) {
            return 'is ' . $this->readableSize($length) . ', the limit is ' . $maxKb . 'KB';
        }

        return null;
    }

    /**
     * Guards the fetcher against being pointed at the server's own network.
     */
    private function isPrivateHost(string $host): bool
    {
        $ips = [];

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $ips[] = $host;
        } else {
            foreach (@dns_get_record($host, DNS_A | DNS_AAAA) ?: [] as $record) {
                $ips[] = $record['ip'] ?? $record['ipv6'] ?? null;
            }

            $ips = array_values(array_filter($ips));

            if (empty($ips)) {
                $resolved = gethostbyname($host);
                if ($resolved !== $host) {
                    $ips[] = $resolved;
                }
            }
        }

        // A name we cannot resolve is treated as unsafe rather than tried.
        if (empty($ips)) {
            return true;
        }

        foreach ($ips as $ip) {
            $public = filter_var(
                $ip,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            );

            if ($public === false) {
                return true;
            }
        }

        return false;
    }

    private function readableSize(int $bytes): string
    {
        return $bytes >= 1048576
            ? round($bytes / 1048576, 1) . 'MB'
            : max(1, (int) round($bytes / 1024)) . 'KB';
    }
}
