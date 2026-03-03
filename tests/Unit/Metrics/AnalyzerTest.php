<?php

declare(strict_types=1);

namespace Tests\Unit\Metrics;

use DouglasGreen\PhpLinter\Config;
use DouglasGreen\PhpLinter\IgnoreList;
use DouglasGreen\PhpLinter\IssueHolder;
use DouglasGreen\PhpLinter\Metrics\Analyzer;
use DouglasGreen\PhpLinter\Metrics\CacheManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Analyzer::class)]
#[Small]
final class AnalyzerTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/phplint-analyzer-test-' . uniqid();
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
        $cache = $this->createMock(CacheManager::class);
        $ignoreList = $this->createMock(IgnoreList::class);
        $config = $this->createMock(Config::class);
        $issueHolder = $this->createMock(IssueHolder::class);

        // Act
        $analyzer = new Analyzer($this->tempDir, $cache, $ignoreList, $config, $issueHolder);

        // Assert
        $this->assertInstanceOf(Analyzer::class, $analyzer);
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
