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
 *
 * @since 1.0.0
 * @see https://pdepend.org/documentation/software-metrics/index.html
 *
 * @internal
 */
class XmlParser
{
    /**
     * Parsed data containing metrics, files, and packages.
     *
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
     * @return list<MetricData>|null List of file data objects or null if absent.
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
     *
     * @return list<MetricData> List of class data objects.
     */
    protected static function parseClasses(SimpleXMLElement $classes): array
    {
        $classList = [];
        foreach ($classes as $class) {
            $fileAttribs = $class->file->attributes();
            $filename = (string) $fileAttribs['name'];

            $classList[] = new MetricData(
                name: self::extractString($class, 'name'),
                filename: $filename,
                methods: self::parseMethods($class->method),
                ca: self::extractInt($class, 'ca'),
                ce: self::extractInt($class, 'ce'),
                cbo: self::extractInt($class, 'cbo'),
                ccn2: self::extractInt($class, 'ccn2'),
                cr: self::extractFloat($class, 'cr'),
                csz: self::extractInt($class, 'csz'),
                cloc: self::extractInt($class, 'cloc'),
                dit: self::extractInt($class, 'dit'),
                eloc: self::extractInt($class, 'eloc'),
                he: self::extractInt($class, 'he'),
                loc: self::extractInt($class, 'loc'),
                mi: self::extractFloat($class, 'mi'),
                nocc: self::extractInt($class, 'nocc'),
                npm: self::extractInt($class, 'npm'),
                npath: self::extractInt($class, 'npath'),
                vars: self::extractInt($class, 'vars'),
                varsnp: self::extractInt($class, 'varsnp'),
            );
        }

        return $classList;
    }

    /**
     * Parses file elements from XML.
     *
     * @param SimpleXMLElement $files The XML element containing files.
     *
     * @return list<MetricData> List of file data objects.
     */
    protected static function parseFiles(SimpleXMLElement $files): array
    {
        $fileList = [];
        foreach ($files->file as $file) {
            $fileList[] = new MetricData(
                name: self::extractString($file, 'name'),
                cloc: self::extractInt($file, 'cloc'),
                eloc: self::extractInt($file, 'eloc'),
                loc: self::extractInt($file, 'loc'),
            );
        }

        return $fileList;
    }

    /**
     * Parses function elements from XML.
     *
     * @param SimpleXMLElement $functions The XML element containing functions.
     *
     * @return list<MetricData> List of function data objects.
     */
    protected static function parseFunctions(SimpleXMLElement $functions): array
    {
        $functionList = [];
        foreach ($functions as $function) {
            $fileAttribs = $function->file->attributes();
            $filename = (string) $fileAttribs['name'];

            $functionList[] = new MetricData(
                name: self::extractString($function, 'name'),
                filename: $filename,
                ccn2: self::extractInt($function, 'ccn2'),
                cloc: self::extractInt($function, 'cloc'),
                eloc: self::extractInt($function, 'eloc'),
                he: self::extractInt($function, 'he'),
                loc: self::extractInt($function, 'loc'),
                mi: self::extractFloat($function, 'mi'),
                npath: self::extractInt($function, 'npath'),
            );
        }

        return $functionList;
    }

    /**
     * Parses method elements from XML.
     *
     * @param SimpleXMLElement $methods The XML element containing methods.
     *
     * @return list<MetricData> List of method data objects.
     */
    protected static function parseMethods(SimpleXMLElement $methods): array
    {
        $methodList = [];
        foreach ($methods as $method) {
            $methodList[] = new MetricData(
                name: self::extractString($method, 'name'),
                ccn2: self::extractInt($method, 'ccn2'),
                cloc: self::extractInt($method, 'cloc'),
                eloc: self::extractInt($method, 'eloc'),
                he: self::extractInt($method, 'he'),
                loc: self::extractInt($method, 'loc'),
                mi: self::extractFloat($method, 'mi'),
                npath: self::extractInt($method, 'npath'),
            );
        }

        return $methodList;
    }

    /**
     * Parses metric attributes from the root XML element.
     *
     * @param SimpleXMLElement $xml The root XML element.
     *
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
     *
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

    /**
     * Extracts a string attribute from an XML element.
     */
    protected static function extractString(SimpleXMLElement $element, string $attribute): ?string
    {
        $value = $element->attributes()[$attribute] ?? null;
        return $value !== null ? (string) $value : null;
    }

    /**
     * Extracts an integer attribute from an XML element.
     */
    protected static function extractInt(SimpleXMLElement $element, string $attribute): ?int
    {
        $value = $element->attributes()[$attribute] ?? null;
        return $value !== null ? (int) $value : null;
    }

    /**
     * Extracts a float attribute from an XML element.
     */
    protected static function extractFloat(SimpleXMLElement $element, string $attribute): ?float
    {
        $value = $element->attributes()[$attribute] ?? null;
        return $value !== null ? (float) $value : null;
    }
}
