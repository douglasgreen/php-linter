<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter;

/**
 * Hold an array of issues.
 */
trait IssueHolder
{
    /**
     * @var array<string, bool>
     */
    protected array $issues = [];

    /**
     * @return array<string, bool>
     */
    public function getIssues(): array
    {
        return $this->issues;
    }

    public function hasIssues(): bool
    {
        return $this->issues !== [];
    }

    protected function addIssue(string $issue): void
    {
        $this->issues[$issue] = true;
    }

    /**
     * @param array<string, bool> $issues
     */
    protected function addIssues(array $issues): void
    {
        $this->issues = array_merge($this->issues, $issues);
    }
}
