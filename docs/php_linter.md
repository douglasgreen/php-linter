# php-linter

Linter for PHP

This project is being developed as a potential replacement for
[PHP Mess Detector](https://phpmd.org/) (PHPMD).That project is also a wrapper for PDepend. Compared
to PHPMD, this project:

-   Offers more metrics from PDepend, including Code Rank, Afferent Coupling, Efferent Coupling,
    Comment to Code Ratio, Halstead Effort, Maintainability Index, and Lines of Code per File.
-   Provides predefined settings for each metric defined mostly by a study of PHP code. The settings
    are split into warning level, at which your code exceeds 95% of the metrics of similar code, and
    error level, at which your code exceeds 99% of the metrics of similar code.
-   Has a different workflow. PHPMD has a workflow that you trigger the error, then suppress it and
    ignore it forever after. PHP Linter instead just triggers a report that is split into error and
    warning levels. It's just an advisory report but it always presents the same report without
    suppressing errors. The problem with PHPMD is that once you suppress the error you never see it
    again and your code can grow without limits without getting further warnings.
-   Processes more code. PHPMD depends on the parse tree provided by PDepend for all of its
    warnings. The problem is that PDepend only parses the code inside classes and functions. It
    completely ignores the rest of your code in standalone files so you're only doing a partial
    check. PHP Linter solves that problem by providing a PDepend check and a separate style check
    using Nikic PHP Parser that checks the whole code base.

## Usage

This project features two scripts to run project checks:

-   `bin/check-metrics` - Runs metric checks using [PDepend](https://pdepend.org/)
-   `bin/check-style` - Runs style checks using
    [Nikic PHP Parser](https://github.com/nikic/PHP-Parser). This part is still under development.

There is a third script, `bin/generate-metrics`, that updates the cache file at
`var/cache/pdepend/summary.xml` by running PDepend. This file is used by `check-metrics` for most of
its metrics so it should be run before running checks.

There is a fourth script, `bin/php-linter`, that runs the two check scripts. It also runs the third
script `generate-metrics` if you pass `--generate` or `-g` as an argument.

You can add the individual scripts or the combined linter script to your lint section in
composer.json:

```
   "scripts": {
        "lint": [
            "php-linter"
        ]
    }
```

I leave the `--generate` argument off here so the scripts don't run in Continuous Integration. I
just run them manually during development.

## Ignore list

### Overview

The ignore list allows you to specify patterns of files and directories to ignore, similar to a
`.gitignore` file. You only have to ignore PHP files that are contained in directories that are in
version control. You don't have to ignore the `vendor/` directory, for example, because it isn't in
version control.

### How It Works

1. **Loading the Ignore File**: The class reads the `.phplintignore` file, ignoring any lines that
   are comments or empty.
2. **Storing Patterns**: Valid ignore patterns are converted into regular expressions and stored
   internally.
3. **Checking Paths**: The class provides a method to check if a given file path should be ignored
   based on the stored patterns.

### Syntax

The `.phplintignore` file supports a simple syntax for specifying ignore patterns:

-   **Comments**: Lines starting with `#` are considered comments and are ignored.
-   **Wildcards**:
    -   `*` matches any number of characters (including none).
    -   `?` matches any single character.
-   **Examples**:
    -   `*.log` ignores all files with the `.log` extension.
    -   `build/*.tmp` ignores all `.tmp` files in the `build` directory.
    -   `config/*.php` ignores all `.php` files in the `config` directory.
