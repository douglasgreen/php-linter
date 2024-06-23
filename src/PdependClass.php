<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter;

/**
 * @see https://pdepend.org/documentation/software-metrics/index.html
 */
class PdependClass
{
    /**
     * @var int
     */
    public const STATUS_OK = 0;

    /**
     * @var int
     */
    public const STATUS_WARN = 1;

    /**
     * @var int
     */
    public const STATUS_ERROR = 2;

    protected int $errorCount = 0;

    /**
     * @param array<string, mixed> $class
     */
    public function __construct(
        protected readonly array $class
    ) {}

    public function checkMaxAfferentCoupling(int $maxWarn, int $maxError): int
    {
        $afferentCoupling = (int) $this->class['ca'];
        $message = 'Afferent coupling = %d > %d';
        return $this->checkMax($message, $afferentCoupling, $maxWarn, $maxError);
    }

    public function checkMaxCodeRank(float $maxWarn): int
    {
        $codeRank = (float) $this->class['cr'];
        $message = 'Code rank = %0.2f > %0.2f';
        return $this->checkMax($message, $codeRank, $maxWarn);
    }

    public function checkMaxClassSize(int $maxWarn, int $maxError): int
    {
        $csz = (int) $this->class['csz'];
        $message = 'Class size (# methods + # properties) = %d > %d';
        return $this->checkMax($message, $csz, $maxWarn, $maxError);
    }

    public function checkMaxEfferentCoupling(int $maxWarn, int $maxError): int
    {
        $efferentCoupling = (int) $this->class['ce'];
        $message = 'Efferent coupling = %d > %d';
        return $this->checkMax($message, $efferentCoupling, $maxWarn, $maxError);
    }

    public function checkMaxInheritanceDepth(int $maxWarn, int $maxError): int
    {
        $dit = (int) $this->class['dit'];
        $message = 'Inheritance depth = %d > %d';
        return $this->checkMax($message, $dit, $maxWarn, $maxError);
    }

    public function checkMaxLinesOfCode(int $maxWarn, int $maxError): int
    {
        $loc = (int) $this->class['loc'];
        $message = '# lines of code = %d > %d';
        return $this->checkMax($message, $loc, $maxWarn, $maxError);
    }

    public function checkMaxProperties(int $maxWarn, int $maxError): int
    {
        $vars = (int) $this->class['vars'];
        $message = '# properties = %d > %d';
        return $this->checkMax($message, $vars, $maxWarn, $maxError);
    }

    public function checkMaxNonPrivateProperties(int $maxWarn, int $maxError): int
    {
        $varsnp = (int) $this->class['varsnp'];
        $message = '# non-private properties = %d > %d';
        return $this->checkMax($message, $varsnp, $maxWarn, $maxError);
    }

    public function checkMaxPublicMethods(int $maxWarn, int $maxError): int
    {
        $npm = (int) $this->class['npm'];
        $message = '# public methods = %d > %d';
        return $this->checkMax($message, $npm, $maxWarn, $maxError);
    }

    public function checkMinCommentRatio(float $minWarn, float $minError): int
    {
        $eloc = (int) $this->class['eloc'];
        if ($eloc === 0) {
            return self::STATUS_OK;
        }

        $cloc = (int) $this->class['cloc'];
        $ratio = $cloc / $eloc;
        $message = 'Comment to code ratio = %0.2f < %0.2f';
        return $this->checkMin($message, $ratio, $minWarn, $minError);
    }

    protected function checkMax(
        string $message,
        float|int $value,
        float|int $maxWarn,
        float|int|null $maxError = null,
    ): int {
        if ($maxError !== null && $value > $maxError) {
            $this->report(sprintf($message, $value, $maxError), true);
            $this->errorCount++;
            return self::STATUS_ERROR;
        }

        if ($value > $maxWarn) {
            $this->report(sprintf($message, $value, $maxWarn));
            return self::STATUS_WARN;
        }

        return self::STATUS_OK;
    }

    protected function checkMin(
        string $message,
        float|int $value,
        float|int $minWarn,
        float|int $minError,
    ): int {
        if ($value < $minError) {
            $this->report(sprintf($message, $value, $minError), true);
            $this->errorCount++;
            return self::STATUS_ERROR;
        }

        if ($value < $minWarn) {
            $this->report(sprintf($message, $value, $minWarn));
            return self::STATUS_WARN;
        }

        return self::STATUS_OK;
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
