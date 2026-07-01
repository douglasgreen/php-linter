# AGENTS.md

PHP 8.3+ static analysis tool using nikic/php-parser (AST linting) + pdepend (metrics).

## Commands

```bash
composer lint       # php-linter + php-cs-fixer (dry-run) + phpstan (level 8) + rector (dry-run)
composer lint:fix   # php-cs-fixer fix + rector fix — run this before committing
composer test       # phpunit (Unit suite only), requires git context
composer test:unit  # same as above
npm run lint        # markdownlint on all *.md
npm run format      # prettier + markdownlint --fix

composer qa         # lint + test in sequence
```

**Order:** run `composer lint:fix` before `composer lint` and `composer test`.

## Architecture

Single-package library, namespace `DouglasGreen\PhpLinter\`.

| Dir | Purpose |
|-----|---------|
| `bin/php-linter` | CLI entry point, chains all checks |
| `src/Linter/` | AST-based linting via nikic/php-parser visitors |
| `src/Linter/Checker/` | Individual lint rule checkers |
| `src/Linter/Visitor/` | AST node visitors |
| `src/Linter/PhpDoc/` | PHPDoc analysis |
| `src/Metrics/` | pdepend-based complexity/coupling/etc. analysis |
| `src/` (top-level) | ComposerChecker, DocStandardsChecker, PackageJsonChecker, Config, IssueHolder, IgnoreList, Repository |

## Key constraints

- **Must run from repo root.** The tool calls `getcwd()`, `git ls-files`, reads `composer.json`, and uses `var/cache/pdepend`.
- **Tests require git context.** `Repository` class shells out to `git`. Tests fail outside a repo.
- `tests/` is listed in `.phplintignore` — the linter does not scan its own tests.
- `var/` is gitignored and used for all caches (pdepend, phpunit, php-cs-fixer).
- `composer.lock` is gitignored but checked in; `package-lock.json` is gitignored and not checked in.
- PHP config `platform.php: "8.3"` — won't install on older PHP, even for dev.

## Tooling

- **php-cs-fixer**: PSR-12 base, strict types, ordered class elements (traits→constants→properties→constructor→methods), no unused imports, import classes/constants/functions, short array syntax
- **phpstan**: level 8 (strictest), scans `src/` and `tests/`, excludes `var/` and `vendor/`
- **rector**: PHP 8.3, dead code + code quality + coding style + type declarations + early return + phpunit sets, Symfony composer-based rules, `DisallowedEmptyRuleFixerRector` skipped
- **markdownlint**: max line length 100, ATX headers, fenced code blocks
- **prettier**: 4-space indent, single quotes, trailing commas, LF endings; MD files overridden to 2-space/100-char print width
