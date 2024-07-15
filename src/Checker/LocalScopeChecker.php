<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Nikic\Checker;

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
                'Exit expression found in local scope, use exceptions instead: ' . $name,
            );
        }

        return $this->getIssues();
    }
}
