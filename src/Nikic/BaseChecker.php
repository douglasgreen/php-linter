<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Nikic;

use PhpParser\Node;

abstract class BaseChecker
{
    /**
     * @var array<string, bool>
     */
    protected array $issues = [];

    public function __construct(
        protected readonly Node $node
    ) {}

    /**
     * Do the check and return a list of issues.
     * @return array<string, bool>
     */
    abstract public function check(): array;

    protected function addIssue(string $issue): void
    {
        $this->issues[$issue] = true;
    }

    /**
     * @return array<string, bool>
     */
    protected function getIssues(): array
    {
        return $this->issues;
    }
}
