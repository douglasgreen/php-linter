#!/usr/bin/env php
<?php

declare(strict_types=1);

use DouglasGreen\PhpLinter\Pdepend\MetricChecker;
use DouglasGreen\PhpLinter\Pdepend\XmlParser;
use DouglasGreen\PhpLinter\Repository;
use DouglasGreen\Utility\FileSystem\Path;
use DouglasGreen\Utility\FileSystem\PathUtil;

// Must be run in repository root directory.
$currentDir = getcwd();
if ($currentDir === false) {
    throw new Exception('Unable to get working dir');
}

require_once $currentDir . '/vendor/autoload.php';

$currentPath = new Path($currentDir);

$filesChecked = [];

$repository = new Repository();
$phpFiles = $repository->getFilesByExtension('php');

$xmlPath = new Path();
$xmlPath->addSubpath(XmlParser::SUMMARY_FILE);
if (! $xmlPath->exists()) {
    die('=> Skipping metrics checks (PDepend summary XML file not found)' . PHP_EOL);
}

try {
    $parser = new XmlParser((string) $xmlPath);
    $packages = $parser->getPackages();
    if ($packages === null) {
        die('No packages found.' . PHP_EOL);
    }

    foreach ($packages as $package) {
        $classes = $package['classes'];
        foreach ($classes as $class) {
            // Warnings exceed 95% of similar code, errors exceed 99%.
            $classChecker = new MetricChecker($class, $class['name']);

            $classChecker->checkMaxClassSize(24, 56);

            // Has no error level.
            $classChecker->checkMaxCodeRank(0.55);

            $classChecker->checkMaxLinesOfCode(420, 1140);

            $classChecker->checkMaxNonPrivateProperties(1, 5);
            $classChecker->checkMaxProperties(7, 17);
            $classChecker->checkMaxPublicMethods(14, 35);

            $classChecker->checkMaxAfferentCoupling(7, 24);
            $classChecker->checkMaxEfferentCoupling(10, 19);

            // @see https://everything2.com/title/comment-to-code+ratio
            $classChecker->checkMinCommentRatio(0.1, 0.05);

            $filename = $currentPath->getSubpath($class['filename']);
            $filesChecked[$filename] = true;
            $classChecker->printIssues($filename);

            foreach ($class['methods'] as $method) {
                $methodChecker = new MetricChecker($method, $class['name'], $method['name']);
                $methodChecker->checkMaxExtendedCyclomaticComplexity(9, 25);
                $methodChecker->checkMaxLinesOfCode(50, 130);
                $methodChecker->checkMaxNpathComplexity(50, 10000);
                $methodChecker->checkMaxHalsteadEffort(25000, 135000);
                $methodChecker->checkMinMaintainabilityIndex(40, 25);

                $methodChecker->printIssues($filename);
            }
        }

        foreach ($package['functions'] as $function) {
            $functionChecker = new MetricChecker($function, null, $function['name']);
            $functionChecker->checkMaxExtendedCyclomaticComplexity(9, 25);
            $functionChecker->checkMaxLinesOfCode(50, 130);
            $functionChecker->checkMaxNpathComplexity(50, 10000);
            $functionChecker->checkMaxHalsteadEffort(25000, 135000);
            $functionChecker->checkMinMaintainabilityIndex(40, 25);

            $filename = $currentPath->getSubpath($function['filename']);
            $filesChecked[$filename] = true;
            $functionChecker->printIssues($filename);
        }
    }

    // Check other files that don't contain classes or functions.
    foreach ($phpFiles as $phpFile) {
        $filename = $currentPath->getSubpath($phpFile);
        if (! isset($filesChecked[$filename])) {
            if (! file_exists($filename)) {
                continue;
            }

            $loc = count(PathUtil::loadLines($phpFile));
            $fileChecker = new MetricChecker([
                'loc' => $loc,
            ]);
            $fileChecker->checkMaxLinesOfCode(100, 200);
            $filesChecked[$filename] = true;
            $fileChecker->printIssues($filename);
        }
    }
} catch (Exception $exception) {
    echo 'PDepend error: ' . $exception->getMessage() . PHP_EOL;
}
