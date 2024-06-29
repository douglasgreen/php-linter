<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Nikic\Checker;

use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\Instanceof_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Eval_;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Goto_;
use PhpParser\Node\Stmt\If_;

class ExpressionChecker extends NodeChecker
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

        // Check for fully-qualified names.
        if ($this->node instanceof Name && $this->node->isFullyQualified()) {
            $this->addQualifiedName($this->node->toString());
        } elseif ($this->node instanceof New_) {
            $this->handleNewExpression($this->node);
        } elseif ($this->node instanceof Instanceof_) {
            $this->handleInstanceofExpression($this->node);
        } elseif ($this->node instanceof StaticCall || $this->node instanceof StaticPropertyFetch) {
            $this->handleStaticExpression($this->node);
        } elseif ($this->node instanceof ClassLike) {
            $this->handleClassLike($this->node);
        } elseif ($this->node instanceof FuncCall) {
            $this->handleFuncCall($this->node);
        } elseif ($this->node instanceof ConstFetch) {
            $this->handleConstFetch($this->node);
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
        $this->qualifiedNames[$name] = true;
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

    protected function handleClassLike(ClassLike $classLike): void
    {
        if ($classLike->namespacedName !== null) {
            $this->addQualifiedName($classLike->namespacedName->toString());
        }
    }

    protected function handleConstFetch(ConstFetch $constFetch): void
    {
        if ($constFetch->name instanceof Name && $constFetch->name->isFullyQualified()) {
            $this->addQualifiedName($constFetch->name->toString());
        }
    }

    protected function handleFuncCall(FuncCall $funcCall): void
    {
        if ($funcCall->name instanceof Name && $funcCall->name->isFullyQualified()) {
            $this->addQualifiedName($funcCall->name->toString());
        }
    }

    protected function handleInstanceofExpression(Instanceof_ $instanceof): void
    {
        if ($instanceof->class instanceof Name && $instanceof->class->isFullyQualified()) {
            $this->addQualifiedName($instanceof->class->toString());
        }
    }

    protected function handleNewExpression(New_ $new): void
    {
        if ($new->class instanceof Name && $new->class->isFullyQualified()) {
            $this->addQualifiedName($new->class->toString());
        } elseif ($new->class instanceof ClassConstFetch) {
            if ($new->class->class instanceof Name && $new->class->class->isFullyQualified()) {
                $this->addQualifiedName($new->class->class->toString());
            }
        } elseif ($new->class instanceof String_) {
            // Handle cases where the class name is a string literal
            $name = $new->class->value;
            if (str_contains($name, '\\')) {
                $this->addQualifiedName($new->class->value);
            }
        }
    }

    protected function handleStaticExpression(StaticCall|StaticPropertyFetch $node): void
    {
        if ($node->class instanceof Name && $node->class->isFullyQualified()) {
            $this->addQualifiedName($node->class->toString());
        }
    }
}