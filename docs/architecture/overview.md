# Architecture Overview

## Current reality

Phase 7 is complete at checkpoint
`b09b0c654f8b67f7cac3807f839f665e8c5b5469`, tree
`c0b6fed92dbb43d0bb91891b4e4496e84c0c85e4`. Phase 8G adds the upper
`kongono/console-application` boundary at the Phase 8 checkpoint. Phase 9G now
adds deterministic rule-free policy identity evidence and the supported
protected application workflow through `kongono/security-policy-build` and
`kongono/security-application`; Phase 10B adds the neutral
`kongono/observability` package, Phase 10C adds `kongono/tracing`, Phase 10D
adds `kongono/metrics`, and Phase 10E adds separate
`kongono/operational-events` and `kongono/security-audit`, bringing the topology
to exactly forty-nine acyclic production packages. The official one-shot `kongo` executable implements
exactly `application:build`, `security:check`, and `security:inspect`. The earlier Phase 2 checkpoint
`647053d4bddc4c76090bca9bf3d693faa5880c52` retains its root-owned, test-owned
runnable fixture. The
fixture externally proves bounded first-party HTTP messages, deterministic
routing, explicit PSR-15 middleware, Web Application's sole Kernel scope,
ports-only Web Runtime failure/delivery orchestration, exact cleanup, and
sequential isolation. It is not a production application, supported template,
SAPI adapter, server, listener, emitter, or deployment entrypoint.

No supported production HTTP integration exists. Phase 3B selects PHP
`cgi-fcgi`, CGI/1.1, and FastCGI `RESPONDER` as the first exact host profile.
Phase 3C and ADR 0053 implement `kongono/php-cgi` bounded explicit inbound
conversion. Phase 3D and ADR 0054 implement fully staged bounded response
emission with computed length. Phase 3E and ADR 0055 implement the literal typed
operator profile, fixed process-prerequisite bootstrap, and independent current-
delivery permission evaluator. Phase 3F implements the concrete delivery and
thin entry into Web Runtime. Streaming is unsupported; post-commit failure is
uncertain, throws, and is never rolled back or retried. Phase 3G implements the
bounded front-controller lifecycle and deployment constraints. Phase 3H adds
external test-owned acceptance through an operator-supplied loopback listener
without adding a production listener or deployment artifact. See the
[PHP CGI profile](php-cgi-fastcgi-profile.md) and [Phase 3 roadmap](phase-3-roadmap.md).

Kongono's runtime implementation includes the presentation-neutral Diagnostics
value foundation, Phase 0Q exact bounded context for all 11 Graph failures, and
Phase 0T exact bounded context for all six Kernel lifecycle failures,
alongside package-local runtime diagnostic metadata for every current deliberate
Kernel, Graph, and DI failure. Its broader
production behavior remains Phase 0J: architectural policy, documentation, a
    reproducible PHP quality toolchain, `packages/api-metadata`, `packages/kernel`,
`packages/application-graph`, `packages/dependency-injection`, and
`packages/diagnostics`. The
Experimental Graph snapshot supports exact outgoing and incoming selection of
immediately stored relations by endpoint-key value and exact whole-snapshot
selection through `nodesOfKind(NodeKind): array` and
`relationsOfKind(RelationKind): array`. Kind selection compares open labels
exactly within separate node and relation domains, returns `[]` for absent kinds,
and preserves canonical order and stored-object identity in fresh lists. The
node selector is an `O(N)` scan and the relation selector an `O(R)` scan, with
`O(k)` result allocation and no retained kind index or query cache. These are
narrow selectors, not a built-in kind vocabulary, combined filtering,
predicates, neighbors, traversal, or a generic query system. The Experimental
resolver still uses explicit canonical interface definitions, validates complete
dependency graphs, and constructs transient objects on every edge.

Phase 0M gives the existing Kernel, Graph, and DI exception markers three string
accessors for exact ID, title, and remediation. Three Internal backed-enum
catalogs own the 42 mappings; no universal catch or diagnostic interface exists.
Existing messages remain unchanged protected detail, and optional native causes
are preserved by identity. Expected application outcomes remain distinct;
infrastructure, native, autoloader, and consumer failures are not wrapped merely
to gain Kongono metadata.

No stable Graph identity, domain schema, metadata, serialization, persistence,
mutation, discovery, compiler, Graph/framework integration, runtime adapter,
HTTP or CLI runtime mapping, development error
page, production error surface, logging, telemetry, retry, cleanup, incident
system, extension host, agent runtime, query plan, performance budget, or CLI
executable is implemented.

The current package topology has forty production packages. `kongono/api-metadata` owns
`Kongono\ApiMetadata\Api` and `ApiStatus` and requires PHP only. Kernel,
Diagnostics, Application Graph, and Dependency Injection depend directly on it;
the framework root consumes all forty packages and Graph retains its Diagnostics
dependency. Phase 0T also makes Kernel, and Phase 0U makes DI, depend directly
on Diagnostics. Phase 1C adds `kongono/diagnostic-activation`, which requires
only API Metadata and PHP and owns the Experimental unbacked typed bootstrap
permission enum. Phase 1D adds the package's Experimental unbacked typed
adapter-controlled delivery-permission enum without adding a dependency. The
graph is acyclic. Diagnostic Handler is the only current package depending on
both Diagnostic Activation and Diagnostic Presentation; those lower layers do
not depend on Handler or each other. The former
`Kongono\Kernel\Api\*` FQCNs are absent without a shim.

`kongono/web-application` is the composition boundary above API Metadata,
Diagnostics, HTTP Server, Kernel, Routing, and the PSR interfaces. Web Runtime
depends directly on it; Web Application itself has no concrete HTTP Message,
DI, Graph, activation, presentation, handler, adapter, or transport dependency.

Phase 0X implements the leaf above the five originating packages:
`kongono/diagnostic-presentation`, namespace
`Kongono\DiagnosticPresentation\`. It depends directly on all five; only
Diagnostic Handler depends on it. The directory, manifest, four Experimental developer
concepts, and package-local context-free failure foundation exist. Phase 0X
added no renderer, trusted-mode selector, service binding, or runtime adapter.

`kongono/diagnostics` implements closed schemas, immutable context
and field snapshots, Safe/Protected disclosure vocabulary, exact scalar kinds,
deterministic bounded text representation, and its own package-local construction
failures. Graph is its first semantic consumer: the Graph catalog owns exact
schemas, Graph throw sites supply approved values, and the Graph marker adds
`diagnosticContext()`. Phase 0T gives Kernel's six failures the same value
accessor with independently owned schemas and exact Safe values. Phase 0U gives
DI's 25 diagnostics exact schemas across 26 throw sites and the same accessor;
all four catch markers remain unrelated, and Diagnostics construction failures remain
context-free. Capture performs no ambient lookup or I/O and retains no global
per-failure state. Safe and Protected perform no rendering or disclosure;
presentation and transport policy remain future.

The leaf's first implemented Experimental boundary is a pure trusted-developer
projection over exactly one contextual Kernel, Graph, or DI marker. It derives
the owner token, validates and copies catalog ID/title/remediation, fixed
operation and invariant, and canonically ordered bounded fields with exact kind,
Safe/Protected disclosure, and truncation state. It rejects an ambiguous multi-
marker source before accessor calls and excludes arbitrary failures, messages,
causes, traces, source, requests, environment, and transport data. The snapshot
is immutable and detached, performs no I/O, uses no ambient state or cache, and
retains nothing across operations.

Phase 0Z implements the Experimental
`Kongono\DiagnosticPresentation\Developer\Html\TrustedDeveloperHtmlRenderer`
directly below that projector in the same leaf. The pure renderer accepts only
the detached trusted model and returns one complete accessible, escaped,
bounded HTML5 document. This accepted presentation architecture adds no package
edge or runtime integration. The class, exact local stylesheet, and two golden
fixtures are implemented. Diagnostic Presentation itself owns no trusted
activation, adapter, served page, or production surface; Web Runtime's later
neutral adapter remains a separate package responsibility.

At the Phase 1A boundary, Kongono accepted the outer activation policy but had
not implemented enforcement. The planned composition boundary had to provide
affirmative **trusted bootstrap permission** fixed for one adapter instance,
and the adapter had to provide affirmative **adapter-controlled delivery
permission** independently for the current delivery. Missing,
invalid, ambiguous, failed, denied, or uncertain decisions prohibit the trusted
path. Client data, raw environment labels, heuristics, AI output, and ambient or
previous-operation state cannot activate or widen either permission.

The current neutral presentation sequence is:

```text
trusted bootstrap permission AND adapter-controlled delivery permission
    -> Web Runtime response selection
        -> ineligible or uncertain -> fixed generic response
        -> eligible supported failure -> pure projection -> pure rendering
    -> one send attempt and one close attempt through WebDeliveryInterface
```

The two permissions and delivery remain outside Diagnostic Presentation.
Phase 1C implements the trusted-bootstrap decision value and Phase 1D adds the
adapter-controlled delivery decision value in the separate Diagnostic Activation
package. Both are neutral typed inputs: neither evaluates raw configuration or
delivery evidence, and neither authorizes presentation alone. Phase 2G now
implements the neutral Web Runtime adapter and two-gate fail-closed response
selection from supplied decisions. Raw-configuration validation,
delivery-evidence authentication, production SAPI transport/emission, and a
served production page remain absent.
Projection remains separate from rendering, and production requires its own
deny-by-default model rather than redaction of the trusted developer model.

Phase 1E implements the pure combined calculation as Diagnostic Activation's
final readonly Experimental `TrustedDeveloperPresentationEligibility`. It holds
the two decisions for one current delivery and implements only their exact AND
truth table. It validates no evidence and enforces no presentation access;
Diagnostic Presentation remains separate and directly callable.

Phase 1H implements `kongono/diagnostic-handler` as the eighth leaf under
`Kongono\DiagnosticHandler\`. It depends directly only on PHP
`>=8.5.0 <8.6.0`,
API Metadata, Diagnostic Activation, Diagnostic Presentation, Kernel,
Application Graph, and Dependency Injection. The originating-package edges are
required by the accepted source-marker union; no direct Diagnostics edge exists,
and no current package depends on the handler. The sole production declaration,
`TrustedDeveloperDiagnosticHtmlPresenter`, checks eligibility
before source access, return `null` immediately when false, or call the existing
projector then renderer and return the exact HTML unchanged when true. It catches
nothing and owns no evidence, authority, non-trusted response, transport, or
delivery. It propagates all failures unchanged. The presenter is directly
callable but proves no authorization. Diagnostic Handler itself owns no adapter,
configuration reader, evidence evaluator, transport, delivery, response, or
production model. Web Runtime now owns the neutral adapter/delivery sequence.
PHP CGI inbound conversion, bounded response emission, closed native capture,
and concrete delivery are implemented. The host still owns the supported
process, listener, FastCGI framing, and deployment.

## Completed Phase 2 web flow

The [Phase 2 roadmap](phase-2-roadmap.md) preserves this completed dependency order:

```text
Phase 2A architecture and acceptance boundary
    -> Phase 2B HTTP message and interoperability decision
    -> Phase 2C minimal message foundation (implemented)
    -> Phase 2D deterministic routing (implemented)
    -> Phase 2E ordered middleware and terminal handling (implemented)
    -> Phase 2F bootstrap and Kernel operation ownership (implemented)
    -> Phase 2G outer web adapter and safe failure delivery (implemented)
    -> Phase 2H fixture application, black-box acceptance, and checkpoint (complete)
```

This is now a runnable test-owned fixture flow. Phase 2B selects PSR-7 through
`psr/http-message` 2.x, the server-side PSR-17 factory subset, and PSR-15;
Phase 2C implements `kongono/http-message`; Phase 2D implements the separate
`kongono/routing` package. Its immutable bounded declarations and matcher use
only exact encoded request method/path bytes, select by specificity independent
of declaration order, return `null` when unmatched, and retain no request state.
Matches establish no authentication or authorization. Phase 2E maps selected
route names to explicit PSR-15 handlers and adds the reserved match attribute;
unmatched output is delegated. Phase 2F validates that composition before Kernel
boot and owns exact begin/end scope closure for each application call. Web
Runtime and the root-owned fixture complete the bounded delivery and failure
proof. The PHP CGI converter now rejects malformed caller-supplied host input
before application work. The implemented Phase 3F delivery calls conversion
from `request()`, so Web Runtime selects its fixed generic 500. Phase 3D owns the
response emitter, 3E owns operator bootstrap and trust composition, and 3F owns
the delivery/entry composition. See the [HTTP message model](http-message-model.md),
[HTTP server model](http-server-model.md), and [Web Application model](web-application-model.md).

## Intended architecture

Kongono Framework is designed for humans, software clients, and AI agents. Its architecture combines:

1. A small, runtime-independent microkernel.
2. Independently installable components contributing explicit capabilities.
3. A language-neutral IR and authoritative Application Graph.
4. Progressive application architecture, with MVC as a friendly edge rather than a business-logic trap.
5. Persistent-runtime-safe lifecycle and operation scopes.
6. Human, Software/API, and Agent experiences derived from coherent application capabilities.
7. Secure extension governance and standards-based adapters.
8. Actionable, package-owned, securely disclosed diagnostics and explicit error
   boundaries.
9. Presentation-neutral bounded diagnostic values that do not merge package
   recovery ownership.

## Conceptual flow

```text
PHP source frontend (first)
        |
        v
Language-neutral IR              [exact mechanism: EXPERIMENTAL]
        |
        v
Application Graph structural core [implemented, Experimental]
        |
        v
Authoritative domain Graph       [schema/serialization/compiler: EXPERIMENTAL]
        |
        +--> Human Experience
        +--> Software/API Experience
        +--> Agent Experience
        +--> Tooling / infrastructure / documentation
```

Only the structural core is implemented. The surrounding flow expresses intended responsibility, not an implemented compiler pipeline or public contract.

## Framework shape

The Kernel owns boot and shutdown state, operation-scope identity and ownership, active-operation accounting, lifecycle misuse detection, its exception family, and six exact diagnostic-context schemas and values. API Metadata neutrally owns compatibility classification. The Application Graph package owns only the immutable structural snapshot, its local exception family, and its 11 diagnostic-context schemas and values. Dependency Injection separately owns explicit definitions, definition-graph validation, transient construction, and 25 diagnostic schemas across 26 throw sites. Diagnostics owns presentation-neutral bounded values and its construction-failure boundary. Diagnostic Activation owns the implemented typed trusted-bootstrap and adapter-controlled delivery permissions. Diagnostic Presentation owns the implemented pure trusted-developer projection core and pure HTML renderer but does not depend on Diagnostic Activation or own activation decisions. Diagnostic Handler is the only lower package depending on both and owns eligibility-first neutral sequencing. HTTP Message owns the selected server-side message and factory roles plus 28 bounded diagnostics. Routing owns bounded route declarations and deterministic matching; HTTP Server owns PSR-15 composition. Web Application owns application-call Kernel scope cleanup. Web Runtime owns outer request-independent evidence sequencing, fixed generic or eligible trusted response selection, and exact send/close attempts. PHP CGI owns bounded explicit inbound conversion. The root-owned Phase 2 fixture supplies the only concrete runnable integration and is test infrastructure, not production behavior. No production SAPI/server/listener/emitter, trusted network authorization mechanism, or supported deployment profile exists. The monorepo retains atomic root validation across all thirty implemented packages.

## Interoperability boundaries

Composer, PSRs, OpenAPI, OpenTelemetry, OAuth/OIDC, MCP, A2A, and web standards are interoperability tools where explicitly accepted. ADR 0044 accepts PSR-7, selected server-side PSR-17 factories, and PSR-15 as narrow edge contracts while keeping a first-party Kongono HTTP message implementation. ADR 0047 records the bounded first-party PSR-15 server implementation. Their protocols do not supersede Kongono's internal concepts. Kongono-owned abstractions preserve portability and prevent an exporter, provider, adapter, runtime, or third-party message implementation from becoming the architecture.

## Related documentation

- [Application Graph](application-graph.md)
- [Microkernel](microkernel.md)
- [Progressive architecture](progressive-architecture.md)
- [Runtime model](runtime-model.md)
- [Phase 3 roadmap](phase-3-roadmap.md)
- [PHP CGI FastCGI profile](php-cgi-fastcgi-profile.md)
- [Phase 2 roadmap](phase-2-roadmap.md)
- [HTTP message model](http-message-model.md)
- [HTTP server model](http-server-model.md)
- [Web Application model](web-application-model.md)
- [Routing model](routing-model.md)
- [Agent model](agent-model.md)
- [Extension platform](extension-platform.md)
- [Error handling and actionable diagnostics](../engineering/error-handling.md)

## Phase 2 runnable topology

The Phase 2 checkpoint had thirteen acyclic production packages;
`kongono/php-cgi` is now the fourteenth. `kongono/web-runtime`
is the top-level leaf for request-independent delivery evidence, one Web
Application call, fixed generic or eligible trusted failure selection, one send
attempt, and one close attempt. Web Application retains the sole Kernel scope.

Phase 2H adds no package. Its root-owned [runnable fixture](phase-2-runnable-fixture.md)
implements the Public delivery port and proves the full path externally in a
fresh subprocess, including sequential same-process isolation. No production
SAPI/server/listener/emitter, transport authorization, logging, telemetry,
incident system, deployment surface, or production-readiness claim exists.

Phase 3F now supplies the production PHP CGI implementation of the Public Web
Runtime delivery port. The Internal delivery captures one bounded native
snapshot, evaluates current-delivery evidence, opens input lazily, converts and
emits once, and detaches all retained references before cleanup. The
Experimental entry passes one fresh delivery to an already-composed runtime.
The dependency edge is `php-cgi -> web-runtime`; PHP CGI has no direct Web
Application edge. Phase 3G now adds the stateless Experimental front controller,
one entry attempt plus one supplied application-shutdown attempt, and diagnostic
1019 for simultaneous failure. Application construction and partial-boot
cleanup remain caller-owned. `Production` trusted diagnostics remain denied;
uncertain emission or shutdown requires external process retirement. The
deployment constraints add no production listener, server configuration, or
deployment artifact. Phase 3H supplies external test-owned proof. Phase 3
remains smaller than a full framework, Stable API, 1.0, or production-hardening
certification. The frozen intrinsic query ceiling is 8,192 bytes; matching
origin-form target composition makes 8,190 bytes the effective external
maximum. Phase 3 is closed and no later phase begins automatically.

## Current Phase 4 planning boundary

Phase 3 is complete at checkpoint
`6aacafc92f07b567818173d660e2e182dfa83d23`. Phase 4A accepts documentation
architecture only and adds no package, dependency edge, public API, command, or
runtime. The topology remains exactly fourteen acyclic production packages.

The planned developer-intelligence family shares one future versioned,
inspectable, AI-independent knowledge foundation. Kongono Chie builds, Kongono
Hantei independently proves, and Kongono Oshie teaches. These developer
companions are distinct from application Agent Principals; none is implemented.
The deterministic framework remains usable offline and without AI.

The accepted PHP policy targets one compatibility-certified minor at a time.
Phase 5B applies `>=8.5.0 <8.6.0` to the root and all eighteen packages. PHP
8.5 is the active line, and PHP 8.5.9 on Windows is the proved environment.
Stable eligible 8.5 patches remain in range; PHP 8.6, PHP 9, prerelease,
nightly, and automatic future-minor support are not claimed.

See [developer intelligence](developer-intelligence.md), the
[Phase 4 roadmap](phase-4-roadmap.md), and the
[PHP support policy](../engineering/php-support-policy.md). Phase 4 is not the
full framework, Stable API, 1.0, auto-fix, autonomous operations, or production
AI.

## Current Phase 4B boundary

## Current Phase 4E boundary

The implemented Hantei leaf consumes API Metadata, Diagnostics, and Knowledge.
Its fixed profile runs eight repository quality scripts, preserves exact subject
identity, and emits bounded derived verdict evidence. No existing package
depends on Hantei, and Hantei does not depend on Chie.
[ADR 0061](../adr/0061-versioned-knowledge-source-and-provenance-contract.md)
and the [knowledge-foundation architecture](knowledge-foundation.md) accept the
semantic contract for six source-authority classes, six claim classes, exact
source state, bounded provenance, visible conflict, version-matched citations,
deterministic derivation, disclosure, and framework/workspace separation.

This is architecture only. The repository still has fourteen acyclic
production packages and no new dependency edge. No knowledge artifact,
generator, validator, source inventory, schema, digest, command, retrieval
system, model, workspace reader, Graph export, or developer-intelligence
runtime exists. Phase 4C is next in the unchanged sequential roadmap and must
separately select the bounded AI-independent implementation slice.

Phase 4C implements that bounded slice as the fifteenth acyclic package,
`kongono/knowledge`, depending only on API Metadata and Diagnostics. It owns one
closed 31-source canonical artifact, exact identities, whole-source citations,
complete in-memory generation/current validation, and optional content-
addressed atomic initial publication. The artifact is derived evidence, not
authority, and is not checked in.

Phase 4D adds the sixteenth acyclic package, `kongono/chie`, depending only on
API Metadata, Diagnostics, and Knowledge. It validates one explicit artifact
against one explicit root and returns only exact cited sections for six closed
topics or bounded literal diagnostic-identifier occurrences. It has no prompt,
model, general retrieval, arbitrary workspace access, mutation, Hantei verdict,
Graph input, network, deployment, or production authority. See
[read-only Chie](read-only-chie.md).

## Phase 4F teaching boundary

Experimental Oshie teaches six exact version-matched source sections and checks
fixed answers without learner persistence or mutation. Its teaching remains
derived and citation-bound. Chie, Hantei, and Oshie stay independent; Phase 4H
externally proves the boundary without inventing an executable example. See
[read-only Oshie](read-only-oshie.md).
## Phase 4G review candidate

The Experimental Chie package can produce one deterministic, reversible
documentation candidate from a validated current Knowledge artifact. It cannot
apply or verify the candidate. Exact provenance and a pending human/fresh-Hantei
handoff preserve the separation between derived review material and accepted
source authority.

Phase 4H adds the root-owned
[cross-role external acceptance harness](phase-4-external-acceptance.md). Its 28
scenarios prove shared current identities, direct accepted-source readability,
offline Knowledge/Chie/Oshie operation, real versus synthetic Hantei evidence,
visible failure states, inert instructions, non-disclosure, temporary cleanup,
and unchanged role authority. Phase 4A through 4H are implemented and externally
accepted at the bounded checkpoint. Kongono remains pre-1.0 and not
production-ready.

## Completed Phase 5 boundary

Phase 4 is checkpointed at `358e412929d6bb7316195d3a1748c346a2de0e1b`
with eighteen acyclic production packages. Application construction remains
caller-owned at that checkpoint.

Phase 5A accepts documentation architecture for one explicit application
composition root, deterministic build-before-runtime, and readable generated
PHP constructor wiring. Kernel remains lifecycle-neutral. The current
Experimental resolver remains a runtime definition resolver with transient-per-
edge construction, and the current structural Application Graph remains
unchanged and unintegrated.

The supported path rejects runtime service location, ambient containers,
resolver/container injection into application services, property-hook
dependency resolution, request-time reflection/scanning/autowiring, runtime
`eval`, and remote generated code. Production startup must reject missing,
stale, partial, mixed, ambiguous, or unverifiable build state before work.

Phase 5B applies the rolling current-minor Composer migration as installability
metadata only, without runtime semantics, a package, dependency, public API,
polyfill, shim, release/LTS promise, or composition/build capability. Phase 5A
remains accepted. Phase 5B is accepted and its bounded PHP constraint is
current.

Phase 5C accepts the source and lifecycle contract for exactly one inert
`<application-root>/kongono.composition.json`: schema
`"kongono.composition"`, integer version `1`, six exact top-level members,
canonical inputs/services/bindings/typed roots, three lifetimes, cleanup,
bounds, deterministic validation, safe file handling, canonicalization, and
provenance. Phase 5D implements the nineteenth acyclic package,
`kongono/composition`, for strict source/symbol validation, canonical model and
provenance-bearing artifact construction, exact current validation, and
content-addressed no-clobber publication. Phase 5E implements canonical typed
PHP wiring, exact generated identities, non-executing validation/publication,
and isolated application/operation lifetime support. Plural collaboration uses
an explicit application-owned typed aggregate; collection injection and
automatic multibinding remain prohibited. Phase 5F adds exact-current loading
and the single-root web profile while preserving Web Application, Web Runtime,
Kernel-operation, and PHP-CGI ownership. Phase 5G adds the copy-once minimal
application workflow, and Phase 5H adds root-owned external acceptance. Phase 5
is complete at checkpoint `c431482bea664ce3c3201794332c62cab1ed034e`.
See [ADR 0068](../adr/0068-supported-application-composition-and-build-boundary.md),
[ADR 0069](../adr/0069-generated-typed-wiring-and-no-runtime-service-location.md),
[ADR 0070](../adr/0070-versioned-composition-source-and-provenance.md),
[ADR 0071](../adr/0071-roots-bindings-lifetimes-and-cleanup.md),
[ADR 0072](../adr/0072-deterministic-composition-artifact-and-publication.md),
[ADR 0073](../adr/0073-generated-typed-wiring-and-lifecycle-boundary.md),
the [composition guide](application-composition-and-build.md), the
[source guide](composition-source.md), the
[declaration guide](../engineering/composition-declarations.md), and the
[Phase 5 roadmap](phase-5-roadmap.md).

## Completed Phase 6 Data Platform boundary

Phase 6 is complete at checkpoint
`f5903134e77bb2ecfaece6ee6b435a8ab2ecbd05`. Phase 6A accepts a layered Deterministic Data Platform without implementing it.
Its conceptual dependency direction runs from low-level connectivity and
execution through explicit SQL plans and dialect compilation, schema and
migrations, optional generated Data Mapper support, and finally composition
with operation-scoped lifecycle.

SQL stays explicit and parameter-bound. Provider capabilities, unsupported
behavior, transaction ownership, resource cleanup, and persistent-runtime
isolation remain visible. Static facades, ambient connections, service
location, request-time mapping reflection, runtime proxies, hidden lazy loading,
and Active Record as the core model are rejected defaults. Derived plans and
generated mapping are deterministic evidence rather than authority, and data
diagnostics retain existing non-disclosure rules.

Phase 6C implements the neutral Experimental `kongono/database` package
under `Kongono\Database\` and separate Experimental PostgreSQL PDO adapter
`kongono/database-pdo-pgsql` under `Kongono\DatabasePdoPgsql\`. The adapter
depends on the neutral package; the reverse edge is prohibited. PostgreSQL via
`pdo_pgsql` with PostgreSQL 17.2 is the proved first profile only.

Version 1 fixes explicit connector/session/executor/transaction boundaries,
immutable parameter-bound unparsed SQL, exact bounded values and row labels,
session capabilities, row/completion expectations, one active stream and
transaction, explicit states/options, reverse cleanup, combined-failure
retention, no auto retry, and non-disclosing error categories. No ambient
authority, native handles, hidden loading, runtime reflection/proxies, or
Active Record default enters the contract.

Phase 6D implements two Experimental higher-layer packages: immutable bounded
SQL plans and explicit PostgreSQL 17 compilation. The compiler is stateless,
quotes every identifier segment, allocates occurrence-ordered parameters, and
returns the existing immutable statement type. Mutation scope distinguishes a
predicate from deliberate all-row intent. Raw parameter-bound statements remain
supported. Phase 6E adds the explicit typed migration catalog and pure planner
plus PostgreSQL 17 DDL and transaction-scoped execution. Catalog identity,
exact-prefix history, destructive review, lock denial, rollback, and uncertainty
are visible. Phase 6F adds optional Experimental compile-time mapping,
provenance-bound artifacts, and generated typed mappers/repositories. Generated
output is derived and not checked in; raw SQL and typed plans remain first-class.
Phase 6G adds the provider-neutral outer database-application integration layer,
bringing that phase's topology to twenty-seven packages. Phase 6H adds the
root-owned whole-platform PostgreSQL 17 acceptance, four-scenario local
observational measurement evidence, documentation reconciliation, and exact
checkpoint without another production package.

No ORM, pooling, migration startup, deployment entrypoint, runtime telemetry, or
production provider service exists. Kongono remains
pre-1.0 and not production-ready. See [ADR 0077](../adr/0077-layered-data-platform-and-explicit-sql-boundary.md),
[ADR 0078](../adr/0078-deterministic-data-plans-and-operation-scoped-persistence.md),
[ADR 0079](../adr/0079-neutral-database-session-and-provider-capability-contract.md),
[ADR 0080](../adr/0080-explicit-database-result-transaction-and-failure-lifecycle.md),
[ADR 0081](../adr/0081-postgresql-pdo-adapter-and-real-provider-proof.md),
[ADR 0082](../adr/0082-immutable-database-sql-plans-and-postgresql-dialect-compilation.md),
[ADR 0083](../adr/0083-deterministic-schema-migrations-and-postgresql-execution.md),
[ADR 0084](../adr/0084-compile-time-data-mapping-and-generated-repositories.md),
the [Data Platform guide](data-platform.md), the [database contract](database-contract.md),
the [SQL plan guide](database-sql-plans.md),
the [migration guide](database-migrations.md),
the [mapping guide](database-mapping.md),
the [engineering contract tables](../engineering/database-contracts.md), and the
[Phase 6 roadmap](phase-6-roadmap.md).

## Completed Phase 7 Deterministic Persistence milestone

Phase 7A accepts an optional upper persistence capability while preserving all
direct Phase 6 paths. Domain objects remain application-owned plain PHP classes;
future metadata is bounded compile-time authority transformed into deterministic
models, artifacts, and ordinary generated PHP evidence. Runtime Reflection
mapping, proxies, hidden lazy I/O, Active Record inheritance, private-state
bypass, service location, and automatic flush are rejected.

Future persistence contexts, identity maps, snapshots, and change sets are
operation-scoped and retired before fallible cleanup. The caller owns
transactions, flush is explicit, uncertain failure is terminal, and load plans,
aggregate boundaries, relationships, ordering, and cascades are visible and
bounded. Direct SQL, plans, migrations, generated Data Mappers, and repositories
remain first-class without persistence.

Phase 7B adds the Experimental neutral `kongono/persistence` package. Phase 7C
adds Experimental `kongono/persistence-build` and brings the topology to twenty-
nine packages. The neutral identity, opaque token, state,
value-free change, context-interface, flush-result, diagnostic, and failure
contracts provide no runtime behavior. The build package validates application
persistence intent against exact current mapping and domain evidence and emits a
detached canonical provenance artifact. No concrete context, identity map, unit
of work, generated PHP, runtime loader, database execution, transaction helper,
runtime relationship, repository, command, or integration exists. Phase 7D adds
deterministic content-addressed ordinary PHP state adapters for public
construction/accessor hydration, extraction, identity, snapshots, and neutral
change evidence. Their validation is non-executing, structural, exact-current,
and fail closed. Phase 7E adds one concrete single-entity operation context over
a caller-owned database executor. Explicit flush executes at most one change;
transaction/resource ownership remains external, and terminal uncertainty
releases retained domain references. Phase 7F adds explicit bounded one-level
relationship loads/cascades and deterministic generated aggregate repositories.
Generated-code loading, SQL generation, transaction ownership, and Composition
remain absent at Phase 7F. Phase 7G adds exact-current persistence loading and
the outer Composition workflow in `kongono/persistence-application`, bringing
the graph to thirty packages. Phase 7H closes the milestone through isolated
PostgreSQL 18.6 acceptance, PostgreSQL 17.2 Phase 6 regression, cleanup and
nondisclosure proof, documentation, and the exact checkpoint. See [ADR 0087](../adr/0087-explicit-persistence-model-and-compile-time-orm-boundary.md),
[ADR 0088](../adr/0088-operation-scoped-identity-change-tracking-and-transaction-authority.md),
[ADR 0089](../adr/0089-neutral-persistence-contracts-and-package-boundary.md),
[ADR 0090](../adr/0090-deterministic-persistence-model-and-artifact-compilation.md),
[ADR 0091](../adr/0091-deterministic-generated-persistence-state-evidence.md),
[ADR 0092](../adr/0092-operation-scoped-persistence-context-and-explicit-unit-of-work.md),
[ADR 0093](../adr/0093-explicit-load-plans-bounded-cascades-and-generated-aggregate-repositories.md),
[ADR 0094](../adr/0094-exact-current-generated-persistence-application-integration.md),
[ADR 0095](../adr/0095-phase-7-external-persistence-acceptance-and-checkpoint.md),
the [Deterministic Persistence guide](deterministic-persistence.md), and the
[Phase 7 roadmap](phase-7-roadmap.md).

## Implemented Phase 8A through Phase 8H Structured CLI edge

Phase 8A accepts the future `kongo` executable identity, with no alias, and a
deterministic automation-first architecture. Command meaning, one explicit
invocation with a neutral result, and presentation/process adaptation are
separate. Ambient host reads, direct handler output, hidden interaction,
service location, and handler process exit are rejected.

Definitions and provenance must become immutable exact-current build evidence;
runtime scanning, Reflection discovery, mutable registries, plugin probing, and
network discovery are prohibited. Human and versioned machine rendering derive
from the same neutral result. Prompt capability, no-interaction behavior,
mutation authority, destructive intent, confirmation, apply, and honest dry-run
remain explicit. Each invocation owns bounded reverse cleanup and sequential
same-process isolation under existing nondisclosing diagnostic doctrine.

The neutral CLI layer remains below capability adapters, while the future
executable is an upper Composition leaf. Phase 8C extends the Experimental
`kongono/console` neutral package with immutable specifications and a pure
already-tokenized parser. The separate Experimental `kongono/console-build`
package compiles one explicit inert `kongono.commands.json` source to ordinal,
canonical, exact-current definition evidence. It creates no publication,
loader, registry, discovery, handler binding, executor, concrete operation,
command, presenter, or executable. Phase 8D adds the separate Experimental
`kongono/console-runtime` package above Console. Immutable explicit bindings
select one already-constructed handler; each dispatch creates one fresh
operation with explicit cancellation, bounded reverse cleanup, combined failure
preservation, same-instance reentrancy rejection, and sequential same-process
isolation. It performs no presentation, process adaptation, discovery, service
location, application integration, or real command behavior. Phase 8E adds
bounded result records and a separate deterministic human/machine presentation
package with canonical version-1 JSON, shared exit mapping, Protected-field
omission, and one explicit selected-port write. Phase 8F adds the Experimental
`kongono/console-commands` upper package with three exact read-only handlers,
explicit accepted capability inputs, Console Runtime execution, and generated
Composition roots. Phase 8G adds `kongono/console-application`, the official
extensionless `kongo` Composer binary, and exactly `application:build`.
`--plan` is non-mutating and `--apply` explicitly authorizes the two atomic,
content-addressed, contained, no-clobber Composition publications. There is no
prompt; `--no-interaction` is automation-safe. Human and machine forms share
one result and exit map. The Phase 8F handlers stay programmatic until truthful
snapshots can be supplied. No second command, alias, daemon, watcher, installer,
deployment manager, shell, runtime
discovery, service location, or ambient authority exists.
See [ADR 0096](../adr/0096-explicit-structured-cli-and-automation-boundary.md),
[ADR 0097](../adr/0097-deterministic-command-lifecycle-and-presentation-separation.md),
[ADR 0098](../adr/0098-neutral-structured-cli-contracts-and-package-boundary.md),
[ADR 0099](../adr/0099-deterministic-command-parsing-and-definition-evidence.md),
[ADR 0100](../adr/0100-operation-scoped-command-execution-and-cleanup.md),
[ADR 0101](../adr/0101-deterministic-command-presentation-and-process-adaptation.md),
[ADR 0102](../adr/0102-first-read-only-framework-capability-commands.md),
[ADR 0103](../adr/0103-supported-kongo-edge-and-honest-application-build.md),
[ADR 0104](../adr/0104-phase-8-external-cli-acceptance-and-checkpoint.md),
the [Structured CLI guide](structured-cli.md), the [Phase 8 external acceptance guide](phase-8-external-acceptance.md),
and the [Phase 8 roadmap](phase-8-roadmap.md). Phase 8H adds test evidence and
the local checkpoint only; it changes no production package or command.

## Implemented Phase 9A through Phase 9H Identity and Access Security milestone

Phase 9A accepts the provider-neutral separation of identity, authentication,
authorization, credential/session transport, protected-capability enforcement,
disclosure, audit, and telemetry. Authentication success grants no permission.
Authorization is deny by default and fail closed; only an exact `Permit` for
one immutable operation Security Context, exact application-owned Action and
Resource, exact current Policy Evidence, and explicit environmental facts may
proceed to a protected capability.

Future neutral security contracts remain below HTTP, provider, session,
application-workflow, and executable adapters. Composition retains explicit
construction and generated wiring ownership. Security state is operation-
scoped and cannot be obtained from globals, thread locals, service location,
ambient request state, or Kernel lifecycle. Middleware may adapt evidence but
is not the sole enforcement boundary.

Phase 9B implements only the Experimental `kongono/identity-access` neutral
contract package over API Metadata, Diagnostics, and PHP. Its immutable
vocabulary covers five principal kinds, opaque principal/resource references,
eight authentication outcomes, inert Permit/Deny/Indeterminate decisions,
canonical Action/ResourceType names, and explicit anonymous/authenticated
Security Context. Package diagnostics and exceptions are bounded and
non-disclosing.

No authenticator, verifier, credential, cryptography, policy evaluator,
decision receipt, session profile, HTTP or Composition integration,
protected-capability enforcement, schema, provider, command, audit store,
telemetry, or application workflow exists because of Phase 9B.

Phase 9C adds the Experimental `kongono/authentication` package over API
Metadata, Diagnostics, Identity Access, and PHP. Explicit caller-owned evidence
selects one registered verifier by exact mechanism. Each begun attempt verifies
once and retires once; a combined exception preserves both primary and cleanup
failures. Authenticated facts carry principal, mechanism, unnamed ordinal
strength, authentication instant, and validity instant but grant no authority.
Caller-supplied current time and requirements determine freshness and strength
with inclusive equality bounds. The orchestrator retains only verifier
registration and no current-call state across sequential or reentrant calls.

No concrete verifier/provider, credential, password, token, key, cryptography,
session/cookie transport, HTTP integration, authorization evaluator, policy,
Security Context construction, Composition wiring, enforcement, command, audit,
telemetry, application workflow, or Phase 9D behavior exists because of Phase
9C.

Phase 9D adds Experimental `kongono/authorization`. Independently expected and
evidence policy references must match exactly; mismatch and unsupported policy
return Indeterminate without evaluator execution. One exact evaluator receives
the identical request once and its exact result is preserved; thrown failure
never permits. Receipts expose only bounded classification evidence and are not
transferable permission. The engine retains no call state.

No policy language/build/loader, roles/permissions, ambient guard,
protected-capability enforcement, Security Context construction/lifecycle,
HTTP/session transport, Composition wiring, database enforcement, audit,
telemetry, AI authority, or workflow exists because of Phase 9D.

Phase 9E adds explicit version-2 application/operation input lifetimes to
Composition while preserving version-1 inputs as application-lifetime. It adds
the Experimental `kongono/security-runtime` outer package, which constructs one
fresh anonymous or exactly verified authenticated context for one generated
operation. The application retains no operation input, and validation occurs
before lease acquisition or construction. Authentication success grants no
authorization.

Phase 9F adds the Experimental `kongono/security-http` and
`kongono/security-enforcement` outer packages. The former implements exactly
the same-origin HTTPS `browser.session.v1` profile using the host-only
`__Host-kongono_session` cookie and applicable session-bound
`X-Kongono-CSRF` evidence. The latter evaluates exact context/action/resource
bindings and executes the protected capability only for exact Permit. Session
syntax and cookie emission are not session truth, cryptography, storage,
login, or authorization. Authentication success, routing, middleware, and
receipts grant no authority, and no ambient current-user/session/decision
state exists.

Phase 9G adds exact-current rule-free policy identity evidence, one supported
request-to-operation-to-enforcement workflow, a copy-once minimal skeleton, and
read-only inspect/check commands. Application-owned evaluator decisions remain
authoritative and only exact Permit at the enforcer executes. The topology is
exactly forty-four and acyclic. No second provider/profile, broad identity
product, autonomous policy, agent-delegation runtime, or deployment manager
exists. Phase 9H adds only root-owned external proof and the exact local
checkpoint. The profile exercises fresh process and temporary application state
without adding framework authority. Phase 10 runtime behavior remains
unimplemented; Phase 10A documentation architecture is accepted below. See
[ADR 0105](../adr/0105-explicit-identity-authentication-and-authorization-boundary.md),
[ADR 0106](../adr/0106-fail-closed-security-context-and-policy-separation.md),
[ADR 0107](../adr/0107-neutral-identity-and-access-contracts-and-package-boundary.md),
[ADR 0108](../adr/0108-deterministic-explicit-authentication-orchestration.md),
[ADR 0109](../adr/0109-deterministic-deny-by-default-authorization-evaluation.md),
[ADR 0110](../adr/0110-operation-scoped-security-context-and-generated-composition-inputs.md),
[ADR 0111](../adr/0111-secure-browser-session-transport-and-capability-enforcement.md),
[ADR 0112](../adr/0112-deterministic-security-policy-evidence-and-supported-application-workflow.md),
[ADR 0113](../adr/0113-phase-9-external-security-acceptance-and-checkpoint.md),
the [Identity and Access Security guide](identity-and-access-security.md),
the [operation-scoped Security Context guide](operation-scoped-security-context.md),
the [browser-session security and capability enforcement guide](browser-session-security-and-capability-enforcement.md),
the [security application workflow](security-application-workflow.md), and
the [Phase 9 external acceptance guide](phase-9-external-acceptance.md), and
the fixed [Phase 9 roadmap](phase-9-roadmap.md).

## Phase 10 Observability and Operational Evidence milestone

Phase 10A accepts the architecture, Phase 10B implements the separate
Experimental `kongono/observability` neutral contract package, Phase 10C
implements the separate Experimental `kongono/tracing` runtime, and Phase 10D
implements the separate Experimental `kongono/metrics` runtime, and Phase 10E
implements separate Experimental Operational Events and Security Audit sibling
packages. The graph is exactly forty-nine acyclic production packages. All four
signal packages
depend only on Observability, API Metadata, Diagnostics, and PHP; neither has a
production reverse dependency.

The boundary keeps diagnostics, traces, metrics, operational events, security
audit evidence, authorization receipts, and product analytics separate. Signals
are bounded typed evidence rather than identity, authorization, policy truth,
business state, or repair authority. The neutral context is immutable,
explicit, and optionally correlated; Tracing and Metrics retain it only within
their explicit bounded operation lifecycles. No global logger/tracer/meter,
provider, ambient current span, thread/fiber local, request singleton, service
locator, or hidden environment configuration is accepted.

Disclosure remains schema-allowlisted and governed by existing diagnostic
classes. Repeated attributes and metric dimensions need explicit size and
cardinality budgets. Clocks, identifiers, randomness, propagation validation,
and sampling remain explicit collaborators. Exporters stay at outer adapters
with bounded concurrency, memory, backpressure, failure, flush, and cleanup;
instrumentation failure cannot hide the primary outcome, and audit evidence is
not a replayable grant or current durability promise.

OpenTelemetry is the selected outer interoperability direction and not a
current SDK, dependency, global provider, exporter, protocol, collector, or
backend. See [ADR 0114](../adr/0114-explicit-observability-and-operational-evidence-boundary.md),
[ADR 0115](../adr/0115-structured-signals-context-and-exporter-neutrality.md),
the [Observability and Operational Evidence guide](observability-and-operational-evidence.md),
the fixed [Phase 10 roadmap](phase-10-roadmap.md), and
[ADR 0116](../adr/0116-neutral-observability-contracts-and-package-boundary.md), which
owns the exact optional correlation, bounded scalar attributes, versioned
signal identity, closed kind/status, immutable observation envelope, and
recorder-interface surface, plus
[ADR 0117](../adr/0117-operation-scoped-tracing-lifecycle-and-typed-propagation.md),
which owns the explicit bounded tracing lifecycle and typed propagation, and
[ADR 0118](../adr/0118-bounded-cardinality-metric-aggregation-and-explicit-collection-lifecycle.md),
which owns bounded schema cardinality, four exact instruments, deterministic
immediate aggregation, and explicit Active/Collected/Retired collection
lifecycle. Phase 10E additionally implements separate Experimental Operational
Events and Security Audit siblings over Observability, API Metadata,
Diagnostics, and PHP. Their finite schemas admit canonical typed values and
derive disclosure from fields actually supplied. Events retain explicit
severity and neutral status; audit evidence retains explicit outcome, retention
intent, and `not_guaranteed` durability. Each valid call records exactly one
bounded observation; failure is visible, nondisclosing, and unretried. The
packages do not depend on or convert to one another. The graph is exactly
forty-nine acyclic packages and 166 local edges. No logger facade, dynamic
labels, raw sample log, buffer, exporter, backend, store, retention enforcement,
tamper evidence, compliance claim, authorization behavior, product analytics,
OpenTelemetry dependency, or application wiring exists. See
[ADR 0119](../adr/0119-structured-operational-events-and-bounded-security-audit-evidence.md).
Phase 10F requires separately authenticated authority.

Phase 10F now implements the outer Experimental `kongono/opentelemetry` bridge
under [ADR 0120](../adr/0120-explicit-opentelemetry-otlp-http-protobuf-bridge.md).
It preserves the lower neutral boundary while directly building one locked
OTLP HTTP/protobuf profile through an explicit transport. Bounded queue,
backpressure, synchronous flush, partial progress, close cleanup,
nondisclosure, and bridge isolation are current behavior. No SDK/provider,
ambient context, environment discovery, HTTP client, retry, backend, durable
audit claim, or application integration is added. At the Phase 10F checkpoint,
the graph was exactly fifty acyclic packages and 169 local edges.

Phase 10G adds deterministic observability configuration/currentness and one
explicit application workflow through two outer Experimental packages. The
running application binds immutable checked configuration and caller-owned
clock, identifier, sampler, time-anchor, and transport capabilities. Operations
coordinate trace and metric completion plus distinct event/audit recording and
explicit bridge flush/close without ambient state or reload. Two read-only
observability commands bring the official family to five commands. The graph is
52 acyclic packages and 182 local edges.

Phase 10H accepts
[ADR 0122](../adr/0122-phase-10-external-observability-acceptance-and-checkpoint.md)
and closes Phase 10 with root-owned external acceptance and one exact local
checkpoint. The fresh-process harness proves the supported application and
signal boundaries without adding production packages, APIs, dependencies,
exporters, providers, backends, collectors, deployment behavior, or Phase 11
work. See the [external acceptance guide](phase-10-external-acceptance.md).
Kongono remains pre-1.0.
