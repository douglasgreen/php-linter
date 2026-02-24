<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Metrics;

use Exception;

class Analyzer
{
    public const CLASS_SIZE_LIMIT = 60;

    public const CODE_RANK_LIMIT = 2.0;

    public const CLASS_LOC_LIMIT = 1100;

    public const NON_PRIVATE_PROPS_LIMIT = 30;

    public const PROPERTIES_LIMIT = 25;

    public const PUBLIC_METHODS_LIMIT = 40;

    public const AFFERENT_COUPLING_LIMIT = 45;

    public const EFFERENT_COUPLING_LIMIT = 24;

    public const INHERITANCE_DEPTH_LIMIT = 5;

    public const CHILD_CLASSES_LIMIT = 35;

    public const OBJECT_COUPLING_LIMIT = 24;

    public const COMMENT_RATIO_LIMIT = 0.05;

    public const CYCLOMATIC_COMPLEXITY_LIMIT = 25;

    public const METHOD_LOC_LIMIT = 130;

    public const NPATH_COMPLEXITY_LIMIT = 10000;

    public const HALSTEAD_EFFORT_LIMIT = 135000;

    public const MAINTAINABILITY_INDEX_LIMIT = 25;

    public const FILE_LOC_LIMIT = 200;

    public function __construct(
        protected readonly string $currentDir,
        protected readonly CacheManager $cache,
        protected readonly IgnoreList $ignoreList,
    ) {}

    /**
     * @param list<string> $phpFiles
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
                    $classChecker->checkMinCommentRatio(self::COMMENT_RATIO_LIMIT);

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
