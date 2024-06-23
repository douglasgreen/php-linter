<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter;

use SimpleXMLElement;
use DouglasGreen\Utility\FileSystem\FileException;

/**
 * @see https://pdepend.org/documentation/software-metrics/index.html
 */
class PdependParser
{
    public const SUMMARY_FILE = 'var/cache/pdepend/summary.xml';

    /**
     * @var array<string, mixed>
     */
    protected readonly array $data;

    /**
     * @throws FileException
     */
    public function __construct(
        protected readonly string $xmlFile
    ) {
        $xml = simplexml_load_file($this->xmlFile);
        if ($xml === false) {
            throw new FileException('Unable to load XML file');
        }

        if ($xml->files === null) {
            throw new FileException('No files found');
        }

        if ($xml->package === null) {
            throw new FileException('No package found');
        }

        $data = [];
        $data['metrics'] = self::parseMetrics($xml);
        $data['files'] = self::parseFiles($xml->files);
        $data['packages'] = self::parsePackages($xml->package);
        $this->data = $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @return array<int, mixed>
     */
    public function getPackages(): ?array
    {
        return $this->data['packages'];
    }

    /**
     * @return array<string|int, mixed>[]
     */
    protected static function parseClasses(SimpleXMLElement $classes): array
    {
        $classList = [];
        foreach ($classes as $class) {
            $classData = [];
            foreach ($class->attributes() as $key => $value) {
                $classData[$key] = (string) $value;
            }

            $classData['methods'] = self::parseMethods($class->method);
            $classList[] = $classData;
        }

        return $classList;
    }

    /**
     * @return array<int<0, max>, array<string|int, string>>
     */
    protected static function parseFiles(SimpleXMLElement $files): array
    {
        $fileList = [];
        foreach ($files->file as $file) {
            $fileData = [];
            foreach ($file->attributes() as $key => $value) {
                $fileData[$key] = (string) $value;
            }

            $fileList[] = $fileData;
        }

        return $fileList;
    }

    /**
     * @return array<mixed, array<string|int, string>>
     */
    protected static function parseMethods(SimpleXMLElement $methods): array
    {
        $methodList = [];
        foreach ($methods as $method) {
            $methodData = [];
            foreach ($method->attributes() as $key => $value) {
                $methodData[$key] = (string) $value;
            }

            $methodList[] = $methodData;
        }

        return $methodList;
    }

    /**
     * @return string[]
     */
    protected static function parseMetrics(SimpleXMLElement $xml): array
    {
        $attributes = $xml->attributes();
        $metrics = [];
        foreach ($attributes as $key => $value) {
            $metrics[$key] = (string) $value;
        }

        return $metrics;
    }

    /**
     * @return array<string|int, mixed>[]
     */
    protected static function parsePackages(SimpleXMLElement $packages): array
    {
        $packageList = [];
        foreach ($packages as $package) {
            $packageData = [];
            foreach ($package->attributes() as $key => $value) {
                $packageData[$key] = (string) $value;
            }

            $packageData['classes'] = self::parseClasses($package->class);
            $packageList[] = $packageData;
        }

        return $packageList;
    }
}
