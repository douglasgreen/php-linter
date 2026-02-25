# php-linter

Linter for PHP

This project is being developed as a potential replacement for
[PHP Mess Detector](https://phpmd.org/) (PHPMD).That project is also a wrapper for PDepend. Compared
to PHPMD, this project:

-   Offers more metrics from PDepend, including Code Rank, Afferent Coupling, Efferent Coupling,
    Comment to Code Ratio, Halstead Effort, Maintainability Index, and Lines of Code per File.
-   Provides predefined settings for each metric defined mostly by a study of PHP code. The settings
    are set at a level, at which your code exceeds 99% of the metrics of similar code.
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

