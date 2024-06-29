<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Nikic;

use PhpParser\Node;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\Property;

class ClassVisitor
{
    /**
     * @var array<string, bool>
     */
    protected array $issues = [];

    /**
     * @var array<string, array<string, bool>>
     */
    protected array $privateProperties = [];

    public function checkNode(Node $node): void
    {
        if ($node instanceof Property && $node->isPrivate()) {
            foreach ($node->props as $prop) {
                $this->privateProperties[$prop->name->toString()] = [
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

    protected function trackPropertyUsage(string $propName): void
    {
        if (isset($this->privateProperties[$propName])) {
            $this->privateProperties[$propName]['used'] = true;
        }
    }

    /**
     * @return array<string, bool>
     */
    public function getIssues(): array
    {
        foreach ($this->privateProperties as $propName => $propInfo) {
            if ($propInfo['used']) {
                continue;
            }

            $type = $propInfo['static'] ? 'static' : 'non-static';
            $issue = sprintf(
                'Private %s property %s is not used within the class.',
                $type,
                $propName
            );
            $this->issues[$issue] = true;
        }

        return $this->issues;
    }
}
