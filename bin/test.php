#!/usr/bin/env php
<?php

declare(strict_types=1);

use PhpParser\Error;
use PhpParser\NodeDumper;
use PhpParser\ParserFactory;

require __DIR__ . '/../vendor/autoload.php';

$code = <<<'CODE'
    <?php

    namespace ABC;

    function test($foo)
    {
        var_dump($foo);
    }
    CODE;

$parser = (new ParserFactory())->createForNewestSupportedVersion();
try {
    $ast = $parser->parse($code);
    $dumper = new NodeDumper();
    if ($ast !== null) {
        echo $dumper->dump($ast) . PHP_EOL;
    }
} catch (Error $error) {
    echo sprintf('Parse error: %s%s', $error->getMessage(), PHP_EOL);
}
