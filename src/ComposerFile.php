<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter;

use DouglasGreen\Utility\FileSystem\PathUtil;

class ComposerFile
{
    /**
     * Array to store the PSR-4 autoload mappings.
     *
     * @var array<string, string|list<string>>
     */
    protected readonly array $psr4Mappings;

    public function __construct(string $composerJsonPath)
    {
        $this->psr4Mappings = $this->loadComposerJson($composerJsonPath);
    }

    /**
     * Convert a fully-qualified class name to a file name.
     *
     * @param string $className Fully-qualified class name.
     * @return string|null Corresponding file name or null if no matching PSR-4 namespace is found.
     */
    public function convertClassNameToFileName(string $className): ?string
    {
        foreach ($this->psr4Mappings as $namespace => $paths) {
            if (str_starts_with($className, $namespace)) {
                $relativeClass = substr($className, strlen($namespace));
                $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

                if (is_array($paths)) {
                    foreach ($paths as $path) {
                        $fullPath = rtrim(
                            $path,
                            DIRECTORY_SEPARATOR
                        ) . DIRECTORY_SEPARATOR . $relativePath;
                        return ltrim($fullPath, DIRECTORY_SEPARATOR);
                    }
                } else {
                    $fullPath = rtrim(
                        $paths,
                        DIRECTORY_SEPARATOR
                    ) . DIRECTORY_SEPARATOR . $relativePath;
                    return ltrim($fullPath, DIRECTORY_SEPARATOR);
                }
            }
        }

        return null;
    }

    /**
     * Load the composer.json file and extract PSR-4 autoload mappings.
     *
     * @return array<string, string|list<string>>
     */
    protected function loadComposerJson(string $composerJsonPath): array
    {
        $composerJson = PathUtil::loadString($composerJsonPath);
        $composerData = json_decode($composerJson, true, 16, JSON_THROW_ON_ERROR);

        return $composerData['autoload']['psr-4'] ?? [];
    }
}
