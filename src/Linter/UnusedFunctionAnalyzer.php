<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Linter;

use DouglasGreen\PhpLinter\IgnoreList;
use DouglasGreen\PhpLinter\IssueHolder;
use PhpParser\Error;
use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Parser;
use PhpParser\ParserFactory;

/**
 * Analyzes PHP files to detect unused functions and methods.
 *
 * @internal
 */
class UnusedFunctionAnalyzer extends NodeVisitorAbstract
{
    /** @var array<string, array{file: string, line: int, type: string}> */
    private array $definitions = [];

    /** @var array<string, int> */
    private array $calls = [];

    /** @var array<int, string|null> */
    private array $classStack = [];

    private string $currentFile = '';

    public function __construct(
        protected readonly IssueHolder $issueHolder,
        protected readonly IgnoreList $ignoreList,
    ) {}

    /**
     * Runs the analysis on the provided list of PHP files.
     *
     * @param array<string> $phpFiles
     */
    public function run(array $phpFiles): void
    {
        // Filter out ignored files
        $phpFiles = array_filter($phpFiles, fn (string $file): bool => !$this->ignoreList->shouldIgnore($file));

        $parserFactory = new ParserFactory();
        $parser = $parserFactory->createForHostVersion();
        foreach ($phpFiles as $file) {
            $this->currentFile = $file;
            $this->parseFile($parser, $file);
        }

        $this->reportUnusedFunctions();
    }

    public function enterNode(Node $node)
    {
        // Track current class, handling nested or anonymous classes via a stack
        if ($node instanceof ClassLike) {
            $className = null;
            if (isset($node->namespacedName)) {
                $className = $node->namespacedName->toString();
            } elseif (isset($node->name)) {
                $className = $node->name->toString();
            }

            $this->classStack[] = $className;
        }

        $currentClass = end($this->classStack) ?: null;

        // 1. Definition Tracking
        if ($node instanceof ClassMethod) {
            if ($currentClass) {
                $fullName = $currentClass . '::' . $node->name->toString();
                $this->addDefinition($fullName, $this->currentFile, $node->getStartLine(), 'method');
            }
        } elseif ($node instanceof Function_) {
            if (isset($node->namespacedName)) {
                $this->addDefinition($node->namespacedName->toString(), $this->currentFile, $node->getStartLine(), 'function');
            }
        }

        // 2. Usage Tracking
        if ($node instanceof FuncCall) {
            if ($node->name instanceof Name) {
                $this->incrementCall($node->name->toString());
            }
        } elseif ($node instanceof MethodCall || $node instanceof NullsafeMethodCall) {
            if ($node->name instanceof Identifier) {
                $methodName = $node->name->toString();
                $this->incrementCall($methodName);
                $this->incrementCall('*::' . $methodName);
            }
        } elseif ($node instanceof StaticCall) {
            if ($node->name instanceof Identifier) {
                $methodName = $node->name->toString();
                $this->incrementCall($methodName);
                $this->incrementCall('*::' . $methodName);
            }
        }

        return null;
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof ClassLike) {
            array_pop($this->classStack);
        }

        return null;
    }

    /**
     * Parses a single PHP file.
     */
    private function parseFile(Parser $parser, string $file): void
    {
        $content = @file_get_contents($file);
        if ($content === false) {
            return;
        }

        $traverser = new NodeTraverser();
        // NameResolver will convert all class/trait/interface references to fully qualified names
        $traverser->addVisitor(new NameResolver());
        $traverser->addVisitor($this);

        try {
            $ast = $parser->parse($content);
            if ($ast !== null) {
                $traverser->traverse($ast);
            }
        } catch (Error) {
            // Silently ignore parse errors
        }
    }

    private function addDefinition(string $name, string $file, int $line, string $type): void
    {
        $this->definitions[$name] = [
            'file' => $file,
            'line' => $line,
            'type' => $type,
        ];
    }

    private function incrementCall(string $name): void
    {
        if (!isset($this->calls[$name])) {
            $this->calls[$name] = 0;
        }

        ++$this->calls[$name];
    }

    private function reportUnusedFunctions(): void
    {
        foreach ($this->definitions as $funcName => $info) {
            $callCount = 0;

            // Check for direct calls
            if (isset($this->calls[$funcName])) {
                $callCount += $this->calls[$funcName];
            }

            // For methods, check wildcard calls
            if ($info['type'] === 'method') {
                $parts = explode('::', (string) $funcName);
                if (count($parts) === 2) {
                    $methodOnly = $parts[1];
                    if (isset($this->calls['*::' . $methodOnly])) {
                        $callCount += $this->calls['*::' . $methodOnly];
                    }
                }
            }

            if ($callCount === 0 && !$this->isSpecialMethod($funcName)) {
                $this->issueHolder->setCurrentFile($info['file']);
                $this->issueHolder->addIssue(
                    sprintf(
                        'Unused %s "%s" found.',
                        $info['type'],
                        $funcName,
                    ),
                );
            }
        }
    }

    private function isSpecialMethod(string $funcName): bool
    {
        // Check for magic methods and constructors
        $specialMethods = [
            '__construct', '__destruct', '__call', '__callStatic',
            '__get', '__set', '__isset', '__unset', '__sleep',
            '__wakeup', '__serialize', '__unserialize', '__toString',
            '__invoke', '__set_state', '__clone', '__debugInfo',
        ];

        foreach ($specialMethods as $method) {
            if (str_contains($funcName, '::' . $method)) {
                return true;
            }
        }

        return false;
    }
}
