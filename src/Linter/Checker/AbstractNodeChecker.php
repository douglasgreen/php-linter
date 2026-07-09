<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Linter\Checker;

use DouglasGreen\PhpLinter\IssueHolder;
use PhpParser\Node;

/**
 * Base class for checking individual PHP AST nodes.
 *
 * Provides a common interface and issue collection mechanism for specific
 * node validation rules.
 *
 * @internal
 */
class AbstractNodeChecker
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
     * Adds a single issue to the list.
     *
     * @param string $issue The issue description.
     */
    protected function addIssue(string $issue): void
    {
        $this->issueHolder->addIssue($issue);
    }

    /**
     * Returns the list of issues found.
     *
     * @return array<string, bool> The list of issues.
     */
    protected function getIssues(): array
    {
        return [];
    }
}
