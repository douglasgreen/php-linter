---
title: Code linter
description: A static analysis tool for PHP based on the Nikic PHP parser
tags: [php, linter, static-analysis, ast, code-quality]
audience: Developers
last_updated: 2026-02-21
reading_time: 6 min
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
composer require --dev douglasgreen/php-linter
```

## Run the linter

Execute the linter script from the root directory of your repository. 

```bash
vendor/bin/php-linter
```

> **IMPORTANT:**
> You must run the script from the repository root. The linter relies on `git ls-files` and parses the `composer.json` file to validate PSR-4 namespace mappings against your directory structure.

### Add to Composer scripts

To integrate the linter into your continuous integration (CI) pipeline or daily workflow, add it to the scripts section of your `composer.json` file:

```json
{
  "scripts": {
    "lint": [
      "vendor/bin/php-linter"
    ]
  }
}
```

Run the configured script with:

```bash
composer lint
```

## Checked issues

The linter analyzes your code for the following issues:

### Naming conventions
- **CamelCase usage:** Classes, interfaces, and traits must use `UpperCamelCase`. Methods, functions, and variables must use `lowerCamelCase`.
- **Constants:** Must be in `ALL_CAPS`.
- **Name length:** Global names (classes/methods) should be 3–32 characters. Local variables should be 1–24 characters.
- **Redundant suffixes:** Identifies redundant or leaky suffixes like `Abstract`, `Impl`, `Manager`, or `Helper`.
- **Boolean naming:** Boolean return functions should start with declarative verbs (e.g., `is`, `has`, `can`).
- **Verb-based naming:** Non-boolean functions should start with an imperative verb.

### Code structure & PSR standards
- **PSR-4 compliance:** Verifies that the file path matches the class namespace and name based on `composer.json`.
- **Visibility order:** Properties and methods must be ordered by visibility: `public`, then `protected`, then `private`.
- **Member ordering:** Properties must be defined before methods.
- **Namespace imports:** External classes must be imported via `use` statements rather than using fully qualified names inline.

### Best practices & Modernization
- **DTO suggestions:** Identifies arrays accessed with string keys as parameters or return types and suggests using Data Transfer Objects (DTOs) instead.
- **Static vs Instance:** Suggests making methods `static` if they do not use `$this`.
- **PHP 4 Constructors:** Flags old-style constructors (methods named after the class).
- **Strict Loading:** Suggests `require_once` over `include` or `require`.
- **Superglobals:** Flags use of superglobal variables outside of allowed classes.

### Potential bugs & Cleanup
- **Unused code:** Detects unused private properties, unused private methods, and unused function parameters.
- **Redundant variables:** Flags variables that are assigned but only used once.
- **Magic numbers:** Detects duplicate numeric literals used across the code and suggests defining them as constants.
- **Empty blocks:** Flags empty `catch` blocks that suppress errors.

### Security & Forbidden syntax
- **Dangerous functions:** Flags the use of `eval()`.
- **Global scope:** Flags the use of the `global` keyword.
- **Unstructured code:** Flags the use of `goto` statements.
- **Error suppression:** Flags the use of the `@` operator.
- **Execution flow:** Flags `exit` or `die` inside functions/classes, suggesting exceptions instead.
- **Debug leftovers:** Flags common debug calls like `var_dump()`, `print_r()`, or `debug_backtrace()`.

### Repository health
- **Git branch:** Verifies the default repository branch is named `main`.

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

## Disclaimer

This project is not affiliated with or endorsed by the PHP Group.
