#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use DouglasGreen\PhpLinter\ElementVisitor;
use DouglasGreen\PhpLinter\PdependClass;
use DouglasGreen\PhpLinter\PdependParser;
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

echo '=> Checking PDepend metrics' . PHP_EOL;

try {
    $parser = new PdependParser();
    $packages = $parser->getPackages();
    if ($packages === null) {
        die('No packages found.' . PHP_EOL);
    }

    foreach ($packages as $package) {
        if (! isset($package['classes'])) {
            continue;
        }

        $classes = $package['classes'];
        foreach ($classes as $class) {
            $pdependClass = new PdependClass($class);
            $pdependClass->checkMaxLinesOfCode(100);
            $pdependClass->checkMinCommentRatio(0.1);
            $pdependClass->checkMaxClassSize(2);
            $pdependClass->checkMaxPublicMethods(1);
            $pdependClass->checkMaxProperties(1);
            $pdependClass->checkMaxNonPrivateProperties(1);
        }
    }
} catch (Exception $exception) {
    echo 'Error: ' . $exception->getMessage();
}

$parser = (new ParserFactory())->createForNewestSupportedVersion();

$path = new Path();
$path->addSubpath('php_paths');
if (! $path->exists()) {
    die('PHP path file not found in current directory' . PHP_EOL);
}

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
