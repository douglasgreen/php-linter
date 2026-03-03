<?php

declare(strict_types=1);

namespace Tests\Unit\Checker;

use DouglasGreen\PhpLinter\Checker\OperatorChecker;
use DouglasGreen\PhpLinter\IssueHolder;
use PhpParser\Node\Expr\ErrorSuppress;
use PhpParser\Node\Expr\Variable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(OperatorChecker::class)]
#[Small]
final class OperatorCheckerTest extends TestCase
{
    protected function setUp(): void
    {
        IssueHolder::getInstance()->clearIssues();
    }

    protected function tearDown(): void
    {
        IssueHolder::getInstance()->clearIssues();
    }

    #[Test]
    public function testItDetectsErrorSuppressionOperator(): void
    {
        // Arrange
        $node = new ErrorSuppress(new Variable('var'));
        // Act
        $checker = new OperatorChecker($node);
        $checker->check();
        // Assert
        $this->assertTrue(IssueHolder::getInstance()->hasIssues());
    }

    #[Test]
    public function testItIgnoresNonErrorSuppressNodes(): void
    {
        // Arrange
        $node = new Variable('var');

        // Act
        $checker = new OperatorChecker($node);
        $checker->check();

        // Assert
        $this->assertFalse(IssueHolder::getInstance()->hasIssues());
    }
}
