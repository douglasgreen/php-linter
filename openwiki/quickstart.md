# Quickstart

Code linter is a static analysis and metrics evaluation tool for PHP codebases. It uses
`nikic/php-parser` to generate an AST for deep stylistic linting, and wraps `pdepend/pdepend` to
evaluate software metrics and code complexity. It is designed as a potential replacement for
[PHPMD](https://phpmd.org/).

## What It Does

The linter runs a 7-stage analysis pipeline against your repository:

1. **Metrics analysis** — PDepend-based complexity, coupling, and maintainability metrics
2. **AST linting** — Naming conventions, code structure, best practices, security checks
3. **Unused class analysis** — Detects unused classes, interfaces, and traits
4. **Unused function analysis** — Detects unused functions and methods
5. **Composer.json validation** — Structure, dependencies, security, and key ordering
6. **Package.json validation** — Structure, dependencies, cross-file consistency
7. **Documentation standards** — Markdown structure, links, writing style, security

All issues are collected into a single advisory report. Unlike PHPMD, there are no inline
suppressions — problems can only be ignored via project-level configuration.

## Prerequisites

- PHP 8.3 or later
- Composer
- Git repository (the linter requires a Git context for file listing and branch verification)

## Install

```bash
composer require --dev douglasgreen/php-linter
```

## Run

From the repository root:

```bash
vendor/bin/php-linter
```

> **Must run from the repo root.** The tool calls `getcwd()`, runs `git ls-files`, parses
> `composer.json` for PSR-4 mappings, and uses `var/cache/pdepend` for caching.

### Sort JSON Files

```bash
vendor/bin/php-linter --fix
```

When `--fix` is provided, only `composer.json` and `package.json` are sorted into conventional key
order. All other checks are skipped.

### Composer Scripts

```json
{
  "scripts": {
    "lint": ["vendor/bin/php-linter"]
  }
}
```

```bash
composer lint
```

## Configuration

Two optional configuration files in the repo root:

- **`.phplintignore`** — Gitignore-style patterns to exclude files from analysis
- **`php-linter.json`** — Suppress specific issue messages (`ignoreIssues`) and override metric
  thresholds (`metricLimits`)

See [Operations](operations.md) for details.

## Development Commands

```bash
composer lint:fix    # php-cs-fixer + rector (run before committing)
composer lint        # php-cs-fixer (dry-run) + phpstan + rector (dry-run)
composer test        # phpunit (Unit suite)
composer qa          # lint + test
npm run lint         # markdownlint
npm run format       # prettier + markdownlint --fix
```

**Order:** run `composer lint:fix` before `composer lint` and `composer test`.

## Documentation Sections

| Section | What It Covers |
|---------|---------------|
| [Architecture Overview](architecture/overview.md) | Pipeline design, shared infrastructure, data flow, dependencies |
| [Source Map](architecture/source-map.md) | Directory-by-directory guide to every source file |
| [Linting Rules](linting-rules.md) | AST-based lint rules: naming, structure, security, PHPDoc, unused code |
| [Metrics](metrics.md) | PDepend metrics, default thresholds, caching, XML parsing |
| [Standards Checkers](standards-checkers.md) | Composer.json, package.json, and documentation standards |
| [Operations](operations.md) | Dev commands, configuration, ignore lists, tooling, testing, CI |

## Key Design Decisions

- **No inline suppressions.** PHPMD allows suppressing errors inline, which means code can grow in
  complexity without further warnings. This tool provides an advisory report every time and requires
  project-level configuration to ignore issues.
- **Processes all code.** PHPMD only analyzes code inside classes and functions. This tool runs both
  PDepend and a separate nikic/php-parser pass that analyzes the entire codebase, including
  standalone files.
- **Predefined thresholds.** Metric limits are set at the 99th percentile of similar open-source
  PHP code, not arbitrary defaults.

## Key Constraints

- **Must run from repo root.** The tool calls `getcwd()`, `git ls-files`, reads `composer.json`, and
  uses `var/cache/pdepend`.
- **Tests require git context.** `Repository` class shells out to `git`. Tests fail outside a repo.
- `tests/` is listed in `.phplintignore` — the linter does not scan its own tests.
- `var/` is gitignored and used for all caches (pdepend, phpunit, php-cs-fixer).
- `composer.lock` is gitignored but checked in; `package-lock.json` is gitignored and not checked in.
- PHP config `platform.php: "8.3"` — won't install on older PHP, even for dev.
