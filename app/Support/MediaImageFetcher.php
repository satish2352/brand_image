<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

/**
 * Pulls in the media images a bulk upload sheet refers to.
 *
 * An Image cell may name its picture in two ways:
 *
 *   1. a file inside the images ZIP uploaded with the sheet ("site-front.jpg"),
 *      which is the portable form and the one the template recommends;
 *   2. a direct https:// link, fetched over the network.
 *
 * The preview screen only checks each reference — cheap, cached and capped — so
 * the admin sees dead, missing or oversized images before anything is written.
 * The real download happens on publish, once the records exist.
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

    /** The images ZIP uploaded with the sheet, when there was one. */
    private ?MediaImportImageBundle $bundle = null;

    /**
     * Point the fetcher at the archive that came with this upload. Every Image
     * cell that is not a web link is then looked up inside it.
     */
    public function useBundle(?MediaImportImageBundle $bundle): void
    {
        $this->bundle = $bundle;
        $this->probeCache = [];
    }

    /**
     * Split a comma / semicolon / newline separated cell into individual entries.
     *
     * Whitespace is NOT a separator: a file name such as "site front 1.jpg"
     * contains spaces and must survive intact. Web links never contain spaces,
     * so commas / newlines are enough.
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
     * A direct web link, as opposed to a name inside the images ZIP.
     */
    public static function isWebUrl(string $value): bool
    {
        return (bool) preg_match('#^https?://#i', trim($value));
    }

    /**
     * A file path from someone's own machine rather than a portable file name:
     * a Windows drive path (C:\... or C:/...), a UNC share (\\host\share) or a
     * Unix absolute path. Only ever readable when the sheet and the server are
     * the same computer, so it is refused unless explicitly enabled.
     */
    public static function isLocalPath(string $value): bool
    {
        return (bool) preg_match('#^[a-zA-Z]:[\\\\/]#', $value)
            || str_starts_with($value, '\\\\')
            || str_starts_with($value, '/');
    }

    /**
     * Check a reference without downloading it.
     *
     * @return string|null human readable problem, or null when it looks good
     */
    public function probe(string $url, int $maxKb): ?string
    {
        $cacheKey = $url . '|' . $maxKb;

        if (array_key_exists($cacheKey, $this->probeCache)) {
            return $this->probeCache[$cacheKey];
        }

        // Anything that is not a web link names a file we already hold on disk,
        // so it is checked there and never over the network.
        if (!self::isWebUrl($url)) {
            return $this->probeCache[$cacheKey] = $this->fileProblem($url, $maxKb);
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
        // A ZIP member (or, on a self hosted install, a local path) is copied
        // off the disk we already have it on rather than fetched.
        if (!self::isWebUrl($url)) {
            return $this->storeDiskFile($url, $maxKb);
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
     * Validate and store an image we already hold on disk — normally a member
     * of the images ZIP staged for this batch.
     *
     * @throws RuntimeException with a message fit for the admin to read
     */
    private function storeDiskFile(string $reference, int $maxKb): string
    {
        $resolved = $this->resolveFile($reference);

        if ($resolved['problem'] !== null) {
            throw new RuntimeException($resolved['problem']);
        }

        $problem = $this->diskFileProblem($resolved['path'], $maxKb);
        if ($problem !== null) {
            throw new RuntimeException($problem);
        }

        $body = @file_get_contents($resolved['path']);
        if ($body === false || $body === '') {
            throw new RuntimeException('file is empty or could not be read');
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
     * Turn a non-web reference into a readable absolute path.
     *
     * Normally that means finding it in the images ZIP. Failing that, a
     * self-hosted install may still be configured to read paths straight off
     * the machine it runs on — useful on localhost, never on a real server,
     * where the sheet's author and the server are different computers.
     *
     * @return array{path: string|null, problem: string|null}
     */
    private function resolveFile(string $reference): array
    {
        if ($this->bundle !== null) {
            return $this->bundle->resolve($reference);
        }

        if (self::isLocalPath($reference)) {
            if (!config('fileConstants.IMAGE_IMPORT_ALLOW_LOCAL_PATHS')) {
                return [
                    'path' => null,
                    'problem' => 'is a path on your own computer ("' . $reference . '"), which this server cannot open. '
                        . 'Write only the file name in this column and upload the pictures as an images ZIP',
                ];
            }

            if (!is_file($reference)) {
                return ['path' => null, 'problem' => 'local file was not found on the server disk (' . $reference . ')'];
            }

            if (!is_readable($reference)) {
                return ['path' => null, 'problem' => 'local file cannot be read by the server'];
            }

            return ['path' => $reference, 'problem' => null];
        }

        return [
            'path' => null,
            'problem' => 'refers to a file, but no images ZIP was uploaded with this sheet. '
                . 'Attach the ZIP holding your pictures, or write a full https:// link instead',
        ];
    }

    /**
     * Check a ZIP member without loading it, for the preview screen.
     */
    private function fileProblem(string $reference, int $maxKb): ?string
    {
        $resolved = $this->resolveFile($reference);

        return $resolved['problem'] ?? $this->diskFileProblem($resolved['path'], $maxKb);
    }

    /**
     * Everything that can be judged from a file already on disk, shared by the
     * preview probe and the publish download.
     */
    private function diskFileProblem(string $path, int $maxKb): ?string
    {
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
