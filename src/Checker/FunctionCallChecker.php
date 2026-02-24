<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Checker;

use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;

/**
 * Checks for usage of undesirable function calls.
 *
 * Detects calls to debug functions that should not be present in production code.
 *
 * @package DouglasGreen\PhpLinter\Checker
 * @since 1.0.0
 * @internal
 */
class FunctionCallChecker extends NodeChecker
{
    /**
     * List of debug functions that should be removed in production.
     *
     * @var list<string>
     */
    protected const DEBUG_FUNCTIONS = [
        'debug_print_backtrace',
        'debug_zval_dump',
        'print_r',
        'var_dump',
    ];

    /**
     * Checks for calls to debug functions.
     *
     * @return array<string, bool> List of issues found.
     */
    public function check(): array
    {
        if ($this->node instanceof FuncCall) {
            $functionName =
                $this->node->name instanceof Name ? $this->node->name->toString() : null;
            if ($functionName === null) {
                return [];
            }

            if (in_array(strtolower($functionName), self::DEBUG_FUNCTIONS, true)) {
                $this->addIssue(sprintf("Remove call to debug function '%s' to prevent information leakage in production.", $functionName));
            }
        }

        return $this->getIssues();
    }
}
