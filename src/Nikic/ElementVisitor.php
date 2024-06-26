<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Nikic;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class ElementVisitor extends NodeVisitorAbstract
{
    /**
     * @var array<string, bool>
     */
    protected array $issues = [];

    protected ?string $currentFile = null;

    public function enterNode(Node $node): Node|int|null
    {
        $funcCallChecker = new FunctionCallChecker($node);
        $this->addIssues($funcCallChecker->check());

        $funcParamChecker = new FunctionParameterChecker($node);
        $this->addIssues($funcParamChecker->check());

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
