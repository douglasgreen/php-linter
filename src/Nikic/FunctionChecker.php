<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Nikic;

use DouglasGreen\Utility\Regex\Regex;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;

class FunctionChecker extends BaseChecker
{
    /**
     * @return array<string, bool>
     */
    public function check(): array
    {
        if ($this->node instanceof Function_) {
            $funcType = 'Function';
        } elseif ($this->node instanceof ClassMethod) {
            $funcType = 'Method';
        } else {
            return [];
        }

        $funcName = $this->node->name->toString();
        $params = $this->node->params;
        $this->checkBool($params, $funcName, $funcType);
        $this->checkCount($params, $funcName, $funcType);

        if ($this->node->returnType instanceof Identifier) {
            $returnType = $this->node->returnType->name;
            $this->checkReturnType($funcName, $funcType, $returnType);
        }

        return $this->getIssues();
    }

    /**
     * @param list<Param> $params
     */
    protected function checkBool(array $params, string $funcName, string $funcType): void
    {
        foreach ($params as $param) {
            if (! $param->var instanceof Variable) {
                continue;
            }

            $paramName = $param->var->name;
            if (! is_string($paramName)) {
                continue;
            }

            $paramType = $param->type;
            if ($paramType instanceof Identifier && $paramType->name === 'bool') {
                $this->addIssue(
                    sprintf(
                        '%s %s() has a boolean parameter $%s; replace with integer flag values',
                        $funcType,
                        $funcName,
                        $paramName,
                    ),
                );
            }
        }
    }

    /**
     * @param list<Param> $params
     */
    protected function checkCount(array $params, string $funcName, string $funcType): void
    {
        $paramCount = count($params);
        if ($paramCount > 9) {
            $this->addIssue(
                sprintf('%s %s() has too many parameters: %d', $funcType, $funcName, $paramCount),
            );
        }
    }

    protected function checkReturnType(string $funcName, string $funcType, string $returnType): void
    {
        if ($returnType !== 'bool') {
            return;
        }

        if (Regex::hasMatch('/^(has|is|should)[A-Z]/', $funcName)) {
            return;
        }

        $this->addIssue(
            sprintf(
                '%s %s() returns a boolean; consider naming it hasX(), isX() or shouldX()',
                $funcType,
                $funcName,
            ),
        );
    }
}
