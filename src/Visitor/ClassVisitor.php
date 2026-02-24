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
 * Analyzes class and trait structures for property and method usage.
 *
 * Checks for unused private members, visibility ordering, and PHP 4 style constructors.
 *
 * @package DouglasGreen\PhpLinter\Visitor
 * @since 1.0.0
 * @internal
 */
class ClassVisitor extends VisitorChecker
{
    /**
     * Defined methods with their metadata.
     *
     * @var array<string, array{visibility: string, static: bool, used: bool}>
     */
    protected array $methods = [];

    /**
     * Defined properties with their metadata.
     *
     * @var array<string, array{visibility: string, static: bool, used: bool}>
     */
    protected array $properties = [];

    /**
     * List of method names that were called.
     *
     * @var array<string, bool>
     */
    protected array $usedMethodNames = [];

    /**
     * List of property names that were accessed.
     *
     * @var array<string, bool>
     */
    protected array $usedPropertyNames = [];

    /**
     * Initializes a new instance of the ClassVisitor.
     *
     * @param string|null $className The name of the class being visited, or null for anonymous classes.
     * @param array<string, bool> $attribs Attributes of the class (e.g., 'abstract').
     */
    public function __construct(
        protected readonly ?string $className,
        protected readonly array $attribs = [],
    ) {}

    /**
     * Performs final checks after the class/traverse has been fully traversed.
     *
     * This method should be called in leaveNode() when the class node is left.
     *
     * @return void
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

    /**
     * Inspects a node for property or method definitions and usages.
     *
     * @param Node $node The node to check.
     * @return void
     */
    public function checkNode(Node $node): void
    {
        if ($node instanceof Property) {
            foreach ($node->props as $prop) {
                $propName = $prop->name->toString();
                $visibility = static::getVisibility($node);
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
            $propName = static::getPropertyName($node);
            if ($propName !== null) {
                $this->trackPropertyUsage($propName);
            }
        }

        if ($node instanceof MethodCall || $node instanceof StaticCall) {
            $methodName = static::getMethodName($node);
            if ($methodName !== null) {
                $this->trackMethodUsage($methodName);
            }
        }

        if ($node instanceof ClassMethod) {
            $methodName = $node->name->toString();
            $visibility = static::getVisibility($node);
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
     * Validates that members are ordered by visibility: public, protected, private.
     *
     * @param array<string, string> $visibilities Map of member names to their visibility.
     * @param string $type The type of member (e.g., 'Property', 'Method').
     * @return void
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
     * Returns the list of defined methods.
     *
     * @return array<string, array{visibility: string, static: bool, used: bool}>
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * Extracts the method name from a method call node.
     *
     * @param MethodCall|StaticCall $node The method call node.
     * @return string|null The method name or null if it is dynamic.
     */
    protected static function getMethodName(MethodCall|StaticCall $node): ?string
    {
        if ($node->name instanceof Identifier) {
            return $node->name->toString();
        }

        // Dynamic method name, can't track
        return null;
    }

    /**
     * Extracts the property name from a property fetch node.
     *
     * @param Node $node The property fetch node.
     * @return string|null The property name or null if it is dynamic.
     */
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

    /**
     * Determines the visibility of a method or property node.
     *
     * @param ClassMethod|Property $node The node to inspect.
     * @return string The visibility ('public', 'protected', or 'private').
     */
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

    /**
     * Records the usage of a method.
     *
     * @param string $methodName The name of the method being used.
     * @return void
     */
    protected function trackMethodUsage(string $methodName): void
    {
        // Store the name blindly - we don't care if it exists yet.
        $this->usedMethodNames[$methodName] = true;
    }

    /**
     * Records the usage of a property.
     *
     * @param string $propName The name of the property being used.
     * @return void
     */
    protected function trackPropertyUsage(string $propName): void
    {
        // Store the name blindly.
        $this->usedPropertyNames[$propName] = true;
    }
}
