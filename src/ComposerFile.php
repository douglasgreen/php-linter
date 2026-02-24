<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter;

use Exception;

/**
 * Parses composer.json to extract PSR-4 autoloading mappings.
 *
 * @package DouglasGreen\PhpLinter
 * @since 1.0.0
 */
class ComposerFile
{
    /**
     * PSR-4 autoload mappings from composer.json.
     *
     * @var array<string, string|list<string>>
     */
    protected readonly array $psr4Mappings;

    /**
     * Constructs a new ComposerFile instance.
     *
     * @param string $composerJsonPath Path to the composer.json file.
     *
     * @throws Exception If the composer.json file cannot be loaded or parsed.
     */
    public function __construct(string $composerJsonPath)
    {
        $this->psr4Mappings = static::loadComposerJson($composerJsonPath);
    }

    /**
     * Converts a fully-qualified class name to a file name based on PSR-4 rules.
     *
     * @param string $className Fully-qualified class name.
     *
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
                            DIRECTORY_SEPARATOR,
                        ) . DIRECTORY_SEPARATOR . $relativePath;
                        return ltrim($fullPath, DIRECTORY_SEPARATOR);
                    }
                } else {
                    $fullPath = rtrim(
                        $paths,
                        DIRECTORY_SEPARATOR,
                    ) . DIRECTORY_SEPARATOR . $relativePath;
                    return ltrim($fullPath, DIRECTORY_SEPARATOR);
                }
            }
        }

        return null;
    }

    /**
     * Loads the composer.json file and extracts PSR-4 autoload mappings.
     *
     * @param string $composerJsonPath Path to the composer.json file.
     *
     * @return array<string, string|list<string>> The PSR-4 autoload mappings.
     *
     * @throws Exception If the file cannot be loaded or parsed.
     */
    protected static function loadComposerJson(string $composerJsonPath): array
    {
        $composerJson = file_get_contents($composerJsonPath);
        if ($composerJson === false) {
            throw new Exception('Unable to load file to string');
        }

        $composerData = json_decode($composerJson, true, 16, JSON_THROW_ON_ERROR);

        return $composerData['autoload']['psr-4'] ?? [];
    }
}
