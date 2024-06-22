<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter;

use Exception;

/**
 * @see https://pdepend.org/documentation/software-metrics/index.html
 */
final class PDependParser
{
    private readonly string $xmlFile;

    /**
     * @var array<string, mixed>
     */
    private array $data = [];

    public function __construct(string $xmlFile)
    {
        if (! file_exists($xmlFile)) {
            throw new Exception('File not found: ' . $xmlFile);
        }

        $this->xmlFile = $xmlFile;
    }

    public function parse(): void
    {
        $xml = simplexml_load_file($this->xmlFile);
        if ($xml === false) {
            throw new Exception('Unable to load XML');
        }
        /*
        if ($xml->files === false || $xml->package === false) {
            throw new Exception('Bad XML format');
        }
         */
        $this->data['metrics'] = $this->parseMetrics($xml);
        $this->data['files'] = $this->parseFiles($xml->files);
        $this->data['packages'] = $this->parsePackages($xml->package);
    }

    /**
     * @return string[]
     */
    private function parseMetrics(\SimpleXMLElement $xml): array
    {
        $attributes = $xml->attributes();
        $metrics = [];
        foreach ($attributes as $key => $value) {
            $metrics[$key] = (string) $value;
        }

        return $metrics;
    }

    /**
     * @return array<string, array<int|string, string>>
     */
    private function parseFiles($files): array
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
     * @return \non-empty-array<(\int | \string), \mixed>[]
     */
    private function parsePackages($packages): array
    {
        $packageList = [];
        foreach ($packages as $package) {
            $packageData = [];
            foreach ($package->attributes() as $key => $value) {
                $packageData[$key] = (string) $value;
            }

            $packageData['classes'] = $this->parseClasses($package->class);
            $packageList[] = $packageData;
        }

        return $packageList;
    }

    /**
     * @return \non-empty-array<(\int | \string), \mixed>[]
     */
    private function parseClasses($classes): array
    {
        $classList = [];
        foreach ($classes as $class) {
            $classData = [];
            foreach ($class->attributes() as $key => $value) {
                $classData[$key] = (string) $value;
            }

            $classData['methods'] = $this->parseMethods($class->method);
            $classList[] = $classData;
        }

        return $classList;
    }

    /**
     * @return array<mixed, array<int|string, string>>
     */
    private function parseMethods($methods): array
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

    public function getData(): array
    {
        return $this->data;
    }
}
