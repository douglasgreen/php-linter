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
    private IssueHolder $issueHolder;

    protected function setUp(): void
    {
        $this->issueHolder = new IssueHolder();
    }

    #[Test]
    public function testItAddsAndRetrievesIssues(): void
    {
        // Act
        $this->issueHolder->addIssue('Test issue');
        // Assert
        $this->assertTrue($this->issueHolder->hasIssues());
        $this->assertArrayHasKey('Test issue', $this->issueHolder->getIssues());
    }

    #[Test]
    public function testItClearsIssues(): void
    {
        // Arrange
        $this->issueHolder->addIssue('Test issue');

        // Act
        $this->issueHolder->clearIssues();

        // Assert
        $this->assertFalse($this->issueHolder->hasIssues());
        $this->assertSame([], $this->issueHolder->getIssues());
    }

    #[Test]
    public function testItSetsIgnoreIssues(): void
    {
        // Act
        $this->issueHolder->setIgnoreIssues(['Ignored issue']);
        // Assert
        // Issue should be ignored when added
        $this->issueHolder->addIssue('Ignored issue');
        $this->assertFalse($this->issueHolder->hasIssues());
    }

    #[Test]
    public function testItAddsMultipleIssues(): void
    {
        // Act
        $this->issueHolder->addIssues(['Issue 1' => true, 'Issue 2' => true]);

        // Assert
        $issues = $this->issueHolder->getIssues();
        $this->assertArrayHasKey('Issue 1', $issues);
        $this->assertArrayHasKey('Issue 2', $issues);
    }
}
