<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter;

use DouglasGreen\Utility\FileSystem\PathUtil;
use DouglasGreen\Utility\Program\Command;
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

    public function __construct()
    {
        $command = new Command('git ls-files');
        $this->files = $command->run();

        // Command to get the default branch
        $command = "git remote show origin | sed -n '/HEAD branch/s/.*: //p'";

        // Execute the command
        $output = [];
        $returnVar = 0;
        exec($command, $output, $returnVar);

        // Check if the command was successful
        if ($returnVar !== 0) {
            throw new Exception(
                "Failed to execute Git command. Make sure Git is installed and you're in a Git repository."
            );
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
            if (PathUtil::getFileType($file) === 'php') {
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
}
