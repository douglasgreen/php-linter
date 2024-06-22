#!/usr/bin/env php
<?php

declare(strict_types=1);

use DouglasGreen\PhpLinter\PDependParser;

require __DIR__ . '/../vendor/autoload.php';

// Usage example:
try {
    $parser = new PDependParser(__DIR__ . '/../var/pdepend/summary.xml');
    $parser->parse();
    $data = $parser->getData();
    print_r($data);
} catch (Exception $exception) {
    echo 'Error: ' . $exception->getMessage();
}
