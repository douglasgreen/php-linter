<?php

/**
 * Documentation Standards Compliance Checker
 *
 * Validates Markdown documentation for consistency, style, and best practices.
 */

declare(strict_types=1);

namespace DouglasGreen\PhpLinter;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Extension\CommonMark\Node\Block\IndentedCode;
use League\CommonMark\Extension\CommonMark\Node\Block\ListBlock;
use League\CommonMark\Extension\CommonMark\Node\Block\ListItem;
use League\CommonMark\Extension\CommonMark\Node\Block\Paragraph;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Extension\FrontMatter\FrontMatterExtension;
use League\CommonMark\Node\Block\Document;
use League\CommonMark\Node\Node;
use League\CommonMark\Parser\MarkdownParser;

class DocStandardsChecker
{
    private readonly Repository $repository;

    private readonly MarkdownParser $parser;

    /** @var array<int, string> */
    private array $files = [];

    /** @var array<int, string> */
    private array $markdownFiles = [];

    /** @var array<string, array{outgoing: array<int, string>, incoming: array<int, string>}> */
    private array $linkGraph = [];

    /** @var array<string, array<string, string>> */
    private array $requiredFiles = [
        'root' => [
            'README.md' => 'Project explanation and installation guide',
            'CHANGELOG.md' => 'Version history reference',
            'LICENSE' => 'Legal terms',
        ],
        'docs' => [
            'docs/index.md' => 'Documentation navigation hub',
            'docs/development/setup.md' => 'Development environment setup',
            'docs/development/testing.md' => 'Testing procedures',
            'docs/architecture.md' => 'System architecture explanation',
        ],
    ];

    /** @var array<int, string> */
    private array $forbiddenWords = ['simply', 'just', 'obviously', 'clearly', 'basically', 'easily'];

    /** @var array<string, string> */
    private array $securityPatterns = [
        '/\b(sk-[a-zA-Z0-9]{20,})/i' => 'Exposed API key (OpenAI format)',
        '/\b(ghp_[a-zA-Z0-9]{36})/i' => 'Exposed GitHub token',
        '/\b(AKIA[0-9A-Z]{16})/' => 'Exposed AWS Access Key ID',
        '/(?:password|passwd|pwd)\s*[=:]\s*["\'][^"\'<>]+["\']/i' => 'Hardcoded password',
        '/(?:api[_-]?key|apikey)\s*[=:]\s*["\'][^"\'<>]+["\']/i' => 'Hardcoded API key',
        '/\b\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\b/' => 'IP address (verify not sensitive)',
    ];

    public function __construct(private readonly string $rootDir, private readonly IssueHolder $issueHolder, private readonly IgnoreList $ignoreList)
    {
        $this->repository = new Repository();

        // Initialize the League CommonMark parser environment
        $environment = new Environment([]);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new FrontMatterExtension()); // Support YAML Frontmatter natively

        $this->parser = new MarkdownParser($environment);
    }

    public function run(): void
    {
        $this->files = $this->repository->getAllFiles();

        // Filter out ignored files
        $this->files = array_filter($this->files, fn (string $file): bool => !$this->ignoreList->shouldIgnore($file));

        $this->markdownFiles = array_filter($this->files, fn (string $file): bool => (bool) preg_match('/\.md$/i', $file));

        $this->checkRequiredFiles();
        $this->checkFileNaming();
        $this->checkFileEncoding();
        $this->analyzeMarkdownContent();
        $this->detectOrphanedFiles();
        $this->checkDirectoryStructure();
    }

    private function checkRequiredFiles(): void
    {
        // Check root files
        foreach ($this->requiredFiles['root'] as $file => $description) {
            if (!in_array($file, $this->files)) {
                $this->issueHolder->setCurrentFile($file);
                $this->issueHolder->addIssue(
                    'Missing required file: ' . $description,
                );
            }
        }

        // Check docs structure
        foreach ($this->requiredFiles['docs'] as $file => $description) {
            if (!in_array($file, $this->files)) {
                $this->issueHolder->setCurrentFile($file);
                $this->issueHolder->addIssue(
                    'Missing required documentation: ' . $description,
                );
            }
        }

        // Check for ADR directory
        $hasAdr = false;
        foreach ($this->files as $file) {
            if (str_starts_with((string) $file, 'docs/adr/') && preg_match('/^\d{4}-/', basename((string) $file))) {
                $hasAdr = true;
                break;
            }
        }

        if (!$hasAdr && in_array('docs/architecture.md', $this->files)) {
            $this->issueHolder->setCurrentFile('docs/adr/');
            $this->issueHolder->addIssue(
                'Missing ADR directory',
                'Architecture Decision Records help track important design decisions (pattern: NNNN-decision-title.md)',
            );
        }
    }

    private function checkDirectoryStructure(): void
    {
        // Check for docs/ index.md
        if (is_dir($this->rootDir . '/docs') && !in_array('docs/index.md', $this->files)) {
            $this->issueHolder->setCurrentFile('docs/index.md');
            $this->issueHolder->addIssue(
                'Missing navigation hub',
                'docs/ directory should have index.md as a navigation hub for documentation',
            );
        }

        // Check for CHANGELOG format
        if (in_array('CHANGELOG.md', $this->files)) {
            $content = $this->getFileContent('CHANGELOG.md');
            if (!preg_match('/## \[\d+\.\d+/', $content) && !preg_match('/## \d+\.\d+/', $content)) {
                $this->issueHolder->setCurrentFile('CHANGELOG.md');
                $this->issueHolder->addIssue(
                    'Changelog format not recognized',
                    'Consider using Keep a Changelog format (## [1.0.0])',
                );
            }
        }
    }

    private function checkFileNaming(): void
    {
        foreach ($this->markdownFiles as $file) {
            $basename = basename((string) $file);
            $this->issueHolder->setCurrentFile($file);

            // Check kebab-case
            if (!preg_match('/^[a-z0-9]+(-[a-z0-9]+)*\.md$/', $basename) &&
                !in_array($basename, ['README.md', 'CHANGELOG.md', 'LICENSE.md', 'CONTRIBUTING.md'])) {
                $this->issueHolder->addIssue(
                    'Invalid filename: use kebab-case',
                    'Filenames should use kebab-case (e.g., deployment-guide.md) for consistency',
                );
            }

            // Check for spaces or underscores
            if (preg_match('/[ _]/', $basename)) {
                $this->issueHolder->addIssue(
                    'Invalid filename characters',
                    'Use hyphens instead of spaces or underscores for better URL compatibility',
                );
            }
        }
    }

    private function checkFileEncoding(): void
    {
        foreach ($this->markdownFiles as $file) {
            $fullPath = $this->rootDir . '/' . $file;
            $this->issueHolder->setCurrentFile($file);

            // Check for UTF-8
            $content = (string) file_get_contents($fullPath);
            if (!mb_check_encoding($content, 'UTF-8')) {
                $this->issueHolder->addIssue(
                    'Invalid encoding: file must be UTF-8',
                );
            }

            // Check for CRLF line endings
            if (str_contains($content, "\r\n")) {
                $this->issueHolder->addIssue(
                    'Invalid line endings',
                    'Use Unix line endings (LF) instead of Windows (CRLF) for cross-platform compatibility',
                );
            }

            // Check for trailing whitespace
            if (preg_match('/[ \t]+$/m', $content)) {
                $this->issueHolder->addIssue(
                    'Trailing whitespace detected',
                    'Remove trailing whitespace at end of lines for cleaner diffs',
                );
            }
        }
    }

    private function analyzeMarkdownContent(): void
    {
        foreach ($this->markdownFiles as $file) {
            $content = $this->getFileContent($file);
            $document = $this->parser->parse($content);

            $this->checkHeadingStructure($file, $document);
            $this->checkCodeBlocks($file, $document);
            $this->checkWritingStyle($file, $document);
            $this->checkLinks($file, $document);
            $this->checkSecurity($file, $content); // Security is still checked against the raw string
            $this->checkFrontmatter($file, $content, $document);
            $this->buildLinkGraph($file, $document);
        }
    }

    private function checkHeadingStructure(string $file, Document $document): void
    {
        $hasH1 = false;
        $prevLevel = 0;
        $this->issueHolder->setCurrentFile($file);

        foreach ($document->iterator() as $node) {
            if (!$node instanceof Heading) {
                continue;
            }

            $level = $node->getLevel();
            $text = trim($this->extractText($node));
            $lineNum = (string) $node->getStartLine();

            // Check single H1
            if ($level === 1) {
                if ($hasH1) {
                    $this->issueHolder->addIssue(
                        'Multiple H1 headings at line ' . $lineNum,
                        'Document should have exactly one H1 heading for clarity',
                    );
                }

                $hasH1 = true;
            }

            // Check no skipped levels
            if ($prevLevel > 0 && $level > $prevLevel + 1) {
                $this->issueHolder->addIssue(
                    sprintf('Skipped heading level at line %s: H%d -> H%d', $lineNum, $prevLevel, $level),
                    'Use sequential heading levels for proper document structure',
                );
            }

            $prevLevel = $level;

            // Check sentence case
            if ($level <= 3 && preg_match('/[A-Z]{2,}/', $text) && !preg_match('/^[A-Z][a-z]+([a-z]+)*$/', $text) && preg_match('/^[A-Z][a-z]+ [A-Z]/', $text)) {
                $this->issueHolder->addIssue(
                    'Title Case heading at line ' . $lineNum . ": '" . $text . "'",
                    'Use sentence case for better readability',
                );
            }
        }

        if (!$hasH1) {
            $this->issueHolder->addIssue(
                'Missing H1 heading',
                'Document should have exactly one H1 heading as the main title',
            );
        }
    }

    private function checkCodeBlocks(string $file, Document $document): void
    {
        $this->issueHolder->setCurrentFile($file);

        foreach ($document->iterator() as $node) {
            if ($node instanceof FencedCode) {
                $info = $node->getInfo();
                if ($info === null || trim($info) === '') {
                    $this->issueHolder->addIssue(
                        'Code block at line ' . $node->getStartLine() . ' missing language specification',
                        'Specify a language for syntax highlighting (e.g., ```php)',
                    );
                }
            } elseif ($node instanceof IndentedCode) {
                $this->issueHolder->addIssue(
                    'Indented code block detected at line ' . $node->getStartLine(),
                    'Use fenced code blocks (```) instead of indentation for better readability',
                );
            }
        }
    }

    private function checkWritingStyle(string $file, Document $document): void
    {
        $this->issueHolder->setCurrentFile($file);

        foreach ($document->iterator() as $node) {
            // Only check prose context (Paragraphs and Headings) to avoid triggering on code contents
            if (!$node instanceof Paragraph && !$node instanceof Heading) {
                continue;
            }

            $blockText = $this->extractText($node);

            // Check for fluff words
            foreach ($this->forbiddenWords as $word) {
                if (preg_match(sprintf('/\b%s\b/i', $word), $blockText)) {
                    $this->issueHolder->addIssue(
                        "Fluff word detected: '" . $word . "'",
                        'Remove words that can alienate struggling users',
                    );
                }
            }

            // Check for passive voice indicators - heuristic
            if (preg_match('/\b(is|was|were|been|be|being)\s+(?:configured|installed|created|updated|deleted|processed|generated|used|done|made)\b/i', $blockText)) {
                $this->issueHolder->addIssue(
                    'Passive voice detected',
                    "Use active voice (e.g., 'Click Save') instead of passive (e.g., 'Save should be clicked')",
                );
            }

            // Check for future tense
            if (preg_match('/\b(will|shall)\s+(?:return|display|show|create|update)\b/i', $blockText)) {
                $this->issueHolder->addIssue(
                    'Future tense detected',
                    "Use present tense (e.g., 'The API returns') instead of future (e.g., 'The API will return')",
                );
            }
        }

        $this->checkListPunctuation($file, $document);
    }

    private function checkListPunctuation(string $file, Document $document): void
    {
        $this->issueHolder->setCurrentFile($file);

        foreach ($document->iterator() as $node) {
            if (!$node instanceof ListBlock) {
                continue;
            }

            $listType = null; // 'fragment' or 'sentence'
            foreach ($node->children() as $item) {
                if (!$item instanceof ListItem) {
                    continue;
                }

                $itemText = trim($this->extractText($item));
                if ($itemText === '') {
                    continue;
                }

                $hasPeriod = str_ends_with($itemText, '.');

                if ($listType === null) {
                    $listType = $hasPeriod ? 'sentence' : 'fragment';
                } else {
                    $currentType = $hasPeriod ? 'sentence' : 'fragment';
                    if ($currentType !== $listType) {
                        $this->issueHolder->addIssue(
                            'Inconsistent list punctuation at line ' . $item->getStartLine(),
                            'Use consistent punctuation: either all items end with periods or none do',
                        );
                        break;
                    }
                }
            }
        }
    }

    private function checkLinks(string $file, Document $document): void
    {
        $this->issueHolder->setCurrentFile($file);

        foreach ($document->iterator() as $node) {
            if (!$node instanceof Link) {
                continue;
            }

            $linkText = $this->extractText($node);
            $url = $node->getUrl();

            // Check for "click here"
            if (preg_match('/click here/i', $linkText)) {
                $this->issueHolder->addIssue(
                    "Non-descriptive link text: '" . $linkText . "'",
                    'Use descriptive link text that indicates the destination',
                );
            }

            // Check bare URLs (if the link text explicitly matches the destination URL)
            if ($linkText === $url) {
                $this->issueHolder->addIssue(
                    'Bare URL detected',
                    'Use [text](url) format instead of bare URLs for better readability',
                );
            }

            // Resolve relative link for local files
            if (preg_match('/^(https?:|mailto:|#)/', $url)) {
                continue;
            }

            // Remove anchor
            $linkPath = preg_replace('/#.*/', '', $url);
            if (empty($linkPath)) {
                continue;
            }

            // Resolve relative to file
            $dir = dirname($file);
            $targetPath = $dir === '.' ? $linkPath : $dir . '/' . $linkPath;
            $targetPath = preg_replace('#/+#', '/', $targetPath); // normalize

            // Check if exists
            if (!in_array($targetPath, $this->files) && !in_array($targetPath . '.md', $this->files)) {
                $this->issueHolder->addIssue(
                    "Broken internal link: '" . $url . "'",
                    'Link points to a non-existent file',
                );
            }
        }
    }

    private function checkSecurity(string $file, string $content): void
    {
        $this->issueHolder->setCurrentFile($file);

        foreach ($this->securityPatterns as $pattern => $description) {
            if (preg_match($pattern, $content)) {
                $this->issueHolder->addIssue(
                    'Security risk: ' . $description,
                    'Use placeholder variables like <YOUR_API_KEY> instead of exposing credentials',
                );
            }
        }
    }

    private function checkFrontmatter(string $file, string $content, Document $document): void
    {
        $this->issueHolder->setCurrentFile($file);

        // V2 of CommonMark stores FrontMatter in the Document node's `data` array
        if ($document->data->has('front_matter')) {
            $frontMatter = $document->data->get('front_matter');

            if (!isset($frontMatter['title'])) {
                $this->issueHolder->addIssue(
                    'Missing title in frontmatter',
                    "Add 'title' to YAML frontmatter for better documentation metadata",
                );
            }
        } elseif (str_word_count($content) > 300) {
            // For long docs, recommend frontmatter
            $this->issueHolder->addIssue(
                'Consider adding YAML frontmatter',
                'Add YAML frontmatter with title, description, and last_reviewed for better documentation organization',
            );
        }
    }

    private function buildLinkGraph(string $file, Document $document): void
    {
        $this->linkGraph[$file] = ['outgoing' => [], 'incoming' => []];

        foreach ($document->iterator() as $node) {
            if (!$node instanceof Link) {
                continue;
            }

            $url = $node->getUrl();

            if (preg_match('/^(https?:|mailto:|#)/', $url)) {
                continue;
            }

            $linkPath = preg_replace('/#.*/', '', $url);
            if (empty($linkPath)) {
                continue;
            }

            $dir = dirname($file);
            $targetPath = $dir === '.' ? $linkPath : $dir . '/' . $linkPath;
            $targetPath = preg_replace('#/+#', '/', $targetPath);

            // Try with .md extension
            if (!in_array($targetPath, $this->files) && in_array($targetPath . '.md', $this->files)) {
                $targetPath .= '.md';
            }

            $this->linkGraph[$file]['outgoing'][] = $targetPath;

            if (!isset($this->linkGraph[$targetPath])) {
                $this->linkGraph[$targetPath] = ['outgoing' => [], 'incoming' => []];
            }

            $this->linkGraph[$targetPath]['incoming'][] = $file;
        }
    }

    private function detectOrphanedFiles(): void
    {
        $entryPoints = ['README.md', 'docs/index.md', 'CHANGELOG.md'];

        foreach ($this->markdownFiles as $file) {
            // Skip entry points
            if (in_array($file, $entryPoints)) {
                continue;
            }

            $incoming = $this->linkGraph[$file]['incoming'] ?? [];

            // Check if linked from any markdown file
            if (empty($incoming)) {
                $this->issueHolder->setCurrentFile($file);
                $this->issueHolder->addIssue(
                    'Orphaned document',
                    'This file is not linked from any other Markdown file; add cross-references for discoverability',
                );
            }
        }
    }

    private function extractText(Node $node): string
    {
        $text = '';
        foreach ($node->iterator() as $child) {
            if (method_exists($child, 'getLiteral')) {
                $text .= $child->getLiteral();
            }
        }

        return $text;
    }

    private function getFileContent(string $file): string
    {
        return (string) file_get_contents($this->rootDir . '/' . $file);
    }
}
