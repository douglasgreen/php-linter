<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Visitor;

use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\Instanceof_;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassLike;

/**
 * Collects fully qualified names used within a code structure.
 *
 * Tracks class names, interface names, and other references to ensure
 * proper dependency analysis and namespace usage.
 *
 * @package DouglasGreen\PhpLinter\Visitor
 *
 * @since 1.0.0
 *
 * @internal
 */
class NameVisitor extends AbstractVisitorChecker
{
    /**
     * Set of qualified names found in the code.
     *
     * @var array<string, true>
     */
    protected array $qualifiedNames = [];

    /**
     * Inspects a node for fully qualified names.
     *
     * @param Node $node The node to check.
     */
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
     * Returns the set of collected qualified names.
     *
     * @return array<string, true>
     */
    public function getQualifiedNames(): array
    {
        return $this->qualifiedNames;
    }

    /**
     * Adds a qualified name to the set.
     *
     * @param string $name The fully qualified name.
     */
    protected function addQualifiedName(string $name): void
    {
        $this->qualifiedNames[$name] = true;
    }

    /**
     * Recursively checks sub-nodes for Name instances.
     *
     * @param Node $node The node to traverse.
     */
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

    /**
     * Handles class, interface, or trait definitions.
     *
     * @param ClassLike $classLike The class-like node.
     */
    protected function handleClassLike(ClassLike $classLike): void
    {
        if (!$classLike->name instanceof Identifier) {
            return;
        }

        $this->addQualifiedName($classLike->name->toString());
    }

    /**
     * Handles instanceof expressions.
     *
     * @param Instanceof_ $instanceof The instanceof node.
     */
    protected function handleInstanceofExpression(Instanceof_ $instanceof): void
    {
        if ($instanceof->class instanceof Name && $instanceof->class->isFullyQualified()) {
            $this->addQualifiedName($instanceof->class->toString());
        }
    }

    /**
     * Handles new object instantiation.
     *
     * @param New_ $new The new expression node.
     */
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

    /**
     * Handles static method calls and property fetches.
     *
     * @param StaticCall|StaticPropertyFetch $node The static expression node.
     */
    protected function handleStaticExpression(StaticCall|StaticPropertyFetch $node): void
    {
        if ($node->class instanceof Name && $node->class->isFullyQualified()) {
            $this->addQualifiedName($node->class->toString());
        }
    }
}
