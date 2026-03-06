<?php

declare(strict_types=1);

namespace Tests\Unit\Linter\Checker;

use DouglasGreen\PhpLinter\IssueHolder;
use DouglasGreen\PhpLinter\Linter\Checker\FunctionCallChecker;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(FunctionCallChecker::class)]
#[Small]
final class FunctionCallCheckerTest extends TestCase
{
    private IssueHolder $issueHolder;

    protected function setUp(): void
    {
        $this->issueHolder = new IssueHolder();
    }

    public static function debugFunctionProvider(): iterable
    {
        yield 'var_dump' => ['var_dump'];
        yield 'print_r' => ['print_r'];
        yield 'debug_print_backtrace' => ['debug_print_backtrace'];
        yield 'debug_zval_dump' => ['debug_zval_dump'];
        yield 'uppercase VAR_DUMP' => ['VAR_DUMP'];
        yield 'mixed case Var_Dump' => ['Var_Dump'];
    }

    #[Test]
    #[DataProvider('debugFunctionProvider')]
    public function testItDetectsDebugFunctionCalls(string $functionName): void
    {
        // Arrange
        $node = new FuncCall(new Name($functionName));
        // Act
        $checker = new FunctionCallChecker($node, $this->issueHolder);
        $checker->check();
        // Assert
        $this->assertTrue($this->issueHolder->hasIssues());
    }

    #[Test]
    public function testItAllowsNonDebugFunctionCalls(): void
    {
        // Arrange
        $node = new FuncCall(new Name('strlen'));
        // Act
        $checker = new FunctionCallChecker($node, $this->issueHolder);
        $checker->check();

        // Assert
        $this->assertFalse($this->issueHolder->hasIssues());
    }

    #[Test]
    public function testItHandlesDynamicFunctionNames(): void
    {
        // Arrange
        $node = new FuncCall(new Name('variableFunction'));
        // Act
        $checker = new FunctionCallChecker($node, $this->issueHolder);
        $checker->check();

        // Assert
        $this->assertFalse($this->issueHolder->hasIssues());
    }
}
