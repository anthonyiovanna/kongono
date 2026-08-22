# Architecture Overview

## Current reality

Phase 2A through 2H are complete at checkpoint
`647053d4bddc4c76090bca9bf3d693faa5880c52`. The repository has exactly fourteen
acyclic production packages and a root-owned, test-owned runnable fixture. The
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
HTTP or CLI mapping, development error
page, production error surface, logging, telemetry, retry, cleanup, incident
system, extension host, agent runtime, query plan, performance budget, or CLI
executable is implemented.

The current package topology has fourteen packages. `kongono/api-metadata` owns
`Kongono\ApiMetadata\Api` and `ApiStatus` and requires PHP only. Kernel,
Diagnostics, Application Graph, and Dependency Injection depend directly on it;
the framework root consumes all fourteen packages and Graph retains its Diagnostics
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
`Kongono\DiagnosticHandler\`. It depends directly only on PHP 8.5 or later,
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

The Kernel owns boot and shutdown state, operation-scope identity and ownership, active-operation accounting, lifecycle misuse detection, its exception family, and six exact diagnostic-context schemas and values. API Metadata neutrally owns compatibility classification. The Application Graph package owns only the immutable structural snapshot, its local exception family, and its 11 diagnostic-context schemas and values. Dependency Injection separately owns explicit definitions, definition-graph validation, transient construction, and 25 diagnostic schemas across 26 throw sites. Diagnostics owns presentation-neutral bounded values and its construction-failure boundary. Diagnostic Activation owns the implemented typed trusted-bootstrap and adapter-controlled delivery permissions. Diagnostic Presentation owns the implemented pure trusted-developer projection core and pure HTML renderer but does not depend on Diagnostic Activation or own activation decisions. Diagnostic Handler is the only lower package depending on both and owns eligibility-first neutral sequencing. HTTP Message owns the selected server-side message and factory roles plus 28 bounded diagnostics. Routing owns bounded route declarations and deterministic matching; HTTP Server owns PSR-15 composition. Web Application owns application-call Kernel scope cleanup. Web Runtime owns outer request-independent evidence sequencing, fixed generic or eligible trusted response selection, and exact send/close attempts. PHP CGI owns bounded explicit inbound conversion. The root-owned Phase 2 fixture supplies the only concrete runnable integration and is test infrastructure, not production behavior. No production SAPI/server/listener/emitter, trusted network authorization mechanism, or supported deployment profile exists. The monorepo retains atomic root validation across all fourteen implemented packages.

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
