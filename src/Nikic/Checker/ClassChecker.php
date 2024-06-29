<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Nikic\Checker;

class ClassChecker extends NodeChecker
{
    /**
     * @return array<string, bool>
     * @todo Check class name
     */
    public function check(): array
    {
        return $this->getIssues();
    }
}
