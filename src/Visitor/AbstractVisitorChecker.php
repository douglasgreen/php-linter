<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Visitor;

use DouglasGreen\PhpLinter\IssueHolderTrait;
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
    use IssueHolderTrait;

    /**
     * Check a node and store issues for later retrieval.
     *
     * @param Node $node The node to check.
     */
    abstract public function checkNode(Node $node): void;
}
