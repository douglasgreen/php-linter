<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\PhpDoc;

use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\PhpDocParser\ParserConfig;

/**
 * Factory for creating PHPDoc parser instances.
 *
 * @package DouglasGreen\PhpLinter\PhpDoc
 *
 * @internal
 *
 * @since 1.0.0
 */
class ParserFactory
{
    /**
     * Creates a configured Lexer instance.
     */
    public static function createLexer(): Lexer
    {
        $config = new ParserConfig([]);
        return new Lexer($config);
    }

    /**
     * Creates a configured PhpDocParser instance.
     */
    public static function createPhpDocParser(): PhpDocParser
    {
        $config = new ParserConfig([]);
        $constExprParser = new ConstExprParser($config);
        $typeParser = new TypeParser($config, $constExprParser);
        return new PhpDocParser($config, $typeParser, $constExprParser);
    }
}
