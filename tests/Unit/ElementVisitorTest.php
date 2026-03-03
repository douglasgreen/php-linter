<?php

declare(strict_types=1);
namespace Tests\Unit;

use DouglasGreen\PhpLinter\ComposerFile;
use DouglasGreen\PhpLinter\ElementVisitor;
use DouglasGreen\PhpLinter\IssueHolder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ElementVisitor::class)]
#[Small]
final class ElementVisitorTest extends TestCase
{
    #[Test]
    public function testItCanBeInstantiated(): void
    {
        // Arrange
        $composerFile = $this->createMock(ComposerFile::class);
        $issueHolder = $this->createMock(IssueHolder::class);

        // Act
        $visitor = new ElementVisitor($composerFile, 'test.php', $issueHolder);

        // Assert
        $this->assertInstanceOf(ElementVisitor::class, $visitor);
    }

    #[Test]
    public function testItInitializesLocalScopeAsFalse(): void
    {
        // Arrange
        $composerFile = $this->createMock(ComposerFile::class);
        $issueHolder = $this->createMock(IssueHolder::class);
        $visitor = new ElementVisitor($composerFile, 'test.php', $issueHolder);

        // Assert
        $this->assertFalse($visitor->isLocalScope());
    }
}
