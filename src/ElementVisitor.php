<?php

namespace DouglasGreen\PhpLinter;

use PhpParser\Error;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;

class ElementVisitor extends NodeVisitorAbstract {
    public function enterNode(Node $node) {
        if ($node instanceof Node\Stmt\Namespace_) {
            echo 'Namespace: ' . $node->name . PHP_EOL;
        } elseif ($node instanceof Node\Stmt\Class_) {
            echo 'Class: ' . $node->name . PHP_EOL;
        } elseif ($node instanceof Node\Stmt\Interface_) {
            echo 'Interface: ' . $node->name . PHP_EOL;
        } elseif ($node instanceof Node\Stmt\Trait_) {
            echo 'Trait: ' . $node->name . PHP_EOL;
        } elseif ($node instanceof Node\Stmt\ClassMethod) {
            echo 'Method: ' . $node->name . PHP_EOL;
        } elseif ($node instanceof Node\Stmt\Function_) {
            echo 'Function: ' . $node->name . PHP_EOL;
        }
    }
}
