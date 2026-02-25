<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Metrics;

use Exception;

/**
 * Analyzes PHP files using PDepend metrics.
 *
 * Orchestrates metric generation, parsing, and checking against defined thresholds.
 *
 * @package DouglasGreen\PhpLinter\Metrics
 *
 * @since 1.0.0
 *
 * @api
 */
class Analyzer
{
    /**
     * Maximum allowed class size (methods + properties).
     *
     * @var int
     */
    public const CLASS_SIZE_LIMIT = 60;

    /**
     * Maximum allowed code rank.
     *
     * @var float
     */
    public const CODE_RANK_LIMIT = 2.0;

    /**
     * Maximum lines of code for a class.
     *
     * @var int
     */
    public const CLASS_LOC_LIMIT = 1100;

    /**
     * Maximum non-private properties allowed.
     *
     * @var int
     */
    public const NON_PRIVATE_PROPS_LIMIT = 30;

    /**
     * Maximum properties allowed.
     *
     * @var int
     */
    public const PROPERTIES_LIMIT = 25;

    /**
     * Maximum public methods allowed.
     *
     * @var int
     */
    public const PUBLIC_METHODS_LIMIT = 40;

    /**
     * Maximum afferent coupling allowed.
     *
     * @var int
     */
    public const AFFERENT_COUPLING_LIMIT = 45;

    /**
     * Maximum efferent coupling allowed.
     *
     * @var int
     */
    public const EFFERENT_COUPLING_LIMIT = 24;

    /**
     * Maximum inheritance depth allowed.
     *
     * @var int
     */
    public const INHERITANCE_DEPTH_LIMIT = 5;

    /**
     * Maximum child classes allowed.
     *
     * @var int
     */
    public const CHILD_CLASSES_LIMIT = 35;

    /**
     * Maximum object coupling allowed.
     *
     * @var int
     */
    public const OBJECT_COUPLING_LIMIT = 24;

    /**
     * Minimum comment ratio required.
     *
     * @var float
     */
    public const COMMENT_RATIO_LIMIT = 0.05;

    /**
     * Maximum cyclomatic complexity allowed.
     *
     * @var int
     */
    public const CYCLOMATIC_COMPLEXITY_LIMIT = 25;

    /**
     * Maximum lines of code for a method.
     *
     * @var int
     */
    public const METHOD_LOC_LIMIT = 130;

    /**
     * Maximum NPath complexity allowed.
     *
     * @var int
     */
    public const NPATH_COMPLEXITY_LIMIT = 10000;

    /**
     * Maximum Halstead effort allowed.
     *
     * @var int
     */
    public const HALSTEAD_EFFORT_LIMIT = 135000;

    /**
     * Minimum maintainability index required.
     *
     * @var int
     */
    public const MAINTAINABILITY_INDEX_LIMIT = 25;

    /**
     * Maximum lines of code for a file.
     *
     * @var int
     */
    public const FILE_LOC_LIMIT = 200;

    /**
     * Initializes the Analyzer.
     *
     * @param string $currentDir The current working directory.
     * @param CacheManager $cache The cache manager instance.
     * @param IgnoreList $ignoreList The ignore list for filtering files.
     */
    public function __construct(
        protected readonly string $currentDir,
        protected readonly CacheManager $cache,
        protected readonly IgnoreList $ignoreList,
    ) {}

    /**
     * Runs the metric analysis on the provided PHP files.
     *
     * Checks cache validity, generates metrics if needed, parses results,
     * and checks metrics against limits.
     *
     * @param list<string> $phpFiles List of PHP file paths to analyze.
     *
     * @throws Exception If parsing or file operations fail.
     *
     * @sideeffect Writes to stdout for progress and issues.
     * @sideeffect May generate cache files and PDepend summary XML.
     */
    public function run(array $phpFiles): void
    {
        $summaryFile = $this->cache->getSummaryFile();
        $shouldGenerate = !file_exists($summaryFile);

        if (!$shouldGenerate) {
            $summaryTimestamp = filemtime($summaryFile);
            foreach ($phpFiles as $phpFile) {
                if (filemtime($phpFile) > $summaryTimestamp) {
                    $shouldGenerate = true;
                    break;
                }
            }
        }

        if ($shouldGenerate) {
            $metricGenerator = new MetricGenerator($this->cache, $phpFiles);
            $metricGenerator->generate();
        }

        if (!file_exists($summaryFile)) {
            echo '=> Skipping metrics checks (PDepend summary XML file not found)' . PHP_EOL;
            return;
        }

        try {
            $parser = new XmlParser($summaryFile);
            $packages = $parser->getPackages();
            $filesData = $parser->getFiles();
            if ($packages === null) {
                echo 'No packages found.' . PHP_EOL;
                return;
            }

            // Check file-level metrics
            if ($filesData !== null) {
                foreach ($filesData as $fileInfo) {
                    $filename = CacheManager::getOriginalFile(str_replace($this->currentDir . DIRECTORY_SEPARATOR, '', $fileInfo['name']));
                    if ($this->ignoreList->shouldIgnore($filename)) {
                        continue;
                    }

                    $fileChecker = new MetricChecker($fileInfo);
                    $fileChecker->checkMinCommentRatio(self::COMMENT_RATIO_LIMIT);
                    $fileChecker->checkMinMaintainabilityIndex(self::MAINTAINABILITY_INDEX_LIMIT);
                    $fileChecker->printIssues($filename);
                }
            }

            $filesChecked = [];
            foreach ($packages as $package) {
                foreach ($package['classes'] as $class) {
                    $filename = CacheManager::getOriginalFile(str_replace($this->currentDir . DIRECTORY_SEPARATOR, '', $class['filename']));
                    if ($this->ignoreList->shouldIgnore($filename)) {
                        continue;
                    }

                    $classChecker = new MetricChecker($class, $class['name']);
                    $classChecker->checkMaxClassSize(self::CLASS_SIZE_LIMIT);
                    $classChecker->checkMaxCodeRank(self::CODE_RANK_LIMIT);
                    $classChecker->checkMaxLinesOfCode(self::CLASS_LOC_LIMIT);
                    $classChecker->checkMaxNonPrivateProperties(self::NON_PRIVATE_PROPS_LIMIT);
                    $classChecker->checkMaxProperties(self::PROPERTIES_LIMIT);
                    $classChecker->checkMaxPublicMethods(self::PUBLIC_METHODS_LIMIT);
                    $classChecker->checkMaxAfferentCoupling(self::AFFERENT_COUPLING_LIMIT);
                    $classChecker->checkMaxEfferentCoupling(self::EFFERENT_COUPLING_LIMIT);
                    $classChecker->checkMaxInheritanceDepth(self::INHERITANCE_DEPTH_LIMIT);
                    $classChecker->checkMaxNumberOfChildClasses(self::CHILD_CLASSES_LIMIT);
                    $classChecker->checkMaxObjectCoupling(self::OBJECT_COUPLING_LIMIT);

                    $loc = $class['loc'] ?? 0;
                    $filesChecked[$filename] = ($filesChecked[$filename] ?? 0) + $loc;
                    $classChecker->printIssues($filename);

                    foreach ($class['methods'] as $method) {
                        $methodChecker = new MetricChecker($method, $class['name'], $method['name']);
                        $methodChecker->checkMaxCyclomaticComplexity(self::CYCLOMATIC_COMPLEXITY_LIMIT);
                        $methodChecker->checkMaxLinesOfCode(self::METHOD_LOC_LIMIT);
                        $methodChecker->checkMaxNpathComplexity(self::NPATH_COMPLEXITY_LIMIT);
                        $methodChecker->checkMaxHalsteadEffort(self::HALSTEAD_EFFORT_LIMIT);
                        $methodChecker->checkMinMaintainabilityIndex(self::MAINTAINABILITY_INDEX_LIMIT);
                        $methodChecker->printIssues($filename);
                    }
                }

                foreach ($package['functions'] as $function) {
                    $filename = CacheManager::getOriginalFile(str_replace($this->currentDir . DIRECTORY_SEPARATOR, '', $function['filename']));
                    if ($this->ignoreList->shouldIgnore($filename)) {
                        continue;
                    }

                    $functionChecker = new MetricChecker($function, null, $function['name']);
                    $functionChecker->checkMaxCyclomaticComplexity(self::CYCLOMATIC_COMPLEXITY_LIMIT);
                    $functionChecker->checkMaxLinesOfCode(self::METHOD_LOC_LIMIT);
                    $functionChecker->checkMaxNpathComplexity(self::NPATH_COMPLEXITY_LIMIT);
                    $functionChecker->checkMaxHalsteadEffort(self::HALSTEAD_EFFORT_LIMIT);
                    $functionChecker->checkMinMaintainabilityIndex(self::MAINTAINABILITY_INDEX_LIMIT);

                    $loc = $function['loc'] ?? 0;
                    $filesChecked[$filename] = ($filesChecked[$filename] ?? 0) + $loc;
                    $functionChecker->printIssues($filename);
                }
            }

            foreach ($phpFiles as $phpFile) {
                $filename = str_replace($this->currentDir . DIRECTORY_SEPARATOR, '', $phpFile);
                if ($this->ignoreList->shouldIgnore($filename)) {
                    continue;
                }

                $locChecked = $filesChecked[$filename] ?? 0;
                if (!file_exists($phpFile)) {
                    continue;
                }

                $lines = file($phpFile);
                $totalLoc = $lines !== false ? count($lines) : 0;
                $otherLoc = $totalLoc - $locChecked;

                $fileChecker = new MetricChecker(['loc' => $otherLoc]);
                $fileChecker->checkMaxLinesOfCode(self::FILE_LOC_LIMIT);
                $fileChecker->printIssues($filename);
            }
        } catch (Exception $exception) {
            echo 'PDepend error: ' . $exception->getMessage() . PHP_EOL;
        }
    }
}
