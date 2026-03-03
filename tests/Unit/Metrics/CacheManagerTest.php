<?php

declare(strict_types=1);
namespace Tests\Unit\Metrics;

use DouglasGreen\PhpLinter\Metrics\CacheManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(CacheManager::class)]
#[Small]
final class CacheManagerTest extends TestCase
{
    private string $tempDir;
    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/phplint-cache-test-' . uniqid();
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir . '/var');
    }

    #[Test]
    public function testItCreatesCacheDirectoriesOnConstruction(): void
    {
        // Act
        $cacheManager = new CacheManager($this->tempDir);
        // Assert
        $this->assertDirectoryExists($this->tempDir . '/var/cache/pdepend');
        $this->assertDirectoryExists($this->tempDir . '/var/cache/pdepend/files');
    }

    #[Test]
    public function testItReturnsCacheDirectoryPaths(): void
    {
        // Arrange
        $cacheManager = new CacheManager($this->tempDir);
        // Act & Assert
        $this->assertSame(
            $this->tempDir . '/var/cache/pdepend',
            $cacheManager->getCacheDir()
        );
        $this->assertSame(
            $this->tempDir . '/var/cache/pdepend/files',
            $cacheManager->getFileCacheDir()
        );
        $this->assertSame(
            $this->tempDir . '/var/cache/pdepend/summary.xml',
            $cacheManager->getSummaryFile()
        );
    }

    #[Test]
    public function testItCopiesFileToCache(): void
    {
        // Arrange
        $cacheManager = new CacheManager($this->tempDir);
        $sourceFile = $this->tempDir . '/source.txt';
        file_put_contents($sourceFile, 'test content');
        // Act
        $cacheManager->copyFile($sourceFile, 'subdir/dest.txt');
        // Assert
        $this->assertFileExists($this->tempDir . '/var/cache/pdepend/files/subdir/dest.txt');
        $this->assertSame('test content', file_get_contents($this->tempDir . '/var/cache/pdepend/files/subdir/dest.txt'));
    }

    #[Test]
    public function testItResolvesOriginalFileFromCachePath(): void
    {
        // Act & Assert
        $this->assertSame(
            'src/Controller.php',
            CacheManager::getOriginalFile('var/cache/pdepend/files/src/Controller.php.php')
        );
    }

    #[Test]
    public function testItReturnsCacheFileWhenNotInCacheDirectory(): void
    {
        // Act & Assert
        $this->assertSame(
            'src/Controller.php',
            CacheManager::getOriginalFile('src/Controller.php')
        );
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
