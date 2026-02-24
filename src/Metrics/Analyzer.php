<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Metrics;

use Exception;

class Analyzer
{
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
                    $classChecker->checkMaxClassSize(25, 60);
                    $classChecker->checkMaxCodeRank(0.5, 2.0);
                    $classChecker->checkMaxLinesOfCode(400, 1100);
                    $classChecker->checkMaxNonPrivateProperties(10, 30);
                    $classChecker->checkMaxProperties(10, 25);
                    $classChecker->checkMaxPublicMethods(15, 40);
                    $classChecker->checkMaxAfferentCoupling(15, 45);
                    $classChecker->checkMaxEfferentCoupling(12, 24);
                    $classChecker->checkMaxInheritanceDepth(4, 5);
                    $classChecker->checkMaxNumberOfChildClasses(15, 35);
                    $classChecker->checkMaxObjectCoupling(12, 24);
                    $classChecker->checkMinCommentRatio(0.1, 0.05);

                    $loc = $class['loc'] ?? 0;
                    $filesChecked[$filename] = ($filesChecked[$filename] ?? 0) + $loc;
                    $classChecker->printIssues($filename);

                    foreach ($class['methods'] as $method) {
                        $methodChecker = new MetricChecker($method, $class['name'], $method['name']);
                        $methodChecker->checkMaxCyclomaticComplexity(10, 25);
                        $methodChecker->checkMaxLinesOfCode(50, 130);
                        $methodChecker->checkMaxNpathComplexity(50, 10000);
                        $methodChecker->checkMaxHalsteadEffort(25000, 135000);
                        $methodChecker->checkMinMaintainabilityIndex(40, 25);
                        $methodChecker->printIssues($filename);
                    }
                }

                foreach ($package['functions'] as $function) {
                    $filename = CacheManager::getOriginalFile(str_replace($this->currentDir . DIRECTORY_SEPARATOR, '', $function['filename']));
                    if ($this->ignoreList->shouldIgnore($filename)) {
                        continue;
                    }
                    $functionChecker = new MetricChecker($function, null, $function['name']);
                    $functionChecker->checkMaxCyclomaticComplexity(9, 25);
                    $functionChecker->checkMaxLinesOfCode(50, 130);
                    $functionChecker->checkMaxNpathComplexity(50, 10000);
                    $functionChecker->checkMaxHalsteadEffort(25000, 135000);
                    $functionChecker->checkMinMaintainabilityIndex(40, 25);

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
                $fileChecker->checkMaxLinesOfCode(100, 200);
                $fileChecker->printIssues($filename);
            }
        } catch (Exception $exception) {
            echo 'PDepend error: ' . $exception->getMessage() . PHP_EOL;
        }
    }
}
