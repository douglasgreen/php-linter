<?php

declare(strict_types=1);
namespace Tests\Unit;

use DouglasGreen\PhpLinter\IssueHolder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(IssueHolder::class)]
#[Small]
final class IssueHolderTest extends TestCase
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
    public function testItReturnsSingletonInstance(): void
    {
        // Act
        $instance1 = IssueHolder::getInstance();
        $instance2 = IssueHolder::getInstance();
        // Assert
        $this->assertSame($instance1, $instance2);
    }

    #[Test]
    public function testItAddsAndRetrievesIssues(): void
    {
        // Arrange
        $holder = IssueHolder::getInstance();

        // Act
        $holder->addIssue('Test issue');
        // Assert
        $this->assertTrue($holder->hasIssues());
        $this->assertArrayHasKey('Test issue', $holder->getIssues());
    }

    #[Test]
    public function testItClearsIssues(): void
    {
        // Arrange
        $holder = IssueHolder::getInstance();
        $holder->addIssue('Test issue');

        // Act
        $holder->clearIssues();

        // Assert
        $this->assertFalse($holder->hasIssues());
        $this->assertSame([], $holder->getIssues());
    }

    #[Test]
    public function testItSetsIgnoreIssues(): void
    {
        // Arrange
        $holder = IssueHolder::getInstance();
        // Act
        $holder->setIgnoreIssues(['Ignored issue']);
        // Assert
        // Issue should be ignored when added
        $holder->addIssue('Ignored issue');
        $this->assertFalse($holder->hasIssues());
    }
    #[Test]
    public function testItAddsMultipleIssues(): void
    {
        // Arrange
        $holder = IssueHolder::getInstance();

        // Act
        $holder->addIssues(['Issue 1' => true, 'Issue 2' => true]);

        // Assert
        $issues = $holder->getIssues();
        $this->assertArrayHasKey('Issue 1', $issues);
        $this->assertArrayHasKey('Issue 2', $issues);
    }
}
