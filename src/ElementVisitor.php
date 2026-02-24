<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter;

use DouglasGreen\PhpLinter\Checker\ExpressionChecker;
use DouglasGreen\PhpLinter\Checker\FunctionCallChecker;
use DouglasGreen\PhpLinter\Checker\FunctionChecker;
use DouglasGreen\PhpLinter\Checker\LocalScopeChecker;
use DouglasGreen\PhpLinter\Checker\NameChecker;
use DouglasGreen\PhpLinter\Checker\OperatorChecker;
use DouglasGreen\PhpLinter\Checker\TryCatchChecker;
use DouglasGreen\PhpLinter\Visitor\ClassVisitor;
use DouglasGreen\PhpLinter\Visitor\FunctionVisitor;
use DouglasGreen\PhpLinter\Visitor\MagicNumberVisitor;
use DouglasGreen\PhpLinter\Visitor\SuperglobalUsageVisitor;
use PhpParser\Node;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
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

    protected MagicNumberVisitor $magicNumberVisitor;

    protected SuperglobalUsageVisitor $superglobalUsageVisitor;

    protected ?string $currentNamespace = null;

    protected ?string $currentClassName = null;

    protected ?string $currentFile = null;

    protected ?string $currentFunctionName = null;

    /** @var array<string, bool> */
    protected array $methodCalls = [];

    /** Are we inside a class, trait, method, function, or closure? */
    protected bool $isLocalScope = false;

    public function __construct(
        protected readonly ComposerFile $composerFile,
        protected readonly string $phpFile,
    ) {}

    public function beforeTraverse(array $nodes): null
    {
        $this->magicNumberVisitor = new MagicNumberVisitor();
        $this->superglobalUsageVisitor = new SuperglobalUsageVisitor();

        return null;
    }

    public function afterTraverse(array $nodes): null
    {
        $this->magicNumberVisitor->checkDuplicates();
        $this->addIssues($this->magicNumberVisitor->getIssues());

        $this->addIssues($this->superglobalUsageVisitor->getIssues());

        return null;
    }

    public function enterNode(Node $node): null
    {
        $this->handleNamespace($node);
        $this->handleClassOrTrait($node);
        $this->checkClassNode($node);
        $this->handleFunctionOrMethod($node);
        $this->checkFunctionNode($node);
        $this->handleClosure($node);
        $this->checkLocalScope($node);
        $this->trackMethodCalls($node);
        $this->checkTryCatch($node);
        $this->runGenericCheckers($node);

        return null;
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

        if ($node instanceof Class_ || $node instanceof Trait_) {
            $this->classVisitor->checkClass();
            $this->addIssues($this->classVisitor->getIssues());

            $this->currentClassName = null;
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

        $this->magicNumberVisitor->leaveNode($node);
        $this->superglobalUsageVisitor->leaveNode($node);

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

    private function handleNamespace(Node $node): void
    {
        if ($node instanceof Namespace_ && $node->name instanceof Name) {
            $this->currentNamespace = implode('\\', $node->name->getParts());
        }
    }

    private function handleClassOrTrait(Node $node): void
    {
        if ($node instanceof Class_ || $node instanceof Trait_) {
            $this->currentClassName = $node->name instanceof Identifier ? $node->name->name : null;

            if ($node instanceof Class_) {
                $attribs = [
                    'abstract' => $node->isAbstract(),
                    'final' => $node->isFinal(),
                    'readonly' => $node->isReadonly(),
                    'anonymous' => $node->isAnonymous(),
                ];
            } else {
                $attribs = [];
            }

            // Start class visitor to examine nodes within class.
            $this->classVisitor = new ClassVisitor($this->currentClassName, $attribs);
            $this->isLocalScope = true;

            // Check namespace name, class name, and file path.
            if ($this->currentNamespace !== null) {
                $expectedFile = $this->composerFile->convertClassNameToFileName(
                    $this->currentNamespace . '\\' . $this->currentClassName,
                );
                if ($expectedFile !== $this->phpFile) {
                    $this->addIssue(
                        sprintf(
                            'Rename the file "%s" to "%s" to match the class namespace according to PSR-4 autoloading standards.',
                            $this->phpFile,
                            $expectedFile,
                        ),
                    );
                }
            }
        }
    }

    private function checkClassNode(Node $node): void
    {
        if ($this->currentClassName !== null) {
            $this->classVisitor->checkNode($node);
        }
    }

    private function handleFunctionOrMethod(Node $node): void
    {
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
                $params,
            );
            $this->isLocalScope = true;
        }
    }

    private function checkFunctionNode(Node $node): void
    {
        if ($this->currentFunctionName !== null) {
            $this->functionVisitor->checkNode($node);
        }
    }

    private function handleClosure(Node $node): void
    {
        if ($node instanceof Closure) {
            $this->isLocalScope = true;
        }
    }

    private function checkLocalScope(Node $node): void
    {
        if ($this->isLocalScope) {
            $localScopeChecker = new LocalScopeChecker($node);
            $this->addIssues($localScopeChecker->check());
        }
    }

    private function trackMethodCalls(Node $node): void
    {
        if (($node instanceof MethodCall || $node instanceof StaticCall) && $node->name instanceof Identifier) {
            $methodName = $node->name->toString();
            $this->methodCalls[$methodName] = true;
        }
    }

    private function checkTryCatch(Node $node): void
    {
        if ($node instanceof TryCatch) {
            $tryCatchChecker = new TryCatchChecker($node);
            $this->addIssues($tryCatchChecker->check());
        }
    }

    private function runGenericCheckers(Node $node): void
    {
        $funcCallChecker = new FunctionCallChecker($node);
        $this->addIssues($funcCallChecker->check());

        $exprChecker = new ExpressionChecker($node);
        $this->addIssues($exprChecker->check());

        $nameChecker = new NameChecker($node);
        $this->addIssues($nameChecker->check());

        $this->magicNumberVisitor->enterNode($node);
        $this->magicNumberVisitor->checkNode($node);

        $this->superglobalUsageVisitor->enterNode($node);

        $opChecker = new OperatorChecker($node);
        $this->addIssues($opChecker->check());
    }
}
