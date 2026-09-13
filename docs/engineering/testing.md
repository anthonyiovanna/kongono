# Testing

Phase 18H adds four root-test PHP files and the explicit
`composer acceptance:phase18` fresh-process proof. It exercises the accepted
build, runtime, HTTP, application, copied-skeleton, CGI, and inspection stack
with deterministic sequential and concurrent children, bounded failure,
nondisclosure, fingerprint, and cleanup evidence. Phase 18A-H are Accepted and
Phase 18 is closed.

Phase 18G adds exactly 22 production and six package-test PHP files in View
Inspection. Focused proof covers deterministic canonical identity, in-memory
structural artifact revalidation, corrupt and mixed binding rejection, generated
PHP inertness, closed Safe/Protected disclosure, exact role mapping, Graph
absence/match/mismatch/tamper behavior with `not-established`,
constructed-not-recorded projections, independent verification and tamper
failure, sequential/interleaved/independent/Fiber isolation, diagnostics,
dependency direction, tooling, and prohibited surfaces. The current topology is
95 packages / 402 local production edges / acyclic, with PHP inventories
1,861 / 962 / 96 and 95 validation commands. These tests do not prove live
filesystem, deployment, rendering, production observation, security,
performance, browser certification, Stable/1.0, or production readiness.

Phase 18F adds 20 production and five package-test PHP files in View
Application plus three console handlers and one console test file. Focused
proof covers write-free plan/check, explicit build, idempotence, exact
currentness, no-clobber conflict, unsafe-source rejection, authenticated load,
response-policy composition, exact lower failure return, identity mismatch,
sequential isolation, command plan/apply/check/inspect parity, nondisclosure,
and the fresh-copy `view-minimal` install/build/rebuild/check/run path.

Phase 18E adds exactly 11 production and 10 package-test PHP files. Its focused
suite proves completed-result evidence checks, exact body bytes and derived
content length, explicit status/reason/content type/caller headers, all policy
bounds, case-insensitive reserved and duplicate names, CR/LF/NUL/obs-fold and
non-ASCII rejection, factory-adversary failure, nondisclosure, sequential reuse,
and interleaved isolation. All four view-package trees remain regression scope.
The Phase 18F topology was 94 packages / 394 local production edges / acyclic,
with PHP inventories 1,839 / 956 / 92 and 94 validation commands. See
[ADR 0184](../adr/0184-explicit-completed-view-to-psr-7-response-adaptation.md)
and the [HTTP adapter guide](../architecture/compiled-view-http.md). Phase 18A-H
are Accepted and Phase 18 is closed.

Phase 18D adds exactly 11 package-test PHP files covering its 19-production-file
View Runtime boundary: explicit canonical contained loading; fixed-name bounded
stable reads; exact metadata/generated/class/source/model identity; first load,
immutable class reuse, and collision rejection; HTML text and quoted-attribute
escaping; byte/step boundaries; partial-output rejection; neutral failure
mapping; sequential and cooperatively Fiber-overlapped isolation; retirement;
nondisclosure; exact API/dependency inventory; and forbidden-surface absence.
Architecture proof uses the exact 92-package / 384-edge acyclic graph and
1,805/938/92 PHP inventories. Accepted View Contract and View Build suites remain
regressions. No source is parsed or compiled during rendering. Generated code
and the deployment filesystem are trusted inputs. Passing tests makes no HTTP,
browser, compatibility, performance, security-certification, Stable/1.0, or
production-readiness claim. See [ADR 0183](../adr/0183-exact-current-compiled-view-runtime-and-bounded-rendering.md)
and the [runtime guide](../architecture/compiled-view-runtime.md). Phase 18A-H
are Accepted and Phase 18 is closed.

Phase 18C adds exactly 16 package-test PHP files covering its 30-production-file
View Build boundary: duplicate-aware framed parsing; all six schema and four
node kinds; schema-resolved references and bounded loops; deterministic static
encoding, canonical model and complete source maps; four identities; generated
PHP syntax, forbidden surfaces, direct sink protocol and fresh-child
determinism; structural/current validation; tamper and stale rejection;
contained atomic two-file no-clobber publication; diagnostics, nondisclosure,
API inventory, dependency direction, and generated-PHP inertness. Architecture
proof uses the exact 91-package / 380-edge acyclic graph and 1,786/927/92 PHP
inventories. Passing these tests does not prove runtime loading, rendering,
dynamic escaping, HTTP delivery, browser safety, or production readiness.

Phase 18B adds exactly 17 package-test PHP files covering its 30-production-file
neutral contract boundary: identity and byte grammars; closed scalar/list/
record values; depth and aggregate-node limits; recursive schema shape and
matching; two output contexts; request construction validation; completed
output and derived metadata; result/failure/lifecycle relations; nine
nondisclosing diagnostics; exact API/dependency inventory; zero reverse
production dependencies; and forbidden ambient/effect surfaces. Architecture
proof uses the exact 90-package / 377-edge acyclic graph and 1,756/911/92 PHP
inventories. Passing these tests does not prove compilation, rendering,
escaping, HTTP delivery, authority, browser safety, or production readiness.

Phase 14G adds exactly six package-test PHP files covering its 22-file
production boundary: exact profile/wiring rebinding without generated-PHP
loading, optional completed detached runtime summaries without replay, bounded
Graph/Observability/Security Audit/role evidence, independent verification,
determinism, adversarial non-disclosure, diagnostics, API inventory, dependency
direction, and forbidden-surface absence. Architecture proof uses the exact
75-package / 289-edge acyclic graph and 1,358/686/76 PHP inventories. These
tests do not prove live provider/application state, external authority,
recording, telemetry delivery, certification, or production readiness.

Phase 14F adds exactly 11 package-test PHP files covering its 29-file production
boundary: exact profile currentness, deterministic privacy-preserving wiring,
distinct structural/current validation, contained atomic no-clobber
publication, explicit-path one-use loading, direct adapter identity, one-run
lifecycle, cancellation, cleanup and failure preservation, reentrancy,
sequential instance isolation, diagnostics, nondisclosure, topology, and
forbidden-surface absence. Architecture proof uses the exact 74-package /
280-edge acyclic graph and 1,336/680/76 PHP inventories. Passing these tests
does not prove provider availability, credentials, account state, authority,
external effect, or production readiness.

Phase 14E adds exactly nine package-test PHP files covering exact dependency
and API inventory, eligibility and receipt relations, exact action and
capability mapping, exact usage selection, deterministic evidence identities,
raw-argument omission, visibility projection, request-value preservation,
stateless reuse, and non-disclosing rejection. Architecture proof uses the
exact 73-package / 275-edge acyclic graph and 1,307/669/76 PHP inventories.
Passing these tests does not prove provider availability, authentication,
authorization, a capability grant or invocation, external effect, or
production readiness.

Phase 14D adds exactly 12 package tests for direct adapter binding,
exact-current preflight, one-attempt pull lifecycle, cancellation, monotonic
time, evidence budgets, streaming, receipts, cleanup composition, reentrancy,
sequential reuse, and nondisclosure. Architecture proof uses the exact
72-package / 269-edge acyclic graph and 1,294/660/76 PHP inventories.

Phase 14C adds exactly 14 package tests covering the 19-file
`kongono/model-invocation-build` boundary: identity and budget limits,
detachment and isolation, canonical request/source/artifact digest
reproduction, same-process and fresh-process determinism, raw-message omission,
strict structural and exact-current validation, capability compatibility,
nondisclosing failures, and atomic/idempotent/no-clobber contained publication
with temporary cleanup. Architecture coverage reconciles the 71-package,
265-edge acyclic graph and 1,273/648/76 PHP inventories. Passing these tests
does not prove provider availability, credentials, account state, pricing,
runtime enforcement, Agent authority, or external currentness.

Phase 14B adds exactly 17 package tests covering the 41-file neutral contract
boundary: dependency/API inventory, immutability, enum vocabularies, grammars,
raw-byte/list/integer limits, canonical ordering, profile capability matching,
request/result/stream/receipt relations, nondisclosure, and sequential
isolation. Tests use no provider, network, credential, adapter, environment,
sleep, baseline, suppression, or external state. Passing those
tests would not prove live provider compatibility, availability, pricing,
account entitlement, latency, security, or production readiness. See the
[Provider-Neutral Model Invocation guide](../architecture/provider-neutral-model-invocation.md)
and [Phase 14 roadmap](../architecture/phase-14-roadmap.md).

Phase 13H closes the milestone with a root-owned 40-case external process
harness above the Phase 13G focused syntax, static-analysis, style, unit,
architecture, semantic, adversarial, and file-safety coverage from the neutral
Agent Principal Contract, declaration artifact, and admission evidence into the
bounded runtime and exact-current Agent Application. Tests prove exact plan binding, currentness
rechecks, four cancellation/time checkpoints, nine budget boundaries,
at-most-one proposal and capability effect, deterministic receipts, reverse
cleanup and combined failures, defensive immutability, nondisclosure, exact
dependencies and API status, and absence of ambient/provider/application-loop
authority. Agent Application tests additionally prove deterministic profile and
generated bytes, separate structural/current validation, safe publication,
snapshot/reverified one-start loading, write-free check, exact cleanup transfer,
single-active-operation lifecycle, nondisclosure, and stress isolation. Agent
Inspection tests add exact profile/wiring rebinding, optional completed-runtime
summaries, Graph-role matching, neutral projection, fixed role verification,
deterministic interleaving, dependency/topology, and adversarial nondisclosure/
no-execution/no-recorder proofs. External acceptance additionally proves
canonical sequential and concurrent output, fixed non-disclosing failure,
temporary cleanup, repository preservation, exact-current application loading,
runtime recovery, and bounded cross-system evidence. See the
[Phase 13 roadmap](../architecture/phase-13-roadmap.md) and
[Phase 13 external acceptance](../architecture/phase-13-external-acceptance.md).

`composer acceptance:phase13` runs that deterministic offline proof. It uses
production APIs and root-owned fixture collaborators only, adds no provider or
network access, and does not change the eight canonical quality checks.

`composer acceptance:phase12` is the root-owned 50-case process proof for the
accepted Extension Platform. It uses isolated temporary state, real package APIs
and `vendor/bin/kongo`, canonical bounded output, child-process repeatability
and concurrency, repository fingerprinting, nondisclosure, and complete cleanup.
See [Phase 12 external acceptance](../architecture/phase-12-external-acceptance.md).

> **Phase 12 current boundary:** Phases 12A-12H are implemented to their
> accepted boundaries. Phase 12H adds root-owned external evidence only; the
> topology remains 63 packages / 226 acyclic local edges.

> **Phase 13 closed boundary:** Focused package and root architecture tests plus
> the root-owned 40-case external harness validate Phases 13B-13H. The milestone
> is closed at checkpoint `63376c802ac4672c21458b67b2fcadaf8e03a019` without a
> provider, SDK, network call, or wider authority boundary.

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

## Phase 11A and 11B Graph contract proof

Phase 11A added no production or test PHP and preserved the Phase 10
inventories while proving the exact documentation boundary. Phase 11B adds 23
production declarations, six package-test files, and bounded root architecture
coverage. Tests prove the exact four identity categories and prefixes, malformed
digest non-disclosure, closed enums, field-name boundary, readonly detachment,
canonical schema rejection, the exact version-1 catalog and endpoints, bounded
provenance, diagnostic metadata, package direction, tooling integration, and
the frozen Phase 0J identity. Architecture checks enforce 53 packages, 184
local edges, 912 production PHP files, 467 package-test PHP files, and 64 root-
test PHP files, plus local Markdown links, encoding and ordinary-file safety,
ADR ordering, the eight-row Phase 11
roadmap, and absence of implementation or production-readiness claims.

Canonical `composer quality` remains required before and after the bounded
implementation. Its result proves only the exact source state and configured PHP 8.5
environment. It does not implement the IR, Graph construction, artifact,
consumer, extension, or developer-intelligence boundaries described by the
roadmap. Each later Phase 11 row requires its own focused tests, external proof
where applicable, independently authenticated authority, and fresh canonical
validation.

## Phase 11C Graph construction proof

Phase 11C adds 13 production PHP files and seven package-test PHP files. Focused
tests prove exact text grammar and bounds, canonical record lists, complete
four-node/four-relation schema admission, optional fields, duplicates,
endpoints, permitted self-dependency and cycles, fixed construction bounds,
order independence, fresh fact objects, detached immutable lists, state
isolation, and bounded non-disclosing failures. Root architecture coverage
enforces exact API status and surfaces, 54 package manifests, 187 local edges,
925 production PHP files, 474 package-test PHP files, 64 root-test PHP files,
and the unchanged Phase 0J package.

These tests do not prove Composition projection, serialization, artifact
currentness, publication, inspection consumers, AI integration, production
readiness, or Phase 11D behavior.

## Phase 11E Graph artifact proof

Phase 11E adds 17 production and eight package-test PHP files. Focused tests
prove fresh Graph re-admission, canonical JSON and identities, strict duplicate
and shape rejection, caller-relative currentness, guarded explicit loading,
atomic idempotent no-clobber publication, nondisclosure, isolation, and cleanup.
Root architecture evidence enforces 56 manifests, 196 local edges, 953
production PHP files, 489 package-test PHP files, and 64 root-test PHP files.
These tests do not prove source-code-byte identity, signing, source/workspace
currentness, inspection, CLI/application consumption, AI access, deployment, or
production readiness; those are exclusions of the Phase 11E proof itself.

## Phase 11F Graph inspection proof

Phase 11F adds two production and two package-test PHP files. Focused tests
prove zero and mixed vocabulary counts, builder/validator equivalence,
permutation and repeated-call determinism, separate-process isolation, invalid
count rejection, no field-value access, exact fifteen-field Safe projection,
ordinal Console ordering, fixed failure mapping, read-only behavior,
non-disclosure, help, and source/Composer-proxy parity. Root architecture
evidence enforces 56 manifests, 197 local edges, 955 production PHP files, 491
package-test PHP files, 64 root-test PHP files, and unchanged Console core and
Phase 0J `framework:graph` bytes.

These tests do not prove source, Composition, generated-wiring, workspace,
deployment, or runtime currentness, authorization, individual-fact access,
developer-intelligence integration, production readiness, or Phase 11G behavior.

## Phase 11G Application Graph developer-intelligence proof

Phase 11G adds ten production PHP files and six package-test PHP files. Focused
tests prove exact role vocabulary, deterministic aggregate derivation, zero and
mixed counts, detached serialization, constructor invariants, Safe diagnostic
context, non-disclosure, and independent six-check PASS/FAIL verification.
Root architecture evidence enforces 57 manifests, 201 local edges, 965
production PHP files, 497 package-test PHP files, 68 root-test PHP files, exact
API status, tooling registration, and the corrected four-edge dependency
boundary.

These tests do not prove raw or individual-fact access, Graph/workspace/runtime
currentness, role execution, authorization, mutation, AI/model/provider/network
integration, or production readiness.

## Phase 11H external Application Graph proof

Phase 11H adds four root-test PHP files and `composer acceptance:phase11`.
The external 40-case profile proves real Composition-to-Graph construction,
canonical artifacts, explicit guarded loading, bounded inspection, the
supported `kongo` path, visibility-safe role projections, unchanged Chie and
Hantei boundaries, hostile failure behavior, non-disclosure, forced cleanup,
repeatability, concurrency, repository preservation, and zero child/lifecycle
residue. It adds no production PHP or dependency edge.

## Phase 12A architecture and documentation proof

Phase 12A adds no production or test PHP. Validation therefore proves the exact
two-created/twelve-modified Markdown boundary, local links, ADR indexing, API
status, 57-package/201-edge acyclic topology, UTF-8/LF safety, and preservation
of Composer, production/test inventories, scripts, configuration, generated
artifacts, and historical caller records.

Phase 12B adds exact unit and architecture proof for all contract enum cases,
identifier/version/digest bounds, malformed grammar, canonical zero/max/over
limits, list/type/order/uniqueness rejection, detachment and object identity,
diagnostic metadata, previous throwable identity, and nondisclosure. The root
historical architecture proof enforces 58 manifests, 203 acyclic local edges, the exact
21-source/12-test inventory, tool integration, API status, marker independence,
and prohibited behavior. Phase 12C updates current topology proof to 59
manifests and 206 acyclic local edges.

The proof does not establish admission, currentness, trust, capability grant,
Graph truth, authorization, hosting, execution, sandboxing, external protocol,
distribution, or production readiness. Requests are not grants, contributions
are not Graph facts, visibility is not authorization, lifecycle is not hosting,
and category is not a security decision. No Application Graph integration
exists. Phase 12C adds exact empty/one/max/over-limit build proof, type/list/
order/uniqueness rejection, canonical key and digest checks, malformed and
noncanonical artifact rejection, strict contract reconstruction,
caller-relative stale detection, nondisclosing diagnostics, and path-safe
atomic idempotent no-clobber publication including symlink and Windows junction
boundaries. No public loader or prohibited extension authority exists. Phase
12D now proves exact selection/currentness, bundle ordering and bounds,
attributable provenance, explicit two/three-claimant winners, losing-bundle
validation, Graph conflicts, nondisclosure, deterministic identity, and the
historical 60-package / 212-edge boundary. Phase 12E adds grant-plan,
Reflection, generated-byte, no-execution, publication-safety, nondisclosure,
and 61-package / 217-edge coverage. Phase 12F adds exact loading-shape,
irreversible-load, no-constructor-execution, lifecycle order, reverse cleanup,
operation isolation/lease, retirement, failure-identity, nondisclosure, and
fresh-process proof. Current architecture coverage is 62 packages / 220 edges;
Phase 12G adds structural two-file, identity-binding, aggregate-total,
role-derivation, independent-verification, no-load, nondisclosure, supported
CLI, and topology coverage. Current architecture coverage is 63 packages / 226
edges; Phase 12H closes the milestone with root-owned external evidence only.

## Phase 14 external acceptance

`composer acceptance:phase14` runs fresh external PHP processes over production
Phase 14 APIs with root-owned fixtures and an offline fake adapter. It proves
canonical success/failure streams, sequential and concurrent determinism,
bounded output, repository preservation, and complete temporary cleanup. See
[Phase 14 external acceptance](../architecture/phase-14-external-acceptance.md).

## Phase 15A-15H testing posture

Phase 15A is documentation architecture. Phase 15B adds deterministic unit and
architecture tests for the optional neutral contract only. Local PSR-7 fakes
prove that operation and result construction never call body-stream methods.
Boundary tests prove exact identities, digests, origins, budgets, enums, sets,
transfers, receipts, API statuses, dependencies, nondisclosure, and absence of
network effects. No network probe, endpoint, credential, transport, or new
quality stage is added. Later rows are planned to use bounded external-process
conformance for SSRF address classes, resolution binding, DNS rebinding,
redirect revalidation, TLS policy, streaming and decompression limits,
backpressure, cancellation, partial writes, uncertain effects, concurrency,
pool retirement, cleanup, and nondisclosure. Public Internet access and real
credentials are not acceptance dependencies. See
[ADR 0155](../adr/0155-explicit-outbound-http-and-governed-network-egress-boundary.md),
[ADR 0156](../adr/0156-neutral-versioned-network-egress-contract-foundation.md),
and the [Phase 15 roadmap](../architecture/phase-15-roadmap.md).

Phase 15C adds deterministic package tests for identity and closed policy
vocabularies, canonical origin ordering, policy compatibility, exact JSON
framing/member order/digests, structural and caller-relative current
validation, Protected-data nondisclosure, path-safe atomic no-clobber
publication, and exact dependency/API boundaries. They use temporary local
directories only and never resolve a host, open a socket, access credentials,
or perform network I/O.

Phase 15D adds deterministic in-memory tests for canonical address families and
conservative categories, mixed-result denial, exact selected-address binding,
policy and authority currentness, stable capabilities, header/transfer/time
budgets, pull sequencing, cancellation, uncertainty, cleanup, reentrancy,
sequential reuse, body-stream non-consumption, diagnostics, and contract
evolution. Phase 15F adds deterministic in-memory package tests for canonical
profiles, generated wiring, current validation, publication, contained loading,
capability preflight, application lifecycle, one-operation delegation, cleanup,
nondisclosure, and dependency/API boundaries. Phase 15G adds deterministic
offline tests for exact profile/wiring and optional operation/result binding,
canonical bounded evidence, all six terminal outcomes, hostile body
non-consumption, Graph/Observability/Security projection, role-addressed views,
independent verification, and nondisclosure. No test resolves DNS, opens a
socket, reads proxy or credential state, uses wall-clock sleeping, or reaches
the public Internet. The current inventory is 1,514 production, 768
package-test, and 84 root-test PHP files. Phase 15H adds the bounded local
external acceptance harness and Phase 15 is closed at one exact checkpoint.

## Phase 15E cURL transport and Phase 15F application workflow status

Phase 15E accepts ADR 0159 and implements the optional Experimental
`kongono/network-egress-curl` HTTP/1.1 adapter. It binds the canonical origin
to the single Phase 15D selected address, uses explicit in-memory CA-bundle
bytes with strict TLS verification, and performs one bounded synchronous
transfer without proxy, redirect, cookie, netrc, automatic authentication,
retry, pooling, or PSR-18 control-plane behavior.

Phase 15F accepts ADR 0160 and implements the optional Experimental
`kongono/network-egress-application` exact-current single-profile workflow.
It derives canonical profile and generated-wiring evidence from one supplied
policy source, validates and publishes the wiring without overwrite, loads one
exact contained generated class, checks required transport capabilities before
resolution, and delegates one terminal synchronous operation to a fresh Phase
15D runtime. Authority currentness, resolver, transport, cancellation, and
clock collaborators remain explicit and caller-owned.

Phase 15G accepts ADR 0161 and implements the optional Experimental
`kongono/network-egress-inspection` offline upper evidence leaf. It
revalidates exact supplied profile/wiring evidence and can summarize one exactly
paired detached operation/result without generated-code loading, networking,
authority evaluation, recording, replay, repair, certification, or role
execution.

The current topology is 81 packages / 317 local edges / acyclic, with PHP
inventories 1,514 / 768 / 84. Phase 15 is closed. No first-party
resolver, credential provider, provider adapter, live inspection probe,
inspection command, public compatibility certification, Stable/1.0 claim,
production-readiness claim exists.

## Phase 16A-C testing boundary

Phase 16A accepts [ADR 0163](../adr/0163-explicit-external-service-binding-and-secret-lease-boundary.md),
the [external-service binding guide](../architecture/external-service-binding-and-secret-leases.md),
and the [Phase 16 roadmap](../architecture/phase-16-roadmap.md). Phase 16B
accepts [ADR 0164](../adr/0164-neutral-external-service-binding-and-credential-lease-contracts.md)
and adds 15 package-test PHP files for 27 production declarations.

The suite proves exact identities, endpoint/reference adjacent bounds, binding
shape, lease-port signatures, the sole `SensitiveParameter`, Phase 15 resolver
compatibility, lifecycle/result coherence, nondisclosure, statuses,
dependencies, inventories, and prohibited-surface absence. Fixtures use inert
placeholders and reserved `.test` names; no real credential, public endpoint,
production DNS, resolver/source implementation, provider claim, network work,
artifact, runtime, command, or later-phase behavior exists. Phase 16C accepts
[ADR 0165](../adr/0165-deterministic-external-service-binding-artifact-and-generated-php.md)
and adds 15 package-test PHP files for 27 build-package production declarations.
They prove exact source, canonical artifact and generated-PHP framing,
determinism, semantic sensitivity, structural/current/stale distinctions,
inert validation without execution, bounded nondisclosure, and independent
path-safe atomic no-clobber publication. Synthetic `.test` inputs contain no
secret material. Phase 16D accepts
[ADR 0166](../adr/0166-explicit-bounded-system-resolver-adapter.md) and adds 13
package-test PHP files for 17 system-resolver declarations. Hostname and
adversarial behavior uses a package-test-only fake lookup. Native tests use
only canonical numeric IPv4/IPv6 loopback literals with `AI_NUMERICHOST`, never
public DNS. Tests prove the exact three-key `SOL_TCP` hints with omitted
`ai_family`, one call, count-before-explain bounds, complete malformed-result
rejection, duplicate collapse, deterministic family/binary ordering, selected
membership, Phase 15 classifications, fresh sequential calls, four closed
failure reasons, unchanged `Error` propagation, nondisclosure, and the absence
of connection/cache/retry/fallback behavior. Phase 16E accepts [ADR
0167](../adr/0167-operation-scoped-credential-lease-runtime.md) and adds 16
package-test PHP files for 20 runtime declarations. Tests cover exact request
relations, inclusive and plus-one lifetime observations, initial/post-use/final
lease states, primary and retirement failure identity, reentrancy, sequential
recovery, separate-instance overlap, randomized nondisclosure sentinels, and
the absence of source, provider, network, refresh, retry, fallback, background,
or zeroization behavior. Phase 16F accepts [ADR
0168](../adr/0168-exact-current-external-service-binding-application.md) and
adds 18 package-test PHP files for 20 application declarations. Deterministic
fakes prove exact-current profile relations, borrowed-material call order,
request containment, one Phase 15 operation, exactly-once lease retirement,
all outcome/failure mappings, replay rejection, nested throwable identity, and
nondisclosure. No test uses public DNS, a socket, a real credential, an
environment source, a vault, cloud metadata, or a provider account. Phase 16G
accepts [ADR
0169](../adr/0169-visibility-safe-external-service-inspection-and-bounded-evidence.md)
and adds six package-test PHP files for 22 inspection declarations. The tests
cover exact profile reconstruction, independent binding/policy comparison,
optional result coherence across all six outcomes, bounded credential and
egress evidence, role/Graph/Observability/Security Audit projection,
nondisclosure, and the absence of body, credential, resolver, network, replay,
recording, repair, or certification behavior. Phase 16H adds the explicit
`composer acceptance:phase16` root-test-only external-process proof. It uses
only local IPv4 loopback, a numeric resolver proof, and one inert test lease;
it is not part of canonical quality and does not certify public DNS, TLS,
credentials, providers, remote effects, deployment, or production readiness.
Phase 16 is closed; Phase 17A is separately accepted below as documentation
only. Phase 17B-G add one optional mapping/adapter application leaf and one
inspection leaf. Current topology is 89 packages / 375 local production edges
/ acyclic; PHP inventories are 1,726 / 894 / 92. See [ADR
0170](../adr/0170-phase-16-external-service-acceptance-and-checkpoint.md).

## Phase 17 testing and local acceptance

Phase 17B accepts seven local synthetic response fixtures and nine package-test
PHP files under [ADR 0172](../adr/0172-deterministic-openai-responses-offline-protocol-mapping.md).
They prove exact mapping version and API status, profile restrictions,
deterministic compact request bytes, byte/count/depth bounds, duplicate-key and
unsupported-shape rejection, usage relations, nondisclosure, detached values,
and repeated mixed-operation isolation. Tests perform no network, DNS,
credential, endpoint, or provider operation. Phase 17C-D own adapter, SSE,
runtime cancellation, transport uncertainty, and reverse cleanup proofs.

Phase 17C tests prove the one-shot adapter through local external-service and
egress fakes. Phase 17D adds three synthetic SSE fixtures and six package-test
PHP files covering LF/CRLF framing, content-type and field rejection, bounded
reads and stalls, duplicate-safe JSON, exact event order and snapshots,
contiguous neutral emissions, terminal result equality, failure, nondisclosure,
and exactly-once cleanup. The focused suite also exercises the real Phase 14
runtime. It proves consumer-driven PHP body pulls, not immediate first-token
delivery from a socket because Phase 15 may already have buffered the body.
Phase 17E adds four synthetic JSON fixtures and
eight package-test PHP files covering strict schema canonicalization and bounds,
duplicate and unknown-member rejection, exact structured/action request hashes,
local no-coercion value validation, inert function-call normalization, Version
1 regressions, Version 2 pre-effect streaming rejection, one provider attempt,
zero tool/effect calls, and zero second operations. Fixtures remain local
evidence and do not establish current public-provider compatibility.

Phase 17F adds four package-test PHP files covering canonical profile identity,
all four supported mapping/mode combinations, exact lower-profile
reconstruction, deterministic build output, write-free check identity, one
single-use run, lazy cancellation, stale/mismatched pre-effect rejection,
reentry/reuse, lower cleanup and uncertainty preservation, nondisclosure, and
persistent-process isolation. These are local deterministic proofs, not a live
provider, credential, public network, certification, or production-readiness
claim. See [ADR
0176](../adr/0176-exact-current-openai-responses-application-workflow.md).

Phase 17G adds six package-test PHP files covering exact-current profile
reconstruction, mixed and stale binding rejection, absent and matching detached
runtime evidence, every terminal outcome, contiguous event identities, bounded
canonical evidence, Graph absence and role matching, inert Observability and
Security Audit projections, deterministic role-addressed derivation, independent
verification, nondisclosure, statelessness, and prohibited side effects. The
tests use only synthetic fakes and inspect no hidden request, response, text,
structured, action, credential, endpoint, transport, or event payload material.
See [ADR
0177](../adr/0177-visibility-safe-openai-responses-inspection-and-bounded-evidence.md).

Phase 17H accepts [ADR
0178](../adr/0178-phase-17-openai-responses-local-acceptance-and-checkpoint.md)
and adds four root-test PHP files beneath `tests/External/OpenAiResponses/`.
`composer acceptance:phase17` runs fresh sequential and concurrent children
against an owned IPv4 loopback provider-protocol emulator and proves all four
supported profiles through the real Phase 14-17 composition. Streams,
requests, responses, timeouts, repository fingerprints, and temporary cleanup
are bounded; public output is canonical and nondisclosing. No real credential,
account, public OpenAI endpoint, provider certification, live compatibility,
production-readiness, or Phase 18 claim follows from the local evidence. Phase
17A-H are Accepted and Phase 17 is closed; Phase 18A-H are Accepted and Phase
18 is closed.
