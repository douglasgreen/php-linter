<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter;

use Exception;

class Repository
{
    use IssueHolder;

    /** @var array<string, bool> */
    protected array $issues = [];

    /** @var list<string> */
    protected readonly array $files;

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
     * Get the type of a file based on its extension, shebang, or file command.
     */
    protected function getFileType(string $path): ?string
    {
        // 1. Check extension
        if (preg_match('/\.(\w+)$/', $path, $match)) {
            $type = $this->getExtensionType($match[1]);
            if ($type !== null) {
                return $type;
            }
        }

        // 2. Check shebang
        $fileHandle = @fopen($path, 'r');
        if ($fileHandle !== false) {
            $line = fgets($fileHandle);
            fclose($fileHandle);

            if ($line !== false && preg_match('/^#!.*\b(\w+)$/', rtrim($line), $match)) {
                $type = $this->getExtensionType($match[1]);
                if ($type !== null) {
                    return $type;
                }
            }
        }

        // 3. Fallback to file command
        return $this->getTypeFromFileCommand($path);
    }

    protected function getTypeFromFileCommand(string $path): ?string
    {
        $command = sprintf('file -b %s', escapeshellarg($path));
        exec($command, $output, $returnCode);

        if ($returnCode !== 0 || ! isset($output[0])) {
            return null;
        }

        $fileInfo = $output[0];

        // Map file output to internal types
        if (stripos($fileInfo, 'PHP') !== false) {
            return 'php';
        }

        if (stripos($fileInfo, 'XML') !== false) {
            return 'xml';
        }

        if (stripos($fileInfo, 'JSON') !== false) {
            return 'json';
        }

        if (stripos($fileInfo, 'shell script') !== false) {
            return 'bash';
        }

        if (stripos($fileInfo, 'Python') !== false) {
            return 'python';
        }

        // Check for images
        if (stripos($fileInfo, 'image') !== false) {
            return 'images';
        }

        return null;
    }
}
