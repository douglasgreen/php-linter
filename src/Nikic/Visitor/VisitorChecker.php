<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Nikic\Visitor;

use DouglasGreen\PhpLinter\Nikic\IssueHolder;
use PhpParser\Node;

/**
 * Visiter checker checks each node inside a structure like a class or function.
 *
 * It checks multiple nodes and accumulates information.
 *
 * See also NodeChecker.
 */
abstract class VisitorChecker
{
    use IssueHolder;

    /**
     * Check a node and store issues for later retrieval.
     */
    abstract public function checkNode(Node $node): void;
}
