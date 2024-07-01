<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Pdepend;

/**
 * @see https://pdepend.org/documentation/software-metrics/index.html
 */
class MetricChecker
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

    /**
     * @var array<int, list<string>>
     */
    protected array $issues = [];

    protected ?string $currentFile = null;

    protected int $errorCount = 0;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        protected readonly array $data,
        protected readonly ?string $className = null,
        protected readonly ?string $functionName = null,
    ) {}

    public function checkMaxAfferentCoupling(int $maxWarn, int $maxError): int
    {
        $afferentCoupling = (int) $this->data['ca'];
        $message = 'Afferent coupling = %d > %d';
        return $this->checkMax($message, $afferentCoupling, $maxWarn, $maxError);
    }

    public function checkMaxClassSize(int $maxWarn, int $maxError): int
    {
        $csz = (int) $this->data['csz'];
        $message = 'Class size (# methods + # properties) = %d > %d';
        return $this->checkMax($message, $csz, $maxWarn, $maxError);
    }

    public function checkMaxCodeRank(float $maxWarn, float $maxError): int
    {
        $codeRank = (float) $this->data['cr'];
        $message = 'Code rank = %0.2f > %0.2f';
        return $this->checkMax($message, $codeRank, $maxWarn, $maxError);
    }

    public function checkMaxCyclomaticComplexity(int $maxWarn, int $maxError): int
    {
        $ecc = (int) $this->data['ccn2'];
        $message = 'Extended cyclomatic complexity = %d > %d';
        return $this->checkMax($message, $ecc, $maxWarn, $maxError);
    }

    public function checkMaxEfferentCoupling(int $maxWarn, int $maxError): int
    {
        $efferentCoupling = (int) $this->data['ce'];
        $message = 'Efferent coupling = %d > %d';
        return $this->checkMax($message, $efferentCoupling, $maxWarn, $maxError);
    }

    public function checkMaxHalsteadEffort(int $maxWarn, int $maxError): int
    {
        $halsteadEffort = (int) $this->data['he'];
        $message = 'Halstead effort = %d > %d';
        return $this->checkMax($message, $halsteadEffort, $maxWarn, $maxError);
    }

    public function checkMaxInheritanceDepth(int $maxWarn, int $maxError): int
    {
        $dit = (int) $this->data['dit'];
        $message = 'Inheritance depth = %d > %d';
        return $this->checkMax($message, $dit, $maxWarn, $maxError);
    }

    public function checkMaxLinesOfCode(int $maxWarn, int $maxError): int
    {
        $loc = (int) $this->data['loc'];
        $message = '# lines of code = %d > %d';
        return $this->checkMax($message, $loc, $maxWarn, $maxError);
    }

    public function checkMaxNonPrivateProperties(int $maxWarn, int $maxError): int
    {
        $varsnp = (int) $this->data['varsnp'];
        $message = '# non-private properties = %d > %d';
        return $this->checkMax($message, $varsnp, $maxWarn, $maxError);
    }

    public function checkMaxNpathComplexity(int $maxWarn, int $maxError): int
    {
        $npath = (int) $this->data['npath'];
        $message = 'NPath complexity = %d > %d';
        return $this->checkMax($message, $npath, $maxWarn, $maxError);
    }

    public function checkMaxNumberOfChildClasses(int $maxWarn, int $maxError): int
    {
        $nocc = (int) $this->data['nocc'];
        $message = '# child classes = %d > %d';
        return $this->checkMax($message, $nocc, $maxWarn, $maxError);
    }

    public function checkMaxObjectCoupling(int $maxWarn, int $maxError): int
    {
        $objectCoupling = (int) $this->data['cbo'];
        $message = 'Coupling between objects = %d > %d';
        return $this->checkMax($message, $objectCoupling, $maxWarn, $maxError);
    }

    public function checkMaxProperties(int $maxWarn, int $maxError): int
    {
        $vars = (int) $this->data['vars'];
        $message = '# properties = %d > %d';
        return $this->checkMax($message, $vars, $maxWarn, $maxError);
    }

    public function checkMaxPublicMethods(int $maxWarn, int $maxError): int
    {
        $npm = (int) $this->data['npm'];
        $message = '# public methods = %d > %d';
        return $this->checkMax($message, $npm, $maxWarn, $maxError);
    }

    public function checkMinCommentRatio(float $minWarn, float $minError): int
    {
        $eloc = (int) $this->data['eloc'];
        if ($eloc === 0) {
            return self::STATUS_OK;
        }

        $cloc = (int) $this->data['cloc'];
        $ratio = $cloc / $eloc;
        $message = 'Comment to code ratio = %0.2f < %0.2f';
        return $this->checkMin($message, $ratio, $minWarn, $minError);
    }

    public function checkMinMaintainabilityIndex(float $minWarn, float $minError): int
    {
        $maintainabilityIndex = (int) $this->data['mi'];
        $message = 'Maintainability index = %0.2f < %0.2f';
        return $this->checkMin($message, $maintainabilityIndex, $minWarn, $minError);
    }

    /**
     * @return list<string>
     */
    public function getErrors(): array
    {
        return $this->issues[self::STATUS_ERROR] ?? [];
    }

    /**
     * @return list<string>
     */
    public function getWarnings(): array
    {
        return $this->issues[self::STATUS_WARN] ?? [];
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

        foreach ($this->getErrors() as $error) {
            echo $error . PHP_EOL;
        }

        foreach ($this->getWarnings() as $warning) {
            echo $warning . PHP_EOL;
        }
    }

    protected function checkMax(
        string $message,
        float|int $value,
        float|int $maxWarn,
        float|int|null $maxError,
    ): int {
        if ($value > $maxError) {
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

    protected function report(string $issue, bool $isError = false): void
    {
        if ($isError) {
            $level = self::STATUS_ERROR;

            // Code rank is a signal to test classes so it's medium/high rather than warn/error.
            $desc = str_contains($issue, 'Code rank') ? 'high' : 'error';
        } else {
            $level = self::STATUS_WARN;
            $desc = str_contains($issue, 'Code rank') ? 'medium' : 'warning';
        }

        if ($this->className !== null) {
            $name = $this->className;
            if ($this->functionName !== null) {
                $name .= '::' . $this->functionName . '()';
            }
        } elseif ($this->functionName !== null) {
            $name = $this->functionName . '()';
        } else {
            $name = 'File';
        }

        $issue = sprintf('%s - %s (%s)', $name, $issue, $desc);
        $this->issues[$level][] = $issue;
    }
}
