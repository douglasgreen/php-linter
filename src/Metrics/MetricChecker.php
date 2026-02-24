<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Metrics;

/**
 * Checks software metrics against defined limits.
 *
 * Wraps metric data and provides methods to validate against thresholds,
 * collecting issues for reporting.
 *
 * @package DouglasGreen\PhpLinter\Metrics
 * @since 1.0.0
 * @see https://pdepend.org/documentation/software-metrics/index.html
 * @api
 */
class MetricChecker
{
    /**
     * Status constant for a passed check.
     * @var int
     */
    public const STATUS_OK = 0;

    /**
     * Status constant for a failed check.
     * @var int
     */
    public const STATUS_ERROR = 1;

    /**
     * List of issues found during checks.
     * @var list<string>
     */
    protected array $issues = [];

    /**
     * The current file being processed, used for formatting output.
     * @var string|null
     */
    protected ?string $currentFile = null;

    /**
     * Count of errors encountered.
     * @var int
     */
    protected int $errorCount = 0;

    /**
     * Initializes the MetricChecker with metric data and context.
     *
     * @param array<string, mixed> $data Metric data array (e.g., from PDepend XML).
     * @param string|null $className The name of the class being checked, if applicable.
     * @param string|null $functionName The name of the function/method being checked, if applicable.
     */
    public function __construct(
        protected readonly array $data,
        protected readonly ?string $className = null,
        protected readonly ?string $functionName = null,
    ) {}

    /**
     * Checks if afferent coupling exceeds the limit.
     *
     * @param int $limit The maximum allowed afferent coupling.
     * @return int Status code (STATUS_OK or STATUS_ERROR).
     */
    public function checkMaxAfferentCoupling(int $limit): int
    {
        $afferentCoupling = (int) $this->data['ca'];
        $message = 'Afferent coupling = %d > %d';
        $hint = 'Reduce incoming dependencies; consider interface segregation or decoupling.';
        return $this->checkMax($message, $afferentCoupling, $limit, $hint);
    }

    /**
     * Checks if class size (methods + properties) exceeds the limit.
     *
     * @param int $limit The maximum allowed class size.
     * @return int Status code (STATUS_OK or STATUS_ERROR).
     */
    public function checkMaxClassSize(int $limit): int
    {
        $csz = (int) $this->data['csz'];
        $message = 'Class size (# methods + # properties) = %d > %d';
        $hint = 'Reduce class size; extract classes or delegate responsibilities.';
        return $this->checkMax($message, $csz, $limit, $hint);
    }

    /**
     * Checks if code rank exceeds the limit.
     *
     * @param float $limit The maximum allowed code rank.
     * @return int Status code (STATUS_OK or STATUS_ERROR).
     */
    public function checkMaxCodeRank(float $limit): int
    {
        $codeRank = (float) $this->data['cr'];
        $message = 'Code rank = %0.2f > %0.2f';
        $hint = 'High rank implies high responsibility/centrality; ensure stability and test coverage.';
        return $this->checkMax($message, $codeRank, $limit, $hint);
    }

    /**
     * Checks if cyclomatic complexity exceeds the limit.
     *
     * @param int $limit The maximum allowed cyclomatic complexity.
     * @return int Status code (STATUS_OK or STATUS_ERROR).
     */
    public function checkMaxCyclomaticComplexity(int $limit): int
    {
        $ecc = (int) $this->data['ccn2'];
        $message = 'Extended cyclomatic complexity = %d > %d';
        $hint = 'Reduce complexity; extract methods or simplify conditional logic.';
        return $this->checkMax($message, $ecc, $limit, $hint);
    }

    /**
     * Checks if efferent coupling exceeds the limit.
     *
     * @param int $limit The maximum allowed efferent coupling.
     * @return int Status code (STATUS_OK or STATUS_ERROR).
     */
    public function checkMaxEfferentCoupling(int $limit): int
    {
        $efferentCoupling = (int) $this->data['ce'];
        $message = 'Efferent coupling = %d > %d';
        $hint = 'Reduce outgoing dependencies; use dependency injection or interfaces.';
        return $this->checkMax($message, $efferentCoupling, $limit, $hint);
    }

    /**
     * Checks if Halstead effort exceeds the limit.
     *
     * @param int $limit The maximum allowed Halstead effort.
     * @return int Status code (STATUS_OK or STATUS_ERROR).
     */
    public function checkMaxHalsteadEffort(int $limit): int
    {
        $halsteadEffort = (int) $this->data['he'];
        $message = 'Halstead effort = %d > %d';
        $hint = 'Reduce code volume/complexity; simplify logic or break down methods.';
        return $this->checkMax($message, $halsteadEffort, $limit, $hint);
    }

    /**
     * Checks if inheritance depth exceeds the limit.
     *
     * @param int $limit The maximum allowed inheritance depth.
     * @return int Status code (STATUS_OK or STATUS_ERROR).
     */
    public function checkMaxInheritanceDepth(int $limit): int
    {
        $dit = (int) $this->data['dit'];
        $message = 'Inheritance depth = %d > %d';
        $hint = 'Reduce inheritance depth; prefer composition over inheritance.';
        return $this->checkMax($message, $dit, $limit, $hint);
    }

    /**
     * Checks if lines of code exceed the limit.
     *
     * @param int $limit The maximum allowed lines of code.
     * @return int Status code (STATUS_OK or STATUS_ERROR).
     */
    public function checkMaxLinesOfCode(int $limit): int
    {
        $loc = (int) $this->data['loc'];
        $message = '# lines of code = %d > %d';
        $hint = 'Reduce lines of code; extract logic into smaller units.';
        return $this->checkMax($message, $loc, $limit, $hint);
    }

    /**
     * Checks if non-private properties exceed the limit.
     *
     * @param int $limit The maximum allowed non-private properties.
     * @return int Status code (STATUS_OK or STATUS_ERROR).
     */
    public function checkMaxNonPrivateProperties(int $limit): int
    {
        $varsnp = (int) $this->data['varsnp'];
        $message = '# non-private properties = %d > %d';
        $hint = 'Encapsulate fields; make properties private and use accessors.';
        return $this->checkMax($message, $varsnp, $limit, $hint);
    }

    /**
     * Checks if NPath complexity exceeds the limit.
     *
     * @param int $limit The maximum allowed NPath complexity.
     * @return int Status code (STATUS_OK or STATUS_ERROR).
     */
    public function checkMaxNpathComplexity(int $limit): int
    {
        $npath = (int) $this->data['npath'];
        $message = 'NPath complexity = %d > %d';
        $hint = 'Reduce branching paths; simplify control structures or return early.';
        return $this->checkMax($message, $npath, $limit, $hint);
    }

    /**
     * Checks if the number of child classes exceeds the limit.
     *
     * @param int $limit The maximum allowed child classes.
     * @return int Status code (STATUS_OK or STATUS_ERROR).
     */
    public function checkMaxNumberOfChildClasses(int $limit): int
    {
        $nocc = (int) $this->data['nocc'];
        $message = '# child classes = %d > %d';
        $hint = 'Review hierarchy; base class may be too generic or complex.';
        return $this->checkMax($message, $nocc, $limit, $hint);
    }

    /**
     * Checks if object coupling exceeds the limit.
     *
     * @param int $limit The maximum allowed object coupling.
     * @return int Status code (STATUS_OK or STATUS_ERROR).
     */
    public function checkMaxObjectCoupling(int $limit): int
    {
        $objectCoupling = (int) $this->data['cbo'];
        $message = 'Coupling between objects = %d > %d';
        $hint = 'Reduce coupling; decouple from other objects or use events.';
        return $this->checkMax($message, $objectCoupling, $limit, $hint);
    }

    /**
     * Checks if the number of properties exceeds the limit.
     *
     * @param int $limit The maximum allowed properties.
     * @return int Status code (STATUS_OK or STATUS_ERROR).
     */
    public function checkMaxProperties(int $limit): int
    {
        $vars = (int) $this->data['vars'];
        $message = '# properties = %d > %d';
        $hint = 'Reduce state; extract value objects or services.';
        return $this->checkMax($message, $vars, $limit, $hint);
    }

    /**
     * Checks if the number of public methods exceeds the limit.
     *
     * @param int $limit The maximum allowed public methods.
     * @return int Status code (STATUS_OK or STATUS_ERROR).
     */
    public function checkMaxPublicMethods(int $limit): int
    {
        $npm = (int) $this->data['npm'];
        $message = '# public methods = %d > %d';
        $hint = 'Reduce public interface; hide internal methods.';
        return $this->checkMax($message, $npm, $limit, $hint);
    }

    /**
     * Checks if the comment ratio is below the limit.
     *
     * @param float $limit The minimum allowed comment ratio.
     * @return int Status code (STATUS_OK or STATUS_ERROR).
     */
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

    /**
     * Checks if the maintainability index is below the limit.
     *
     * @param float $limit The minimum allowed maintainability index.
     * @return int Status code (STATUS_OK or STATUS_ERROR).
     */
    public function checkMinMaintainabilityIndex(float $limit): int
    {
        $maintainabilityIndex = (int) $this->data['mi'];
        $message = 'Maintainability index = %0.2f < %0.2f';
        $hint = 'Improve maintainability; refactor complex code.';
        return $this->checkMin($message, $maintainabilityIndex, $limit, $hint);
    }

    /**
     * Returns the list of issues found.
     *
     * @return list<string> List of issue messages.
     */
    public function getIssues(): array
    {
        return $this->issues;
    }

    /**
     * Checks if any issues were found.
     *
     * @return bool True if issues exist, false otherwise.
     */
    public function hasIssues(): bool
    {
        return $this->issues !== [];
    }

    /**
     * Prints the collected issues for a specific file.
     *
     * @param string $filename The file name to print issues for.
     * @return void
     */
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

    /**
     * Checks if a value exceeds a maximum limit.
     *
     * @param string $message The message template for the error.
     * @param float|int $value The actual value.
     * @param float|int $limit The limit to check against.
     * @param string $hint Optional hint for resolving the issue.
     * @return int Status code (STATUS_OK or STATUS_ERROR).
     */
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

    /**
     * Checks if a value falls below a minimum limit.
     *
     * @param string $message The message template for the error.
     * @param float|int $value The actual value.
     * @param float|int $limit The limit to check against.
     * @param string $hint Optional hint for resolving the issue.
     * @return int Status code (STATUS_OK or STATUS_ERROR).
     */
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

    /**
     * Formats and records an issue.
     *
     * @param string $issue The issue message.
     * @param string $hint A hint for resolving the issue.
     * @return void
     */
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
