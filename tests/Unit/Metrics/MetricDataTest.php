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
        static::assertNull($metricData->name);
        static::assertNull($metricData->filename);
        static::assertSame([], $metricData->methods);
        static::assertNull($metricData->loc);
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
        static::assertSame('TestClass', $metricData->name);
        static::assertSame('TestClass.php', $metricData->filename);
        static::assertSame($methodData, $metricData->methods);
        static::assertSame(5, $metricData->ca);
        static::assertSame(3, $metricData->ce);
        static::assertSame(8, $metricData->cbo);
        static::assertSame(10, $metricData->ccn2);
        static::assertEqualsWithDelta(1.5, $metricData->cr, PHP_FLOAT_EPSILON);
        static::assertSame(15, $metricData->csz);
        static::assertSame(50, $metricData->cloc);
        static::assertSame(2, $metricData->dit);
        static::assertSame(200, $metricData->eloc);
        static::assertSame(5000, $metricData->he);
        static::assertSame(250, $metricData->loc);
        static::assertEqualsWithDelta(85.5, $metricData->mi, PHP_FLOAT_EPSILON);
        static::assertSame(3, $metricData->nocc);
        static::assertSame(8, $metricData->npm);
        static::assertSame(500, $metricData->npath);
        static::assertSame(10, $metricData->vars);
        static::assertSame(4, $metricData->varsnp);
    }

    #[Test]
    public function testItCreatesMetricDataWithPartialValues(): void
    {
        // Act
        $metricData = new MetricData(name: 'PartialClass', loc: 100);

        // Assert
        static::assertSame('PartialClass', $metricData->name);
        static::assertNull($metricData->filename);
        static::assertSame(100, $metricData->loc);
        static::assertNull($metricData->ca);
    }
}
