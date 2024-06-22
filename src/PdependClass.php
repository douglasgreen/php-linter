<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter;

/**
 * @see https://pdepend.org/documentation/software-metrics/index.html
 */
class PdependClass
{
    protected int $errorCount = 0;

    /**
     * @param array<string, mixed> $class
     */
    public function __construct(
        protected readonly array $class
    ) {}

    public function checkMaxCodeRank(float $maxWarn): void
    {
        $codeRank = (float) $this->class['cr'];
        $message = 'Code rank = %0.2f > %0.2f';
        $this->checkMax($message, $codeRank, $maxWarn);
    }

    public function checkMaxClassSize(int $maxWarn, int $maxError): void
    {
        $csz = (int) $this->class['csz'];
        $message = 'Class size (# methods + # properties) = %d > %d';
        $this->checkMax($message, $csz, $maxWarn, $maxError);
    }

    public function checkMaxInheritanceDepth(int $maxWarn, int $maxError): void
    {
        $dit = (int) $this->class['dit'];
        $message = 'Inheritance depth = %d > %d';
        $this->checkMax($message, $dit, $maxWarn, $maxError);
    }

    public function checkMaxLinesOfCode(int $maxWarn, int $maxError): void
    {
        $loc = (int) $this->class['loc'];
        $message = '# lines of code = %d > %d';
        $this->checkMax($message, $loc, $maxWarn, $maxError);
    }

    public function checkMaxProperties(int $maxWarn, int $maxError): void
    {
        $vars = (int) $this->class['vars'];
        $message = '# properties = %d > %d';
        $this->checkMax($message, $vars, $maxWarn, $maxError);
    }

    public function checkMaxNonPrivateProperties(int $maxWarn, int $maxError): void
    {
        $varsnp = (int) $this->class['varsnp'];
        $message = '# non-private properties = %d > %d';
        $this->checkMax($message, $varsnp, $maxWarn, $maxError);
    }

    public function checkMaxPublicMethods(int $maxWarn, int $maxError): void
    {
        $npm = (int) $this->class['npm'];
        $message = '# public methods = %d > %d';
        $this->checkMax($message, $npm, $maxWarn, $maxError);
    }

    public function checkMinCommentRatio(float $minWarn, float $minError): void
    {
        $eloc = (int) $this->class['eloc'];
        if ($eloc === 0) {
            return;
        }

        $cloc = (int) $this->class['cloc'];
        $ratio = $cloc / $eloc;
        $message = 'Comment to code ratio = %0.2f < %0.2f';
        $this->checkMin($message, $ratio, $minWarn, $minError);
    }

    protected function checkMax(
        string $message,
        float|int $value,
        float|int $maxWarn,
        float|int|null $maxError = null,
    ): void {
        if ($maxError !== null && $value > $maxError) {
            $this->report(sprintf($message, $value, $maxError), true);
            $this->errorCount++;
        } elseif ($value > $maxWarn) {
            $this->report(sprintf($message, $value, $maxWarn));
        }
    }

    protected function checkMin(
        string $message,
        float|int $value,
        float|int $minWarn,
        float|int $minError,
    ): void {
        if ($value < $minError) {
            $this->report(sprintf($message, $value, $minError), true);
            $this->errorCount++;
        } elseif ($value < $minWarn) {
            $this->report(sprintf($message, $value, $minWarn));
        }
    }

    /**
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    protected function report(string $issue, bool $isError = false): void
    {
        $desc = $isError ? 'error' : 'warning';
        printf('%s: %s (%s)' . PHP_EOL, $this->class['fqname'], $issue, $desc);
    }
}
