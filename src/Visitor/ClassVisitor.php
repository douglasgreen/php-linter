<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Visitor;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;

/**
 * Visit classes and traits, but traits don't have $attribs.
 */
class ClassVisitor extends VisitorChecker
{
    /** @var array<string, array{visibility: string, static: bool, used: bool}> */
    protected array $methods = [];

    /** @var array<string, array{visibility: string, static: bool, used: bool}> */
    protected array $properties = [];

    /** @var array<string, bool> */
    protected array $usedMethodNames = [];

    /** @var array<string, bool> */
    protected array $usedPropertyNames = [];

    /**
     * @param array<string, bool> $attribs
     */
    public function __construct(
        protected readonly ?string $className,
        protected readonly array $attribs = [],
    ) {}

    /**
     * Check the class when we have completely traversed it.
     *
     * Call this function in leaveNode().
     */
    public function checkClass(): void
    {
        // Reconcile the recorded usages with the definitions before performing checks.
        foreach (array_keys($this->usedPropertyNames) as $name) {
            if (isset($this->properties[$name])) {
                $this->properties[$name]['used'] = true;
            }
        }

        foreach (array_keys($this->usedMethodNames) as $name) {
            if (isset($this->methods[$name])) {
                $this->methods[$name]['used'] = true;
            }
        }

        $visibilities = [];
        foreach ($this->properties as $propName => $prop) {
            $visibilities[$propName] = $prop['visibility'];
            if ($prop['used']) {
                continue;
            }

            $type = $prop['static'] ? 'static' : 'non-static';

            if ($prop['visibility'] === 'private') {
                $issue = sprintf(
                    'Remove unused private %s property %s to reduce dead code.',
                    $type,
                    $propName,
                );
                $this->issues[$issue] = true;
            } elseif ($prop['visibility'] === 'public') {
                $issue = sprintf('Change public property %s to private or protected to improve encapsulation.', $propName);
                $this->issues[$issue] = true;
            }
        }

        $this->checkVisibilityOrder($visibilities, 'Property');
        $visibilities = [];
        foreach ($this->methods as $methodName => $method) {
            $visibilities[$methodName] = $method['visibility'];
            if ($method['visibility'] === 'private' && ! $method['used']) {
                $type = $method['static'] ? 'static' : 'non-static';
                $className = $this->className ?? '<anonymous>';
                $issue = sprintf(
                    'Remove unused private %s method %s::%s() to reduce dead code.',
                    $type,
                    $className,
                    $methodName,
                );
                $this->issues[$issue] = true;
            }
        }

        $this->checkVisibilityOrder($visibilities, 'Method');
    }

    public function checkNode(Node $node): void
    {
        if ($node instanceof Property) {
            foreach ($node->props as $prop) {
                $propName = $prop->name->toString();
                $visibility = $this->getVisibility($node);
                $this->properties[$propName] = [
                    'visibility' => $visibility,
                    'static' => $node->isStatic(),
                    'used' => false,
                ];
            }

            if ($this->methods !== []) {
                $this->addIssue('Move all properties above all methods to follow standard code organization.');
            }
        }

        if ($node instanceof PropertyFetch || $node instanceof StaticPropertyFetch) {
            $propName = $this->getPropertyName($node);
            if ($propName !== null) {
                $this->trackPropertyUsage($propName);
            }
        }

        if ($node instanceof MethodCall || $node instanceof StaticCall) {
            $methodName = $this->getMethodName($node);
            if ($methodName !== null) {
                $this->trackMethodUsage($methodName);
            }
        }

        if ($node instanceof ClassMethod) {
            $methodName = $node->name->toString();
            $visibility = $this->getVisibility($node);
            $this->methods[$methodName] = [
                'visibility' => $visibility,
                'static' => $node->isStatic(),
                'used' => false,
            ];

            // Check for PHP 4 style constructors.
            if (strcasecmp($methodName, (string) $this->className) === 0) {
                $this->addIssue(
                    sprintf(
                        'Rename method %s() to __construct() in class %s to use modern PHP constructor syntax.',
                        $methodName,
                        $this->className,
                    ),
                );
            }
        }
    }

    /**
     * Check if visibility order is public, then protected, then private.
     *
     * @param array<string, string> $visibilities
     */
    public function checkVisibilityOrder(array $visibilities, string $type): void
    {
        $hasPublic = false;
        $hasProtected = false;
        $hasPrivate = false;
        $badOrder = null;
        foreach ($visibilities as $name => $visibility) {
            if ($visibility === 'public') {
                $hasPublic = true;
                if ($hasProtected || $hasPrivate) {
                    $badOrder = $name;
                    break;
                }
            }

            if ($visibility === 'protected') {
                $hasProtected = true;
                if ($hasPrivate) {
                    $badOrder = $name;
                    break;
                }
            }

            if ($visibility === 'private') {
                $hasPrivate = true;
            }
        }

        if ($badOrder !== null) {
            $this->addIssue(
                sprintf(
                    'Reorder %ss to place public members first, followed by protected, then private, correcting position of %s.',
                    $type,
                    $badOrder,
                ),
            );
        }
    }

    /**
     * @return array<string, array{visibility: string, static: bool, used: bool}>
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    protected static function getMethodName(MethodCall|StaticCall $node): ?string
    {
        if ($node->name instanceof Identifier) {
            return $node->name->toString();
        }

        // Dynamic method name, can't track
        return null;
    }

    protected static function getPropertyName(Node $node): ?string
    {
        if ($node instanceof PropertyFetch) {
            if ($node->name instanceof Identifier) {
                return $node->name->toString();
            }

            if ($node->name instanceof Variable && is_string($node->name->name)) {
                return $node->name->name;
            }
        } elseif ($node instanceof StaticPropertyFetch) {
            if ($node->name instanceof Identifier) {
                return $node->name->toString();
            }
        }

        // Dynamic property name, can't track
        return null;
    }

    protected static function getVisibility(ClassMethod|Property $node): string
    {
        if ($node->isPublic()) {
            return 'public';
        }

        if ($node->isProtected()) {
            return 'protected';
        }

        if ($node->isPrivate()) {
            return 'private';
        }

        // Default visibility
        return 'public';
    }

    protected function trackMethodUsage(string $methodName): void
    {
        // Store the name blindly - we don't care if it exists yet.
        $this->usedMethodNames[$methodName] = true;
    }

    protected function trackPropertyUsage(string $propName): void
    {
        // Store the name blindly.
        $this->usedPropertyNames[$propName] = true;
    }
}
