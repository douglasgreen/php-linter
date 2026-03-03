<?php

declare(strict_types=1);
namespace Tests\Unit\Checker;

use DouglasGreen\PhpLinter\Checker\LocalScopeChecker;
use DouglasGreen\PhpLinter\IssueHolder;
use PhpParser\Node\Expr\Exit_;
use PhpParser\Node\Expr\Variable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(LocalScopeChecker::class)]
#[Small]
final class LocalScopeCheckerTest extends TestCase
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
    public function testItDetectsExitExpression(): void
    {
        // Arrange
        $node = new Exit_(null, ['kind' => Exit_::KIND_EXIT]);
        // Act
        $checker = new LocalScopeChecker($node);
        $checker->check();

        // Assert
        $this->assertTrue(IssueHolder::getInstance()->hasIssues());
    }

    #[Test]
    public function testItDetectsDieExpression(): void
    {
        // Arrange
        $node = new Exit_(null, ['kind' => Exit_::KIND_DIE]);
        // Act
        $checker = new LocalScopeChecker($node);
        $checker->check();

        // Assert
        $this->assertTrue(IssueHolder::getInstance()->hasIssues());
    }

    #[Test]
    public function testItIgnoresNonExitNodes(): void
    {
        // Arrange
        $node = new Variable('var');

        // Act
        $checker = new LocalScopeChecker($node);
        $checker->check();

        // Assert
        $this->assertFalse(IssueHolder::getInstance()->hasIssues());
    }
}
