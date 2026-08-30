# Kongono Engineering Foundation

> **Phase 11 complete boundary:** Phase 11H adds root-owned external proof and
> the exact local checkpoint without changing production Graph behavior. The
> topology remains 57 packages and 201 acyclic local production edges; Phase 12
> and production-readiness remain outside the accepted boundary.


- [Read-only Hantei](../architecture/read-only-hantei.md) documents the fixed
  Phase 4E verifier, subject identity, evidence, process, and mutation boundary.
## Purpose and current scope

Kongono's engineering foundation makes the repository's PHP quality expectations executable across the root and all 52 production packages. It gives contributors one reproducible development environment and one non-mutating local gate without implying that Experimental behavior is Stable or that planned capabilities exist.

Phase 0X expands the automatically discovered inventory with eight Diagnostic
Presentation declarations and six focused package tests. Existing quality rules
and policy remain unchanged.

The root command is:

```bash
composer quality
```

It validates the root and all 52 package manifests in lexicographic order, audits the locked dependency graph, checks the actual runtime platform, checks formatting, performs maximum-level static analysis with strict rules, and runs tests. A passing gate means those configured checks passed for the current checkout and environment; it is not a claim of framework completeness, production readiness, or security certification.

## Guides

- [Toolchain and contributor commands](toolchain.md)
- [PHP style](php-style.md)
- [Static analysis](static-analysis.md)
- [Testing](testing.md)
- [Dependency security](dependency-security.md)
- [Package layout](package-layout.md)
- [API status](api-status.md)
- [Application Graph structure](application-graph-structure.md)
- [Explicit typed dependency resolution](dependency-injection.md)
- [Error handling and actionable diagnostics](error-handling.md)
- [PHP support policy](php-support-policy.md)
- [Generated wiring](generated-wiring.md)
- [Developer-intelligence architecture](../architecture/developer-intelligence.md)
- [Phase 4 roadmap](../architecture/phase-4-roadmap.md)
- [Phase 4 external acceptance](../architecture/phase-4-external-acceptance.md)
- [Application composition and build](../architecture/application-composition-and-build.md)
- [Phase 5 roadmap](../architecture/phase-5-roadmap.md)
- [Phase 7 external acceptance](../architecture/phase-7-external-acceptance.md)
- [Persistence performance baseline](persistence-performance-baseline.md)
- [Structured CLI architecture](../architecture/structured-cli.md)
- [Phase 8 roadmap](../architecture/phase-8-roadmap.md)
- [Identity and Access Security](../architecture/identity-and-access-security.md)
- [Phase 9 roadmap](../architecture/phase-9-roadmap.md)
- [Observability and Operational Evidence](../architecture/observability-and-operational-evidence.md)
- [Phase 10 roadmap](../architecture/phase-10-roadmap.md)
- [Authoritative Application Graph](../architecture/authoritative-application-graph.md)
- [Phase 11 roadmap](../architecture/phase-11-roadmap.md)

## Authority and exceptions

These controls are repository architecture, not optional developer preferences. Do not bypass a failure, weaken policy, authorize a Composer plugin, add a dependency, create a PHPStan baseline, or add a suppression without architect approval. When an approved tool appears to conflict with an approved requirement, stop and report the conflict rather than making the gate artificially green.

**Stability:** Internal engineering policy for the pre-alpha repository. It does not create a public Kongono framework API.

Phase 4F adds the read-only Oshie package to canonical package, style, static-analysis, and PHPUnit gates. Its curriculum resource and real CLI proof are deterministic evidence, not an alternate source of engineering policy.
## Phase 4G proposal engineering

The [reviewable Chie proposal guide](../architecture/reviewable-chie-patch-proposals.md)
defines the only implemented proposal rule: one exact complete-line replacement
in current explanatory documentation, with canonical identities, reversible
diffs, provenance, and a pending human/fresh-Hantei handoff. It grants no apply,
write, Git, verification, model, network, or deployment authority.

## Phase 4 external acceptance engineering

The root-owned `composer acceptance:phase4` harness proves 28 exact scenarios
through real local process boundaries, bounded streams and timeouts, temporary-
only writes, cleanup, shared current identities, and explicit real versus
synthetic Hantei evidence. It adds no package API, dependency, production
authority, network integration, or executable Oshie example. Phase 4 is closed;
Kongono remains pre-1.0.

## Phase 5A composition engineering

Phase 5A adds documentation architecture only. The [generated wiring guide](generated-wiring.md)
records future contributor rules for explicit canonical constructor
dependencies, deterministic readable PHP output, source/output identity,
lifetime and cleanup ownership, no runtime discovery or property-hook
resolution, reproducible-build proof, and benchmark-before-performance claims.

The [composition guide](../architecture/application-composition-and-build.md)
and [Phase 5 roadmap](../architecture/phase-5-roadmap.md) distinguish current
caller-owned composition from Accepted and planned behavior. No package,
source, test, command, dependency, Composer constraint, generated artifact, or
runtime behavior changes in Phase 5A. Phase 5B now applies
`>=8.5.0 <8.6.0` to the root and all eighteen packages. PHP 8.5 is the one
active compatibility-certified minor, with PHP 8.5.9 on Windows proved by this
execution; stable eligible 8.5 patches remain in range. PHP 8.6, PHP 9,
prerelease, nightly, and automatic future-minor support are not claimed. The
migration changes installability metadata only and adds no runtime semantics,
package, dependency, public API, polyfill, shim, release/LTS promise, or
composition/build capability. Phase 5B is accepted and the bounded PHP
constraint is current.

## Phase 5C declaration engineering

Phase 5C accepts the contract for the singular inert
`<application-root>/kongono.composition.json` source only. The
[composition declaration guide](composition-declarations.md) records exact v1
grammar, canonical symbols, three argument forms, three lifetimes, two root
kinds, reachability, cleanup, bounds, safe reading, deterministic failures,
non-disclosure, provenance, tests, and future-version migration review.

Phase 5D adds the Experimental `kongono/composition` parser, validator,
canonical artifact builder, current-state validator, package tests, and
content-addressed publisher. Phase 5E adds canonical typed wiring and isolated
lifecycle support in the same package, using application-owned typed aggregates
instead of collection injection. Phase 5F adds one exact-current loader, a
one-use startup boundary, and the exact single-root web integration without a
schema file, command, container, service locator, or new transport. Composition
remains caller-owned; DI and Graph remain unintegrated, while existing Web
Application, Web Runtime, and PHP-CGI ownership is preserved.
See [ADR 0070](../adr/0070-versioned-composition-source-and-provenance.md),
[ADR 0071](../adr/0071-roots-bindings-lifetimes-and-cleanup.md),
[ADR 0072](../adr/0072-deterministic-composition-artifact-and-publication.md),
[ADR 0073](../adr/0073-generated-typed-wiring-and-lifecycle-boundary.md),
[ADR 0074](../adr/0074-generated-assembly-web-runtime-integration.md),
the [composition source guide](../architecture/composition-source.md), and the
[composition build guide](composition-build.md).
[generated runtime integration guide](../architecture/generated-runtime-integration.md).
[application workflow guide](application-workflow.md).

## Phase 5H external acceptance

The [Phase 5 external acceptance guide](../architecture/phase-5-external-acceptance.md)
documents the root-owned `composer acceptance:phase5` harness. It proves the
fixed 28-scenario external application matrix with mirrored ordinary package
copies, plugins and network disabled, exact raw-stream assertions, unchanged
repository state, and owned temporary cleanup. It is test evidence only, not a
runtime package, framework CLI, installer, deployment mechanism, Stable API,
production-readiness claim, or Phase 6 capability.

## Phase 6B database contract engineering

Phase 5 is complete at checkpoint
`c431482bea664ce3c3201794332c62cab1ed034e`. Phase 6A adds only the
[Data Platform architecture guide](../architecture/data-platform.md),
[Phase 6 roadmap](../architecture/phase-6-roadmap.md), [ADR 0077](../adr/0077-layered-data-platform-and-explicit-sql-boundary.md),
and [ADR 0078](../adr/0078-deterministic-data-plans-and-operation-scoped-persistence.md),
plus current-document reconciliation.

The accepted direction keeps connectivity/execution, SQL plans and dialects,
schema/migrations, optional generated Data Mapper support, and lifecycle
integration conceptually separate. It requires explicit parameter binding,
provider capability truthfulness, operation-scoped resources and transactions,
deterministic derived evidence, and existing diagnostic non-disclosure.

Phase 6B adds the [database contract architecture](../architecture/database-contract.md),
the implementer-facing [database contract tables](database-contracts.md),
[ADR 0079](../adr/0079-neutral-database-session-and-provider-capability-contract.md),
and [ADR 0080](../adr/0080-explicit-database-result-transaction-and-failure-lifecycle.md).
Phase 6C implements the `kongono/database` neutral boundary and separate
`kongono/database-pdo-pgsql` first adapter, exact version-1 statement/value/
result/capability/transaction/lifecycle vocabulary, PostgreSQL PDO proof profile,
no-retry policy, and nondisclosing error categories.

Phase 6D adds Experimental immutable plans in `kongono/database-sql` and the
stateless PostgreSQL 17 compiler in `kongono/database-sql-pgsql`. Explicit
capabilities, mutation scope, identifier quoting, occurrence-ordered parameters,
and non-disclosing failures are covered by focused and real-provider proof. Raw
parameter-bound statements remain supported.

Phase 6E adds the neutral typed migration catalog/planner and explicit
PostgreSQL 17 migration compiler/executor. Phase 6F adds optional Experimental
compile-time mapping, provenance artifacts, and generated typed
mappers/repositories. Phase 6G adds the outer database-application workflow,
exact-current loading, operation-owned sessions, and optional data skeleton.
Phase 6G brought the package graph to twenty-seven packages and the current PHP range
remains `>=8.5.0 <8.6.0`. Generated output is derived and not checked in; raw
SQL and typed plans remain first-class. Phase 6H adds the root-owned whole-
platform PostgreSQL 17 acceptance proof and a versioned local observational
measurement baseline. The [external acceptance guide](../architecture/phase-6-external-acceptance.md)
and [performance baseline guide](database-performance-baseline.md) document the
command, result, limits, and re-baselining rules. No ORM, provider service,
deployment, runtime telemetry, pooling, retry, or further provider exists. See [ADR 0081](../adr/0081-postgresql-pdo-adapter-and-real-provider-proof.md),
[ADR 0082](../adr/0082-immutable-database-sql-plans-and-postgresql-dialect-compilation.md),
[ADR 0083](../adr/0083-deterministic-schema-migrations-and-postgresql-execution.md),
[ADR 0084](../adr/0084-compile-time-data-mapping-and-generated-repositories.md),
[ADR 0085](../adr/0085-generated-data-application-and-operation-owned-sessions.md),
[ADR 0086](../adr/0086-phase-6-external-data-platform-acceptance-and-checkpoint.md),
the [SQL plan engineering guide](database-sql-plans.md), the
[migration engineering guide](database-migrations.md), and the
[mapping engineering guide](database-mapping.md).

## Phase 7 persistence engineering boundary

Phase 6 is complete at checkpoint
`f5903134e77bb2ecfaece6ee6b435a8ab2ecbd05` with twenty-seven production
packages. Phase 7A accepts documentation architecture. Phase 7B adds the
Experimental neutral `kongono/persistence` package. Phase 7C adds Experimental
`kongono/persistence-build` as package twenty-nine and registers its source/tests
in package validation, style, maximum static analysis, PHPUnit, root autoload,
Hantei, and API-status inventory.

The accepted direction keeps application domain classes plain, compiles bounded
versioned metadata before runtime, generates ordinary reviewable PHP evidence,
and scopes any later identity map, snapshot, change set, or persistence context
to one operation. Flush and transaction authority remain explicit; uncertain
failure retires the context. Relationships, load plans, aggregates, cascades,
concurrency, diagnostics, cleanup, and performance claims require visible
bounded contracts and separate proof.

The neutral package supplies identity, opaque concurrency-token, state, immutable
value-free change/flush, context-interface, diagnostic, and exception contracts.
The build package supplies one strict source, mapping-builder consumption,
build-time domain validation, detached model, provenance artifact, separate
structural/current proof, and safe publication. Phase 7D adds deterministic
ordinary PHP state adapters from one exact-current artifact, a closed generated
API, non-executing fail-closed validation, and content-addressed atomic
publication. Phase 7E adds one concrete single-entity operation context and
explicit unit-of-work flush over a caller-supplied database executor. It owns no
session, transaction, retry, SQL, provider, or generated-state loader. Phase 7F
adds separate-source generated aggregate repositories with explicit bounded
relationship loads and one-level cascades. Direct Phase 6 SQL and generated Data
Mapper workflows remain supported. Phase 7G adds the optional exact-current
Composition workflow documented in the
[persistence application workflow](persistence-application-workflow.md). See
also the [persistence context guide](persistence-context.md) and [aggregate
repository guide](aggregate-repositories.md).
Phase 7H closes the thirty-package milestone with a root-owned PostgreSQL 18.6
acceptance harness and local observational timing evidence. PostgreSQL 18 is
the current primary major; the exact accepted minor is 18.6, while PostgreSQL
17.2 remains historical Phase 6 regression evidence. Exact-current evidence is
narrower than universal compatibility, and another provider version requires
fresh acceptance. The external proof adds no runtime telemetry, hidden
transaction, production-readiness, or Stable-API claim.
See [ADR 0087](../adr/0087-explicit-persistence-model-and-compile-time-orm-boundary.md),
[ADR 0088](../adr/0088-operation-scoped-identity-change-tracking-and-transaction-authority.md),
[ADR 0089](../adr/0089-neutral-persistence-contracts-and-package-boundary.md),
[ADR 0090](../adr/0090-deterministic-persistence-model-and-artifact-compilation.md),
[ADR 0091](../adr/0091-deterministic-generated-persistence-state-evidence.md),
[persistence build engineering](persistence-build.md),
[persistence generation engineering](persistence-generation.md),
the [Deterministic Persistence guide](../architecture/deterministic-persistence.md),
and the [Phase 7 roadmap](../architecture/phase-7-roadmap.md).

## Phase 8A through Phase 8H Structured CLI engineering boundary

Phase 8C extends the Experimental `kongono/console` package with six production
declarations and four focused tests, and adds `kongono/console-build` with 16
production declarations and ten focused tests. Exact root and Hantei quality
integration now covers the root and all thirty-six production packages on the
accepted PHP 8.5 current-minor line.

The accepted direction separates command meaning, one explicit invocation and
neutral result, and presentation/process adaptation. Later definition evidence
must be deterministic, provenance-bearing, exact-current, and validated without
execution. Runtime scanning, Reflection discovery, mutable registries, service
location, hidden autowiring, direct handler output, ambient host reads, hidden
interaction, and handler process exit are prohibited.

Human and versioned machine output must derive from the same neutral result.
Mutation requires declared interaction, explicit authority, and honest dry-run
behavior. Each invocation owns bounded reverse cleanup, combined failure, and
sequential same-process isolation under existing nondisclosure rules. See
[ADR 0096](../adr/0096-explicit-structured-cli-and-automation-boundary.md),
[ADR 0097](../adr/0097-deterministic-command-lifecycle-and-presentation-separation.md),
[ADR 0098](../adr/0098-neutral-structured-cli-contracts-and-package-boundary.md),
[ADR 0100](../adr/0100-operation-scoped-command-execution-and-cleanup.md),
[ADR 0101](../adr/0101-deterministic-command-presentation-and-process-adaptation.md),
[ADR 0102](../adr/0102-first-read-only-framework-capability-commands.md),
[ADR 0103](../adr/0103-supported-kongo-edge-and-honest-application-build.md),
the [Structured CLI guide](../architecture/structured-cli.md), and the
[Phase 8 roadmap](../architecture/phase-8-roadmap.md). Phase 8D adds nine
Console Runtime test files proving explicit binding, fresh operations,
cancellation precedence, reverse every-attempt cleanup, primary/cleanup failure
identity, nondisclosure, reentrancy rejection, and sequential recovery. Phase
8E adds three Console result-model tests and ten Console Presentation tests for
canonical human/machine parity, Protected omission, help projection, exact
media/exit/stream behavior, output failure, and sequential isolation. Phase 8F
adds eight Console Commands tests and one root integration test proving exact
definitions, typed handlers, read-only evidence, presentation parity,
nondisclosure, generated Composition wiring, lease cleanup, and external
generated lint/PHPStan/style. Phase 8G adds the upper
`kongono/console-application` package and nine focused package-test paths plus
one root integration test. They prove the exact one-command definition/factory,
closed process grammar and help, non-mutating plan, explicit atomic apply,
human/machine and exit parity, one-write failure behavior, nondisclosure,
same-process isolation, direct-source and Composer-proxy execution, and zero
repository mutation from external temporary-root builds. Phase 8H adds the
root-owned `composer acceptance:phase8` real-process profile. It proves both
executable edges, deterministic help/results, corrected exit-3 conflict
no-clobber, plan/apply truth, recovery, nondisclosure, repository preservation,
and complete owned-temp cleanup. See the [external acceptance guide](../architecture/phase-8-external-acceptance.md).

## Phase 9A Identity and Access Security engineering boundary

Phase 9A adds documentation architecture only. It defines explicit separation,
deny-by-default evaluation, protected-capability enforcement, immutable
operation-scoped security state, deterministic policy provenance, credential
opacity, and nondisclosure. It does not add a package, contract, PHP source,
dependency, schema, verifier, provider, policy engine, session, HTTP adapter,
test, tool command, generated output, or runtime integration.

Later engineering must keep neutral contracts below transport and application
adapters, pass security inputs through explicit typed Composition, validate
policy evidence before runtime, and prove cleanup and sequential/reentrant
isolation without networks, globals, ambient clocks, or real secrets. Hantei
may inspect deterministic evidence but grants no authority. See the
[Identity and Access Security guide](../architecture/identity-and-access-security.md)
and fixed [Phase 9 roadmap](../architecture/phase-9-roadmap.md).

## Phase 9H external security engineering boundary

Phase 9H adds four root test PHP files and two documentation authorities. The
`composer acceptance:phase9` profile proves the selected security workflow
through explicit child-process argument arrays and one cleanup-owned temporary
application. It adds no production PHP, package, dependency, skeleton source,
provider, policy semantics, or runtime authority. See
[ADR 0113](../adr/0113-phase-9-external-security-acceptance-and-checkpoint.md)
and the [external acceptance guide](../architecture/phase-9-external-acceptance.md).

## Phase 10B Observability engineering boundary

Phase 10B adds the Experimental `kongono/observability` neutral contract
package over only API Metadata, Diagnostics, and PHP. No existing production
package depends on it at that boundary. Phase 10B's topology was exactly
forty-five acyclic production packages, 749 production PHP files, and 388
package-test PHP files. Engineering
must keep diagnostics, traces, metrics, operational
events, security audit evidence, authorization receipts, and product analytics
separate and non-authoritative.

Later implementation must use typed structured versioned evidence, immutable
operation-scoped context, explicit clocks/identifiers/randomness/sampling,
schema-allowlisted nondisclosing attributes, cardinality budgets, deterministic
build evidence, cleanup isolation, and bounded concurrency/backpressure/failure
behavior. No ambient/global provider, current span, service lookup, runtime
scanning, hidden environment configuration, or lower-package exporter coupling
is accepted. OpenTelemetry is only the selected future outer interoperability
direction; no SDK, dependency, protocol, collector, or backend is selected.

The package supplies bounded scalar attributes, explicit optional correlation,
versioned signal identity, closed kind/status, an immutable envelope, and a
one-method recorder interface with no implementation. See [ADR 0114](../adr/0114-explicit-observability-and-operational-evidence-boundary.md),
[ADR 0115](../adr/0115-structured-signals-context-and-exporter-neutrality.md),
[ADR 0116](../adr/0116-neutral-observability-contracts-and-package-boundary.md),
the [Observability and Operational Evidence guide](../architecture/observability-and-operational-evidence.md),
and fixed [Phase 10 roadmap](../architecture/phase-10-roadmap.md).

## Phase 10C tracing engineering boundary

Phase 10C adds the Experimental `kongono/tracing` package over only
Observability, API Metadata, Diagnostics, and PHP. No other production package
depends on it. The topology is exactly forty-six acyclic production packages,
772 production PHP files, and 401 package-test PHP files.

Engineering uses exact typed nonzero hexadecimal IDs and trusted typed
propagation without carrier parsing; caller-owned monotonic clock, identifier
source, sampler, and recorder; one root plus at most 64 active children; at most
25 caller attributes; fixed reserved trace mapping; Record/Drop behavior;
idempotent cleanup; exact parent ownership; overlap/reentrancy isolation; and
visible nondisclosing collaborator/recording failures. No default collaborator,
ambient current span, OpenTelemetry dependency, exporter, backend,
metric/event/audit runtime within Tracing, or Composition/application
integration exists. See [ADR 0117](../adr/0117-operation-scoped-tracing-lifecycle-and-typed-propagation.md).

## Phase 10D metrics engineering boundary

Phase 10D adds the Experimental `kongono/metrics` package over only
Observability, API Metadata, Diagnostics, and PHP, independently of Tracing.
No production package depends on Metrics. The topology is exactly forty-seven
acyclic production packages and 160 local edges, with 802 production PHP files
and 416 package-test PHP files.

Engineering permits only immutable finite numbers and units, schema-owned
canonical dimensions, 64-series instrument and 256-series plan bounds, four
exact instrument kinds, immediate aggregate-only state, canonical observations,
and one explicit Active/Collected/Retired lifecycle. Collection must release
all retained runtime state before external recording; failure remains final,
visible, nondisclosing, retains exact previous identity, and is never retried.
Exact ownership, overflow-before-mutation, idempotent retirement, and fresh
sequential/overlapping/reentrant isolation are mandatory.

No dynamic labels, raw sample logs, eviction, sampling, clocks, IDs, ambient
meter state, registry/provider, exporter/backend, OpenTelemetry or PSR logging,
event/audit pipeline inside Metrics, or application integration is included.
See [ADR 0118](../adr/0118-bounded-cardinality-metric-aggregation-and-explicit-collection-lifecycle.md).

## Phase 10E structured evidence engineering boundary

Phase 10E adds separate sibling Experimental `kongono/operational-events` and
`kongono/security-audit` packages. Each depends only on Observability, API
Metadata, Diagnostics, and PHP; neither depends on or converts to the other.
The current topology is exactly forty-nine acyclic packages and 166 local
edges, with 831 production PHP files and 432 package-test PHP files.

Engineering admits only finite schema-declared scalar domains, canonical
attributes, disclosure derived from fields actually supplied, explicit event
severity/status or audit outcome/retention intent, and one bounded observation
per valid recorder call. Audit durability is always the exact
`not_guaranteed` value. Validation records nothing. Recorder failure is visible,
nondisclosing, final, preserves the exact previous throwable, and is never
retried; sequential, overlapping, and recorder-reentrant calls remain isolated.

No logger facade, free-form message pipeline, arbitrary context, conversion
API, hidden buffer, retry, exporter, store, retention enforcement, tamper
evidence, compliance claim, product analytics, authorization behavior,
application integration, or Stable API is included. See
[ADR 0119](../adr/0119-structured-operational-events-and-bounded-security-audit-evidence.md).
Phase 10F is the sole possible next handoff.

## Phase 10F OpenTelemetry bridge engineering boundary

Phase 10F adds exactly `kongono/opentelemetry` over PHP, API Metadata,
Diagnostics, Observability, generated OTLP protobuf 1.10.0, and stable semantic
conventions 1.38.0. The topology is exactly fifty acyclic packages and 169
local edges, with 855 production PHP files and 445 package-test PHP files.

Engineering requires one direct generated-protobuf profile, explicit resource,
batch, time-anchor, clock, and transport capabilities, bounded FIFO storage,
no-drop backpressure, validation-before-retention, no record-time I/O,
synchronous contiguous batching, partial-progress retention, explicit flush,
idempotent close, continued cleanup, nondisclosure, reentry rejection, and
instance isolation. Histogram aggregate evidence uses the documented lossless
gauge family and never fabricates OTLP histogram boundaries.

No SDK, API/context/provider package, global registry, auto-configuration,
environment discovery, HTTP client, endpoint/header/TLS/credential policy,
retry, collector/backend, application workflow, durable audit claim, or Stable
API is included. See
[ADR 0120](../adr/0120-explicit-opentelemetry-otlp-http-protobuf-bridge.md).
Phase 10G is the sole possible next handoff.

## Phase 10H external closure boundary

Phase 10H adds only the root-owned `composer acceptance:phase10` harness,
supporting test classes, ADR 0122, external acceptance documentation, fixed
Hantei inventory, and checkpoint documentation. The harness copies the
supported skeleton into a unique temporary root, installs from exact local
packages, exercises public build/application/Console boundaries in fresh
processes, parses emitted payloads with pinned protobuf classes, and requires
complete cleanup plus byte-identical repository status.

This is external evidence, not production behavior. It adds no package,
dependency, runtime API, exporter, provider, backend, network transport,
collector, deployment, durable audit mechanism, performance promise, or
Phase 11 work. Phase 10A-H is complete only at the exact local checkpoint;
Kongono remains pre-1.0.

## Phase 11A and 11B Graph contract engineering boundary

Phase 11A added exactly ADR 0123, the authoritative Graph architecture guide,
the fixed Phase 11 roadmap, and reconciled documentation. Phase 11B adds ADR
0124 and the separate Experimental `kongono/application-graph-contract`
package. Its 23 production declarations and six package tests cover exact
identities, vocabulary, schemas, provenance, visibility, disclosure,
diagnostics, immutability, ordering, and non-disclosure.

Engineering must preserve the current Experimental Phase 0J structural
snapshot and distinguish its opaque graph-local keys and open kinds from future
stable identity, vocabulary, schema, provenance, and visibility contracts.
Future producer records remain non-authoritative until deterministic admission;
future consumers receive only bounded visibility-filtered views and cannot
treat omission as false or convert Graph facts into operational permission.

The fixed order places identity/schema foundations before IR construction,
Composition projection, artifact publication, inspection, developer-
intelligence derivation, and external acceptance. Each row requires separate
authenticated authority. Phase 11C provides explicit IR records and complete
deterministic Graph construction; Phase 11D provides Composition projection;
Phase 11E provides canonical artifacts, caller-relative currentness, explicit
loading, and no-clobber publication. Inspection, commands, consumers,
extensions, and runtime integration remain absent; Phase 11F is unstarted.
