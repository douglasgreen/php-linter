<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Metrics;

class CacheManager
{
    public const CACHE_DIR = 'var/cache/pdepend';

    public const FILE_CACHE_DIR = 'var/cache/pdepend/files';

    public const SUMMARY_FILE = 'var/cache/pdepend/summary.xml';

    protected readonly string $cacheDir;

    protected readonly string $fileCacheDir;

    protected readonly string $summaryFile;

    public function __construct(
        protected readonly string $currentDir,
    ) {
        $this->cacheDir = $currentDir . DIRECTORY_SEPARATOR . self::CACHE_DIR;
        $this->fileCacheDir = $currentDir . DIRECTORY_SEPARATOR . self::FILE_CACHE_DIR;
        $this->summaryFile = $currentDir . DIRECTORY_SEPARATOR . self::SUMMARY_FILE;

        // Ensure the cache directory exists
        if (! is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
        }

        // Ensure the file cache directory exists and is clear.
        if (is_dir($this->fileCacheDir)) {
            $files = glob($this->fileCacheDir . '/*');
            if (is_array($files)) {
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
            }
        } else {
            mkdir($this->fileCacheDir, 0777, true);
        }
    }

    /**
     * Get the original name of the file from its cache name.
     */
    public static function getOriginalFile(string $cacheFile): string
    {
        $fileCacheDir = self::FILE_CACHE_DIR . DIRECTORY_SEPARATOR;
        // Check if the cache file starts with the cache directory path
        if (str_starts_with($cacheFile, $fileCacheDir)) {
            // Strip the cache directory path from the beginning
            $originalFile = substr($cacheFile, strlen($fileCacheDir));
            // Strip the .php extension from the end
            if (str_ends_with($originalFile, '.php')) {
                return substr($originalFile, 0, -4);
            }

            return $originalFile;
        }

        // Return the original cache file if it does not start with the cache directory path
        return $cacheFile;
    }

    /**
     * Add file to file cache.
     */
    public function copyFile(string $file, string $newFile): void
    {
        $newFilePath = $this->fileCacheDir . DIRECTORY_SEPARATOR . $newFile;
        $newFileDir = dirname($newFilePath);

        // Ensure the directory exists
        if (! is_dir($newFileDir)) {
            mkdir($newFileDir, 0777, true);
        }

        // Copy the file
        copy($file, $newFilePath);
    }

    public function getCacheDir(): string
    {
        return $this->cacheDir;
    }

    public function getFileCacheDir(): string
    {
        return $this->fileCacheDir;
    }

    public function getSummaryFile(): string
    {
        return $this->summaryFile;
    }
}
