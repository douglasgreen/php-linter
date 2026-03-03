<?php

declare(strict_types=1);
namespace Tests\Unit\Visitor;

use DouglasGreen\PhpLinter\IssueHolder;
use DouglasGreen\PhpLinter\Visitor\AbstractVisitorChecker;
use PhpParser\Node;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractVisitorChecker::class)]
#[Small]
final class AbstractVisitorCheckerTest extends TestCase
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
    public function testItAddsIssuesThroughProtectedMethod(): void
    {
        // Arrange
        $visitor = new class extends AbstractVisitorChecker {
            public function checkNode(Node $node): void
            {
                $this->addIssue('Test issue');
            }
        };
        // Act
        $visitor->checkNode(new \PhpParser\Node\Stmt\Nop());

        // Assert
        $this->assertTrue($visitor->hasIssues());
        $this->assertSame(['Test issue' => true], $visitor->getIssues());
    }
    #[Test]
    public function testItAddsMultipleIssues(): void
    {
        // Arrange
        $visitor = new class extends AbstractVisitorChecker {
            public function checkNode(Node $node): void
            {
                $this->addIssues(['Issue 1' => true, 'Issue 2' => true]);
            }
        };
        // Act
        $visitor->checkNode(new \PhpParser\Node\Stmt\Nop());
        // Assert
        $issues = $visitor->getIssues();
        $this->assertArrayHasKey('Issue 1', $issues);
        $this->assertArrayHasKey('Issue 2', $issues);
    }
}
