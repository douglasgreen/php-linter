<?php

declare(strict_types=1);

namespace Tests\Unit\Visitor;

use DouglasGreen\PhpLinter\IssueHolder;
use DouglasGreen\PhpLinter\Visitor\NameVisitor;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Identifier;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(NameVisitor::class)]
#[Small]
final class NameVisitorTest extends TestCase
{
    private NameVisitor $visitor;

    protected function setUp(): void
    {
        $this->visitor = new NameVisitor(new IssueHolder());
    }
    #[Test]
    public function testItCollectsFullyQualifiedNames(): void
    {
        // Arrange
        $name = new Name\FullyQualified(['App', 'Service', 'TestService']);
        // Act
        $this->visitor->checkNode($name);
        $names = $this->visitor->getQualifiedNames();

        // Assert
        $this->assertArrayHasKey('App\Service\TestService', $names);
    }
    #[Test]
    public function testItCollectsNamesFromNewExpressions(): void
    {
        // Arrange
        $name = new Name\FullyQualified(['App', 'Entity', 'User']);
        $newExpr = new New_($name);

        // Act
        $this->visitor->checkNode($newExpr);
        $names = $this->visitor->getQualifiedNames();

        // Assert
        $this->assertArrayHasKey('App\Entity\User', $names);
    }

    #[Test]
    public function testItCollectsNamesFromClassDefinitions(): void
    {
        // Arrange
        $class = new Class_(new Identifier('TestClass'));
        // Act
        $this->visitor->checkNode($class);
        $names = $this->visitor->getQualifiedNames();

        // Assert
        $this->assertArrayHasKey('TestClass', $names);
    }

    #[Test]
    public function testItReturnsEmptyArrayInitially(): void
    {
        // Act
        $names = $this->visitor->getQualifiedNames();

        // Assert
        $this->assertSame([], $names);
    }
}
