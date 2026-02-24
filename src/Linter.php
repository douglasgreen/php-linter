<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter;

use Exception;
use PhpParser\Error;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;

/**
 * Orchestrates the linting process for a list of PHP files.
 *
 * @package DouglasGreen\PhpLinter
 *
 * @since 1.0.0
 */
class Linter
{
    /**
     * Constructs a new Linter instance.
     *
     * @param ComposerFile $composerFile The composer file handler.
     * @param IgnoreList $ignoreList The ignore list handler.
     */
    public function __construct(
        protected readonly ComposerFile $composerFile,
        protected readonly IgnoreList $ignoreList,
    ) {}

    /**
     * Runs the linter on the provided list of PHP files.
     *
     * @param list<string> $phpFiles The list of PHP file paths to lint.
     */
    public function run(array $phpFiles): void
    {
        $parser = (new ParserFactory())->createForNewestSupportedVersion();

        foreach ($phpFiles as $phpFile) {
            if ($this->ignoreList->shouldIgnore($phpFile)) {
                continue;
            }

            try {
                $code = file_get_contents($phpFile);
                if ($code === false) {
                    throw new Exception('Unable to load file to string');
                }

                $stmts = $parser->parse($code);
                if ($stmts === null) {
                    echo 'No statements found in file.' . PHP_EOL;
                    continue;
                }

                $traverser = new NodeTraverser();
                $visitor = new ElementVisitor($this->composerFile, $phpFile);
                $traverser->addVisitor($visitor);
                $traverser->traverse($stmts);
                $visitor->printIssues($phpFile);
            } catch (Error $error) {
                echo 'Parse Error: ', $error->getMessage();
            }
        }
    }
}
