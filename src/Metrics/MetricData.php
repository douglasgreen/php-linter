<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Metrics;

/**
 * Data Transfer Object for code metrics.
 *
 * Holds metric data for files, classes, methods, and functions.
 *
 * @package DouglasGreen\PhpLinter\Metrics
 *
 * @since 1.0.0
 */
readonly class MetricData
{
    /**
     * @param string|null $name The name of the element (class, method, function).
     * @param string|null $filename The file containing this element.
     * @param list<MetricData> $methods Method metrics for classes.
     * @param int|null $ca Afferent coupling.
     * @param int|null $ce Efferent coupling.
     * @param int|null $cbo Coupling between objects.
     * @param int|null $ccn2 Extended cyclomatic complexity.
     * @param float|null $cr Code rank.
     * @param int|null $csz Class size (methods + properties).
     * @param int|null $cloc Comment lines of code.
     * @param int|null $dit Depth of inheritance tree.
     * @param int|null $eloc Executable lines of code.
     * @param int|null $he Halstead effort.
     * @param int|null $loc Lines of code.
     * @param float|null $mi Maintainability index.
     * @param int|null $nocc Number of child classes.
     * @param int|null $npm Number of public methods.
     * @param int|null $npath NPath complexity.
     * @param int|null $vars Number of properties.
     * @param int|null $varsnp Number of non-private properties.
     */
    public function __construct(
        public ?string $name = null,
        public ?string $filename = null,
        public array $methods = [],
        public ?int $ca = null,
        public ?int $ce = null,
        public ?int $cbo = null,
        public ?int $ccn2 = null,
        public ?float $cr = null,
        public ?int $csz = null,
        public ?int $cloc = null,
        public ?int $dit = null,
        public ?int $eloc = null,
        public ?int $he = null,
        public ?int $loc = null,
        public ?float $mi = null,
        public ?int $nocc = null,
        public ?int $npm = null,
        public ?int $npath = null,
        public ?int $vars = null,
        public ?int $varsnp = null,
    ) {}
}
