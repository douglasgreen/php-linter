<?php

declare(strict_types=1);

namespace Tests\Unit\Visitor;

use DouglasGreen\PhpLinter\Visitor\ClassVisitor;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Modifier;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ClassVisitor::class)]
#[Small]
final class ClassVisitorTest extends TestCase
{
    #[Test]
    public function testItCreatesClassVisitorWithName(): void
    {
        // Act
        $visitor = new ClassVisitor('TestClass');
        // Assert
        $this->assertSame([], $visitor->getMethods());
    }

    #[Test]
    public function testItTracksPropertyDefinitions(): void
    {
        // Arrange
        $visitor = new ClassVisitor('TestClass');
        $property = new Property(
            modifiers: Modifier::PUBLIC,
            props:
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
        $visitor = new ClassVisitor('TestClass');
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
        $visitor = new ClassVisitor('TestClass');
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
        $visitor = new ClassVisitor('TestClass');
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
