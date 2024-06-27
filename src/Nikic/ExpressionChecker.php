<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Nikic;

use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\Eval_;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Instanceof_;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Goto_;
use PhpParser\Node\Stmt\If_;

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
     * @var array<string, true>
     */
    protected array $qualifiedNames = [];

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

        if ($this->node instanceof Name && $this->node->isFullyQualified()) {
            $this->addQualifiedName($this->node->toString());
        } elseif ($this->node instanceof New_) {
            $this->handleNewExpression($this->node);
        } elseif ($this->node instanceof Instanceof_) {
            $this->handleInstanceofExpression($this->node);
        } elseif ($this->node instanceof StaticCall || $this->node instanceof StaticPropertyFetch) {
            $this->handleStaticExpression($this->node);
        }

        // Check for any Name nodes that might be part of other expressions
        $this->checkNodeForNames($this->node);

        foreach (array_keys($this->qualifiedNames) as $qualifiedName) {
            $this->addIssue('Import external classes with use statement: ' . $qualifiedName);
        }

        return $this->getIssues();
    }

    protected function addQualifiedName(string $name): void
    {
        if (str_contains($name, '\\')) {
            $this->qualifiedNames[$name] = true;
        }
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

    protected function checkNodeForNames(Node $node): void
    {
        foreach ($node->getSubNodeNames() as $subNodeName) {
            $subNode = $node->{$subNodeName};
            if ($subNode instanceof Name && $subNode->isFullyQualified()) {
                $this->addQualifiedName($subNode->toString());
            } elseif (is_array($subNode)) {
                foreach ($subNode as $arrayItem) {
                    if ($arrayItem instanceof Node) {
                        $this->checkNodeForNames($arrayItem);
                    }
                }
            }
        }
    }

    protected function handleNewExpression(New_ $new): void
    {
        if ($new->class instanceof Name) {
            $this->addQualifiedName($new->class->toString());
        } elseif ($new->class instanceof ClassConstFetch) {
            if ($new->class->class instanceof Name) {
                $this->addQualifiedName($new->class->class->toString());
            }
        } elseif ($new->class instanceof String_) {
            // Handle cases where the class name is a string literal
            $this->addQualifiedName($new->class->value);
        }
    }

    protected function handleInstanceofExpression(Instanceof_ $instanceof): void
    {
        if ($instanceof->class instanceof Name) {
            $this->addQualifiedName($instanceof->class->toString());
        }
    }

    protected function handleStaticExpression(StaticCall|StaticPropertyFetch $node): void
    {
        if ($node->class instanceof Name) {
            $this->addQualifiedName($node->class->toString());
        }
    }
}
