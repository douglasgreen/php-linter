# Source Map

## Directory Structure

```
bin/
  php-linter              CLI entry point; orchestrates all 7 analysis stages

src/
  ComposerChecker.php     composer.json validation + key sorting (40 KB)
  Config.php              Loads php-linter.json (ignoreIssues, metricLimits)
  DocStandardsChecker.php Markdown documentation validation (20 KB)
  IgnoreList.php          .phplintignore loader; glob-to-regex matching
  IssueHolder.php         Central issue collection with file/class/function context
  PackageJsonChecker.php  package.json validation + key sorting (50 KB)
  Repository.php          git ls-files wrapper; file type detection

  Linter/
    Linter.php            Orchestrates AST parsing and traversal per file
    ElementVisitor.php    Main AST visitor; delegates to sub-visitors and checkers
    ComposerFile.php      PSR-4 namespace-to-path mapping from composer.json
    UnusedClassAnalyzer.php   Two-pass: collect class defs → collect usages → diff
    UnusedFunctionAnalyzer.php Two-pass: collect function defs → collect usages → diff

    Checker/
      AbstractNodeChecker.php  Base class: holds Node + IssueHolder
      DocBlockChecker.php      PHPDoc tag validation, summary formatting, complex types
      ExpressionChecker.php    Checks expressions (DTO suggestions, etc.)
      FunctionCallChecker.php  Flags dangerous functions (eval, var_dump, etc.)
      FunctionChecker.php      Function-level rules (exit/die, goto, global, @, etc.)
      LocalScopeChecker.php    Checks local scope context
      NameChecker.php          Naming conventions (CamelCase, constants, length, prefixes)
      OperatorChecker.php      Operator-level checks
      TryCatchChecker.php      Empty catch block detection

    Visitor/
      AbstractVisitorChecker.php  Base class for visitors
      ClassVisitor.php           Unused private members, visibility ordering, PHP4 constructors
      FunctionVisitor.php        Unused parameters, single-use variables
      MagicNumberVisitor.php     Duplicate numeric literals → constant suggestions
      SuperglobalUsageVisitor.php Flags superglobal usage outside allowed contexts

    PhpDoc/
      ParserFactory.php          Factory for PHPStan PhpDoc lexer+parser

  Metrics/
    Analyzer.php           Orchestrates PDepend run; holds all default threshold constants
    CacheManager.php       Manages var/cache/pdepend/ directories and file copying
    MetricChecker.php      Per-metric validation against limits; adds issues
    MetricData.php         Data transfer object for parsed metric values
    MetricGenerator.php    Runs vendor/bin/pdepend to generate summary XML
    XmlParser.php          Parses PDepend summary XML into structured arrays

tests/
  fixtures/                Test fixture files
  Unit/
    ConfigTest.php
    DocStandardsCheckerTest.php
    IgnoreListTest.php
    IssueHolderTest.php
    RepositoryTest.php
    Linter/
      Checker/             One test per checker (8 tests)
      Visitor/             One test per visitor (5 tests)
      ComposerFileTest.php
      ElementVisitorTest.php
      LinterTest.php
    Metrics/
      AnalyzerTest.php
      CacheManagerTest.php
      MetricCheckerTest.php
      MetricDataTest.php
      MetricGeneratorTest.php
      XmlParserTest.php
```

## Configuration Files (Repo Root)

| File | Purpose |
|------|---------|
| `composer.json` | Package definition, autoloading, dev tool scripts |
| `mago.toml` | Mago linter config (PHP static analysis + formatter) |
| `phpunit.xml.dist` | PHPUnit config — single `Unit` testsuite |
| `phpstan.neon.dist` | PHPStan level 8 config |
| `rector.php` | Rector code quality + PHP 8.3 + PHPUnit sets |
| `.php-cs-fixer.php` | PSR-12 + strict types + ordered class elements |
| `.markdownlint.json` | Markdown linting rules |
| `.prettierrc.json` | Prettier formatting for Markdown/JSON |
| `.phplintignore` | Files excluded from the linter's own self-analysis |
| `.gitignore` | Excludes `var/`, `vendor/`, `node_modules/`, lock files |
| `.github/workflows/openwiki-update.yml` | Scheduled OpenWiki regeneration workflow |

## Size Notes

The two largest source files are `PackageJsonChecker.php` (50 KB) and `ComposerChecker.php`
(40 KB). These are monolithic validator classes that check many aspects of their respective JSON
files. `DocStandardsChecker.php` (20 KB) is similarly comprehensive for Markdown validation.
