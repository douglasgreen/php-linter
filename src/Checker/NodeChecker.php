<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Nikic\Checker;

use DouglasGreen\PhpLinter\IssueHolder;
use PhpParser\Node;

/**
 * Node checker checks a single node.
 *
 * It checks a single node which it takes in the constructor.
 *
 * See also VisitorChecker.
 */
abstract class NodeChecker
{
    use IssueHolder;

    public function __construct(
        protected readonly Node $node
    ) {}

    /**
     * Do the check and return a list of issues.
     * @return array<string, bool>
     */
    abstract public function check(): array;
}
