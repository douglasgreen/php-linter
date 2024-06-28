<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter;

use DouglasGreen\Utility\FileSystem\DirUtil;
use DouglasGreen\Utility\FileSystem\PathUtil;

class CacheManager
{
    public const CACHE_DIR = 'var/cache/pdepend';

    public const FILE_CACHE_DIR = 'var/cache/pdepend/files';

    public const SUMMARY_FILE = 'var/cache/pdepend/summary.xml';

    protected readonly string $cacheDir;

    protected readonly string $fileCacheDir;

    protected readonly string $summaryFile;

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

    public function __construct(
        protected readonly string $currentDir
    ) {
        $this->cacheDir = PathUtil::addSubpath($currentDir, self::CACHE_DIR);
        $this->fileCacheDir = PathUtil::addSubpath($currentDir, self::FILE_CACHE_DIR);
        $this->summaryFile = PathUtil::addSubpath($currentDir, self::SUMMARY_FILE);

        // Ensure the cache directory exists
        if (! is_dir($this->cacheDir)) {
            DirUtil::makeRecursive($this->cacheDir);
        }

        // Ensure the file cache directory exists and is clear.
        if (is_dir($this->fileCacheDir)) {
            DirUtil::removeContents($this->fileCacheDir);
        } else {
            DirUtil::make($this->fileCacheDir);
        }
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
            DirUtil::makeRecursive($newFileDir);
        }

        // Copy the file
        PathUtil::copy($file, $newFilePath);
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
