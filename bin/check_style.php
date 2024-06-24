#!/usr/bin/env php
<?php

declare(strict_types=1);

use DouglasGreen\PhpLinter\ElementVisitor;
use DouglasGreen\PhpLinter\Repository;
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

$currentPath = new Path($currentDir);

$filesChecked = [];

$repository = new Repository();
$phpFiles = $repository->getFilesByExtension('php');

$phpPath = new Path();
$phpPath->addSubpath('php_paths');
if (! $phpPath->exists()) {
    die('=> Skipping lint checks (PHP path file not found)' . PHP_EOL);
}

$parser = (new ParserFactory())->createForNewestSupportedVersion();

$phpPaths = $phpPath->loadLines(Path::IGNORE_NEW_LINES);
foreach ($phpPaths as $phpPath) {
    $files = DirUtil::listFiles($phpPath);
    foreach ($files as $file) {
        try {
            $code = PathUtil::loadString($file);
            $stmts = $parser->parse($code);
            if ($stmts === null) {
                echo 'No statements found in file.' . PHP_EOL;
                continue;
            }

            $traverser = new NodeTraverser();
            $visitor = new ElementVisitor();
            $traverser->addVisitor($visitor);
            $traverser->traverse($stmts);
            $visitor->printIssues($file);
        } catch (Error $e) {
            echo 'Parse Error: ', $e->getMessage();
        }
    }
}
