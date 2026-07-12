# Standards Checkers

Three checker classes run as stages 5-7 of the analysis pipeline, validating project configuration files and documentation standards.

## ComposerChecker (`src/ComposerChecker.php`, 40 KB)

Validates `composer.json` against Composer best practices and project standards. Uses `justinrainbow/json-schema` for schema validation.

### Checks Performed

- **Structure:** Required fields (`name`, `description`, `type`)
- **Package name:** Lowercase, hyphenated, `owner/package` format
- **Package type:** Validated against allowed types (library, project, composer-plugin, etc.)
- **License:** Presence and optionally validated against expected license
- **Keywords:** Presence, no duplicates, non-empty strings
- **PHP version:** Constraint present, minimum 8.3+
- **Autoload:** PSR-4 configuration and namespace conventions
- **Config:** `sort-packages` check, platform override warnings
- **Dependencies:** Version constraints, wildcard warnings, dev branch warnings
- **Scripts:** Security checks for dangerous commands in post-install/post-update
- **Binaries:** Files exist and are executable
- **Support:** Issues URL and valid source URLs
- **Public packages:** Additional validation for homepage, authors, keywords

### Key Ordering (`--fix` mode)

Defines a conventional key order (`KEY_ORDER` constant) based on the [Composer schema](https://getcomposer.org/doc/04-schema.md). Keys not in the standard order are appended at the end in their original relative order. Uses RFC 2119 levels (`MUST`, `SHOULD`, `MAY`).

## PackageJsonChecker (`src/PackageJsonChecker.php`, 50 KB)

Validates `package.json` with similar checks to ComposerChecker, plus cross-file consistency with `composer.json`.

### Checks Performed

- **Structure:** Required fields (`name`, `version`, `description`)
- **Package name:** Lowercase, hyphenated, `@scope/package` for scoped packages
- **Package type:** Validated against allowed types (module, commonjs, esm, cjs)
- **License:** Presence and cross-file consistency with composer.json
- **Keywords:** Presence, no duplicates, no forbidden generic terms
- **Engines:** Node.js and npm version constraints specified and meet minimums
- **Dependencies:** Dev tools in devDependencies, wildcard warnings, version inconsistencies
- **Scripts:** Standard scripts (lint, test, format) when tools are installed, dangerous command detection
- **Binaries:** Files exist, executable, in `bin/` directory
- **Exports:** Recommends exports field for ES modules
- **Deprecated configs:** Flags legacy config files (eslintrc, tslint, etc.)
- **File locations:** Files in appropriate directories based on type
- **Tooling configs:** Prettier, ESLint, Stylelint configuration validation
- **Cross-file consistency:** Project name, description, license compared with composer.json
- **Public packages:** Homepage, repository, author, contributors validation

### Key Ordering (`--fix` mode)

Similar to ComposerChecker, defines conventional key order for `package.json`.

## DocStandardsChecker (`src/DocStandardsChecker.php`, 20 KB)

Validates Markdown documentation using `league/commonmark` for parsing. Builds a link graph to detect orphaned files.

### Checks Performed

- **Required files:** README.md, LICENSE (or LICENSE.md/LICENSE.txt), AGENTS.md (or CLAUDE.md)
- **File naming:** Kebab-case for Markdown files
- **File encoding:** UTF-8, Unix line endings (LF)
- **Heading structure:** Single H1, no skipped levels, sentence case
- **Code blocks:** Language specification in fenced code blocks
- **Writing style:** Fluff words, passive voice, future tense
- **Links:** Internal link validation, bare URLs, non-descriptive link text
- **Security:** Detects exposed API keys, tokens, passwords, credentials
- **Orphaned files:** Markdown files not linked from other documentation

### Link Graph

The checker maintains a bidirectional link graph (`$linkGraph`) tracking outgoing and incoming links between Markdown files. Files with zero incoming links are flagged as orphans.

## `--fix` Mode

When `bin/php-linter` is called with `--fix`:
1. `ComposerChecker` and `PackageJsonChecker` sort their respective JSON files
2. All other checks are skipped
3. The script exits immediately after sorting

## Where to Add New Checks

- **New composer.json rule** → Add to `ComposerChecker`
- **New package.json rule** → Add to `PackageJsonChecker`
- **New Markdown rule** → Add to `DocStandardsChecker`
- Add corresponding tests in `tests/Unit/`
