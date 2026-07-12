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
        mkdir($this->tempDir, 0o777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    /**
     * @return iterable<string, array{0: string, 1: string, 2: string}>
     */
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
        static::assertTrue($ignoreList->shouldIgnore('vendor/autoload.php'));
        static::assertFalse($ignoreList->shouldIgnore('src/Controller.php'));
    }

    #[Test]
    public function testItIgnoresFilesMatchingQuestionMarkPattern(): void
    {
        // Arrange
        $this->createIgnoreFile(['test?.php']);
        // Act
        $ignoreList = new IgnoreList($this->tempDir);

        // Assert
        static::assertTrue($ignoreList->shouldIgnore('test1.php'));
        static::assertTrue($ignoreList->shouldIgnore('testA.php'));
        static::assertFalse($ignoreList->shouldIgnore('test10.php'));
    }

    #[Test]
    public function testItIgnoresFilesMatchingDirectoryPattern(): void
    {
        // Arrange
        $this->createIgnoreFile(['cache/*']);
        // Act
        $ignoreList = new IgnoreList($this->tempDir);

        // Assert
        static::assertTrue($ignoreList->shouldIgnore('cache/file1.php'));
        static::assertTrue($ignoreList->shouldIgnore('cache/subdir/file2.php'));
    }

    #[Test]
    public function testItSkipsEmptyLinesAndComments(): void
    {
        // Arrange
        file_put_contents($this->tempDir . '/.phplintignore', "# This is a comment\n\nvendor/*\n");
        // Act
        $ignoreList = new IgnoreList($this->tempDir);

        // Assert
        static::assertTrue($ignoreList->shouldIgnore('vendor/test.php'));
    }

    #[Test]
    public function testItReturnsNoPatternsWhenFileDoesNotExist(): void
    {
        // Act
        $ignoreList = new IgnoreList($this->tempDir);
        // Assert
        static::assertFalse($ignoreList->shouldIgnore('any/file.php'));
    }

    #[Test]
    #[DataProvider('subpathProvider')]
    public function testItCorrectlyAddsSubpath(string $base, string $sub, string $expected): void
    {
        // Act
        $result = IgnoreList::addSubpath($base, $sub);

        // Assert
        static::assertSame($expected, $result);
    }

    /**
     * @param list<string> $patterns
     */
    private function createIgnoreFile(array $patterns): void
    {
        file_put_contents($this->tempDir . '/.phplintignore', implode("\n", $patterns));
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = scandir($dir);
        if ($files === false) {
            return;
        }

        $files = array_diff($files, ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }

        rmdir($dir);
    }
}
