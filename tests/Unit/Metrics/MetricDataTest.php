<?php

declare(strict_types=1);

namespace Tests\Unit\Metrics;

use DouglasGreen\PhpLinter\Metrics\MetricData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(MetricData::class)]
#[Small]
final class MetricDataTest extends TestCase
{
    #[Test]
    public function testItCreatesMetricDataWithDefaultValues(): void
    {
        // Act
        $metricData = new MetricData();

        // Assert
        $this->assertNull($metricData->name);
        $this->assertNull($metricData->filename);
        $this->assertSame([], $metricData->methods);
        $this->assertNull($metricData->loc);
    }

    #[Test]
    public function testItCreatesMetricDataWithAllValues(): void
    {
        // Arrange
        $methodData = [];

        // Act
        $metricData = new MetricData(
            name: 'TestClass',
            filename: 'TestClass.php',
            methods: $methodData,
            ca: 5,
            ce: 3,
            cbo: 8,
            ccn2: 10,
            cr: 1.5,
            csz: 15,
            cloc: 50,
            dit: 2,
            eloc: 200,
            he: 5000,
            loc: 250,
            mi: 85.5,
            nocc: 3,
            npm: 8,
            npath: 500,
            vars: 10,
            varsnp: 4,
        );
        // Assert
        $this->assertSame('TestClass', $metricData->name);
        $this->assertSame('TestClass.php', $metricData->filename);
        $this->assertSame($methodData, $metricData->methods);
        $this->assertSame(5, $metricData->ca);
        $this->assertSame(3, $metricData->ce);
        $this->assertSame(8, $metricData->cbo);
        $this->assertSame(10, $metricData->ccn2);
        $this->assertEqualsWithDelta(1.5, $metricData->cr, PHP_FLOAT_EPSILON);
        $this->assertSame(15, $metricData->csz);
        $this->assertSame(50, $metricData->cloc);
        $this->assertSame(2, $metricData->dit);
        $this->assertSame(200, $metricData->eloc);
        $this->assertSame(5000, $metricData->he);
        $this->assertSame(250, $metricData->loc);
        $this->assertEqualsWithDelta(85.5, $metricData->mi, PHP_FLOAT_EPSILON);
        $this->assertSame(3, $metricData->nocc);
        $this->assertSame(8, $metricData->npm);
        $this->assertSame(500, $metricData->npath);
        $this->assertSame(10, $metricData->vars);
        $this->assertSame(4, $metricData->varsnp);
    }

    #[Test]
    public function testItCreatesMetricDataWithPartialValues(): void
    {
        // Act
        $metricData = new MetricData(
            name: 'PartialClass',
            loc: 100,
        );

        // Assert
        $this->assertSame('PartialClass', $metricData->name);
        $this->assertNull($metricData->filename);
        $this->assertSame(100, $metricData->loc);
        $this->assertNull($metricData->ca);
    }
}
