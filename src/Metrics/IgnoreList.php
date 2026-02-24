<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Metrics;

class IgnoreList
{
    public const IGNORE_FILE = '.phplintignore';

    /** @var list<string> */
    protected readonly array $ignorePatterns;

    public function __construct(string $currentDir)
    {
        $ignoreFile = $currentDir . DIRECTORY_SEPARATOR . self::IGNORE_FILE;
        $this->ignorePatterns = static::loadIgnoreFile($ignoreFile);
    }

    public function shouldIgnore(string $filePath): bool
    {
        foreach ($this->ignorePatterns as $ignorePattern) {
            if (preg_match($ignorePattern, $filePath) === 1) {
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
