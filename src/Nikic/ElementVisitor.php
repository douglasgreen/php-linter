<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Nikic;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\Node\Stmt\TryCatch;
use PhpParser\NodeVisitorAbstract;

class ElementVisitor extends NodeVisitorAbstract
{
    /**
     * @var array<string, bool>
     */
    protected array $issues = [];

    protected ?string $currentClassName = null;

    protected ?string $currentFile = null;

    protected ?string $currentFunctionName = null;

    protected ?string $currentMethodName = null;

    protected ?string $currentTraitName = null;

    /**
     * Are we inside a class, trait, method, function, or closure?
     */
    protected bool $isLocalScope = false;

    public function enterNode(Node $node): null
    {
        if ($node instanceof Class_ && $node->name !== null) {
            $this->currentClassName = $node->name->toString();
            $this->isLocalScope = true;
        }

        if ($node instanceof Trait_ && $node->name !== null) {
            $this->currentTraitName = $node->name->toString();
            $this->isLocalScope = true;
        }

        if ($node instanceof ClassMethod && $node->name !== null) {
            $this->currentMethodName = $node->name->toString();
            $this->isLocalScope = true;
        }

        if ($node instanceof Function_ && $node->name !== null) {
            $this->currentFunctionName = $node->name->toString();
            $this->isLocalScope = true;
        }

        if ($node instanceof Closure) {
            $this->isLocalScope = true;
        }

        if ($this->isLocalScope) {
            $localScopeChecker = new LocalScopeChecker($node);
            $this->addIssues($localScopeChecker->check());
        }

        if ($this->currentClassName !== null) {
            $classChecker = new ClassChecker($node);
            $this->addIssues($classChecker->check($this->currentClassName));
        }

        if ($node instanceof Function_ || $node instanceof ClassMethod) {
            $funcChecker = new FunctionChecker($node);
            $this->addIssues($funcChecker->check());
        }

        if ($node instanceof TryCatch) {
            $tryCatchChecker = new TryCatchChecker($node);
            $this->addIssues($tryCatchChecker->check());
        }

        if ($node instanceof Array_) {
            $arrayChecker = new ArrayChecker($node);
            $this->addIssues($arrayChecker->check());
        }

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
        if ($node instanceof Class_) {
            $this->currentClassName = null;
            $this->isLocalScope = false;
        }

        if ($node instanceof Trait_) {
            $this->currentTraitName = null;
            $this->isLocalScope = false;
        }

        if ($node instanceof Function_) {
            $this->currentFunctionName = null;
            $this->isLocalScope = false;
        }

        if ($node instanceof ClassMethod) {
            $this->currentMethodName = null;
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

    /**
     * @param array<string, bool> $issues
     */
    protected function addIssues(array $issues): void
    {
        $this->issues = array_merge($this->issues, $issues);
    }
}
