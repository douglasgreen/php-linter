<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter;

use DouglasGreen\Utility\FileSystem\PathUtil;
use DouglasGreen\Utility\Program\Command;

class Repository
{
    /**
     * @var list<string>
     */
    protected readonly array $files;

    public function __construct()
    {
        $command = new Command('git ls-files');
        $this->files = $command->run();
    }

    /**
     * @return mixed[]
     */
    public function getPhpFiles(): array
    {
        $matches = [];
        foreach ($this->files as $file) {
            if (PathUtil::getFileType($file) === 'php') {
                $matches[] = $file;
            }
        }

        return $matches;
    }
}
