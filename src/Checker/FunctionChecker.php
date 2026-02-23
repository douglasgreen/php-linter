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

class FunctionChecker extends NodeChecker
{
    /**
     * Boolean function names usually start with declarative verbs.
     *
     * @var list<string>
     */
    protected const BOOL_FUNC_NAMES = [
        'accepts', 'allows', 'applies', 'are', 'can', 'complies', 'contains', 'equals',
        'exists', 'expects', 'expires', 'has', 'have', 'is', 'matches', 'needs',
        'requires', 'returns', 'should', 'supports', 'uses', 'was',
    ];

    /** @var array<string, string> */
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
     * Function names usually start with an imperative verb or preposition.
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

    /** @var array<string, array{type: ?string, promoted: bool}> */
    protected array $params = [];

    /**
     * @return array<string, bool>
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

        if ($this->node instanceof ClassMethod) {
            $this->checkStaticRecommendation($funcName);
        }

        return $this->getIssues();
    }

    /**
     * @return array<string, array{type: ?string, promoted: bool}>
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Determines if a class is a "Newable" (fine to instantiate)
     * vs an "Injectable" (should be DI'd).
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

    protected static function getNewClassName(New_ $node): string
    {
        if ($node->class instanceof Name) {
            return (string) $node->class;
        }

        return 'anonymous or dynamic class';
    }

    /**
     * @param array<Param> $params
     */
    protected function checkParams(array $params, string $funcName, string $funcType): void
    {
        $paramCount = count($params);
        if ($paramCount > 9) {
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
                if ($paramType === 'bool') {
                    $this->addIssue(
                        sprintf(
                            'Replace the boolean parameter $%s in %s %s() with an integer flag or enum. Boolean parameters often indicate a violation of the Single Responsibility Principle.',
                            $paramName,
                            $funcType,
                            $funcName,
                        ),
                    );
                }
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

    protected function checkStaticRecommendation(string $methodName): void
    {
        // Fix for undefined method isStatic/isAbstract
        if (!$this->node instanceof ClassMethod) {
            return;
        }

        // Skip if already static
        if ($this->node->isStatic()) {
            return;
        }

        // Skip if abstract (no body)
        if ($this->node->isAbstract()) {
            return;
        }

        // Skip magic methods
        if (str_starts_with($methodName, '__')) {
            return;
        }

        // Check for $this usage
        if ($this->node->stmts === null) {
            return;
        }

        $nodeFinder = new NodeFinder();
        $usesThis = $nodeFinder->findFirst($this->node->stmts, fn (Node $node): bool => $node instanceof Variable && $node->name === 'this');

        if (!$usesThis instanceof Node) {
            $this->addIssue(
                sprintf(
                    'Declare method %s() as static. It does not use $this and therefore does not belong to an object instance.',
                    $methodName,
                ),
            );
        }
    }

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
