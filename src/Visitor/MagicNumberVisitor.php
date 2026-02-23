<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Visitor;

use PhpParser\Node;
use PhpParser\Node\Scalar\Float_;
use PhpParser\Node\Scalar\Int_;

class MagicNumberVisitor extends VisitorChecker
{
    /** @var array<string|int, int> */
    protected array $counts = [];

    /** @var array<string|int, array<int>> */
    protected array $lines = [];

    public function checkNode(Node $node): void
    {
        if ($node instanceof Int_ || $node instanceof Float_) {
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
