<?php

declare(strict_types=1);

namespace Tests\Unit\Linter\Visitor;

use DouglasGreen\PhpLinter\IssueHolder;
use DouglasGreen\PhpLinter\Linter\Visitor\AbstractVisitorChecker;
use PhpParser\Node;
use PhpParser\Node\Stmt\Nop;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractVisitorChecker::class)]
#[Small]
final class AbstractVisitorCheckerTest extends TestCase
{
    private IssueHolder $issueHolder;

    protected function setUp(): void
    {
        $this->issueHolder = new IssueHolder();
    }

    #[Test]
    public function testItAddsIssuesThroughProtectedMethod(): void
    {
        // Arrange
        $visitor = new class ($this->issueHolder) extends AbstractVisitorChecker {
            public function checkNode(Node $node): void
            {
                $this->addIssue('Test issue');
            }
        };
        // Act
        $visitor->checkNode(new Nop());

        // Assert
        $this->assertTrue($this->issueHolder->hasIssues());
        $this->assertSame(['Test issue' => true], $this->issueHolder->getIssues());
    }

    #[Test]
    public function testItAddsMultipleIssues(): void
    {
        // Arrange
        $visitor = new class ($this->issueHolder) extends AbstractVisitorChecker {
            public function checkNode(Node $node): void
            {
                $this->addIssues(['Issue 1' => true, 'Issue 2' => true]);
            }
        };
        // Act
        $visitor->checkNode(new Nop());
        // Assert
        $issues = $this->issueHolder->getIssues();
        $this->assertArrayHasKey('Issue 1', $issues);
        $this->assertArrayHasKey('Issue 2', $issues);
    }
}
