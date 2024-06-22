<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter;

/**
 * @see https://pdepend.org/documentation/software-metrics/index.html
 */
class PdependClass
{
    /**
     * @param array<string, mixed> $class
     */
    public function __construct(
        protected readonly array $class
    ) {}

    public function checkMaxClassSize(int $maxSize): void
    {
        $csz = (int) $this->class['csz'];
        if ($csz > $maxSize) {
            $this->report(
                sprintf('Class size (# methods + # properties) = %d > %d', $csz, $maxSize),
            );
        }
    }

    public function checkMaxLinesOfCode(int $maxLoc): void
    {
        $loc = (int) $this->class['loc'];
        if ($loc > $maxLoc) {
            $this->report(sprintf('# lines of code = %d > %d', $loc, $maxLoc));
        }
    }

    public function checkMaxProperties(int $maxVars): void
    {
        $vars = (int) $this->class['vars'];
        if ($vars > $maxVars) {
            $this->report(sprintf('# properties = %d > %d', $vars, $maxVars));
        }
    }

    public function checkMaxNonPrivateProperties(int $maxVarsnp): void
    {
        $varsnp = (int) $this->class['varsnp'];
        if ($varsnp > $maxVarsnp) {
            $this->report(sprintf('# non-private properties = %d > %d', $varsnp, $maxVarsnp));
        }
    }

    public function checkMaxPublicMethods(int $maxNpm): void
    {
        $npm = (int) $this->class['npm'];
        if ($npm > $maxNpm) {
            $this->report(sprintf('# public methods = %d > %d', $npm, $maxNpm));
        }
    }

    public function checkMinCommentRatio(float $minRatio): void
    {
        $eloc = (int) $this->class['eloc'];
        if ($eloc === 0) {
            return;
        }

        $cloc = (int) $this->class['cloc'];
        $ratio = $cloc / $eloc;
        if ($ratio < $minRatio) {
            $this->report(sprintf('Comment to code ratio = %0.2f < %0.2f', $ratio, $minRatio));
        }
    }

    protected function report(string $issue): void
    {
        printf('%s: %s' . PHP_EOL, $this->class['fqname'], $issue);
    }
}
