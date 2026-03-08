<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter;

/**
 * Provides functionality to hold and manage a list of unique issues.
 *
 * @api
 */
class IssueHolder
{
    /**
     * List of issues organized by file.
     * Structure: [file => [ [class, function, message, hint], ... ]]
     *
     * @var array<string, list<array{class: ?string, function: ?string, message: string, hint: string}>>
     */
    private array $issues = [];

    /**
     * List of issues to ignore.
     *
     * @var list<string>
     */
    private array $ignoreIssues = [];

    /** Current file context. */
    private ?string $currentFile = null;

    /** Current class context. */
    private ?string $currentClass = null;

    /** Current function context. */
    private ?string $currentFunction = null;

    /**
     * Sets the current file context.
     */
    public function setCurrentFile(?string $file): void
    {
        $this->currentFile = $file;
    }

    /**
     * Sets the current class context.
     */
    public function setCurrentClass(?string $class): void
    {
        $this->currentClass = $class;
    }

    /**
     * Sets the current function context.
     */
    public function setCurrentFunction(?string $function): void
    {
        $this->currentFunction = $function;
    }

    /**
     * Sets the list of issues to ignore.
     *
     * @param list<string> $ignoreIssues List of issue strings to ignore.
     */
    public function setIgnoreIssues(array $ignoreIssues): void
    {
        $this->ignoreIssues = $ignoreIssues;
    }

    /**
     * Adds a single issue to the list.
     *
     * @param string $message The issue description.
     * @param string $hint Optional hint for resolving the issue.
     */
    public function addIssue(string $message, string $hint = ''): void
    {
        // Check if this issue should be ignored
        foreach ($this->ignoreIssues as $ignorePattern) {
            if ($message === $ignorePattern) {
                return;
            }
        }

        $file = $this->currentFile ?? 'Unknown';

        if (!isset($this->issues[$file])) {
            $this->issues[$file] = [];
        }

        $this->issues[$file][] = [
            'class' => $this->currentClass,
            'function' => $this->currentFunction,
            'message' => $message,
            'hint' => $hint,
        ];
    }

    /**
     * Adds multiple issues to the list.
     *
     * @param array<string, bool> $issues The issues to add (keys are messages).
     */
    public function addIssues(array $issues): void
    {
        foreach (array_keys($issues) as $message) {
            $this->addIssue($message);
        }
    }

    /**
     * Returns the list of issues in the old format (for backward compatibility).
     *
     * @return array<string, bool> The list of issues.
     */
    public function getIssues(): array
    {
        $result = [];
        foreach ($this->issues as $fileIssues) {
            foreach ($fileIssues as $issue) {
                $result[$issue['message']] = true;
            }
        }

        return $result;
    }

    /**
     * Checks if there are any issues.
     */
    public function hasIssues(): bool
    {
        return $this->issues !== [];
    }

    /**
     * Clears all issues.
     */
    public function clearIssues(): void
    {
        $this->issues = [];
        $this->currentFile = null;
        $this->currentClass = null;
        $this->currentFunction = null;
    }

    /**
     * Prints all issues organized by file.
     */
    public function printIssues(): void
    {
        if (!$this->hasIssues()) {
            return;
        }

        foreach ($this->issues as $file => $fileIssues) {
            echo PHP_EOL . '==> ' . $file . PHP_EOL;

            foreach ($fileIssues as $issue) {
                $name = $this->formatContextName($issue['class'], $issue['function']);
                echo $name . ' - ' . $issue['message'] . PHP_EOL;

                if ($issue['hint'] !== '') {
                    echo '    Action: ' . $issue['hint'] . PHP_EOL;
                }
            }
        }

        $this->clearIssues();
    }

    /**
     * Formats the context name (Class::function() or function() or File).
     */
    private function formatContextName(?string $class, ?string $function): string
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
