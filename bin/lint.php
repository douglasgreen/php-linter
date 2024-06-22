#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use DouglasGreen\PhpLinter\ElementVisitor;
use DouglasGreen\PhpLinter\PdependParser;
use DouglasGreen\Utility\FileSystem\DirUtil;
use DouglasGreen\Utility\FileSystem\Path;
use DouglasGreen\Utility\FileSystem\PathUtil;
use PhpParser\Error;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;

require_once __DIR__ . '/../vendor/autoload.php';

// Change to repository root directory.
DirUtil::setCurrent(__DIR__ . '/..');

try {
    $parser = new PdependParser();
    $data = $parser->getData();
    print_r($data);
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
        echo '===> ' . $file . PHP_EOL;
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
