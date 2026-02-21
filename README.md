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
