# Contributing to Kongono

## Phase 21 storage changes

Phases 21A-G are Current and Accepted. Phase 21B adds only the optional
Experimental `kongono/storage-contract` leaf: 40 inert declarations for exact
bounded identities, support capabilities, content evidence, conditional
requests, typed results, and explicit source/sink lifecycle. Phase 21C adds
only the optional Experimental `kongono/storage-build` leaf for explicit
bounded profiles, canonical inert artifacts, strict structural and
exact-current validation, and contained content-addressed publication. Phase
21D adds the separately optional Experimental neutral adapter protocol and
contained local-filesystem implementation. Do not add another adapter,
provider, command, configuration key, credential source, or authorization
mechanism beyond the accepted Phase 21F boundary without separate authority.

Phase 21E adds only the separately optional Experimental
`kongono/storage-runtime` exact-current operation leaf. It consumes one exact
current profile and one directly supplied compatible neutral adapter, retains
operation-local state only, and does not require `kongono/storage-adapter-local`.
Phase 21F adds the separately optional Experimental
`kongono/storage-application` profile workflow, three profile commands, and
the copy-once `storage-minimal` example. Business authorization stays with
the application-supplied collaborator.

Future contributions must preserve install-none optionality and keep logical
object identity, namespace, provider locator, content digest, metadata,
version/concurrency evidence, operation lifecycle, authority, and authorization
distinct. Storage is not cache, database persistence, asset build, HTTP
delivery, upload handling, media processing, public-file serving, or backup.

Do not introduce raw path/string authority, ambient roots, environment or
current-working-directory discovery, a global facade, service location,
runtime scanning, provider auto-selection, silent overwrite, hidden retries,
universal whole-object buffering, directory discovery, automatic public URLs,
or inflated provider guarantees. Future streaming, bounds, backpressure,
cancellation, close, abort, uncertain mutation, and cleanup must be explicit
and persistent-runtime safe.

Review the [Storage Platform guide](docs/architecture/storage-platform.md),
[storage-authoring guide](docs/engineering/storage-authoring.md),
[ADR 0206](docs/adr/0206-optional-object-storage-platform-and-explicit-authority-boundary.md),
[ADR 0207](docs/adr/0207-deterministic-storage-integrity-lifecycle-and-provider-separation.md),
[ADR 0208](docs/adr/0208-neutral-storage-contract-and-bounded-evidence-vocabulary.md),
[ADR 0209](docs/adr/0209-deterministic-storage-profile-artifact-and-contained-publication.md),
[ADR 0210](docs/adr/0210-neutral-storage-adapter-and-contained-local-filesystem-implementation.md),
[ADR 0211](docs/adr/0211-exact-current-storage-runtime-and-operation-scoped-lifecycle.md),
[ADR 0212](docs/adr/0212-supported-storage-application-workflow-and-explicit-authorization.md),
the [deterministic storage-profile build guide](docs/architecture/deterministic-storage-profile-build.md),
[contained local adapter guide](docs/architecture/contained-local-storage-adapter.md),
the [exact-current storage runtime guide](docs/architecture/exact-current-storage-runtime.md),
the [storage application workflow guide](docs/architecture/storage-application-workflow.md),
and [Phase 21 roadmap](docs/architecture/phase-21-roadmap.md) before proposing
storage work. Phase 21G adds only optional offline
[`kongono/storage-inspection`](packages/storage-inspection/README.md) evidence;
see [ADR 0213](docs/adr/0213-visibility-safe-storage-inspection-and-developer-intelligence.md)
and the [storage inspection guide](docs/architecture/storage-inspection-and-intelligence.md).
Phase 21H adds the [root-owned local acceptance harness](docs/architecture/phase-21-external-storage-acceptance.md).
The separate time-bound audit, architect acceptance, and exact checkpoint
gates completed for Phase 21; the local proof retains its recorded limits.

## Phase 20 cache changes

Phases 20A-H are Accepted and Phase 20 is closed. Phase 20B adds only
the optional Experimental `kongono/cache-contract` leaf of bounded immutable
values, closed vocabulary, and nondisclosing diagnostics. Phase 20C adds only
the optional Experimental `kongono/cache-build` explicit profile, canonical
artifact, exact-current validation, and contained publication boundary. Phase
20D adds the separately optional Experimental neutral adapter contract and
bounded memory implementation. Do not add another adapter, backend,
exact-current runtime, serialization behavior,
command, application workflow, provider integration, or later-phase dependency
without separately authenticated authority. Redis, Memcached, cloud,
filesystem, database, PSR-6, and PSR-16 adapters remain future optional work.

Cache contributions must keep application values, application-owned
namespace, logical key, key identity, explicit codec/schema, bounded opaque
bytes, content identity, entry version, freshness policy, supplied time,
provider identity, operation, and outcome separate. Applications must remain
correct after miss, expiry, conflict, rejection, eviction, backend loss, and
flush. Fresh hit, miss, expired, conditional conflict, rejected, and
unavailable must remain typed and visible.

Do not introduce a facade, singleton, global registry, service locator, magic
serialization, native `serialize()`/`unserialize()`, hidden clock, default
forever entry, implicit TTL, stale fallback, read-through callback, implicit
retry, or provider discovery. Entry visibility must be atomic, conditional
mutation must bind explicit version evidence, results must be detached, and
mutable state and cleanup must remain operation-local and deterministic.

Never use cache evidence as persistence, authentication, authorization,
credential, session, grant, policy, audit, lock, lease, queue, rate limiter,
counter, durable record, or proof of an effect. Bounds and nondisclosure apply
before any future adapter effect. See [Cache authoring](docs/engineering/cache-authoring.md)
the [Phase 20 roadmap](docs/architecture/phase-20-roadmap.md), and the
[Phase 20 external acceptance guide](docs/architecture/phase-20-external-cache-acceptance.md).

## Phase 19 asset changes

Phase 19A-H are Accepted and Phase 19 is closed. Phase 19B supplies the neutral contract, Phase 19C
supplies the optional deterministic native `kongono/asset-build` leaf, and
Phase 19D supplies the optional neutral adapter contract plus separately
optional bounded Vite adapter. Phases 19E-G supply the exact-current resolver,
explicit application workflow, and offline inspection leaf. Phase 19H adds
only root-owned external acceptance and the exact checkpoint. Do not add wider
build, delivery, frontend, release, or Phase 20 behavior without separately
authenticated authority.

Later authorized asset changes must use one explicit bounded application-owned
source declaration and deterministic fixtures. Keep logical identity, source
path, output path, URL, content identity, build identity, and integrity metadata
separate. Validate complete adapter output against the neutral contract, treat
build tools as untrusted producers, and preserve a first-class native flow that
does not require Node.

Keep tool-specific behavior in replaceable adapter packages. No contribution
may add hidden directory scans, request-time builds, implicit or mutable
manifests, newest-file selection, stale development fallback, ambient service
location, undeclared subprocesses, package installation, or network access.
Fixtures and human or machine workflows must prove canonical order,
currentness, containment, no-clobber publication, finite bounds, disclosure
limits, operation-scoped cleanup, and persistent-runtime isolation.

Phase 19C contributions must preserve exact source bytes, validate every
dependency kind for complete reachability and cycles, retain distinct
source-set/graph/content/build/path identities, and keep canonical artifacts
free of raw bytes, private source paths, URLs, ambient policy, and time or
environment input. See [ADR 0191](docs/adr/0191-deterministic-native-asset-graph-and-build-artifact.md).

Application and deployment layers retain origin, CDN, CSP, headers, cache,
credentials, and external-effect policy. Browser-executable output remains
trusted application code, not sandboxed by Kongono. See [asset authoring](docs/engineering/asset-authoring.md)
and the [fixed roadmap](docs/architecture/phase-19-roadmap.md).

## Phase 18A-G compiled presentation changes

Phase 18A accepts only the documentation architecture in
[ADR 0179](docs/adr/0179-server-rendered-presentation-and-compiled-view-boundary.md),
[ADR 0180](docs/adr/0180-context-aware-output-safety-and-presentation-lifecycle.md),
the [presentation guide](docs/architecture/server-rendered-presentation.md),
the [view-authoring guide](docs/engineering/view-authoring.md), and the
[fixed roadmap](docs/architecture/phase-18-roadmap.md). Phase 18B-C accept the
neutral contract and deterministic build packages. Phase 18D accepts
[ADR 0183](docs/adr/0183-exact-current-compiled-view-runtime-and-bounded-rendering.md)
and the [compiled-view runtime](docs/architecture/compiled-view-runtime.md).
Phase 18E accepts [ADR 0184](docs/adr/0184-explicit-completed-view-to-psr-7-response-adaptation.md)
and the [compiled-view HTTP adapter](docs/architecture/compiled-view-http.md).
Phase 18F accepts [ADR 0185](docs/adr/0185-supported-compiled-view-application-workflow.md),
the exact-current application workflow, three view commands, and copy-once
example. Phase 18G accepts [ADR 0186](docs/adr/0186-visibility-safe-compiled-view-inspection-and-developer-intelligence.md)
and the offline, read-only, stateless
[compiled-view inspection boundary](docs/architecture/compiled-view-inspection-and-intelligence.md).
Do not implement or prototype Phase 18H without a separately authenticated
directive.

Review presentation proposals for optionality, application-owned view
selection, routing/HTTP separation, closed bounded inputs, compile-time known
output contexts, deterministic content-addressed build evidence, exact-current
contained loading, operation isolation, partial-output rejection, cleanup
truth, and nondisclosing diagnostics. Reject runtime parsing or compilation,
scanning, Reflection discovery, `eval`, service location, ambient state,
untyped raw markup, generic escaping promises, hidden build behavior, or a
mandatory view dependency.

Phase 18D runtime examples use the accepted exact-current loader and stateless
bounded renderer; Phase 18E uses only the accepted completed-result PSR-7
adapter; and Phase 18G inspection must keep supplied evidence distinct from live
filesystem truth, execute no generated code, and disclose only the closed
Safe/Protected schema. Phase 18H examples remain planned pseudocode. No source is
parsed or compiled during rendering. Generated code and the deployment
filesystem are trusted inputs. The accepted application workflow and copy-once
skeleton add no cache, watcher, daemon, installer, deployment manager,
automatic migration, or asset system.

Kongono is pre-alpha. Contributions must preserve the authorized architecture and must not turn planned or experimental concepts into accidental public APIs.

## Before changing the repository

Read, in order:

1. [`AGENTS.md`](AGENTS.md), which is binding for AI coding agents and a useful scope discipline for all contributors.
2. [`ARCHITECTURE.md`](ARCHITECTURE.md) and [`docs/doctrine.md`](docs/doctrine.md).
3. The relevant architecture documentation and accepted ADRs.
4. [`docs/documentation-standard.md`](docs/documentation-standard.md).

If a change requires an unapproved architectural, security, public-API, package-boundary, dependency, protocol, runtime, compatibility, licensing, or data-model decision, stop and request architect approval. State the required decision, viable options, tradeoffs, and a recommendation without implementing it.

## Change discipline

- Implement the smallest correct change within the approved phase.
- Do not refactor unrelated work or introduce speculative abstractions.
- Do not add dependencies, package boundaries, public APIs, or technology substitutions without authorization.
- Keep planned architecture clearly separate from implemented behavior.
- Update affected documentation and ADRs when an approved architectural decision changes.
- Use precise types, names, and structure before adding comments.
- Explain non-obvious purpose, invariants, security, lifecycle, concurrency, failure, and side effects where relevant.
- Do not manually maintain exhaustive caller/callee lists; those relationships are intended to become machine-derived.

## Validation

Kongono development requires PHP `>=8.5.0 <8.6.0` and Composer 2.10 or newer. Install the reproducible root development environment with:

```bash
composer install
```

Before submitting a PHP change, run the complete non-mutating gate:

```bash
composer quality
```

The gate validates the root and package Composer manifests, audits the locked dependency graph, checks the actual runtime platform, checks coding style, runs maximum-level static analysis with strict rules, and executes the test suite. The individual commands are available when narrowing down a failure:

```bash
composer validate
composer validate:composer
composer validate:packages
composer audit
composer audit:locked
composer platform
composer cs
composer analyse
composer test
```

`composer validate` remains Composer's built-in root-manifest validation command. Kongono's explicit root script is `composer validate:composer`, and `composer validate:packages` validates all nineteen package manifests in lexicographic directory order. Both omit publication-readiness checks because the license remains intentionally undecided. `composer audit` remains Composer's built-in installed-dependency audit. Kongono's deterministic lock-file audit is `composer audit:locked`, and the complete quality gate uses the explicit repository scripts. Use `composer cs:fix` only as an explicit formatting action, then rerun the non-mutating checks. Do not bypass dependency policy, platform requirements, static-analysis findings, or tests. A dependency addition, Composer plugin, baseline, suppression, or policy exception requires architect approval. Report exactly what ran and do not describe an unexecuted check as passing.

## PHP support and developer intelligence

The accepted [PHP support policy](docs/engineering/php-support-policy.md)
targets one compatibility-certified stable minor at a time. Phase 5B applies
`>=8.5.0 <8.6.0` to the root and all eighteen packages. PHP 8.5 is the one
active line, and Phase 5B proves PHP 8.5.9 on Windows. Eligible stable 8.5
patches remain in range. Moving to a later minor requires the complete
separately accepted compatibility gate; PHP 8.6, PHP 9, prerelease, nightly,
and automatic future-minor support are not claimed.

Ordinary contribution remains deterministic and works without AI or a network
except where an existing literal gate requires external access. The
Experimental Kongono Chie, Kongono Hantei, and Kongono Oshie packages implement
their bounded Phase 4 roles: Chie explains, diagnoses, and proposes one
reviewable documentation candidate; Hantei independently proves without
repairing; Oshie teaches cited accepted sources. None replaces contributor
review, accepted commands, or evidence. Developer companions are distinct from
application Agent Principals. See the [Phase 4 roadmap](docs/architecture/phase-4-roadmap.md).

## Commits and review

Keep changes focused and reviewable. Commit, branch, push, release, and publishing policy will be established separately; do not infer it from this document.

## Knowledge and evidence labels

When a claim depends on version or repository state, identify the exact state,
source authority class, evidence scope, and whether the claim is an accepted
fact, deterministic observation, inference, recommendation, generated
candidate, or unresolved uncertainty. A passing command belongs only to its
exact source state, environment, command, and scope.

Do not reuse historical reports as current proof after relevant bytes change,
promote generated material to authority, silently merge Kongono versions, or
hide disagreement between accepted policy and observed implementation. See
[ADR 0061](docs/adr/0061-versioned-knowledge-source-and-provenance-contract.md)
and the [knowledge foundation](docs/architecture/knowledge-foundation.md).

## Framework-knowledge checks

Phase 4C implements one bounded artifact package. Run `composer knowledge:check`
to generate and validate the exact 31-source `phase4-foundation` artifact in
memory without writing. Generated artifacts are derived evidence and must not be
checked in, treated as authority, mixed across source states, or used to widen
tool access. Optional publication requires an explicit external directory and
creates only the content-addressed initial file. See the [artifact guide](docs/architecture/framework-knowledge-artifact.md)
and [ADR 0062](docs/adr/0062-deterministic-framework-knowledge-artifact.md).

## Read-only Chie checks

## Read-only Hantei verification

Run `composer hantei:verify` to execute the complete fixed Phase 4E profile.
Callers cannot select or skip checks. A `PASS` applies only to the reported
subject and profile identities; `FAIL` and `BLOCKED` must remain distinct. Do
not treat ignored cache effects as source mutation or a verdict as authority.
Phase 4D adds `packages/chie` as an offline consumer of an explicit current
artifact. Focused work uses the package PHPUnit, PHPStan, and PHP CS Fixer
commands documented in the [toolchain](docs/engineering/toolchain.md). The
`kongono-chie` bin requires an explicit framework root and content-addressed
artifact path; canonical quality does not generate or discover an artifact for
it. Do not add prompts, ambient workspace access, network calls, models,
recommendations, patches, verification verdicts, or write authority. See the
[read-only Chie guide](docs/architecture/read-only-chie.md).

Phase 4F contributors may exercise Oshie's closed `catalog`, `lesson`, `exercise`, and `check` grammar only with an explicit current artifact. Do not add curriculum discovery, learner persistence, executable examples, free-form generation, mutation, verification, Git, deployment, or production authority. See the [read-only Oshie guide](docs/architecture/read-only-oshie.md).

## Reviewable Chie proposal checks

Phase 4G contributors may use only the explicit canonical proposal spec and the
closed exact-line rule against a current explanatory documentation source.
Review the forward and reverse diff, provenance, identities, warnings, and
pending handoff. Safe input is asserted, not sanitized. Chie does not apply the
candidate or run Hantei; after an external human-controlled application, run a
fresh Hantei verification. See the [proposal guide](docs/architecture/reviewable-chie-patch-proposals.md).

## Phase 4 external acceptance

Run `composer acceptance:phase4` for the exact cross-role 28-scenario proof.
The harness uses real local command boundaries and one verified temporary tree;
it must finish with real current-repository Hantei PASS and labels its isolated
FAIL/BLOCKED evaluator fixtures synthetic. Do not treat the Chie candidate as
applied or verified, invent an executable Oshie example, or infer model,
network, Git, deployment, production, or Phase 5 authority from acceptance.

## Phase 5A composition documentation

Phase 4 is checkpointed at `358e412929d6bb7316195d3a1748c346a2de0e1b`.
Phase 5A accepts documentation architecture only; contributors must not infer a
composition package, declaration syntax, build artifact, generator, command,
application skeleton, or runtime integration.

Future composition work uses explicit reviewed declarations, canonical symbol
spelling, constructor injection, deterministic build-before-runtime behavior,
and readable generated PHP. Do not introduce runtime service location, an
ambient/global container, resolver injection into application services,
request-time reflection or attribute scanning, hidden autowiring, property-hook
dependency resolution, runtime `eval`, or remote generated code. The existing
Experimental Dependency Injection resolver and Application Graph remain
unchanged.

Keep current, Accepted, and planned behavior explicit in documentation. The
[composition guide](docs/architecture/application-composition-and-build.md),
[Phase 5 roadmap](docs/architecture/phase-5-roadmap.md), and
[generated wiring guide](docs/engineering/generated-wiring.md) define the
accepted boundary. Phase 5B changes installability metadata only and adds no
runtime semantics, package, dependency, public API, polyfill, shim, release/LTS
promise, or composition/build capability. Phase 5B is accepted and its bounded
PHP range is current.

## Phase 5C composition declaration review

Phase 5C accepts one inert version-1 declaration contract at
`<application-root>/kongono.composition.json`. Phase 5D implements its strict
reader and artifact boundary in `kongono/composition`; no schema file, command,
wiring, or runtime integration exists. Contributors reviewing sources must require exact canonical symbols,
the six-member root grammar, explicit inputs/services/bindings/typed roots,
ordered constructor arguments, lifetime reachability, reverse cleanup,
deterministic bounds and failures, source containment, non-disclosure, and
bounded provenance. Do not infer or scan services, merge configuration,
introduce service location, or add later capability families.

Phase 5E generation accepts only individual `service`, typed `input`, or
`default: true` arguments. Model plural collaborators as an application-owned
typed aggregate with one named constructor edge per member. Do not add
collection injection, multibinding, tags, priorities, discovery, a container,
or a loader. Current composition remains caller-owned and DI, Graph, Kernel,
routes, and runtime behavior remain unchanged. Run the package tests, generated
PHP probes, focused analysis, and cache-disabled style checks before canonical
quality. Phase 5F has not begun. See
[ADRs 0070](docs/adr/0070-versioned-composition-source-and-provenance.md) and
[0071](docs/adr/0071-roots-bindings-lifetimes-and-cleanup.md), the
[artifact decision](docs/adr/0072-deterministic-composition-artifact-and-publication.md), the
[generated-wiring decision](docs/adr/0073-generated-typed-wiring-and-lifecycle-boundary.md), the
[source guide](docs/architecture/composition-source.md), and the
[declaration guide](docs/engineering/composition-declarations.md).

## Phase 10G observability changes

Changes to the observability workflow must preserve the closed configuration
shape, exact-current/no-clobber publication, explicit typed collaborators,
distinct trace/metric/event/audit semantics, and bounded operation cleanup.
Run validation for both observability packages, Console Application, Hantei,
and `skeletons/observability-minimal`, plus focused PHPStan, cache-disabled
PHP CS Fixer, package/CLI/skeleton behavior tests, canonical quality, and Phase
8-9 compatibility acceptance. Do not add endpoint discovery, credentials,
providers, ambient state, retry, background work, or a second profile.
