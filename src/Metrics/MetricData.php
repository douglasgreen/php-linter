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
class MetricData
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
        public readonly ?string $name = null,
        public readonly ?string $filename = null,
        public readonly array $methods = [],
        public readonly ?int $ca = null,
        public readonly ?int $ce = null,
        public readonly ?int $cbo = null,
        public readonly ?int $ccn2 = null,
        public readonly ?float $cr = null,
        public readonly ?int $csz = null,
        public readonly ?int $cloc = null,
        public readonly ?int $dit = null,
        public readonly ?int $eloc = null,
        public readonly ?int $he = null,
        public readonly ?int $loc = null,
        public readonly ?float $mi = null,
        public readonly ?int $nocc = null,
        public readonly ?int $npm = null,
        public readonly ?int $npath = null,
        public readonly ?int $vars = null,
        public readonly ?int $varsnp = null,
    ) {}
}
