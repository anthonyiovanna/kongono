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

## Phase 5 composition build proof

Phase 5A adds no PHP source, test source, fixture, package, command, schema, or
runtime behavior. Existing quality and Phase 4 acceptance remain the current
implementation evidence.

Phase 5D and 5E tests prove deterministic byte-identical builds,
complete source/output identity, canonical declaration ordering, atomic
publication, no-clobber behavior, stale/missing/mixed/tampered rejection,
changed-during-read handling, generated PHP syntax/style/static analysis, and
absence of request-time discovery or generation. Tests must also prove exact
application/operation/transient lifetime ownership, partial-boot cleanup,
reverse-order release, combined failure, shutdown, sequential/reentrant/
overlapping isolation, and exclusion of secrets and Protected context.

Generated-wiring performance claims require a named reproducible comparison
against the current resolver; design intent is not benchmark evidence. The
Application Graph remains unintegrated, and property hooks must not become a
dependency-resolution test surface. See the [generated wiring guide](generated-wiring.md)
and [Phase 5 roadmap](../architecture/phase-5-roadmap.md).

## Accepted Phase 5C proof obligations

Phase 5C is documentation and architecture only; the accepted source path is
`<application-root>/kongono.composition.json`, and it adds no parser, schema,
package, command, fixture, source test, artifact, wiring, or runtime test.
Phase 5B is accepted, its `>=8.5.0 <8.6.0` constraint is current, and existing
Phase 4 acceptance, Hantei, and canonical quality remain current implementation
evidence.

The Phase 5D package suite proves the exact six-member schema/version grammar,
strict JSON and duplicate rejection, three argument forms, canonical symbols,
three lifetimes, two root kinds, all bounds, stable validation order, path/file
safety, changed-during-read rejection, provenance, non-disclosure,
deterministic output, raw-versus-semantic identity, and stale/tampered failure.
It uses fresh system-temporary application/output directories and leaves no
repository fixture state. The Phase 5E suite proves canonical generated bytes,
exact identities, static inspection, explicit aggregate order, three lifetime
rules, operation isolation/leases, reverse and partial cleanup, exact failure
precedence, closed/retired state, non-disclosure, stale/tampered rejection, and
no-clobber publication. Phase 5F additionally proves exact loading and
unchanged web lifecycle ownership. Phase 5G tests the workflow, exact skeleton
inventory/declarations, real route and unmatched pipeline, deterministic
commands, mirrored external installation, repeated build/check/run isolation,
and one-request PHP-CGI exit. DI and Graph remain unintegrated. See the
[declaration guide](composition-declarations.md),
[composition build guide](composition-build.md), and
[application workflow guide](application-workflow.md).

## Phase 5 external acceptance

The root-owned `composer acceptance:phase5` command is the black-box Phase 5H
gate. Its 28 ordered scenarios cover offline mirrored install; first/repeated
build, check, and run; exact 200/404 bodies; missing, invalid, stale, tampered,
crossed, and renamed state; failed-build atomicity; sequential root isolation;
operation retirement; nondisclosure; and owned cleanup. The PHPUnit boundary
runs the harness twice, requires byte-identical canonical single-LF JSON and
empty stderr, and proves repository status, index, lock, protected content, and
temporary residue remain unchanged.

This evidence closes only the supported pre-1.0 application-composition slice.
It is not a Stable API, full-framework, production-readiness, deployment, or
Phase 6 test claim.

## Phase 6E database proof

Phase 5 is complete at checkpoint
`c431482bea664ce3c3201794332c62cab1ed034e`. Phases 6A and 6B change
documentation and architecture only. Phase 6C implements the accepted neutral
and PostgreSQL PDO adapter contracts, package tests, architecture/static/style
coverage, and one root-owned external harness.

Focused neutral and adapter tests cover exact value/boundary validation,
capability and result-shape failures, duplicate labels and ordinal rows,
one-stream and one-transaction restrictions, state transitions, reverse cleanup
and combined failure, no retry, operation isolation, and nondisclosure. Static,
style, and architecture proof covers the dependency direction, Experimental API
status, closed diagnostics, sensitive configuration, and native PDO isolation.

The Phase 6D package tests additionally prove immutable detached plan shapes,
identifier and collection bounds, capability derivation, explicit all-row
intent, canonical PostgreSQL 17 output, ordered parameters, compiler reuse,
fiber isolation, foreign-plan rejection, and nondisclosure. Architecture proof
locks the 26-package dependency direction, mapping and migration package
surfaces, and absence of native/provider and later-phase declarations.

The exact external command accepts one caller-supplied PostgreSQL binary
directory, requires PostgreSQL 17.2, uses only loopback and a fresh external
temporary tree, and emits one canonical JSON line. Its prior 76 cases retain
the Phase 6C provider proof and add real Select/Insert/Update/Delete compilation,
quoted identifiers, ordering, limit/offset, RETURNING, transaction execution,
explicit all-row mutation, raw-SQL coexistence, adversarial rejection, and
complete process/tree cleanup. Phase 6E adds typed-catalog, exact-history,
destructive-policy, all-operation/type, advisory-lock, transactional rollback,
reserved-history, caller-session, and nondisclosure cases while retaining all
76 prior cases. Phase 6F retains all 104 Phase 6E cases and adds exactly 16
generated mapping cases for 120 total, including current validation, CRUD,
scalar/null hydration, both identity types, cardinality rejection, caller-owned
rollback/reuse, repository independence, deterministic failure, nondisclosure,
and cleanup. It makes no universal portability, automatic recovery, ORM, or
production-service claim. Phase 6G adds exact-current loading, composition,
operation isolation, and data-skeleton proof while retaining all 120 earlier
cases for 140 accepted cases before Phase 6H.

Phase 6H retains those cases and adds the named whole-platform closure proofs:
simultaneous temporary-relation isolation, read-committed visibility across
commit and rollback, peer survival, generated operation identity/lifetime
boundaries, exact-current state refusal, write-free copied-application checks,
nondisclosure, and complete cleanup. The success JSON also contains the four
ordered version-1 performance summaries. The final observed case and PHPUnit
assertion counts are evidence rather than advance-locked contracts. See
[ADR 0081](../adr/0081-postgresql-pdo-adapter-and-real-provider-proof.md),
[ADR 0082](../adr/0082-immutable-database-sql-plans-and-postgresql-dialect-compilation.md),
[ADR 0083](../adr/0083-deterministic-schema-migrations-and-postgresql-execution.md),
[ADR 0084](../adr/0084-compile-time-data-mapping-and-generated-repositories.md),
[ADR 0085](../adr/0085-generated-data-application-and-operation-owned-sessions.md),
[ADR 0086](../adr/0086-phase-6-external-data-platform-acceptance-and-checkpoint.md),
[Phase 6 external acceptance](../architecture/phase-6-external-acceptance.md),
[performance baseline](database-performance-baseline.md),
the [Data Platform guide](../architecture/data-platform.md),
[database contract](../architecture/database-contract.md),
[SQL plan guide](../architecture/database-sql-plans.md),
[migration guide](../architecture/database-migrations.md),
[mapping guide](../architecture/database-mapping.md),
[engineering contract tables](database-contracts.md), and [Phase 6 roadmap](../architecture/phase-6-roadmap.md).

## Phase 7B-7D persistence build and generation proof

Phase 6 is complete at checkpoint
`f5903134e77bb2ecfaece6ee6b435a8ab2ecbd05`. Phase 7A accepts documentation
architecture. Phase 7B adds eight focused package test files for the neutral
contract package. Phase 7C adds ten focused test files and four PHP fixtures for
the build package and strengthens the root architecture suite. The canonical
quality gate now covers twenty-nine package manifests, 478 production package
PHP files, 244 package test/fixture PHP files, and the unchanged 43 root tests.

Phase 7B tests prove exact identity/token validation without autoloading or
stringification; immutable detached bounded changes and change sets; exact
context signatures and absence of an implementation; flush-result invariants;
seven non-disclosing diagnostics; the exception family; API statuses; package
direction; and absence of prohibited runtime/mapping/database/integration code.
Later metadata, generated evidence, concrete contexts, relationships, and
external PostgreSQL proof require their own authenticated phases and focused
tests. Phase 7C tests add deterministic repeated builds; strict JSON/schema and
bounds; construction/field/aggregate/relationship validation; current mapping
consumption; canonical provenance; structural/current distinction; guarded
publication; changed-input and path rejection; disclosure safety; exact API
statuses; and prohibited-runtime scans. Phase 7D adds six focused test files for
deterministic emission, the exact generated API, invariant-preserving hydration,
typed extraction/identity/snapshot/change behavior, structural/current
validation, unexpected executable-syntax no-execution, malicious-token and
stale rejection, content-addressed no-clobber publication, and external fresh-
output lint/maximum-static-analysis/style/load proof. Phase 7E adds focused
proof for exact one-entity ownership, managed/new/removed state, zero-or-one
change execution, explicit absence/conflict outcomes, caller database identity,
zero transaction lifecycle calls, terminal uncertainty, reference release,
nondisclosure, isolation, no retry, and no automatic flush. Phase 7F adds proof
for strict separate source admission, deterministic generated repository bytes,
non-executing/current validation, content-addressed no-clobber publication,
exact one-call capability forwarding, bounded loaded shapes, one-level cascade
policy, absence/conflict distinction, operation isolation, and reference release.
Phase 7G adds exact-current generated loading and whole-workflow build/check
proof. Phase 7H adds the root-owned PostgreSQL 18.6 external matrix, including
operation-local identity behavior, overlapping-operation isolation, explicit
transaction control, optimistic conflicts, bounded aggregate loads and
one-level cascades, nondisclosure, cleanup, and deterministic performance-
summary math. The independent Phase 6 command continues to prove the historical
PostgreSQL 17.2 regression profile.

See [ADR 0087](../adr/0087-explicit-persistence-model-and-compile-time-orm-boundary.md),
[ADR 0088](../adr/0088-operation-scoped-identity-change-tracking-and-transaction-authority.md),
[ADR 0089](../adr/0089-neutral-persistence-contracts-and-package-boundary.md),
[ADR 0090](../adr/0090-deterministic-persistence-model-and-artifact-compilation.md),
[ADR 0091](../adr/0091-deterministic-generated-persistence-state-evidence.md),
[ADR 0095](../adr/0095-phase-7-external-persistence-acceptance-and-checkpoint.md),
[persistence build engineering](persistence-build.md),
[persistence generation engineering](persistence-generation.md),
[Phase 7 external acceptance](../architecture/phase-7-external-acceptance.md),
[persistence performance evidence](persistence-performance-baseline.md),
the [Deterministic Persistence guide](../architecture/deterministic-persistence.md),
and the [Phase 7 roadmap](../architecture/phase-7-roadmap.md).

## Phase 8B through Phase 8G Console proof

Phase 8B adds eight focused `kongono/console` unit-test files. Phase 8C adds four
Console parser/specification test files, ten `kongono/console-build` test files,
and extends the root API architecture suite. The exact inventory becomes
thirty-five package manifests, 609 production package PHP files, 320 package
test PHP files, and 49 root test PHP files. Enum-owned production diagnostics
become 290 unique values and the combined production diagnostic/Hantei identity
becomes 298 unique values, including exactly `KGN-CONSOLE-1001` through
`KGN-CONSOLE-1010` and `KGN-CONSOLE-BUILD-1001` through
`KGN-CONSOLE-BUILD-1005`.

The focused tests execute identifier, summary, input, invocation, failure, and
result bounds and invariants; detachment, order, lookup, and policy combinations;
exact lifecycle and handler signatures; diagnostic context safety; exception
marker behavior; optional previous-cause retention; safe stringification; and
adversarial nondisclosure. The architecture test proves one exact API-status
attribute per production declaration, the Experimental/Internal/Public split,
the exact package dependency and tooling boundary, validator equality, and the
absence of forbidden dependencies, ambient globals, parsing, registries,
presentation, process behavior, and concrete lifecycle implementations.

Focused tests prove deterministic already-tokenized parsing, secure-carrier
nondisclosure, strict source compilation, canonical artifact evidence,
path/read safety, invariant-owner rehydration, and exact-current validation.

Phase 8D adds nine `kongono/console-runtime` test files and extends the Console
contract test. They prove exact immutable binding and lookup, fresh operations,
one explicit cancellation check, result identity, cooperative cancellation, no
late relabeling, reverse every-attempt cleanup, retire-before-release, primary
and cleanup identity/order, hostile-message nondisclosure, early-retirement and
reentrancy misuse, different-dispatcher independence, and sequential recovery
after success, cancellation, handler failure, and cleanup failure. Phase 8E
adds result-record boundary tests plus deterministic human/machine result and
help parity, canonical key/type ordering, Protected sentinel absence, unknown
data rejection, exact one-port writes, and safe output-failure proofs. Phase 8F
adds exact family/definition, typed handler, adversarial disclosure, presentation
parity, generated-output, external lint/PHPStan/style, operation lifecycle, and
Composition lease-cleanup proofs. Phase 8G adds focused package and root
integration tests for exact source/model/enum/factory definition parity, all
accepted and rejected process grammars, argument bounds, nondisclosure,
non-mutating absent/current planning, partial/tampered/linked/outside failure,
explicit atomic/idempotent/no-clobber application, changing-source identities,
human/machine and exit parity, stream selection, one-write failure without
retry, cleanup, sequential isolation, direct-source and Composer-proxy `kongo`
execution, and zero repository mutation from fresh external temporary roots.
Phase 8H adds `composer acceptance:phase8`, a root-owned real-process profile
covering both executable edges, human/machine parity, malformed grammar and
source, plan/apply identity truth, exit-3 no-clobber conflict, recovery,
nondisclosure, status preservation, and cleanup. Current execution evidence is
Windows; argv arrays and the absence of shell syntax define the portability
boundary without claiming execution on every operating system.

## Phase 9B neutral Identity Access proof

Phase 9B adds eight focused `kongono/identity-access` unit-test files and extends
the root architecture suite. The repository inventory becomes exactly
thirty-seven package directories/manifests, 639 production package PHP files,
337 package test PHP files, and 56 root test PHP files.

The focused tests prove every byte boundary for opaque principal/resource
identifiers and canonical action/resource-type names; valid UTF-8 and C0/C1
control rejection; exact preservation, equality, case sensitivity, and absence
of normalization or autoloading; all five principal kinds; all eight
authentication outcome factories and principal-presence rules; the exact inert
Permit/Deny/Indeterminate vocabulary; resource-reference access without domain
loading; explicit anonymous/authenticated Security Context shape without
ambient state; and exact final/readonly/enum/property/method surfaces.

Diagnostic and exception tests prove five unique `KGN-ACCESS-1001` through
`KGN-ACCESS-1005` identities, exact categories/titles/remediations, Safe bounded
context, package-marker membership, cached detached context, previous-cause
identity, safe stringification, and rejected-input nondisclosure. Reflection
and architecture proofs reject Stringable, JSON/array/serialization/debug magic,
global I/O, discovery, forbidden dependencies, extra public APIs, and any
lower-package dependency on Identity Access. These tests use synthetic values,
not real credentials, providers, sessions, networks, clocks, randomness,
environment state, or private data.

See [ADR 0107](../adr/0107-neutral-identity-and-access-contracts-and-package-boundary.md),
the [Identity and Access Security guide](../architecture/identity-and-access-security.md),
and the [Phase 9 roadmap](../architecture/phase-9-roadmap.md).

## Phase 9C deterministic Authentication proof

Phase 9C adds eight focused `kongono/authentication` unit-test files and extends
the root architecture suite. At Phase 9D the current repository inventory is
exactly thirty-nine package directories/manifests, 674 production package PHP
files, 353 package test PHP files, and 56 root test PHP files.

Value tests prove exact mechanism grammar and equality, explicit instant
ordering and elapsed time, strength bounds/comparison, requirement bounds,
facts invariants and object identity, and request detachment/order at zero, one,
eight, and invalid entry counts. Verification tests prove all eight outcomes,
including facts and principal presence only for Authenticated.

Orchestrator tests prove Absent, Ambiguous, and Unsupported without unintended
verifier calls; exact mechanism selection with no priority or fallback; one
begin/verify/retire order; non-authenticated pass-through; mechanism mismatch,
future authentication, insufficient strength, expired validity, and excessive
age handling; and inclusive validity, age, and strength equality bounds.
Lifecycle fixtures prove unchanged begin/verification failure propagation,
cleanup-only and combined failure preservation, exactly-once retirement, fresh
sequential operations, and independent reentrant call state.

Diagnostic and exception tests prove all eight identifiers, exact marker/base/
subclass shapes, Safe bounded context, safe stringification, and exact retained
cause identities without disclosure. Architecture proofs enforce 39 acyclic
manifests, exact Authentication dependencies, no lower-package dependency,
seventeen production declarations with exact API status and public shape, and
absence of ambient globals, clock/request/session/network access, credentials,
provider SDKs, authorization/policy APIs, Security Context construction,
serialization, mutable/static call state, and extra capability imports. Tests
use synthetic non-secret evidence only.

See [ADR 0108](../adr/0108-deterministic-explicit-authentication-orchestration.md),
the [Identity and Access Security guide](../architecture/identity-and-access-security.md),
and the [Phase 9 roadmap](../architecture/phase-9-roadmap.md).

## Phase 9D deterministic Authorization proof

Phase 9D adds exactly eight focused `kongono/authorization` unit-test files and
extends the root architecture suite. Value tests prove every canonical bound
and exact three-field policy-reference equality. Request and evidence tests
prove constructor order, exact object identity, and evidence interfaces limited
to `reference()` or `profile()`.

Engine tests prove one/32 registration and zero/33/non-list/non-evaluator/
duplicate rejection; one evidence-reference read; mismatch and unsupported
Indeterminate results without evaluator execution; exact name/version/
provenance selection; identical request delivery exactly once; exact Permit,
Deny, and Indeterminate preservation; safely wrapped failure; and sequential/
reentrant isolation. Receipt tests prove only the bounded fields, forced
Indeterminate mismatch/unsupported shapes, and absence of guard, enforcement,
serialization, replay, identifier, payload, evaluator, and failure surfaces.

Diagnostic, exception, and architecture proofs enforce six unique identifiers,
Safe fresh contexts, nondisclosure, exact native previous, exact API statuses
and public methods, eighteen production files, eight test files, 39 acyclic
manifests, no lower-package dependency on Authorization, and absence of policy
language/loading, role/permission semantics, ambient state, HTTP/session,
Composition, database enforcement, audit, telemetry, and Phase 9E surfaces.
Tests use inert synthetic non-secret fixtures only.

See [ADR 0109](../adr/0109-deterministic-deny-by-default-authorization-evaluation.md),
the [Identity and Access Security guide](../architecture/identity-and-access-security.md),
and the [Phase 9 roadmap](../architecture/phase-9-roadmap.md).

## Phase 9G policy and supported workflow proof

Phase 9G focused tests prove the closed bounded policy source, canonical
root-independent artifact identity, separate structural/current validation,
write-free inspect/check, atomic idempotent publication, conflict no-clobber,
source/builder currentness, path containment, and bounded nondisclosure. The
supported application tests prove one operation context, one authorization
request/evaluation, exact-Permit-only execution, deny/indeterminate prevention,
and exactly-once cleanup. Console and architecture tests prove the exact three-
command edge and forty-four acyclic manifests.

Gate D additionally copies `skeletons/security-minimal` to a fresh external
temporary root, installs from local package mirrors with network and plugins
disabled, builds, checks, runs Permit and Deny scenarios, and confirms the
repository and permanent environment remain unchanged. This is implementation
proof, not the Phase 9H external acceptance harness.

## Phase 9H external security proof

`composer acceptance:phase9` runs the root-owned security harness with no
arguments. It uses fresh child processes and one owned temporary application,
proves exact-Permit enforcement, authentication/authorization separation,
transport rejection, operation cleanup and isolation, policy currentness,
read-only tooling, Phase 8 compatibility, nondisclosure, and complete cleanup.
Success is one canonical `kongono-phase9-security` JSON line; failure is only
the fixed `phase9_acceptance_failed` stderr token. This profile is milestone
evidence, not a production provider or security certification.

## Phase 10B neutral observability proof

Phase 10B adds nine focused package test files plus architecture coverage. The
suite proves exact enum order; key, text, float, correlation, count, and schema-
version bounds; canonical ordinal attribute ordering and object identity;
explicit fresh uncorrelated/correlated contexts; the inert immutable
observation envelope; the one-method recorder interface with no framework
implementation; and eager bounded non-disclosing diagnostics.

Architecture proof covers the exact 17-declaration API surface and status map,
root tooling integration, package dependency direction, absence of ambient
state and future runtime/provider behavior, and the historical Phase 10B
45-package/749-production/388-package-test inventory.

## Phase 10C tracing proof

Phase 10C adds 13 focused package test files plus architecture coverage. Tests
prove exact ID grammars and nondisclosure, enum/API surfaces, typed propagation,
root-only sampling and collaborator order/counts, new/inbound root behavior,
root/child propagation and parenting, exact ownership and duplicate rejection,
64/65 child capacity, 25/26 attributes and seven reserved collisions,
deterministic observation mapping and exact caller-object retention, Drop,
final recorder failure, idempotent retirement, retained-reference release, and
sequential/overlapping/reentrant isolation.

Architecture and Hantei proofs enforce exact 23-production/13-test Tracing
inventory, statuses and public surfaces, three inward package dependencies,
zero reverse dependency, no production collaborator implementation or ambient
current span, exact root tooling/profile equality, an acyclic 46-package graph,
772 production PHP files, and 401 package-test PHP files. Tests use only local
doubles and implement no carrier parser, exporter, backend, metric/event/audit
runtime within Tracing, OpenTelemetry, or application behavior.

## Phase 10D metrics proof

Phase 10D adds exactly 15 focused Metrics test files plus architecture coverage.
They prove finite int/float identity, unit grammar, four instrument kinds,
three lifecycle states, sensitive/unbounded dimension denial, canonical schema
ordering, exact object ownership and dimension matching, 64-series instrument
and 256-series plan limits, histogram boundary and inclusive-bucket semantics,
Counter/UpDownCounter/Gauge/Histogram aggregation, overflow-before-mutation,
no raw sample retention, canonical observations, recorder failure finality,
reference release, idempotent retirement, nondisclosure, and fresh sequential,
overlapping, and recorder-reentrant isolation.

Architecture and Hantei proofs enforce the exact 30-production/15-test Metrics
inventory, statuses and public surfaces, three inward package dependencies,
zero reverse dependency, five diagnostic identifiers and repository totals of
345 enum and 353 production identifiers, exact root tooling/profile equality,
an acyclic 47-package/160-edge graph, 802 production PHP files, and 416 package-
test PHP files. Forbidden-scope checks exclude global registries/providers,
dynamic labels, exporters/backends, ambient state, application integration,
OpenTelemetry, PSR logging, and event/audit constructs inside Metrics.

## Phase 10E structured evidence proof

Phase 10E adds exactly eight focused Operational Events and eight focused
Security Audit test files plus architecture coverage. Tests prove exact enums
and API surfaces; finite field/schema bounds and canonical order; duplicate,
reserved, sensitive, unknown, missing, wrong-kind, and out-of-domain rejection;
supplied-field disclosure; exact event and audit observation mapping; 32-
attribute ceilings; zero recorder calls on validation failure and exactly one on
success; previous-cause retention without disclosure or retry; and fresh
sequential, overlapping, and recorder-reentrant isolation.

Architecture and Hantei proofs enforce exact 14-production/8-test Operational
Events and 15-production/8-test Security Audit inventories, separate catch and
dependency boundaries, six new diagnostics, exact root tooling/profile
equality, an acyclic 49-package/166-edge graph, 831 production PHP files, and
432 package-test PHP files. Forbidden-scope checks exclude conversion APIs,
generic logging, arbitrary context, ambient providers, buffers, retries,
exporters, stores, retention enforcement, tamper evidence, compliance claims,
authorization behavior, product analytics, and application integration. Prior
Observability, Tracing, and Metrics suites remain regression gates.

## Phase 10F OpenTelemetry bridge proof

Phase 10F adds exactly 13 focused package test files plus Hantei and API
architecture coverage. Tests parse each emitted request with generated protobuf
classes and prove the exact resource, schema, scope, trace IDs/timestamps/status,
Counter/UpDownCounter/Gauge mappings, lossless histogram aggregate gauge family,
and separate operational-event/security-audit log semantics. They also prove
correlation remains an attribute, audit durability remains `not_guaranteed`,
and no body, event, link, boundary, quantile, parent, identity, or authority is
invented.

Boundary tests cover queue capacities 1 and 1024, batch sizes 1 and 256,
contiguous FIFO batching, fixed endpoint/content-type arguments, no record-time
I/O, visible no-drop backpressure, mapping-before-I/O, partial progress, no
retry, send/flush/close and combined failures, exact previous throwable,
nondisclosure, reentry rejection, idempotent close, post-close rejection,
reference release, and separate-instance isolation.

Phase 10F architecture and Hantei proofs enforce the exact 24-production/13-test package
inventory, API statuses, three inward local edges, zero reverse edge, direct
dependency/lock shape, and the then-current fifty acyclic packages, 169 local
production edges, 855 production PHP files, and 445 package-test PHP files. Forbidden-scope scans
exclude SDK/provider/global state, auto-configuration, discovery, HTTP/client,
retry, background cleanup, application integration, and backend mechanisms.

## Phase 10G observability workflow proof

Phase 10G tests cover strict closed configuration parsing, separate source/model/
implementation/artifact identities, canonical exact-current validation,
write-free inspection/checking, atomic no-clobber publication, Windows path
adversaries, immutable loaded configuration, direct factory construction, exact
trace/metric begin and cleanup order, work/result/failure identity, event/audit
separation, 64-operation/reentrant isolation, explicit flush/terminal close,
and nondisclosure.

Console tests prove two read-only commands with exact input and human/machine
parity. Fresh-copy skeleton tests prove offline local installation, build,
check, run, idempotence, tamper/stale rejection, zero-network transport, and
cleanup. Architecture and Hantei proofs enforce 52 packages, 182 local edges,
the final production/test inventories, statuses, dependency directions, and
absence of ambient/provider/backend/Phase 10H mechanisms.

## Phase 10H external observability proof

`composer acceptance:phase10` runs the root-owned fresh-process harness with no
arguments. It creates one unique system-temporary application root containing
a space, copies the observability skeleton without links, installs exact local
packages offline with `symlink: false`, and proves build/check/run/idempotence,
tamper and stale rejection, read-only Console parity, mutation-intent refusal,
typed signal separation, OTLP protobuf parsing, cardinality, backpressure,
failure ordering, explicit flush/close, isolation, nondisclosure, and cleanup.

The harness also reruns every Phase 10 package suite and the Phase 8 and Phase
9 external acceptance commands in fresh children. Success is one canonical
LF-terminated JSON document emitted only after cleanup; failure emits only the
fixed `phase10_acceptance_failed` token. No case count is predeclared: the
passing run records the observed count. This proof is test evidence and makes
no production-readiness, security-certification, portability, performance, or
Phase 11 claim.
