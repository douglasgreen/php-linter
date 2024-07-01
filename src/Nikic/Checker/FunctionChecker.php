<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Nikic\Checker;

use DouglasGreen\Utility\Regex\Regex;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;

/**
 * @todo Check that getters return a value and setters set a value and return void.
 */
class FunctionChecker extends NodeChecker
{
    /**
     * Boolean function names usually start with declarative verbs.
     *
     * @var list<string>
     */
    protected const BOOL_FUNC_NAMES = [
        'accepts',
        'allows',
        'applies',
        'are',
        'can',
        'complies',
        'contains',
        'equals',
        'exists',
        'expects',
        'expires',
        'has',
        'have',
        'is',
        'matches',
        'needs',
        'requires',
        'returns',
        'should',
        'supports',
        'uses',
        'was',
    ];

    /**
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
     * Function names usually start with an imperative verb or preposition.
     *
     * @var list<string>
     */
    protected const FUNC_NAMES = [
        'accept',
        'access',
        'act',
        'activate',
        'add',
        'adjust',
        'allow',
        'analyze',
        'append',
        'apply',
        'as',
        'ask',
        'assert',
        'assign',
        'at',
        'attempt',
        'authenticate',
        'authorize',
        'be',
        'before',
        'begin',
        'build',
        'by',
        'cache',
        'calculate',
        'call',
        'cancel',
        'cast',
        'change',
        'check',
        'choose',
        'clean',
        'cleanup',
        'clear',
        'clone',
        'close',
        'collect',
        'colorize',
        'comment',
        'commit',
        'compare',
        'compile',
        'complete',
        'compose',
        'compute',
        'configure',
        'confirm',
        'connect',
        'construct',
        'consume',
        'continue',
        'convert',
        'copy',
        'count',
        'create',
        'current',
        'debug',
        'decide',
        'decline',
        'decode',
        'decrement',
        'decrypt',
        'delete',
        'deliver',
        'derive',
        'describe',
        'deselect',
        'destroy',
        'detach',
        'detect',
        'determine',
        'diff',
        'disable',
        'discard',
        'disconnect',
        'dispatch',
        'display',
        'divide',
        'do',
        'double',
        'download',
        'dump',
        'duplicate',
        'echo',
        'edit',
        'email',
        'emulate',
        'enable',
        'encode',
        'encrypt',
        'end',
        'ensure',
        'enter',
        'equal',
        'erase',
        'escape',
        'evaluate',
        'exchange',
        'exclude',
        'execute',
        'expand',
        'expect',
        'export',
        'extend',
        'extract',
        'fail',
        'fetch',
        'fill',
        'filter',
        'finalize',
        'find',
        'finish',
        'fire',
        'fix',
        'fixup',
        'flatten',
        'flush',
        'force',
        'format',
        'from',
        'gather',
        'generate',
        'get',
        'give',
        'go',
        'grade',
        'grant',
        'group',
        'guarantee',
        'guess',
        'handle',
        'hash',
        'hide',
        'identify',
        'ignore',
        'import',
        'in',
        'include',
        'increment',
        'indent',
        'init',
        'initialize',
        'inject',
        'input',
        'insert',
        'inspect',
        'install',
        'instantiate',
        'interact',
        'interpolate',
        'invalidate',
        'invoke',
        'key',
        'leave',
        'list',
        'load',
        'lock',
        'log',
        'login',
        'logout',
        'lookup',
        'mail',
        'make',
        'map',
        'mark',
        'mask',
        'match',
        'max',
        'merge',
        'migrate',
        'min',
        'modify',
        'move',
        'multiply',
        'must',
        'name',
        'negate',
        'next',
        'normalize',
        'notify',
        'obtain',
        'offset',
        'on',
        'open',
        'output',
        'override',
        'overwrite',
        'pad',
        'parse',
        'pass',
        'peek',
        'perform',
        'persist',
        'pop',
        'populate',
        'post',
        'prefix',
        'prepare',
        'prepend',
        'preprocess',
        'prettify',
        'print',
        'process',
        'provide',
        'prune',
        'publish',
        'purge',
        'push',
        'put',
        'query',
        'queue',
        'quit',
        'quote',
        'read',
        'recommend',
        'record',
        'recover',
        'recreate',
        'redirect',
        'reduce',
        'refresh',
        'register',
        'reject',
        'reload',
        'remove',
        'rename',
        'render',
        'reorder',
        'replace',
        'report',
        'request',
        'require',
        'reset',
        'resolve',
        'restore',
        'restrict',
        'retrieve',
        'retry',
        'return',
        'reverse',
        'review',
        'rewind',
        'rotate',
        'round',
        'run',
        'sanitize',
        'save',
        'scan',
        'schedule',
        'seal',
        'search',
        'seed',
        'seek',
        'select',
        'send',
        'serialize',
        'set',
        'setup',
        'shift',
        'show',
        'shuffle',
        'sign',
        'skip',
        'sort',
        'split',
        'start',
        'stop',
        'store',
        'stream',
        'stringify',
        'strip',
        'submit',
        'subscribe',
        'substitute',
        'subtract',
        'suffix',
        'suppress',
        'sync',
        'tag',
        'tear',
        'tell',
        'terminate',
        'test',
        'throw',
        'time',
        'to',
        'toggle',
        'tokenize',
        'track',
        'transform',
        'translate',
        'traverse',
        'trigger',
        'trim',
        'try',
        'unescape',
        'uninstall',
        'unlink',
        'unlock',
        'unmap',
        'unregister',
        'unserialize',
        'unset',
        'unwrap',
        'update',
        'use',
        'valid',
        'validate',
        'verify',
        'version',
        'view',
        'visit',
        'wait',
        'walk',
        'warm',
        'warn',
        'will',
        'with',
        'without',
        'wrap',
        'write',
    ];

    /**
     * @var array<string, array{type: ?string, promoted: bool}>
     */
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
     * @param list<Param> $params
     */
    protected function checkParams(array $params, string $funcName, string $funcType): void
    {
        $paramCount = count($params);
        if ($paramCount > 9) {
            $this->addIssue(
                sprintf('%s %s() has too many parameters: %d', $funcType, $funcName, $paramCount),
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
                            '%s %s() has a boolean parameter $%s; replace with integer flag values',
                            $funcType,
                            $funcName,
                            $paramName,
                        ),
                    );
                }
            }

            // @todo Replace with $param->isPromoted() when that function is released.
            $isPromoted = $param->flags !== 0;
            $this->params[$paramName] = [
                'type' => $paramType,
                'promoted' => $isPromoted,
            ];
        }
    }

    protected function checkReturnType(string $funcName, string $funcType, string $returnType): void
    {
        $prefix = Regex::replace('/([a-z])[A-Z_].*/', '\1', $funcName);

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
                    '%s %s() returns a boolean; consider naming it %s',
                    $funcType,
                    $funcName,
                    $suggest
                ),
            );
        } else {
            if (in_array($prefix, self::FUNC_NAMES, true)) {
                return;
            }

            $this->addIssue(
                sprintf('%s %s() should start with a verb', $funcType, $funcName),
            );
        }
    }
}
