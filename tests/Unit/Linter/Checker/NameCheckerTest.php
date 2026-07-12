<?php

declare(strict_types=1);

namespace Tests\Unit\Linter\Checker;

use DouglasGreen\PhpLinter\IssueHolder;
use DouglasGreen\PhpLinter\Linter\Checker\NameChecker;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Trait_;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(NameChecker::class)]
#[Small]
final class NameCheckerTest extends TestCase
{
    private IssueHolder $issueHolder;

    protected function setUp(): void
    {
        $this->issueHolder = new IssueHolder();
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function validClassNameProvider(): iterable
    {
        yield 'PascalCase single word' => ['User'];
        yield 'PascalCase multiple words' => ['UserController'];
        yield 'PascalCase with numbers' => ['Api2Client'];
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function invalidClassNameProvider(): iterable
    {
        yield 'snake_case' => ['user_controller'];
        yield 'camelCase' => ['userController'];
        yield 'ALL_CAPS' => ['USER_CONTROLLER'];
    }

    #[Test]
    #[DataProvider('validClassNameProvider')]
    public function testItAcceptsValidClassNames(string $className): void
    {
        // Arrange
        $node = new Class_(new Identifier($className));
        // Act
        $checker = new NameChecker($node, $this->issueHolder);
        $checker->check();
        // Assert
        $this->assertFalse($this->issueHolder->hasIssues());
    }

    #[Test]
    #[DataProvider('invalidClassNameProvider')]
    public function testItRejectsInvalidClassNames(string $className): void
    {
        // Arrange
        $node = new Class_(new Identifier($className));

        // Act
        $checker = new NameChecker($node, $this->issueHolder);
        $checker->check();

        // Assert
        $this->assertTrue($this->issueHolder->hasIssues());
    }

    #[Test]
    public function testItRequiresInterfaceSuffix(): void
    {
        // Arrange
        $node = new Interface_(new Identifier('UserRepository'));
        // Act
        $checker = new NameChecker($node, $this->issueHolder);
        $checker->check();
        // Assert
        $this->assertTrue($this->issueHolder->hasIssues());
    }

    #[Test]
    public function testItRequiresTraitSuffix(): void
    {
        // Arrange
        $node = new Trait_(new Identifier('Loggable'));
        // Act
        $checker = new NameChecker($node, $this->issueHolder);
        $checker->check();
        // Assert
        $this->assertTrue($this->issueHolder->hasIssues());
    }

    #[Test]
    public function testItAcceptsValidVariableNames(): void
    {
        // Arrange
        $node = new Variable('validVariableName');

        // Act
        $checker = new NameChecker($node, $this->issueHolder);
        $checker->check();

        // Assert
        $this->assertFalse($this->issueHolder->hasIssues());
    }
}
