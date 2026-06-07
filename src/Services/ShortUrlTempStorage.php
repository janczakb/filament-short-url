<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class ShortUrlTempStorage
{
    public const string ROOT = 'short-urls/tmp';

    public const string OG_PERMANENT = 'short-urls/og';

    public const string LOGO_PERMANENT = 'short-urls/logos';

    /**
     * Hourly bucket path for new temporary uploads.
     */
    public function bucketDirectory(?Carbon $at = null): string
    {
        $at ??= Carbon::now();

        return self::ROOT.'/'.$at->format('Y/m/d/H');
    }

    /**
     * Resolve a storage directory, bucketing tmp uploads by hour.
     */
    public function resolveDirectory(string $directory): string
    {
        if ($directory === self::ROOT) {
            return $this->bucketDirectory();
        }

        return $directory;
    }

    public function isTemporaryPath(?string $path): bool
    {
        return is_string($path) && $path !== '' && str_starts_with($path, self::ROOT.'/');
    }

    /**
     * Move a tmp file to its permanent directory on model save.
     */
    public function promote(string $tmpPath, string $permanentDirectory, string $diskName = 'public'): ?string
    {
        if (! $this->isTemporaryPath($tmpPath)) {
            return $tmpPath;
        }

        $disk = Storage::disk($diskName);

        if (! $disk->exists($tmpPath)) {
            return null;
        }

        $filename = basename($tmpPath);
        $newPath = rtrim($permanentDirectory, '/').'/'.$filename;

        $disk->makeDirectory($permanentDirectory);
        $disk->move($tmpPath, $newPath);

        return $newPath;
    }

    /**
     * Delete hour buckets older than the given TTL.
     *
     * Complexity is O(hours), not O(files): only ~25–50 directory checks for a 24h TTL.
     */
    public function pruneBucketsOlderThanHours(int $hours = 24, string $diskName = 'public'): int
    {
        $disk = Storage::disk($diskName);

        if (! $disk->exists(self::ROOT)) {
            return 0;
        }

        $cutoff = Carbon::now()->subHours($hours);
        $prunedFiles = 0;

        $prunedFiles += $this->pruneLegacyFlatFiles($disk, $cutoff);
        $prunedFiles += $this->pruneHourBuckets($disk, $cutoff);

        return $prunedFiles;
    }

    private function pruneLegacyFlatFiles(Filesystem $disk, Carbon $cutoff): int
    {
        $pruned = 0;

        foreach ($disk->files(self::ROOT) as $file) {
            if ($disk->lastModified($file) < $cutoff->getTimestamp()) {
                $disk->delete($file);
                $pruned++;
            }
        }

        return $pruned;
    }

    private function pruneHourBuckets(Filesystem $disk, Carbon $cutoff): int
    {
        $pruned = 0;

        foreach ($disk->directories(self::ROOT) as $yearDir) {
            if (! ctype_digit(basename($yearDir))) {
                continue;
            }

            $year = (int) basename($yearDir);

            foreach ($disk->directories($yearDir) as $monthDir) {
                $month = (int) basename($monthDir);

                foreach ($disk->directories($monthDir) as $dayDir) {
                    $day = (int) basename($dayDir);

                    foreach ($disk->directories($dayDir) as $hourDir) {
                        $hour = (int) basename($hourDir);

                        if (! $this->isBucketExpired($year, $month, $day, $hour, $cutoff)) {
                            continue;
                        }

                        $pruned += count($disk->allFiles($hourDir));
                        $disk->deleteDirectory($hourDir);
                    }

                    $this->deleteDirectoryIfEmpty($disk, $dayDir);
                }

                $this->deleteDirectoryIfEmpty($disk, $monthDir);
            }

            $this->deleteDirectoryIfEmpty($disk, $yearDir);
        }

        return $pruned;
    }

    private function isBucketExpired(int $year, int $month, int $day, int $hour, Carbon $cutoff): bool
    {
        try {
            $bucketTime = Carbon::create($year, $month, $day, $hour, 0, 0);
        } catch (\Exception) {
            return false;
        }

        return $bucketTime->lt($cutoff);
    }

    private function deleteDirectoryIfEmpty(Filesystem $disk, string $directory): void
    {
        if ($disk->exists($directory) && $disk->allFiles($directory) === [] && $disk->directories($directory) === []) {
            $disk->deleteDirectory($directory);
        }
    }
}
