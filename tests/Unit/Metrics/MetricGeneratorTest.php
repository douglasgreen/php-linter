<?php

declare(strict_types=1);
namespace Tests\Unit\Metrics;
use DouglasGreen\PhpLinter\Metrics\CacheManager;
use DouglasGreen\PhpLinter\Metrics\MetricGenerator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(MetricGenerator::class)]
#[Small]
final class MetricGeneratorTest extends TestCase
{
    #[Test]
    public function testItCanBeInstantiated(): void
    {
        // Arrange
        $cache = $this->createMock(CacheManager::class);
        // Act
        $generator = new MetricGenerator($cache, []);
        // Assert
        $this->assertInstanceOf(MetricGenerator::class, $generator);
    }
}
