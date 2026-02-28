<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Checker;

use DouglasGreen\PhpLinter\IssueHolderTrait;
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
    use IssueHolderTrait;

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
}
