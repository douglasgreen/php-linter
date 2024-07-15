<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter;

use DouglasGreen\Utility\FileSystem\PathUtil;
use DouglasGreen\Utility\Regex\Regex;

class IgnoreList
{
    public const IGNORE_FILE = '.phplintignore';

    /**
     * @var list<string>
     */
    protected readonly array $ignorePatterns;

    public function __construct(string $currentDir)
    {
        $ignoreFile = PathUtil::addSubpath($currentDir, self::IGNORE_FILE);
        $this->ignorePatterns = $this->loadIgnoreFile($ignoreFile);
    }

    public function shouldIgnore(string $filePath): bool
    {
        foreach ($this->ignorePatterns as $ignorePattern) {
            if (Regex::hasMatch($ignorePattern, $filePath)) {
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
    protected function loadIgnoreFile(string $ignoreFile): array
    {
        if (! file_exists($ignoreFile)) {
            return [];
        }

        $lines = PathUtil::loadLines($ignoreFile);
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
