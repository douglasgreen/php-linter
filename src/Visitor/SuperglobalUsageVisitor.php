<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Visitor;

use DouglasGreen\PhpLinter\IssueHolderTrait;
use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeVisitorAbstract;

/**
 * Enforces encapsulation of superglobal access.
 *
 * Detects direct access to PHP superglobals and flags usage outside of
 * allowed contexts (Controllers, Middleware, or global scope).
 *
 * @package DouglasGreen\PhpLinter\Visitor
 *
 * @since 1.0.0
 *
 * @internal
 */
class SuperglobalUsageVisitor extends NodeVisitorAbstract
{
    use IssueHolderTrait;

    /**
     * Stack of class names currently being traversed.
     *
     * @var array<int, string>
     */
    protected array $classStack = [];

    /** Depth within function or method calls. */
    protected int $functionDepth = 0;

    /**
     * List of PHP superglobals.
     *
     * @var list<string>
     */
    protected array $superglobals = [
        '_GET',
        '_POST',
        '_SESSION',
        '_COOKIE',
        '_FILES',
        '_SERVER',
        '_ENV',
        '_REQUEST',
    ];

    /**
     * Class suffixes that are allowed to use superglobals.
     *
     * @var list<string>
     */
    protected array $allowedSuffixes = [
        'Controller',
        'Middleware',
    ];

    /**
     * Enters a node to track class/function context and check for superglobals.
     *
     * @param Node $node The node being entered.
     */
    public function enterNode(Node $node): null
    {
        // 1. Track class context
        if ($node instanceof Class_) {
            // Get the short name of the class (null for anonymous classes)
            $this->classStack[] = $node->name instanceof Identifier ? $node->name->toString() : 'Anonymous';
        }

        // 2. Track function/method depth to distinguish global scope
        if ($node instanceof FunctionLike) {
            $this->functionDepth++;
        }

        // 3. Detect superglobal usage
        if ($node instanceof Variable && is_string($node->name) && in_array($node->name, $this->superglobals, true) && ! $this->isAllowedContext()) {
            $context = $this->getContextName();
            $this->addIssue(
                sprintf(
                    'Move superglobal $%s access out of %s. Superglobals should only be accessed in the global scope or within classes ending in Controller or Middleware to ensure proper encapsulation.',
                    $node->name,
                    $context,
                ),
            );
        }

        return null;
    }

    /**
     * Leaves a node to update class/function context stacks.
     *
     * @param Node $node The node being left.
     */
    public function leaveNode(Node $node): null
    {
        // Pop class stack when exiting a class definition
        if ($node instanceof Class_) {
            array_pop($this->classStack);
        }

        // Decrement function depth when exiting a function/method
        if ($node instanceof FunctionLike) {
            $this->functionDepth--;
        }

        return null;
    }

    /**
     * Returns a string representation of the current context.
     *
     * @return string The context name (e.g., 'class MyClass', 'function scope').
     */
    protected function getContextName(): string
    {
        $currentClass = end($this->classStack);
        if ($currentClass) {
            return 'class ' . $currentClass;
        }

        if ($this->functionDepth > 0) {
            return 'function scope';
        }

        return 'global scope';
    }

    /**
     * Determines if the current context allows superglobal access.
     *
     * @return bool True if superglobals are allowed in the current context.
     */
    protected function isAllowedContext(): bool
    {
        // Global scope (outside functions and classes) is allowed
        if ($this->functionDepth === 0) {
            return true;
        }

        // Inside a function/method, check if it's an allowed class
        $currentClass = end($this->classStack);
        if (! $currentClass) {
            return false; // Function outside a class is not allowed
        }

        foreach ($this->allowedSuffixes as $suffix) {
            if (str_ends_with($currentClass, $suffix)) {
                return true;
            }
        }

        return false;
    }
}
