---
title: Code linter
description: A static analysis tool for PHP based on the Nikic PHP parser
tags: [php, linter, static-analysis, ast, code-quality]
audience: Developers
last_updated: 2026-02-21
reading_time: 4 min
---

# Code linter

Code linter is a static analysis tool for PHP codebases. It uses the `nikic/php-parser` library to generate an Abstract Syntax Tree (AST) of your project and performs deep analysis on code structure, naming conventions, and stylistic rules.

Unlike partial parsers, Code linter processes all PHP files in your repository, including standalone scripts. This approach ensures consistent code quality and verifies that your codebase adheres to PSR-4 autoloading standards.

## Prerequisites

- PHP 8.3 or later
- Composer
- Git repository (the linter requires a Git context to verify the main branch and file index)

## Install the linter

Add the package to your project as a development dependency using Composer:

```bash
composer require --dev douglasgreen/code-linter
```

## Run the linter

Execute the linter script from the root directory of your repository. 

```bash
vendor/bin/code_linter.php
```

> **IMPORTANT:**
> You must run the script from the repository root. The linter relies on `git ls-files` and parses the `composer.json` file to validate PSR-4 namespace mappings against your directory structure.

### Add to Composer scripts

To integrate the linter into your continuous integration (CI) pipeline or daily workflow, add it to the scripts section of your `composer.json` file:

```json
{
  "scripts": {
    "lint": [
      "vendor/bin/code_linter.php"
    ]
  }
}
```

Run the configured script with:

```bash
composer lint
```

## Configure the ignore list

The linter supports an ignore list similar to `.gitignore`. Create a `.phplintignore` file in the root of your repository to exclude specific paths or files from analysis.

### Syntax rules

- Lines starting with `#` act as comments.
- `*` matches any number of characters.
- `?` matches a single character.

### Example configuration

```text
# Ignore third-party and configuration files
config/*.php

# Ignore build artifacts
build/*.tmp.php
```

## To-do list

The following features, fixes, and enhancements guide the future development of this project.

### Architecture and performance

- Add a configuration file format (e.g., `linter.xml` or `linter.json`) to customize rule thresholds and toggle specific checks.
- Implement a result caching mechanism to skip unmodified files during consecutive runs.
- Add an update flag to process only Git staged or modified files.

### Code quality rules (from codebase comments)

- Implement structural validation checks for classes and traits (`src/Checker/ClassChecker.php`).
- Update comment parsing logic so it does not falsely identify email addresses as PHPDoc tags (`src/Checker/CommentChecker.php`).
- Verify that getter methods return a value and setter methods return `void` (`src/Checker/FunctionChecker.php`).
- Validate contextual removal of redundant class suffixes like "Manager" or "Handler" without triggering false positives (`src/ElementVisitor.php`).

### Advanced static analysis

- Analyze `switch` statements for missing `break` statements or `// fallthru` comments.
- Recommend the `static` keyword for methods that do not reference `$this`.
- Enforce the `readonly` keyword on properties assigned only once.
- Suggest Dependency Injection (DI) instead of using the `new` keyword inside methods.
- Detect magic numbers (numeric literals other than 0, 1, or repeated digits).
- Recommend converting associative array parameters and return types into dedicated Data Transfer Objects (DTOs).
- Enforce logical code ordering: alphabetical, call order, or standard visibility order (constants, properties, constructor, public methods, private methods).
- Calculate Lack of Cohesion of Methods (LCOM4) to identify classes that require splitting.
- Recommend Dependency Injection annotations (`@di`) for classes managing external resources.
- Warn when developers should move standalone functions or constants into classes.

### Ecosystem integration

- Provide custom linter rules for `composer.json` and `package.json` file structures.
- Analyze namespaces to guarantee non-overlapping structures that strictly match the directory tree.
