<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Visitor;

use DouglasGreen\PhpLinter\IssueHolder;
use PhpParser\Node;

/**
 * Abstract base class for visitor-style node checks.
 *
 * Visitor checkers analyze nodes within a structure (like a class or function)
 * and accumulate issues during traversal.
 *
 * @package DouglasGreen\PhpLinter\Visitor
 *
 * @since 1.0.0
 *
 * @internal
 */
abstract class AbstractVisitorChecker
{
    protected ?IssueHolder $issueHolder = null;

    public function __construct()
    {
        $this->issueHolder = IssueHolder::getInstance();
    }

    protected function getIssueHolder(): IssueHolder
    {
        if ($this->issueHolder === null) {
            $this->issueHolder = IssueHolder::getInstance();
        }
        return $this->issueHolder;
    }

    /**
     * Gets the list of issues.
     *
     * @return array<string, bool> The list of issues.
     */
    public function getIssues(): array
    {
        return $this->getIssueHolder()->getIssues();
    }

    /**
     * Checks if there are any issues.
     *
     * @return bool True if there are issues, false otherwise.
     */
    public function hasIssues(): bool
    {
        return $this->getIssueHolder()->hasIssues();
    }

    /**
     * Check a node and store issues for later retrieval.
     *
     * @param Node $node The node to check.
     */
    abstract public function checkNode(Node $node): void;

    /**
     * Adds a single issue to the list.
     *
     * @param string $issue The issue description.
     */
    protected function addIssue(string $issue): void
    {
        $this->getIssueHolder()->addIssue($issue);
    }

    /**
     * Adds multiple issues to the list.
     *
     * @param array<string, bool> $issues The issues to add.
     */
    protected function addIssues(array $issues): void
    {
        $this->getIssueHolder()->addIssues($issues);
    }
}
