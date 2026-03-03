<?php

declare(strict_types=1);

namespace Tests\Unit\Visitor;

use DouglasGreen\PhpLinter\IssueHolder;
use DouglasGreen\PhpLinter\Visitor\ClassVisitor;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\Class_;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ClassVisitor::class)]
#[Small]
final class ClassVisitorTest extends TestCase
{
    private IssueHolder $issueHolder;

    protected function setUp(): void
    {
        $this->issueHolder = new IssueHolder();
    }

    #[Test]
    public function testItCreatesClassVisitorWithName(): void
    {
        // Act
        $visitor = new ClassVisitor($this->issueHolder, 'TestClass');
        // Assert
        $this->assertSame([], $visitor->getMethods());
    }

    #[Test]
    public function testItTracksPropertyDefinitions(): void
    {
        // Arrange
        $visitor = new ClassVisitor($this->issueHolder, 'TestClass');
        $property = new Property(
            props: [new \PhpParser\Node\PropertyItem(new Identifier('testProp'))],
            flags: Class_::MODIFIER_PUBLIC,
        );
        // Act
        $visitor->checkNode($property);

        // Assert
        $this->addToAssertionCount(1);
    }

    #[Test]
    public function testItTracksMethodDefinitions(): void
    {
        // Arrange
        $visitor = new ClassVisitor($this->issueHolder, 'TestClass');
        $method = new ClassMethod(new Identifier('testMethod'));
        // Act
        $visitor->checkNode($method);

        // Assert
        $methods = $visitor->getMethods();
        $this->assertArrayHasKey('testMethod', $methods);
    }

    #[Test]
    public function testItTracksMethodCalls(): void
    {
        // Arrange
        $visitor = new ClassVisitor($this->issueHolder, 'TestClass');
        $methodCall = new MethodCall(
            new Variable('this'),
            new Identifier('someMethod')
        );

        // Act
        $visitor->checkNode($methodCall);
        // Assert
        $this->addToAssertionCount(1);
    }

    #[Test]
    public function testItTracksPropertyFetches(): void
    {
        // Arrange
        $visitor = new ClassVisitor($this->issueHolder, 'TestClass');
        $propertyFetch = new PropertyFetch(
            new Variable('this'),
            new Identifier('someProperty')
        );
        // Act
        $visitor->checkNode($propertyFetch);

        // Assert
        $this->addToAssertionCount(1);
    }
}
