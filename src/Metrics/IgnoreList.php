<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Metrics;

/**
 * Manages the list of files to ignore based on patterns.
 *
 * Loads patterns from a `.phplintignore` file and matches file paths against them.
 *
 * @package DouglasGreen\PhpLinter\Metrics
 *
 * @since 1.0.0
 *
 * @internal
 */
class IgnoreList
{
    /**
     * The name of the ignore file.
     *
     * @var string
     */
    public const IGNORE_FILE = '.phplintignore';

    /**
     * List of compiled regex patterns derived from the ignore file.
     *
     * @var list<string>
     */
    protected readonly array $ignorePatterns;

    /**
     * Initializes the IgnoreList by loading patterns from the ignore file.
     *
     * @param string $currentDir The directory where the ignore file is located.
     */
    public function __construct(string $currentDir)
    {
        $ignoreFile = $currentDir . DIRECTORY_SEPARATOR . self::IGNORE_FILE;
        $this->ignorePatterns = static::loadIgnoreFile($ignoreFile);
    }

    /**
     * Checks if a file path should be ignored.
     *
     * @param string $filePath The file path to check.
     *
     * @return bool True if the file matches an ignore pattern, false otherwise.
     */
    public function shouldIgnore(string $filePath): bool
    {
        foreach ($this->ignorePatterns as $ignorePattern) {
            if (preg_match($ignorePattern, $filePath) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Converts a glob-like pattern to a regex pattern.
     *
     * @param string $pattern The glob pattern (e.g., "vendor/*").
     *
     * @return string The compiled regex pattern (e.g., "#^vendor/.*#").
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
     * Loads and parses the ignore file into a list of regex patterns.
     *
     * @param string $ignoreFile The path to the ignore file.
     *
     * @return list<string> List of compiled regex patterns.
     */
    protected static function loadIgnoreFile(string $ignoreFile): array
    {
        if (! file_exists($ignoreFile)) {
            return [];
        }

        $lines = file($ignoreFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return [];
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
