<?php
declare(strict_types=1);
namespace Tests\Unit\Checker;

use DouglasGreen\PhpLinter\Checker\TryCatchChecker;
use DouglasGreen\PhpLinter\IssueHolder;
use PhpParser\Node\Stmt\Catch_;
use PhpParser\Node\Stmt\Nop;
use PhpParser\Node\Stmt\TryCatch;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(TryCatchChecker::class)]
#[Small]
final class TryCatchCheckerTest extends TestCase
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
    public function testItDetectsEmptyCatchBlock(): void
    {
        // Arrange
        $catch = new Catch_([], null, []);
        $node = new TryCatch([], [$catch]);

        // Act
        $checker = new TryCatchChecker($node);
        $checker->check();

        // Assert
        $this->assertTrue(IssueHolder::getInstance()->hasIssues());
    }

    #[Test]
    public function testItDetectsCatchBlockWithNop(): void
    {
        // Arrange
        $catch = new Catch_([], null, );
        $node = new TryCatch([], [$catch]);
        // Act
        $checker = new TryCatchChecker($node);
        $checker->check();

        // Assert
        $this->assertTrue(IssueHolder::getInstance()->hasIssues());
    }

    #[Test]
    public function testItAllowsNonEmptyCatchBlock(): void
    {
        // Arrange
        $catch = new Catch_([], null, [new \PhpParser\Node\Stmt\Echo_([])]);
        $node = new TryCatch([], [$catch]);

        // Act
        $checker = new TryCatchChecker($node);
        $checker->check();
        // Assert
        $this->assertFalse(IssueHolder::getInstance()->hasIssues());
    }

    #[Test]
    public function testItIgnoresNonTryCatchNodes(): void
    {
        // Arrange
        $node = new \PhpParser\Node\Stmt\If_(null);

        // Act
        $checker = new TryCatchChecker($node);
        $issues = $checker->check();

        // Assert
        $this->assertSame([], $issues);
        $this->assertFalse(IssueHolder::getInstance()->hasIssues());
    }
}
