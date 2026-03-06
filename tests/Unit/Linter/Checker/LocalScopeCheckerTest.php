<?php

declare(strict_types=1);

namespace Tests\Unit\Linter\Checker;

use DouglasGreen\PhpLinter\IssueHolder;
use DouglasGreen\PhpLinter\Linter\Checker\LocalScopeChecker;
use PhpParser\Node\Expr\Exit_;
use PhpParser\Node\Expr\Variable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(LocalScopeChecker::class)]
#[Small]
final class LocalScopeCheckerTest extends TestCase
{
    private IssueHolder $issueHolder;

    protected function setUp(): void
    {
        $this->issueHolder = new IssueHolder();
    }

    #[Test]
    public function testItDetectsExitExpression(): void
    {
        // Arrange
        $node = new Exit_(null, ['kind' => Exit_::KIND_EXIT]);
        // Act
        $checker = new LocalScopeChecker($node, $this->issueHolder);
        $checker->check();

        // Assert
        $this->assertTrue($this->issueHolder->hasIssues());
    }

    #[Test]
    public function testItDetectsDieExpression(): void
    {
        // Arrange
        $node = new Exit_(null, ['kind' => Exit_::KIND_DIE]);
        // Act
        $checker = new LocalScopeChecker($node, $this->issueHolder);
        $checker->check();

        // Assert
        $this->assertTrue($this->issueHolder->hasIssues());
    }

    #[Test]
    public function testItIgnoresNonExitNodes(): void
    {
        // Arrange
        $node = new Variable('var');

        // Act
        $checker = new LocalScopeChecker($node, $this->issueHolder);
        $checker->check();

        // Assert
        $this->assertFalse($this->issueHolder->hasIssues());
    }
}
