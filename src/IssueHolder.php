<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter;

/**
 * Provides functionality to hold and manage a list of unique issues.
 * Uses singleton pattern to share issues across all classes.
 *
 * @package DouglasGreen\PhpLinter
 *
 * @since 1.0.0
 */
class IssueHolder
{
    private static ?self $instance = null;

    /**
     * List of unique issues encountered.
     *
     * @var array<string, bool>
     */
    private array $issues = [];

    /**
     * List of issues to ignore.
     *
     * @var list<string>
     */
    private array $ignoreIssues = [];

    private function __construct()
    {
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Sets the list of issues to ignore globally.
     *
     * @param list<string> $ignoreIssues List of issue strings to ignore.
     */
    public function setIgnoreIssues(array $ignoreIssues): void
    {
        $this->ignoreIssues = $ignoreIssues;
    }

    /**
     * Returns the list of issues.
     *
     * @return array<string, bool> The list of issues.
     */
    public function getIssues(): array
    {
        return $this->issues;
    }

    /**
     * Checks if there are any issues.
     *
     * @return bool True if there are issues, false otherwise.
     */
    public function hasIssues(): bool
    {
        return $this->issues !== [];
    }

    /**
     * Adds a single issue to the list.
     *
     * @param string $issue The issue description.
     */
    public function addIssue(string $issue): void
    {
        // Check if this issue should be ignored
        foreach ($this->ignoreIssues as $ignorePattern) {
            if ($issue === $ignorePattern) {
                return;
            }
        }

        $this->issues[$issue] = true;
    }

    /**
     * Adds multiple issues to the list.
     *
     * @param array<string, bool> $issues The issues to add.
     */
    public function addIssues(array $issues): void
    {
        foreach (array_keys($issues) as $issue) {
            $this->addIssue($issue);
        }
    }

    /**
     * Clears all issues.
     */
    public function clearIssues(): void
    {
        $this->issues = [];
    }
}

