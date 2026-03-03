<?php

declare(strict_types=1);

namespace Tests\Unit\Checker;

use DouglasGreen\PhpLinter\Checker\ExpressionChecker;
use DouglasParser\Node\Expr\Assign;
use DouglasGreen\PhpLinter\IssueHolder;
use PhpParser\Node\Expr\Eval_;
use PhpParser\Node\Expr\Include_;
use PhpParser\Node\Stmt\Global_;
use PhpParser\Node\Stmt\Goto_;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Expr\Variable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ExpressionChecker::class)]
#[Small]
final class ExpressionCheckerTest extends TestCase
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
    public function testItDetectsEvalUsage(): void
    {
        // Arrange
        $node = new Eval_(new Variable('code'));
        // Act
        $checker = new ExpressionChecker($node);
        $checker->check();
        // Assert
        $this->assertTrue(IssueHolder::getInstance()->hasIssues());
    }

    #[Test]
    public function testItDetectsGlobalKeyword(): void
    {
        // Arrange
        $node = new Global_();

        // Act
        $checker = new ExpressionChecker($node);
        $checker->check();

        // Assert
        $this->assertTrue(IssueHolder::getInstance()->hasIssues());
    }

    #[Test]
    public function testItDetectsGotoStatement(): void
    {
        // Arrange
        $node = new Goto_(new \PhpParser\Node\Identifier('label'));
        // Act
        $checker = new ExpressionChecker($node);
        $checker->check();
        // Assert
        $this->assertTrue(IssueHolder::getInstance()->hasIssues());
    }

    #[Test]
    public function testItRecommendsRequireOnceOverInclude(): void
    {
        // Arrange
        $node = new Include_(new \PhpParser\Node\Scalar\String_('file.php'), Include_::TYPE_INCLUDE);

        // Act
        $checker = new ExpressionChecker($node);
        $checker->check();

        // Assert
        $this->assertTrue(IssueHolder::getInstance()->hasIssues());
    }

    #[Test]
    public function testItAllowsRequireOnce(): void
    {
        // Arrange
        $node = new Include_(new \PhpParser\Node\Scalar\String_('file.php'), Include_::TYPE_REQUIRE_ONCE);

        // Act
        $checker = new ExpressionChecker($node);
        $checker->check();

        // Assert
        $this->assertFalse(IssueHolder::getInstance()->hasIssues());
    }

    #[Test]
    public function testItDetectsAssignmentInIfCondition(): void
    {
        // Arrange
        $assign = new Assign(new Variable('x'), new \PhpParser\Node\Scalar\Int_(1));
        $node = new If_($assign);

        // Act
        $checker = new ExpressionChecker($node);
        $checker->check();

        // Assert
        $this->assertTrue(IssueHolder::getInstance()->hasIssues());
    }
}
