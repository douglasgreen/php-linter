<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Checker;

use DouglasGreen\PhpLinter\IssueHolder;
use PhpParser\Node;

/**
 * Base class for checking individual PHP AST nodes.
 *
 * Provides a common interface and issue collection mechanism for specific
 * node validation rules.
 *
 * @package DouglasGreen\PhpLinter\Checker
 *
 * @since 1.0.0
 *
 * @api
 */
abstract class AbstractNodeChecker
{
    /**
     * Initializes the checker with the target node.
     *
     * @param Node $node The PHP AST node to inspect.
     */
    public function __construct(
        protected readonly Node $node,
    ) {}

    /**
     * Performs validation checks on the associated node.
     *
     * @return array<string, bool> A map of issue messages to their status.
     */
    abstract public function check(): array;

    /**
     * Gets the list of issues.
     *
     * @return array<string, bool> The list of issues.
     */
    protected function getIssues(): array
    {
        return IssueHolder::getIssues();
    }

    /**
     * Checks if there are any issues.
     *
     * @return bool True if there are issues, false otherwise.
     */
    protected function hasIssues(): bool
    {
        return IssueHolder::hasIssues();
    }

    /**
     * Adds a single issue to the list.
     *
     * @param string $issue The issue description.
     */
    protected function addIssue(string $issue): void
    {
        IssueHolder::addIssue($issue);
    }

    /**
     * Adds multiple issues to the list.
     *
     * @param array<string, bool> $issues The issues to add.
     */
    protected function addIssues(array $issues): void
    {
        IssueHolder::addIssues($issues);
    }
}
