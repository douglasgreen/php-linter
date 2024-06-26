<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Nikic;

use PhpParser\Node\Expr\Eval_;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Goto_;

class ExpressionChecker extends BaseChecker
{
    /**
     * @var list<string>
     */
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
                $this->addIssue('Debug function found: ' . $functionName);
            }
        }

        if ($this->node instanceof Eval_) {
            $this->addIssue('Avoid using eval() because it is a security risk');
        }

        if ($this->node instanceof Goto_) {
            $this->addIssue('Use structured programming instead of goto statements');
        }

        return $this->getIssues();
    }
}
