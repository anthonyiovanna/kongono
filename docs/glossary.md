# Kongono Glossary

## Hantei

The optional independent Kongono verifier implemented in Phase 4E. Hantei runs
one closed versioned quality profile against an exact unchanged Git-visible
subject and returns bounded derived `PASS`, `FAIL`, or `BLOCKED` evidence. It
cannot repair source and does not depend on Chie.

## Hantei Report

A non-authoritative canonical evidence object binding a verdict to exact profile,
subject, framework-artifact, source-set, tool, and ordered check identities.
These definitions establish architectural vocabulary, not public PHP APIs. Unless identified as locked direction, exact implementation semantics remain experimental or open.

## Action

An exact application-owned identifier for behavior that a later authorization
evaluation may be asked to permit. Phase 9B implements Experimental
`Kongono\IdentityAccess\Action` as a 1-128 byte canonical ASCII value matching
`^[a-z][a-z0-9]*(?:[._:-][a-z0-9]+)*$`. It is distinct from a route, HTTP
method, handler, command definition, role, and capability instance; it grants
nothing and performs no evaluation.

## Actionable Diagnostic

A package- or capability-owned explanation of one deliberate Kongono failure
that identifies what failed, why and where it failed, and what the developer can
do next. For current Kernel, Graph, and DI failures, the owning package marker
exposes the exact ID, title, remediation, and bounded context. Phase 0Q integrates
all 11 Graph failures, Phase 0T integrates all six Kernel lifecycle failures, and
Phase 0U integrates all 25 DI diagnostics across 26 throw sites. Diagnostics
construction failures remain context-free. Phase 0M itself added no renderer.

## Agent Experience / AX

The experience presented to AI agents through structured, authorized, machine-readable capabilities and context. AX is first-class alongside DX and UX, but no AX protocol or API is selected.

## Agent Principal

A Principal representing an AI agent that may act only within its identity, permissions, capabilities, constraints, and delegated authority. It is not merely an API client. Resolution and execution semantics remain open.

## Application Graph

Kongono's planned authoritative, machine-readable representation of an application, derived from a language-neutral IR. Phase 0D implements only its generic structural prerequisite; domain schemas, stable identities, serialization, storage, compilation, security metadata, and the complete authoritative representation remain experimental or open.

## Application Graph Structural Snapshot

The Phase 0D immutable in-memory model of nodes and directed relations. It validates exact structural invariants, provides canonical iteration and exact node lookup, and contains no domain payload, provenance, serialization, discovery, or compiler behavior. It is not yet the full authoritative Application Graph.

## API Status

Machine-readable compatibility and supported-use metadata attached to each production class-like type. Phase 0C defines Internal, Experimental, and Public; Phase 0S moves the unchanged bootstrap types atomically from the former `Kongono\Kernel\Api\` ownership to `Kongono\ApiMetadata\`. The old FQCNs are unavailable without a shim. A type's status applies to its public and protected members; it is not authorization, package visibility, an Application Graph node, or a security boundary.

## API Metadata Package

The implemented `kongono/api-metadata` package that neutrally owns
`Kongono\ApiMetadata\Api` and `ApiStatus` after the atomic Phase 0S pre-alpha migration.
It is a precise two-type compatibility-metadata boundary, not a runtime service,
generic metadata bag, Kernel capability, or authorization mechanism. It requires
PHP only and provides no registry, cache, I/O, diagnostic context, or transport.

## Application Profile

A planned coherent selection or configuration of Kongono capabilities for a class of applications or deployments. The profile model, names, composition rules, and packaging are not yet defined.

## Capability

A coherent unit of framework or application ability, such as HTTP, authorization, events, or workflows, with explicit contracts and dependencies. Capability discovery, metadata, packaging, and runtime semantics remain open.

## Component

An independently installable framework package or implementation unit that may provide one or more capabilities. `kongono/kernel` is the first physical package but is a lifecycle core, not authorization for any planned capability package.

## Canonical Graph Order

The deterministic Phase 0D iteration order independent of constructor input order. Nodes compare by binary Node Key value; relations compare by binary source-key value, Relation Kind value, then target-key value. No locale-aware comparison or Unicode normalization occurs.

## Consent

An actor's recorded authorization for a specified action or use of authority, subject to scope, constraints, lifecycle, and revocation. Consent semantics and evidence formats remain open.

## Delegation

A governed grant of authority from one Principal to another, constrained by capability, intent, consent, policy, time, scope, and revocation. Delegation chains, receipts, and enforcement formats are not yet specified.

## Dependency Injection

Phase 0F dependency injection means only construction from explicit immutable definitions: a canonical interface contract, an explicit concrete implementation, and an ordered list of interface dependencies. Phase 0U adds exact bounded failure context without changing definition or resolution behavior. It does not imply autowiring, PSR-11, a complete container, configuration injection, or a service-lifetime system.

## Dependency Resolver

The final readonly Phase 0F composition-root component that validates a complete definition set and recursively constructs explicit implementations. It retains definitions only, constructs a fresh object on every root and dependency edge, and cannot itself be defined or injected as a Kongono service.

## Developer Experience / DX

The experience of building, understanding, testing, operating, and evolving a Kongono application. Kongono aims for high productivity through explicit and inspectable mechanisms rather than hidden magic.

## Developer Diagnostic Owner

The implemented Phase 0X `DeveloperDiagnosticOwner` string-backed enum that identifies the
single contextual source family of a trusted developer projection. Its exact
tokens are `kernel`, `application_graph`, and `dependency_injection`. It is an
Experimental projection value, not a universal exception owner or authorization
decision.

## Diagnostic Identifier

A stable, case-sensitive semantic identifier using
`KGN-<DOMAIN>-<NNNN>` for one deliberate Kongono failure reason. It is not
`Exception::getCode()`, a Documentation ID, Application Graph identity,
operation/request/trace ID, incident reference, URL, database key, or transport
code. Current package exceptions emit their assigned identity through
`diagnosticId()`; titles and remediation are diagnostic prose rather than stable
machine identity.

## Diagnostic Activation

The implemented `kongono/diagnostic-activation` package, namespace
`Kongono\DiagnosticActivation\`, owns the two Experimental unbacked
`TrustedDeveloperBootstrapPermission` and
`AdapterControlledDeliveryPermission` values plus the Experimental final
readonly `TrustedDeveloperPresentationEligibility` pure conjunction. It depends
on API Metadata alone. It is not an authorization engine or security boundary
and implements no raw configuration, delivery evidence evaluation, adapter, or
presentation access.

## Diagnostic Presentation

The implemented lower-layer integration boundary `kongono/diagnostic-presentation`,
namespace `Kongono\DiagnosticPresentation\`. Phase 0X provides its trusted-
developer projection core, and Phase 0Z provides the first pure HTML renderer;
later presentation models and other renderers remain owned here but
unimplemented. It consumes all five originating packages, while only Diagnostic
Handler depends on it. Diagnostic Presentation does not depend on the separate
Diagnostic Activation or Handler packages. Diagnostics stays presentation-neutral.

## Diagnostic Handler

The implemented Phase 1H leaf integration boundary
`kongono/diagnostic-handler`, namespace `Kongono\DiagnosticHandler\`. It
sequences an already-created trusted-presentation eligibility value with the
existing projector and HTML renderer. It depends on API Metadata, Diagnostic
Activation, Diagnostic Presentation, Kernel, Application Graph, and Dependency
Injection, but not directly on Diagnostics; no package depends on it. It is the
only current package depending on both Diagnostic Activation and Diagnostic
Presentation.

## Trusted Diagnostic Handler

The implemented Experimental final readonly
`TrustedDeveloperDiagnosticHtmlPresenter`, the sole first production declaration
of Diagnostic Handler. It checks eligibility before accessing its contextual
Kernel, Graph, or Dependency Injection source. False returns `null` immediately;
true invokes the existing projector and renderer and returns the exact HTML
unchanged. It catches nothing, proves no authority, and owns no evidence,
authentication, non-trusted response, transport, or delivery. Direct use does
not prove authorization, and every failure propagates unchanged.

## `renderIfEligible`

The Trusted Diagnostic Handler's sole method. Its two inputs are one
`TrustedDeveloperPresentationEligibility` for the current delivery and one
source implementing exactly one of the Kernel, Application Graph, or Dependency
Injection exception markers. Passing the source argument is not source access.
The Experimental method is directly callable but is not authorization.

## Denied Trusted Presentation Result

The handler's `null` result when combined eligibility is false. It means
only that trusted developer HTML was not produced for that reason. It does not
mean handled, successful, retryable, empty output, fallback selected, incident
recorded, logged, or any particular response, status, model, or transport. Web
Runtime now owns the fixed generic non-trusted path for its delivery port. PHP
`cgi-fcgi` FastCGI `RESPONDER` is the selected production host profile, and
Phase 3F supplies its concrete delivery and thin entry; Phase 3H supplies
test-owned external proof for the selected synchronous profile. The harness is
not a production or supported server/client, and deployment hardening remains
outside Phase 3.

## Diagnostic Context

A Public final readonly presentation-neutral value containing operation and
violated-invariant prose plus zero or more schema-approved fields. It is
constructed eagerly in schema order with detached values and hard bounds. All 11
Graph, six Kernel, and 25 DI diagnostics return one same-object context through
their separate package markers; the 25 DI schemas cover 26 throw sites.

## Diagnostic Field

A Public final readonly context fact with a canonical key, fixed label, bounded
`text`, `integer`, or `boolean` value, fixed disclosure, and a truncation flag
only for text. It copies schema metadata and does not retain its schema object.

## Context Schema

The closed definition of fixed operation, violated invariant, allowed field keys
and labels, scalar kinds, disclosures, canonical order, and required/optional
status. `DiagnosticContextSchema` and `DiagnosticFieldSchema` implement structural
validation. Originating packages still own which schemas are semantically correct
for diagnostic identifiers. Graph implements its 11 exact catalog schemas,
Kernel its six lifecycle schemas, and DI its 25 schemas across 26 throw sites.

## Safe Disclosure

The Public `DiagnosticDisclosure::Safe` classification for a schema-approved
semantic category eligible for intentional disclosure after a separate
presentation or transport policy allows it. Safe never means automatically
public. The implemented trusted developer projection preserves Safe classification
exactly; it does not itself disclose, sanitize, or authorize a value. Phase 0Q
stores Safe Graph lengths, counts, positions, and broad kind
tokens; Phase 0T stores only closed Safe Kernel state names, counts, and the
conservative scope-condition token; Phase 0U stores bounded DI counts, positions,
and closed role/kind/mode/type/rejection tokens. None performs disclosure.

## Protected Disclosure

The Public `DiagnosticDisclosure::Protected` classification for diagnostic
information restricted to a trusted developer or protected operational boundary.
Protected values are excluded from untrusted production output by default.
Phase 0Q stores exact bounded Graph keys and kinds plus optional numeric
unexpected-key text. The trusted developer projection includes Protected fields
unchanged; both trusted bootstrap permission and adapter-controlled delivery
permission must be affirmative before presentation access, projection,
rendering, or delivery. The pure HTML renderer itself performs no activation
enforcement. Web Runtime enforces the supplied two-gate decision for response
selection. PHP CGI supplies the selected literal operator and exact
delivery-evidence evaluation; a general raw configuration system and production
disclosure policy remain unimplemented.

## Forbidden Capture

Secret-bearing, unbounded, uncertain, or otherwise prohibited material that
must not enter diagnostic context at all. It is not a third disclosure class;
Kongono does not detect or redact it. Graph deliberately omits an arbitrary
string key when it is the first non-list mismatch and never inspects later keys;
this is conservative omission, not sanitization. A future boundary may record
only that forbidden material was omitted.

## Trusted Developer Diagnostic Projection

The implemented Phase 0X pure, deterministic, immutable, and detached model of one
Kernel, Application Graph, or Dependency Injection contextual failure for a
trusted developer boundary. It contains only exact owner, identifier, title,
fixed operation and invariant, remediation, and canonically ordered bounded
fields with unchanged kind, Safe/Protected disclosure, and truncation state. It
contains no message, cause, trace, source, request, environment, transport, or
arbitrary metadata and is prohibited as a production model. The projector and
model types and the pure HTML renderer exist. Their direct callability is not
authorization. Those low-level APIs perform no activation enforcement; Web
Runtime separately enforces the supplied two-gate eligibility before selecting
trusted output.

## Trusted Bootstrap Permission

The Accepted Phase 1A operator-owned decision that Web Runtime holds immutably
for one adapter instance before it serves work. Phase 3E PHP CGI supplies the
decision only after exact literal operator-profile and native-prerequisite
validation.
Phase 1C implements its exact Experimental unbacked type as
`Kongono\DiagnosticActivation\TrustedDeveloperBootstrapPermission`, with
`Denied`, `Allowed`, and `allowsTrustedPresentation(): bool`. PHP CGI's
`Production` profile maps to `Denied`; `LoopbackDevelopment` maps to `Allowed`
only after fixed SAPI and INI validation. No raw configuration parser or reload
exists. Phase 3F composes this permission with a fresh concrete delivery through
an already-composed Web Runtime.

## Adapter-Controlled Delivery Permission

The Accepted Phase 1A decision, independent of trusted bootstrap permission,
that Web Runtime now obtains from `WebDeliveryInterface` for the current
delivery boundary. The delivery implementation makes it from server-controlled
transport ownership; client assertions cannot grant it,
uncertainty is denial, and one operation's decision cannot authorize another.
Phase 1D implements its neutral Experimental unbacked representation as
`Kongono\DiagnosticActivation\AdapterControlledDeliveryPermission`, with
`Denied`, `Allowed`, and `allowsTrustedPresentation(): bool`. Phase 3E PHP CGI
evaluates one explicit delivery snapshot and allows only exact `cgi-fcgi`,
CGI/1.1 RESPONDER, canonical direct loopback, and absence of all forwarded
markers. Phase 3F captures that bounded evidence for each delivery. The external
listener/process boundary and broader identity, authentication, and
authorization mechanisms remain unimplemented.

## Trusted Diagnostic Activation

The Accepted Phase 1A fail-closed rule that trusted bootstrap permission and
adapter-controlled delivery permission must both be affirmative before
presentation access, projection, copying Safe or Protected fields, rendering,
or delivery. Missing, invalid, ambiguous, failed, denied, or uncertain decisions
prohibit the trusted path. The architecture, both typed inputs, their pure AND
calculation, the Handler's denial-before-access sequencing, and Web Runtime's
neutral fail-closed response-selection/delivery composition are implemented.
PHP CGI's literal operator selector, fixed prerequisite validation, independent
host evidence evaluation, bounded response emitter, concrete delivery, and thin
entry composition are implemented. A general configuration reader, first-party
listener/process boundary and hardened served deployment remain unimplemented.
Phase 3H supplies root-owned external acceptance evidence only; no production
package depends on that harness. Direct construction or a true result is not
authorization.

## Trusted Developer HTML Diagnostic Page

The implemented Phase 0Z pure renderer in Diagnostic Presentation that consumes
only one implemented `TrustedDeveloperDiagnostic` and returns a complete,
accessible, deterministic, escaped HTML5 document with five fixed visible
regions and one exact local stylesheet. It performs no authorization,
projection, transport, or production presentation. The renderer and golden
fixtures are implemented, and Diagnostic Handler provides neutral eligibility-
first sequencing. The renderer and Handler themselves own no served route,
evidence evaluator, adapter, transport, delivery, response, or production
model. Web Runtime's separate neutral delivery sequence and PHP CGI trust-input
sources are implemented; concrete PHP CGI delivery is not.

## Trusted Developer Mode

Legacy shorthand for Trusted Diagnostic Activation, not a single switch or
environment label. Web Runtime now enforces explicitly supplied decisions;
Phase 1A requires both trusted
bootstrap permission and independent adapter-controlled delivery permission
before presentation access, projection, rendering, or delivery. Query, route,
path, body, header, cookie, session, form, user, database, provider, raw
environment string, heuristic, AI, ambient, or previous-operation state can
never activate or widen either decision. Calling the projector or renderer is
not authorization. The Phase 3E literal PHP CGI operator selector is not a
general configuration mechanism, and no concrete PHP CGI runtime delivery
exists.

## Documentation ID

A planned stable identifier connecting a documentation concept with source symbols, Application Graph nodes, IDEs, CLI tools, and AI tools. The exact syntax and mechanism are experimental; no identifier API or annotation exists.

## Event

A planned explicit record or notification that something relevant occurred. Delivery, durability, ordering, schema, transaction, and Graph semantics remain open.

## Error Boundary

A layer that owns meaningful recovery, semantic translation, cleanup, final
recording, or safe presentation for failures crossing it. Catching without such
behavior is not a boundary. No shared Kongono error-boundary API exists.

## Expected Application Outcome

An ordinary unsuccessful application decision, such as invalid user input,
authorization denial, business-rule rejection, or optimistic conflict. It is not
automatically an exception; focused typed outcome APIs remain capability-specific
and unselected.

## Extension

An independently supplied contribution that adds behavior or other capabilities through the governed Extension Platform without unnecessary source-level coupling. Extensions may eventually be core, trusted/in-process, sandboxed, or external; trust and packaging contracts remain open.

## Extension Host

The planned governance boundary for extension permissions, capabilities, lifecycle, storage, network access, secrets, events, UI contributions, agent capabilities, updates, removal, and health. It is not implemented, and its sandboxing or process model is not selected.

## Graph Exception Boundary

The Public `Kongono\ApplicationGraph\Exception\ExceptionInterface` marker implemented by failures deliberately thrown by the Application Graph package. It exposes ID, title, remediation, and one exact bounded `DiagnosticContext`. It is separate from Kernel, DI, and Diagnostics markers; native engine errors are not wrapped merely to enter it.

## Experimental API

A contract available to early adopters and evaluators only with explicit awareness of instability. It carries no backward-compatibility promise before promotion and may be extended only as its documentation permits. Experimental does not mean production-guaranteed.

## Human Principal

A Principal representing a human actor. Authentication, identity resolution, credential, session, and policy semantics are not yet defined.

## Human/User Experience / UX

The experience presented to people using a Kongono application. UX is one of three first-class consumption surfaces alongside Software/API Experience and Agent Experience.

## HTTP Message

A PSR-7 representation of an HTTP server request or response and its headers,
URI, and stateful body stream. Message `with*()` operations use copy-on-change
semantics, but a shared stream and mutable consumer values prevent a claim of
deep immutability. Phase 2C implements the bounded server-side roles.

## HTTP Message Package

The implemented ninth first-party package at `packages/http-message`, Composer name
`kongono/http-message`, and namespace `Kongono\Http\Message\`. It implements
the selected server-side PSR-7 and PSR-17 roles while depending only on PHP, API
Metadata, Diagnostics, and those two interface packages. It contains no router,
middleware, adapter, emitter, SAPI parser, or client HTTP implementation.

## HTTP Server Package

The implemented eleventh first-party package at `packages/http-server`, Composer
name `kongono/http-server`, and namespace `Kongono\Http\Server\`. It installs and
uses the PSR-15 handler and middleware interfaces for an explicit, bounded,
immutable, transport-free middleware pipeline and complete canonical route-name
terminal mapping. Selected matches use the reserved `RouteMatch::class`
attribute but remain untrusted and non-authorizing; unmatched output is
delegated. It provides no error middleware, bootstrap, Kernel web composition,
adapter, emission, or generic production response. The root-owned Phase 2
fixture composes it externally without becoming HTTP Server package behavior or
a production application.

## Web Application Package

The implemented twelfth first-party package at `packages/web-application`,
Composer name `kongono/web-application`, and namespace
`Kongono\Web\Application\`. It validates Routing and HTTP Server composition
before Kernel boot, exposes one PSR-15 handler, and owns one exact local Kernel
operation scope around each application call. Cleanup failure retires the
application, simultaneous work and cleanup failure preserves both objects, and
shutdown is explicit. It retains no request-specific state and provides no
outer adapter, response emission, generic production failure mapping, or trusted
diagnostic delivery. Web Runtime owns those outer package concerns, and the
root-owned Phase 2 fixture proves their composition without becoming package
behavior or a production application.

## Intent

A structured expression of the outcome a Principal seeks. Intent may inform authorization, delegation, auditing, or agent interaction, but its schema and enforcement role remain open.

## Incident Reference

An opaque future reference that may let an operator locate a protected failure
record without disclosing diagnostic detail. It is unique to an occurrence and
is distinct from a stable Diagnostic Identifier. Incident storage is not
implemented.

## Internal API

A type intended only for Kongono framework internals. External use and extension are unsupported, and no backward-compatibility promise applies unless a later Accepted contract says otherwise. Internal describes support, not PHP visibility.

## Intermediate Representation / IR

A planned language-neutral representation from which the Application Graph derives. PHP is the first source-language frontend, but the IR must not be permanently tied to PHP syntax. Its schema and compilation model are experimental.

## Kernel

The small, runtime-independent core whose Phase 0C implementation owns boot and shutdown state, operation-scope identity and completion, active-operation accounting, lifecycle misuse detection, and the Kernel exception family. Phase 0S completed neutral extraction of API-status metadata, so it is not a Kernel lifecycle responsibility. Phase 0T adds six exact lifecycle diagnostic contexts without adding state or scope metadata. Responsibilities beyond the implemented Experimental boundary remain unspecified.

## Kernel Exception Boundary

The Public `Kongono\Kernel\Exception\ExceptionInterface` marker implemented by every failure deliberately thrown by the Phase 0C Kernel package. Phase 0T adds one exact bounded `DiagnosticContext` accessor while preserving its separation from Graph, DI, and Diagnostics markers. Native engine errors are not wrapped merely to enter this boundary, and concrete lifecycle exception behavior remains Experimental.

## Kernel Lifecycle

The boot/operation/shutdown contract for one Kernel instance: boot once, begin and end overlapping operations while booted, refuse shutdown while operations remain, then shut down terminally. It provides no runtime adapter or ambient current operation.

## Kernel State

One of the Phase 0C lifecycle values `Unbooted`, `Booted`, or `Shutdown`. Shutdown is terminal; invalid transitions leave the current state unchanged.

## Module

An explicit application architecture boundary that groups related behavior and contracts. It is distinct from a Composer component or external extension. Module metadata, dependency rules, and runtime semantics remain open.

## Node

One immutable Phase 0D structural item containing exactly a Node Key and Node Kind. Its identity inside a snapshot is the exact key value, not PHP object identity; it contains no payload, metadata, provenance, or behavior.

## Node Key

A non-empty opaque string identifying one Node inside the snapshot and producer contract that supplied it. Its value is preserved exactly. It is graph-local, not a stable cross-build Application Graph ID, Documentation ID, database ID, URI, UUID, package coordinate, or PHP symbol.

## Node Kind

A non-empty, exactly preserved open label carried by a Node. It is not a built-in framework vocabulary, registry entry, or finalized domain schema.

## Operation Scope

An opaque object whose identity marks one operation owned by one booted Kernel. Scopes may overlap and must be ended explicitly through their owner. Phase 0C scopes contain no ID, metadata, context, callback, serialization, or self-ending behavior, and no global current-operation pointer exists.

## Persistent Runtime

A runtime in which the process and some resources may outlive one request, job, command, or other operation. Kongono must make operation scope, cleanup, and isolation explicit rather than assume process teardown.

## Phase 2

The completed bounded Phase 2A-H milestone whose checkpoint is
`647053d4bddc4c76090bca9bf3d693faa5880c52`. Exactly thirteen production
packages and a root-owned, test-owned subprocess fixture implement and prove the
first runnable server-side framework path. Phase 2 is not the full framework, a
1.0 release, supported production integration, or production readiness.

## Phase 3

The Accepted bounded A-H path from Phase 2's fixture-runnable checkpoint to one
supported synchronous production HTTP integration profile. Phase 3A establishes
the milestone and roadmap. Phase 3B selects PHP `cgi-fcgi`, CGI/1.1, and FastCGI
`RESPONDER`. Phase 3C and ADR 0053 implement bounded explicit inbound conversion.
Phase 3D and ADR 0054 implement fully staged bounded response emission. Phase
3E and ADR 0055 implement immutable operator bootstrap and independent current-
delivery permission evaluation. Phase 3F and ADR 0056 implement concrete
delivery and thin entry composition. Phase 3G and ADR 0057 implement the bounded
front-controller lifecycle and deployment constraints. Phase 3H and ADR 0058
complete test-owned external acceptance for the selected synchronous PHP-CGI
FastCGI RESPONDER profile. Phase 3 is complete and starts no next phase
automatically. It remains smaller than the full framework,
Stable API, 1.0, or production-hardening certification.

## PHP CGI FastCGI Responder Profile

The Accepted Phase 3B host architecture requiring PHP SAPI `cgi-fcgi`, CGI/1.1,
and FastCGI role `RESPONDER`. Phase 3C accepts a caller-supplied closed bounded
server projection and caller-owned input stream without reading ambient process
state. Phase 3D stages bounded output before SAPI commitment, treats post-commit
failure as committed or uncertain without rollback or retry. Phase 3E validates
fixed process prerequisites and derives independent bootstrap/current-delivery
permissions, and leaves
listener/process ownership to the host. The Phase 3F delivery calls conversion
from `request()`, so malformed input maps to Web Runtime's generic 500.

## PHP CGI Package

The implemented outer leaf at `packages/php-cgi`, Composer name
`kongono/php-cgi`, and namespace `Kongono\PhpCgi\`. Its direct dependencies are
PHP, API Metadata, Diagnostic Activation, Diagnostics, HTTP Message, and PSR-7. It converts explicit
caller values into a first-party request and emits fully staged bounded
responses. It also owns literal typed operator bootstrap, independent exact
current-delivery permission evaluation, the Internal concrete delivery, and the
Experimental thin entry into Web Runtime. It supplies no listener or deployment.
Streaming is unsupported.

## PSR-7

The PHP-FIG HTTP message interfaces supplied by `psr/http-message`. Kongono
accepts the 2.x interface line for future server request, response, URI, stream,
and uploaded-file roles. Phase 2C supplies Kongono's bounded implementation and does not
own Kongono architecture.

## PSR-15

The PHP-FIG server request-handler and middleware interfaces. Kongono installs
and uses their 1.x interface packages for Phase 2E terminal handling and ordered
middleware in `kongono/http-server`. PSR-15 supplies interoperability contracts,
not bootstrap, adapter, transport, emission, or a framework substrate.

## PSR-17

The PHP-FIG factories for PSR-7 objects. Kongono uses the 1.1 interface line
for exactly the server-request, response, URI, stream, and uploaded-file
factory roles. The client `RequestFactoryInterface` remains outside Phase 2,
and Phase 2C supplies the five selected stateless first-party factories.

## Principal

An immutable exact reference to an identity known in an application-owned
domain. Phase 9B implements Experimental `PrincipalReference` as one of the
exact Human, Service, Organization, System, or Agent `PrincipalKind` values plus
one opaque 1-512 byte valid-UTF-8 `PrincipalId` without C0/C1 controls. It is not
the user record, provider object, credential, display name, role, tenant, actor,
subject, or permission. Kind and reference grant nothing by themselves.

## Public API

A supported consumer contract governed by Kongono's versioning policy and release maturity. Public does not mean Stable or unchangeable forever, particularly before 1.0; extension is permitted only as the specific contract documents.

## Relation

One immutable directed Phase 0D connection whose structure is exactly source Node Key, Relation Kind, and target Node Key. Self-relations and cycles are valid; exact duplicate triples are not.

## Relation Kind

A non-empty, exactly preserved open label carried by a Relation. It establishes no built-in dependency, call, event, module, or security vocabulary.

## Runnable Fixture Application

The implemented root-owned, test-owned Phase 2 subprocess composition that
proves the complete web path through explicit bootstrap, first-party request
construction, Kernel operation ownership, deterministic routing, ordered
middleware, terminal handling, response delivery, generic failure behavior,
fail-closed trusted diagnostics, cleanup, and sequential-operation isolation.
It is not a production application, supported template, framework CLI, SAPI
adapter, server, listener, emitter, or deployment entrypoint.

## Service Principal

A Principal representing a software service or machine workload. Credential, identity, trust, rotation, and authorization semantics remain open.

## Service Contract

The exact canonical declared fully qualified name of an existing PHP interface used as a Phase 0F service identity. Classes, aliases, case variants, leading-backslash names, and the Kongono resolver contract or its subinterfaces are not service contracts.

## Route Definition

An immutable Experimental Phase 2D declaration with one canonical route name,
one bounded list of exact uppercase HTTP methods, and one bounded path template.
Literal segments and whole-segment `{parameter}` forms are implemented; optional,
wildcard, regex, host, query, handler, middleware, and generation syntax are not.

## Route Match

A fresh immutable Experimental Phase 2D result containing the selected route
definition and exact encoded, untrusted path parameters in template order. A
match proves neither authentication nor authorization and invokes no handler.
Valid unmatched requests return `null` instead.

## Route Matcher

The immutable stateless Phase 2D component that reads only a PSR-7 request method
and URI path, validates bounded encoded syntax, and selects a conflict-free route
by lexicographic literal/parameter specificity independent of declaration order.
It performs no decoding, normalization, I/O, ambient current-route storage, DI,
Graph, Kernel, middleware, or handler work. Phase 2E separately composes its
matches with explicit PSR-15 handlers, and Phase 2F wraps that composition in
the Web Application Kernel-scope boundary. Phase 2G is implemented, and Phase
2H's root-owned fixture proves the complete path externally.

## Service Definition

One immutable Phase 0F mapping from a Service Contract to a canonical concrete implementation and an ordered list of Service Contract dependencies. Constructor reflection validates the explicit list but does not discover dependencies.

## Service Scope

A planned lifetime and ownership boundary for service instances. Phase 0F selects only transient-per-edge construction and implements no shared, singleton, request, operation, worker, process, tenant, or custom scope.

## Structured CLI

The Accepted Phase 8A architecture plus the implemented Phase 8B neutral
contracts, Phase 8C deterministic parser/evidence boundary, Phase 8D
operation-scoped execution boundary, Phase 8E deterministic presentation
boundary, Phase 8F read-only capability family, and Phase 8G supported
application/process edge for a first-party developer and operator edge. The
official executable is exactly `kongo`; no alias
is accepted. Command meaning, one explicit invocation and neutral result, and
human or versioned machine presentation plus process adaptation remain
separate. Definitions must become fail-closed deterministic build evidence,
interaction and mutation authority are explicit, and every invocation owns
bounded cleanup and same-process isolation. Phase 8C implements immutable
specifications and already-tokenized parsing in `kongono/console`, plus
canonical exact-current definition evidence in `kongono/console-build`. Phase
8D implements immutable explicit handler binding and one fresh operation per
dispatch in `kongono/console-runtime`, with explicit cancellation, reverse
cleanup, combined failure preservation, and sequential isolation. Phase 8E
adds bounded result records plus deterministic human and canonical version-1
machine output in `kongono/console-presentation`. Phase 8F adds the exact
`graph:inspect`, `composition:inspect`, and `quality:inspect` handlers in
`kongono/console-commands`, with generated Composition wiring and no mutation
authority. Phase 8G adds `kongono/console-application` and exactly
`application:build`: non-mutating `--plan`, explicit atomic/no-clobber
`--apply`, no prompt, automation-safe `--no-interaction`, and shared
human/machine result and exit behavior. Phase 8F handlers remain programmatic
without fabricated snapshots. Phase 8H adds root-owned external process
acceptance and the exact checkpoint without changing production behavior. A
conflicting output is a no-clobber Precondition at exit 3; Infrastructure
remains exit 6. See the [Structured CLI guide](architecture/structured-cli.md),
[external acceptance guide](architecture/phase-8-external-acceptance.md), and
[Phase 8 roadmap](architecture/phase-8-roadmap.md).

## Command Meaning

The immutable, bounded Phase 8B definition of one command identifier, summary,
interaction policy, and mutation policy. Phase 8C combines it with explicit
input definitions in a `CommandSpecification`; one immutable ordinal
`CommandSpecificationSet` is the complete parser input. It remains separate
from execution and presentation and owns no handler, mutable registry, alias,
service binding, or discovery rule.

## Command Invocation

The Phase 8B detached value containing one definition, ordered already-normalized
named inputs, explicit execution intent, and explicit interaction mode. A later
Phase 8D operation executes command behavior through typed collaborators and produces one
presentation-neutral result. The invocation reads no
ambient process, stream, environment, directory, terminal, signal, clock,
randomness, credential, or registry state and owns bounded cleanup before
completion. Phase 8C's pure parser constructs this value from explicit tokens
and a separate sensitive-input carrier. Phase 8D dispatches it without
presentation or process adaptation.

## Command Execution Operation

The fresh Phase 8D concrete lifecycle owner created for one bound dispatch. It
retains the exact invocation and explicit cancellation collaborator only while
Active and accepts at most 64 cleanup collaborators unique by object identity.
Retirement becomes observable and detaches operation-owned references before
attempting every cleanup once in reverse order. Repeated retirement is a no-op.

## Command Definition Artifact

The Phase 8C canonical JSON evidence compiled by `kongono/console-build` from
one explicit application-owned `kongono.commands.json` version-1 source.
Commands and inputs use ordinal order, the model is rehydrated through Console
invariant owners, and exact-current validation rebuilds and compares the entire
artifact. The artifact is derived evidence, not runtime authority; Console
Runtime does not load it. No publisher, loader, mutable registry, presenter, or
executable exists.

## Neutral Command Result

The implemented presentation-independent Phase 8B outcome preserved by Phase
8D execution. It reports succeeded, rejected, failed, or cancelled and retains
only bounded neutral failure evidence. Phase 8E adds one bounded record of
ordinal Safe or Protected scalar fields. Human and machine renderers derive
from it without granting stream, process-exit, disclosure, or execution
authority; Protected values are omitted and only their count is disclosed.

## Command Presentation

The immutable Phase 8E stdout-or-stderr document with one exact media type and
shared exit status. Human and canonical version-1 machine forms derive from the
same neutral result or immutable help definition projection. A caller-owned
output port receives the exact completed bytes once; the delivery adapter owns
no native stream and is not the future process adapter or `kongo` executable.

## Transient Resolution

The Phase 0F rule that every top-level resolve call and every occurrence of a dependency creates a fresh implementation object. Duplicate dependencies and diamond branches never share lower-level instances, and no constructed service is retained by the resolver.

## Trust

The evaluated confidence and policy relationship under which a Principal, extension, credential, or system may act. Trust is not a substitute for explicit authorization. Its sources, scoring or categorization, and enforcement semantics remain open.

## Web Runtime Adapter

The implemented outer integration boundary that owns current-delivery evidence,
one Web Application call, fixed generic or eligible trusted response selection,
one send attempt, and one close attempt without becoming a Kernel dependency.
Web Application owns the sole Kernel operation scope. Production SAPI conversion,
parsing, proxy trust, response emission, and server selection remain outside the
package.

## Workflow

A planned coordination of multiple actions or steps, potentially durable and long-running, with explicit state and failure behavior. Definition, persistence, replay, compensation, and Graph contracts remain open.

## Web Runtime

The implemented Phase 2G top-level leaf that coordinates one `WebDeliveryInterface`
with one `WebApplication` call, fixed generic or eligible trusted failure output,
one send attempt, and one close attempt. It owns no Kernel scope and provides no
production SAPI/server/listener/emitter. Phase 2H's concrete implementation is
root-owned fixture code only. Stability remains Experimental except the Public
delivery and exception markers.

## Runnable Fixture

The bounded test-owned Phase 2 subprocess composition at
`tests/BlackBox/Fixture/run.php`. It accepts only fixed modes and scenarios,
exercises the real thirteen-package web path, emits detached newline-delimited
JSON, and proves exact cleanup and sequential isolation. It is not a production
server, framework CLI, supported template, full framework, 1.0 release, or
production-readiness claim.

## CGI Web Delivery

The Internal Phase 3F `CgiWebDelivery` implementation of Web Runtime's Public
delivery port. One fresh instance owns one closed projected CGI snapshot, lazy
native input, one request-conversion attempt, one response-emission attempt, and
explicit cleanup. It is not a listener, server, process manager, deployment
solution, or general SAPI adapter.

## CGI Web Entry

The Experimental Phase 3F `CgiWebEntry` composition leaf. It creates one fresh
CGI Web Delivery and passes it once to an already-composed `WebRuntimeAdapter`.
It does not construct Web Application, select permissions, recover failures,
emit independently, or own application shutdown.

## CGI Front Controller

The stateless Experimental Phase 3G `CgiFrontController`. It attempts the CGI
Web Entry once, then invokes one caller-supplied `Closure(): void` for the paired
already-booted application's explicit shutdown. The consumer owns correct
runtime/closure pairing, application construction, and partial-boot cleanup.
PHP CGI gains no direct Web Application edge.

## CGI Entry and Shutdown Failure

The Experimental Phase 3G `CgiEntryShutdownException` used only when the entry
and shutdown both fail. It preserves the entry failure as primary/native
previous and the shutdown failure separately. Diagnostic 1019 has only a fixed
Safe token; throwable accessors are developer-inspection surfaces. There is no
retry or second response. Uncertain emission or shutdown requires external
process retirement. The selected-profile constraints have root-owned external
acceptance proof without a production listener, server configuration,
deployment artifact, supported server/client, or production package dependency.
`Production` trust remains denied. Process, proxy, TLS, credentials, logs,
monitoring, incidents, and deployment ownership remain external. Phase 3 is
complete, is not Stable API, 1.0, universal SAPI support, deployment automation,
or production-hardening certification, and starts no next phase automatically.

## Phase 4

The fixed A-H milestone beginning after Phase 3 checkpoint
`6aacafc92f07b567818173d660e2e182dfa83d23`. Phase 4 establishes a future
versioned, inspectable, AI-independent knowledge foundation and separated Chie,
Hantei, and Oshie roles. Phase 4A is Accepted documentation architecture only;
no role, knowledge artifact, package, command, provider, patch proposal, or
runtime behavior is implemented. Phase 4 is smaller than the full framework,
Stable API, 1.0, auto-fix, autonomous operations, or production AI.

## Developer Intelligence

The optional planned family of developer-facing roles that explains, verifies,
and teaches from one future version-matched deterministic knowledge foundation.
It does not replace ordinary offline framework operation and is distinct from
an application Agent Principal.

## Knowledge Foundation

The planned versioned, inspectable, AI-independent source and provenance
foundation shared by Chie, Hantei, and Oshie. Phase 4A selects no package,
schema, format, identifier, index, retrieval engine, model, provider, command,
or persistence mechanism. Stale, mixed, missing, or unverifiable knowledge must
fail visibly.

## Kongono Chie

The Experimental developer companion with the canonical promise **Chie helps
you build it.** Chie deterministically explains and diagnoses one exact current
artifact and may propose one reviewable documentation candidate. It cannot
apply or certify its own work and is not an application Agent Principal.

## Kongono Hantei

The Experimental independent verifier with the canonical promise **Hantei
proves it works.** Hantei selects and interprets one closed deterministic
evidence profile and returns `PASS`, `FAIL`, or `BLOCKED`; it cannot repair the
subject it judges and does not replace accepted quality gates.

## Kongono Oshie

The Experimental teaching role with the canonical promise **Oshie helps you
understand it.** Oshie teaches a closed curriculum from exact version-matched
accepted sources and deterministic exact-answer exercises. Its curriculum has
no executable example. Teaching is derived material, not repository truth.

## Rolling Current-Minor Baseline

The Accepted policy that the actively developed line supports exactly one
compatibility-certified stable PHP minor at a time. PHP 8.5 is the current
line, and Phase 5B applies `>=8.5.0 <8.6.0` to the root and all eighteen
packages. PHP 8.5.9 on Windows is the environment proved by this execution;
stable eligible 8.5 patches remain within range. PHP 8.6, PHP 9, prerelease,
nightly, and automatic future-minor support are not claimed. The migration
changes installability metadata only and adds no runtime semantics, package,
dependency, public API, polyfill, shim, release/LTS promise, or composition/
build capability. Phase 5A remains accepted, Phase 5B is accepted, and this
bounded PHP constraint is current.

## Compatibility Gate

The deterministic evidence boundary required before Kongono adopts another PHP
minor. It includes official migration/RFC review, zero Kongono-owned
deprecations, dependency and platform proof, strict optimized autoloading,
style and static analysis, complete tests and external acceptance, persistent-
runtime cleanup, supported-host proof, runtime-change review, measured
performance/memory evidence, and explicit release communication.

## Candidate Patch

A generated or human-authored proposed change that is not accepted repository
truth. A future Phase 4G Chie proposal must preserve explicit authority, exact
diff, provenance, practical reversal, human-controlled application, and fresh
independent Hantei proof. Phase 4A implements no patch proposal or application
behavior.

## Knowledge Source

A specifically included input used to support or derive a knowledge claim. Its
path or format does not establish authority; each use declares one semantic
Source Authority Class and exact Source State.

## Source Authority Class

One of the six Phase 4B semantic roles: Governing Authority, Implementation
Evidence, Current Explanatory Documentation, Historical Evidence, Workspace
Observation, or Derived or Advisory Material. These are architecture concepts,
not implemented enum values or schema rows.

## Source State

The exact immutable identity of every semantically included input and contract
needed to version-match a claim, including dirty tracked and untracked bytes
when applicable. A branch, mutable tag, path, timestamp, or compatible version
alone is insufficient.

## Provenance

Bounded, non-secret evidence connecting a claim to its exact source state,
source authority, location, derivation, disclosure decisions, validation scope,
and conflicts or uncertainty. Provenance does not copy forbidden material to
prove exclusion.

## Knowledge Claim

An assertion classified as an Accepted Framework Fact, Deterministic
Observation, Inference, Recommendation, Generated Candidate Content, or
Unresolved Uncertainty or Conflict. The six classes are semantic architecture,
not an implemented API.

## Knowledge Conflict

An unresolved disagreement among applicable sources. It remains visible and
invalidates the affected claim unless explicit accepted supersession or another
accepted deterministic rule resolves it; recency and model confidence are not
tie-breakers.

## Knowledge Artifact

A future deterministically derived, versioned representation of validated
knowledge. Phase 4B defines its governing semantics but implements no artifact,
format, generator, validator, or location.

## Framework Knowledge Set

The future versioned view derived from one exact Kongono framework source
state. It remains official framework knowledge and cannot be rewritten by an
application workspace.

## Workspace Evidence Overlay

A future explicitly authorized, project-scoped observation layer that
references one exact Framework Knowledge Set. It cannot become framework truth
or contaminate another workspace, user, organization, tenant, or source state.

## Framework Knowledge Artifact

The implemented Phase 4C canonical JSON snapshot of the exact closed 31-source
`phase4-foundation` inventory. It embeds source bytes, authority labels, exact
identities, and whole-source citations. It is derived evidence rather than
authority, is never checked in, and is rejected as stale when current complete
regeneration differs.

## Framework Knowledge Source Reference

An exact `kgn-source-v1:<source-set-sha256>:<four-digit-ordinal>` reference to
one whole embedded source in one exact Framework Knowledge Artifact. It is not
a line, symbol, fragment, fuzzy, semantic, ranked, cross-version, or workspace
reference.

## Kongono Chie

The developer companion whose promise is **Chie helps you build it.** Phase 4D
implements only deterministic offline read-only explanation for six closed
topics and bounded literal observation of one supplied Safe diagnostic
identifier. It has no free-form prompt, recommendation, patch, verification,
arbitrary workspace, model, network, Git, deployment, or production authority.

## Chie Citation

A Phase 4D binding from one exact claim to a version-matched artifact source
reference, relative source path, authority class, source SHA-256, and inclusive
one-based line range. It is derived provenance, not independent authority or
proof of correctness.

## Diagnostic Observation

One syntactically Safe diagnostic identifier explicitly supplied to Chie for
bounded literal lookup in the already validated framework artifact. It does not
prove runtime occurrence and does not authorize reading messages, traces,
context, logs, environment values, source files, or arbitrary workspace data.

## Oshie curriculum

The closed package-owned six-record Phase 4F teaching profile. It maps fixed lesson and exercise identifiers to exact accepted source sections, ordered options, and deterministic accepted answers. Curriculum values are derived teaching and cannot establish framework truth.

## Oshie attempt

One stateless exact-answer result containing a correct or incorrect outcome, the package-owned accepted answer, fixed feedback, and exact citations. It never contains the submitted answer.
**Chie patch proposal**
: A deterministic Phase 4G review candidate for one exact complete-line change
  in current explanatory documentation. It is derived, unapplied, unverified,
  reversible, provenance-bound, and pending human application plus fresh Hantei
  verification; it is not accepted authority or a guaranteed fix.

**Phase 4 external acceptance**
: The root-owned, test-owned Phase 4H proof of exactly 28 ordered scenarios
  across the real local Knowledge, Chie, Oshie, and Hantei boundaries. It binds
  successful evidence to one current artifact/source set, distinguishes real
  Hantei PASS from synthetic evaluator FAIL/BLOCKED evidence, proves offline and
  failure behavior, and grants no package, production, mutation, Git, network,
  deployment, or Phase 5 authority.

## Application Composition Root

The Accepted future supported boundary that assembles reviewed application
declarations and existing first-party capabilities. It owns construction,
startup order, retained application-lifetime objects, operation factories,
shutdown order, and partial-boot cleanup. It is not Kernel and must not expose a
resolver or container to application services. Phase 5A implements no root API.

## Composition Build

The planned explicit deterministic build/check step that validates composition
inputs, dependency structure, roots, lifetimes, routes, cleanup, and provenance
before runtime and produces one complete inspectable artifact. It never runs on
the first request. Phase 5A selects no syntax, command, schema, or artifact
format.

## Generated Typed Wiring

The Accepted preferred future production mechanism: readable ordinary PHP with
explicit constructor calls and dependency edges generated from canonical
reviewed inputs. It is statically analyzable derived evidence, not authority,
and removes dynamic resolution and discovery from the request path only after a
successful current build.

## Composition Source

The Accepted singular inert application-owned file at
`<application-root>/kongono.composition.json`. It is authoritative application
code in data form, not generated state or framework authority. The Phase 5D
build package consumes it before runtime; no runtime reader or generated wiring
exists.

## Composition Artifact

The implemented Phase 5D compact canonical JSON evidence that binds the exact
raw source, project files, symbol source set, closed builder implementation,
and canonical semantic model. Raw-source and model identities are separate;
current validation requires an exact byte-identical rebuild. Publication is
content-addressed and no-clobber. The artifact is not runtime service access,
generated PHP, a cache pointer, or authority.

## Composition Schema Version

The exact integer selecting composition-source semantics. Phase 5C accepts only
version `1` with schema name `"kongono.composition"`; there is no implicit
upgrade, downgrade, compatibility guess, or unknown-member tolerance.

## Composition Input

A version-1 declaration containing exactly one canonical class/interface
`contract`. It represents a typed object supplied explicitly by the future
application host. Its runtime value, credentials, handles, and environment-
derived data never appear in the source or generated artifact.

## Service Declaration

A version-1 object with exactly canonical concrete `class`, one `lifetime`, an
authoritative ordered `arguments` list, and `cleanup` as null or an exact method
name. It is an Accepted data contract, not a current runtime registration.

## Binding Declaration

A version-1 single-hop mapping with exactly canonical `contract` and one
declared concrete `service`. Each contract has zero or one binding; chains,
aliases, contextual selection, priorities, fallbacks, wildcards, and automatic
multi-binding are prohibited.

## Application Root

A version-1 root declaration whose kind is `"application"` and whose canonical
contract resolves to an application-lifetime service. It is a future typed
entry point, never a general runtime lookup.

## Operation Root

A version-1 root declaration whose kind is `"operation"` and whose canonical
contract resolves to an operation-lifetime service. It begins one isolated
future operation boundary and is not shared across operations.

## Cleanup Declaration

The optional exact method name on a service declaration. When non-null, it must
identify a public, non-static, zero-argument instance method returning `void`;
future owners call it at most once in reverse successful-construction order.

## Canonical Composition Model

The detached semantic model formed from exact declared strings, ordinal
case-sensitive ordering for non-semantic collections, and preserved constructor
argument order. Explicit typed aggregate members are separate ordered service
edges. Phase 5D implements this model; Phase 5E removes the superseded service-
list form.

## Typed Aggregate Service

An ordinary application-owned concrete service whose constructor names each
plural collaborator with a separate typed parameter and `service` edge. Its
parameter order is member order. Kongono supplies no aggregate base type,
collection injection, automatic multibinding, tags, or priorities.

## Composition Provenance

The bounded future evidence binding schema/version, raw-source and canonical-
model SHA-256 identities, root Composer manifest/lock identities, the ordinal
declaring-source manifest, builder package/version, and generated-artifact
identity. It excludes absolute paths, usernames, timestamps, randomness,
environment values, credentials, request data, and Protected data.

## Application Lifetime

The Accepted Phase 5C lifetime of one future instance per successfully composed
application, shared across operations and cleaned during shutdown. Its runtime
API remains unimplemented, and application objects retain responsibility for
documented concurrency safety and absence of operation-specific state.

## Operation Lifetime

The Accepted Phase 5C lifetime of at most one future instance per declared
service in one operation. It is never shared across operations and is cleaned
when that operation ends. Exact runtime factory/API remains unimplemented.

## Transient Lifetime

The Accepted Phase 5C lifetime in which every explicit future construction edge
receives a fresh instance owned and cleaned by the active application or
operation boundary. The current resolver independently implements transient-
per-edge construction; Phase 5C adds no generated-wiring runtime API.

## Stale Build Artifact

A composition artifact whose bound input state no longer matches the accepted
current source state. Production startup must reject it before accepting work;
it cannot be silently reused, repaired, or replaced by request-time discovery.

## Runtime Service Location

Fetching a dependency during runtime from a general resolver, container,
registry, or ambient lookup by string, class name, or equivalent key. The
supported production path rejects it, including resolver/container injection
and property-hook dependency fetching. Explicit typed generated factories are a
different possible future mechanism and require separately accepted lifecycle
semantics.

## Application Build Workflow

The Experimental reusable Phase 5G boundary that builds or non-mutatingly
checks the exact current content-addressed Composition artifact and generated
PHP pair. It derives exact filenames without scanning and owns no mutable
current/latest pointer or automatic cleanup.

## Copy-once Application Skeleton

The Kongono-owned `skeletons/minimal/` template before copying. After copying,
all non-generated files are application-owned; Kongono does not automatically
upgrade, merge, overwrite, migrate, or remotely update them. `vendor/` and the
content-addressed build files are derived and replaceable.

## Phase 5 External Acceptance

The root-owned, test-only 28-scenario proof for the supported pre-1.0
application-composition slice. It installs an exact copied skeleton against
ordinary mirrored local packages with plugins and network disabled, exercises
real build/check/run and PHP-CGI boundaries, proves fail-closed state handling,
isolation, nondisclosure, and cleanup, and emits deterministic canonical JSON.
It is not a production API, installer, listener, deployment certification, or
Phase 6 capability.

## Phase 6 External Acceptance

The root-owned test-only whole-platform proof for the accepted eight-package
Deterministic Data Platform. It starts an owned local PostgreSQL 17 cluster,
exercises explicit session/transaction/SQL/migration/mapping/generated-
application boundaries and a copied data-minimal workflow, emits one bounded
canonical result after cleanup, and adds no production provider service,
deployment behavior, ORM, or runtime telemetry.

## Database Performance Baseline

The versioned local observational timing evidence emitted by Phase 6 external
acceptance. Version 1 uses `hrtime(true)`, fixed warmup and sample counts, and
nearest-rank summaries for four ordered scenarios. Its values are neither exact
build identities nor SLAs, budgets, regression thresholds, portability claims,
or framework rankings; compatible profiles must be compared cautiously and
material environment changes require re-baselining.

## Deterministic Persistence

The optional Phase 7 capability above the completed Data Platform that provides
the developer-facing role commonly called an ORM through compile-time metadata,
ordinary generated PHP evidence, explicit I/O, and operation-scoped state.
Phase 7A accepts the architecture. Phase 7B implements the Experimental neutral
contract package. Phase 7C implements the Experimental persistence build source,
detached model, and provenance artifact. Phase 7D implements deterministic
ordinary PHP state-adapter evidence with structural/current validation and
guarded publication. Phase 7E implements one single-entity operation context
through explicit generic adapter and change-executor capabilities, without
loading generated PHP or owning transaction authority; direct SQL and Data
Mapper paths remain first-class.

## Generated Persistence State Artifact

The Phase 7D canonical ordinary PHP evidence emitted from one exact-current
Phase 7C artifact. Its content-addressed namespace contains one final adapter per
entity for public hydration/extraction, typed identity, snapshots, and neutral
change records. Structural validation never executes the candidate; exact-
current validation separately regenerates and compares exact bytes. It is not a
runtime loader, context, identity map, unit of work, repository, or database
authority.

## Persistence Build Artifact

The Phase 7C canonical JSON evidence compiled by `kongono/persistence-build`
from one strict application-owned persistence source, exact current mapping
evidence, typed domain construction/accessors, root Composer identities, and
builder identities. Structural validation proves canonical internal consistency;
exact-current validation separately rebuilds every explicit input. The artifact
is derived evidence, not runtime authority, generated PHP, or a usable ORM.

## Persistence Context

An operation-scoped contract for observing entity identity/state and changes
and explicitly retiring owned references. Phase 7E's concrete
`OperationPersistenceContext` owns exactly one entity, snapshot, identity, and
state, and executes at most one reviewed change per explicit flush. It cannot be
static, application-shared, process-global, reused after retirement, or treated
as session/transaction authority. It loads no generated PHP and provides no
relationship, cascade, repository, or multi-entity unit of work.

## Identity Map

An operation/context-local association between an explicit persistent identity
and one in-memory domain object. Phase 7H externally proves reuse within one
operation and isolation across overlapping operations. Its guarantee ends with
that operation and grants no process cache, authorization, ownership, or
distributed consistency.

## Change Set

A deterministic ordered list of Phase 7B immutable `PersistenceChange` records.
The neutral record exposes an entity reference, change kind, optional identity,
field names without values, and optional expected concurrency token. It performs
no automatic diff or I/O and carries no snapshot, SQL, parameters, relationship
contents, executor, or transaction authority.

## Explicit Flush

The caller-invoked Phase 7E boundary that reviews and attempts at most one known
persistence change with an explicit result. No destructor, response completion,
shutdown, property access, or arbitrary method call flushes automatically. The
caller retains transaction authority, and uncertain executor or synchronization
failure retires the context.

## Load Plan

The bounded Phase 7F declaration of aggregate and relationship data a named
generated repository method intends to load, including visible ordering and I/O
breadth. It rejects lazy proxies, property-hook queries, hidden N+1 behavior,
implicit joins, and unlimited graph traversal.

## Aggregate Boundary

An application-owned domain consistency boundary that constrains a future named
load or write operation. Relationship ownership, cascades, destructive scope,
and multi-aggregate work require explicit later contracts; an aggregate boundary
does not authorize recursive graph persistence.

## Optimistic Concurrency

An explicit write-time check that detects when an accepted concurrency token no
longer matches. Phase 7E preserves conflict as a distinct zero-applied outcome,
and Phase 7H proves it against the real provider without retry or disclosure of
identity or token values. The caller still owns token policy and transactions.

## Named Aggregate Load Plan

Phase 7F runtime evidence for one concrete generated repository method. It names
the aggregate/root and carries an ordered list of relationship targets,
cardinality, nullability, ordering evidence, per-relationship bounds, and one
exact total bound. It is inert and grants no database or transaction authority.

## Loaded Aggregate

Explicit Phase 7F evidence containing one root object and an ordinal map of
bounded relationship values. It is not a proxy, identity map, transparent
collection, or mutated domain graph. Generated repositories validate its exact
declared shape; the caller releases it at the operation boundary.

## Authentication

The deterministic evaluation of explicit detached evidence through one
explicitly registered verifier to establish bounded facts about an identity
claim. Phase 9B supplies the inert outcome vocabulary. Phase 9C implements
provider-neutral orchestration with exact mechanism selection, verifier-attempt
cleanup, failure composition, explicit strength/freshness requirements, and no
hidden fallback. Authentication success is evidence, not authorization, and
grants no access or Security Context.

## Authentication Evidence

An adapter-owned already-detached object offered to one authentication request.
Phase 9C's Experimental `AuthenticationEvidence` interface exposes only its
exact `AuthenticationMechanism`. It exposes no payload, credential, secret,
request, header, cookie, password, token, provider claim, serialization,
logging, debug, erasure, or network method. Zero evidence means Absent; more
than one means Ambiguous. Evidence is not a Principal, Security Context,
session, verification, or authorization decision.

## Authentication Mechanism

Phase 9C's Experimental canonical 1-64 byte ASCII identifier matching
`^[a-z][a-z0-9]*(?:[._:-][a-z0-9]+)*$`. It selects one explicitly registered
verifier by exact equality. It does not select a provider protocol, credential
format, strength meaning, session, policy, or permission.

## Authentication Facts

Phase 9C's Experimental immutable validated tuple containing exactly one
`PrincipalReference`, mechanism, unnamed ordinal strength, authentication
instant, and validity-until instant. Validity cannot precede authentication.
Facts retain no evidence, credential, provider object, issuer, audience, role,
policy, session, reason, or authorization. They remain evidence rather than
permission and do not construct a Security Context.

## Authentication Requirement

Phase 9C's Experimental immutable pair of minimum `AuthenticationStrength` and
maximum age from zero through 31,536,000 seconds. The caller separately supplies
explicit current time. Equality at minimum strength and maximum age satisfies
the requirement. A requirement is not a policy, role, session, verifier,
principal, or authorization decision.

## Authentication Verification

Phase 9C's Experimental immutable result pairing one existing
`AuthenticationOutcome` with `AuthenticationFacts` only for Authenticated. The
other seven outcomes contain neither facts nor a principal. A verification
stores no raw evidence, provider response, reason, exception, diagnostic,
session, Security Context, policy, or authorization decision.

## Authentication Verifier

An explicitly constructed Phase 9C adapter SPI registered under one exact
mechanism. It begins one operation from one explicit evidence object and one
explicit instant. The neutral interface defines no discovery, `supports()`
probe, priority, fallback, provider protocol, credential accessor, ambient
request/session/clock read, policy, or authorization behavior.

## Authentication Verifier Operation

One adapter-owned Phase 9C attempt returned by a verifier. Its exact contract is
`verify()` followed by explicit `retire()`. Verification runs at most once and
retirement runs exactly once after a successful begin, including after a
verification failure. It is local to one orchestration call and is not a
session, context, credential store, retry handle, principal, verification
cache, or authorization capability.

## Authorization

The planned fail-closed evaluation of one immutable Security Context, exact
Action, exact Resource, exact current Policy Evidence, and explicit
environmental facts to produce an Authorization Decision. Phase 9B implements
only the inert input/result vocabulary. No evaluator or enforcement exists;
only a later exact `Permit` over exact inputs may permit progress.

## Security Context

Phase 9B's Experimental immutable explicit value that is either Anonymous with
no principal or Authenticated with exactly one PrincipalReference. It is not a
static current user, ambient `Auth`, thread local, request singleton authority,
service-located object, session, or Kernel state. It validates shape only,
performs no authentication or authorization, and owns no cleanup itself. Phase
9E explicitly constructs a fresh context for one generated operation and passes
that same object as a version-2 operation input; generated application state
never retains it. Authentication success still grants no authorization.

## Generated Operation Input

Phase 9E's Experimental bounded positional object supplied for exactly one
generated Composition operation. Version-2 source declares its exact contract
and `operation` lifetime. It is validated before lease or construction and
passed through ordinary constructor PHP; it is not a container, lookup,
registry, resolver, serialized value, ambient state, or authority.

## Resource

An exact application-owned reference for a record, object, collection, field,
capability, or other protected subject of a later authorization evaluation.
Phase 9B implements `ResourceReference` as one canonical 1-128 byte
`ResourceType` plus one opaque 1-512 byte valid-UTF-8 `ResourceId` without C0/C1
controls. It is distinct from a route, URI, repository, database row, or
provider object and performs no domain loading or authorization.

## Policy Evidence

Deterministic, versioned, bounded, provenance-bound application policy truth
validated as exact-current before runtime. Missing, stale, partial,
mixed-version, ambiguous, conflicting, unsupported, or unverifiable evidence
cannot permit. Phase 9D implements only the opaque
`AuthorizationPolicyEvidence` interface exposing one exact independently
comparable name/version/provenance reference. It selects no source, schema,
language, compiler, digest, artifact, publisher, runtime loader, trust proof, or
build-currentness mechanism; all such forms remain unimplemented.

## Authorization Decision

Phase 9B's Experimental inert backed enum with exact values `Permit`, `Deny`,
and `Indeterminate`. It stores no reason, policy, receipt, context, or resource
and evaluates nothing. Only later separately authorized enforcement may treat
exact Permit over exact evaluated inputs as permission; the value is not an
ambient or transferable capability.

## Decision Receipt

Phase 9D's Experimental bounded non-secret evidence for one authorization
evaluation. It contains exactly decision, reason, expected policy reference,
Action, Resource Type, Security Context state, and environment profile. It
retains no principal/resource identifier or opaque evidence payload, cannot
replay or enforce a grant, and never becomes policy authority. Storage,
retention, privacy, audit, and telemetry integration remain unimplemented.

## Authorization Environment Profile

Phase 9D's exact canonical 1-64 byte ASCII identifier for one application-owned
environmental-facts shape. It is not a tenant, role, permission, request
attribute, claim, or grant. Opaque `AuthorizationEnvironmentEvidence` exposes
only this profile; fact payloads remain caller-owned and evaluator-specific.

## Browser Session Evidence

Phase 9F's detached opaque evidence for the sole supported
`browser.session.v1` authentication mechanism. It contains one exact
43-character unpadded base64url session identifier and, for state-changing
requests, one same-shape CSRF proof plus an explicit requirement flag. It is
transport input to the selected verifier, not session truth, a credential
store, cryptography, login, authorization, or an ambient current session.

## Protected Capability

Phase 9F's typed boundary around one protected read or effect. It declares the
exact Action and Resource Reference it targets and exposes one execution method.
Obtaining or constructing it grants nothing. The stateless enforcer may invoke
it exactly once only after one exact Authorization evaluation returns Permit
for the identical operation Security Context and equal action/resource. It is
not a bearer token, receipt, cached decision, route guard, or transferable grant.

## Current Security Policy

Phase 9G's immutable checked policy identity evidence for one running
application. It binds an application-owned name, version, provenance, exact
source bytes, detached model, and builder implementation. It contains no policy
rules and grants no authority; changed source or builder bytes require a new
build, check, and application start.

## Security Application Workflow

Phase 9G's supported upper lifecycle that begins one browser-session security
operation, uses its exact Security Context to build one Authorization Request,
calls the protected-capability enforcer once, and closes the operation once.
Only an application-owned evaluator's exact Permit at the enforcer executes.

## Phase 9 External Security Acceptance

The root-owned Phase 9H process profile invoked by `composer
acceptance:phase9`. It uses synthetic application fixtures and fresh temporary
state to prove the selected authentication, authorization, transport,
enforcement, lifecycle, policy-currentness, disclosure, and cleanup boundaries.
It is derived test evidence, not a provider, policy engine, security
certification, production API, or authority grant.
