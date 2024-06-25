<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Nikic;

use DouglasGreen\Utility\Regex\Regex;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Const_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Trait_;

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
                $parts = explode('\\', $name);
                foreach ($parts as $part) {
                    if (! $this->checkUpperName($part)) {
                        break;
                    }
                }
            } elseif ($this->node instanceof Class_) {
                $this->checkUpperName($name);
                $this->checkGlobalNameLength($name, 'Class');
            } elseif ($this->node instanceof Interface_) {
                $this->checkUpperName($name);
                $this->checkGlobalNameLength($name, 'Interface');
            } elseif ($this->node instanceof Trait_) {
                $this->checkUpperName($name);
                $this->checkGlobalNameLength($name, 'Trait');
            } elseif ($this->node instanceof ClassMethod) {
                $this->checkLowerName($name);
                $this->checkGlobalNameLength($name, 'Method');
            } elseif ($this->node instanceof Function_) {
                $this->checkLowerName($name);
                $this->checkGlobalNameLength($name, 'Function');
            } elseif ($this->node instanceof Variable) {
                $this->checkLowerName($name);
                $this->checkLocalNameLength($name, 'Variable');
            } elseif ($name !== '') {
                //var_dump(get_class($this->node), $name);
            }
        }
        elseif (property_exists($this->node, 'consts')) {
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

    protected function checkAllCapName(string $name): bool
    {
        if (! Regex::hasMatch('/^[A-Z]+(_[A-Z]+)*$/', $name)) {
            $issue = 'Not all caps: ' . $name;
            $this->addIssue($issue);
            return false;
        }

        return true;
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

    /**
     * Global names are names of classes, methods, functions. etc. that are globally visible.
     */
    protected function checkGlobalNameLength(string $name, string $type): bool
    {
        if (strlen($name) > 32) {
            $issue = $type . ' name too long: ' . $name;
            $this->addIssue($issue);
            return false;
        }

        if (strlen($name) < 3 && ! in_array($name, self::VALID_SHORT_NAMES, true)) {
            $issue = $type . ' name too short: ' . $name;
            $this->addIssue($issue);
            return false;
        }

        return true;
    }

    /**
     * Local names are names of variables that are only locally visible and can be shorter.
     */
    protected function checkLocalNameLength(string $name, string $type): bool
    {
        if (strlen($name) > 24) {
            $issue = $type . ' name too long: ' . $name;
            $this->addIssue($issue);
            return false;
        }

        if (strlen($name) < 3 && ! in_array($name, self::VALID_SHORT_NAMES, true)) {
            $issue = $type . ' name too short: ' . $name;
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
