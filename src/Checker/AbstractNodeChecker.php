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
     * Initializes the checker with the target node and issue holder.
     *
     * @param Node $node The PHP AST node to inspect.
     * @param IssueHolder $issueHolder The issue holder for collecting issues.
     */
    public function __construct(
        protected readonly Node $node,
        protected readonly IssueHolder $issueHolder,
    ) {}

    /**
     * Performs validation checks on the associated node.
     *
     * @return array<string, bool> A map of issue messages to their status.
     */
    abstract public function check(): array;

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
