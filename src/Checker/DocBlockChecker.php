<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Checker;

use DouglasGreen\PhpLinter\IssueHolder;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\Trait_;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTextNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;

/**
 * Checks PHPDoc blocks for compliance with documentation standards.
 *
 * @package DouglasGreen\PhpLinter\Checker
 *
 * @since 1.0.0
 */
class DocBlockChecker extends AbstractNodeChecker
{
    /**
     * Order priority for PHPDoc tags.
     */
    private const TAG_ORDER = [
        '@api' => 1, '@internal' => 1,
        '@template' => 2, '@template-covariant' => 2, '@phpstan-type' => 2,
        '@extends' => 3, '@implements' => 3, '@use' => 3, '@mixin' => 3,
        '@param' => 4,
        '@return' => 5,
        '@throws' => 6,
        '@pure' => 7, '@immutable' => 7,
        '@see' => 8, '@link' => 8,
        '@since' => 9, '@deprecated' => 9,
        '@copyright' => 10, '@license' => 10,
    ];

    /**
     * @param Lexer $lexer The PHPDoc lexer.
     * @param PhpDocParser $phpDocParser The PHPDoc parser.
     */
    public function __construct(
        Node $node,
        IssueHolder $issueHolder,
        private readonly Lexer $lexer,
        private readonly PhpDocParser $phpDocParser
    ) {
        parent::__construct($node, $issueHolder);
    }

    /**
     * Performs validation checks on the associated node's DocBlock.
     *
     * @return array<string, bool> A map of issue messages to their status.
     */
    public function check(): array
    {
        $docComment = $this->node->getDocComment();

        if ($docComment === null) {
            $this->checkMissingDocBlock();
            return $this->getIssues();
        }

        $tokens = new TokenIterator($this->lexer->tokenize($docComment->getText()));
        $phpDocNode = $this->phpDocParser->parse($tokens);

        $this->validateSummaryAndDescription($phpDocNode);
        $this->validateMandatoryTags($phpDocNode);
        $this->validateTagOrdering($phpDocNode);
        $this->validateComplexTypes($phpDocNode);

        return $this->getIssues();
    }

    /**
     * Checks if a missing DocBlock is a violation.
     */
    private function checkMissingDocBlock(): void
    {
        // Rule 7.3: Public API elements MUST NOT be undocumented.
        // For now, we check if it's a class-like structure or a public method.
        $isPublicApi = false;
        if ($this->node instanceof Class_ || $this->node instanceof Interface_ || $this->node instanceof Trait_ || $this->node instanceof Enum_) {
            $isPublicApi = true;
        } elseif (($this->node instanceof ClassMethod || $this->node instanceof Property) && $this->node->isPublic()) {
            $isPublicApi = true;
        }

        if ($isPublicApi) {
            $this->addIssue('Public API elements MUST have a DocBlock.');
        }
    }

    /**
     * Validates summary and description formatting.
     *
     * @param PhpDocNode $phpDocNode The parsed DocBlock node.
     */
    private function validateSummaryAndDescription(PhpDocNode $phpDocNode): void
    {
        // Extract text from the first PhpDocTextNode
        $text = '';
        foreach ($phpDocNode->children as $child) {
            if ($child instanceof PhpDocTextNode) {
                $text = $child->text;
                break;
            }
        }

        // Attempt to get summary (first line of text)
        $parts = preg_split('/\R/', trim($text), 2);
        $summary = $parts[0] ?? '';

        if ($summary === '') {
            $this->addIssue('DocBlock MUST start with a summary line.');
            return;
        }

        // Rule 1.3: Summary checks
        if (strlen($summary) > 80) {
            $this->addIssue('DocBlock summary MUST be under 80 characters.');
        }
        if (!preg_match('/^[A-Z]/', $summary)) {
            $this->addIssue('DocBlock summary MUST start with a capital letter.');
        }
        if (!str_ends_with($summary, '.')) {
            $this->addIssue('DocBlock summary MUST end with a period.');
        }
    }

    /**
     * Validates the presence of mandatory tags.
     *
     * @param PhpDocNode $phpDocNode The parsed DocBlock node.
     */
    private function validateMandatoryTags(PhpDocNode $phpDocNode): void
    {
        if ($this->node instanceof Class_ || $this->node instanceof Interface_ || $this->node instanceof Trait_ || $this->node instanceof Enum_) {
            $this->checkClassTags($phpDocNode);
        } elseif ($this->node instanceof ClassMethod || $this->node instanceof Function_) {
            $this->checkFunctionTags($phpDocNode);
        } elseif ($this->node instanceof Property) {
            $this->checkPropertyTags($phpDocNode);
        }
    }

    /**
     * Checks mandatory tags for classes/interfaces/traits/enums.
     *
     * @param PhpDocNode $phpDocNode The parsed DocBlock node.
     */
    private function checkClassTags(PhpDocNode $phpDocNode): void
    {
        if ($phpDocNode->getTagsByName('@package') === []) {
            $this->addIssue('Class-like structures MUST have a @package tag.');
        }
        if ($phpDocNode->getTagsByName('@since') === []) {
            $this->addIssue('Class-like structures MUST have a @since tag.');
        }
        if ($phpDocNode->getTagsByName('@api') === [] && $phpDocNode->getTagsByName('@internal') === []) {
            $this->addIssue('Class-like structures MUST have an @api or @internal tag.');
        }
    }

    /**
     * Checks mandatory tags for methods/functions.
     *
     * @param PhpDocNode $phpDocNode The parsed DocBlock node.
     */
    private function checkFunctionTags(PhpDocNode $phpDocNode): void
    {
        // Check @param tags match parameters
        $params = $this->node instanceof ClassMethod ? $this->node->getParams() : [];
        $paramTags = $phpDocNode->getTagsByName('@param');
        
        if (count($params) !== count($paramTags)) {
            $this->addIssue('Method parameters and @param tags count mismatch.');
        }

        // Check @return
        $returnType = $this->node instanceof ClassMethod ? $this->node->getReturnType() : null;
        $returnTags = $phpDocNode->getTagsByName('@return');
        
        // Rule 4.1: @return required for non-void, optional for void but recommended
        if ($returnType !== null && (string) $returnType !== 'void' && $returnTags === []) {
            $this->addIssue('Non-void methods MUST have a @return tag.');
        }
    }

    /**
     * Checks mandatory tags for properties.
     *
     * @param PhpDocNode $phpDocNode The parsed DocBlock node.
     */
    private function checkPropertyTags(PhpDocNode $phpDocNode): void
    {
        // Rule 1.1: @var required if no native type or more specific type needed
        if ($this->node->type === null && $phpDocNode->getTagsByName('@var') === []) {
            $this->addIssue('Properties without native types MUST have a @var tag.');
        }
    }

    /**
     * Validates the ordering of tags.
     *
     * @param PhpDocNode $phpDocNode The parsed DocBlock node.
     */
    private function validateTagOrdering(PhpDocNode $phpDocNode): void
    {
        $lastPriority = 0;
        foreach ($phpDocNode->children as $child) {
            if (!$child instanceof PhpDocTagNode) {
                continue;
            }
            
            $tagName = $child->name;
            // Normalize tag name (remove variable part for params like @param $var)
            $baseTagName = strtok($tagName, ' ');
            if ($baseTagName === false) {
                $baseTagName = $tagName;
            }

            $priority = self::TAG_ORDER[$baseTagName] ?? 99;

            if ($priority < $lastPriority) {
                $this->addIssue(sprintf('Tag %s is out of order. Follow the standard tag ordering.', $tagName));
                // We report once per block to avoid noise
                return;
            }
            $lastPriority = $priority;
        }
    }

    /**
     * Validates complex types (e.g., no bare 'array').
     *
     * @param PhpDocNode $phpDocNode The parsed DocBlock node.
     */
    private function validateComplexTypes(PhpDocNode $phpDocNode): void
    {
        $typeTags = array_merge(
            $phpDocNode->getTagsByName('@param'),
            $phpDocNode->getTagsByName('@return'),
            $phpDocNode->getTagsByName('@var')
        );

        foreach ($typeTags as $tag) {
            // Accessing the type string is tricky without deep inspection of the AST.
            // We check the raw text of the tag value for simplicity.
            $typeString = (string) $tag->value;
            
            // Rule 4.4: No bare 'array'
            if (preg_match('/\barray\b/', $typeString) && !preg_match('/array[<\{]/', $typeString)) {
                $this->addIssue('Use typed generics syntax (e.g., list<Foo>) instead of bare "array".');
            }
        }
    }
}
