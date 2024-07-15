<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Nikic\Checker;

use PhpParser\Node\Expr\Array_;

class ArrayChecker extends NodeChecker
{
    /**
     * @var array<string|int, bool>
     */
    protected $duplicateKeys = [];

    /**
     * @todo Add check.
     *
     * I was checking for duplicate key in array but PHPStan already does that.
     *
     * @return array<string, bool>
     */
    public function check(): array
    {
        if (! $this->node instanceof Array_) {
            return [];
        }

        return $this->getIssues();
    }
}
