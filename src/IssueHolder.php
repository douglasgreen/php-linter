<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter;

/**
 * Provides functionality to hold and manage a list of unique issues.
 *
 * @package DouglasGreen\PhpLinter
 * @since 1.0.0
 */
trait IssueHolder
{
    /**
     * List of unique issues encountered.
     *
     * @var array<string, bool>
     */
    protected array $issues = [];

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
    protected function addIssue(string $issue): void
    {
        $this->issues[$issue] = true;
    }

    /**
     * Adds multiple issues to the list.
     *
     * @param array<string, bool> $issues The issues to add.
     */
    protected function addIssues(array $issues): void
    {
        $this->issues = array_merge($this->issues, $issues);
    }
}
