<?php

declare(strict_types=1);

namespace Tests\Unit;

use DouglasGreen\PhpLinter\IgnoreList;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(IgnoreList::class)]
#[Small]
final class IgnoreListTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/phplint-ignore-test-' . uniqid();
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    public static function subpathProvider(): iterable
    {
        yield 'trailing slash on base' => ['/path/to/', 'subdir/file.txt', '/path/to/subdir/file.txt'];
        yield 'no trailing slash on base' => ['/path/to', 'subdir/file.txt', '/path/to/subdir/file.txt'];
        yield 'leading slash on sub' => ['/path/to', '/subdir/file.txt', '/path/to/subdir/file.txt'];
    }

    #[Test]
    public function testItIgnoresFilesMatchingStarPattern(): void
    {
        // Arrange
        $this->createIgnoreFile(['vendor/*.php']);
        // Act
        $ignoreList = new IgnoreList($this->tempDir);

        // Assert
        $this->assertTrue($ignoreList->shouldIgnore('vendor/autoload.php'));
        $this->assertFalse($ignoreList->shouldIgnore('src/Controller.php'));
    }

    #[Test]
    public function testItIgnoresFilesMatchingQuestionMarkPattern(): void
    {
        // Arrange
        $this->createIgnoreFile(['test?.php']);
        // Act
        $ignoreList = new IgnoreList($this->tempDir);

        // Assert
        $this->assertTrue($ignoreList->shouldIgnore('test1.php'));
        $this->assertTrue($ignoreList->shouldIgnore('testA.php'));
        $this->assertFalse($ignoreList->shouldIgnore('test10.php'));
    }

    #[Test]
    public function testItIgnoresFilesMatchingDirectoryPattern(): void
    {
        // Arrange
        $this->createIgnoreFile(['cache/*']);
        // Act
        $ignoreList = new IgnoreList($this->tempDir);

        // Assert
        $this->assertTrue($ignoreList->shouldIgnore('cache/file1.php'));
        $this->assertTrue($ignoreList->shouldIgnore('cache/subdir/file2.php'));
    }

    #[Test]
    public function testItSkipsEmptyLinesAndComments(): void
    {
        // Arrange
        file_put_contents(
            $this->tempDir . '/.phplintignore',
            "# This is a comment\n\nvendor/*\n",
        );
        // Act
        $ignoreList = new IgnoreList($this->tempDir);

        // Assert
        $this->assertTrue($ignoreList->shouldIgnore('vendor/test.php'));
    }

    #[Test]
    public function testItReturnsNoPatternsWhenFileDoesNotExist(): void
    {
        // Act
        $ignoreList = new IgnoreList($this->tempDir);
        // Assert
        $this->assertFalse($ignoreList->shouldIgnore('any/file.php'));
    }

    #[Test]
    #[DataProvider('subpathProvider')]
    public function testItCorrectlyAddsSubpath(string $base, string $sub, string $expected): void
    {
        // Act
        $result = IgnoreList::addSubpath($base, $sub);

        // Assert
        $this->assertSame($expected, $result);
    }

    private function createIgnoreFile(array $patterns): void
    {
        file_put_contents($this->tempDir . '/.phplintignore', implode("\n", $patterns));
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
