<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter;

use Exception;
use PhpParser\Error;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;

class Linter
{
    public function __construct(
        protected readonly ComposerFile $composerFile,
        protected readonly IgnoreList $ignoreList,
    ) {}

    /**
     * @param list<string> $phpFiles
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
