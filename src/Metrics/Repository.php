<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Metrics;

use Exception;

/**
 * Represents the Git repository context.
 *
 * Provides access to tracked files and repository metadata.
 *
 * @package DouglasGreen\PhpLinter\Metrics
 * @since 1.0.0
 * @api
 */
class Repository
{
    use IssueHolder;

    /**
     * List of files tracked by Git.
     * @var list<string>
     */
    protected readonly array $files;

    /**
     * The default branch name of the repository.
     * @var string
     */
    protected readonly string $defaultBranch;

    /**
     * Initializes the Repository by querying Git for files and default branch.
     *
     * @throws Exception If Git commands fail (e.g., not in a Git repository).
     *
     * @sideeffect Executes `git ls-files` and `git remote show origin`.
     */
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

    /**
     * Checks repository standards (e.g., default branch name).
     *
     * @return void
     */
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
     * Returns the list of PHP files tracked by Git.
     *
     * @return list<string> List of PHP file paths.
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

    /**
     * Prints any issues found during checks.
     *
     * @return void
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
}
