<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Metrics;

/**
 * Manages cache directories and files for PDepend execution.
 *
 * Ensures cache directories exist and handles file copying for non-.php files.
 *
 * @package DouglasGreen\PhpLinter\Metrics
 *
 * @since 1.0.0
 *
 * @internal
 */
class CacheManager
{
    /**
     * Path to the main cache directory relative to the project root.
     *
     * @var string
     */
    public const CACHE_DIR = 'var/cache/pdepend';

    /**
     * Path to the file cache directory for copied files.
     *
     * @var string
     */
    public const FILE_CACHE_DIR = 'var/cache/pdepend/files';

    /**
     * Path to the summary XML file.
     *
     * @var string
     */
    public const SUMMARY_FILE = 'var/cache/pdepend/summary.xml';

    /**
     * Permissions mode for created directories.
     *
     * @var int
     */
    public const DIRECTORY_MODE = 0777;

    /**
     * Absolute path to the main cache directory.
     */
    protected readonly string $cacheDir;

    /**
     * Absolute path to the file cache directory.
     */
    protected readonly string $fileCacheDir;

    /**
     * Absolute path to the summary XML file.
     */
    protected readonly string $summaryFile;

    /**
     * Initializes the CacheManager and ensures directories exist.
     *
     * @param string $currentDir The current working directory (project root).
     */
    public function __construct(
        protected readonly string $currentDir,
    ) {
        $this->cacheDir = $currentDir . DIRECTORY_SEPARATOR . self::CACHE_DIR;
        $this->fileCacheDir = $currentDir . DIRECTORY_SEPARATOR . self::FILE_CACHE_DIR;
        $this->summaryFile = $currentDir . DIRECTORY_SEPARATOR . self::SUMMARY_FILE;

        // Ensure the cache directory exists
        if (! is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, self::DIRECTORY_MODE, true);
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
            mkdir($this->fileCacheDir, self::DIRECTORY_MODE, true);
        }
    }

    /**
     * Resolves the original file path from a cached file path.
     *
     * Strips the cache directory prefix and .php extension added for PDepend.
     *
     * @param string $cacheFile The cached file path.
     *
     * @return string The original file path.
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
     * Copies a file into the file cache directory.
     *
     * Used to allow PDepend to process files without .php extensions.
     *
     * @param string $file The source file path.
     * @param string $newFile The relative destination path within the cache.
     */
    public function copyFile(string $file, string $newFile): void
    {
        $newFilePath = $this->fileCacheDir . DIRECTORY_SEPARATOR . $newFile;
        $newFileDir = dirname($newFilePath);

        // Ensure the directory exists
        if (! is_dir($newFileDir)) {
            mkdir($newFileDir, self::DIRECTORY_MODE, true);
        }

        // Copy the file
        copy($file, $newFilePath);
    }

    /**
     * Returns the absolute path to the main cache directory.
     *
     * @return string The cache directory path.
     */
    public function getCacheDir(): string
    {
        return $this->cacheDir;
    }

    /**
     * Returns the absolute path to the file cache directory.
     *
     * @return string The file cache directory path.
     */
    public function getFileCacheDir(): string
    {
        return $this->fileCacheDir;
    }

    /**
     * Returns the absolute path to the summary XML file.
     *
     * @return string The summary file path.
     */
    public function getSummaryFile(): string
    {
        return $this->summaryFile;
    }
}
