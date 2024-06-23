#!/usr/bin/env php
<?php

declare(strict_types=1);

use DouglasGreen\PhpLinter\ElementVisitor;
use DouglasGreen\PhpLinter\Pdepend\MetricChecker;
use DouglasGreen\PhpLinter\Pdepend\XmlParser;
use DouglasGreen\Utility\FileSystem\DirUtil;
use DouglasGreen\Utility\FileSystem\Path;
use DouglasGreen\Utility\FileSystem\PathUtil;
use PhpParser\Error;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;

// Must be run in repository root directory.
$currentDir = getcwd();
if ($currentDir === false) {
    throw new Exception('Unable to get working dir');
}

require_once $currentDir . '/vendor/autoload.php';

$path = new Path();
$path->addSubpath(XmlParser::SUMMARY_FILE);
$prevFilename = null;
if ($path->exists()) {
    echo '=> Checking PDepend metrics' . PHP_EOL;

    try {
        $parser = new XmlParser((string) $path);
        $packages = $parser->getPackages();
        if ($packages === null) {
            die('No packages found.' . PHP_EOL);
        }

        foreach ($packages as $package) {
            $classes = $package['classes'];
            foreach ($classes as $class) {
                // Warnings exceed 95% of similar code, errors exceed 99%.
                $classChecker = new MetricChecker($class);

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

                if ($classChecker->hasIssues()) {
                    if ($prevFilename !== $class['filename']) {
                        echo PHP_EOL . '==> ' . $class['filename'] . PHP_EOL;
                        $prevFilename = $class['filename'];
                    }

                    $classChecker->printIssues();
                }

                foreach ($class['methods'] as $method) {
                    $methodChecker = new MetricChecker($method, $class['name']);
                    $methodChecker->checkMaxExtendedCyclomaticComplexity(9, 25);
                    $methodChecker->checkMaxLinesOfCode(50, 130);
                    $methodChecker->checkMaxNpathComplexity(50, 10000);
                    $methodChecker->checkMaxHalsteadEffort(25000, 135000);
                    $methodChecker->checkMinMaintainabilityIndex(40, 25);

                    if ($methodChecker->hasIssues()) {
                        if ($prevFilename !== $class['filename']) {
                            echo PHP_EOL . '==> ' . $class['filename'] . PHP_EOL;
                            $prevFilename = $class['filename'];
                        }

                        $methodChecker->printIssues();
                    }
                }
            }

            foreach ($package['functions'] as $function) {
                $functionChecker = new MetricChecker($function);
                $functionChecker->checkMaxExtendedCyclomaticComplexity(9, 25);
                $functionChecker->checkMaxLinesOfCode(50, 130);
                $functionChecker->checkMaxNpathComplexity(50, 10000);
                $functionChecker->checkMaxHalsteadEffort(25000, 135000);
                $functionChecker->checkMinMaintainabilityIndex(40, 25);

                if ($functionChecker->hasIssues()) {
                    if ($prevFilename !== $function['filename']) {
                        echo PHP_EOL . '==> ' . $function['filename'] . PHP_EOL;
                        $prevFilename = $function['filename'];
                    }

                    $functionChecker->printIssues();
                }
            }
        }
    } catch (Exception $exception) {
        echo 'PDepend error: ' . $exception->getMessage() . PHP_EOL;
    }
} else {
    echo '=> Skipping metrics checks (PDepend summary XML file not found)' . PHP_EOL;
}

$path = new Path();
$path->addSubpath('php_paths');
if ($path->exists()) {
    $parser = (new ParserFactory())->createForNewestSupportedVersion();

    echo PHP_EOL . '=> Performing lint checks' . PHP_EOL;

    $phpPaths = $path->loadLines(Path::IGNORE_NEW_LINES);
    foreach ($phpPaths as $phpPath) {
        $files = DirUtil::listFiles($phpPath);
        foreach ($files as $file) {
            echo '==> ' . $file . PHP_EOL;
            try {
                $code = PathUtil::loadString($file);
                $stmts = $parser->parse($code);
                if ($stmts === null) {
                    echo 'No statements found in file.' . PHP_EOL;
                    continue;
                }

                $traverser = new NodeTraverser();
                $traverser->addVisitor(new ElementVisitor());
                $traverser->traverse($stmts);
            } catch (Error $e) {
                echo 'Parse Error: ', $e->getMessage();
            }
        }
    }
} else {
    echo '=> Skipping lint checks (PHP path file not found)' . PHP_EOL;
}
