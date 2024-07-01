<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Nikic\Checker;

use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Eval_;
use PhpParser\Node\Stmt\Goto_;
use PhpParser\Node\Stmt\If_;

class ExpressionChecker extends NodeChecker
{
    /**
     * @return array<string, bool>
     */
    public function check(): array
    {
        if ($this->node instanceof If_) {
            $this->checkCondition($this->node->cond, 'if');

            foreach ($this->node->elseifs as $elseif) {
                $this->checkCondition($elseif->cond, 'elseif');
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

    protected function checkCondition(Node $condition, string $clauseType): void
    {
        if ($condition instanceof Assign) {
            $this->addIssue('Avoid assignments in if conditions');
        }

        // Recursively check subnodes
        foreach ($condition->getSubNodeNames() as $name) {
            $subNode = $condition->{$name};
            if ($subNode instanceof Node) {
                $this->checkCondition($subNode, $clauseType);
            } elseif (is_array($subNode)) {
                foreach ($subNode as $node) {
                    if ($node instanceof Node) {
                        $this->checkCondition($node, $clauseType);
                    }
                }
            }
        }
    }
}
