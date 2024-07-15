<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Nikic\Checker;

use DouglasGreen\Utility\Regex\Regex;
use PhpParser\Comment;
use PhpParser\Comment\Doc;

/**
 * @todo Rework this so it doesn't match emails.
 */
class CommentChecker extends NodeChecker
{
    /**
     * @see https://github.com/php-fig/fig-standards/blob/master/proposed/phpdoc-tags.md
     * @var list<string>
     */
    protected const PHPDOC_TAGS = [
        '@api',
        '@author',
        '@copyright',
        '@deprecated',
        '@generated',
        '@internal',
        '@link',
        '@method',
        '@package',
        '@param',
        '@property',
        '@return',
        '@see',
        '@since',
        '@throws',
        '@todo',
        '@uses',
        '@var',
        '@version',
    ];

    /**
     * @return array<string, bool>
     */
    public function check(): array
    {
        // Check for comments attached to this node
        $comments = $this->node->getComments();
        foreach ($comments as $comment) {
            $this->checkComment($comment);
        }

        // Check for doc comment
        $docComment = $this->node->getDocComment();
        if ($docComment !== null) {
            $this->checkDocComment($docComment);
        }

        return $this->getIssues();
    }

    /**
     * @return list<string>
     */
    protected static function getPhpdocTags(string $text): array
    {
        $matches = Regex::matchAll('/@\w+(-\w+)*/', $text);
        if ($matches !== []) {
            return $matches[0];
        }

        return [];
    }

    protected static function isSingleLineComment(Comment $comment): bool
    {
        // Single-line comments start with '//' and don't contain newlines
        return str_starts_with(trim($comment->getText()), '//')
            && (! str_contains($comment->getText(), "\n"));
    }

    /**
     * @return array{description: string, tags: array<string, list<string>>}
     */
    protected static function parseDocblock(string $docblock): array
    {
        // Remove leading and trailing whitespace
        $docblock = trim($docblock);

        // Remove the opening and closing comment delimiters
        $docblock = preg_replace('/^\/\*\*?|\*\/$/', '', $docblock);

        // Split the docblock into lines
        $lines = explode("\n", (string) $docblock);

        // Strip asterisks and slashes from the beginning of each line
        $lines = array_map(
            fn($line): string => Regex::replace('/^\s*\*\s?/', '', $line),
            $lines
        );

        $tags = [];
        $currentTag = null;
        $description = '';

        foreach ($lines as $line) {
            $line = trim((string) $line);

            if (preg_match('/^(@\w+)\s*(.*)/', $line, $matches)) {
                // Start of a new tag
                if ($currentTag !== null) {
                    $tags[$currentTag['name']][] = trim($currentTag['text']);
                }

                $currentTag = [
                    'name' => $matches[1],
                    'text' => $matches[2],
                ];
            } elseif ($currentTag !== null) {
                // Continuation of the current tag
                $currentTag['text'] .= ' ' . $line;
            } elseif ($line !== '') {
                // Part of the description
                $description .= $line . ' ';
            }
        }

        // Add the last tag if there is one
        if ($currentTag !== null) {
            $tags[$currentTag['name']][] = trim($currentTag['text']);
        }

        return [
            'description' => trim($description),
            'tags' => $tags,
        ];
    }

    protected function checkComment(Comment $comment): void
    {
        if ($comment instanceof Doc) {
            // This is a doc comment (/** */)
            $this->checkDocComment($comment);
        } elseif (self::isSingleLineComment($comment)) {
            // This is a single-line comment (//)
            $this->checkSingleLineComment($comment);
        } else {
            // This is a multi-line comment (/* */)
            $this->checkMultiLineComment($comment);
        }

        // @todo See if I can improve this check
        if ($comment->getStartLine() === $this->node->getEndLine()) {
            $this->addIssue('Avoid putting comments at end of line');
        }
    }

    protected function checkDocComment(Doc $doc): void
    {
        //echo "Doc Comment found: " . $comment->getText() . "\n";
        //echo "Attached to node of type: " . get_class($this->node) . "\n\n";
        $text = $doc->getText();
        $tags = self::getPhpdocTags($text);
        foreach ($tags as $tag) {
            $lowTag = strtolower($tag);
            if (! in_array($lowTag, self::PHPDOC_TAGS, true)) {
                $this->addIssue('Invalid PHPDoc tag found in docblock: ' . $tag);
            }

            $data = self::parseDocblock($text);

            if (isset($data['tags']['@todo'])) {
                foreach ($data['tags']['@todo'] as $todo) {
                    $this->addIssue('Note: @todo ' . $todo);
                }
            }
        }
    }

    protected function checkMultiLineComment(Comment $comment): void
    {
        //echo "Multi-line Comment found: " . $comment->getText() . "\n";
        //echo "Attached to node of type: " . get_class($this->node) . "\n\n";
        $text = $comment->getText();
        $tags = self::getPhpdocTags($text);
        foreach ($tags as $tag) {
            $lowTag = strtolower($tag);
            if (in_array($lowTag, self::PHPDOC_TAGS, true)) {
                $this->addIssue(
                    'PHPDoc tag found in multi-line comment instead of dockblock: ' . $tag
                );
            }

            $data = self::parseDocblock($text);

            if (isset($data['tags']['@todo'])) {
                foreach ($data['tags']['@todo'] as $todo) {
                    $this->addIssue('Note: @todo ' . $todo);
                }
            }
        }
    }

    protected function checkSingleLineComment(Comment $comment): void
    {
        //echo "Single-line Comment found: " . $comment->getText() . "\n";
        //echo "Attached to node of type: " . get_class($this->node) . "\n\n";
        $text = $comment->getText();
        $tags = self::getPhpdocTags($text);
        foreach ($tags as $tag) {
            $lowTag = strtolower($tag);
            if (in_array($lowTag, self::PHPDOC_TAGS, true)) {
                $this->addIssue(
                    'PHPDoc tag found in single-line comment instead of dockblock: ' . $tag
                );
            }

            if ($lowTag === '@todo') {
                $this->addIssue('Note: ' . Regex::replace('#^//\s*#', '', trim($text)));
            }
        }
    }
}
