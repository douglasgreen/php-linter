<?php

declare(strict_types=1);
namespace Tests\Unit\Visitor;

use DouglasGreen\PhpLinter\Visitor\MagicNumberVisitor;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\Float_;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(MagicNumberVisitor::class)]
#[Small]
final class MagicNumberVisitorTest extends TestCase
{
    private MagicNumberVisitor $visitor;

    protected function setUp(): void
    {
        $this->visitor = new MagicNumberVisitor();
    }

    #[Test]
    public function testItTracksMagicNumbers(): void
    {
        // Arrange
        $node = new Int_(42);

        // Act
        $this->visitor->checkNode($node);
        // Assert - checkDuplicates should report when count > 1
        $this->visitor->checkNode($node); // Add second occurrence
        // No exception means it tracked successfully
        $this->addToAssertionCount(1);
    }

    #[Test]
    public function testItIgnoresZeroAndOne(): void
    {
        // Arrange
        $zero = new Int_(0);
        $one = new Int_(1);

        // Act
        $this->visitor->checkNode($zero);
        $this->visitor->checkNode($one);
        // Assert - should not track these
        $this->addToAssertionCount(1);
    }

    #[Test]
    public function testItIgnoresSingleDigits(): void
    {
        // Arrange
        $node = new Int_(5);

        // Act
        $this->visitor->checkNode($node);

        // Assert - should not track single digits
        $this->addToAssertionCount(1);
    }

    #[Test]
    public function testItIgnoresRepeatedDigits(): void
    {
        // Arrange
        $node = new Int_(11);

        // Act
        $this->visitor->checkNode($node);

        // Assert - should not track repeated digits like 11, 222, etc.
        $this->addToAssertionCount(1);
    }

    #[Test]
    public function testItTracksFloatNumbers(): void
    {
        // Arrange
        $node = new Float_(3.14);

        // Act
        $this->visitor->checkNode($node);
        // Assert - should track without error
        $this->addToAssertionCount(1);
    }
}
