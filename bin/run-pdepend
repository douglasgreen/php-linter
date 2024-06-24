#!/usr/bin/env php
<?php

declare(strict_types=1);

// Ensure the var/cache/pdepend directory exists
$cacheDir = 'var/cache/pdepend';
if (! is_dir($cacheDir)) {
    mkdir($cacheDir, 0o777, true);
}

// Execute git ls-files to get a list of all files tracked by Git
exec('git ls-files', $gitFiles, $returnCode);
if ($returnCode !== 0) {
    die('Failed to get a list of Git files.' . PHP_EOL);
}

// Initialize an array to store top-level directories with PHP files
$phpPaths = [];

// Iterate over the list of Git files
foreach ($gitFiles as $gitFile) {
    if (pathinfo($gitFile, PATHINFO_EXTENSION) === 'php') {
        // Get the top-level directory of the PHP file
        $topLevelDir = explode('/', $gitFile)[0];
        // Store the top-level directory in the phpPaths array
        $phpPaths[$topLevelDir] = true;
    }
}

// Get the list of unique top-level directories
$files = array_keys($phpPaths);

// Join array elements with a comma
$fileList = implode(',', $files);

// Run PDepend with the file list
$command = sprintf(
    'vendor/bin/pdepend --summary-xml=%s/summary.xml %s',
    $cacheDir,
    escapeshellarg($fileList),
);

// Open process and capture stdout and stderr
$descriptors = [
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w'],
];

$process = proc_open($command, $descriptors, $pipes);

if (is_resource($process)) {
    // Read the output in real-time
    while (! feof($pipes[1])) {
        echo fgets($pipes[1]);
    }

    while (! feof($pipes[2])) {
        echo fgets($pipes[2]);
    }

    // Close the pipes
    fclose($pipes[1]);
    fclose($pipes[2]);

    // Close the process
    proc_close($process);
} else {
    echo 'Failed to execute the PDepend command.' . PHP_EOL;
}
