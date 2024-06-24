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
    /**
     * @var list<string>
     */
    protected const MAGIC_METHODS = [
        '__construct',
        '__destruct',
        '__call',
        '__callStatic',
        '__get',
        '__set',
        '__isset',
        '__unset',
        '__sleep',
        '__wakeup',
        '__serialize',
        '__unserialize',
        '__toString',
        '__invoke',
        '__set_state',
        '__clone',
        '__debugInfo',
    ];

    /**
     * @var list<string>
     */
    protected const SUPERGLOBALS = [
        '$GLOBALS',
        '$_SERVER',
        '$_GET',
        '$_POST',
        '$_FILES',
        '$_REQUEST',
        '$_SESSION',
        '$_ENV',
        '$_COOKIE',
        '$http_response_header',
        '$argc',
        '$argv',
    ];

    /**
     * @var list<string>
     */
    protected array $issues = [];

    protected ?string $currentFile = null;

    public function enterNode(Node $node): Node|int|null
    {
        $name = property_exists($node, 'name') ? (string) $node->name : '';
        if ($node instanceof Namespace_) {
            // echo 'Namespace: ' . $name . PHP_EOL;
            $parts = explode('\\', $name);
            foreach ($parts as $part) {
                if (! $this->checkUpperName($part)) {
                    break;
                }
            }
        } elseif ($node instanceof Class_) {
            $this->checkUpperName($name);
        } elseif ($node instanceof Interface_) {
            $this->checkUpperName($name);
        } elseif ($node instanceof Trait_) {
            $this->checkUpperName($name);
        } elseif ($node instanceof ClassMethod) {
            $this->checkLowerName($name);
        } elseif ($node instanceof Function_) {
            $this->checkLowerName($name);
        } elseif ($node instanceof Variable) {
            $this->checkLowerName($name);
        } elseif ($name !== '') {
            //var_dump(get_class($node), $name);
        }

        return null;
    }

    public function hasIssues(): bool
    {
        return $this->issues !== [];
    }

    public function printIssues(string $filename): void
    {
        if (! $this->hasIssues()) {
            return;
        }

        if ($this->currentFile !== $filename) {
            echo PHP_EOL . '==> ' . $filename . PHP_EOL;
            $this->currentFile = $filename;
        }

        foreach ($this->issues as $issue) {
            echo $issue . PHP_EOL;
        }
    }

    protected function checkLowerName(string $name): bool
    {
        if (! self::isLowerCamelCase($name)) {
            $issue = 'Not camel case: ' . $name;
            $this->issues[] = $issue;
            return false;
        }

        return true;
    }

    protected function checkUpperName(string $name): bool
    {
        if (! self::isUpperCamelCase($name)) {
            $issue = 'Not camel case: ' . $name;
            $this->issues[] = $issue;
            return false;
        }

        return true;
    }

    protected static function isLowerCamelCase(string $name): bool
    {
        if (in_array($name, self::MAGIC_METHODS, true)) {
            return true;
        }

        if (in_array($name, self::SUPERGLOBALS, true)) {
            return true;
        }

        return ! Regex::hasMatch('/\$[A-Z]|^[A-Z]|[A-Z]{2}|_/', $name);
    }

    protected static function isUpperCamelCase(string $name): bool
    {
        if (in_array($name, self::MAGIC_METHODS, true)) {
            return true;
        }

        if (in_array($name, self::SUPERGLOBALS, true)) {
            return true;
        }

        return ! Regex::hasMatch('/[A-Z]{2}|_/', $name);
    }
}
