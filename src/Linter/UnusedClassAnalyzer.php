<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Linter;

use DouglasGreen\PhpLinter\IssueHolder;
use PhpParser\Error;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;

/**
 * Analyzes PHP files to detect unused classes, interfaces, and traits.
 *
 * @internal
 */
class UnusedClassAnalyzer extends NodeVisitorAbstract
{
    /** @var array<string, array{file: string, line: int, type: string}> */
    private array $definitions = [];

    /** @var array<string, int> */
    private array $usages = [];

    private string $currentFile = '';

    public function __construct(
        protected readonly IssueHolder $issueHolder,
    ) {}

    /**
     * Runs the analysis on the provided list of PHP files.
     *
     * @param array<string> $phpFiles
     */
    public function run(array $phpFiles): void
    {
        $parserFactory = new ParserFactory();

        // Version compatible instantiation (supports php-parser v4 and v5)
        if (method_exists($parserFactory, 'createForHostVersion')) {
            $parser = $parserFactory->createForHostVersion();
        } else {
            // @phpstan-ignore-next-line
            $parser = $parserFactory->create(ParserFactory::PREFER_PHP7);
        }

        foreach ($phpFiles as $file) {
            $this->currentFile = $file;
            $this->parseFile($parser, $file);
        }

        $this->reportUnusedDefinitions();
    }

    /**
     * Parses a single PHP file.
     *
     * @param \PhpParser\Parser $parser
     */
    private function parseFile($parser, string $file): void
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
        } catch (Error $exception) {
            // Silently ignore parse errors
        }
    }

    public function enterNode(Node $node)
    {
        // 1. Definition Tracking
        if ($node instanceof Node\Stmt\Class_ || $node instanceof Node\Stmt\Interface_ || $node instanceof Node\Stmt\Trait_) {
            if ($node->name !== null && isset($node->namespacedName)) {
                $fqcn = $node->namespacedName->toString();
                $type = 'unknown';
                if ($node instanceof Node\Stmt\Class_) {
                    $type = 'class';
                } elseif ($node instanceof Node\Stmt\Interface_) {
                    $type = 'interface';
                } elseif ($node instanceof Node\Stmt\Trait_) {
                    $type = 'trait';
                }

                $this->definitions[$fqcn] = [
                    'file' => $this->currentFile,
                    'line' => $node->getStartLine(),
                    'type' => $type,
                ];
            }
        }

        // 2. Usage Tracking
        if ($node instanceof Node\Expr\New_ && $node->class instanceof Node\Name) {
            $this->addUsage($node->class);
        } elseif ($node instanceof Node\Expr\Instanceof_ && $node->class instanceof Node\Name) {
            $this->addUsage($node->class);
        } elseif (($node instanceof Node\Expr\StaticCall || $node instanceof Node\Expr\StaticPropertyFetch || $node instanceof Node\Expr\ClassConstFetch) && $node->class instanceof Node\Name) {
            $this->addUsage($node->class);
        } elseif ($node instanceof Node\Stmt\Class_) {
            if ($node->extends !== null) {
                $this->addUsage($node->extends);
            }
            foreach ($node->implements as $impl) {
                $this->addUsage($impl);
            }
        } elseif ($node instanceof Node\Stmt\Interface_) {
            foreach ($node->extends as $ext) {
                $this->addUsage($ext);
            }
        } elseif ($node instanceof Node\Stmt\TraitUse) {
            foreach ($node->traits as $trait) {
                $this->addUsage($trait);
            }
        } elseif ($node instanceof Node\Stmt\Catch_) {
            foreach ($node->types as $type) {
                $this->addUsage($type);
            }
        } elseif ($node instanceof Node\Attribute) {
            $this->addUsage($node->name);
        }

        // 3. Type hints Tracking
        if ($node instanceof Node\Stmt\Function_ || $node instanceof Node\Stmt\ClassMethod || $node instanceof Node\Expr\Closure || $node instanceof Node\Expr\ArrowFunction) {
            $this->recordType($node->getReturnType());
            foreach ($node->getParams() as $param) {
                $this->recordType($param->type);
            }
        }

        if ($node instanceof Node\Stmt\Property) {
            $this->recordType($node->type);
        }

        return null;
    }

    private function recordType(?Node $node): void
    {
        if ($node === null) {
            return;
        }

        if ($node instanceof Node\Name) {
            $this->addUsage($node);
        } elseif ($node instanceof Node\NullableType) {
            $this->recordType($node->type);
        } elseif ($node instanceof Node\UnionType || $node instanceof Node\IntersectionType) {
            foreach ($node->types as $t) {
                $this->recordType($t);
            }
        }
    }

    private function addUsage(Node\Name $name): void
    {
        $fqcn = $name->toString();

        // Exclude internal keywords
        if (!in_array(strtolower($fqcn), ['self', 'static', 'parent'], true)) {
            if (!isset($this->usages[$fqcn])) {
                $this->usages[$fqcn] = 0;
            }
            $this->usages[$fqcn]++;
        }
    }

    private function reportUnusedDefinitions(): void
    {
        foreach ($this->definitions as $fqcn => $info) {
            $count = $this->usages[$fqcn] ?? 0;

            if ($count === 0) {
                $this->issueHolder->setCurrentFile($info['file']);
                $this->issueHolder->addIssue(
                    sprintf(
                        'Unused %s "%s" found.',
                        $info['type'],
                        $fqcn,
                    ),
                );
            }
        }
    }
}
