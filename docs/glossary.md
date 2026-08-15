# Kongono Glossary

These definitions establish architectural vocabulary, not public PHP APIs. Unless identified as locked direction, exact implementation semantics remain experimental or open.

## Action

A planned explicit application operation or use case that coordinates business behavior behind an authorized calling surface. Its invocation, metadata, transaction, and Graph contracts remain open.

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
`Kongono\DiagnosticActivation\`, owns exactly the Experimental unbacked
`TrustedDeveloperBootstrapPermission` and
`AdapterControlledDeliveryPermission` values. It depends on API Metadata alone.
It is not an authorization engine or security boundary and implements no raw
configuration, delivery evidence evaluation, combined gate, adapter, or
presentation access.

## Diagnostic Presentation

The implemented leaf integration boundary `kongono/diagnostic-presentation`,
namespace `Kongono\DiagnosticPresentation\`. Phase 0X provides its trusted-
developer projection core, and Phase 0Z provides the first pure HTML renderer;
later presentation models and other renderers remain owned here but
unimplemented. It consumes all five originating packages, while none depends on
it. Diagnostic Presentation does not depend on the separate Diagnostic
Activation package. Diagnostics stays presentation-neutral.

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
rendering, or delivery. The pure HTML renderer exists, but no activation
enforcement, trusted-mode selector, or production disclosure policy exists.

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
authorization, and no activation enforcement exists.

## Trusted Bootstrap Permission

The Accepted Phase 1A server-owned decision that a future composition boundary
must validate and fix immutably for one adapter instance before it serves work.
Phase 1C implements its exact Experimental unbacked type as
`Kongono\DiagnosticActivation\TrustedDeveloperBootstrapPermission`, with
`Denied`, `Allowed`, and `allowsTrustedPresentation(): bool`. Absence requires
future composition of `Denied`; invalid or ambiguous raw configuration is
rejected before serving. The configuration source, validation failure,
composition API, reload mechanism, and adapter remain unimplemented.

## Adapter-Controlled Delivery Permission

The Accepted Phase 1A decision, independent of trusted bootstrap permission,
that a future outer adapter must make for the current delivery boundary from
server-controlled transport ownership. Client assertions cannot grant it,
uncertainty is denial, and one operation's decision cannot authorize another.
Phase 1D implements its neutral Experimental unbacked representation as
`Kongono\DiagnosticActivation\AdapterControlledDeliveryPermission`, with
`Denied`, `Allowed`, and `allowsTrustedPresentation(): bool`. Its exact evidence,
transport, listener, identity, authentication, and authorization mechanism is
unimplemented and remains owned by the future adapter.

## Trusted Diagnostic Activation

The Accepted Phase 1A fail-closed rule that trusted bootstrap permission and
adapter-controlled delivery permission must both be affirmative before
presentation access, projection, copying Safe or Protected fields, rendering,
or delivery. Missing, invalid, ambiguous, failed, denied, or uncertain decisions
prohibit the trusted path. The architecture and both typed inputs are
implemented; the configuration reader, delivery-evidence evaluation, adapter,
transport, served page, and enforcement are unimplemented. Phase 1E implements
only the pure `TrustedDeveloperPresentationEligibility` AND calculation for one
current delivery. Direct construction or a true result is not authorization.

## Trusted Developer HTML Diagnostic Page

The implemented Phase 0Z pure renderer in Diagnostic Presentation that consumes
only one implemented `TrustedDeveloperDiagnostic` and returns a complete,
accessible, deterministic, escaped HTML5 document with five fixed visible
regions and one exact local stylesheet. It performs no authorization,
projection, transport, or production presentation. The renderer and golden
fixtures are implemented, but no served route, trusted activation boundary,
adapter, or production model exists.

## Trusted Developer Mode

Legacy shorthand for the future enforcement of Trusted Diagnostic Activation,
not a single switch or environment label. Phase 1A requires both trusted
bootstrap permission and independent adapter-controlled delivery permission
before presentation access, projection, rendering, or delivery. Query, route,
path, body, header, cookie, session, form, user, database, provider, raw
environment string, heuristic, AI, ambient, or previous-operation state can
never activate or widen either decision. Calling the projector or renderer is
not authorization. No selector, configuration mechanism, or runtime adapter
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

## Principal

The planned general identity abstraction for an actor that can be authenticated, authorized, delegated to, constrained, and audited. Principal kinds may include Human, Agent, Service, Organization, and System. Exact data and API models remain open.

## Public API

A supported consumer contract governed by Kongono's versioning policy and release maturity. Public does not mean Stable or unchangeable forever, particularly before 1.0; extension is permitted only as the specific contract documents.

## Relation

One immutable directed Phase 0D connection whose structure is exactly source Node Key, Relation Kind, and target Node Key. Self-relations and cycles are valid; exact duplicate triples are not.

## Relation Kind

A non-empty, exactly preserved open label carried by a Relation. It establishes no built-in dependency, call, event, module, or security vocabulary.

## Service Principal

A Principal representing a software service or machine workload. Credential, identity, trust, rotation, and authorization semantics remain open.

## Service Contract

The exact canonical declared fully qualified name of an existing PHP interface used as a Phase 0F service identity. Classes, aliases, case variants, leading-backslash names, and the Kongono resolver contract or its subinterfaces are not service contracts.

## Service Definition

One immutable Phase 0F mapping from a Service Contract to a canonical concrete implementation and an ordered list of Service Contract dependencies. Constructor reflection validates the explicit list but does not discover dependencies.

## Service Scope

A planned lifetime and ownership boundary for service instances. Phase 0F selects only transient-per-edge construction and implements no shared, singleton, request, operation, worker, process, tenant, or custom scope.

## Structured CLI

The doctrine that meaningful `kongo` commands should support deterministic, machine-readable, automation-safe interaction, including `--json`, `--dry-run`, and `--no-interaction` where applicable. The CLI architecture and exact command contracts are not yet defined.

## Transient Resolution

The Phase 0F rule that every top-level resolve call and every occurrence of a dependency creates a fresh implementation object. Duplicate dependencies and diamond branches never share lower-level instances, and no constructed service is retained by the resolver.

## Trust

The evaluated confidence and policy relationship under which a Principal, extension, credential, or system may act. Trust is not a substitute for explicit authorization. Its sources, scoring or categorization, and enforcement semantics remain open.

## Workflow

A planned coordination of multiple actions or steps, potentially durable and long-running, with explicit state and failure behavior. Definition, persistence, replay, compensation, and Graph contracts remain open.
