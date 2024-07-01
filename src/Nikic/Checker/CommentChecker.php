<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Nikic\Checker;

use DouglasGreen\Utility\Regex\Regex;
use PhpParser\Comment;
use PhpParser\Comment\Doc;

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
        $matches = Regex::matchAll('/@\w+/', $text);
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
    }

    protected function checkDocComment(Doc $doc): void
    {
        //echo "Doc Comment found: " . $comment->getText() . "\n";
        //echo "Attached to node of type: " . get_class($this->node) . "\n\n";
        $text = $doc->getText();
        $tags = self::getPhpdocTags($text);
        foreach ($tags as $tag) {
            if (! in_array(strtolower($tag), self::PHPDOC_TAGS, true)) {
                $this->addIssue('Invalid PHPDoc tag found in docblock: ' . $tag);
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
            if (in_array(strtolower($tag), self::PHPDOC_TAGS, true)) {
                $this->addIssue(
                    'PHPDoc tag found in multi-line comment instead of dockblock: ' . $tag
                );
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
            if (in_array(strtolower($tag), self::PHPDOC_TAGS, true)) {
                $this->addIssue(
                    'PHPDoc tag found in single-line comment instead of dockblock: ' . $tag
                );
            }
        }
    }
}
