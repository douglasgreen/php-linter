<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter;

use DouglasGreen\Utility\Program\Command;
use DouglasGreen\Utility\Regex\Regex;

class Repository
{
    /**
     * @var list<string>
     */
    protected array $files;

    public function __construct()
    {
        $command = new Command('git ls-files');
        $this->files = $command->run();
    }

    /**
     * @return mixed[]
     */
    public function getFilesByExtension(string $ext): array
    {
        $matches = [];
        foreach ($this->files as $file) {
            if (Regex::hasMatch('/\.' . $ext . '$/i', $file)) {
                $matches[] = $file;
            }
        }

        return $matches;
    }
}
