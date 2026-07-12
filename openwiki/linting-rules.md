# Linting Rules

The AST-based linter uses `nikic/php-parser` to parse PHP files and traverse the AST. The main entry point is `Linter::run()` (`src/Linter/Linter.php`), which creates a `ParserFactory`, parses each file, and traverses the AST with an `ElementVisitor`.

## ElementVisitor — Central Dispatcher

`ElementVisitor` (`src/Linter/ElementVisitor.php`) extends `NodeVisitorAbstract` and is the main AST visitor. For each node it:

1. Tracks namespace, class, function, and scope context
2. Delegates to sub-visitors (`ClassVisitor`, `FunctionVisitor`, `MagicNumberVisitor`, `SuperglobalUsageVisitor`)
3. Instantiates per-node checkers (`NameChecker`, `FunctionChecker`, `DocBlockChecker`, `ExpressionChecker`, `FunctionCallChecker`, `OperatorChecker`, `TryCatchChecker`, `LocalScopeChecker`)
4. Runs PHPDoc analysis via `PHPStan\PhpDocParser`

Key methods in `ElementVisitor::enterNode()`:
- `handleNamespace()` / `handleClassOrTrait()` / `handleFunctionOrMethod()` — set context
- `checkClassNode()` / `checkFunctionNode()` — delegate to `ClassVisitor` / `FunctionVisitor`
- `checkLocalScope()` — delegates to `LocalScopeChecker`
- `checkTryCatch()` — delegates to `TryCatchChecker`
- `runGenericCheckers()` — runs `NameChecker`, `FunctionChecker`, `DocBlockChecker`, `ExpressionChecker`, `FunctionCallChecker`, `OperatorChecker`
- `checkTopLevelFunction()` / `checkTopLevelConst()` / `checkTopLevelDefine()` — PSR-1 checks
- `checkDocBlock()` — PHPDoc validation via `DocBlockChecker`

After traversal, `MagicNumberVisitor::checkDuplicates()` is called in `afterTraverse()`.

## Checker Classes (`src/Linter/Checker/`)

All checkers extend `AbstractNodeChecker`, which holds the current `Node` and `IssueHolder`.

| Checker | File | What It Checks |
|---------|------|----------------|
| `NameChecker` | `NameChecker.php` (12 KB) | CamelCase for classes/methods/variables, ALL_CAPS constants, name length limits (3-32 global, 1-24 local), PSR suffixes/prefixes (Abstract, Trait, Interface), boolean function naming (is/has/can), verb-based function naming |
| `FunctionChecker` | `FunctionChecker.php` (15 KB) | Parameter counts, return types, DTO suggestions (arrays with string keys), static method recommendations, PHP 4 constructor detection |
| `DocBlockChecker` | `DocBlockChecker.php` (9 KB) | Missing DocBlocks on public API, summary line formatting (<80 chars, capital letter, period), mandatory tags (@api/@internal for classes, @var for untyped properties), tag ordering, complex type requirements (no bare `array`) |
| `ExpressionChecker` | `ExpressionChecker.php` (4 KB) | Expression-level checks including DTO suggestions |
| `FunctionCallChecker` | `FunctionCallChecker.php` (1 KB) | Dangerous function calls: `eval()`, `var_dump()`, `print_r()`, `debug_backtrace()` |
| `OperatorChecker` | `OperatorChecker.php` (1 KB) | Error suppression operator `@` |
| `TryCatchChecker` | `TryCatchChecker.php` (1 KB) | Empty catch blocks |
| `LocalScopeChecker` | `LocalScopeChecker.php` (1 KB) | Local scope context validation |

## Visitor Classes (`src/Linter/Visitor/`)

All visitors extend `AbstractVisitorChecker`, which holds an `IssueHolder`.

| Visitor | File | What It Checks |
|---------|------|----------------|
| `ClassVisitor` | `ClassVisitor.php` (10 KB) | Unused private properties/methods, visibility ordering (public → protected → private), member ordering (properties before methods), PHP 4 style constructors |
| `FunctionVisitor` | `FunctionVisitor.php` (5 KB) | Unused parameters (excluding promoted and abstract), variables referenced only once |
| `MagicNumberVisitor` | `MagicNumberVisitor.php` (4 KB) | Duplicate numeric literals across the codebase; suggests defining constants |
| `SuperglobalUsageVisitor` | `SuperglobalUsageVisitor.php` (5 KB) | Superglobal usage outside allowed contexts (Controllers, Middleware, global scope) |

## Unused Code Analyzers

Two separate two-pass analyzers run independently of the main `ElementVisitor`:

- **`UnusedClassAnalyzer`** (`src/Linter/UnusedClassAnalyzer.php`, 7 KB) — Pass 1: collect all class/interface/trait definitions. Pass 2: collect all usages (instantiation, static calls, type hints, instanceof). Report definitions with zero usages.
- **`UnusedFunctionAnalyzer`** (`src/Linter/UnusedFunctionAnalyzer.php`, 8 KB) — Same pattern for functions and methods.

Both use `NodeTraverser` with `NameResolver` to resolve fully-qualified names.

## Naming Conventions Enforced

- **Classes/Interfaces/Traits:** `UpperCamelCase`, 3-32 chars
- **Methods/Functions/Variables:** `lowerCamelCase`, local names 1-24 chars
- **Constants:** `ALL_CAPS`
- **Abstract classes:** must be prefixed with `Abstract`
- **Traits:** must be suffixed with `Trait`
- **Interfaces:** must be suffixed with `Interface`
- **Boolean functions:** must start with declarative verbs (is, has, can, accepts, allows, etc.)
- **Non-boolean functions:** must start with an imperative verb

## Security & Forbidden Syntax

- `eval()` — flagged as dangerous
- `global` keyword — flagged
- `goto` statements — flagged
- `@` error suppression operator — flagged
- `exit`/`die` inside functions/classes — suggests exceptions instead
- `var_dump()`, `print_r()`, `debug_backtrace()` — debug leftovers
- Superglobals outside allowed contexts — flagged

## PSR Compliance

- **PSR-1:** Top-level constants and functions should be moved into class namespaces
- **PSR-4:** File paths must match class namespaces based on `composer.json` autoload mappings (validated via `ComposerFile::convertClassNameToFileName()`)

## Where to Add New Rules

1. **New naming rule** → Add to `NameChecker`
2. **New function-level rule** → Add to `FunctionChecker`
3. **New PHPDoc rule** → Add to `DocBlockChecker`
4. **New class-level rule** → Add to `ClassVisitor`
5. **New expression-level rule** → Add to `ExpressionChecker` or create a new checker
6. Wire new checkers into `ElementVisitor::enterNode()` via `runGenericCheckers()` or a dedicated call
7. Add corresponding tests in `tests/Unit/Linter/Checker/` or `tests/Unit/Linter/Visitor/`