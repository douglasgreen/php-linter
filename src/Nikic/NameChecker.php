<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Nikic;

use DouglasGreen\Utility\Regex\Regex;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\Node\Expr\PropertyFetch;

class NameChecker extends BaseChecker
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
    protected const VALID_SHORT_NAMES = ['db', 'id'];

    /**
     * @return array<string, bool>
     */
    public function check(): array
    {
        if (! property_exists($this->node, 'name')) {
            return $this->getIssues();
        }

        if ($this->node->name === null) {
            return $this->getIssues();
        }

        if ($this->node->name instanceof PropertyFetch) {
            // @todo Find out why this doesn't work.
            // Cannot cast PhpParser\Node\Expr|PhpParser\Node\Identifier to string.
            // $name = (string) $this->node->name->name;
            return $this->getIssues();
        }

        $name = (string) $this->node->name;

        if ($this->node instanceof Namespace_) {
            // echo 'Namespace: ' . $name . PHP_EOL;
            $parts = explode('\\', $name);
            foreach ($parts as $part) {
                if (! $this->checkUpperName($part)) {
                    break;
                }
            }
        } elseif ($this->node instanceof Class_) {
            $this->checkUpperName($name);
        } elseif ($this->node instanceof Interface_) {
            $this->checkUpperName($name);
        } elseif ($this->node instanceof Trait_) {
            $this->checkUpperName($name);
        } elseif ($this->node instanceof ClassMethod) {
            $this->checkLowerName($name);
        } elseif ($this->node instanceof Function_) {
            $this->checkLowerName($name);
        } elseif ($this->node instanceof Variable) {
            $this->checkLowerName($name);
            $this->checkNameLength($name);
        } elseif ($name !== '') {
            //var_dump(get_class($this->node), $name);
        }

        return $this->getIssues();
    }

    protected function checkLowerName(string $name): bool
    {
        if (! self::isLowerCamelCase($name)) {
            $issue = 'Not camel case: ' . $name;
            $this->addIssue($issue);
            return false;
        }

        return true;
    }

    protected function checkNameLength(string $name): bool
    {
        if (strlen($name) > 25) {
            $issue = 'Variable name too long: ' . $name;
            $this->addIssue($issue);
            return false;
        }

        if (strlen($name) < 3 && ! in_array($name, self::VALID_SHORT_NAMES, true)) {
            $issue = 'Variable name too short: ' . $name;
            $this->addIssue($issue);
            return false;
        }

        return true;
    }

    protected function checkUpperName(string $name): bool
    {
        if (! self::isUpperCamelCase($name)) {
            $issue = 'Not camel case: ' . $name;
            $this->addIssue($issue);
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
