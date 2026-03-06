<?php

declare(strict_types=1);

namespace Tests\Unit\Metrics;

use DouglasGreen\PhpLinter\Metrics\XmlParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(XmlParser::class)]
#[Small]
final class XmlParserTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/phplint-xml-test-' . uniqid();
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    #[Test]
    public function testItThrowsExceptionForInvalidXmlFile(): void
    {
        // Arrange
        $xmlPath = $this->tempDir . '/invalid.xml';
        file_put_contents($xmlPath, 'not valid xml');

        // Assert
        $this->expectException(RuntimeException::class);

        // Act
        new XmlParser($xmlPath);
    }

    #[Test]
    public function testItParsesValidPDependXml(): void
    {
        // Arrange
        $xmlContent = <<<XML
<?xml version="1.0"?>
<pdepend>
    <files>
        <file name="test.php" cloc="10" eloc="50" loc="60"/>
    </files>
    <package name="TestPackage">
        <class name="TestClass" file="test.php" loc="100" csz="10">
            <method name="testMethod" loc="20" ccn2="5"/>
        </class>
    </package>
</pdepend>
XML;
        $xmlPath = $this->tempDir . '/summary.xml';
        file_put_contents($xmlPath, $xmlContent);

        // Act
        $parser = new XmlParser($xmlPath);
        $data = $parser->getData();

        // Assert
        $this->assertArrayHasKey('files', $data);
        $this->assertArrayHasKey('packages', $data);
        $this->assertArrayHasKey('metrics', $data);
    }

    #[Test]
    public function testItReturnsFilesList(): void
    {
        // Arrange
        $xmlContent = <<<XML
<?xml version="1.0"?>
<pdepend>
    <files>
        <file name="file1.php" cloc="5" eloc="25" loc="30"/>
        <file name="file2.php" cloc="10" eloc="50" loc="60"/>
    </files>
    <package name="TestPackage"/>
</pdepend>
XML;
        $xmlPath = $this->tempDir . '/summary.xml';
        file_put_contents($xmlPath, $xmlContent);

        // Act
        $parser = new XmlParser($xmlPath);
        $files = $parser->getFiles();

        // Assert
        $this->assertCount(2, $files);
        $this->assertSame('file1.php', $files[0]->name);
        $this->assertSame(30, $files[0]->loc);
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
