# Operations

Development commands, configuration, tooling, testing, and CI for the php-linter project.

## Development Commands

```bash
# PHP tooling (run lint:fix before lint and test)
composer lint:fix    # php-cs-fixer fix + rector fix
composer lint        # php-cs-fixer (dry-run) + phpstan + rector (dry-run)
composer test        # phpunit (Unit suite)
composer test:unit   # same as above
composer qa          # lint + test in sequence

# Node tooling
npm run lint         # markdownlint on all *.md
npm run format       # prettier --write + markdownlint --fix
```

**Order:** Always run `composer lint:fix` before `composer lint` and `composer test`.

## Configuration

### `php-linter.json` (User-Facing Config)

Optional file in the repo root. Loaded by `Config` (`src/Config.php`).

```json
{
  "ignoreIssues": [
    "Remove unused private non-static method MyClass::unusedMethod() to reduce dead code."
  ],
  "metricLimits": {
    "classSize": 80,
    "cyclomaticComplexity": 30
  }
}
```

- **`ignoreIssues`**: Exact issue message strings to suppress. Matches the complete message text, not short codes. Run the linter first to find exact strings.
- **`metricLimits`**: Override default metric thresholds. Keys are optional; unspecified keys use defaults from `Analyzer` constants. See [Metrics](metrics.md) for all available keys.

### `.phplintignore` (File Exclusion)

Gitignore-style file in the repo root. Loaded by `IgnoreList` (`src/IgnoreList.php`).

```text
# Ignore third-party and configuration files
config/*.php

# Ignore build artifacts
build/*.tmp.php
```

- Lines starting with `#` are comments
- `*` matches any number of characters
- `?` matches a single character
- Patterns are converted to regex internally

The linter's own `.phplintignore` excludes `tests/` from self-analysis.

## PHP Tooling

### php-cs-fixer (`.php-cs-fixer.php`)

- Base: `@PER-CS` (latest PER revision)
- Strict types: `nullable_type_declaration_for_default_null_value`
- Architecture: `class_attributes_separation`, `ordered_class_elements` (traits → constants → properties → constructor → methods)
- Imports: `no_unused_imports`, `import_classes/constants/functions`
- Short array syntax
- Scans `src/` and `tests/`, ignores VCS and dot files

### PHPStan (`phpstan.neon.dist`)

- Level 8 (strictest)
- PHP 8.3-8.5
- Scans `bin/php-linter`, `src/`, `tests/`
- Excludes `var/`, `vendor/`
- Includes `shipmonk/dead-code-detector` rules

### Rector (`rector.php`)

- PHP 8.3 target
- Prepared sets: deadCode, codeQuality, codingStyle, typeDeclarations, earlyReturn, phpunitCodeQuality
- Level set: `UP_TO_PHP_85`
- Composer-based: Symfony
- Custom rule: `DeclareStrictTypesRector`
- Skips: `DisallowedEmptyRuleFixerRector`
- Import names with `removeUnusedImports: true`
- No parallel execution

### Mago (`mago.toml`)

Carthage Software's PHP linter/formatter. Used as an additional quality gate.

- PHP 8.3, version 1
- Source: `src/`, `tests/`, includes `vendor/`
- Integrations: Symfony, PHPUnit
- Key rules: cyclomatic complexity threshold 25, excessive parameters threshold 10, Halstead thresholds, too-many-methods/properties threshold 40
- Assertion style: `this`
- Analyzer: `find-unused-definitions: true`, `analyze-dead-code: false`

Recent git history shows the project migrated from php-cs-fixer-only to also using Mago. Several commits adjust `mago.toml` configuration (strict mode, assertion style, rule tuning).

## Testing

### PHPUnit (`phpunit.xml.dist`)

- Single testsuite: `Unit` (directory: `tests/Unit`)
- Bootstrap: `vendor/autoload.php`
- Cache: `var/cache/.phpunit.cache`
- Execution order: depends, defects
- Strict about coverage metadata and output
- Fail on all issues
- Environment: `APP_ENV=testing`, `display_errors=1`, `error_reporting=-1`, `memory_limit=512M`

### Test Structure

```
tests/Unit/
  ConfigTest.php              — Config loading and parsing
  DocStandardsCheckerTest.php — Markdown validation
  IgnoreListTest.php          — .phplintignore pattern matching
  IssueHolderTest.php         — Issue collection and suppression
  RepositoryTest.php          — Git file listing
  Linter/
    Checker/                  — One test per checker (8 tests)
    Visitor/                  — One test per visitor (5 tests)
    ComposerFileTest.php      — PSR-4 mapping
    ElementVisitorTest.php    — ElementVisitor integration
    LinterTest.php            — Linter orchestration
  Metrics/
    AnalyzerTest.php          — Metric analysis
    CacheManagerTest.php      — Cache directory management
    MetricCheckerTest.php     — Threshold checking
    MetricDataTest.php        — Data transfer object
    MetricGeneratorTest.php   — PDepend execution
    XmlParserTest.php         — XML parsing
```

Tests require git context — the `Repository` class shells out to `git`, so tests fail outside a repository.

## CI

### OpenWiki Workflow (`.github/workflows/openwiki-update.yml`)

Scheduled GitHub Actions workflow that regenerates the OpenWiki documentation. Do not hand-edit generated pages under `openwiki/` unless explicitly asked; prefer updating source code/docs and letting OpenWiki regenerate.

## Key Constraints

- **Must run from repo root** — `getcwd()`, `git ls-files`, `composer.json`, `var/cache/pdepend`
- **Tests require git context** — `Repository` shells out to `git`
- `var/` is gitignored (pdepend, phpunit, php-cs-fixer caches)
- `composer.lock` is gitignored but checked in
- `package-lock.json` is gitignored and not checked in
- PHP `platform.php: "8.3"` — won't install on older PHP
