---
title: Code linter
description: A static analysis and metrics tool for PHP based on the Nikic PHP parser and PDepend
tags: [php, linter, static-analysis, ast, code-quality, metrics]
audience: Developers
last_updated: 2026-02-24
reading_time: 8 min
---

# Code linter

Code linter is a static analysis and metrics evaluation tool for PHP codebases. It uses the
`nikic/php-parser` library to generate an Abstract Syntax Tree (AST) for deep stylistic analysis,
and it wraps `pdepend/pdepend` to evaluate software metrics and code complexity.

## Why Code linter

This project is being developed as a potential replacement for
[PHP Mess Detector](https://phpmd.org/) (PHPMD). Compared to PHPMD, this project:

- **Offers more metrics:** Utilizing PDepend, it tracks advanced metrics including Code Rank,
  Afferent/Efferent Coupling, Comment to Code Ratio, Halstead Effort, Maintainability Index, and
  Lines of Code per File.
- **Provides predefined settings:** Thresholds for each metric are predefined mostly by a study of
  PHP code. The limits are set at a level at which your code exceeds 99% of the metrics of similar
  open-source code.
- **Promotes a better workflow:** PHPMD allows you to trigger an error, suppress it, and ignore it
  forever. Code linter, instead, provides an advisory report every time without offering inline
  suppressions. The problem with PHPMD's approach is that once you suppress an error, your code can
  grow in complexity without limits without getting further warnings.
- **Processes more code:** PHPMD relies solely on the parse tree provided by PDepend, which
  completely ignores code outside of classes and functions. Code linter solves this by running the
  PDepend check _and_ a separate linting check using the Nikic PHP Parser that analyzes your entire
  codebase, including standalone files.

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

Execute the script from the root directory of your repository.

```bash
vendor/bin/php-linter
```

> **IMPORTANT:** You must run the script from the repository root. The linter relies on
> `git ls-files`, parses the `composer.json` file for PSR-4 namespace mappings, and utilizes a
> `var/cache/pdepend` directory for caching metric analysis.

### Add to Composer scripts

To integrate the linter into your continuous integration (CI) pipeline or daily workflow, add it to
the scripts section of your `composer.json` file:

```json
{
  "scripts": {
    "lint": ["vendor/bin/php-linter"]
  }
}
```

Run the configured script with:

```bash
composer lint
```

## Checked metrics

The analyzer enforces limits on various software metrics. Exceeding these thresholds indicates code
that may be overly complex, tightly coupled, or difficult to maintain.

### Class-level metrics

- **Class Size:** Max 60 (Total number of methods and properties).
- **Lines of Code (Class):** Max 1,100.
- **Code Rank:** Max 2.0 (Measures class centrality and responsibility).
- **Properties:** Max 25 total properties, and Max 30 non-private properties.
- **Public Methods:** Max 40.
- **Afferent Coupling:** Max 45 (Incoming dependencies).
- **Efferent Coupling:** Max 24 (Outgoing dependencies).
- **Object Coupling:** Max 24 (Coupling between objects).
- **Inheritance Depth:** Max 5.
- **Child Classes:** Max 35.

### Method and Function-level metrics

- **Lines of Code (Method):** Max 130.
- **Cyclomatic Complexity:** Max 25 (Extended cyclomatic complexity).
- **NPath Complexity:** Max 10,000 (Acyclic execution paths).
- **Halstead Effort:** Max 135,000.
- **Maintainability Index:** Min 25 (Code falls below this limit is considered hard to maintain).

### File-level metrics

- **Comment to Code Ratio:** Min 0.05 (5% of code should be documented).
- **Lines of Code (Standalone files):** Max 200 (For lines executed outside of classes and
  functions).

## Checked linting issues

The AST linter analyzes your code for the following stylistic and structural issues:

### Naming conventions

- **CamelCase usage:** Classes, interfaces, and traits must use `UpperCamelCase`. Methods,
  functions, and variables must use `lowerCamelCase`.
- **Constants:** Must be in `ALL_CAPS`.
- **Name length:** Global names (classes/methods) should be 3–32 characters. Local variables should
  be 1–24 characters.
- **PSR suffixes/prefixes:** Abstract classes must be prefixed with `Abstract`, traits must be
  suffixed with `Trait`, and interfaces must be suffixed with `Interface`.
- **Boolean naming:** Boolean return functions should start with declarative verbs (e.g., `is`,
  `has`, `can`).
- **Verb-based naming:** Non-boolean functions should start with an imperative verb.

### Code structure & PSR standards

- **PSR-1 compliance:** Checks for constants and functions that should be moved from the top-level
  namespace to a class namespace.
- **PSR-4 compliance:** Verifies that the file path matches the class namespace and name based on
  `composer.json`.
- **Visibility order:** Properties and methods must be ordered by visibility: `public`, then
  `protected`, then `private`.
- **Member ordering:** Properties must be defined before methods.

### Best practices & Modernization

- **DTO suggestions:** Identifies arrays accessed with string keys as parameters or return types and
  suggests using Data Transfer Objects (DTOs) instead.
- **PHP 4 Constructors:** Flags old-style constructors (methods named after the class).
- **Strict Loading:** Suggests `require_once` over `include` or `require`.
- **Superglobals:** Flags use of superglobal variables outside of allowed contexts (like
  Controllers, Middleware, or global scope).

### Potential bugs & Cleanup

- **Unused code:** Detects unused private properties, unused private methods, and unused function
  parameters.
- **Redundant variables:** Flags variables that are assigned but only used once.
- **Magic numbers:** Detects duplicate numeric literals used across the code and suggests defining
  them as constants.
- **Empty blocks:** Flags empty `catch` blocks that suppress errors.

### Security & Forbidden syntax

- **Dangerous functions:** Flags the use of `eval()`.
- **Global scope:** Flags the use of the `global` keyword.
- **Unstructured code:** Flags the use of `goto` statements.
- **Error suppression:** Flags the use of the `@` operator.
- **Execution flow:** Flags `exit` or `die` inside functions/classes, suggesting exceptions instead.
- **Debug leftovers:** Flags common debug calls like `var_dump()`, `print_r()`, or
  `debug_backtrace()`.

### Repository health

- **Git branch:** Verifies the default repository branch is named `main`.

### PHPDoc Standards

- **Missing Documentation:** Public API elements (classes, interfaces, traits, enums, public methods, and public properties) must have a DocBlock.
- **Summary Formatting:** DocBlocks must start with a summary line under 80 characters, starting with a capital letter and ending with a period.
- **Mandatory Tags:**
    - Classes must have `@package`, `@since`, and either `@api` or `@internal`.
    - Methods must have `@param` tags matching the function signature and `@return` for non-void methods.
    - Properties without native types must have a `@var` tag.
- **Tag Ordering:** Tags must follow a specific order (e.g., `@api` before `@param`).
- **Complex Types:** Bare `array` types are forbidden; use typed generics syntax (e.g., `list<string>`, `array<string, int>`).

## Configure the ignore list

The linter supports an ignore list similar to `.gitignore`. Create a `.phplintignore` file in the
root of your repository to exclude specific paths or files from analysis.

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

## Configure error ignoring

Create a `php-linter.json` file in the root of your repository to configure the linter. This file supports two main configuration options: ignoring specific issue types and customizing metric limits.

### Configuration structure

The configuration file contains two optional arrays:

- **`ignoreIssues`**: An array of issue strings to ignore. The strings must match the exact issue text reported by the linter.
- **`metricLimits`**: An object mapping metric names to custom limit values. These override the default constants defined in the Analyzer class.

```json
{
  "ignoreIssues": [
    "Remove unused private non-static method MyClass::unusedMethod() to reduce dead code.",
    "Remove unused parameter \"paramName\" from function \"myFunction()\"; it is defined but not used in the function body.",
    "Replace the magic number 42 with a named constant. It appears 3 times on lines 10, 25, 30. Centralizing this value improves maintainability and readability."
  ],
  "metricLimits": {
    "classSize": 80,
    "classLoc": 1500,
    "methodLoc": 200,
    "cyclomaticComplexity": 30,
    "npathComplexity": 15000,
    "halsteadEffort": 200000,
    "maintainabilityIndex": 20,
    "commentRatio": 0.03,
    "properties": 30,
    "nonPrivateProps": 35,
    "publicMethods": 50,
    "afferentCoupling": 60,
    "efferentCoupling": 30,
    "objectCoupling": 30,
    "inheritanceDepth": 6,
    "childClasses": 50,
    "codeRank": 3.0,
    "fileLoc": 300
  }
}
```

**Note on ignoreIssues:** The ignore list matches the complete issue message text, not short codes. To find the exact strings to ignore, run the linter first and copy the issue messages you want to suppress into the `ignoreIssues` array. When an issue message is listed in `ignoreIssues`, all instances of that exact issue will be suppressed from the output.

**Note on metricLimits:** All metric limit keys are optional. If a key is not specified, the linter uses its default value (the class constant defined in the Analyzer). The example above shows all available metric limit keys with their default values.

### Custom configuration file path

When using the linter programmatically, you can specify a custom path to the configuration file:

```php
use DouglasGreen\PhpLinter\Config;

// Use default php-linter.json in current directory
$config = new Config($currentDir);

// Use a custom configuration file
$config = new Config($currentDir, '/path/to/custom-config.json');
```

## Disclaimer

This project is not affiliated with or endorsed by the PHP Group.
