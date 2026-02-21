# code-linter

Code linter for PHP based on Nikic parser

## Setup

Add the project with Composer.

```
composer require douglasgreen/code-linter
```

Linter for PHP

This project is being developed as a replacement for
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

-   `bin/code_linter.php` - Runs style checks using
    [Nikic PHP Parser](https://github.com/nikic/PHP-Parser).

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


# To-do List

This project is a replacement for PHP Mess Detected (PHPMD).

## Structure

PHPMD uses the AST from PDepend to apply metrics. Unfortunately this is a bad design. PDepend only
measures the complexity inside code units like classes and methods. It doesn't measure anything in
standalone files that is not in a class or method. PHP isn't like Java and that it allows unlimited
amounts of standalone code that is not in classes. Because PHPMD depends on PDepend, it has the same
limitations.

PHPMD also offers limited access to the metrics of PDepend.

A better design is to use PDepend only for its measurements and then use Nikic Parser to parse the
rest of the code and look for lint issues. This also enables the PDepend measurements to be cached.

## PDepend

Use PDepend to generate and cache metrics.

1. Make a list of each PHP directory.
2. Run PDepend on each directory one at a time and cache the results.
3. Generate summary statics of normal metrics.
4. Use it to reject above 95% for each value.

## Warning vs. Error

PHPMD gives warnings too frequently and then they are disabled. Once you exceed the threshold, your
code can grow without limit and not have any further warnings.

There should be a warning level (> 95%) and an error level (> 99.5%).

## Values

Update the values with vendor files (vendor.xml).

## Config File

Transfer the values to a config file and install with config-setup.

## Docs

Write documentation

## Update

Add an update flag(?) to update the cache.

## Linter

Remove extra lines from linter and finish.

## Cache

Give `check-style` its own cache file and update `php-linter`.

## Variable metric

Complexity is in the state manipulation, not the structures. Check the local variable count.

## Explanations

### Boolean function names

Booleans should all be named with a declarative verb as if they're answering a yes or no question.
So you shouldn't using imperative verb like check or validate. Instead:

-   Use a quality like isValid()
-   Check for success like canStop()
-   Express a goal like shouldAccept() or shouldUse().

### Name printer

Write a name printer for $var and func(), etc.

### Automatic update of staged/changed files

When check-metrics is run, make a list of staged/changed files. If any are newer than the staged.xml
cache, update the cache with just those files. Use it instead of summary.xml for those files.

### Caching for check-style

Save errors/code info in a cache file for check-style.

### Functions

Give warning to move functions and constants into classes.

### More checks

-   LCOM4
-   Switch - break or // fallthru
-   No known abbrev in func/class name
-   Recommend static for non-$this
-   No static mutation
-   Yes readonly when only one assign
-   Suggest DI when new used
-   Magic number is anything but 1 digit or 1 digit repeated
-   @param/@return of array with named key - change to object?
-   @order alphabetical, call, typical
-   File, class, trait, functions besides get/set/construct/destruct/test require comments
-   Intro order: file comment, declare, namespace, use, require

### Composer/Package Linter

Add linters for composer.json and package.json.

### DI checker

Mark every class that uses a resource as @di.

Allow user to mark classes with @di meaning "this class should be injected".

Check every usage of @di classes and resources in functions that are not in @di classes. Meaning a
@di class can contain resources.

### Namespaces

Namespaces should be non-overlapping. This agrees with directory structure.

Src: <owner>\<project>

Src dir: src/ for simple project, src/<project> for multi-project

Tests: <owner>\Tests\<project>

Test dir: tests/ for simple project, tests/<project> for multi-project.
