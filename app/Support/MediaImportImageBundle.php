<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

/**
 * The images that travel with a bulk upload sheet, as one ZIP archive.
 *
 * The sheet cannot carry the pictures themselves and it must not point at the
 * uploader's own computer — the server never sees that disk. So the admin
 * uploads an archive alongside the sheet and each Image cell holds nothing but
 * a file name ("site-front.jpg"), which this class resolves back to a real file.
 *
 * Extraction is deliberately paranoid: entries are re-written under generated
 * names inside one private staging directory, so a hostile archive cannot
 * escape it, overwrite anything, or fill the disk.
 *
 * The staging directory outlives the preview request — publish re-opens it with
 * open() using the batch token — and is deleted once the import is published or
 * discarded.
 */
class MediaImportImageBundle
{
    /** Files taken from one archive; a full sheet is 5000 rows x 10 images. */
    public const MAX_ENTRIES = 20000;

    /** Total extracted size, so a zip bomb cannot fill a shared host's quota. */
    public const MAX_UNCOMPRESSED_MB = 1024;

    public const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    /** Index value for a name that appears more than once in the archive. */
    private const AMBIGUOUS = '?';

    /** Written beside the extracted files so publish can re-open the bundle. */
    private const INDEX_FILE = '_index.json';

    /** Path of the staging directory, relative to the local disk. */
    private string $directory;

    /** @var array<string,string> lookup key => extracted file name */
    private array $index;

    /** Archive members that were left out, e.g. a PDF dropped in by mistake. */
    private array $skipped;

    private int $fileCount;

    /** @var array<string,bool> extracted file names a sheet row has claimed */
    private array $used = [];

    private function __construct(string $directory, array $index, array $skipped, int $fileCount)
    {
        $this->directory = $directory;
        $this->index = $index;
        $this->skipped = $skipped;
        $this->fileCount = $fileCount;
    }

    /* =====================================================================
     |  BUILD & REOPEN
     ===================================================================== */

    /**
     * Unpack an uploaded archive into its own staging directory.
     *
     * @param string $directory path relative to the local disk, e.g. "media_imports/<token>_images"
     *
     * @throws RuntimeException with a message fit for the admin to read
     */
    public static function extract(UploadedFile $zip, string $directory): self
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException(
                'This server cannot open ZIP files — the PHP "zip" extension is not enabled. '
                . 'Please enable it from cPanel > Select PHP Version > Extensions, or use image links instead.'
            );
        }

        $disk = Storage::disk('local');
        $disk->deleteDirectory($directory);
        $disk->makeDirectory($directory);

        $archive = new ZipArchive();

        if ($archive->open($zip->getRealPath()) !== true) {
            throw new RuntimeException(
                'The images ZIP could not be opened. Please re-create the archive and upload it again.'
            );
        }

        $index = [];
        $skipped = [];
        $files = 0;
        $bytes = 0;
        $budget = self::MAX_UNCOMPRESSED_MB * 1024 * 1024;

        for ($i = 0; $i < $archive->numFiles; $i++) {
            $stat = $archive->statIndex($i);

            if ($stat === false) {
                continue;
            }

            $name = str_replace('\\', '/', (string) $stat['name']);

            // Folder entries, the noise macOS adds to every archive, and hidden files.
            if (str_ends_with($name, '/') || str_starts_with($name, '__MACOSX/') || str_starts_with(basename($name), '.')) {
                continue;
            }

            $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

            if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
                $skipped[] = basename($name);
                continue;
            }

            if ($files >= self::MAX_ENTRIES) {
                $skipped[] = basename($name);
                continue;
            }

            $bytes += (int) $stat['size'];

            if ($bytes > $budget) {
                $archive->close();
                $disk->deleteDirectory($directory);

                throw new RuntimeException(
                    'The images ZIP unpacks to more than ' . self::MAX_UNCOMPRESSED_MB
                    . 'MB. Please split it into smaller batches.'
                );
            }

            // Extracted under a generated name: nothing from the archive reaches
            // the filesystem, so a "../../" entry has nowhere to go.
            $stored = sprintf('%05d.%s', $files, $extension === 'jpeg' ? 'jpg' : $extension);

            if (!self::copyOut($archive, $stat['name'], $disk->path($directory . '/' . $stored))) {
                $skipped[] = basename($name);
                continue;
            }

            $files++;

            // The full path inside the archive is unique, so it always wins.
            $full = self::key($name);
            $index[$full] = $stored;

            // Everything else the sheet might reasonably write for this file:
            // the bare file name, and either of those without the extension —
            // people habitually type "banner" rather than "banner.jpg".
            $short = self::key(basename($name));

            foreach ([$short, self::stem($full), self::stem($short)] as $alias) {
                if ($alias === '' || $alias === $full) {
                    continue;
                }

                // Two different files answering to one name is a question we
                // cannot settle for the admin, so we ask them instead.
                $index[$alias] = isset($index[$alias]) && $index[$alias] !== $stored
                    ? self::AMBIGUOUS
                    : $stored;
            }
        }

        $archive->close();

        if ($files === 0) {
            $disk->deleteDirectory($directory);

            throw new RuntimeException(
                'The images ZIP contains no usable images. Only JPG, PNG and WebP files are read — '
                . 'put the pictures directly in the archive, not inside another ZIP.'
            );
        }

        $bundle = new self($directory, $index, $skipped, $files);
        $bundle->save();

        return $bundle;
    }

    /**
     * Re-open the staging directory a previous preview left behind.
     */
    public static function open(string $directory): ?self
    {
        $disk = Storage::disk('local');
        $path = $directory . '/' . self::INDEX_FILE;

        if (!$disk->exists($path)) {
            return null;
        }

        $data = json_decode((string) $disk->get($path), true);

        if (!is_array($data) || empty($data['index'])) {
            return null;
        }

        return new self(
            $directory,
            $data['index'],
            $data['skipped'] ?? [],
            (int) ($data['files'] ?? count($data['index']))
        );
    }

    private function save(): void
    {
        Storage::disk('local')->put(
            $this->directory . '/' . self::INDEX_FILE,
            json_encode([
                'index' => $this->index,
                'skipped' => $this->skipped,
                'files' => $this->fileCount,
            ])
        );
    }

    /* =====================================================================
     |  LOOKUP
     ===================================================================== */

    /**
     * Find the file a sheet cell is asking for.
     *
     * @return array{path: string|null, problem: string|null}
     */
    public function resolve(string $name): array
    {
        $key = self::key($name);

        // Tried in order of how precisely each one identifies a file. A sheet
        // written on Windows often still holds a whole path, and the extension
        // is frequently left off or typed in the wrong case.
        $stored = null;

        foreach ([$key, self::key(basename($key)), self::stem($key), self::stem(self::key(basename($key)))] as $candidate) {
            $stored = $this->index[$candidate] ?? null;

            if ($stored !== null) {
                break;
            }
        }

        if ($stored === null) {
            return [
                'path' => null,
                'problem' => 'was not found in the images ZIP — check the spelling, and make sure the picture '
                    . 'is directly inside the archive rather than in a second ZIP',
            ];
        }

        if ($stored === self::AMBIGUOUS) {
            return [
                'path' => null,
                'problem' => 'matches more than one file in the images ZIP — write the file name in full, '
                    . 'e.g. "photos/' . basename($key) . '.jpg"',
            ];
        }

        $this->used[$stored] = true;

        return ['path' => Storage::disk('local')->path($this->directory . '/' . $stored), 'problem' => null];
    }

    public function fileCount(): int
    {
        return $this->fileCount;
    }

    /** Archive members that were not images, reported back as a warning. */
    public function skippedFiles(): array
    {
        return $this->skipped;
    }

    /** How many extracted images no row asked for — usually a typo in the sheet. */
    public function unusedCount(): int
    {
        return max(0, $this->fileCount - count($this->used));
    }

    public function delete(): void
    {
        Storage::disk('local')->deleteDirectory($this->directory);
    }

    /* =====================================================================
     |  INTERNALS
     ===================================================================== */

    /**
     * Stream one archive member to disk, so a large archive never has to fit
     * in PHP's memory limit.
     */
    private static function copyOut(ZipArchive $archive, string $entry, string $destination): bool
    {
        $in = $archive->getStream($entry);

        if ($in === false) {
            return false;
        }

        $out = @fopen($destination, 'wb');

        if ($out === false) {
            fclose($in);

            return false;
        }

        $copied = stream_copy_to_stream($in, $out);

        fclose($in);
        fclose($out);

        if ($copied === false || $copied === 0) {
            @unlink($destination);

            return false;
        }

        return true;
    }

    /**
     * One spelling for a name however the sheet or the archive wrote it.
     */
    private static function key(string $name): string
    {
        $name = str_replace('\\', '/', trim($name));
        $name = ltrim($name, '/');

        if (str_starts_with($name, './')) {
            $name = substr($name, 2);
        }

        return mb_strtolower($name);
    }

    /**
     * The same name with its extension dropped, so "banner" finds "banner.jpg".
     * Returns '' when there is nothing to drop, which callers skip.
     */
    private static function stem(string $key): string
    {
        $dot = strrpos($key, '.');

        // A dot inside a folder name ("my.photos/banner") is not an extension.
        if ($dot === false || $dot === 0 || str_contains(substr($key, $dot), '/')) {
            return '';
        }

        return substr($key, 0, $dot);
    }
}
