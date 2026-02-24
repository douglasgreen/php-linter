<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Metrics;

use RuntimeException;
use SimpleXMLElement;

/**
 * Parses PDepend summary XML files into structured arrays.
 *
 * Provides access to metrics, files, and packages extracted from the XML.
 *
 * @package DouglasGreen\PhpLinter\Metrics
 * @since 1.0.0
 * @see https://pdepend.org/documentation/software-metrics/index.html
 * @internal
 */
class XmlParser
{
    /**
     * Parsed data containing metrics, files, and packages.
     * @var array<string, mixed>
     */
    protected readonly array $data;

    /**
     * Loads and parses the specified XML file.
     *
     * @param string $xmlFile Path to the PDepend summary XML file.
     *
     * @throws RuntimeException If the XML file cannot be loaded or is invalid.
     */
    public function __construct(
        protected readonly string $xmlFile,
    ) {
        $xml = simplexml_load_file($this->xmlFile);
        if ($xml === false) {
            throw new RuntimeException('Unable to load XML file');
        }

        if ($xml->files === null) {
            throw new RuntimeException('No files found');
        }

        if ($xml->package === null) {
            throw new RuntimeException('No package found');
        }

        $data = [];
        $data['metrics'] = self::parseMetrics($xml);
        $data['files'] = self::parseFiles($xml->files);
        $data['packages'] = self::parsePackages($xml->package);
        $this->data = $data;
    }

    /**
     * Returns the entire parsed data structure.
     *
     * @return array<string, mixed> Associative array of parsed data.
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Returns the list of parsed files.
     *
     * @return array<int, mixed>|null List of file data arrays or null if absent.
     */
    public function getFiles(): ?array
    {
        return $this->data['files'];
    }

    /**
     * Returns the list of parsed packages.
     *
     * @return array<int, mixed>|null List of package data arrays or null if absent.
     */
    public function getPackages(): ?array
    {
        return $this->data['packages'];
    }

    /**
     * Parses class elements from XML.
     *
     * @param SimpleXMLElement $classes The XML element containing classes.
     * @return array<string|int, mixed>[] List of class data arrays.
     */
    protected static function parseClasses(SimpleXMLElement $classes): array
    {
        $classList = [];
        foreach ($classes as $class) {
            $classData = [];
            foreach ($class->attributes() as $key => $value) {
                $classData[$key] = (string) $value;
            }

            $fileAttribs = $class->file->attributes();
            $classData['filename'] = (string) $fileAttribs['name'];

            $classData['methods'] = self::parseMethods($class->method);

            $classList[] = $classData;
        }

        return $classList;
    }

    /**
     * Parses file elements from XML.
     *
     * @param SimpleXMLElement $files The XML element containing files.
     * @return array<int<0, max>, array<string|int, string>> List of file data arrays.
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
     * Parses function elements from XML.
     *
     * @param SimpleXMLElement $functions The XML element containing functions.
     * @return array<mixed, array<string|int, string>> List of function data arrays.
     */
    protected static function parseFunctions(SimpleXMLElement $functions): array
    {
        $functionList = [];
        foreach ($functions as $function) {
            $functionData = [];
            foreach ($function->attributes() as $key => $value) {
                $functionData[$key] = (string) $value;
            }

            $fileAttribs = $function->file->attributes();
            $functionData['filename'] = (string) $fileAttribs['name'];

            $functionList[] = $functionData;
        }

        return $functionList;
    }

    /**
     * Parses method elements from XML.
     *
     * @param SimpleXMLElement $methods The XML element containing methods.
     * @return array<mixed, array<string|int, string>> List of method data arrays.
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
     * Parses metric attributes from the root XML element.
     *
     * @param SimpleXMLElement $xml The root XML element.
     * @return string[] Associative array of metric names to values.
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
     * Parses package elements from XML.
     *
     * @param SimpleXMLElement $packages The XML element containing packages.
     * @return array<string|int, mixed>[] List of package data arrays.
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
            $packageData['functions'] = self::parseFunctions($package->function);
            $packageList[] = $packageData;
        }

        return $packageList;
    }
}
