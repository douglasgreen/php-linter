<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Checker;

use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Eval_;
use PhpParser\Node\Expr\Include_;
use PhpParser\Node\Stmt\Global_;
use PhpParser\Node\Stmt\Goto_;
use PhpParser\Node\Stmt\If_;

/**
 * Checks various PHP expressions and statements for best practices.
 *
 * Analyzes if conditions, eval, global, goto, and include statements.
 *
 * @package DouglasGreen\PhpLinter\Checker
 *
 * @since 1.0.0
 *
 * @internal
 */
class ExpressionChecker extends NodeChecker
{
    /**
     * Performs checks on expression and statement nodes.
     *
     * @return array<string, bool> List of issues found.
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
            $this->addIssue('Remove eval() usage to prevent code injection vulnerabilities');
        }

        if ($this->node instanceof Global_) {
            $this->addIssue('Remove the "global" keyword and pass variables as function arguments to ensure explicit dependencies');
        }

        if ($this->node instanceof Goto_) {
            $this->addIssue('Remove goto statements and refactor control flow to improve code structure');
        }

        if ($this->node instanceof Include_) {
            $type = static::getIncludeType($this->node->type);
            if ($type !== 'require_once') {
                $this->addIssue('Replace ' . $type . ' with require_once to ensure the file is loaded and halt execution on failure');
            }
        }

        return $this->getIssues();
    }

    /**
     * Maps the integer type constant of an Include_ node to a string name.
     *
     * @param int $type The Include_ type constant.
     *
     * @return string The string representation of the include type.
     */
    protected static function getIncludeType(int $type): string
    {
        return match ($type) {
            Include_::TYPE_INCLUDE => 'include',
            Include_::TYPE_INCLUDE_ONCE => 'include_once',
            Include_::TYPE_REQUIRE => 'require',
            Include_::TYPE_REQUIRE_ONCE => 'require_once',
            default => 'unknown',
        };
    } // end

    /**
     * Recursively checks a condition node for assignments.
     *
     * @param Node $condition The condition node to check.
     * @param string $clauseType The type of clause ('if', 'elseif').
     */
    protected function checkCondition(Node $condition, string $clauseType): void
    {
        if ($condition instanceof Assign) {
            $this->addIssue('Move the assignment out of the condition to avoid confusion with equality checks');
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
