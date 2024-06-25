<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Nikic;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class ElementVisitor extends NodeVisitorAbstract
{
    /**
     * @var list<string>
     */
    protected array $issues = [];

    protected ?string $currentFile = null;

    public function enterNode(Node $node): Node|int|null
    {
        $nameChecker = new NameChecker($node);
        $this->issues = array_merge($this->issues, $nameChecker->check());
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

        foreach ($this->issues as $issue) {
            echo $issue . PHP_EOL;
        }
    }
}
