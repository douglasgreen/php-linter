<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Nikic\Visitor;

use PhpParser\Node;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;

class FunctionVisitor extends VisitorChecker
{
    /**
     * @var array<string, int>
     */
    protected array $variableCounts = [];

    /**
     * @param array<string, bool> $attribs
     * @param array<string, array{type: ?string, promoted: bool}> $params
     */
    public function __construct(
        protected string $functionName,
        protected array $attribs,
        protected array $params
    ) {}

    /**
     * Check the function when we have completely traversed it.
     *
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
            if (isset($this->variableCounts[$paramName]) && $this->variableCounts[$paramName] > 1) {
                continue;
            }

            $issue = sprintf(
                'Parameter %s is not used within function %s().',
                $paramName,
                $this->functionName
            );
            $this->issues[$issue] = true;
        }

        // Check that each variable is used more than once.
        foreach ($this->variableCounts as $variable => $count) {
            if ($count === 1 && ! isset($this->params[$variable])) {
                $issue = sprintf(
                    'Variable %s is used only once within function %s().',
                    $variable,
                    $this->functionName
                );
                $this->issues[$issue] = true;
            }
        }
    }

    public function checkNode(Node $node): void
    {
        // Check if the variable is not part of a property fetch
        if ($node instanceof Variable && ! $this->isPropertyFetch($node)) {
            $variableName = $this->getVariableName($node);
            if ($variableName !== null) {
                $this->incrementVariableCount($variableName);
            }
        }
    }

    /**
     * @return array<string, int>
     */
    public function getVariableCounts(): array
    {
        return $this->variableCounts;
    }

    protected function getVariableName(Variable $variable): ?string
    {
        // Exclude $this as it's a special case
        if (is_string($variable->name) && $variable->name !== 'this') {
            return $variable->name;
            // Return without $ prefix
        }

        return null;
    }

    protected function incrementVariableCount(string $variableName): void
    {
        if (isset($this->variableCounts[$variableName])) {
            $this->variableCounts[$variableName]++;
        } else {
            $this->variableCounts[$variableName] = 1;
        }
    }

    protected function isPropertyFetch(Node $node): bool
    {
        $parent = $node->getAttribute('parent');
        return $parent instanceof PropertyFetch && $parent->var === $node;
    }
}
