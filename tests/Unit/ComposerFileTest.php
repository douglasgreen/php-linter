<?php

declare(strict_types=1);
namespace Tests\Unit;

use DouglasGreen\PhpLinter\ComposerFile;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ComposerFile::class)]
#[Small]
final class ComposerFileTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/phplint-composer-test-' . uniqid();
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }
    #[Test]
    public function testItLoadsPsr4MappingsFromComposerJson(): void
    {
        // Arrange
        $composerContent = json_encode([
            'autoload' => [
                'psr-4' => [
                    'App\\' => 'src/',
                ],
            ],
        ]);
        $composerPath = $this->tempDir . '/composer.json';
        file_put_contents($composerPath, $composerContent);

        // Act
        $composerFile = new ComposerFile($composerPath);

        // Assert
        $this->assertSame('src/Controller.php', $composerFile->convertClassNameToFileName('App\\Controller'));
    }
    #[Test]
    public function testItHandlesMultiplePsr4Paths(): void
    {
        // Arrange
        $composerContent = json_encode([
            'autoload' => [
                'psr-4' => [
                    'App\\' => ['src/', 'lib/'],
                ],
            ],
        ]);
        $composerPath = $this->tempDir . '/composer.json';
        file_put_contents($composerPath, $composerContent);

        // Act
        $composerFile = new ComposerFile($composerPath);

        // Assert
        $this->assertSame('src/Controller.php', $composerFile->convertClassNameToFileName('App\\Controller'));
    }
    #[Test]
    public function testItReturnsNullForUnmappedNamespace(): void
    {
        // Arrange
        $composerContent = json_encode([
            'autoload' => [
                'psr-4' => [
                    'App\\' => 'src/',
                ],
            ],
        ]);
        $composerPath = $this->tempDir . '/composer.json';
        file_put_contents($composerPath, $composerContent);

        // Act
        $composerFile = new ComposerFile($composerPath);

        // Assert
        $this->assertNull($composerFile->convertClassNameToFileName('Vendor\\Package\\Class'));
    }

    #[Test]
    public function testItHandlesNestedNamespaces(): void
    {
        // Arrange
        $composerContent = json_encode([
            'autoload' => [
                'psr-4' => [
                    'App\\' => 'src/',
                ],
            ],
        ]);
        $composerPath = $this->tempDir . '/composer.json';
        file_put_contents($composerPath, $composerContent);

        // Act
        $composerFile = new ComposerFile($composerPath);

        // Assert
        $this->assertSame(
            'src/Service/User/Manager.php',
            $composerFile->convertClassNameToFileName('App\\Service\\User\\Manager')
        );
    }

    #[Test]
    public function testItHandlesEmptyPsr4Mappings(): void
    {
        // Arrange
        $composerContent = json_encode([
            'autoload' => [
                'psr-4' => [],
            ],
        ]);
        $composerPath = $this->tempDir . '/composer.json';
        file_put_contents($composerPath, $composerContent);

        // Act
        $composerFile = new ComposerFile($composerPath);

        // Assert
        $this->assertNull($composerFile->convertClassNameToFileName('App\\Controller'));
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
