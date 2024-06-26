<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Nikic;

use PhpParser\Node\Stmt\ClassMethod;

class ClassChecker extends BaseChecker
{
    /**
     * @return array<string, bool>
     */
    public function check(string $className = null): array
    {
        if ($this->node instanceof ClassMethod) {
            $methodName = $this->node->name->toString();
            if (strcasecmp($methodName, (string) $className) === 0) {
                $this->addIssue(
                    sprintf(
                        'Use __construct instead of PHP 4 style constructors like %s() in class %s',
                        $methodName,
                        $className,
                    ),
                );
            }
        }

        return $this->getIssues();
    }
}
