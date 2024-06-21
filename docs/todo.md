# To-do List

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
