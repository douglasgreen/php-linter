<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Checker;

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

/**
 * Checks naming conventions for PHP code elements.
 *
 * Validates casing, length, and suffixes for classes, methods, properties, etc.
 *
 * @package DouglasGreen\PhpLinter\Checker
 * @since 1.0.0
 * @internal
 */
class NameChecker extends NodeChecker
{
    /**
     * Map of undesirable class suffixes to reasons why they are bad.
     *
     * @var array<string, string>
     */
    protected const BAD_CLASS_SUFFIXES = [
        'Abstract' => 'violates standard PHP naming conventions; use as a prefix or let the "abstract" keyword handle it',
        'Array' => 'classes are objects, not primitives; use "Collection" or a domain-specific plural name instead',
        'Impl' => 'is a "Java-ism" that adds no value; name the class after its specific strategy (e.g., "S3Storage" vs "StorageImpl")',
        'Implementation' => 'is redundant when using interfaces; describe *how* it implements it (e.g., "JsonParser" vs "ParserImplementation")',
        'Instance' => 'is redundant; every class is a blueprint for an instance, so the suffix provides no additional context',
        'Object' => 'is redundant; in an OOP language, the fact that a class defines an object is already implied',
    ];

    /**
     * List of PHP magic methods.
     *
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
     * List of PHP superglobals.
     *
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
     * List of acceptable short variable names.
     *
     * @var list<string>
     */
    protected const VALID_SHORT_NAMES = ['db', 'id'];

    /**
     * Performs naming convention checks on the node.
     *
     * @return array<string, bool> List of issues found.
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

    /**
     * Extracts the string name from a Variable node.
     *
     * @param Variable $variable The variable node.
     * @return string|null The variable name or null if it's a complex expression.
     */
    protected static function getVariableName(Variable $variable): ?string
    {
        if (is_string($variable->name)) {
            return $variable->name;
        }

        // For complex variable names like ${$expr}, $variable->name is an instance of Expr, so
        // implement further logic if needed.
        return null;
    }

    /**
     * Checks if a name follows lowerCamelCase conventions.
     *
     * @param string $name The name to check.
     * @return bool True if the name is valid lowerCamelCase.
     */
    protected static function isLowerCamelCase(string $name): bool
    {
        if (in_array($name, self::MAGIC_METHODS, true)) {
            return true;
        }

        if (in_array($name, self::SUPERGLOBALS, true)) {
            return true;
        }

        return preg_match('/\$[A-Z]|^[A-Z]|[A-Z]{2}|_/', $name) === 0;
    }

    /**
     * Checks if a name follows UpperCamelCase (PascalCase) conventions.
     *
     * @param string $name The name to check.
     * @return bool True if the name is valid UpperCamelCase.
     */
    protected static function isUpperCamelCase(string $name): bool
    {
        if (in_array($name, self::MAGIC_METHODS, true)) {
            return true;
        }

        if (in_array($name, self::SUPERGLOBALS, true)) {
            return true;
        }

        return preg_match('/[A-Z]{2}|_/', $name) === 0;
    }

    /**
     * Validates that a constant name is in UPPER_SNAKE_CASE.
     *
     * @param string $name The constant name to check.
     */
    protected function checkAllCapName(string $name): void
    {
        if (preg_match('/^[A-Z]+(_[A-Z]+)*$/', $name) === 0) {
            $this->addIssue(
                sprintf(
                    "Rename constant '%s' to use UPPER_SNAKE_CASE. Constants should be uppercase to distinguish them from variables.",
                    $name,
                ),
            );
        }
    }

    /**
     * Validates the length of globally visible names (classes, methods, etc.).
     *
     * @param string $name The name to check.
     * @param string $type The type of element (e.g., 'Class', 'Method').
     */
    protected function checkGlobalNameLength(string $name, string $type): void
    {
        if (strlen($name) > 32) {
            $this->addIssue(
                sprintf(
                    "Rename %s '%s' to be 32 characters or fewer. Long names harm readability.",
                    $type,
                    $name,
                ),
            );
        }

        if (strlen($name) < 3 && ! in_array($name, self::VALID_SHORT_NAMES, true)) {
            $this->addIssue(
                sprintf(
                    "Rename %s '%s' to be at least 3 characters long. Short names are often ambiguous unless they are standard abbreviations like 'id' or 'db'.",
                    $type,
                    $name,
                ),
            );
        }
    }

    /**
     * Validates the length of locally visible names (variables).
     *
     * @param string $name The name to check.
     * @param string $type The type of element (e.g., 'Variable').
     */
    protected function checkLocalNameLength(string $name, string $type): void
    {
        if (strlen($name) > 24) {
            $this->addIssue(
                sprintf(
                    "Rename %s '%s' to be 24 characters or fewer. Long variable names can make code harder to read.",
                    $type,
                    $name,
                ),
            );
        }
    }

    /**
     * Validates that a name uses camelCase.
     *
     * @param string $name The name to check.
     */
    protected function checkLowerName(string $name): void
    {
        if (! self::isLowerCamelCase($name)) {
            $this->addIssue(
                sprintf(
                    "Rename '%s' to use camelCase. Methods, functions, and variables should start with a lowercase letter.",
                    $name,
                ),
            );
        }
    }

    /**
     * Checks for undesirable suffixes on class-like elements.
     *
     * @param string $name The name to check.
     * @param string $type The type of element (e.g., 'Class', 'Interface').
     */
    protected function checkSuffix(string $name, string $type): void
    {
        if (! in_array($type, ['Namespace', 'Class', 'Interface', 'Trait'], true)) {
            return;
        }

        foreach (self::BAD_CLASS_SUFFIXES as $badSuffix => $reason) {
            if (preg_match('/' . $badSuffix . '$/i', $name)) {
                $this->addIssue(
                    sprintf(
                        "Rename %s '%s' to remove the '%s' suffix. The suffix '%s' %s.",
                        $type,
                        $name,
                        $badSuffix,
                        $badSuffix,
                        $reason,
                    ),
                );
                return;
            }
        }

        if (str_ends_with($name, $type)) {
            $this->addIssue(
                sprintf(
                    "Rename %s '%s' to remove the '%s' suffix. The suffix '%s' is redundant.",
                    $type,
                    $name,
                    $type,
                    $type,
                ),
            );
        }
    }

    /**
     * Validates that a name uses PascalCase.
     *
     * @param string $name The name to check.
     */
    protected function checkUpperName(string $name): void
    {
        if (! self::isUpperCamelCase($name)) {
            $this->addIssue(
                sprintf(
                    "Rename '%s' to use PascalCase. Classes, interfaces, traits, and namespaces should start with an uppercase letter.",
                    $name,
                ),
            );
        }
    }

    /**
     * Extracts a string name from a node if possible.
     *
     * @param Node $node The node to extract a name from.
     * @return string|null The extracted name or null.
     */
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

    /**
     * Constructs a string representation of a property fetch.
     *
     * @param PropertyFetch $propertyFetch The property fetch node.
     * @return string|null The string representation (e.g., "$this->prop") or null.
     */
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
