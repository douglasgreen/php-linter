<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Visitor;

use PhpParser\Node;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;

/**
 * Analyzes function and method scope for variable usage and parameter handling.
 *
 * @package DouglasGreen\PhpLinter\Visitor
 *
 * @since 1.0.0
 *
 * @internal
 */
class FunctionVisitor extends VisitorChecker
{
    /**
     * Counts of variable references within the function.
     *
     * @var array<string, int>
     */
    protected array $variableCounts = [];

    /**
     * Initializes a new instance of the FunctionVisitor.
     *
     * @param string $functionName The name of the function or method being analyzed.
     * @param array<string, bool> $attribs Attributes of the function (e.g., 'abstract').
     * @param array<string, array{type: string|null, promoted: bool}> $params Parameter definitions.
     */
    public function __construct(
        protected string $functionName,
        protected array $attribs,
        protected array $params,
    ) {}

    /**
     * Performs final checks after the function has been fully traversed.
     *
     * Checks for unused parameters and variables referenced only once.
     * Call this function in leaveNode().
     */
    public function checkFunction(): void
    {
        // Check that each parameter is used.
        foreach ($this->params as $paramName => $paramInfo) {
            // Abstract functions don't have implementations to check.
            if (! empty($this->attribs['abstract'])) {
                continue;
            }

            // Promoted variables don't have to be used.
            if ($paramInfo['promoted']) {
                continue;
            }

            // The parameter names are also counted as variables.
            // If the variable count is set, the parameter is used at least once.
            if (isset($this->variableCounts[$paramName])) {
                continue;
            }

            $issue = sprintf(
                'Remove unused parameter "%s" from function "%s()"; it is defined but not used in the function body.',
                $paramName,
                $this->functionName,
            );
            $this->issues[$issue] = true;
        }

        // Check that each variable is used more than once.
        foreach ($this->variableCounts as $variable => $count) {
            if ($count === 1 && ! isset($this->params[$variable])) {
                $issue = sprintf(
                    'Remove or inline variable "%s" in function "%s()"; it is referenced only once.',
                    $variable,
                    $this->functionName,
                );
                $this->issues[$issue] = true;
            }
        }
    }

    /**
     * Inspects a node for variable usage.
     *
     * @param Node $node The node to check.
     */
    public function checkNode(Node $node): void
    {
        // Check if the variable is not part of a property fetch
        if ($node instanceof Variable && ! static::isPropertyFetch($node)) {
            $variableName = static::getVariableName($node);
            if ($variableName !== null) {
                $this->incrementVariableCount($variableName);
            }
        }
    }

    /**
     * Returns the variable reference counts.
     *
     * @return array<string, int>
     */
    public function getVariableCounts(): array
    {
        return $this->variableCounts;
    }

    /**
     * Extracts the variable name from a Variable node.
     *
     * @param Variable $variable The variable node.
     *
     * @return string|null The variable name or null if it is $this.
     */
    protected static function getVariableName(Variable $variable): ?string
    {
        // Exclude $this as it's a special case
        if (is_string($variable->name) && $variable->name !== 'this') {
            return $variable->name;
            // Return without $ prefix
        }

        return null;
    }

    /**
     * Determines if a node is the variable part of a property fetch.
     *
     * @param Node $node The node to check.
     *
     * @return bool True if the node is the variable in a property fetch.
     */
    protected static function isPropertyFetch(Node $node): bool
    {
        $parent = $node->getAttribute('parent');
        return $parent instanceof PropertyFetch && $parent->var === $node;
    }

    /**
     * Increments the reference count for a variable.
     *
     * @param string $variableName The name of the variable.
     */
    protected function incrementVariableCount(string $variableName): void
    {
        if (isset($this->variableCounts[$variableName])) {
            $this->variableCounts[$variableName]++;
        } else {
            $this->variableCounts[$variableName] = 1;
        }
    }
}
