<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Linter\Visitor;

use DouglasGreen\PhpLinter\IssueHolder;
use PhpParser\Node;

/**
 * Abstract base class for visitor-style node checks.
 *
 * Visitor checkers analyze nodes within a structure (like a class or function)
 * and accumulate issues during traversal.
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
     * Adds a single issue to the list.
     *
     * @param string $issue The issue description.
     */
    protected function addIssue(string $issue): void
    {
        $this->issueHolder->addIssue($issue);
    }
}
