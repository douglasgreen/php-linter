<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Checker;

use PhpParser\Node\Expr\ErrorSuppress;

class OperatorChecker extends NodeChecker
{
    /**
     * @return array<string, bool>
     */
    public function check(): array
    {
        if ($this->node instanceof ErrorSuppress) {
            $this->addIssue('Remove the error suppression operator "@". Suppressing errors hides potential bugs and prevents proper error handling.');
        }

        return $this->getIssues();
    }
}
