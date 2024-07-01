<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Nikic\Visitor;

use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\Instanceof_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node;
use PhpParser\Node\Name;

class NameVisitor extends VisitorChecker
{
    /**
     * @var array<string, true>
     */
    protected array $qualifiedNames = [];

    public function checkNode(Node $node): void
    {
        // Check for fully-qualified names.
        if ($node instanceof Name && $node->isFullyQualified()) {
            $this->addQualifiedName($node->toString());
        } elseif ($node instanceof New_) {
            $this->handleNewExpression($node);
        } elseif ($node instanceof Instanceof_) {
            $this->handleInstanceofExpression($node);
        } elseif ($node instanceof StaticCall || $node instanceof StaticPropertyFetch) {
            $this->handleStaticExpression($node);
        } elseif ($node instanceof ClassLike) {
            $this->handleClassLike($node);
        }

        // Check for any Name nodes that might be part of other expressions
        $this->checkNodeForNames($node);
    }

    /**
     * @return array<string, true>
     */
    public function getQualifiedNames(): array
    {
        return $this->qualifiedNames;
    }

    protected function addQualifiedName(string $name): void
    {
        $this->qualifiedNames[$name] = true;
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
        if ($classLike->name === null) {
            return;
        }

        $this->addQualifiedName($classLike->name->toString());
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
