<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Checker;

use PhpParser\Node\Expr\ErrorSuppress;

/**
 * Checks for usage of undesirable operators.
 *
 * Specifically targets the error suppression operator (@).
 *
 * @package DouglasGreen\PhpLinter\Checker
 *
 * @since 1.0.0
 *
 * @internal
 */
class OperatorChecker extends NodeChecker
{
    /**
     * Checks for the error suppression operator (@).
     *
     * @return array<string, bool> List of issues found.
     */
    public function check(): array
    {
        if ($this->node instanceof ErrorSuppress) {
            $this->addIssue('Remove the error suppression operator "@". Suppressing errors hides potential bugs and prevents proper error handling.');
        }

        return $this->getIssues();
    }
}
