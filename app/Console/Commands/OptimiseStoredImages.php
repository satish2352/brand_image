<?php

namespace App\Console\Commands;

use App\Support\ImageResizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * One-off catch up for pictures uploaded before anything resized them.
 *
 * uploadImage() and MediaImageFetcher now shrink everything on the way in, but
 * the inventory already holds years of full resolution camera originals, and
 * those are what the public site is still serving. This walks the stored images
 * and rewrites each one through the same resizer.
 *
 * Files are replaced in place, so the file names in the database keep working
 * and nothing else has to be touched. Run it with --dry-run first to see what
 * it would save.
 */
class OptimiseStoredImages extends Command
{
    protected $signature = 'images:optimise
                            {--dry-run : Report what would be saved without changing any file}
                            {--folder= : Storage folder to walk (default: the media upload folder)}
                            {--max-width= : Longest edge to allow, in pixels (default: ' . ImageResizer::MAX_WIDTH . ')}';

    protected $description = 'Shrink already uploaded images that were stored at full camera resolution';

    public function handle(): int
    {
        if (!extension_loaded('gd')) {
            $this->error('The GD extension is not available, so no image can be resized.');

            return self::FAILURE;
        }

        $disk = Storage::disk('public');
        $folder = (string) ($this->option('folder') ?: config('fileConstants.IMAGE_ADD'));
        $maxWidth = (int) ($this->option('max-width') ?: ImageResizer::MAX_WIDTH);
        $dryRun = (bool) $this->option('dry-run');

        if (!$disk->exists($folder)) {
            $this->error('Folder "' . $folder . '" does not exist on the public disk.');

            return self::FAILURE;
        }

        $files = $disk->files($folder);

        if ($files === []) {
            $this->info('Nothing to do — "' . $folder . '" holds no files.');

            return self::SUCCESS;
        }

        $this->info(($dryRun ? '[dry run] ' : '') . 'Checking ' . count($files) . ' file(s) in ' . $folder);
        $this->newLine();

        $bar = $this->output->createProgressBar(count($files));
        $bar->start();

        $touched = 0;
        $skipped = 0;
        $failed = 0;
        $before = 0;
        $after = 0;

        foreach ($files as $path) {
            $bar->advance();

            try {
                $original = $disk->get($path);
            } catch (\Throwable $e) {
                $failed++;
                continue;
            }

            if ($original === null || $original === '') {
                $skipped++;
                continue;
            }

            $originalSize = strlen($original);

            // optimise() hands back the very same bytes when a picture is
            // already small enough, is a format we do not re-encode, or is too
            // large to open safely — all of which mean "leave this one alone".
            $optimised = ImageResizer::optimise($original, $maxWidth, $maxWidth);

            if ($optimised === $original) {
                $skipped++;
                continue;
            }

            $before += $originalSize;
            $after += strlen($optimised);
            $touched++;

            if ($dryRun) {
                continue;
            }

            try {
                $disk->put($path, $optimised);
            } catch (\Throwable $e) {
                $failed++;
                $this->newLine();
                $this->warn('Could not write ' . $path . ': ' . $e->getMessage());
            }
        }

        $bar->finish();
        $this->newLine(2);

        $saved = $before - $after;

        $this->table(
            ['', 'Count'],
            [
                [$dryRun ? 'Would be resized' : 'Resized', $touched],
                ['Left alone', $skipped],
                ['Failed', $failed],
            ]
        );

        if ($touched > 0) {
            $this->info(sprintf(
                '%s %s → %s  (%s saved, %.0f%% smaller)',
                $dryRun ? 'Would shrink' : 'Shrank',
                $this->readable($before),
                $this->readable($after),
                $this->readable($saved),
                $before > 0 ? ($saved / $before) * 100 : 0
            ));
        }

        if ($dryRun && $touched > 0) {
            $this->newLine();
            $this->comment('Nothing was changed. Re-run without --dry-run to apply.');
        }

        return self::SUCCESS;
    }

    private function readable(int $bytes): string
    {
        if ($bytes >= 1024 * 1024 * 1024) {
            return round($bytes / (1024 * 1024 * 1024), 2) . ' GB';
        }

        if ($bytes >= 1024 * 1024) {
            return round($bytes / (1024 * 1024), 1) . ' MB';
        }

        return round($bytes / 1024) . ' KB';
    }
}
