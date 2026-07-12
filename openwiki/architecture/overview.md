# Architecture Overview

## Single-Package Library

The entire codebase is a single Composer package under namespace `DouglasGreen\PhpLinter\`. There is
no application framework — the tool is a CLI script that chains analysis stages and prints a report.

## Pipeline Design

The CLI entry point (`bin/php-linter`) orchestrates a linear pipeline of 7 analysis stages. All
stages share three infrastructure objects:

- **`IssueHolder`** — Collects issues keyed by file, with current file/class/function context.
  Supports exact-match suppression via `ignoreIssues` from `Config`.
- **`IgnoreList`** — Reads `.phplintignore` and provides `shouldIgnore($path)` for file-level
  exclusion using gitignore-style glob patterns converted to regex.
- **`Config`** — Loads `php-linter.json` for `ignoreIssues` (exact issue message strings) and
  `metricLimits` (override default metric thresholds).

Each stage receives one or more of these objects and calls `IssueHolder::addIssue()` when a problem
is found. The final `printIssues()` call outputs the complete report.

```
bin/php-linter
  │
  ├─ Config ─────────── php-linter.json
  ├─ IssueHolder ────── collects all issues
  ├─ Repository ─────── git ls-files → PHP file list
  ├─ IgnoreList ─────── .phplintignore
  │
  ├─ Stage 1: Metrics\Analyzer ─────── PDepend XML → MetricChecker
  ├─ Stage 2: Linter\Linter ────────── nikic/php-parser AST → ElementVisitor
  ├─ Stage 3: Linter\UnusedClassAnalyzer
  ├─ Stage 4: Linter\UnusedFunctionAnalyzer
  ├─ Stage 5: ComposerChecker ──────── composer.json validation + --fix sorting
  ├─ Stage 6: PackageJsonChecker ───── package.json validation + --fix sorting
  └─ Stage 7: DocStandardsChecker ──── Markdown validation (CommonMark parser)
```

## Data Flow

### Metrics Path

1. `Repository::getPhpFiles()` returns PHP files from `git ls-files`
2. `CacheManager` ensures `var/cache/pdepend/` exists
3. `MetricGenerator` runs `vendor/bin/pdepend --summary-xml=...` on PHP directories
4. `XmlParser` loads the summary XML into structured arrays (packages, classes, files)
5. `Analyzer` iterates packages/classes/files and creates `MetricChecker` instances
6. `MetricChecker` compares each metric against limits (from constants or `Config::getMetricLimits()`)
7. Violations are added to `IssueHolder`

### Linting Path

1. `Linter::run()` parses each PHP file with `nikic/php-parser`
2. `ElementVisitor` (extends `NodeVisitorAbstract`) traverses the AST
3. ElementVisitor delegates to sub-visitors (`ClassVisitor`, `FunctionVisitor`,
   `MagicNumberVisitor`, `SuperglobalUsageVisitor`) and checkers (`NameChecker`,
   `FunctionChecker`, `DocBlockChecker`, `ExpressionChecker`, etc.)
4. Checkers are instantiated per-node with the current `IssueHolder`
5. Issues are collected and printed at the end

### Unused Code Path

1. `UnusedClassAnalyzer` and `UnusedFunctionAnalyzer` each do a two-pass traversal:
   - First pass: collect all definitions (classes, interfaces, traits, functions)
   - Second pass: collect all usages (instantiation, static calls, function calls)
2. Definitions with no usages are reported as issues

### File Checker Path

1. `ComposerChecker` validates `composer.json` structure, dependencies, and security
2. `PackageJsonChecker` validates `package.json` with cross-file consistency checks
3. Both support `--fix` mode to sort keys into conventional order
4. `DocStandardsChecker` uses League\CommonMark to parse Markdown and validates structure,
   links, writing style, and security (exposed credentials)

## Key Infrastructure Classes

| Class | Location | Role |
|-------|----------|------|
| `Repository` | `src/Repository.php` | Wraps `git ls-files`, file type detection |
| `Config` | `src/Config.php` | Loads `php-linter.json` |
| `IgnoreList` | `src/IgnoreList.php` | Loads `.phplintignore`, glob-to-regex matching |
| `IssueHolder` | `src/IssueHolder.php` | Central issue collection with context and suppression |
| `ComposerFile` | `src/Linter/ComposerFile.php` | PSR-4 namespace-to-path mapping from `composer.json` |

## Dependencies

| Package | Purpose |
|---------|---------|
| `nikic/php-parser` | AST generation for linting |
| `pdepend/pdepend` | Software metrics generation |
| `phpstan/phpdoc-parser` | PHPDoc tag parsing for DocBlock checks |
| `league/commonmark` | Markdown parsing for documentation checks |
| `justinrainbow/json-schema` | JSON schema validation for composer.json |
| `symfony/yaml` | YAML parsing (used in config/tooling) |

## When to Modify What

- **New lint rule** → Add a checker in `src/Linter/Checker/` or a visitor in
  `src/Linter/Visitor/`, wire it into `ElementVisitor`
- **New metric** → Add a constant + check method in `Metrics/Analyzer` and `Metrics/MetricChecker`
- **New file-type checker** → Add a top-level class in `src/` and a stage in `bin/php-linter`
- **Configuration option** → Extend `Config.php` and wire it through the relevant stage

## Source Map

For a file-by-file guide, see [Source Map](source-map.md).

## Source Map

| Path | Purpose |
|------|---------|
| `bin/php-linter` | CLI entry point; orchestrates all 7 stages |
| `src/Repository.php` | Wraps `git ls-files`, file type detection |
| `src/Config.php` | Loads `php-linter.json` (ignoreIssues, metricLimits) |
| `src/IssueHolder.php` | Central issue collection with context and suppression |
| `src/IgnoreList.php` | Loads `.phplintignore`, glob-to-regex matching |
| `src/ComposerChecker.php` | composer.json validation + `--fix` key sorting |
| `src/PackageJsonChecker.php` | package.json validation + `--fix` key sorting |
| `src/DocStandardsChecker.php` | Markdown documentation standards checking |
| `src/Linter/Linter.php` | Orchestrates AST parsing and traversal |
| `src/Linter/ElementVisitor.php` | Central AST visitor dispatching to all checkers |
| `src/Linter/ComposerFile.php` | PSR-4 namespace-to-path resolution from composer.json |
| `src/Linter/UnusedClassAnalyzer.php` | Two-pass unused class/interface/trait detection |
| `src/Linter/UnusedFunctionAnalyzer.php` | Two-pass unused function detection |
| `src/Linter/PhpDoc/ParserFactory.php` | PHPDoc lexer/parser factory |
| `src/Linter/Checker/AbstractNodeChecker.php` | Base class for node checkers |
| `src/Linter/Checker/NameChecker.php` | Naming convention validation |
| `src/Linter/Checker/FunctionChecker.php` | Function/method best practices |
| `src/Linter/Checker/DocBlockChecker.php` | PHPDoc compliance |
| `src/Linter/Checker/ExpressionChecker.php` | Security and forbidden syntax |
| `src/Linter/Checker/FunctionCallChecker.php` | Function call pattern checks |
| `src/Linter/Checker/LocalScopeChecker.php` | Scope-related checks |
| `src/Linter/Checker/OperatorChecker.php` | Operator-related checks |
| `src/Linter/Checker/TryCatchChecker.php` | Empty catch block detection |
| `src/Linter/Visitor/AbstractVisitorChecker.php` | Base class for sub-visitors |
| `src/Linter/Visitor/ClassVisitor.php` | Class-level unused member and ordering checks |
| `src/Linter/Visitor/FunctionVisitor.php` | Function-level variable usage checks |
| `src/Linter/Visitor/MagicNumberVisitor.php` | Duplicate numeric literal detection |
| `src/Linter/Visitor/SuperglobalUsageVisitor.php` | Superglobal usage validation |
| `src/Metrics/Analyzer.php` | PDepend metric analysis orchestration + threshold constants |
| `src/Metrics/CacheManager.php` | Manages `var/cache/pdepend/` directory |
| `src/Metrics/MetricGenerator.php` | Runs PDepend subprocess |
| `src/Metrics/XmlParser.php` | Parses PDepend summary XML |
| `src/Metrics/MetricChecker.php` | Compares metrics against thresholds |
| `src/Metrics/MetricData.php` | Data object holding metric values |
| `tests/Unit/` | PHPUnit tests mirroring src/ structure |

## Related Pages

- [Linting Rules](../linting-rules.md) — AST lint rules, visitors, and checkers in detail
- [Metrics](../metrics.md) — PDepend metrics, thresholds, and caching
- [Standards Checkers](../standards-checkers.md) — Composer, package.json, and doc validation
- [Operations](../operations.md) — Configuration, tooling, testing, and CI
