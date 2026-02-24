<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Checker;

use PhpParser\Node\Expr\Exit_;

/**
 * Checks for constructs that disrupt local scope control flow.
 *
 * Detects usage of exit/die expressions.
 *
 * @package DouglasGreen\PhpLinter\Checker
 *
 * @since 1.0.0
 *
 * @internal
 */
class LocalScopeChecker extends NodeChecker
{
    /**
     * Checks for exit or die expressions.
     *
     * @return array<string, bool> List of issues found.
     */
    public function check(): array
    {
        // Check if the given node is an instance of a PHP exit expression (exit or die).
        if ($this->node instanceof Exit_) {
            $kind = $this->node->getAttribute('kind');
            $name = $kind === Exit_::KIND_EXIT ? 'exit' : 'die';
            $this->addIssue(
                sprintf("Replace the '%s' expression with an exception throw to allow proper error handling.", $name),
            );
        }

        return $this->getIssues();
    }
}
