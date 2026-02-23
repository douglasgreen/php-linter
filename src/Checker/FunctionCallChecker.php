<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Checker;

use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;

class FunctionCallChecker extends NodeChecker
{
    /** @var list<string> */
    protected const DEBUG_FUNCTIONS = [
        'debug_print_backtrace',
        'debug_zval_dump',
        'print_r',
        'var_dump',
    ];

    /**
     * @return array<string, bool>
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
