<?php

declare(strict_types=1);

namespace Tests\Unit;

use DouglasGreen\PhpLinter\Config;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Config::class)]
#[Small]
final class ConfigTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/phplint-test-' . uniqid();
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    #[Test]
    public function testItLoadsEmptyConfigWhenFileDoesNotExist(): void
    {
        // Arrange
        $configPath = $this->tempDir . '/nonexistent.json';

        // Act
        $config = new Config($this->tempDir, $configPath);

        // Assert
        $this->assertSame([], $config->getIgnoreIssues());
        $this->assertSame([], $config->getMetricLimits());
    }

    #[Test]
    public function testItLoadsIgnoreIssuesFromConfigFile(): void
    {
        // Arrange
        $configContent = json_encode([
            'ignoreIssues' => ['Test issue 1', 'Test issue 2'],
        ]);
        $configPath = $this->tempDir . '/php-linter.json';
        file_put_contents($configPath, $configContent);
        // Act
        $config = new Config($this->tempDir, $configPath);
        // Assert
        $this->assertSame(['Test issue 1', 'Test issue 2'], $config->getIgnoreIssues());
    }

    #[Test]
    public function testItLoadsMetricLimitsFromConfigFile(): void
    {
        // Arrange
        $configContent = json_encode([
            'metricLimits' => [
                'classSize' => 50,
                'methodLoc' => 100,
            ],
        ]);
        $configPath = $this->tempDir . '/php-linter.json';
        file_put_contents($configPath, $configContent);

        // Act
        $config = new Config($this->tempDir, $configPath);

        // Assert
        $this->assertSame(['classSize' => 50, 'methodLoc' => 100], $config->getMetricLimits());
    }

    #[Test]
    public function testItUsesDefaultPathWhenConfigPathIsNull(): void
    {
        // Arrange
        $configContent = json_encode(['ignoreIssues' => ['Default path issue']]);
        file_put_contents($this->tempDir . '/php-linter.json', $configContent);

        // Act
        $config = new Config($this->tempDir);

        // Assert
        $this->assertSame(['Default path issue'], $config->getIgnoreIssues());
    }

    #[Test]
    public function testItFiltersNonStringIgnoreIssues(): void
    {
        // Arrange
        $configContent = json_encode([
            'ignoreIssues' => ['Valid issue', 123, null, 'Another valid issue'],
        ]);
        $configPath = $this->tempDir . '/php-linter.json';
        file_put_contents($configPath, $configContent);

        // Act
        $config = new Config($this->tempDir, $configPath);

        // Assert
        $this->assertSame(['Valid issue', 'Another valid issue'], $config->getIgnoreIssues());
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
