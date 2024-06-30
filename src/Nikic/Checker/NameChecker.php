<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Nikic\Checker;

use DouglasGreen\Utility\Regex\Regex;
use PhpParser\Node;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Const_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Trait_;

class NameChecker extends NodeChecker
{
    /**
     * @var list<string>
     */
    protected const BAD_SUFFIXES = [
        'Class',
        'Data',
        'Function',
        'Impl',
        'Implementation',
        'Info',
        'Information',
        'Interface',
        'Method',
        'Object',
        'Stuff',
        'Trait',
        'Variable',
    ];

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
        'GLOBALS',
        '_SERVER',
        '_GET',
        '_POST',
        '_FILES',
        '_REQUEST',
        '_SESSION',
        '_ENV',
        '_COOKIE',
        'http_response_header',
        'argc',
        'argv',
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
        if (property_exists($this->node, 'name')) {
            $name = $this->getName($this->node);
            if ($name === null) {
                return $this->getIssues();
            }

            if ($this->node instanceof Namespace_) {
                $parts = explode('\\', $name);
                foreach ($parts as $part) {
                    $this->checkUpperName($part);
                }

                $this->checkSuffix($name, 'Namespace');
            } elseif ($this->node instanceof Class_) {
                $this->checkUpperName($name);
                $this->checkGlobalNameLength($name, 'Class');
                $this->checkSuffix($name, 'Class');
            } elseif ($this->node instanceof Interface_) {
                $this->checkUpperName($name);
                $this->checkGlobalNameLength($name, 'Interface');
                $this->checkSuffix($name, 'Interface');
            } elseif ($this->node instanceof Trait_) {
                $this->checkUpperName($name);
                $this->checkGlobalNameLength($name, 'Trait');
                $this->checkSuffix($name, 'Trait');
            } elseif ($this->node instanceof ClassMethod) {
                $this->checkLowerName($name);
                $this->checkGlobalNameLength($name, 'Method');
                $this->checkSuffix($name, 'Method');
            } elseif ($this->node instanceof Function_) {
                $this->checkLowerName($name);
                $this->checkGlobalNameLength($name, 'Function');
                $this->checkSuffix($name, 'Function');
            } elseif ($this->node instanceof Variable) {
                $this->checkLowerName($name);
                $this->checkLocalNameLength($name, 'Variable');
                $this->checkSuffix($name, 'Variable');
            } elseif ($name !== '') {
                //var_dump(get_class($this->node), $name);
            }
        } elseif (property_exists($this->node, 'consts')) {
            if ($this->node instanceof Const_) {
                foreach ($this->node->consts as $const) {
                    $constName = (string) $const->name;
                    $this->checkAllCapName($constName);
                    $this->checkGlobalNameLength($constName, 'Constant');
                }
            } elseif ($this->node instanceof ClassConst) {
                foreach ($this->node->consts as $const) {
                    $constName = (string) $const->name;
                    $this->checkAllCapName($constName);
                    $this->checkGlobalNameLength($constName, 'Constant');
                }
            }
        }

        return $this->getIssues();
    }

    protected static function getVariableName(Variable $variable): ?string
    {
        if (is_string($variable->name)) {
            return '$' . $variable->name;
        }

        // For complex variable names like ${$expr}, $variable->name is an instance of Expr, so
        // implement further logic if needed.
        return null;
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

    protected function checkAllCapName(string $name): void
    {
        if (! Regex::hasMatch('/^[A-Z]+(_[A-Z]+)*$/', $name)) {
            $issue = 'Not all caps: ' . $name;
            $this->addIssue($issue);
        }
    }

    /**
     * Global names are names of classes, methods, functions. etc. that are globally visible.
     */
    protected function checkGlobalNameLength(string $name, string $type): void
    {
        if (strlen($name) > 32) {
            $issue = $type . ' name too long: ' . $name;
            $this->addIssue($issue);
        }

        if (strlen($name) < 3 && ! in_array($name, self::VALID_SHORT_NAMES, true)) {
            $issue = $type . ' name too short: ' . $name;
            $this->addIssue($issue);
        }
    }

    /**
     * Local names are names of variables that are only locally visible and can be shorter.
     */
    protected function checkLocalNameLength(string $name, string $type): void
    {
        if (strlen($name) > 24) {
            $issue = $type . ' name too long: ' . $name;
            $this->addIssue($issue);
        }

        if (strlen($name) < 3 && ! in_array($name, self::VALID_SHORT_NAMES, true)) {
            $issue = $type . ' name too short: ' . $name;
            $this->addIssue($issue);
        }
    }

    protected function checkLowerName(string $name): void
    {
        if (! self::isLowerCamelCase($name)) {
            $issue = 'Not camel case: ' . $name;
            $this->addIssue($issue);
        }
    }

    protected function checkSuffix(string $name, string $type): void
    {
        foreach (self::BAD_SUFFIXES as $badSuffix) {
            if (Regex::hasMatch('/' . $badSuffix . '$/i', $name)) {
                $this->addIssue(
                    sprintf('%s names should not end in %s: %s', $type, $badSuffix, $name)
                );
                break;
            }
        }
    }

    protected function checkUpperName(string $name): void
    {
        if (! self::isUpperCamelCase($name)) {
            $issue = 'Not camel case: ' . $name;
            $this->addIssue($issue);
        }
    }

    protected function getName(Node $node): ?string
    {
        if (! property_exists($node, 'name')) {
            return null;
        }

        $name = $node->name;

        if ($name === null) {
            return null;
        }

        if ($name instanceof Name) {
            return $name->toString();
        }

        if ($name instanceof Identifier) {
            return $name->name;
        }

        if ($name instanceof PropertyFetch) {
            return $this->getPropertyFetchName($name);
        }

        if ($name instanceof Variable) {
            return self::getVariableName($name);
        }

        if (is_string($name)) {
            return $name;
        }

        // Handle other cases or log unhandled types
        return null;
    }

    protected function getPropertyFetchName(PropertyFetch $propertyFetch): ?string
    {
        $varName = $this->getName($propertyFetch->var);
        $propName = $this->getName($propertyFetch->name);
        if ($varName === null) {
            return null;
        }

        if ($propName === null) {
            return null;
        }

        return sprintf('%s->%s', $varName, $propName);
    }
}
