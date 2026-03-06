<?php

declare(strict_types=1);

namespace Tests\Unit\Linter\Checker;

use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Scalar\Int_;
use DouglasGreen\PhpLinter\IssueHolder;
use DouglasGreen\PhpLinter\Linter\Checker\ExpressionChecker;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Eval_;
use PhpParser\Node\Expr\Include_;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Global_;
use PhpParser\Node\Stmt\Goto_;
use PhpParser\Node\Stmt\If_;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ExpressionChecker::class)]
#[Small]
final class ExpressionCheckerTest extends TestCase
{
    private IssueHolder $issueHolder;

    protected function setUp(): void
    {
        $this->issueHolder = new IssueHolder();
    }

    #[Test]
    public function testItDetectsEvalUsage(): void
    {
        // Arrange
        $node = new Eval_(new Variable('code'));
        // Act
        $checker = new ExpressionChecker($node, $this->issueHolder);
        $checker->check();
        // Assert
        $this->assertTrue($this->issueHolder->hasIssues());
    }

    #[Test]
    public function testItDetectsGlobalKeyword(): void
    {
        // Arrange
        $node = new Global_([new Variable('testVar')]);

        // Act
        $checker = new ExpressionChecker($node, $this->issueHolder);
        $checker->check();

        // Assert
        $this->assertTrue($this->issueHolder->hasIssues());
    }

    #[Test]
    public function testItDetectsGotoStatement(): void
    {
        // Arrange
        $node = new Goto_(new Identifier('label'));
        // Act
        $checker = new ExpressionChecker($node, $this->issueHolder);
        $checker->check();
        // Assert
        $this->assertTrue($this->issueHolder->hasIssues());
    }

    #[Test]
    public function testItRecommendsRequireOnceOverInclude(): void
    {
        // Arrange
        $node = new Include_(new String_('file.php'), Include_::TYPE_INCLUDE);

        // Act
        $checker = new ExpressionChecker($node, $this->issueHolder);
        $checker->check();

        // Assert
        $this->assertTrue($this->issueHolder->hasIssues());
    }

    #[Test]
    public function testItAllowsRequireOnce(): void
    {
        // Arrange
        $node = new Include_(new String_('file.php'), Include_::TYPE_REQUIRE_ONCE);

        // Act
        $checker = new ExpressionChecker($node, $this->issueHolder);
        $checker->check();

        // Assert
        $this->assertFalse($this->issueHolder->hasIssues());
    }

    #[Test]
    public function testItDetectsAssignmentInIfCondition(): void
    {
        // Arrange
        $assign = new Assign(new Variable('x'), new Int_(1));
        $node = new If_($assign);

        // Act
        $checker = new ExpressionChecker($node, $this->issueHolder);
        $checker->check();

        // Assert
        $this->assertTrue($this->issueHolder->hasIssues());
    }
}
