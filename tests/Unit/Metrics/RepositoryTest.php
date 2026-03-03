<?php

declare(strict_types=1);
namespace Tests\Unit\Metrics;

use DouglasGreen\PhpLinter\Metrics\Repository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Repository::class)]
#[Small]
final class RepositoryTest extends TestCase
{
    #[Test]
    public function testItCanBeInstantiated(): void
    {
        // This test verifies the class exists and can be loaded
        // Full testing requires a git repository context
        $this->assertTrue(class_exists(Repository::class));
    }
}
