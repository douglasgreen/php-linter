<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Checker;

use PhpParser\Node\Stmt\Nop;
use PhpParser\Node\Stmt\TryCatch;

/**
 * Checks try-catch blocks for proper error handling.
 *
 * Ensures catch blocks are not empty, which would suppress errors silently.
 *
 * @package DouglasGreen\PhpLinter\Checker
 * @since 1.0.0
 * @internal
 */
class TryCatchChecker extends NodeChecker
{
    /**
     * Checks for empty catch blocks.
     *
     * @return array<string, bool> List of issues found.
     */
    public function check(): array
    {
        if (! $this->node instanceof TryCatch) {
            return [];
        }

        foreach ($this->node->catches as $catch) {
            if ($catch->stmts === [] || $catch->stmts[0] instanceof Nop) {
                $this->addIssue('Add error handling or logging to the empty catch block. Suppressing exceptions hides bugs and makes debugging difficult.');
            }
        }

        return $this->getIssues();
    }
}
