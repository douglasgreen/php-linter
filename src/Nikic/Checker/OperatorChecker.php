<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Nikic\Checker;

use PhpParser\Node\Expr\ErrorSuppress;

class OperatorChecker extends NodeChecker
{
    /**
     * @return array<string, bool>
     */
    public function check(): array
    {
        if ($this->node instanceof ErrorSuppress) {
            $this->addIssue('Error suppression operator @ found');
        }

        return $this->getIssues();
    }
}
