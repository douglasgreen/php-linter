<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Checker;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeFinder;

/**
 * Analyzes function and method definitions for best practices.
 *
 * Checks parameter counts, return types, naming conventions, and static recommendations.
 *
 * @package DouglasGreen\PhpLinter\Checker
 *
 * @since 1.0.0
 *
 * @internal
 */
class FunctionChecker extends AbstractNodeChecker
{
    /**
     * Common prefixes for boolean function names.
     *
     * @var list<string>
     */
    protected const BOOL_FUNC_NAMES = [
        'accepts', 'allows', 'applies', 'are', 'can', 'complies', 'contains', 'equals',
        'exists', 'expects', 'expires', 'has', 'have', 'is', 'matches', 'needs',
        'requires', 'returns', 'should', 'supports', 'uses', 'was',
    ];

    /**
     * Map of non-boolean prefixes to suggested boolean prefixes.
     *
     * @var array<string, string>
     */
    protected const BOOL_FUNC_RENAMES = [
        'check' => 'isValid',
        'validate' => 'isValid',
        'stop' => 'canStop',
        'fail' => 'shouldFail',
        'accept' => 'shouldAccept',
        'use' => 'shouldUse',
        'be' => 'shouldBe',
        'invoke' => 'canInvoke',
    ];

    /**
     * Common imperative verb prefixes for function names.
     *
     * @var list<string>
     */
    protected const FUNC_NAMES = [
        'accept', 'access', 'act', 'activate', 'add', 'adjust', 'after', 'allow', 'analyze',
        'append', 'apply', 'as', 'ask', 'assert', 'assign', 'at', 'attempt',
        'authenticate', 'authorize', 'be', 'before', 'begin', 'build', 'by', 'cache',
        'calculate', 'call', 'cancel', 'cast', 'change', 'check', 'choose', 'clean',
        'cleanup', 'clear', 'clone', 'close', 'collect', 'colorize', 'combine', 'comment',
        'commit', 'compare', 'compile', 'complete', 'compose', 'compute', 'configure',
        'confirm', 'connect', 'construct', 'consume', 'continue', 'convert', 'copy',
        'count', 'create', 'current', 'debug', 'decide', 'decline', 'decode',
        'decrement', 'decrypt', 'delete', 'deliver', 'derive', 'describe', 'deselect',
        'destroy', 'detach', 'detect', 'determine', 'diff', 'disable', 'discard',
        'disconnect', 'dispatch', 'display', 'divide', 'do', 'double', 'download',
        'dump', 'duplicate', 'echo', 'edit', 'email', 'emulate', 'enable', 'encode',
        'encrypt', 'end', 'ensure', 'enter', 'equal', 'erase', 'escape', 'evaluate',
        'exchange', 'exclude', 'execute', 'expand', 'expect', 'export', 'extend',
        'extract', 'fail', 'fetch', 'fill', 'filter', 'finalize', 'find', 'finish',
        'fire', 'fix', 'fixup', 'flatten', 'flush', 'force', 'format', 'from',
        'gather', 'generate', 'get', 'give', 'go', 'grade', 'grant', 'group',
        'guarantee', 'guess', 'handle', 'hash', 'hide', 'identify', 'ignore',
        'import', 'in', 'include', 'increment', 'indent', 'init', 'initialize',
        'inject', 'input', 'insert', 'inspect', 'install', 'instantiate', 'interact',
        'interpolate', 'invalidate', 'invoke', 'key', 'leave', 'list', 'load', 'lock',
        'log', 'login', 'logout', 'lookup', 'mail', 'make', 'map', 'mark', 'mask',
        'match', 'max', 'merge', 'migrate', 'min', 'modify', 'move', 'multiply',
        'must', 'name', 'negate', 'next', 'normalize', 'notify', 'obtain', 'offset',
        'on', 'open', 'output', 'override', 'overwrite', 'pad', 'parse', 'pass',
        'peek', 'perform', 'persist', 'pop', 'populate', 'post', 'prefix', 'prepare',
        'prepend', 'preprocess', 'prettify', 'print', 'process', 'provide', 'prune',
        'publish', 'purge', 'push', 'put', 'query', 'queue', 'quit', 'quote', 'read',
        'recommend', 'record', 'recover', 'recreate', 'redirect', 'reduce', 'refresh',
        'register', 'reject', 'reload', 'remove', 'rename', 'render', 'reorder',
        'replace', 'report', 'request', 'require', 'reset', 'resolve', 'restore',
        'restrict', 'retrieve', 'retry', 'return', 'reverse', 'review', 'rewind',
        'rotate', 'round', 'run', 'sanitize', 'save', 'scan', 'schedule', 'seal',
        'search', 'seed', 'seek', 'select', 'send', 'serialize', 'set', 'setup',
        'shift', 'show', 'shuffle', 'sign', 'skip', 'sort', 'split', 'start', 'stop',
        'store', 'stream', 'stringify', 'strip', 'submit', 'subscribe', 'substitute',
        'subtract', 'suffix', 'suppress', 'sync', 'tag', 'tear', 'tell', 'terminate',
        'test', 'throw', 'time', 'to', 'toggle', 'tokenize', 'track', 'transform',
        'translate', 'traverse', 'trigger', 'trim', 'try', 'unescape', 'uninstall',
        'unlink', 'unlock', 'unmap', 'unregister', 'unserialize', 'unset', 'unwrap',
        'update', 'use', 'valid', 'validate', 'verify', 'version', 'view', 'visit',
        'wait', 'walk', 'warm', 'warn', 'will', 'with', 'without', 'wrap', 'write',
    ];

    /**
     * Stores parameter metadata indexed by parameter name.
     *
     * @var array<string, array{type: ?string, promoted: bool}>
     */
    protected array $params = [];

    /**
     * Constructs a FunctionChecker with optional readonly class context.
     *
     * @param Node $node The function or method node to check.
     * @param bool $isReadonly Whether the containing class is readonly.
     */
    public function __construct(Node $node, protected bool $isReadonly = false)
    {
        parent::__construct($node);
    }

    /**
     * Performs validation checks on function or method nodes.
     *
     * @return array<string, bool> List of issues found.
     */
    public function check(): array
    {
        if ($this->node instanceof Function_) {
            $funcType = 'Function';
        } elseif ($this->node instanceof ClassMethod) {
            $funcType = 'Method';
        } else {
            return [];
        }

        $funcName = $this->node->name->toString();
        $params = $this->node->params;
        $this->checkParams($params, $funcName, $funcType);

        if ($this->node->returnType instanceof Identifier) {
            $returnType = $this->node->returnType->name;
            $this->checkReturnType($funcName, $funcType, $returnType);

            if ($returnType === 'array') {
                $this->checkReturnArrayDto();
            }
        }

        return $this->getIssues();
    }

    /**
     * Returns the collected parameter metadata.
     *
     * @return array<string, array{type: ?string, promoted: bool}>
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Determines if a class is a "Newable" (fine to instantiate) vs an "Injectable" (should be DI'd).
     *
     * @param string $className The name of the class to check.
     *
     * @return bool True if the class is considered safe to instantiate directly.
     */
    protected static function isNewable(string $className): bool
    {
        // 1. Exceptions and Errors
        if (str_ends_with($className, 'Exception') || str_ends_with($className, 'Error')) {
            return true;
        }

        // 2. Common PHP built-in Value Objects/Containers
        $builtIns = [
            'stdClass',
            'DateTime',
            'DateTimeImmutable',
            'DateTimeZone',
            'DateInterval',
            'ArrayObject',
            'ArrayIterator',
            'SplFileInfo',
            'ReflectionClass',
        ];
        if (in_array($className, $builtIns, true)) {
            return true;
        }

        // 3. Data-centric suffixes (DTOs, Entities, Value Objects)
        $dataSuffixes = ['Dto', 'Entity', 'Value', 'Vo', 'Collection', 'Criteria'];
        foreach ($dataSuffixes as $suffix) {
            if (str_ends_with($className, $suffix)) {
                return true;
            }
        }

        // 4. Anonymous classes
        return $className === 'anonymous or dynamic class';
    }

    /**
     * Extracts the class name from a New_ expression node.
     *
     * @param New_ $node The instantiation node.
     *
     * @return string The class name or a placeholder for anonymous classes.
     */
    protected static function getNewClassName(New_ $node): string
    {
        if ($node->class instanceof Name) {
            return (string) $node->class;
        }

        return 'anonymous or dynamic class';
    }

    /**
     * Validates the parameter list of a function or method.
     *
     * @param array<Param> $params Array of parameter nodes.
     * @param string $funcName The name of the function or method.
     * @param string $funcType The type of element ('Function' or 'Method').
     */
    protected function checkParams(array $params, string $funcName, string $funcType): void
    {
        $paramCount = count($params);
        if ($paramCount > 9 && !$this->isReadonly) {
            $this->addIssue(
                sprintf(
                    'Reduce the parameter count of %s %s() from %d to 9 or fewer. Long parameter lists reduce readability and increase the chance of errors.',
                    $funcType,
                    $funcName,
                    $paramCount,
                ),
            );
        }

        foreach ($params as $param) {
            if (! $param->var instanceof Variable) {
                continue;
            }

            $paramName = $param->var->name;
            if (! is_string($paramName)) {
                continue;
            }

            $paramType = null;
            if ($param->type instanceof Identifier) {
                $paramType = $param->type->name;
            }

            if ($paramType === 'array') {
                $this->checkArrayDto($paramName);
            }

            $this->params[$paramName] = [
                'type' => $paramType,
                'promoted' => $param->isPromoted(),
            ];
        }
    }

    /**
     * Validates the return type against the function or method name.
     *
     * @param string $funcName The name of the function or method.
     * @param string $funcType The type of element ('Function' or 'Method').
     * @param string $returnType The return type string.
     */
    protected function checkReturnType(string $funcName, string $funcType, string $returnType): void
    {
        $prefix = (string) preg_replace('/([a-z])[A-Z_].*/', '\1', $funcName);

        if ($returnType === 'bool') {
            if (in_array($prefix, self::BOOL_FUNC_NAMES, true)) {
                return;
            }

            if (array_key_exists($prefix, self::BOOL_FUNC_RENAMES)) {
                $suggest = self::BOOL_FUNC_RENAMES[$prefix] . '()';
            } else {
                $suggest = 'sX(), hasX(), etc.';
            }

            $this->addIssue(
                sprintf(
                    'Rename %s %s() to %s or a similar boolean prefix (is, has, can). Methods returning bool should indicate their result via their name.',
                    $funcType,
                    $funcName,
                    $suggest,
                ),
            );
        } else {
            if (in_array($prefix, self::FUNC_NAMES, true)) {
                return;
            }

            $this->addIssue(
                sprintf(
                    'Rename %s %s() to start with an imperative verb. Method names should describe the action being performed.',
                    $funcType,
                    $funcName,
                ),
            );
        }
    }

    /**
     * Checks if an array parameter should be replaced with a DTO.
     *
     * @param string $paramName The name of the parameter to check.
     */
    protected function checkArrayDto(string $paramName): void
    {
        // Narrow type to access $stmts
        if (!$this->node instanceof Function_ && !$this->node instanceof ClassMethod) {
            return;
        }

        if ($this->node->stmts === null) {
            return;
        }

        $nodeFinder = new NodeFinder();
        $keys = [];

        /** @var array<ArrayDimFetch> $dimFetches */
        $dimFetches = $nodeFinder->findInstanceOf($this->node->stmts, ArrayDimFetch::class);

        foreach ($dimFetches as $dimFetch) {
            if (!$dimFetch->var instanceof Variable) {
                continue;
            }

            if ($dimFetch->var->name !== $paramName) {
                continue;
            }

            if (!$dimFetch->dim instanceof String_) {
                continue;
            }

            $keys[] = $dimFetch->dim->value;
        }

        if ($keys !== []) {
            $uniqueKeys = array_unique($keys);
            $this->addIssue(
                sprintf(
                    'Replace array parameter $%s with a DTO class. The array is accessed with string keys (%s), which suggests a structured data type is more appropriate.',
                    $paramName,
                    implode(', ', $uniqueKeys),
                ),
            );
        }
    }

    /**
     * Checks if an array return type should be replaced with a DTO.
     */
    protected function checkReturnArrayDto(): void
    {
        // Narrow type to access $stmts
        if (!$this->node instanceof Function_ && !$this->node instanceof ClassMethod) {
            return;
        }

        if ($this->node->stmts === null) {
            return;
        }

        $nodeFinder = new NodeFinder();
        $keys = [];

        /** @var array<Return_> $returnStmts */
        $returnStmts = $nodeFinder->findInstanceOf($this->node->stmts, Return_::class);

        foreach ($returnStmts as $returnStmt) {
            if ($returnStmt->expr instanceof Array_) {
                foreach ($returnStmt->expr->items as $item) {
                    // Fix for "instanceof ArrayItem will always evaluate to true"
                    // In nikic/php-parser 5.x, ArrayItem is the standard item type in Array_::$items
                    if ($item->key instanceof String_) {
                        $keys[] = $item->key->value;
                    }
                }
            }
        }

        if ($keys !== []) {
            $uniqueKeys = array_unique($keys);
            $this->addIssue(
                sprintf(
                    'Return a DTO object instead of an array. The return array uses string keys (%s), which suggests a structured data type is more appropriate.',
                    implode(', ', $uniqueKeys),
                ),
            );
        }
    }
}
