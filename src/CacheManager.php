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
        $newFilePath = $this->fileCacheDir . '/' . $newFile;
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
