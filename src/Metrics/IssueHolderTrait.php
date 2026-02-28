<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Metrics;

/**
 * Provides issue tracking capabilities to classes.
 *
 * Maintains an internal list of unique issues identified during checks.
 *
 * @package DouglasGreen\PhpLinter\Metrics
 *
 * @since 1.0.0
 *
 * @internal
 */
trait IssueHolderTrait
{
    /**
     * Internal storage for issues, keyed by issue string to ensure uniqueness.
     *
     * @var array<string, bool>
     */
    protected array $issues = [];

    /**
     * Returns all unique issues found.
     *
     * @return array<string, bool> Map of issue strings to true.
     */
    public function getIssues(): array
    {
        return $this->issues;
    }

    /**
     * Checks if any issues have been recorded.
     *
     * @return bool True if issues exist, false otherwise.
     */
    public function hasIssues(): bool
    {
        return $this->issues !== [];
    }

    /**
     * Adds a single issue to the collection.
     *
     * @param string $issue The issue description.
     */
    protected function addIssue(string $issue): void
    {
        $this->issues[$issue] = true;
    }

    /**
     * Merges multiple issues into the collection.
     *
     * @param array<string, bool> $issues Map of issues to merge.
     */
    protected function addIssues(array $issues): void
    {
        $this->issues = array_merge($this->issues, $issues);
    }
}
