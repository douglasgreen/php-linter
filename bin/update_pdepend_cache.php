#!/usr/bin/env php
<?php

declare(strict_types=1);

use DouglasGreen\Utility\FileSystem\DirUtil;
use DouglasGreen\Utility\FileSystem\PathUtil;
use DouglasGreen\Utility\Program\Command;

require_once __DIR__ . '/../vendor/autoload.php';

// Change to repository root directory.
DirUtil::setCurrent(__DIR__ . '/..');

// Execute the 'git ls-files' command to get a list of all PHP files in the repository
$command = new Command('git ls-files');
$output = $command->run();

// Define the base directory for the PDepend XML summaries
$baseDir = 'var/cache/pdepend/files/';

echo 'Updating PDepend cache.' . PHP_EOL;

$oldFiles = DirUtil::listFiles($baseDir);

// Iterate through each file from the git output
foreach ($output as $file) {
    if (pathinfo($file, PATHINFO_EXTENSION) !== 'php') {
        continue;
    }

    // Define the output XML file path
    $xmlFilePath = PathUtil::addSubpath($baseDir, $file . '.xml');
    $xmlFileDir = dirname($xmlFilePath);

    // Create the directory for the XML file if it doesn't exist
    if (! file_exists($xmlFileDir)) {
        DirUtil::makeRecursive($xmlFileDir);
    }

    // Run the PDepend command
    $command = new Command('vendor/bin/pdepend');
    $command->addFlag('--summary-xml', $xmlFilePath);
    $command->addArg($file);
    $command->run();
    echo $xmlFilePath . ': ' . $command->buildCommand() . PHP_EOL;

    echo 'Processed: ' . $file . PHP_EOL;
}

echo 'PDepend cache updated.' . PHP_EOL;
