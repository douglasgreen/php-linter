<?php

declare(strict_types=1);

namespace Tests\Unit\Linter\Checker;

use PhpParser\Node\Stmt\Echo_;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Name;
use DouglasGreen\PhpLinter\IssueHolder;
use DouglasGreen\PhpLinter\Linter\Checker\TryCatchChecker;
use PhpParser\Node\Stmt\Catch_;
use PhpParser\Node\Stmt\TryCatch;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(TryCatchChecker::class)]
#[Small]
final class TryCatchCheckerTest extends TestCase
{
    private IssueHolder $issueHolder;

    protected function setUp(): void
    {
        $this->issueHolder = new IssueHolder();
    }

    #[Test]
    public function testItDetectsEmptyCatchBlock(): void
    {
        // Arrange
        $catch = new Catch_([], null, []);
        $node = new TryCatch([], [$catch]);

        // Act
        $checker = new TryCatchChecker($node, $this->issueHolder);
        $checker->check();

        // Assert
        $this->assertTrue($this->issueHolder->hasIssues());
    }

    #[Test]
    public function testItDetectsCatchBlockWithNop(): void
    {
        // Arrange
        $catch = new Catch_([], );
        $node = new TryCatch([], [$catch]);
        // Act
        $checker = new TryCatchChecker($node, $this->issueHolder);
        $checker->check();

        // Assert
        $this->assertTrue($this->issueHolder->hasIssues());
    }

    #[Test]
    public function testItAllowsNonEmptyCatchBlock(): void
    {
        // Arrange
        $catch = new Catch_([], null, [new Echo_([])]);
        $node = new TryCatch([], [$catch]);

        // Act
        $checker = new TryCatchChecker($node, $this->issueHolder);
        $checker->check();
        // Assert
        $this->assertFalse($this->issueHolder->hasIssues());
    }

    #[Test]
    public function testItIgnoresNonTryCatchNodes(): void
    {
        // Arrange
        $node = new If_(new ConstFetch(new Name('true')));

        // Act
        $checker = new TryCatchChecker($node, $this->issueHolder);
        $issues = $checker->check();

        // Assert
        $this->assertSame([], $issues);
        $this->assertFalse($this->issueHolder->hasIssues());
    }
}
