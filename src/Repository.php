<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter;

use Exception;

/**
 * Interacts with the Git repository to list files and determine their types.
 *
 * @package DouglasGreen\PhpLinter
 *
 * @since 1.0.0
 */
class Repository
{
    use IssueHolderTrait;

    /**
     * List of issues found in the repository.
     *
     * @var array<string, bool>
     */
    protected array $issues = [];

    /**
     * List of files in the repository.
     *
     * @var list<string>
     */
    protected readonly array $files;

    /**
     * Constructs a new Repository instance.
     *
     * @throws Exception If Git is not installed or the directory is not a repository.
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
     * Returns the list of PHP files in the repository.
     *
     * @return list<string> The list of PHP file paths.
     */
    public function getPhpFiles(): array
    {
        $matches = [];
        foreach ($this->files as $file) {
            if (static::getFileType($file) === 'php') {
                $matches[] = $file;
            }
        }

        return $matches;
    }

    /**
     * Prints the list of issues found in the repository.
     */
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

    /**
     * Determines the file type based on the extension.
     *
     * @param string $extension The file extension.
     *
     * @return string|null The internal file type or null if unknown.
     */
    protected static function getExtensionType(string $extension): ?string
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
     * Determines the file type using the 'file' system command.
     *
     * @param string $path The file path.
     *
     * @return string|null The internal file type or null if unknown.
     */
    protected static function getTypeFromFileCommand(string $path): ?string
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

    /**
     * Determines the type of a file based on extension, shebang, or file command.
     *
     * @param string $path The file path.
     *
     * @return string|null The internal file type or null if unknown.
     */
    protected static function getFileType(string $path): ?string
    {
        // 1. Check extension
        if (preg_match('/\.(\w+)$/', $path, $match)) {
            $type = static::getExtensionType($match[1]);
            if ($type !== null) {
                return $type;
            }
        }

        // 2. Check shebang
        $fileHandle = fopen($path, 'r');
        if ($fileHandle !== false) {
            $line = fgets($fileHandle);
            fclose($fileHandle);

            if ($line !== false && preg_match('/^#!.*\b(\w+)$/', rtrim($line), $match)) {
                $type = static::getExtensionType($match[1]);
                if ($type !== null) {
                    return $type;
                }
            }
        }

        // 3. Fallback to file command
        return static::getTypeFromFileCommand($path);
    }
}
