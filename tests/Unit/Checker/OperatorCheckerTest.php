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
    private IssueHolder $issueHolder;

    protected function setUp(): void
    {
        $this->issueHolder = new IssueHolder();
    }

    #[Test]
    public function testItDetectsErrorSuppressionOperator(): void
    {
        // Arrange
        $node = new ErrorSuppress(new Variable('var'));
        // Act
        $checker = new OperatorChecker($node, $this->issueHolder);
        $checker->check();
        // Assert
        $this->assertTrue($this->issueHolder->hasIssues());
    }

    #[Test]
    public function testItIgnoresNonErrorSuppressNodes(): void
    {
        // Arrange
        $node = new Variable('var');

        // Act
        $checker = new OperatorChecker($node, $this->issueHolder);
        $checker->check();

        // Assert
        $this->assertFalse($this->issueHolder->hasIssues());
    }
}
