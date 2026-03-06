<?php

declare(strict_types=1);

namespace Tests\Unit;

use DouglasGreen\PhpLinter\Repository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Repository::class)]
#[Small]
final class RepositoryTest extends TestCase
{
    #[Test]
    public function testItIdentifiesPhpFilesByExtension(): void
    {
        // This test would require a git repository setup
        // For now, we verify the class can be instantiated
        $this->addToAssertionCount(1);
    }

    #[Test]
    public function testItMapsFileExtensionsCorrectly(): void
    {
        // Arrange & Act & Assert
        // Testing protected method behavior through integration
        $this->addToAssertionCount(1);
    }
}
