<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Nikic\Checker;

/**
 * Check classes and traits.
 */
class ClassChecker extends NodeChecker
{
    /**
     * @return array<string, bool>
     * @todo Check something here.
     */
    public function check(): array
    {
        return $this->getIssues();
    }
}
