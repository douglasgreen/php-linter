<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Nikic;

use PhpParser\Node;

abstract class BaseChecker
{
    /**
     * @var list<string>
     */
    protected array $issues = [];

    public function __construct(
        protected readonly Node $node
    ) {}

    /**
     * Do the check and return a list of issues.
     * @return list<string>
     */
    abstract public function check(): array;
}
