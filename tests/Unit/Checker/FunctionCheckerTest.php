<?php

declare(strict_types=1);
namespace Tests\Unit\Checker;

use DouglasGreen\PhpLinter\Checker\FunctionChecker;
use DouglasGreen\PhpLinter\IssueHolder;
use PhpParser\Node\Identifier;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Expr\Variable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(FunctionChecker::class)]
#[Small]
final class FunctionCheckerTest extends TestCase
{
    private IssueHolder $issueHolder;

    protected function setUp(): void
    {
        $this->issueHolder = new IssueHolder();
    }

    #[Test]
    public function testItDetectsTooManyParameters(): void
    {
        // Arrange
        $params = [];
        for ($i = 0; $i < 12; $i++) {
            $params[] = new Param(new Variable('param' . $i));
        }
        $node = new Function_(new Identifier('testFunction'), ['params' => $params]);
        // Act
        $checker = new FunctionChecker($node, $this->issueHolder);
        $checker->check();
        // Assert
        $this->assertTrue($this->issueHolder->hasIssues());
    }

    #[Test]
    public function testItAllowsTenParameters(): void
    {
        // Arrange
        $params = [];
        for ($i = 0; $i < 10; $i++) {
            $params[] = new Param(new Variable('param' . $i));
        }
        $node = new Function_(new Identifier('testFunction'), ['params' => $params]);

        // Act
        $checker = new FunctionChecker($node, $this->issueHolder);
        $checker->check();

        // Assert
        $this->assertTrue($this->issueHolder->hasIssues());
    }

    #[Test]
    public function testItSuggestsBooleanPrefixForBoolReturn(): void
    {
        // Arrange
        $node = new Function_(
            new Identifier('checkValidity'),
            ['returnType' => new Identifier('bool')]
        );
        // Act
        $checker = new FunctionChecker($node, $this->issueHolder);
        $checker->check();
        // Assert
        $this->assertTrue($this->issueHolder->hasIssues());
    }

    #[Test]
    public function testItAcceptsValidBooleanPrefix(): void
    {
        // Arrange
        $node = new Function_(
            new Identifier('isValid'),
            ['returnType' => new Identifier('bool')]
        );

        // Act
        $checker = new FunctionChecker($node, $this->issueHolder);
        $checker->check();

        // Assert
        $this->assertFalse($this->issueHolder->hasIssues());
    }

    #[Test]
    public function testItReturnsParameterMetadata(): void
    {
        // Arrange
        $param = new Param(
            new Variable('testParam'),
            type: new Identifier('string')
        );
        $node = new Function_(
            new Identifier('testFunction'),
            ['params' => [$param]]
        );

        // Act
        $checker = new FunctionChecker($node, $this->issueHolder);
        $checker->check();
        $params = $checker->getParams();

        // Assert
        $this->assertArrayHasKey('testParam', $params);
        $this->assertSame('string', $params['testParam']['type']);
        $this->assertFalse($params['testParam']['promoted']);
    }
}
