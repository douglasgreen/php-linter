<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Checker;

use PhpParser\Node\Expr\Exit_;

/**
 * Check rules that only apply in local scope.
 */
class LocalScopeChecker extends NodeChecker
{
    /**
     * @return array<string, bool>
     */
    public function check(): array
    {
        // Check if the given node is an instance of a PHP exit expression (exit or die).
        if ($this->node instanceof Exit_) {
            $kind = $this->node->getAttribute('kind');
            $name = $kind === Exit_::KIND_EXIT ? 'exit' : 'die';
            $this->addIssue(
                sprintf("Replace the '%s' expression with an exception throw to allow proper error handling.", $name),
            );
        }

        return $this->getIssues();
    }
}
