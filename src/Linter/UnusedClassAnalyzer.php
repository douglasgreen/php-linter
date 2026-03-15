<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Linter;

use DouglasGreen\PhpLinter\IgnoreList;
use DouglasGreen\PhpLinter\IssueHolder;
use PhpParser\Error;
use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\Instanceof_;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Stmt\Catch_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\Node\Stmt\TraitUse;
use PhpParser\Node\UnionType;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Parser;
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

        $this->reportUnusedDefinitions();
    }

    public function enterNode(Node $node)
    {
        // 1. Definition Tracking
        if (($node instanceof Class_ || $node instanceof Interface_ || $node instanceof Trait_) && ($node->name instanceof Identifier && isset($node->namespacedName))) {
            $fqcn = $node->namespacedName->toString();
            $type = 'unknown';
            if ($node instanceof Class_) {
                $type = 'class';
            } elseif ($node instanceof Interface_) {
                $type = 'interface';
            } elseif ($node instanceof Trait_) {
                $type = 'trait';
            }

            $this->definitions[$fqcn] = [
                'file' => $this->currentFile,
                'line' => $node->getStartLine(),
                'type' => $type,
            ];
        }

        // 2. Usage Tracking
        if ($node instanceof New_ && $node->class instanceof Name) {
            $this->addUsage($node->class);
        } elseif ($node instanceof Instanceof_ && $node->class instanceof Name) {
            $this->addUsage($node->class);
        } elseif (($node instanceof StaticCall || $node instanceof StaticPropertyFetch || $node instanceof ClassConstFetch) && $node->class instanceof Name) {
            $this->addUsage($node->class);
        } elseif ($node instanceof Class_) {
            if ($node->extends instanceof Name) {
                $this->addUsage($node->extends);
            }

            foreach ($node->implements as $impl) {
                $this->addUsage($impl);
            }
        } elseif ($node instanceof Interface_) {
            foreach ($node->extends as $ext) {
                $this->addUsage($ext);
            }
        } elseif ($node instanceof TraitUse) {
            foreach ($node->traits as $trait) {
                $this->addUsage($trait);
            }
        } elseif ($node instanceof Catch_) {
            foreach ($node->types as $type) {
                $this->addUsage($type);
            }
        } elseif ($node instanceof Attribute) {
            $this->addUsage($node->name);
        }

        // 3. Type hints Tracking
        if ($node instanceof Function_ || $node instanceof ClassMethod || $node instanceof Closure || $node instanceof ArrowFunction) {
            $this->recordType($node->getReturnType());
            foreach ($node->getParams() as $param) {
                $this->recordType($param->type);
            }
        }

        if ($node instanceof Property) {
            $this->recordType($node->type);
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

    private function recordType(?Node $node): void
    {
        if (!$node instanceof Node) {
            return;
        }

        if ($node instanceof Name) {
            $this->addUsage($node);
        } elseif ($node instanceof NullableType) {
            $this->recordType($node->type);
        } elseif ($node instanceof UnionType || $node instanceof IntersectionType) {
            foreach ($node->types as $t) {
                $this->recordType($t);
            }
        }
    }

    private function addUsage(Name $name): void
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
