<?php

declare(strict_types=1);
namespace Tests\Unit\Visitor;

use DouglasGreen\PhpLinter\IssueHolder;
use DouglasGreen\PhpLinter\Visitor\SuperglobalUsageVisitor;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Identifier;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(SuperglobalUsageVisitor::class)]
#[Small]
final class SuperglobalUsageVisitorTest extends TestCase
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
    #[DataProvider('superglobalProvider')]
    public function testItAllowsSuperglobalInGlobalScope(string $superglobal): void
    {
        // Arrange
        $visitor = new SuperglobalUsageVisitor();
        $varNode = new Variable($superglobal);
        // Act
        $visitor->enterNode($varNode);
        // Assert
        $this->assertFalse(IssueHolder::getInstance()->hasIssues());
    }

    #[Test]
    #[DataProvider('superglobalProvider')]
    public function testItFlagsSuperglobalInFunctionScope(string $superglobal): void
    {
        // Arrange
        $visitor = new SuperglobalUsageVisitor();
        $function = new Function_(new Identifier('testFunction'), [], null);
        $varNode = new Variable($superglobal);

        // Act
        $visitor->enterNode($function);
        $visitor->enterNode($varNode);
        // Assert
        $this->assertTrue(IssueHolder::getInstance()->hasIssues());
    }

    #[Test]
    public function testItAllowsSuperglobalInControllerClass(): void
    {
        // Arrange
        $visitor = new SuperglobalUsageVisitor();
        $class = new Class_(new Identifier('UserController'));
        $varNode = new Variable('_POST');

        // Act
        $visitor->enterNode($class);
        $visitor->enterNode($varNode);
        // Assert
        $this->assertFalse(IssueHolder::getInstance()->hasIssues());
    }

    #[Test]
    public function testItAllowsSuperglobalInMiddlewareClass(): void
    {
        // Arrange
        $visitor = new SuperglobalUsageVisitor();
        $class = new Class_(new Identifier('AuthMiddleware'));
        $varNode = new Variable('_GET');

        // Act
        $visitor->enterNode($class);
        $visitor->enterNode($varNode);

        // Assert
        $this->assertFalse(IssueHolder::getInstance()->hasIssues());
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
}
