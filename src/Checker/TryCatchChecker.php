<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Checker;

use PhpParser\Node\Stmt\Nop;
use PhpParser\Node\Stmt\TryCatch;

class TryCatchChecker extends NodeChecker
{
    /**
     * @return array<string, bool>
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
