<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Linter\Checker;

use PhpParser\Node\Expr\ErrorSuppress;

/**
 * Checks for usage of undesirable operators.
 *
 * Specifically targets the error suppression operator (@).
 *
 * @package DouglasGreen\PhpLinter\Checker
 *
 * @internal
 *
 * @since 1.0.0
 */
class OperatorChecker extends AbstractNodeChecker
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
