<?php

declare(strict_types=1);

namespace Tests\Unit\Linter;

use DouglasGreen\PhpLinter\IssueHolder;
use DouglasGreen\PhpLinter\Linter\ComposerFile;
use DouglasGreen\PhpLinter\Linter\ElementVisitor;
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
        $composerFile = $this->createStub(ComposerFile::class);
        $issueHolder = $this->createStub(IssueHolder::class);

        // Act
        $visitor = new ElementVisitor($composerFile, 'test.php', $issueHolder);

        // Assert
        $this->assertInstanceOf(ElementVisitor::class, $visitor);
    }

    #[Test]
    public function testItInitializesLocalScopeAsFalse(): void
    {
        // Arrange
        $composerFile = $this->createStub(ComposerFile::class);
        $issueHolder = $this->createStub(IssueHolder::class);
        $visitor = new ElementVisitor($composerFile, 'test.php', $issueHolder);

        // Assert
        $this->assertFalse($visitor->isLocalScope());
    }
}
