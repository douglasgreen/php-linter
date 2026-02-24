<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Metrics;

/**
 * @see https://pdepend.org/documentation/software-metrics/index.html
 */
class MetricChecker
{
    /** @var int */
    public const STATUS_OK = 0;

    /** @var int */
    public const STATUS_ERROR = 1;

    /** @var list<string> */
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

    public function checkMaxAfferentCoupling(int $limit): int
    {
        $afferentCoupling = (int) $this->data['ca'];
        $message = 'Afferent coupling = %d > %d';
        $hint = 'Reduce incoming dependencies; consider interface segregation or decoupling.';
        return $this->checkMax($message, $afferentCoupling, $limit, $hint);
    }

    public function checkMaxClassSize(int $limit): int
    {
        $csz = (int) $this->data['csz'];
        $message = 'Class size (# methods + # properties) = %d > %d';
        $hint = 'Reduce class size; extract classes or delegate responsibilities.';
        return $this->checkMax($message, $csz, $limit, $hint);
    }

    public function checkMaxCodeRank(float $limit): int
    {
        $codeRank = (float) $this->data['cr'];
        $message = 'Code rank = %0.2f > %0.2f';
        $hint = 'High rank implies high responsibility/centrality; ensure stability and test coverage.';
        return $this->checkMax($message, $codeRank, $limit, $hint);
    }

    public function checkMaxCyclomaticComplexity(int $limit): int
    {
        $ecc = (int) $this->data['ccn2'];
        $message = 'Extended cyclomatic complexity = %d > %d';
        $hint = 'Reduce complexity; extract methods or simplify conditional logic.';
        return $this->checkMax($message, $ecc, $limit, $hint);
    }

    public function checkMaxEfferentCoupling(int $limit): int
    {
        $efferentCoupling = (int) $this->data['ce'];
        $message = 'Efferent coupling = %d > %d';
        $hint = 'Reduce outgoing dependencies; use dependency injection or interfaces.';
        return $this->checkMax($message, $efferentCoupling, $limit, $hint);
    }

    public function checkMaxHalsteadEffort(int $limit): int
    {
        $halsteadEffort = (int) $this->data['he'];
        $message = 'Halstead effort = %d > %d';
        $hint = 'Reduce code volume/complexity; simplify logic or break down methods.';
        return $this->checkMax($message, $halsteadEffort, $limit, $hint);
    }

    public function checkMaxInheritanceDepth(int $limit): int
    {
        $dit = (int) $this->data['dit'];
        $message = 'Inheritance depth = %d > %d';
        $hint = 'Reduce inheritance depth; prefer composition over inheritance.';
        return $this->checkMax($message, $dit, $limit, $hint);
    }

    public function checkMaxLinesOfCode(int $limit): int
    {
        $loc = (int) $this->data['loc'];
        $message = '# lines of code = %d > %d';
        $hint = 'Reduce lines of code; extract logic into smaller units.';
        return $this->checkMax($message, $loc, $limit, $hint);
    }

    public function checkMaxNonPrivateProperties(int $limit): int
    {
        $varsnp = (int) $this->data['varsnp'];
        $message = '# non-private properties = %d > %d';
        $hint = 'Encapsulate fields; make properties private and use accessors.';
        return $this->checkMax($message, $varsnp, $limit, $hint);
    }

    public function checkMaxNpathComplexity(int $limit): int
    {
        $npath = (int) $this->data['npath'];
        $message = 'NPath complexity = %d > %d';
        $hint = 'Reduce branching paths; simplify control structures or return early.';
        return $this->checkMax($message, $npath, $limit, $hint);
    }

    public function checkMaxNumberOfChildClasses(int $limit): int
    {
        $nocc = (int) $this->data['nocc'];
        $message = '# child classes = %d > %d';
        $hint = 'Review hierarchy; base class may be too generic or complex.';
        return $this->checkMax($message, $nocc, $limit, $hint);
    }

    public function checkMaxObjectCoupling(int $limit): int
    {
        $objectCoupling = (int) $this->data['cbo'];
        $message = 'Coupling between objects = %d > %d';
        $hint = 'Reduce coupling; decouple from other objects or use events.';
        return $this->checkMax($message, $objectCoupling, $limit, $hint);
    }

    public function checkMaxProperties(int $limit): int
    {
        $vars = (int) $this->data['vars'];
        $message = '# properties = %d > %d';
        $hint = 'Reduce state; extract value objects or services.';
        return $this->checkMax($message, $vars, $limit, $hint);
    }

    public function checkMaxPublicMethods(int $limit): int
    {
        $npm = (int) $this->data['npm'];
        $message = '# public methods = %d > %d';
        $hint = 'Reduce public interface; hide internal methods.';
        return $this->checkMax($message, $npm, $limit, $hint);
    }

    public function checkMinCommentRatio(float $limit): int
    {
        $eloc = (int) $this->data['eloc'];
        if ($eloc === 0) {
            return self::STATUS_OK;
        }

        $cloc = (int) $this->data['cloc'];
        $ratio = $cloc / $eloc;
        $message = 'Comment to code ratio = %0.2f < %0.2f';
        $hint = 'Increase documentation; add comments for complex logic.';
        return $this->checkMin($message, $ratio, $limit, $hint);
    }

    public function checkMinMaintainabilityIndex(float $limit): int
    {
        $maintainabilityIndex = (int) $this->data['mi'];
        $message = 'Maintainability index = %0.2f < %0.2f';
        $hint = 'Improve maintainability; refactor complex code.';
        return $this->checkMin($message, $maintainabilityIndex, $limit, $hint);
    }

    /**
     * @return list<string>
     */
    public function getIssues(): array
    {
        return $this->issues;
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

        foreach ($this->getIssues() as $issue) {
            echo $issue . PHP_EOL;
        }
    }

    protected function checkMax(
        string $message,
        float|int $value,
        float|int $limit,
        string $hint = '',
    ): int {
        if ($value > $limit) {
            $this->report(sprintf($message, $value, $limit), $hint);
            $this->errorCount++;
            return self::STATUS_ERROR;
        }

        return self::STATUS_OK;
    }

    protected function checkMin(
        string $message,
        float|int $value,
        float|int $limit,
        string $hint = '',
    ): int {
        if ($value < $limit) {
            $this->report(sprintf($message, $value, $limit), $hint);
            $this->errorCount++;
            return self::STATUS_ERROR;
        }

        return self::STATUS_OK;
    }

    protected function report(string $issue, string $hint): void
    {
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

        $output = sprintf('%s - %s', $name, $issue);
        if ($hint !== '') {
            $output .= PHP_EOL . '    Action: ' . $hint;
        }

        $this->issues[] = $output;
    }
}
