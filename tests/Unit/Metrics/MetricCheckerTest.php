<?php
declare(strict_types=1);
namespace Tests\Unit\Metrics;

use DouglasGreen\PhpLinter\IssueHolder;
use DouglasGreen\PhpLinter\Metrics\MetricChecker;
use DouglasGreen\PhpLinter\Metrics\MetricData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(MetricChecker::class)]
#[Small]
final class MetricCheckerTest extends TestCase
{
    private IssueHolder $issueHolder;

    protected function setUp(): void
    {
        $this->issueHolder = new IssueHolder();
    }

    #[Test]
    public function testItReturnsOkWhenValueIsWithinLimit(): void
    {
        // Arrange
        $data = new MetricData(loc: 50);
        $checker = new MetricChecker($data, $this->issueHolder);
        // Act
        $result = $checker->checkMaxLinesOfCode(100);
        // Assert
        $this->assertSame(MetricChecker::STATUS_OK, $result);
        $this->assertFalse($checker->hasIssues());
    }

    #[Test]
    public function testItReturnsErrorWhenValueExceedsLimit(): void
    {
        // Arrange
        $data = new MetricData(loc: 150);
        $checker = new MetricChecker($data, $this->issueHolder);
        // Act
        $result = $checker->checkMaxLinesOfCode(100);
        // Assert
        $this->assertSame(MetricChecker::STATUS_ERROR, $result);
        $this->assertTrue($checker->hasIssues());
    }
    #[Test]
    public function testItChecksCyclomaticComplexity(): void
    {
        // Arrange
        $data = new MetricData(ccn2: 30);
        $checker = new MetricChecker($data, $this->issueHolder);
        // Act
        $result = $checker->checkMaxCyclomaticComplexity(25);

        // Assert
        $this->assertSame(MetricChecker::STATUS_ERROR, $result);
    }

    #[Test]
    public function testItChecksMaintainabilityIndex(): void
    {
        // Arrange
        $data = new MetricData(mi: 20.0);
        $checker = new MetricChecker($data, $this->issueHolder);

        // Act
        $result = $checker->checkMinMaintainabilityIndex(25.0);

        // Assert
        $this->assertSame(MetricChecker::STATUS_ERROR, $result);
    }

    #[Test]
    public function testItSkipsCommentRatioCheckWhenNoExecutableLines(): void
    {
        // Arrange
        $data = new MetricData(eloc: 0, cloc: 0);
        $checker = new MetricChecker($data, $this->issueHolder);
        // Act
        $result = $checker->checkMinCommentRatio(0.05);

        // Assert
        $this->assertSame(MetricChecker::STATUS_OK, $result);
    }

    #[Test]
    public function testItFormatsIssuesWithClassAndFunctionName(): void
    {
        // Arrange
        $data = new MetricData(loc: 150);
        $checker = new MetricChecker($data, $this->issueHolder, 'TestClass', 'testMethod');

        // Act
        $checker->checkMaxLinesOfCode(100);
        $issues = $checker->getIssues();

        // Assert
        $this->assertNotEmpty($issues);
        $this->assertStringContainsString('TestClass::testMethod()', $issues[0]);
    }

    #[Test]
    public function testItFormatsIssuesWithFunctionNameOnly(): void
    {
        // Arrange
        $data = new MetricData(loc: 150);
        $checker = new MetricChecker($data, $this->issueHolder, null, 'testFunction');

        // Act
        $checker->checkMaxLinesOfCode(100);
        $issues = $checker->getIssues();

        // Assert
        $this->assertNotEmpty($issues);
        $this->assertStringContainsString('testFunction()', $issues[0]);
    }

    #[Test]
    public function testItFormatsIssuesWithFileWhenNoClassOrFunction(): void
    {
        // Arrange
        $data = new MetricData(loc: 150);
        $checker = new MetricChecker($data, $this->issueHolder);

        // Act
        $checker->checkMaxLinesOfCode(100);
        $issues = $checker->getIssues();
        // Assert
        $this->assertNotEmpty($issues);
        $this->assertStringContainsString('File', $issues[0]);
    }
}
