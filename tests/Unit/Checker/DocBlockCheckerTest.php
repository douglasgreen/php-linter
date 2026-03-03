<?php

declare(strict_types=1);

namespace Tests\Unit\Checker;

use DouglasGreen\PhpLinter\Checker\DocBlockChecker;
use DouglasGreen\PhpLinter\IssueHolder;
use DouglasGreen\PhpLinter\PhpDoc\ParserFactory;
use PhpParser\Comment\Doc;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\PropertyProperty;
use PhpParser\Node\Stmt\Trait_;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the DocBlockChecker class.
 *
 * @package Tests\Unit\Checker
 */
#[CoversClass(DocBlockChecker::class)]
#[Small]
final class DocBlockCheckerTest extends TestCase
{
    private IssueHolder $issueHolder;

    protected function setUp(): void
    {
        $this->issueHolder = new IssueHolder();
    }

    /**
     * Tests that missing DocBlocks are detected on public API elements.
     */
    #[DataProvider('publicApiNodeProvider')]
    public function testItDetectsMissingDocBlockOnPublicApi(object $node): void
    {
        // Arrange
        $lexer = ParserFactory::createLexer();
        $parser = ParserFactory::createPhpDocParser();
        $checker = new DocBlockChecker($node, $this->issueHolder, $lexer, $parser);

        // Act
        $checker->check();
        $issues = $this->issueHolder->getIssues();

        // Assert
        $this->assertArrayHasKey('Public API elements MUST have a DocBlock.', $issues);
    }

    /**
     * Provides nodes that require DocBlocks.
     */
    public static function publicApiNodeProvider(): iterable
    {
        yield 'class' => [new Class_(new Identifier('TestClass'))];
        yield 'interface' => [new Interface_(new Identifier('TestInterface'))];
        yield 'trait' => [new Trait_(new Identifier('TestTrait'))];
        yield 'public method' => [
            new ClassMethod(
                new Identifier('publicMethod'),
                ['flags' => Class_::MODIFIER_PUBLIC]
            ),
        ];
        yield 'public property' => [
            new Property(
                Class_::MODIFIER_PUBLIC,
                [new PropertyProperty('testProp')],
                []
            ),
        ];
    }

    /**
     * Tests that valid DocBlocks do not trigger issues.
     */
    public function testItAcceptsValidClassDocBlock(): void
    {
        // Arrange
        $doc = new Doc(<<<'DOC'
/**
 * A valid summary.
 *
 * @package Test
 * @api
 * @since 1.0.0
 */
DOC);
        $node = new Class_(new Identifier('ValidClass'), [], ['comments' => [$doc]]);
        $lexer = ParserFactory::createLexer();
        $parser = ParserFactory::createPhpDocParser();
        $checker = new DocBlockChecker($node, $this->issueHolder, $lexer, $parser);

        // Act
        $checker->check();
        $issues = $this->issueHolder->getIssues();

        // Assert
        $this->assertEmpty($issues);
    }

    /**
     * Tests summary validation rules.
     */
    #[DataProvider('summaryProvider')]
    public function testItValidatesSummary(string $docBlock, ?string $expectedIssue): void
    {
        // Arrange
        $doc = new Doc($docBlock);
        // Using Class_ as a container for the docblock
        $node = new Class_(new Identifier('TestClass'), [], ['comments' => [$doc]]);
        $lexer = ParserFactory::createLexer();
        $parser = ParserFactory::createPhpDocParser();
        $checker = new DocBlockChecker($node, $this->issueHolder, $lexer, $parser);

        // Act
        $checker->check();
        $issues = $this->issueHolder->getIssues();

        // Assert
        if ($expectedIssue === null) {
            // We only check that the specific summary issue is NOT present.
            // Other issues (like missing tags) might exist, so we don't assertEmpty.
            foreach ($issues as $message => $bool) {
                $this->assertStringNotContainsString('summary', $message);
            }
        } else {
            $this->assertArrayHasKey($expectedIssue, $issues);
        }
    }

    /**
     * Provides summary test cases.
     */
    public static function summaryProvider(): iterable
    {
        yield 'valid summary' => [
            "/**\n * Valid summary.\n */",
            null,
        ];
        yield 'missing summary' => [
            "/**\n * @package Test\n */",
            'DocBlock MUST start with a summary line.',
        ];
        yield 'too long' => [
            "/**\n * This summary is definitely way too long and it exceeds the eighty character limit imposed by the standards.\n */",
            'DocBlock summary MUST be under 80 characters.',
        ];
        yield 'no capital start' => [
            "/**\n * lowercase start.\n */",
            'DocBlock summary MUST start with a capital letter.',
        ];
        yield 'no period end' => [
            "/**\n * Missing period\n */",
            'DocBlock summary MUST end with a period.',
        ];
    }

    /**
     * Tests mandatory tag validation for classes.
     */
    #[DataProvider('classTagProvider')]
    public function testItValidatesMandatoryClassTags(string $docBlock, string $expectedIssue): void
    {
        // Arrange
        $doc = new Doc($docBlock);
        $node = new Class_(new Identifier('TestClass'), [], ['comments' => [$doc]]);
        $lexer = ParserFactory::createLexer();
        $parser = ParserFactory::createPhpDocParser();
        $checker = new DocBlockChecker($node, $this->issueHolder, $lexer, $parser);

        // Act
        $checker->check();
        $issues = $this->issueHolder->getIssues();

        // Assert
        $this->assertArrayHasKey($expectedIssue, $issues);
    }

    /**
     * Provides class tag test cases.
     */
    public static function classTagProvider(): iterable
    {
        yield 'missing package' => [
            "/**\n * Summary.\n * @since 1.0.0\n * @api\n */",
            'Class-like structures MUST have a @package tag.',
        ];
        yield 'missing since' => [
            "/**\n * Summary.\n * @package Test\n * @api\n */",
            'Class-like structures MUST have a @since tag.',
        ];
        yield 'missing api or internal' => [
            "/**\n * Summary.\n * @package Test\n * @since 1.0.0\n */",
            'Class-like structures MUST have an @api or @internal tag.',
        ];
    }

    /**
     * Tests that bare array types are flagged.
     */
    public function testItDetectsBareArrayType(): void
    {
        // Arrange
        $doc = new Doc(<<<'DOC'
/**
 * Summary.
 *
 * @param array $items
 */
DOC);
        $node = new ClassMethod(new Identifier('test'), [], ['comments' => [$doc]]);
        $lexer = ParserFactory::createLexer();
        $parser = ParserFactory::createPhpDocParser();
        $checker = new DocBlockChecker($node, $this->issueHolder, $lexer, $parser);

        // Act
        $checker->check();
        $issues = $this->issueHolder->getIssues();

        // Assert
        $this->assertArrayHasKey('Use typed generics syntax (e.g., list<Foo>) instead of bare "array".', $issues);
    }

    /**
     * Tests that tag ordering is validated.
     */
    public function testItDetectsOutOfOrderTags(): void
    {
        // Arrange
        // @param (4) should come after @api (1)
        $doc = new Doc(<<<'DOC'
/**
 * Summary.
 *
 * @param string $arg
 * @api
 */
DOC);
        $node = new ClassMethod(new Identifier('test'), [], ['comments' => [$doc]]);
        $lexer = ParserFactory::createLexer();
        $parser = ParserFactory::createPhpDocParser();
        $checker = new DocBlockChecker($node, $this->issueHolder, $lexer, $parser);

        // Act
        $checker->check();
        $issues = $this->issueHolder->getIssues();

        // Assert
        $this->assertArrayHasKey('Tag @api is out of order. Follow the standard tag ordering.', $issues);
    }
}
