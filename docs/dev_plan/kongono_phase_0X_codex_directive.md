# CODEX IMPLEMENTATION DIRECTIVE - KONGONO PHASE 0X

## Exact Trusted-Developer Diagnostic Projection Core

## Mission

Implement the first executable boundary approved by ADR 0034: a new leaf
Composer package, `kongono/diagnostic-presentation`, containing only immutable
trusted-developer projection values, their pure projector, and a package-local
context-free failure foundation.

This phase turns bounded package-owned diagnostic metadata and context into one
detached developer-facing snapshot. It does **not** render that snapshot, enable
trusted mode, authorize disclosure, integrate with HTTP or CLI, create a
production model, or attach the package to Kernel, Dependency Injection, the
Application Graph, logging, telemetry, or another runtime mechanism.

The executor is an implementer, not an architect. Every type, dependency,
method, validation boundary, failure category, file, and prohibited capability
authorized here is closed. If this directive and the accepted repository cannot
be reconciled exactly, stop and report the mismatch. Do not guess.

# 1. AUTHORITY AND PRECEDENCE

Read completely before editing:

1. `AGENTS.md`
2. `ARCHITECTURE.md`
3. `SECURITY.md`
4. `docs/doctrine.md`
5. `docs/documentation-standard.md`
6. `docs/engineering/api-status.md`
7. `docs/engineering/error-handling.md`
8. `docs/engineering/package-layout.md`
9. `docs/engineering/testing.md`
10. `docs/adr/0003-monorepo-initial-development.md`
11. `docs/adr/0004-microkernel-capability-architecture.md`
12. `docs/adr/0026-actionable-diagnostics-and-error-boundaries.md`
13. `docs/adr/0027-package-local-runtime-diagnostic-metadata.md`
14. `docs/adr/0028-bounded-diagnostic-context-and-disclosure.md`
15. `docs/adr/0029-presentation-neutral-diagnostic-value-foundation.md`
16. `docs/adr/0031-neutral-api-metadata-ownership-and-acyclic-package-dependencies.md`
17. `docs/adr/0032-kernel-lifecycle-diagnostic-context-integration.md`
18. `docs/adr/0033-dependency-injection-diagnostic-context-integration.md`
19. `docs/adr/0034-trusted-developer-diagnostic-projection.md`
20. the root manifest, all five current package manifests, and all five package
    READMEs;
21. the three existing contextual exception markers;
22. all six Diagnostics value/schema types and their tests;
23. `tests/Architecture/ApiStatusConventionTest.php`;
24. `docs/dev_plan/kongono_phase_0W_codex_directive.md`;
25. `docs/task reports/Phase_0W.md`; and
26. this directive.

Accepted ADRs and locked doctrine remain authoritative. This directive selects
only the exact implementation details ADR 0034 deliberately left open. It does
not reopen its package direction, trust boundary, disclosure rules, projected
information, persistent-runtime requirements, or rejected alternatives.

## Mandatory stop rule

Stop before mutation if any of these is true:

- repository identity, branch, history, index, status inventory, or locked
  caller-record identity differs from Section 2;
- the Phase 0X directive is not the exact architect-delivered artifact;
- the Phase 0X report is absent, populated, staged, tracked, or present in
  `HEAD` before implementation;
- an authorized path is a directory, link, reparse point, or alternate stream
  when an ordinary file is required;
- a required source contract differs from Sections 4 through 10;
- Composer proposes any third-party change or any existing local-package
  requirement/reference change;
- implementing a required test would require another production type, fixture,
  dependency, suppression, exclusion, or public API;
- any architectural, security, disclosure, compatibility, runtime, or output
  decision remains unstated; or
- a prohibited capability appears necessary.

On a stop, do not stage, commit, reset, clean, amend, delete caller records,
weaken validation, or populate the zero-byte report. Return a blocking TCR
outside the report with the exact decision required, evidence, options, and a
recommendation.

# 2. LOCKED BASELINE AND CALLER SETUP

## 2.1 Repository identity before caller setup

The accepted Phase 0W worktree is:

```text
branch                         main
only local branch              main
HEAD                           7d4df1f4e31b9bdb6cd134b44682719bdb17aa6f
tree                           b1d0057925158e0f0f37ca13694d1d0d525ce5f0
parent                         75749a37a184996203e569e039bddbc3ec10cdf7
HEAD subject                   Establish Kongono framework baseline through Phase 0U
commit count                   6
remotes                        0
tags                           0
staged paths                   0
tracked modifications          14
untracked paths                6
total status paths             20
tracked diff                   14 files, 381 insertions, 70 deletions
package directories            5
package manifests              5, plus root = 6
production PHP declarations    40
test PHP files                 73
Composer lock bytes            179468
Composer lock SHA-256          197D310AB802AF3992ED0566E710AABD551A6A06BA5188D3E5D1C360C36374E9
```

The exact tracked modifications are:

```text
ARCHITECTURE.md
README.md
SECURITY.md
docs/adr/0026-actionable-diagnostics-and-error-boundaries.md
docs/adr/0028-bounded-diagnostic-context-and-disclosure.md
docs/adr/0029-presentation-neutral-diagnostic-value-foundation.md
docs/adr/README.md
docs/architecture/overview.md
docs/architecture/runtime-model.md
docs/doctrine.md
docs/engineering/error-handling.md
docs/engineering/package-layout.md
docs/glossary.md
packages/diagnostics/README.md
```

The exact untracked paths and identities are:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `docs/adr/0034-trusted-developer-diagnostic-projection.md` | 11249 | `DD751DFF6B454AFFAC44150B4600E7A3075C30A65C79A1BF42A39B4954E173B9` |
| `docs/dev_plan/kongono_phase_0V_1_codex_directive_correction.md` | 15434 | `C0DF348516E2A8848278F51A768835A95BA24FDDEAAD49478B4D5F8B3037FBC5` |
| `docs/dev_plan/kongono_phase_0W_codex_directive.md` | 32691 | `F94B1CBA904441B2DE6BCC4B51C34319E85367F783FEAF669479CDC763863FD2` |
| `docs/task reports/Phase_0V.md` | 24353 | `6C5053A469664994C5494598AF0D29E53089F0EACAD08D7ADA744838F404A4A3` |
| `docs/task reports/Phase_0V_1_correction.md` | 10110 | `AB965D2542B243A038B2213106D3C739DBF40F29913D72FED72D275181EF45E3` |
| `docs/task reports/Phase_0W.md` | 17338 | `08E491A635717DA07E7012A593C5173E04EBA6A614E49529632241008DCDE38E` |

These six records are caller-managed evidence. Preserve them byte-for-byte
except that ADR 0034 is an architecture document explicitly authorized for
reconciliation in Section 12. Never edit the two Phase 0V records, the Phase 0W
directive, or the Phase 0W report.

## 2.2 Required caller setup

Before execution, the caller will add exactly:

```text
docs/dev_plan/kongono_phase_0X_codex_directive.md
docs/task reports/Phase_0X.md
```

The directive must be populated and match the byte count and SHA-256 delivered
by the architect outside this document. `Phase_0X.md` must be an ordinary
zero-byte file with SHA-256
`E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855`.
Neither path may be staged or present in `HEAD`.

After caller setup and before executor mutation, require exactly 14 tracked
modifications, eight untracked paths, 22 status paths, and zero staged paths.
The eight untracked paths must be exactly the six in Section 2.1 plus the two
Phase 0X caller paths. Any extra path is a blocker.

## 2.3 Tool environment

Expected environment:

```text
PHP             8.5.9
Composer        2.10.2
Git             2.45.1.windows.1
PHPUnit         13.3.0
PHPStan         2.2.8
PHP CS Fixer    3.95.18
```

Record actual versions. Version drift is a blocker unless the architect
explicitly accepts it. The accepted Composer warnings remain limited to missing
licenses, local unbounded `@dev`/`*` constraints, and the existing missing-curl
performance warning when emitted. Any other warning is a blocker.

## 2.4 Pre-change quality

Run unchanged `composer quality` before editing. Expected canonical result:

```text
security advisories     0
platform                pass
PHP CS Fixer            0 of 113 files fixable
PHPStan                 0 errors
PHPUnit                 204 tests, 3377 assertions
```

If only the fail-closed advisory request is blocked by managed network/cache
access, rerun the identical command with approved access. Do not change flags,
policy, cache configuration, dependencies, or scripts. A blocked audit is not a
pass.

# 3. EXACT PHASE OUTCOME

At completion:

- six package directories and seven Composer manifests exist;
- `kongono/diagnostic-presentation` is a leaf package with namespace
  `Kongono\DiagnosticPresentation\`;
- the framework root consumes all six packages;
- no existing package depends on Diagnostic Presentation;
- Diagnostic Presentation depends directly on API Metadata, Application Graph,
  Dependency Injection, Diagnostics, and Kernel;
- exactly four reserved Experimental developer concepts exist;
- exactly one package-local Public exception marker, one Internal base, one
  Experimental concrete failure, and one Internal 15-case catalog exist;
- one stateless projector accepts exactly the three contextual package markers;
- projection returns a detached immutable snapshot containing both Safe and
  Protected fields with their classifications unchanged;
- no renderer, trusted-mode selector, authorization check, production model,
  runtime adapter, binding, transport, or I/O exists; and
- all prior doctrine, semantics, recovery boundaries, contexts, identifiers,
  source exceptions, and package behavior remain unchanged.

# 4. PACKAGE AND DEPENDENCY CONTRACT

Create `packages/diagnostic-presentation/composer.json` with exactly this
semantic content and normal repository formatting:

```json
{
  "name": "kongono/diagnostic-presentation",
  "description": "Trusted developer diagnostic projection values for Kongono Framework.",
  "type": "library",
  "require": {
    "kongono/api-metadata": "*",
    "kongono/application-graph": "*",
    "kongono/dependency-injection": "*",
    "kongono/diagnostics": "*",
    "kongono/kernel": "*",
    "php": ">=8.5"
  },
  "autoload": {
    "psr-4": {
      "Kongono\\DiagnosticPresentation\\": "src/"
    }
  },
  "autoload-dev": {
    "psr-4": {
      "Kongono\\DiagnosticPresentation\\Tests\\": "tests/"
    }
  }
}
```

Update root `composer.json` only to:

1. require `kongono/diagnostic-presentation` as `@dev` in lexical order;
2. map `Kongono\DiagnosticPresentation\Tests\` to
   `packages/diagnostic-presentation/tests/` in root `autoload-dev`;
3. validate the new package manifest in lexical package order; and
4. retain every other dependency, repository, policy, script, and setting.

Update the tooling configurations only to include the new package's `src` and
`tests` trees:

- `.php-cs-fixer.dist.php`
- `phpstan.neon.dist`
- `phpunit.xml.dist`

Preserve their existing rules and strictness. Do not add a baseline,
suppression, cache exception, test exclusion, or alternate suite.

After both manifests are correct, run exactly:

```text
composer update kongono/diagnostic-presentation --with-dependencies --no-interaction
```

Composer alone generates `composer.lock`. The only authorized lock changes are:

- the root content hash required by the root manifest edit; and
- one new `kongono/diagnostic-presentation` path-package entry with the exact
  requirements from this section.

Every existing local-package reference, requirement, version, source/dist
field, and every third-party package entry must remain unchanged. Stop on any
other lock delta. Do not edit the lock manually or broaden the command.

# 5. API STATUS AND EXACT TYPE INVENTORY

Create exactly these production declarations:

| FQCN | Kind | Status |
|---|---|---|
| `Kongono\DiagnosticPresentation\Developer\DeveloperDiagnosticOwner` | string-backed enum | Experimental |
| `Kongono\DiagnosticPresentation\Developer\TrustedDeveloperDiagnosticField` | final readonly class | Experimental |
| `Kongono\DiagnosticPresentation\Developer\TrustedDeveloperDiagnostic` | final readonly class | Experimental |
| `Kongono\DiagnosticPresentation\Developer\TrustedDeveloperDiagnosticProjector` | final readonly class | Experimental |
| `Kongono\DiagnosticPresentation\Diagnostic\DiagnosticPresentationDiagnostic` | string-backed enum | Internal |
| `Kongono\DiagnosticPresentation\Exception\ExceptionInterface` | interface extending `Throwable` | Public |
| `Kongono\DiagnosticPresentation\Exception\DiagnosticPresentationException` | abstract class | Internal |
| `Kongono\DiagnosticPresentation\Exception\InvalidDiagnosticProjectionException` | final class | Experimental |

Each non-bootstrap production declaration has exactly one `#[Api(...)]`
attribute. Do not create another production declaration, trait, interface,
attribute, enum, helper, registry, factory, renderer, adapter, configuration
type, or alias.

Update `tests/Architecture/ApiStatusConventionTest.php` so it proves:

- exactly 48 production declarations across six package source trees;
- exact status for all eight new types;
- exactly five pairwise-distinct package exception markers;
- the new concrete failure implements only its package marker;
- the marker and base shapes in Section 9;
- exactly 68 unique catalog identifiers split 6 Kernel, 11 Graph, 25 DI, 11
  Diagnostics, and 15 Presentation;
- exact manifests and acyclic package edges; and
- Diagnostics production source still contains no dependency on Presentation,
  Kernel, Graph, or DI.

# 6. DEVELOPER OWNER

`DeveloperDiagnosticOwner` is a string-backed enum with exactly these cases and
values, in this declaration order:

```text
Kernel               kernel
ApplicationGraph     application_graph
DependencyInjection  dependency_injection
```

It declares no methods, constants, properties, aliases, or additional cases.
The owner is derived only by the projector from the source marker. No string
input, class-name parsing, exception namespace, identifier prefix, environment,
configuration, or caller field selects it.

# 7. TRUSTED DEVELOPER FIELD

`TrustedDeveloperDiagnosticField` has exactly six private readonly instance
properties:

```text
string                key
string                label
DiagnosticValueKind   kind
string|int|bool       value
DiagnosticDisclosure disclosure
?bool                 textWasTruncated
```

Its only public methods are its constructor and these exact accessors:

```php
public function key(): string;
public function label(): string;
public function kind(): DiagnosticValueKind;
public function value(): string|int|bool;
public function disclosure(): DiagnosticDisclosure;
public function textWasTruncated(): ?bool;
```

The constructor is exactly:

```php
public function __construct(
    string $key,
    string $label,
    DiagnosticValueKind $kind,
    string|int|bool $value,
    DiagnosticDisclosure $disclosure,
    ?bool $textWasTruncated,
);
```

It validates before assignment:

- key is non-empty lowercase ASCII snake case matching the existing Diagnostics
  expression and contains at most 64 bytes;
- label is non-empty valid UTF-8 and contains at most 96 bytes;
- `Text` requires a string value and a boolean truncation state;
- `Integer` requires an integer and a null truncation state;
- `Boolean` requires a boolean and a null truncation state;
- a text value is printable ASCII only and contains at most 512 bytes; and
- when text truncation is `true`, the value ends exactly in `...[truncated]`.

The class does not escape, sanitize, redact, translate, format, classify,
truncate, or otherwise alter its inputs. The projector copies the already
represented value and exact metadata from `DiagnosticField`. Invalid input
raises the exact package failure assigned in Section 10.

Document the constructor's `InvalidDiagnosticProjectionException` failure
contract explicitly. Do not claim native coercion, recovery, or sanitization.

# 8. TRUSTED DEVELOPER DIAGNOSTIC

`TrustedDeveloperDiagnostic` has exactly seven private readonly instance
properties:

```text
DeveloperDiagnosticOwner owner
string                   identifier
string                   title
string                   operation
string                   violatedInvariant
string                   remediation
list<TrustedDeveloperDiagnosticField> fields
```

Its only public methods are its constructor and these accessors:

```php
public function owner(): DeveloperDiagnosticOwner;
public function identifier(): string;
public function title(): string;
public function operation(): string;
public function violatedInvariant(): string;
public function remediation(): string;
/** @return list<TrustedDeveloperDiagnosticField> */
public function fields(): array;
```

The constructor is exactly:

```php
/** @param array<array-key, mixed> $fields */
public function __construct(
    DeveloperDiagnosticOwner $owner,
    string $identifier,
    string $title,
    string $operation,
    string $violatedInvariant,
    string $remediation,
    array $fields,
);
```

Validate before assignment:

- identifier matches the owner exactly:
  - Kernel: `^KGN-KERNEL-[0-9]{4}$`;
  - Application Graph: `^KGN-GRAPH-[0-9]{4}$`;
  - Dependency Injection: `^KGN-DI-[0-9]{4}$`;
- title is non-empty valid UTF-8 and at most 160 bytes;
- operation is non-empty valid UTF-8 and at most 160 bytes;
- violated invariant is non-empty valid UTF-8 and at most 256 bytes;
- remediation is non-empty valid UTF-8 and at most 256 bytes;
- fields is a zero-based consecutive list;
- the list contains at most 16 entries;
- every entry is exactly a `TrustedDeveloperDiagnosticField`; and
- field keys are unique.

Copy fields into a new list by value before assignment so caller array
references cannot mutate stored list membership. `fields()` returns a detached
array. The immutable field objects themselves may be shared because they cannot
change. Preserve input/schema order; do not sort.

Structural identifier validation does not assert that a custom marker's ID is a
Kongono-owned catalog entry. Do not add catalog reflection, allowlists,
registries, enum lookup, or ownership claims. Direct construction is an
Experimental implementation surface used by the projector and tests, not a
Public supported extension or authorization boundary.

Document the constructor's `InvalidDiagnosticProjectionException` failure
contract explicitly.

# 9. PROJECTOR AND FAILURE BOUNDARY

## 9.1 Exact projector surface

`TrustedDeveloperDiagnosticProjector` is stateless. It declares no constructor,
property, constant, cache, registry, retained exception, or secondary public
method. Its only declared public method is:

```php
public function project(
    Kongono\Kernel\Exception\ExceptionInterface
    |Kongono\ApplicationGraph\Exception\ExceptionInterface
    |Kongono\DependencyInjection\Exception\ExceptionInterface $source,
): TrustedDeveloperDiagnostic;
```

Normal repository formatting may place the union on multiple lines. The native
parameter type must contain exactly those three interfaces and no `Throwable`,
Diagnostics marker, object, mixed, string, array, generic marker, or new
interface.

## 9.2 Exact algorithm

Projection proceeds in this order:

1. evaluate membership in all three accepted markers without calling a source
   accessor;
2. reject a source satisfying more than one marker with
   `AmbiguousDiagnosticOwner` before any accessor call;
3. derive the exact owner and allowed prefix from the sole marker;
4. read `diagnosticId()`, `diagnosticTitle()`,
   `diagnosticRemediation()`, and `diagnosticContext()` exactly once each, in
   that order, into local values;
5. read the context's `operation()`, `violatedInvariant()`, and `fields()`
   exactly once each, in that order, into local values;
6. for each source field in existing canonical order, read `key()`, `label()`,
   `kind()`, `value()`, `disclosure()`, and `textWasTruncated()` exactly once
   each, in that order, into local values;
7. create a new `TrustedDeveloperDiagnosticField` from those locals; and
8. create and return one new `TrustedDeveloperDiagnostic` from all detached
   locals.

Do not catch source accessor failures. Any native or consumer `Throwable`
raised by an accessor propagates unchanged, with the same object identity. Do
not wrap it merely to enter the Presentation exception family. Do not continue
reading after a failure.

Each call returns a fresh diagnostic and fresh field objects. The projector
retains nothing between calls and performs no I/O. It includes every source
field, both Safe and Protected, and preserves disclosure and truncation state
exactly. It never omits, promotes, reclassifies, redacts, sanitizes, escapes,
sorts, merges, enriches, translates, or infers.

Calling `project()` is **not** authorization. This phase creates no trusted-mode
input or check. An outer future integration must decide whether the trusted
developer boundary is authorized before calling this projector or exposing its
result.

The method documentation must state that invalid projection structure raises
`InvalidDiagnosticProjectionException` and that a source-accessor `Throwable`
propagates unchanged. It must not promise that these are the only possible
native failures.

## 9.3 Exception marker and base

`Kongono\DiagnosticPresentation\Exception\ExceptionInterface` extends only
`Throwable` and declares exactly:

```php
public function diagnosticId(): string;
public function diagnosticTitle(): string;
public function diagnosticRemediation(): string;
```

It has no context accessor and no relationship to the four existing package
markers.

`DiagnosticPresentationException`:

- is abstract and extends `RuntimeException`;
- implements the Presentation marker only;
- stores exactly one private readonly `DiagnosticPresentationDiagnostic`;
- has one final public constructor accepting the diagnostic, message, and
  nullable previous `Throwable` in that order;
- passes code `0` and the previous exception to `RuntimeException`;
- implements the three marker methods as final public methods by delegating to
  the catalog; and
- performs no context construction, projection, rendering, logging, transport,
  or wrapping policy.

`InvalidDiagnosticProjectionException` is final, Experimental, extends the
base, and declares no property or method.

# 10. EXACT PRESENTATION DIAGNOSTIC CATALOG

`DiagnosticPresentationDiagnostic` is an Internal string-backed enum with
exactly 15 cases in this order:

| Case | Identifier | Exact title | Exact remediation |
|---|---|---|---|
| `AmbiguousDiagnosticOwner` | `KGN-PRESENTATION-1001` | Ambiguous diagnostic owner | Use a source that implements exactly one supported package exception marker |
| `InvalidDiagnosticIdentifier` | `KGN-PRESENTATION-1002` | Invalid diagnostic identifier | Return the exact owner prefix followed by four ASCII decimal digits |
| `InvalidDiagnosticTitle` | `KGN-PRESENTATION-1003` | Invalid diagnostic title | Return a non-empty valid UTF-8 title containing at most 160 bytes |
| `InvalidDiagnosticRemediation` | `KGN-PRESENTATION-1004` | Invalid diagnostic remediation | Return non-empty valid UTF-8 remediation containing at most 256 bytes |
| `InvalidDiagnosticOperation` | `KGN-PRESENTATION-1005` | Invalid diagnostic operation | Return a non-empty valid UTF-8 operation containing at most 160 bytes |
| `InvalidDiagnosticInvariant` | `KGN-PRESENTATION-1006` | Invalid diagnostic invariant | Return a non-empty valid UTF-8 invariant containing at most 256 bytes |
| `DiagnosticFieldsNotList` | `KGN-PRESENTATION-1007` | Diagnostic fields are not a list | Supply fields as a zero-based consecutive list |
| `InvalidDiagnosticFieldEntry` | `KGN-PRESENTATION-1008` | Invalid diagnostic field entry | Supply only trusted developer diagnostic field values |
| `TooManyDiagnosticFields` | `KGN-PRESENTATION-1009` | Too many diagnostic fields | Limit one projected diagnostic to at most 16 fields |
| `DuplicateDiagnosticFieldKey` | `KGN-PRESENTATION-1010` | Duplicate diagnostic field key | Use each canonical diagnostic field key at most once |
| `InvalidDiagnosticFieldKey` | `KGN-PRESENTATION-1011` | Invalid diagnostic field key | Use lowercase ASCII snake case containing at most 64 bytes |
| `InvalidDiagnosticFieldLabel` | `KGN-PRESENTATION-1012` | Invalid diagnostic field label | Use a non-empty valid UTF-8 label containing at most 96 bytes |
| `DiagnosticFieldValueKindMismatch` | `KGN-PRESENTATION-1013` | Diagnostic field value kind mismatch | Match the scalar value to the exact diagnostic value kind |
| `InvalidDiagnosticTextRepresentation` | `KGN-PRESENTATION-1014` | Invalid diagnostic text representation | Use printable ASCII represented text containing at most 512 bytes |
| `InvalidDiagnosticTruncationState` | `KGN-PRESENTATION-1015` | Invalid diagnostic truncation state | Use a boolean state for text, null for other kinds, and the truncation suffix when true |

The enum declares exactly `title(): string` and `remediation(): string`, each as
an exhaustive match over all cases. Do not add context schemas. These failures
are deliberately context-free.

Failure mapping is exact:

- Section 9 ambiguous source -> 1001;
- invalid/mismatched identifier -> 1002;
- invalid title -> 1003;
- invalid remediation -> 1004;
- invalid operation -> 1005;
- invalid invariant -> 1006;
- non-list field array -> 1007;
- wrong field entry type -> 1008;
- more than 16 fields -> 1009;
- duplicate field key -> 1010;
- invalid field key -> 1011;
- invalid field label -> 1012;
- value/kind PHP type mismatch -> 1013;
- non-printable or overlong text representation -> 1014; and
- invalid null/boolean truncation shape or missing true suffix -> 1015.

Exception messages must identify the violated structural rule and, when useful,
the field position or canonical key. They must not echo an invalid title,
remediation, arbitrary text value, native message, trace, path, request data,
environment data, secret, or other uncontrolled payload. Tests assert diagnostic
identity and useful structural wording, not punctuation-only prose choices.

# 11. EXACT TEST BOUNDARY

Create exactly these six test files and no fixture tree:

```text
packages/diagnostic-presentation/tests/Unit/DeveloperDiagnosticOwnerTest.php
packages/diagnostic-presentation/tests/Unit/DiagnosticPresentationDiagnosticTest.php
packages/diagnostic-presentation/tests/Unit/ExceptionBoundaryTest.php
packages/diagnostic-presentation/tests/Unit/TrustedDeveloperDiagnosticFieldTest.php
packages/diagnostic-presentation/tests/Unit/TrustedDeveloperDiagnosticTest.php
packages/diagnostic-presentation/tests/Unit/TrustedDeveloperDiagnosticProjectorTest.php
```

Anonymous test-only classes extending `RuntimeException` may implement one or
more existing Public marker interfaces. They are authorized only as focused
boundary doubles; they do not establish a supported extension contract.

Tests must prove at minimum:

### Owner

- exact three-case order, names, values, backing type, and no declared methods.

### Catalog and exception boundary

- exact 15 cases, IDs, titles, remediations, uniqueness, and prefix;
- marker interface shape and separation from every existing marker;
- base constructor/property/method shape, final method delegation, previous
  identity, code zero, and no context accessor; and
- concrete failure finality and membership only in its package family.

### Field

- exact final/readonly/property/method shape;
- preservation of each scalar kind, disclosure value, and valid truncation
  state;
- key and label bounds including invalid UTF-8;
- exact kind mismatch rejection;
- printable-ASCII and 512-byte text bounds;
- truncation-state rules; and
- no escaping, redaction, sanitization, or mutation.

### Diagnostic

- exact final/readonly/property/method shape;
- all three identifier-owner mappings, including structural but noncatalog
  four-digit IDs;
- title, operation, invariant, and remediation UTF-8/byte bounds;
- list, entry, maximum, duplicate-key, canonical-order, and detached-array
  rules;
- caller-array and referenced-element mutation cannot alter stored membership;
  and
- returned-array mutation cannot alter the stored list.

### Projector

- exact final/readonly/stateless shape and sole native union method;
- owner derivation for each of the three markers;
- precise metadata/context projection;
- canonical field order and exact copying of key, label, kind, scalar value,
  Safe/Protected disclosure, and nullable truncation state;
- zero-field, mixed-field, and maximum-16-field examples;
- fresh model and field objects on repeated projection;
- no source/context/field object retained in reflected projector or result
  state;
- every source accessor is called exactly once and in the required order;
- direct source inspection proves that every final `DiagnosticContext` and
  `DiagnosticField` accessor expression occurs exactly once in the projector,
  while projection-output tests prove the resulting values and order;
- ambiguous dual- and triple-marker sources fail with 1001 before any accessor;
- invalid custom marker ID/title/remediation is rejected by the exact catalog
  case;
- a structurally valid noncatalog custom ID remains accepted without being
  claimed as a Kongono catalog member;
- an accessor-thrown native `Throwable` propagates unchanged and later
  accessors are not read; and
- projection itself performs no output, I/O, trust selection, disclosure
  filtering, registry lookup, catch-all wrapping, or cross-call retention.

Update the existing architecture test as required by Section 5. Do not modify
any other existing PHP test or any existing package source file.

# 12. DOCUMENTATION AND ADR WORK

Create:

```text
docs/adr/0035-trusted-developer-diagnostic-projection-implementation.md
packages/diagnostic-presentation/README.md
```

ADR 0035 must have `Status: Accepted`, `Date: 2026-08-14`, and the standard
sections Context, Decision, Rationale, Consequences, Rejected alternatives, and
What remains open. Record the exact package graph, eight-type inventory,
Experimental/Public/Internal classifications, 15 context-free failures, pure
single-read projection, custom-marker structural validation, detached snapshot,
Protected-field preservation, absence of authorization, and persistent-runtime
isolation.

Its open section must retain exactly the later architectural subjects:

- trusted server-controlled configuration/capability and outer adapter;
- accessible server-rendered developer format;
- escaping for a selected output grammar;
- safe copy/export formats;
- native cause-chain, source, stack-frame, and documentation providers;
- separate deny-by-default production model and allowlists;
- HTTP/CLI/other transport mapping and status/exit policy;
- localization, logging, telemetry, incidents, serialization, and simultaneous
  primary/projection/rendering failure representation.

Reconcile ADR 0034 from planned-only to implemented-projection language and link
ADR 0035. Preserve its architecture and make clear that trusted activation,
rendering, production presentation, and runtime integration remain future.

Modify exactly these existing documentation files and no others:

```text
README.md
ARCHITECTURE.md
SECURITY.md
docs/adr/0003-monorepo-initial-development.md
docs/adr/0004-microkernel-capability-architecture.md
docs/adr/0026-actionable-diagnostics-and-error-boundaries.md
docs/adr/0028-bounded-diagnostic-context-and-disclosure.md
docs/adr/0029-presentation-neutral-diagnostic-value-foundation.md
docs/adr/0031-neutral-api-metadata-ownership-and-acyclic-package-dependencies.md
docs/adr/0034-trusted-developer-diagnostic-projection.md
docs/adr/README.md
docs/architecture/overview.md
docs/architecture/runtime-model.md
docs/doctrine.md
docs/engineering/README.md
docs/engineering/api-status.md
docs/engineering/error-handling.md
docs/engineering/package-layout.md
docs/engineering/testing.md
docs/glossary.md
packages/diagnostics/README.md
```

Documentation must agree that:

- the current runtime now contains six packages and seven manifests;
- the projection package and four developer concepts exist;
- the projector is pure, stateless, deterministic, bounded, detached, and
  Experimental;
- it accepts only the three contextual markers and creates no universal marker;
- both Safe and Protected classifications are preserved because the model is
  for an already-authorized trusted developer boundary;
- projection is not authorization and no trusted-mode selector exists;
- no renderer, output grammar, transport adapter, production model, service
  binding, logging, or telemetry exists;
- Diagnostics remains presentation-neutral and has no reverse dependency; and
- consumer-defined structurally valid metadata does not become a Kongono-owned
  catalog entry.

Historical directives and task reports remain historical and must not be
rewritten merely because their earlier counts were correct at the time.

# 13. CLOSED FILE INVENTORY

## 13.1 Create exactly 17 paths

```text
docs/adr/0035-trusted-developer-diagnostic-projection-implementation.md
packages/diagnostic-presentation/README.md
packages/diagnostic-presentation/composer.json
packages/diagnostic-presentation/src/Developer/DeveloperDiagnosticOwner.php
packages/diagnostic-presentation/src/Developer/TrustedDeveloperDiagnostic.php
packages/diagnostic-presentation/src/Developer/TrustedDeveloperDiagnosticField.php
packages/diagnostic-presentation/src/Developer/TrustedDeveloperDiagnosticProjector.php
packages/diagnostic-presentation/src/Diagnostic/DiagnosticPresentationDiagnostic.php
packages/diagnostic-presentation/src/Exception/DiagnosticPresentationException.php
packages/diagnostic-presentation/src/Exception/ExceptionInterface.php
packages/diagnostic-presentation/src/Exception/InvalidDiagnosticProjectionException.php
packages/diagnostic-presentation/tests/Unit/DeveloperDiagnosticOwnerTest.php
packages/diagnostic-presentation/tests/Unit/DiagnosticPresentationDiagnosticTest.php
packages/diagnostic-presentation/tests/Unit/ExceptionBoundaryTest.php
packages/diagnostic-presentation/tests/Unit/TrustedDeveloperDiagnosticFieldTest.php
packages/diagnostic-presentation/tests/Unit/TrustedDeveloperDiagnosticProjectorTest.php
packages/diagnostic-presentation/tests/Unit/TrustedDeveloperDiagnosticTest.php
```

## 13.2 Modify exactly 27 existing paths

```text
.php-cs-fixer.dist.php
ARCHITECTURE.md
README.md
SECURITY.md
composer.json
composer.lock
docs/adr/0003-monorepo-initial-development.md
docs/adr/0004-microkernel-capability-architecture.md
docs/adr/0026-actionable-diagnostics-and-error-boundaries.md
docs/adr/0028-bounded-diagnostic-context-and-disclosure.md
docs/adr/0029-presentation-neutral-diagnostic-value-foundation.md
docs/adr/0031-neutral-api-metadata-ownership-and-acyclic-package-dependencies.md
docs/adr/0034-trusted-developer-diagnostic-projection.md
docs/adr/README.md
docs/architecture/overview.md
docs/architecture/runtime-model.md
docs/doctrine.md
docs/engineering/README.md
docs/engineering/api-status.md
docs/engineering/error-handling.md
docs/engineering/package-layout.md
docs/engineering/testing.md
docs/glossary.md
packages/diagnostics/README.md
phpstan.neon.dist
phpunit.xml.dist
tests/Architecture/ApiStatusConventionTest.php
```

The Phase 0X report is populated only after every completion gate passes; it is
not an implementation modification. Preserve every other path byte-for-byte.
Do not delete, rename, move, stage, commit, or normalize anything.

# 14. PROHIBITED SCOPE

Do not implement or introduce:

- HTML, CSS, templates, JavaScript, Markdown, JSON, terminal, plaintext, or any
  renderer/output grammar;
- an error page, debug toolbar, CLI presenter, HTTP response, problem-details
  payload, content type, status code, exit code, clipboard action, or asset;
- trusted-mode selection, configuration, environment lookup, capability token,
  authorization, authentication, request inspection, or production disclosure;
- a production diagnostic model, allowlist, redaction-after-projection,
  sanitization, secret detection, or output escaping;
- source paths, excerpts, stack frames, native messages, causes, traces, request
  or operation envelopes, headers, bodies, cookies, environment/configuration,
  SQL, provider payloads, logs, telemetry, incidents, or documentation URLs;
- logging, telemetry, metrics, tracing, incidents, localization, serialization,
  persistence, networking, filesystem access, clocks, randomness, or global
  state;
- a universal exception/diagnostic interface or changes to any existing marker;
- changes to existing diagnostic IDs, titles, remediations, contexts, schemas,
  throw sites, package production source, or package behavior;
- a service container binding, resolver definition, Kernel lifecycle hook,
  Application Graph node/relation, extension point, plugin, agent, event,
  workflow, CLI, HTTP/router, ORM/database, frontend, or framework bootstrap;
- caching, registries, memoization, static mutable state, singleton state,
  thread/fiber local state, or cross-operation retention;
- a third-party dependency, Composer plugin, policy exception, PHPStan baseline,
  suppression, ignored error, test skip, or quality weakening;
- a Git stage, commit, tag, branch, remote, push, reset, checkout, clean, stash,
  amend, or history/ref/configuration mutation; or
- any work for Phase 0Y or later.

# 15. IMPLEMENTATION ORDER

1. Read all authority and verify exact repository/caller/file-safety state.
2. Run pre-change `composer quality` and record exact evidence.
3. Inspect the three marker interfaces, Diagnostics values, architecture test,
   manifests, lock, and every authorized existing documentation file.
4. Create ADR 0035 and the new package manifest/README.
5. Update root manifest/tooling, run the exact scoped Composer update, and stop
   on any unauthorized lock delta.
6. Implement the eight exact production declarations.
7. Implement the six package tests and update the one architecture test.
8. Reconcile only the authorized documentation files.
9. Run focused syntax, manifest, package-test, architecture, structural,
   security, file-safety, and canonical quality gates.
10. Verify final Git state and closed path inventory.
11. Populate the report only after all gates pass, then verify and return its
    final byte count and SHA-256 outside its body.

# 16. REQUIRED VALIDATION

## 16.1 Focused checks

Run at least:

```text
php -l packages/diagnostic-presentation/src/Developer/DeveloperDiagnosticOwner.php
php -l packages/diagnostic-presentation/src/Developer/TrustedDeveloperDiagnostic.php
php -l packages/diagnostic-presentation/src/Developer/TrustedDeveloperDiagnosticField.php
php -l packages/diagnostic-presentation/src/Developer/TrustedDeveloperDiagnosticProjector.php
php -l packages/diagnostic-presentation/src/Diagnostic/DiagnosticPresentationDiagnostic.php
php -l packages/diagnostic-presentation/src/Exception/DiagnosticPresentationException.php
php -l packages/diagnostic-presentation/src/Exception/ExceptionInterface.php
php -l packages/diagnostic-presentation/src/Exception/InvalidDiagnosticProjectionException.php
composer --working-dir=packages/diagnostic-presentation validate --no-check-publish
composer dump-autoload --optimize --strict-psr
vendor/bin/phpunit --configuration phpunit.xml.dist packages/diagnostic-presentation/tests
vendor/bin/phpunit --configuration phpunit.xml.dist tests/Architecture/ApiStatusConventionTest.php
composer analyse
composer cs
```

The package suite must be nonempty and cover every Section 11 rule. Optimized
strict PSR generation must emit no new noncompliance and every new type must be
autoloadable.

## 16.2 Structural and security proof

Directly inspect and report:

- exact eight production declarations and six package tests;
- exact class/enum/interface property and public-method inventories;
- exact native projector union;
- exact 15 catalog cases and 68 repository-wide unique IDs;
- six source directories, six package manifests, one root manifest, and the
  exact acyclic dependency graph;
- lock delta limited to Section 4;
- no reverse dependency or existing package source change;
- no forbidden source/context/payload concept in projected state;
- no output, I/O, ambient state, cache, registry, retained source, or trust
  selection;
- no executable PHP outside authorized production/test/config paths;
- no secret, credential, token, key material, accidental binary, NUL, invalid
  UTF-8, CRLF, trailing whitespace, missing final LF, non-ordinary file,
  alternate stream, reparse point, or mode change in the Phase 0X boundary; and
- all local Markdown links resolve.

Do not print secret-like file contents or alternate-stream contents. Report
paths and metadata only.

## 16.3 Canonical quality

Run final unchanged:

```text
composer quality
```

It must pass validation for root and six package manifests, locked security
audit, platform checks, style across 127 PHP files, maximum strict static
analysis, and the complete PHPUnit suite. Final PHP tests must exceed the
204-test/3377-assertion baseline and cover all required Phase 0X behaviors.

If only the advisory request is environment-blocked, rerun the identical
command with approved access. Never report a blocked audit as success.

## 16.4 Final Git state

Before report population require:

```text
branch                  main
HEAD                    7d4df1f4e31b9bdb6cd134b44682719bdb17aa6f
staged paths            0
tracked modifications   26
untracked paths         25
total status paths      51
remotes                 0
tags                    0
```

The 26 tracked paths must be exactly the 14 Phase 0W paths plus the 12 newly
modified tracked paths from Section 13.2. The 25 untracked paths must be exactly
the eight caller/baseline records after setup, ADR 0035, and the 16 new package
paths. Before report population, `Phase_0X.md` remains zero bytes. No path is
staged and `HEAD`, its tree, parent, subject, history, refs, modes, and index
remain unchanged.

After report population only its bytes/hash change; status counts and path
inventories remain 26/25/51 and zero staged.

# 17. STRICT ACCEPTANCE CRITERIA

Every item must pass:

- [ ] Authority, baseline, caller setup, identities, branch, history, refs,
  index, and pre-change quality are exact.
- [ ] Exactly one six-package leaf is created with the authorized dependencies.
- [ ] Root/package manifests, tooling, autoload, and Composer-generated lock
  contain only the authorized changes.
- [ ] Existing local package references and all third-party lock entries are
  unchanged.
- [ ] Exactly eight production declarations have the exact statuses, shapes,
  properties, methods, cases, and dependencies required here.
- [ ] The 15 Presentation diagnostics are unique, context-free, and mapped to
  the exact construction failures.
- [ ] The Public Presentation marker remains separate from all existing package
  markers and exposes only three string metadata methods.
- [ ] Field and diagnostic models are immutable, bounded, canonical-order,
  detached snapshots with exact validation.
- [ ] The projector accepts only the three contextual markers, is stateless,
  performs exact single reads, derives ownership, and returns fresh values.
- [ ] Dual/triple marker ambiguity is rejected before accessor access.
- [ ] Native accessor failures propagate unchanged and no later access occurs.
- [ ] Both Safe and Protected fields remain present and classified exactly;
  projection is not authorization.
- [ ] No renderer, trusted selector, production model, runtime integration,
  output, I/O, ambient state, cache, registry, or later capability exists.
- [ ] Existing package production code, diagnostics, contexts, semantics, and
  recovery boundaries are byte-identical.
- [ ] Architecture enforcement proves 48 production declarations, six source
  trees, five distinct markers, and 68 unique diagnostic IDs.
- [ ] Exactly six package test files and one modified root architecture test
  cover all required behaviors without fixture or suppression loopholes.
- [ ] Documentation/ADRs consistently distinguish implemented projection from
  future activation, rendering, production disclosure, and transport.
- [ ] Links, security, encoding, whitespace, newline, ordinary-file, stream,
  reparse, mode, closed-inventory, and `git diff --check` gates pass.
- [ ] Focused validation and final `composer quality` pass with zero advisories,
  zero PHPStan errors, and zero style findings.
- [ ] Final Git state is exactly 26 modified, 25 untracked, 51 total, zero
  staged, with unchanged HEAD/history/refs.
- [ ] No Git staging, commit, remote, tag, push, history, index, or configuration
  mutation occurred.
- [ ] The completion report contains exact evidence, all required headings, and
  a final external byte/hash identity.

Any failed or unreached criterion means the phase is not Complete.

# 18. REQUIRED EXECUTOR COMPLETION REPORT

Populate `docs/task reports/Phase_0X.md` only after complete success. Use exactly
these headings in order:

```text
# PHASE 0X TASK COMPLETION REPORT

## 1. STATUS
## 2. SUMMARY
## 3. BASELINE VERIFIED
## 4. FILES CREATED
## 5. FILES MODIFIED
## 6. ARCHITECTURAL DECISIONS
## 7. DEVIATIONS FROM DIRECTIVE
## 8. DEPENDENCIES ADDED/REMOVED
## 9. ENVIRONMENT
## 10. COMMANDS RUN
## 11. VALIDATION RESULTS
## 12. DOCUMENTATION REVIEW
## 13. SECURITY AND FILE-SAFETY REVIEW
## 14. WARNINGS / KNOWN ISSUES
## 15. QUESTIONS / BLOCKERS
## 16. GIT DIFF SUMMARY
## 17. ACCEPTANCE CRITERIA
## 18. NEXT STEP
```

Report exact:

- baseline/caller identities and pre/post status inventories;
- all created/modified paths and confirmation every other path was preserved;
- the exact dependency and Composer lock delta, including the new path reference
  and final lock SHA-256;
- API statuses, type/property/method/case inventories, package graph, ID counts,
  and autoload proof;
- test files and actual test/assertion counts for focused and complete suites;
- PHP, Composer, Git, PHPUnit, PHPStan, and PHP CS Fixer versions;
- every command, managed-access retry, warning, and verified result;
- no renderer/trust selector/production model/runtime integration/output/I/O;
- documentation, links, encoding, whitespace, file-safety, and Git evidence;
- PASS/FAIL for every Section 17 criterion; and
- no assumptions presented as verified facts.

The final next-step text is exactly:

```text
Await architect review before Phase 0Y.
```

After writing the report, verify strict UTF-8, LF-only, no BOM/NUL/trailing
whitespace, exactly one final LF, exact heading order, zero staged paths, absence
from `HEAD`, and unchanged 26/25/51 status counts. Return the report's final byte
count and SHA-256 outside its body; do not embed a self-digest.

# 19. FINAL OUTCOME

Implement only the pure trusted-developer projection core approved by ADR 0034:
one leaf package, four immutable Experimental developer concepts, one exact
three-marker projector, and one context-free package failure foundation.

The result must be useful to a later clean developer error page without
prematurely selecting the page, renderer, transport, debug-mode activation,
production disclosure, runtime integration, or any other future capability.
