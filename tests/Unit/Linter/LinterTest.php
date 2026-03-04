<?php
declare(strict_types=1);
namespace Tests\Unit\Linter;

use DouglasGreen\PhpLinter\Linter\ComposerFile;
use DouglasGreen\PhpLinter\IgnoreList;
use DouglasGreen\PhpLinter\IssueHolder;
use DouglasGreen\PhpLinter\Linter\Linter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Linter::class)]
#[Small]
final class LinterTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/phplint-linter-test-' . uniqid();
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    #[Test]
    public function testItCanBeInstantiated(): void
    {
        // Arrange
        $composerFile = $this->createMock(ComposerFile::class);
        $ignoreList = $this->createMock(IgnoreList::class);
        $issueHolder = $this->createMock(IssueHolder::class);
        // Act
        $linter = new Linter($composerFile, $ignoreList, $issueHolder);

        // Assert
        $this->assertInstanceOf(Linter::class, $linter);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
