#!/usr/bin/env php
<?php

declare(strict_types=1);

use DouglasGreen\PhpLinter\PdependParser;
use DouglasGreen\Utility\FileSystem\DirUtil;

require_once __DIR__ . '/../vendor/autoload.php';

// Change to repository root directory.
DirUtil::setCurrent(__DIR__ . '/..');

echo 'Updating PDepend cache.' . PHP_EOL;

$cacheFiles = DirUtil::listFiles(PdependParser::FILE_DIR);

foreach ($cacheFiles as $cacheFile) {
    try {
        printf('Processing %s.' . PHP_EOL, $cacheFile);
        $parser = new PdependParser($cacheFile);
        $data = $parser->getData();
        //print_r($data);
    } catch (Exception $exception) {
        echo 'Error: ' . $exception->getMessage();
    }
}
