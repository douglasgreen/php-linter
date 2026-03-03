<?php

declare(strict_types=1);

namespace Tests\Unit\Checker;

use DouglasGreen\PhpLinter\Checker\AbstractNodeChecker;
use DouglasGreen\PhpLinter\IssueHolder;
use PhpParser\Node;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractNodeChecker::class)]
#[Small]
final class AbstractNodeCheckerTest extends TestCase
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
        $node = new \PhpParser\Node\Stmt\Nop();
        $checker = new class($node) extends AbstractNodeChecker {
            public function check(): array
            {
                $this->addIssue('Test issue');
                return $this->getIssues();
            }
        };
        // Act
        $checker->check();
        // Assert
        $this->assertTrue($checker->hasIssues());
    }

    #[Test]
    public function testItAddsMultipleIssues(): void
    {
        // Arrange
        $node = new \PhpParser\Node\Stmt\Nop();
        $checker = new class($node) extends AbstractNodeChecker {
            public function check(): array
            {
                $this->addIssues(['Issue 1' => true, 'Issue 2' => true]);
                return $this->getIssues();
            }
        };

        // Act
        $issues = $checker->check();

        // Assert
        $this->assertArrayHasKey('Issue 1', $issues);
        $this->assertArrayHasKey('Issue 2', $issues);
    }
}
