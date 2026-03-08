<?php

declare(strict_types=1);

namespace DouglasGreen\PHPProjectChecker\Linter;

use DouglasGreen\PhpLinter\Repository;
use PhpParser\Error;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;

class UnusedFunctionAnalyzer
{
    /** @var array<string, array{file: string, line: int, type: string}> */
    private array $definitions =[];

    /** @var array<string, int> */
    private array $calls =[];

    private Repository $repository;

    public function __construct()
    {
        $this->repository = new Repository();
    }

    public function analyze(): void
    {
        $files = $this->repository->getPhpFiles();

        $parser = (new ParserFactory())->createForNewestSupportedVersion();
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());

        $visitor = new class($this) extends NodeVisitorAbstract {
            private FunctionAnalyzer $analyzer;
            private array $classStack =[];
            private string $file = '';

            public function __construct(FunctionAnalyzer $analyzer)
            {
                $this->analyzer = $analyzer;
            }

            public function setFile(string $file): void
            {
                $this->file = $file;
            }

            public function enterNode(Node $node)
            {
                // Track current class, handling nested or anonymous classes via a stack
                if ($node instanceof ClassLike) {
                    $className = null;
                    if (isset($node->namespacedName)) {
                        $className = $node->namespacedName->toString();
                    } elseif (isset($node->name) && $node->name instanceof Node\Identifier) {
                        $className = $node->name->toString();
                    }
                    $this->classStack[] = $className;
                }

                $currentClass = end($this->classStack) ?: null;

                // Find definitions
                if ($node instanceof Node\Stmt\ClassMethod) {
                    if ($currentClass && $node->name instanceof Node\Identifier) {
                        $fullName = $currentClass . '::' . $node->name->toString();
                        $this->analyzer->addDefinition($fullName, $this->file, $node->getStartLine(), 'method');
                    }
                } elseif ($node instanceof Node\Stmt\Function_) {
                    if (isset($node->namespacedName)) {
                        $this->analyzer->addDefinition($node->namespacedName->toString(), $this->file, $node->getStartLine(), 'function');
                    }
                }

                // Find calls
                elseif ($node instanceof Node\Expr\FuncCall) {
                    if ($node->name instanceof Node\Name) {
                        $this->analyzer->incrementCall($node->name->toString());
                    }
                } elseif ($node instanceof Node\Expr\MethodCall || $node instanceof Node\Expr\NullsafeMethodCall) {
                    if ($node->name instanceof Node\Identifier) {
                        $methodName = $node->name->toString();
                        $this->analyzer->incrementCall($methodName);
                        $this->analyzer->incrementCall('*::' . $methodName);
                    }
                } elseif ($node instanceof Node\Expr\StaticCall) {
                    if ($node->name instanceof Node\Identifier) {
                        $methodName = $node->name->toString();
                        $this->analyzer->incrementCall($methodName);
                        $this->analyzer->incrementCall('*::' . $methodName);
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
        };

        $traverser->addVisitor($visitor);

        foreach ($files as $file) {
            $content = file_get_contents($file);
            if ($content === false) {
                continue;
            }

            try {
                $ast = $parser->parse($content);
                if ($ast !== null) {
                    $visitor->setFile($file);
                    $traverser->traverse($ast);
                }
            } catch (Error $e) {
                // Ignore parsing errors for individual files
            }
        }

        $this->printReport();
    }

    public function addDefinition(string $name, string $file, int $line, string $type): void
    {
        $this->definitions[$name] =[
            'file' => $file,
            'line' => $line,
            'type' => $type,
        ];
    }

    public function incrementCall(string $name): void
    {
        if (!isset($this->calls[$name])) {
            $this->calls[$name] = 0;
        }

        ++$this->calls[$name];
    }

    private function printReport(): void
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

            // Output only the items that have not been called anywhere
            if ($callCount === 0 && !$this->isSpecialMethod($funcName)) {
                echo sprintf(
                    "%-50s [%s]\n  File: %s:%d\n\n",
                    $funcName,
                    $info['type'],
                    $info['file'],
                    $info['line'],
                );
            }
        }
    }

    private function isSpecialMethod(string $funcName): bool
    {
        // Check for magic methods and constructors
        $specialMethods =[
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
