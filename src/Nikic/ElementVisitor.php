<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Nikic;

use DouglasGreen\PhpLinter\ComposerFile;
use DouglasGreen\PhpLinter\Nikic\Checker\ArrayChecker;
use DouglasGreen\PhpLinter\Nikic\Checker\ClassChecker;
use DouglasGreen\PhpLinter\Nikic\Checker\ExpressionChecker;
use DouglasGreen\PhpLinter\Nikic\Checker\FunctionCallChecker;
use DouglasGreen\PhpLinter\Nikic\Checker\FunctionChecker;
use DouglasGreen\PhpLinter\Nikic\Checker\LocalScopeChecker;
use DouglasGreen\PhpLinter\Nikic\Checker\NameChecker;
use DouglasGreen\PhpLinter\Nikic\Checker\OperatorChecker;
use DouglasGreen\PhpLinter\Nikic\Checker\TryCatchChecker;
use DouglasGreen\PhpLinter\Nikic\Visitor\ClassVisitor;
use DouglasGreen\PhpLinter\Nikic\Visitor\FunctionVisitor;
use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\Node\Stmt\TryCatch;
use PhpParser\NodeVisitorAbstract;

class ElementVisitor extends NodeVisitorAbstract
{
    use IssueHolder;

    protected ClassVisitor $classVisitor;

    protected FunctionVisitor $functionVisitor;

    /**
     * @var array<string, bool>
     */
    protected array $methodCalls = [];

    protected ?string $currentNamespace = null;

    protected ?string $currentClassName = null;

    protected ?string $currentFile = null;

    protected ?string $currentFunctionName = null;

    protected ?string $currentTraitName = null;

    /**
     * Are we inside a class, trait, method, function, or closure?
     */
    protected bool $isLocalScope = false;

    public function __construct(
        protected readonly ComposerFile $composerFile,
        protected readonly string $phpFile
    ) {}

    public function enterNode(Node $node): null
    {
        if ($node instanceof Namespace_ && $node->name !== null) {
            $this->currentNamespace = implode('\\', $node->name->parts);
        }

        // @todo Remove words like Manager, Handler, etc. if no conflict
        if ($node instanceof Class_) {
            $this->currentClassName = $node->name === null ? null : $node->name->name;

            // Run checks on class node.
            $classChecker = new ClassChecker($node);
            $this->addIssues($classChecker->check());
            $attribs = [
                'abstract' => $node->isAbstract(),
                'final' => $node->isFinal(),
                'readonly' => $node->isReadonly(),
                'anonymous' => $node->isAnonymous(),
            ];

            // Start class visitor to examine nodes within class.
            $this->classVisitor = new ClassVisitor($this->currentClassName, $attribs);
            $this->isLocalScope = true;

            // Check namespace name, class name, and file path.
            if ($this->currentNamespace !== null) {
                $expectedFile = $this->composerFile->convertClassNameToFileName(
                    $this->currentNamespace . '\\' . $this->currentClassName
                );
                if ($expectedFile !== $this->phpFile) {
                    $this->addIssue(
                        sprintf(
                            'File name %s does not match expected file name %s.',
                            $this->phpFile,
                            $expectedFile
                        )
                    );
                }
            }
        }

        // Continue examining nodes within class.
        if ($this->currentClassName !== null) {
            $this->classVisitor->checkNode($node);
        }

        // @todo Make sure trait names don't end in Trait and Interface names don't end in Interface.
        if ($node instanceof Trait_ && $node->name !== null) {
            $this->currentTraitName = $node->name->toString();
            $this->isLocalScope = true;
        }

        if ($node instanceof Function_ || $node instanceof ClassMethod) {
            $this->currentFunctionName = $node->name->name;

            // Run checks on function node.
            $funcChecker = new FunctionChecker($node);
            $this->addIssues($funcChecker->check());

            if ($node instanceof ClassMethod) {
                $attribs = [
                    'public' => $node->isPublic(),
                    'protected' => $node->isProtected(),
                    'private' => $node->isPrivate(),
                    'abstract' => $node->isAbstract(),
                    'final' => $node->isFinal(),
                    'static' => $node->isStatic(),
                    'magic' => $node->isMagic(),
                ];
            } else {
                $attribs = [];
            }

            $params = $funcChecker->getParams();

            // Start function visitor to examine nodes within function.
            $this->functionVisitor = new FunctionVisitor(
                (string) $this->currentFunctionName,
                $attribs,
                $params
            );
            $this->isLocalScope = true;
        }

        // Continue examining nodes within function.
        if ($this->currentFunctionName !== null) {
            $this->functionVisitor->checkNode($node);
        }

        if ($node instanceof Closure) {
            $this->isLocalScope = true;
        }

        if ($this->isLocalScope) {
            $localScopeChecker = new LocalScopeChecker($node);
            $this->addIssues($localScopeChecker->check());
        }

        if (($node instanceof MethodCall || $node instanceof StaticCall) && $node->name instanceof Identifier) {
            $methodName = $node->name->toString();
            $this->methodCalls[$methodName] = true;
        }

        if ($node instanceof TryCatch) {
            $tryCatchChecker = new TryCatchChecker($node);
            $this->addIssues($tryCatchChecker->check());
        }

        if ($node instanceof Array_) {
            $arrayChecker = new ArrayChecker($node);
            $this->addIssues($arrayChecker->check());
        }

        $funcCallChecker = new FunctionCallChecker($node);
        $this->addIssues($funcCallChecker->check());

        $exprChecker = new ExpressionChecker($node);
        $this->addIssues($exprChecker->check());

        $nameChecker = new NameChecker($node);
        $this->addIssues($nameChecker->check());

        $opChecker = new OperatorChecker($node);
        $this->addIssues($opChecker->check());

        return null;
    }

    public function hasIssues(): bool
    {
        return $this->issues !== [];
    }

    public function isLocalScope(): bool
    {
        return $this->isLocalScope;
    }

    public function leaveNode(Node $node): null
    {
        if ($node instanceof Namespace_) {
            $this->currentNamespace = null;
        }

        if ($node instanceof Class_) {
            $this->classVisitor->checkClass();
            $this->addIssues($this->classVisitor->getIssues());

            $this->currentClassName = null;
            $this->isLocalScope = false;
        }

        if ($node instanceof Trait_) {
            $this->currentTraitName = null;
            $this->isLocalScope = false;
        }

        if ($node instanceof Function_ || $node instanceof ClassMethod) {
            $this->functionVisitor->checkFunction();
            $this->addIssues($this->functionVisitor->getIssues());

            $this->currentFunctionName = null;
            $this->isLocalScope = false;
        }

        if ($node instanceof Closure) {
            $this->isLocalScope = false;
        }

        return null;
    }

    public function printIssues(string $filename): void
    {
        if (! $this->hasIssues()) {
            return;
        }

        if ($this->currentFile !== $filename) {
            echo PHP_EOL . '==> ' . $filename . PHP_EOL;
            $this->currentFile = $filename;
        }

        foreach (array_keys($this->issues) as $issue) {
            echo $issue . PHP_EOL;
        }
    }
}
