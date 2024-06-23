<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter;

use DouglasGreen\Utility\Regex\Regex;
use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\NodeVisitorAbstract;

class ElementVisitor extends NodeVisitorAbstract
{
    public function enterNode(Node $node): Node|int|null
    {
        $name = property_exists($node, 'name') ? (string) $node->name : '';
        if ($node instanceof Namespace_) {
            // echo 'Namespace: ' . $name . PHP_EOL;
            $parts = explode('\\', $name);
            foreach ($parts as $part) {
                if (! self::checkUpperName($part)) {
                    break;
                }
            }
        } elseif ($node instanceof Class_) {
            // echo 'Class: ' . $name . PHP_EOL;
            self::checkUpperName($name);
        } elseif ($node instanceof Interface_) {
            // echo 'Interface: ' . $name . PHP_EOL;
            self::checkUpperName($name);
        } elseif ($node instanceof Trait_) {
            // echo 'Trait: ' . $name . PHP_EOL;
            self::checkUpperName($name);
        } elseif ($node instanceof ClassMethod) {
            // echo 'Method: ' . $name . PHP_EOL;
            self::checkLowerName($name);
        } elseif ($node instanceof Function_) {
            // echo 'Function: ' . $name . PHP_EOL;
            self::checkLowerName($name);
        } elseif ($node instanceof Variable) {
            // echo 'Variable: ' . $name . PHP_EOL;
            self::checkLowerName($name);
        } elseif ($name !== '') {
            //var_dump(get_class($node), $name);
        }

        return null;
    }

    protected static function checkLowerName(string $name): bool
    {
        if (! self::isLowerCamelCase($name)) {
            echo 'Not camel case: ' . $name . PHP_EOL;
            return false;
        }

        return true;
    }

    protected static function checkUpperName(string $name): bool
    {
        if (! self::isUpperCamelCase($name)) {
            echo 'Not camel case: ' . $name . PHP_EOL;
            return false;
        }

        return true;
    }

    protected static function isLowerCamelCase(string $name): bool
    {
        return ! Regex::hasMatch('/\$[A-Z]|^[A-Z]|[A-Z]{2}|_/', $name);
    }

    protected static function isUpperCamelCase(string $name): bool
    {
        return ! Regex::hasMatch('/[A-Z]{2}|_/', $name);
    }
}
