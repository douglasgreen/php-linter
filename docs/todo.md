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

## PHPMD

Remove PHPMD from config and replace with this.

## More metrics

Replace the https://phpmd.org/rules/codesize.html#excessiveparameterlist with a parameter count.

## Cache

Give `check_style.php` its own cache file and update `php-linter`.
