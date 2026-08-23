# Testing

## Purpose and runner

PHPUnit 13 is the official runner for Kongono's own framework repository. This decision gives core development one test lifecycle and assertion model; it does not constrain applications built with Kongono or create a framework testing API.

Run tests with:

```bash
composer test
```

The root suite discovers repository tooling and architecture tests beneath `tests/`, plus package tests beneath the API Metadata, Kernel, Application Graph, Dependency Injection, Diagnostic Presentation, and Diagnostics trees. Each package declares its own development test namespace, which the root repeats in `autoload-dev` because dependency development mappings are not imported. The exact test inventory is 80 PHP files. Root architecture owns the exact 49-type API-status inventory, five-way exception-marker separation, and 68 globally unique diagnostic IDs split 6 Kernel, 11 Graph, 25 DI, 15 Presentation, and 11 Diagnostics across every `packages/*/src` tree; its token scanner remains test-only infrastructure rather than a Graph or dependency-discovery frontend.

The 49 production PHP files plus 80 test PHP files form an exact 129-file
source-and-test inventory. With `.php-cs-fixer.dist.php`, syntax validation has
130 entries; style checks cover the 129 PHP files.

Phase 0S adds one focused five-method API Metadata test covering the exact enum
vocabulary, attribute shape, bootstrap exemptions, removal of the legacy Kernel
FQCNs, and the two-source-file package boundary. Root enforcement discovers all
six package source trees after Phase 0Z expands the inventory to 49 types.

Phase 0M tests cover all 42 package-local catalog cases, exact enum case names,
identifiers, titles, remediation strings, global uniqueness and domain syntax;
the exact Public marker and Internal base reflection shapes; every reachable
deliberate throw mapping; both static DI-1025 invariant-defense sites; unchanged
messages and state guarantees; optional-cause identity and native code behavior;
and unchanged autoloader and consumer-constructor propagation. Catalog tests
duplicate the authorized expectations explicitly; production catalogs do not
read Markdown or external data at runtime.

Phase 0P adds seven focused Diagnostics test files covering the exact
vocabularies, schemas, detached snapshots, scalar-kind checks, byte-oriented text
representation and truncation, package-local construction failures, API status,
and absence of ambient or mutable global state.

Phase 0Q adds focused Graph coverage for all 11 catalog-owned schemas and exact
throw-site values, numeric first-mismatch capture, arbitrary string-key omission,
the closed broad kind-token vocabulary, schema order, bounded byte
representation, same-object context access, detachment, cause/code/message
preservation, failure priority, success behavior, exact marker/base shapes, and
the one-way Graph-to-Diagnostics dependency. Phase 0T adds one exact seven-method
Kernel diagnostic-context test covering all six schemas and maps, closed values,
same-object ownership, detachment, reflection shapes, marker separation, and the
one-way Kernel-to-Diagnostics dependency. Phase 0U adds one exact ten-method DI
diagnostic-context test covering all 25 schemas, 26 throw-site maps, bounded token
algorithms, context ownership, native propagation, and the independent
DI-to-Diagnostics edge. Phase 0X adds six focused Diagnostic Presentation test
files covering the exact owner and catalog vocabularies, package-local exception
boundary, immutable field and diagnostic validation, pure three-marker
projection, ambiguity-before-access, exact single reads, detachment, disclosure
preservation, native failure propagation, and absence of retained state. Phase
0Z adds ten focused renderer tests covering exact class shape, full and empty
golden documents, owner labels, final-boundary escaping, field semantics,
determinism, maximum bounds, semantic accessibility grammar, and absence of
active or external surfaces. These are string/DOM contract tests, not browser,
assistive-technology, HTTP, CSP, or production-integration tests. The canonical
suite contains exactly 241 tests.

That 241-test statement is historical Phase 0Z evidence. The current global
inventory after the Phase 3 checkpoint is 151 test PHP files. The canonical
pre-Phase-4A `composer quality` run on PHP 8.5.9 reports exactly 561 tests and
13,004 assertions. Phase-specific counts above remain point-in-time evidence
and must not be relabeled as the current repository total.

## Planned Hantei relationship

The current deterministic commands, configuration, test code, and observable
results remain authoritative. Planned Kongono Hantei will independently select,
run, and interpret applicable accepted evidence and return `PASS`, `FAIL`, or
`BLOCKED`. It does not replace, skip, weaken, or repair a test or the subject it
judges. Stale, partial, irrelevant, or environment-blocked evidence must remain
visible.

Kongono Chie, Kongono Hantei, Kongono Oshie, and knowledge artifacts are not
implemented in Phase 4A. The framework remains testable offline and without AI,
subject to the existing external-access requirements of a literal gate. See the
[developer-intelligence architecture](../architecture/developer-intelligence.md)
and [Phase 4 roadmap](../architecture/phase-4-roadmap.md).

## Failure behavior

The PHPUnit configuration explicitly fails on warnings, risky tests, deprecations, PHPUnit deprecations, notices, incomplete tests, an empty configured suite, and unexpected output. These signals often expose broken assumptions or accidental behavior and must not be hidden by skipping a test, marking it incomplete, or weakening configuration.

Coverage metadata and a coverage percentage are not selected in Phase 0B. Their absence is not permission to claim coverage. Future coverage policy requires separate authorization.

Tests may write PHPUnit's local cache, but they must not introduce unapproved framework functionality, network dependencies, persistent state, or public contracts merely to exercise the runner.

## Exact-state evidence scope

A test or quality result is implementation evidence only for the exact source
state, environment, literal command, configuration, and scope that produced it.
Passing evidence cannot authorize architecture. A historical report, prior
result, equal test count, or model summary is not reusable current proof after
relevant bytes, environment assumptions, or required commands change.

Future deterministic knowledge artifacts must preserve this scope and expose
stale, skipped, weakened, irrelevant, blocked, conflicting, or incomplete
evidence. Planned Hantei may interpret applicable accepted evidence but cannot
elevate it, repair its subject, or replace the underlying command. See [ADR
0061](../adr/0061-versioned-knowledge-source-and-provenance-contract.md) and the
[knowledge foundation](../architecture/knowledge-foundation.md).

## Framework-knowledge artifact tests

Phase 4C adds six package test files with exactly six tests each. They prove the
closed 31-source inventory, authority rejection, deterministic generation,
canonical schema and identities, structural/current stale rejection, atomic
no-clobber publication, exact diagnostics, bounded command streams and exits,
and absence of ambient network/model/workspace behavior. External proof uses
fresh directories outside the repository, changes only copied source bytes,
validates independently generated artifacts, and removes every temporary path.
The canonical suite contains exactly 597 tests; assertion count is observed,
not predeclared as a compatibility contract.

## Read-only Chie tests

## Read-only Hantei tests

Hantei tests prove exact report shapes and precedence, the closed profile,
subject construction, bounded argument-vector processes, Safe diagnostics,
canonical LF-only JSON, and CLI exits. The real `composer hantei:verify` proof
must decode to eight ordered passing checks without locking incidental assertion
counts, timing, cache hashes, wrapper newlines, or raw output lengths.
Phase 4D adds six package test files with exactly six tests each. They prove all
six exact topics, current validation before resolution, cited line ranges,
literal diagnostic matching and limits, inert hostile text, detached values,
five response states, canonical JSON, safe command streams and exits, ten
diagnostics, package/API boundaries, and absence of later role or mutation
authority. Command proofs use fresh external temporary directories and remove
only their own paths. The canonical suite contains exactly 633 tests; its
assertion count is recorded from the completed run rather than predeclared.

Phase 4F focused tests cover the exact curriculum, request grammar, response matrix, source resolution, diagnostic disclosure, CLI streams/exits, stateless calls, and prohibited authority. A fresh external temporary artifact drives raw-process proofs for catalog, lesson, practice, correct, and incorrect states. Incidental final suite assertion totals are observed, not predeclared.
## Phase 4G proposal proof

Focused tests cover canonical spec admission, Safe text bounds, current-artifact
and authority matching, unique complete-line replacement, exact reversible
diffs, provenance and identities, CLI streams/exits, sequential isolation, and
non-disclosure. The real proof constructs but does not apply a candidate, proves
test-only forward/reverse application, and then runs Hantei only against the
unchanged repository. A later external application requires fresh Hantei proof.

## Phase 4 external acceptance

`composer acceptance:phase4` runs the exact ordered 28-scenario root-owned
harness. It covers two byte-identical Knowledge generations, current
validation, cross-role identities and citations, Chie explanation, diagnosis,
proposal, reversal and unapplied state, Oshie catalog, lesson, exercise and
outcomes, offline equivalence, missing, stale, invalid, malicious and
non-disclosure cases, real Hantei PASS, synthetic Hantei FAIL/BLOCKED,
repository non-mutation, bounded process streams, and temporary cleanup.
Synthetic evidence never substitutes for current framework quality, and no
executable Oshie example is claimed.
