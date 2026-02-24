<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Metrics;

use Exception;

class Repository
{
    use IssueHolder;

    /** @var array<string, bool> */
    protected array $issues = [];

    /** @var list<string> */
    protected readonly array $files;

    protected readonly string $defaultBranch;

    public function __construct()
    {
        $output = [];
        $returnVar = 0;
        exec('git ls-files', $output, $returnVar);

        if ($returnVar !== 0) {
            throw new Exception(
                "Failed to execute Git command. Make sure Git is installed and you're in a Git repository.",
            );
        }

        $this->files = $output;

        // Command to get the default branch
        $command = "git remote show origin | sed -n '/HEAD branch/s/.*: //p'";

        // Execute the command
        $output = [];
        $returnVar = 0;
        exec($command, $output, $returnVar);

        // Check if the command was successful
        if ($returnVar !== 0) {
            throw new Exception(
                "Failed to execute Git command. Make sure Git is installed and you're in a Git repository.",
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
                sprintf('The default branch is "%s" but should be "main"', $this->defaultBranch),
            );
        }
    }

    /**
     * @return list<string>
     */
    public function getPhpFiles(): array
    {
        $matches = [];
        foreach ($this->files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
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
