<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter;

/**
 * Handles loading and parsing of php-linter.json configuration file.
 *
 * @package DouglasGreen\PhpLinter
 *
 * @since 1.0.0
 */
class Config
{
    /**
     * List of issue strings to ignore.
     *
     * @var list<string>
     */
    protected readonly array $ignoreIssues;

    /**
     * Constructs a new Config instance by loading php-linter.json if it exists.
     *
     * @param string $currentDir The project root directory.
     */
    public function __construct(string $currentDir)
    {
        $configFile = $currentDir . DIRECTORY_SEPARATOR . 'php-linter.json';

        if (! file_exists($configFile)) {
            $this->ignoreIssues = [];
            return;
        }

        $content = file_get_contents($configFile);
        if ($content === false) {
            $this->ignoreIssues = [];
            return;
        }

        $data = json_decode($content, true);
        if (! is_array($data) || ! isset($data['ignoreIssues']) || ! is_array($data['ignoreIssues'])) {
            $this->ignoreIssues = [];
            return;
        }

        $this->ignoreIssues = array_filter($data['ignoreIssues'], 'is_string');
    }

    /**
     * Returns the list of issues to ignore.
     *
     * @return list<string> List of issue strings to ignore.
     */
    public function getIgnoreIssues(): array
    {
        return $this->ignoreIssues;
    }
}
