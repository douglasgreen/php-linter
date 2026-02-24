<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter;

use Exception;

/**
 * Manages the list of files to ignore based on .phplintignore patterns.
 *
 * @package DouglasGreen\PhpLinter
 * @since 1.0.0
 */
class IgnoreList
{
    public const IGNORE_FILE = '.phplintignore';

    /**
     * List of regex patterns for ignoring files.
     *
     * @var list<string>
     */
    protected readonly array $ignorePatterns;

    /**
     * Constructs a new IgnoreList instance.
     *
     * @param string $currentDir The current directory path.
     *
     * @throws Exception If the ignore file cannot be loaded.
     */
    public function __construct(string $currentDir)
    {
        $ignoreFile = static::addSubpath($currentDir, self::IGNORE_FILE);
        $this->ignorePatterns = static::loadIgnoreFile($ignoreFile);
    }

    /**
     * Adds a subpath to a base path ensuring proper directory separators.
     *
     * @param string $path The base path.
     * @param string $subpath The subpath to add.
     *
     * @return string The combined path.
     */
    public static function addSubpath(string $path, string $subpath): string
    {
        // Ensure the current filename ends with a directory separator
        if (substr($path, -1) !== DIRECTORY_SEPARATOR) {
            $path .= DIRECTORY_SEPARATOR;
        }

        // Ensure the subpath does not start with a directory separator
        if (substr($subpath, 0, 1) === DIRECTORY_SEPARATOR) {
            $subpath = ltrim($subpath, DIRECTORY_SEPARATOR);
        }

        return $path . $subpath;
    }

    /**
     * Checks if a file should be ignored based on the patterns.
     *
     * @param string $filePath The file path to check.
     *
     * @return bool True if the file should be ignored, false otherwise.
     */
    public function shouldIgnore(string $filePath): bool
    {
        foreach ($this->ignorePatterns as $ignorePattern) {
            if (preg_match($ignorePattern, $filePath)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Converts an ignore pattern to a regex pattern.
     *
     * @param string $pattern The ignore pattern.
     *
     * @return string The regex pattern.
     */
    protected static function preparePattern(string $pattern): string
    {
        // Convert the ignore pattern to a regex pattern
        $pattern = preg_quote($pattern, '#');
        $pattern = str_replace('\*', '.*', $pattern);
        $pattern = str_replace('\?', '.', $pattern);
        return sprintf('#^%s#', $pattern);
    }

    /**
     * Loads the ignore file and returns the list of patterns.
     *
     * @param string $ignoreFile Path to the ignore file.
     *
     * @return list<string> The list of regex patterns.
     *
     * @throws Exception If the file cannot be loaded.
     */
    protected static function loadIgnoreFile(string $ignoreFile): array
    {
        if (! file_exists($ignoreFile)) {
            return [];
        }

        $lines = file($ignoreFile);
        if ($lines === false) {
            throw new Exception('Unable to load file to array');
        }

        $patterns = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            if (str_starts_with($line, '#')) {
                continue;
            }

            $patterns[] = self::preparePattern($line);
        }

        return $patterns;
    }
}
