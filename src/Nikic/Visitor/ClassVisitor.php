<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Nikic\Visitor;

use PhpParser\Node;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;

class ClassVisitor extends VisitorChecker
{
    /**
     * @var array<string, array{visibility: string, static: bool, used: bool}>
     */
    protected array $methods = [];

    /**
     * @var array<string, array{visibility: string, static: bool, used: bool}>
     */
    protected array $properties = [];

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
        }

        if ($node instanceof PropertyFetch || $node instanceof StaticPropertyFetch) {
            $propName = $this->getPropertyName($node);
            if ($propName !== null) {
                $this->trackPropertyUsage($propName);
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
        }
    }

    /**
     * @return array<string, bool>
     */
    public function getIssues(): array
    {
        foreach ($this->properties as $propName => $propInfo) {
            if ($propInfo['used']) {
                continue;
            }

            $type = $propInfo['static'] ? 'static' : 'non-static';

            if ($propInfo['visibility'] === 'private') {
                $issue = sprintf(
                    'Private %s property %s is not used within the class.',
                    $type,
                    $propName
                );
                $this->issues[$issue] = true;
            } elseif ($propInfo['visibility'] === 'public') {
                $issue = sprintf('Avoid using public properties like %s', $propName);
                $this->issues[$issue] = true;
            }
        }

        return $this->issues;
    }

    /**
     * @return array<string, array{visibility: string, static: bool, used: bool}>
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    protected function getPropertyName(Node $node): ?string
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

    protected function getVisibility(ClassMethod|Property $node): string
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

    protected function trackPropertyUsage(string $propName): void
    {
        if (isset($this->properties[$propName])) {
            $this->properties[$propName]['used'] = true;
        }
    }
}
