<?php

/*
 * @todo Use this method instead of visitor.
 */

require 'vendor/autoload.php';

use PhpParser\Error;
use PhpParser\Node;
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
} catch (Error $error) {
    echo "Parse error: {$error->getMessage()}\n";
    return;
}

// Create a NodeFinder instance
$nodeFinder = new NodeFinder();

// Find all function nodes
$functions = $nodeFinder->findInstanceOf($ast, Function_::class);

// Iterate over the functions and count their parameters
foreach ($functions as $function) {
    $functionName = $function->name->name;
    $paramCount = count($function->params);
    echo "Function '$functionName' has $paramCount parameter(s).\n";
}
