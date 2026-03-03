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
    /**
     * Initializes the visitor checker with an issue holder.
     *
     * @param IssueHolder $issueHolder The issue holder for collecting issues.
     */
    public function __construct(
        protected readonly IssueHolder $issueHolder,
    ) {}

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
        $this->issueHolder->addIssue($issue);
    }

    /**
     * Adds multiple issues to the list.
     *
     * @param array<string, bool> $issues The issues to add.
     */
    protected function addIssues(array $issues): void
    {
        $this->issueHolder->addIssues($issues);
    }
}
