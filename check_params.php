<?php

declare(strict_types=1);

/*
 * @todo Use this method instead of visitor.
 */

require __DIR__ . '/vendor/autoload.php';

use PhpParser\Error;
use PhpParser\Node\Stmt\Function_;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;

$code = <<<'CODE'
    <?php

    function foo($a, $b) {
        return $a + $b;
    }

    function bar($x) {
        return $x * 2;
    }

    function baz() {
        return 42;
    }
    CODE;

// Create a parser
$parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);

try {
    // Parse the code into an AST
    $ast = $parser->parse($code);
    if ($ast === null) {
        die('Parser error' . PHP_EOL);
    }
} catch (Error $error) {
    die(sprintf('Parse error: %s%s', $error->getMessage(), PHP_EOL));
}

// Create a NodeFinder instance
$nodeFinder = new NodeFinder();

// Find all function nodes
$functions = $nodeFinder->findInstanceOf($ast, Function_::class);

// Iterate over the functions and count their parameters
foreach ($functions as $function) {
    if (! $function instanceof Function_) {
        continue;
    }

    $functionName = $function->name->name;
    $paramCount = count($function->params);
    echo "Function '{$functionName}' has {$paramCount} parameter(s).\n";
}
