<?php

declare(strict_types=1);

namespace Tests\Unit\Linter\Visitor;

use DouglasGreen\PhpLinter\IssueHolder;
use DouglasGreen\PhpLinter\Linter\Visitor\SuperglobalUsageVisitor;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Function_;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(SuperglobalUsageVisitor::class)]
#[Small]
final class SuperglobalUsageVisitorTest extends TestCase
{
    private IssueHolder $issueHolder;

    protected function setUp(): void
    {
        $this->issueHolder = new IssueHolder();
    }

    public static function superglobalProvider(): iterable
    {
        yield '_GET' => ['_GET'];
        yield '_POST' => ['_POST'];
        yield '_SESSION' => ['_SESSION'];
        yield '_COOKIE' => ['_COOKIE'];
        yield '_FILES' => ['_FILES'];
        yield '_SERVER' => ['_SERVER'];
        yield '_ENV' => ['_ENV'];
        yield '_REQUEST' => ['_REQUEST'];
    }

    #[Test]
    #[DataProvider('superglobalProvider')]
    public function testItAllowsSuperglobalInGlobalScope(string $superglobal): void
    {
        // Arrange
        $visitor = new SuperglobalUsageVisitor($this->issueHolder);
        $varNode = new Variable($superglobal);
        // Act
        $visitor->enterNode($varNode);
        // Assert
        $this->assertFalse($this->issueHolder->hasIssues());
    }

    #[Test]
    #[DataProvider('superglobalProvider')]
    public function testItFlagsSuperglobalInFunctionScope(string $superglobal): void
    {
        // Arrange
        $visitor = new SuperglobalUsageVisitor($this->issueHolder);
        $function = new Function_(new Identifier('testFunction'), [], []);
        $varNode = new Variable($superglobal);

        // Act
        $visitor->enterNode($function);
        $visitor->enterNode($varNode);
        // Assert
        $this->assertTrue($this->issueHolder->hasIssues());
    }

    #[Test]
    public function testItAllowsSuperglobalInControllerClass(): void
    {
        // Arrange
        $visitor = new SuperglobalUsageVisitor($this->issueHolder);
        $class = new Class_(new Identifier('UserController'));
        $varNode = new Variable('_POST');

        // Act
        $visitor->enterNode($class);
        $visitor->enterNode($varNode);
        // Assert
        $this->assertFalse($this->issueHolder->hasIssues());
    }

    #[Test]
    public function testItAllowsSuperglobalInMiddlewareClass(): void
    {
        // Arrange
        $visitor = new SuperglobalUsageVisitor($this->issueHolder);
        $class = new Class_(new Identifier('AuthMiddleware'));
        $varNode = new Variable('_GET');

        // Act
        $visitor->enterNode($class);
        $visitor->enterNode($varNode);

        // Assert
        $this->assertFalse($this->issueHolder->hasIssues());
    }
}
