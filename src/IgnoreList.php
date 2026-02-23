<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter;

use Exception;

class IgnoreList
{
    public const IGNORE_FILE = '.phplintignore';

    /** @var list<string> */
    protected readonly array $ignorePatterns;

    public function __construct(string $currentDir)
    {
        $ignoreFile = static::addSubpath($currentDir, self::IGNORE_FILE);
        $this->ignorePatterns = static::loadIgnoreFile($ignoreFile);
    }

    /**
     * Add a subpath.
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

    public function shouldIgnore(string $filePath): bool
    {
        foreach ($this->ignorePatterns as $ignorePattern) {
            if (preg_match($ignorePattern, $filePath)) {
                return true;
            }
        }

        return false;
    }

    protected static function preparePattern(string $pattern): string
    {
        // Convert the ignore pattern to a regex pattern
        $pattern = preg_quote($pattern, '#');
        $pattern = str_replace('\*', '.*', $pattern);
        $pattern = str_replace('\?', '.', $pattern);
        return sprintf('#^%s#', $pattern);
    }

    /**
     * @return list<string>
     *
     * @throws Exception
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
