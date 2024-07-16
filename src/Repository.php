<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter;

use Exception;

class Repository
{
    use IssueHolder;

    /**
     * @var array<string, bool>
     */
    protected array $issues = [];

    /**
     * @var list<string>
     */
    protected readonly array $files;

    protected readonly string $defaultBranch;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $gitMessage = "Failed to execute Git command. Make sure Git is installed and you're in a Git repository.";

        exec('git ls-files', $files, $returnCode);
        if ($returnCode !== 0) {
            throw new Exception($gitMessage);
        }

        $this->files = $files;

        // Command to get the default branch
        $command = "git remote show origin | sed -n '/HEAD branch/s/.*: //p'";

        // Execute the command
        exec($command, $output, $returnCode);

        // Check if the command was successful
        if ($returnCode !== 0) {
            throw new Exception($gitMessage);
        }

        // Get the branch name from the output
        $this->defaultBranch = trim($output[0]);
    }

    public function check(): void
    {
        // Check if the default branch is 'main'
        if ($this->defaultBranch !== 'main') {
            $this->addIssue(
                sprintf('The default branch is "%s" but should be "main"', $this->defaultBranch)
            );
        }
    }

    /**
     * @return mixed[]
     */
    public function getPhpFiles(): array
    {
        $matches = [];
        foreach ($this->files as $file) {
            if ($this->getFileType($file) === 'php') {
                $matches[] = $file;
            }
        }

        return $matches;
    }

    public function printIssues(): void
    {
        if (! $this->hasIssues()) {
            return;
        }

        echo '==> Git repository' . PHP_EOL;

        foreach (array_keys($this->issues) as $issue) {
            echo $issue . PHP_EOL;
        }
    }

    protected function getExtensionType(string $extension): ?string
    {
        return match ($extension) {
            'bash', 'sh' => 'bash',
            'css' => 'css',
            'csv', 'pdv', 'tsv', 'txt' => 'data',
            'jpg', 'jpeg', 'png', 'gif', 'bmp', 'tiff', 'svg', 'webp' => 'images',
            'js', 'ts' => 'js',
            'json' => 'json',
            'md' => 'md',
            'php' => 'php',
            'sql' => 'sql',
            'xml', 'xsd', 'xsl', 'xslt', 'xhtml' => 'xml',
            'yaml', 'yml' => 'yaml',
            default => null,
        };
    }

    /**
     * Get the type of a file based on its extension or other info.
     *
     * @todo Use the return type of "file" command if available.
     * Example: file -b bin/task.php
     * a /usr/bin/env php script, ASCII text executable
     */
    protected function getFileType(string $path): ?string
    {
        if (! str_contains($path, '.')) {
            $fileHandle = fopen($path, 'r');
            if ($fileHandle === false) {
                throw new Exception('Unable to open file for reading');
            }

            $line = fgets($fileHandle);
            if ($line === false) {
                return null;
            }

            if (preg_match('/^#!.*\b(\w+)$/', rtrim($line), $match)) {
                return $this->getExtensionType($match[1]);
            }
        } elseif (preg_match('/\.(\w+)$/', $path, $match)) {
            return $this->getExtensionType($match[1]);
        }

        return null;
    }
}
