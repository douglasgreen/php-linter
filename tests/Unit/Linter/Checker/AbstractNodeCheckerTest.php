<?php

declare(strict_types=1);

namespace Tests\Unit\Linter\Checker;

use DouglasGreen\PhpLinter\IssueHolder;
use DouglasGreen\PhpLinter\Linter\Checker\AbstractNodeChecker;
use PhpParser\Node\Stmt\Nop;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractNodeChecker::class)]
#[Small]
final class AbstractNodeCheckerTest extends TestCase
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
        $node = new Nop();
        $checker = new class ($node, $this->issueHolder) extends AbstractNodeChecker {
            public function check(): array
            {
                $this->addIssue('Test issue');
                return [];
            }
        };
        // Act
        $checker->check();
        // Assert
        $this->assertTrue($this->issueHolder->hasIssues());
    }

    #[Test]
    public function testItAddsMultipleIssues(): void
    {
        // Arrange
        $node = new Nop();
        $checker = new class ($node, $this->issueHolder) extends AbstractNodeChecker {
            public function check(): array
            {
                $this->addIssues(['Issue 1' => true, 'Issue 2' => true]);
                return [];
            }
        };

        // Act
        $checker->check();

        $issues = $this->issueHolder->getIssues();

        // Assert
        $this->assertArrayHasKey('Issue 1', $issues);
        $this->assertArrayHasKey('Issue 2', $issues);
    }
}
