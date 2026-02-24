<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Visitor;

use PhpParser\Node;
use PhpParser\Node\Const_;
use PhpParser\Node\Scalar\Float_;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Stmt\ClassConst;
use ReturnTypeWillChange;

/**
 * Detects and reports magic numbers in code.
 *
 * Magic numbers are numeric literals that appear directly in the code
 * without a named constant definition, reducing maintainability.
 *
 * @package DouglasGreen\PhpLinter\Visitor
 *
 * @since 1.0.0
 *
 * @internal
 */
class MagicNumberVisitor extends VisitorChecker
{
    /**
     * Counts of occurrences for each magic number found.
     *
     * @var array<string|int, int>
     */
    protected array $counts = [];

    /**
     * Line numbers where each magic number appears.
     *
     * @var array<string|int, array<int>>
     */
    protected array $lines = [];

    /** Depth counter for constant definitions to ignore them. */
    protected int $inConst = 0;

    /**
     * Enters a node to track constant definition context.
     *
     * @param Node $node The node being entered.
     */
    #[ReturnTypeWillChange]
    public function enterNode(Node $node): null
    {
        if ($node instanceof Const_ || $node instanceof ClassConst) {
            $this->inConst++;
        }

        return null;
    }

    /**
     * Leaves a node to update constant definition context.
     *
     * @param Node $node The node being left.
     */
    #[ReturnTypeWillChange]
    public function leaveNode(Node $node): null
    {
        if ($node instanceof Const_ || $node instanceof ClassConst) {
            $this->inConst--;
        }

        return null;
    }

    /**
     * Inspects a node for magic numbers.
     *
     * @param Node $node The node to check.
     */
    public function checkNode(Node $node): void
    {
        if ($node instanceof Int_ || $node instanceof Float_) {
            // Ignore numbers in constant definitions.
            if ($this->inConst > 0) {
                return;
            }

            // Fallback for parent attribute if it is actively set.
            $parent = $node->getAttribute('parent');
            if ($parent instanceof Const_ || $parent instanceof ClassConst) {
                return;
            }

            $value = $node->value;
            $valStr = (string) abs($value);

            // Ignore 0 and 1
            if (in_array($value, [0.0, 1.0], true)) {
                return;
            }

            // Ignore single digits.
            if (strlen((string) $value) === 1) {
                return;
            }

            // Ignore repeated digits (e.g., 11, 222, 55.55)
            $digitsOnly = str_replace('.', '', $valStr);
            if (preg_match('/^(\d)\1+$/', $digitsOnly)) {
                return;
            }

            $key = (string) $value;
            $this->counts[$key] = ($this->counts[$key] ?? 0) + 1;
            $this->lines[$key][] = $node->getStartLine();
        }
    }

    /**
     * Reports magic numbers that appear more than once.
     */
    public function checkDuplicates(): void
    {
        foreach ($this->counts as $value => $count) {
            if ($count > 1) {
                $lines = $this->lines[$value];
                $this->addIssue(
                    sprintf(
                        'Replace the magic number %s with a named constant. It appears %d times on lines %s. Centralizing this value improves maintainability and readability.',
                        $value,
                        $count,
                        implode(', ', $lines),
                    ),
                );
            }
        }
    }
}
