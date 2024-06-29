<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Nikic\Checker;

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
            if (empty($catch->stmts) || $catch->stmts[0] instanceof Nop) {
                $this->addIssue('Empty catch block found suppressing errors');
            }
        }

        return $this->getIssues();
    }
}
