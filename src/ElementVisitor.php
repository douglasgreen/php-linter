<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Nikic;

use DouglasGreen\PhpLinter\ComposerFile;
use DouglasGreen\PhpLinter\IssueHolder;
use DouglasGreen\PhpLinter\Nikic\Checker\ArrayChecker;
use DouglasGreen\PhpLinter\Nikic\Checker\ClassChecker;
use DouglasGreen\PhpLinter\Nikic\Checker\CommentChecker;
use DouglasGreen\PhpLinter\Nikic\Checker\ExpressionChecker;
use DouglasGreen\PhpLinter\Nikic\Checker\FunctionCallChecker;
use DouglasGreen\PhpLinter\Nikic\Checker\FunctionChecker;
use DouglasGreen\PhpLinter\Nikic\Checker\LocalScopeChecker;
use DouglasGreen\PhpLinter\Nikic\Checker\NameChecker;
use DouglasGreen\PhpLinter\Nikic\Checker\OperatorChecker;
use DouglasGreen\PhpLinter\Nikic\Checker\TryCatchChecker;
use DouglasGreen\PhpLinter\Nikic\Visitor\ClassVisitor;
use DouglasGreen\PhpLinter\Nikic\Visitor\FunctionVisitor;
use DouglasGreen\PhpLinter\Nikic\Visitor\NameVisitor;
use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\ConstFetch;
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
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeVisitorAbstract;

class ElementVisitor extends NodeVisitorAbstract
{
    use IssueHolder;

    /**
     * @var bool[]
     */
    public $qualifiedNames;

    protected ClassVisitor $classVisitor;

    protected FunctionVisitor $functionVisitor;

    protected NameVisitor $nameVisitor;

    /**
     * @var array<string, bool>
     */
    protected array $classNames = [];

    /**
     * @var array<string, bool>
     */
    protected array $constFetches = [];

    /**
     * @var array<string, bool>
     */
    protected array $funcCalls = [];

    /**
     * @var array<string, bool>
     */
    protected array $methodCalls = [];

    /**
     * @var array<string, bool>
     */
    protected array $useStatements = [];

    protected ?string $currentNamespace = null;

    protected ?string $currentClassName = null;

    protected ?string $currentFile = null;

    protected ?string $currentFunctionName = null;

    /**
     * Are we inside a class, trait, method, function, or closure?
     */
    protected bool $isLocalScope = false;

    public function __construct(
        protected readonly ComposerFile $composerFile,
        protected readonly string $phpFile
    ) {}

    public function beforeTraverse(array $nodes): null
    {
        $this->nameVisitor = new NameVisitor();

        return null;
    }

    public function afterTraverse(array $nodes): null
    {
        // We have to wait until after traverse until both qualified names and use statements are
        // available.
        $qualifiedNames = $this->nameVisitor->getQualifiedNames();
        foreach (array_keys($qualifiedNames) as $qualifiedName) {
            if (isset($this->useStatements[$qualifiedName])) {
                continue;
            }

            if (isset($this->classNames[$qualifiedName])) {
                continue;
            }

            if (isset($this->funcCalls[$qualifiedName])) {
                continue;
            }

            if (isset($this->constFetches[$qualifiedName])) {
                continue;
            }

            $this->addIssue('Import external classes with use statement: ' . $qualifiedName);
        }

        return null;
    }

    public function enterNode(Node $node): null
    {
        if ($node instanceof Namespace_ && $node->name !== null) {
            $this->currentNamespace = implode('\\', $node->name->getParts());
        }

        if ($node instanceof Use_) {
            foreach ($node->uses as $use) {
                $name = (string) $use->name;
                $this->useStatements[$name] = true;
            }
        }

        // Classes and traits share some of the same code.
        // @todo Remove words like Manager, Handler, etc. if no conflict
        if ($node instanceof Class_ || $node instanceof Trait_) {
            $this->currentClassName = $node->name === null ? null : $node->name->name;
            if ($this->currentClassName !== null) {
                $this->classNames[$this->currentClassName] = true;
            }

            // Run checks on class node.
            $classChecker = new ClassChecker($node);
            $this->addIssues($classChecker->check());

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

        if ($node instanceof FuncCall && $node->name instanceof Name) {
            $name = $node->name->toString();
            $this->funcCalls[$name] = true;
        }

        if ($node instanceof ConstFetch && $node->name instanceof Name) {
            $name = $node->name->toString();
            $this->constFetches[$name] = true;
        }

        $this->nameVisitor->checkNode($node);

        if ($node instanceof Name && $node->isFullyQualified()) {
            $name = $node->toString();
            $this->qualifiedNames[$name] = true;
        }

        $opChecker = new OperatorChecker($node);
        $this->addIssues($opChecker->check());

        $commentChecker = new CommentChecker($node);
        $this->addIssues($commentChecker->check());

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
