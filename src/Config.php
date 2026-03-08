<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter;

/**
 * Handles loading and parsing of php-linter.json configuration file.
 *
 * @api
 */
class Config
{
    /**
     * List of issue strings to ignore.
     *
     * @var list<string>
     */
    protected readonly array $ignoreIssues;

    /**
     * Map of metric limit names to values.
     *
     * @var array<string, int|float>
     */
    protected readonly array $metricLimits;

    /**
     * Constructs a new Config instance by loading php-linter.json if it exists.
     *
     * @param string $currentDir The project root directory.
     * @param string|null $configFilePath Optional path to config file. If null, uses $currentDir/php-linter.json.
     */
    public function __construct(string $currentDir, ?string $configFilePath = null)
    {
        $configFile = $configFilePath ?? $currentDir . DIRECTORY_SEPARATOR . 'php-linter.json';

        if (! file_exists($configFile)) {
            $this->ignoreIssues = [];
            $this->metricLimits = [];
            return;
        }

        $content = file_get_contents($configFile);
        if ($content === false) {
            $this->ignoreIssues = [];
            $this->metricLimits = [];
            return;
        }

        $data = json_decode($content, true);
        if (! is_array($data)) {
            $this->ignoreIssues = [];
            $this->metricLimits = [];
            return;
        }

        // Parse ignoreIssues
        if (isset($data['ignoreIssues']) && is_array($data['ignoreIssues'])) {
            $this->ignoreIssues = array_values(array_filter($data['ignoreIssues'], is_string(...)));
        } else {
            $this->ignoreIssues = [];
        }

        // Parse metricLimits
        $this->metricLimits = isset($data['metricLimits']) && is_array($data['metricLimits']) ? $data['metricLimits'] : [];
    }

    /**
     * Returns the list of issues to ignore.
     *
     * @return list<string> List of issue strings to ignore.
     */
    public function getIgnoreIssues(): array
    {
        return $this->ignoreIssues;
    }

    /**
     * Returns the map of metric limits.
     *
     * @return array<string, int|float> Map of metric limit names to values.
     */
    public function getMetricLimits(): array
    {
        return $this->metricLimits;
    }
}
