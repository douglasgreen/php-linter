<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Metrics;

class MetricGenerator
{
    /**
     * @param list<string> $phpFiles
     */
    public function __construct(
        protected readonly CacheManager $cacheManager,
        protected readonly array $phpFiles,
    ) {}

    public function generate(): void
    {
        $summaryCacheDir = $this->cacheManager->getCacheDir();
        $fileCacheDir = $this->cacheManager->getFileCacheDir();

        $phpDirs = [];

        // PDepend doesn't recognize files without .php extension so we must copy them to the cache.
        foreach ($this->phpFiles as $file) {
            if (str_ends_with((string) $file, '.php')) {
                // Get the top-level directory name
                $topLevelDir = explode('/', (string) $file)[0];
                $phpDirs[$topLevelDir] = true;
            } else {
                // Copy the file to file cache directory with .php extension
                $newFile = $file . '.php';
                $this->cacheManager->copyFile($file, $newFile);

                // Add file cache directory to $phpDirs
                $phpDirs[$fileCacheDir] = true;
            }
        }

        // Remove duplicate directories
        $phpDirs = array_keys($phpDirs);

        // Join array elements with a comma
        $dirList = implode(',', $phpDirs);

        // Run PDepend with the file list
        $command = sprintf(
            'vendor/bin/pdepend --summary-xml=%s/summary.xml %s',
            $summaryCacheDir,
            escapeshellarg($dirList),
        );

        // Open process and capture stdout and stderr
        $descriptors = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptors, $pipes);

        if (is_resource($process)) {
            // Read the output in real-time
            while (! feof($pipes[1])) {
                echo fgets($pipes[1]);
            }

            while (! feof($pipes[2])) {
                echo fgets($pipes[2]);
            }

            // Close the pipes
            fclose($pipes[1]);
            fclose($pipes[2]);

            // Close the process
            proc_close($process);
        } else {
            echo 'Failed to execute the PDepend command.' . PHP_EOL;
        }
    }
}
