# Metrics

The metrics subsystem wraps `pdepend/pdepend` to evaluate software metrics and code complexity. It operates as Stage 1 of the analysis pipeline, before AST linting.

## Pipeline

```
Analyzer.run()
  │
  ├─ CacheManager ──── ensures var/cache/pdepend/ exists
  ├─ MetricGenerator ── runs vendor/bin/pdepend --summary-xml=...
  ├─ XmlParser ──────── parses summary.xml into structured arrays
  ├─ Analyzer ────────── iterates packages → classes → methods
  │     └─ MetricChecker ── compares each metric vs limit
  │           └─ IssueHolder::addIssue() on violation
  └─ Done
```

## Classes (`src/Metrics/`)

| Class | File | Role |
|-------|------|------|
| `Analyzer` | `Analyzer.php` (13 KB) | Orchestrator; holds all default threshold constants; iterates PDepend results |
| `CacheManager` | `CacheManager.php` (5 KB) | Manages `var/cache/pdepend/` directories; copies non-.php files with .php extension for PDepend |
| `MetricGenerator` | `MetricGenerator.php` (3 KB) | Constructs and executes the `vendor/bin/pdepend` shell command |
| `XmlParser` | `XmlParser.php` (10 KB) | Parses PDepend summary XML into structured arrays (metrics, files, packages) using `SimpleXMLElement` |
| `MetricChecker` | `MetricChecker.php` (12 KB) | Per-entity metric validation; creates `STATUS_OK`/`STATUS_ERROR` results and adds issues |
| `MetricData` | `MetricData.php` (2 KB) | Data transfer object holding parsed metric values for a single entity |

## Default Thresholds

All thresholds are defined as constants in `Analyzer` and can be overridden via `php-linter.json`.

### Class-level Metrics

| Metric | Constant | Default | Description |
|--------|----------|---------|-------------|
| Class Size | `CLASS_SIZE_LIMIT` | 60 | Total methods + properties |
| Class LOC | `CLASS_LOC_LIMIT` | 1,100 | Lines of code in class |
| Code Rank | `CODE_RANK_LIMIT` | 2.0 | Class centrality/responsibility |
| Properties | `PROPERTIES_LIMIT` | 25 | Total properties |
| Non-Private Props | `NON_PRIVATE_PROPS_LIMIT` | 30 | Non-private properties |
| Public Methods | `PUBLIC_METHODS_LIMIT` | 40 | Public method count |
| Afferent Coupling | `AFFERENT_COUPLING_LIMIT` | 45 | Incoming dependencies |
| Efferent Coupling | `EFFERENT_COUPLING_LIMIT` | 24 | Outgoing dependencies |
| Object Coupling | `OBJECT_COUPLING_LIMIT` | 24 | Coupling between objects |
| Inheritance Depth | `INHERITANCE_DEPTH_LIMIT` | 5 | Maximum inheritance depth |
| Child Classes | `CHILD_CLASSES_LIMIT` | 35 | Maximum child classes |

### Method/Function-level Metrics

| Metric | Constant | Default | Description |
|--------|----------|---------|-------------|
| Method LOC | `METHOD_LOC_LIMIT` | 130 | Lines of code in method |
| Cyclomatic Complexity | `CYCLOMATIC_COMPLEXITY_LIMIT` | 25 | Extended cyclomatic complexity |
| NPath Complexity | `NPATH_COMPLEXITY_LIMIT` | 10,000 | Acyclic execution paths |
| Halstead Effort | `HALSTEAD_EFFORT_LIMIT` | 135,000 | Halstead effort metric |
| Maintainability Index | `MAINTAINABILITY_INDEX_LIMIT` | 25 | Minimum maintainability (below = hard to maintain) |

### File-level Metrics

| Metric | Constant | Default | Description |
|--------|----------|---------|-------------|
| Comment Ratio | `COMMENT_RATIO_LIMIT` | 0.05 | Minimum 5% comment-to-code ratio |
| File LOC | `FILE_LOC_LIMIT` | 200 | Max lines in standalone files (outside classes/functions) |

## Caching

PDepend results are cached in `var/cache/pdepend/`:

- `summary.xml` — PDepend output with all metrics
- `files/` — Copied non-.php files (PDepend only recognizes `.php` extensions)

`CacheManager` ensures directories exist (mode `0o777`) and provides `copyFile()` for non-.php files. The `Analyzer` checks cache validity before deciding whether to regenerate.

## Configuration Override

Metric limits can be overridden in `php-linter.json`:

```json
{
  "metricLimits": {
    "classSize": 80,
    "methodLoc": 200,
    "cyclomaticComplexity": 30
  }
}
```

All keys are optional. Unspecified keys use the default constant from `Analyzer`. The `Config::getMetricLimits()` method returns the override map, which `Analyzer` passes to `MetricChecker`.

## MetricChecker Pattern

`MetricChecker` is instantiated per entity (class, method, file) with:
- `MetricData` — the parsed metric values
- `IssueHolder` — for collecting violations
- File, class name, and function name context

It provides `checkMax()` and `checkMin()` helper methods that compare a value against a limit, format a message, and add an issue if the threshold is exceeded. Each metric has a dedicated `checkMax*()` or `checkMin*()` method (e.g., `checkMaxClassSize()`, `checkMinMaintainabilityIndex()`).

## Where to Add New Metrics

1. Add a threshold constant to `Analyzer`
2. Add a `checkMax*()` or `checkMin*()` method to `MetricChecker`
3. Call the new check method in `Analyzer` during iteration
4. Add the metric key to the `metricLimits` documentation in README.md
5. Add tests in `tests/Unit/Metrics/`
