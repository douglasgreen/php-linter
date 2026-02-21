<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Visitor;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeVisitorAbstract;

class SuperglobalUsageVisitor extends NodeVisitorAbstract
{
    use IssueHolder;

    /** @var string[] */
    protected array $classStack = [];

    /** @var string[] */
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

    /** @var string[] */
    protected array $allowedSuffixes = [
        'Controller',
        'Middleware',
    ];

    public function enterNode(Node $node): null
    {
        // 1. Track class context
        if ($node instanceof Class_) {
            // Get the short name of the class (null for anonymous classes)
            $this->classStack[] = $node->name instanceof Identifier ? $node->name->toString() : 'Anonymous';
        }

        // 2. Detect superglobal usage
        if ($node instanceof Variable && is_string($node->name) && in_array($node->name, $this->superglobals, true) && ! $this->isInsideAllowedClass()) {
            $this->addIssue(
                sprintf(
                    'Superglobal $%s used in forbidden context (%s)',
                    $node->name,
                    end($this->classStack) ?: 'Global Scope',
                ),
            );
        }

        return null;
    }

    public function leaveNode(Node $node): null
    {
        // Pop class stack when exiting a class definition
        if ($node instanceof Class_) {
            array_pop($this->classStack);
        }

        return null;
    }

    protected function isInsideAllowedClass(): bool
    {
        $currentClass = end($this->classStack);
        if (! $currentClass) {
            return false; // Global scope is not allowed
        }

        foreach ($this->allowedSuffixes as $suffix) {
            if (str_ends_with($currentClass, $suffix)) {
                return true;
            }
        }

        return false;
    }
}
