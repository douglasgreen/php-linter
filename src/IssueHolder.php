<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter;

/**
 * Provides functionality to hold and manage a list of unique issues.
 * Uses static methods and properties to share issues across all classes.
 *
 * @package DouglasGreen\PhpLinter
 *
 * @since 1.0.0
 */
class IssueHolder
{
    /**
     * List of issues organized by file.
     * Structure: [file => [ [class, function, message, hint], ... ]]
     *
     * @var array<string, list<array{class: ?string, function: ?string, message: string, hint: string}>>
     */
    private static array $issues = [];

    /**
     * List of issues to ignore.
     *
     * @var list<string>
     */
    private static array $ignoreIssues = [];

    /** Current file context. */
    private static ?string $currentFile = null;

    /** Current class context. */
    private static ?string $currentClass = null;

    /** Current function context. */
    private static ?string $currentFunction = null;

    /**
     * Sets the current file context.
     */
    public static function setCurrentFile(?string $file): void
    {
        self::$currentFile = $file;
    }

    /**
     * Sets the current class context.
     */
    public static function setCurrentClass(?string $class): void
    {
        self::$currentClass = $class;
    }

    /**
     * Sets the current function context.
     */
    public static function setCurrentFunction(?string $function): void
    {
        self::$currentFunction = $function;
    }

    /**
     * Sets the list of issues to ignore globally.
     *
     * @param list<string> $ignoreIssues List of issue strings to ignore.
     */
    public static function setIgnoreIssues(array $ignoreIssues): void
    {
        self::$ignoreIssues = $ignoreIssues;
    }

    /**
     * Adds a single issue to the list.
     *
     * @param string $message The issue description.
     * @param string $hint Optional hint for resolving the issue.
     */
    public static function addIssue(string $message, string $hint = ''): void
    {
        // Check if this issue should be ignored
        foreach (self::$ignoreIssues as $ignorePattern) {
            if ($message === $ignorePattern) {
                return;
            }
        }

        $file = self::$currentFile ?? 'Unknown';
        
        if (!isset(self::$issues[$file])) {
            self::$issues[$file] = [];
        }

        self::$issues[$file][] = [
            'class' => self::$currentClass,
            'function' => self::$currentFunction,
            'message' => $message,
            'hint' => $hint,
        ];
    }

    /**
     * Adds multiple issues to the list.
     *
     * @param array<string, bool> $issues The issues to add (keys are messages).
     */
    public static function addIssues(array $issues): void
    {
        foreach (array_keys($issues) as $message) {
            self::addIssue($message);
        }
    }

    /**
     * Returns the list of issues in the old format (for backward compatibility).
     *
     * @return array<string, bool> The list of issues.
     */
    public static function getIssues(): array
    {
        $result = [];
        foreach (self::$issues as $file => $fileIssues) {
            foreach ($fileIssues as $issue) {
                $result[$issue['message']] = true;
            }
        }
        return $result;
    }

    /**
     * Checks if there are any issues.
     */
    public static function hasIssues(): bool
    {
        return self::$issues !== [];
    }

    /**
     * Clears all issues.
     */
    public static function clearIssues(): void
    {
        self::$issues = [];
        self::$currentFile = null;
        self::$currentClass = null;
        self::$currentFunction = null;
    }

    /**
     * Prints all issues organized by file.
     */
    public static function printIssues(): void
    {
        if (!self::hasIssues()) {
            return;
        }

        foreach (self::$issues as $file => $fileIssues) {
            echo PHP_EOL . '==> ' . $file . PHP_EOL;

            foreach ($fileIssues as $issue) {
                $name = self::formatContextName($issue['class'], $issue['function']);
                echo $name . ' - ' . $issue['message'] . PHP_EOL;
                
                if ($issue['hint'] !== '') {
                    echo '    Action: ' . $issue['hint'] . PHP_EOL;
                }
            }
        }

        self::clearIssues();
    }

    /**
     * Formats the context name (Class::function() or function() or File).
     */
    private static function formatContextName(?string $class, ?string $function): string
    {
        if ($class !== null) {
            $name = $class;
            if ($function !== null) {
                $name .= '::' . $function . '()';
            }
        } elseif ($function !== null) {
            $name = $function . '()';
        } else {
            $name = 'File';
        }

        return $name;
    }
}
