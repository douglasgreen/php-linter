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
use PhpParser\Node\Stmt\Const_;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\Node\Stmt\TryCatch;
use PhpParser\NodeVisitorAbstract;

/**
 * Visitor for traversing PHP AST nodes to detect linting issues.
 *
 * @package DouglasGreen\PhpLinter
 *
 * @since 1.0.0
 */
class ElementVisitor extends NodeVisitorAbstract
{
    /** Visitor for class-related checks. */
    protected ClassVisitor $classVisitor;

    /** Visitor for function-related checks. */
    protected FunctionVisitor $functionVisitor;

    /** Visitor for magic number checks. */
    protected MagicNumberVisitor $magicNumberVisitor;

    /** Visitor for superglobal usage checks. */
    protected SuperglobalUsageVisitor $superglobalUsageVisitor;

    /** Current namespace name. */
    protected ?string $currentNamespace = null;

    /** Current class name. */
    protected ?string $currentClassName = null;

    /** Current function or method name. */
    protected ?string $currentFunctionName = null;

    /**
     * Tracks method calls encountered.
     *
     * @var array<string, bool>
     */
    protected array $methodCalls = [];

    /** Indicates if currently inside a class, trait, method, function, or closure. */
    protected bool $isLocalScope = false;

    /** Indicates if the current class is readonly. */
    protected bool $isReadonlyClass = false;

    /** Indicates if currently inside a class, interface, trait, or enum. */
    protected bool $inClassLike = false;

    /**
     * Constructs a new ElementVisitor instance.
     *
     * @param ComposerFile $composerFile The composer file handler.
     * @param string $phpFile The PHP file path being processed.
     * @param IssueHolder $issueHolder The issue holder for collecting issues.
     */
    public function __construct(
        protected readonly ComposerFile $composerFile,
        protected readonly string $phpFile,
        protected readonly IssueHolder $issueHolder,
    ) {
        $this->issueHolder->setCurrentFile($phpFile);
    }

    /**
     * Initializes visitors before traversal.
     *
     * @param array<Node> $nodes The nodes to traverse.
     */
    public function beforeTraverse(array $nodes): null
    {
        $this->magicNumberVisitor = new MagicNumberVisitor($this->issueHolder);
        $this->superglobalUsageVisitor = new SuperglobalUsageVisitor($this->issueHolder);
        $this->issueHolder->setCurrentFile($this->phpFile);

        return null;
    }

    /**
     * Finalizes checks after traversal.
     *
     * @param array<Node> $nodes The nodes traversed.
     */
    public function afterTraverse(array $nodes): null
    {
        $this->magicNumberVisitor->checkDuplicates();

        return null;
    }

    /**
     * Enters a node to perform checks.
     *
     * @param Node $node The node being entered.
     */
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
        $this->checkTopLevelFunction($node);
        $this->checkTopLevelConst($node);
        $this->checkTopLevelDefine($node);

        return null;
    }

    /**
     * Checks if currently in local scope.
     *
     * @return bool True if in local scope, false otherwise.
     */
    public function isLocalScope(): bool
    {
        return $this->isLocalScope;
    }

    /**
     * Leaves a node to clean up state.
     *
     * @param Node $node The node being left.
     */
    public function leaveNode(Node $node): null
    {
        if ($node instanceof Namespace_) {
            $this->currentNamespace = null;
        }

        if ($node instanceof Class_ || $node instanceof Trait_ || $node instanceof Interface_ || $node instanceof Enum_) {
            $this->classVisitor->checkClass();

            $this->currentClassName = null;
            $this->isReadonlyClass = false;
            $this->isLocalScope = false;
            $this->inClassLike = false;
            $this->issueHolder->setCurrentClass(null);
        }

        if ($node instanceof Function_ || $node instanceof ClassMethod) {
            $this->functionVisitor->checkFunction();

            $this->currentFunctionName = null;
            $this->isLocalScope = false;
            $this->issueHolder->setCurrentFunction(null);
        }

        if ($node instanceof Closure) {
            $this->isLocalScope = false;
        }

        $this->magicNumberVisitor->leaveNode($node);
        $this->superglobalUsageVisitor->leaveNode($node);

        return null;
    }

    /**
     * Handles namespace nodes.
     *
     * @param Node $node The current node.
     */
    private function handleNamespace(Node $node): void
    {
        if ($node instanceof Namespace_ && $node->name instanceof Name) {
            $this->currentNamespace = implode('\\', $node->name->getParts());
        }
    }

    /**
     * Handles class or trait nodes.
     *
     * @param Node $node The current node.
     */
    private function handleClassOrTrait(Node $node): void
    {
        if ($node instanceof Class_ || $node instanceof Trait_ || $node instanceof Interface_ || $node instanceof Enum_) {
            $this->inClassLike = true;
            $this->currentClassName = $node->name instanceof Identifier ? $node->name->name : null;
            $this->isReadonlyClass = $node instanceof Class_ && $node->isReadonly();
            $this->issueHolder->setCurrentClass($this->currentClassName);

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
            $this->classVisitor = new ClassVisitor($this->issueHolder, $this->currentClassName, $attribs);
            $this->isLocalScope = true;

            // Check namespace name, class name, and file path.
            if ($this->currentNamespace !== null) {
                $expectedFile = $this->composerFile->convertClassNameToFileName(
                    $this->currentNamespace . '\\' . $this->currentClassName,
                );
                if ($expectedFile !== null && $expectedFile !== $this->phpFile) {
                    $this->issueHolder->addIssue(
                        sprintf(
                            'Rename the file "%s" to "%s" to match the class namespace according to PSR-4 autoloading standards.',
                            $this->phpFile,
                            $expectedFile,
                        ),
                    );
                } elseif ($expectedFile === null) {
                    $this->issueHolder->addIssue(
                        sprintf(
                            'Class namespace "%s\\%s" does not match any PSR-4 autoload path in composer.json.',
                            $this->currentNamespace,
                            $this->currentClassName,
                        ),
                    );
                }
            }
        }
    }

    /**
     * Checks a node within a class context.
     *
     * @param Node $node The current node.
     */
    private function checkClassNode(Node $node): void
    {
        if ($this->currentClassName !== null) {
            $this->classVisitor->checkNode($node);
        }
    }

    /**
     * Handles function or method nodes.
     *
     * @param Node $node The current node.
     */
    private function handleFunctionOrMethod(Node $node): void
    {
        if ($node instanceof Function_ || $node instanceof ClassMethod) {
            $this->currentFunctionName = $node->name->name;
            $this->issueHolder->setCurrentFunction($this->currentFunctionName);

            // Run checks on function node.
            $funcChecker = new FunctionChecker($node, $this->issueHolder, $this->isReadonlyClass);
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
                $this->issueHolder,
                $this->currentFunctionName,
                $attribs,
                $params,
            );
            $this->isLocalScope = true;
        }
    }

    /**
     * Checks a node within a function context.
     *
     * @param Node $node The current node.
     */
    private function checkFunctionNode(Node $node): void
    {
        if ($this->currentFunctionName !== null) {
            $this->functionVisitor->checkNode($node);
        }
    }

    /**
     * Handles closure nodes.
     *
     * @param Node $node The current node.
     */
    private function handleClosure(Node $node): void
    {
        if ($node instanceof Closure) {
            $this->isLocalScope = true;
        }
    }

    /**
     * Checks a node within local scope.
     *
     * @param Node $node The current node.
     */
    private function checkLocalScope(Node $node): void
    {
        if ($this->isLocalScope) {
            $localScopeChecker = new LocalScopeChecker($node, $this->issueHolder);
            $this->issueHolder->addIssues($localScopeChecker->check());
        }
    }

    /**
     * Tracks method calls.
     *
     * @param Node $node The current node.
     */
    private function trackMethodCalls(Node $node): void
    {
        if (($node instanceof MethodCall || $node instanceof StaticCall) && $node->name instanceof Identifier) {
            $methodName = $node->name->toString();
            $this->methodCalls[$methodName] = true;
        }
    }

    /**
     * Checks try-catch blocks.
     *
     * @param Node $node The current node.
     */
    private function checkTryCatch(Node $node): void
    {
        if ($node instanceof TryCatch) {
            $tryCatchChecker = new TryCatchChecker($node, $this->issueHolder);
            $this->issueHolder->addIssues($tryCatchChecker->check());
        }
    }

    /**
     * Runs generic checkers on a node.
     *
     * @param Node $node The current node.
     */
    private function runGenericCheckers(Node $node): void
    {
        $funcCallChecker = new FunctionCallChecker($node, $this->issueHolder);
        $this->issueHolder->addIssues($funcCallChecker->check());

        $exprChecker = new ExpressionChecker($node, $this->issueHolder);
        $this->issueHolder->addIssues($exprChecker->check());

        $nameChecker = new NameChecker($node, $this->issueHolder);
        $this->issueHolder->addIssues($nameChecker->check());

        $this->magicNumberVisitor->enterNode($node);
        $this->magicNumberVisitor->checkNode($node);

        $this->superglobalUsageVisitor->enterNode($node);

        $opChecker = new OperatorChecker($node, $this->issueHolder);
        $this->issueHolder->addIssues($opChecker->check());
    }

    /**
     * Checks for top-level function definitions.
     *
     * @param Node $node The current node.
     */
    private function checkTopLevelFunction(Node $node): void
    {
        if ($node instanceof Function_ && !$this->inClassLike) {
            $this->issueHolder->addIssue(
                sprintf(
                    'Function "%s" should be moved inside a class as a method according to PSR-1.',
                    $node->name->name,
                ),
            );
        }
    }

    /**
     * Checks for top-level constant definitions.
     *
     * @param Node $node The current node.
     */
    private function checkTopLevelConst(Node $node): void
    {
        if ($node instanceof Const_ && !$this->inClassLike) {
            foreach ($node->consts as $const) {
                $this->issueHolder->addIssue(
                    sprintf(
                        'Constant "%s" should be moved inside a class as a class constant according to PSR-1.',
                        $const->name->name,
                    ),
                );
            }
        }
    }

    /**
     * Checks for top-level define() calls.
     *
     * @param Node $node The current node.
     */
    private function checkTopLevelDefine(Node $node): void
    {
        if ($node instanceof FuncCall && !$this->inClassLike && ($node->name instanceof Name && $node->name->toString() === 'define')) {
            $this->issueHolder->addIssue(
                'Global define() call should be moved inside a class as a class constant according to PSR-1.',
            );
        }
    }
}
