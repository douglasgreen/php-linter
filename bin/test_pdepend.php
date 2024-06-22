#!/usr/bin/env php
<?php

declare(strict_types=1);

use DouglasGreen\PhpLinter\PdependParser;

require __DIR__ . '/../vendor/autoload.php';

// Usage example:
try {
    $parser = new PdependParser(__DIR__ . '/../var/pdepend/summary.xml');
    $data = $parser->getData();
    print_r($data);
} catch (Exception $exception) {
    echo 'Error: ' . $exception->getMessage();
}
