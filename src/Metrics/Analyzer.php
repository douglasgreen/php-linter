<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Metrics;

use Exception;

class Analyzer
{
    public const CLASS_SIZE_WARN = 25;

    public const CLASS_SIZE_ERROR = 60;

    public const CODE_RANK_WARN = 0.5;

    public const CODE_RANK_ERROR = 2.0;

    public const CLASS_LOC_WARN = 400;

    public const CLASS_LOC_ERROR = 1100;

    public const NON_PRIVATE_PROPS_WARN = 10;

    public const NON_PRIVATE_PROPS_ERROR = 30;

    public const PROPERTIES_WARN = 10;

    public const PROPERTIES_ERROR = 25;

    public const PUBLIC_METHODS_WARN = 15;

    public const PUBLIC_METHODS_ERROR = 40;

    public const AFFERENT_COUPLING_WARN = 15;

    public const AFFERENT_COUPLING_ERROR = 45;

    public const EFFERENT_COUPLING_WARN = 12;

    public const EFFERENT_COUPLING_ERROR = 24;

    public const INHERITANCE_DEPTH_WARN = 4;

    public const INHERITANCE_DEPTH_ERROR = 5;

    public const CHILD_CLASSES_WARN = 15;

    public const CHILD_CLASSES_ERROR = 35;

    public const OBJECT_COUPLING_WARN = 12;

    public const OBJECT_COUPLING_ERROR = 24;

    public const COMMENT_RATIO_WARN = 0.1;

    public const COMMENT_RATIO_ERROR = 0.05;

    public const CYCLOMATIC_COMPLEXITY_WARN = 10;

    public const CYCLOMATIC_COMPLEXITY_ERROR = 25;

    public const METHOD_LOC_WARN = 50;

    public const METHOD_LOC_ERROR = 130;

    public const NPATH_COMPLEXITY_WARN = 50;

    public const NPATH_COMPLEXITY_ERROR = 10000;

    public const HALSTEAD_EFFORT_WARN = 25000;

    public const HALSTEAD_EFFORT_ERROR = 135000;

    public const MAINTAINABILITY_INDEX_WARN = 40;

    public const MAINTAINABILITY_INDEX_ERROR = 25;

    public const FUNCTION_CYCLOMATIC_COMPLEXITY_WARN = 9;

    public const FILE_LOC_WARN = 100;

    public const FILE_LOC_ERROR = 200;

    public function __construct(
        protected readonly string $currentDir,
        protected readonly CacheManager $cache,
        protected readonly IgnoreList $ignoreList,
    ) {}

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
            if ($packages === null) {
                echo 'No packages found.' . PHP_EOL;
                return;
            }

            $filesChecked = [];
            foreach ($packages as $package) {
                foreach ($package['classes'] as $class) {
                    $filename = CacheManager::getOriginalFile(str_replace($this->currentDir . DIRECTORY_SEPARATOR, '', $class['filename']));
                    if ($this->ignoreList->shouldIgnore($filename)) {
                        continue;
                    }

                    $classChecker = new MetricChecker($class, $class['name']);
                    $classChecker->checkMaxClassSize(self::CLASS_SIZE_WARN, self::CLASS_SIZE_ERROR);
                    $classChecker->checkMaxCodeRank(self::CODE_RANK_WARN, self::CODE_RANK_ERROR);
                    $classChecker->checkMaxLinesOfCode(self::CLASS_LOC_WARN, self::CLASS_LOC_ERROR);
                    $classChecker->checkMaxNonPrivateProperties(self::NON_PRIVATE_PROPS_WARN, self::NON_PRIVATE_PROPS_ERROR);
                    $classChecker->checkMaxProperties(self::PROPERTIES_WARN, self::PROPERTIES_ERROR);
                    $classChecker->checkMaxPublicMethods(self::PUBLIC_METHODS_WARN, self::PUBLIC_METHODS_ERROR);
                    $classChecker->checkMaxAfferentCoupling(self::AFFERENT_COUPLING_WARN, self::AFFERENT_COUPLING_ERROR);
                    $classChecker->checkMaxEfferentCoupling(self::EFFERENT_COUPLING_WARN, self::EFFERENT_COUPLING_ERROR);
                    $classChecker->checkMaxInheritanceDepth(self::INHERITANCE_DEPTH_WARN, self::INHERITANCE_DEPTH_ERROR);
                    $classChecker->checkMaxNumberOfChildClasses(self::CHILD_CLASSES_WARN, self::CHILD_CLASSES_ERROR);
                    $classChecker->checkMaxObjectCoupling(self::OBJECT_COUPLING_WARN, self::OBJECT_COUPLING_ERROR);
                    $classChecker->checkMinCommentRatio(self::COMMENT_RATIO_WARN, self::COMMENT_RATIO_ERROR);

                    $loc = $class['loc'] ?? 0;
                    $filesChecked[$filename] = ($filesChecked[$filename] ?? 0) + $loc;
                    $classChecker->printIssues($filename);

                    foreach ($class['methods'] as $method) {
                        $methodChecker = new MetricChecker($method, $class['name'], $method['name']);
                        $methodChecker->checkMaxCyclomaticComplexity(self::CYCLOMATIC_COMPLEXITY_WARN, self::CYCLOMATIC_COMPLEXITY_ERROR);
                        $methodChecker->checkMaxLinesOfCode(self::METHOD_LOC_WARN, self::METHOD_LOC_ERROR);
                        $methodChecker->checkMaxNpathComplexity(self::NPATH_COMPLEXITY_WARN, self::NPATH_COMPLEXITY_ERROR);
                        $methodChecker->checkMaxHalsteadEffort(self::HALSTEAD_EFFORT_WARN, self::HALSTEAD_EFFORT_ERROR);
                        $methodChecker->checkMinMaintainabilityIndex(self::MAINTAINABILITY_INDEX_WARN, self::MAINTAINABILITY_INDEX_ERROR);
                        $methodChecker->printIssues($filename);
                    }
                }

                foreach ($package['functions'] as $function) {
                    $filename = CacheManager::getOriginalFile(str_replace($this->currentDir . DIRECTORY_SEPARATOR, '', $function['filename']));
                    if ($this->ignoreList->shouldIgnore($filename)) {
                        continue;
                    }

                    $functionChecker = new MetricChecker($function, null, $function['name']);
                    $functionChecker->checkMaxCyclomaticComplexity(self::FUNCTION_CYCLOMATIC_COMPLEXITY_WARN, self::CYCLOMATIC_COMPLEXITY_ERROR);
                    $functionChecker->checkMaxLinesOfCode(self::METHOD_LOC_WARN, self::METHOD_LOC_ERROR);
                    $functionChecker->checkMaxNpathComplexity(self::NPATH_COMPLEXITY_WARN, self::NPATH_COMPLEXITY_ERROR);
                    $functionChecker->checkMaxHalsteadEffort(self::HALSTEAD_EFFORT_WARN, self::HALSTEAD_EFFORT_ERROR);
                    $functionChecker->checkMinMaintainabilityIndex(self::MAINTAINABILITY_INDEX_WARN, self::MAINTAINABILITY_INDEX_ERROR);

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
                $fileChecker->checkMaxLinesOfCode(self::FILE_LOC_WARN, self::FILE_LOC_ERROR);
                $fileChecker->printIssues($filename);
            }
        } catch (Exception $exception) {
            echo 'PDepend error: ' . $exception->getMessage() . PHP_EOL;
        }
    }
}
