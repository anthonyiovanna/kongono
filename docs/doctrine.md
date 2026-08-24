# Kongono Architectural Doctrine

## Status

The principles in this document are locked architectural direction. Phase 2A
through 2H are complete at checkpoint
`647053d4bddc4c76090bca9bf3d693faa5880c52`. Exactly fourteen acyclic production
packages implement the bounded framework path, and a root-owned, test-owned
fixture proves that path externally. The fixture is not a production
application, supported template, SAPI adapter, server, listener, emitter,
deployment entrypoint, or trust mechanism.

Phase 3B selects PHP `cgi-fcgi`, CGI/1.1, and FastCGI `RESPONDER` as the first
exact production host profile. Phase 3C and ADR 0053 implement bounded explicit
inbound conversion. Phase 3D and ADR 0054 implement fully staged bounded
response emission. Phase 3E and ADR 0055 implement explicit typed operator
selection, fixed native bootstrap validation, and independent current-delivery
permission evaluation without a general configuration or raw scalar parser.
Streaming is unsupported, and post-commit failure is uncertain, throws, and is
never rolled back or retried. The Phase 3F delivery and thin production entry,
Phase 3G front-controller lifecycle/deployment constraints, and Phase 3H
test-owned external acceptance are implemented. The acceptance harness is not a
production listener, server, client, process manager, or deployment artifact.
Kernel remains
transport-neutral; Web Application retains one local Kernel scope per accepted
application call; and Web Runtime retains outer failure and send/close
orchestration. Phase 3 is complete; no next phase starts automatically. See the
[PHP CGI profile](architecture/php-cgi-fastcgi-profile.md) and [Phase 3 roadmap](architecture/phase-3-roadmap.md).

Phase 2B accepts PSR-7, the selected server-side PSR-17 factories, future
PSR-15, and the first-party `kongono/http-message` package boundary. Phase 2C
implements that ninth package with the five selected message roles, five
stateless factories, and bounded package-local diagnostics. Phase 2D implements
Routing as a separate tenth package consuming only PSR-7 interfaces, API
Metadata, and Diagnostics. It performs exact encoded method/path matching with
no normalization, handler execution, authorization, or retained request state.
Phase 2F owns application bootstrap and application-call Kernel scope cleanup,
Phase 2G owns the ports-only outer adapter, and Phase 2H's fixture proves their
composition. Production host conversion, emission, deployment, and broader
resource cleanup remain unimplemented.

## Product philosophy

Kongono Framework is a modern application framework designed for humans, software clients, and AI agents. Its guiding product statement is **Build what comes next.** It is not a Laravel clone. It seeks comparable developer productivity through an explicit, typed, deterministic, observable, modular, persistent-runtime-safe, and AI-native architecture.

Applications should be understandable to developers, infrastructure, tooling, AI agents, and other applications without relying on undocumented convention or probabilistic inference.

## Authoritative application knowledge

The planned Application Graph will be Kongono's authoritative, machine-readable application representation. It is intended to describe modules, services, dependencies, routes, actions, entities, schemas, events, jobs, workflows, policies, permissions, principals, agents, delegations, capabilities, extension contributions, frontend contracts, external APIs, and documentation relationships.

The Graph will derive from a language-neutral Intermediate Representation. PHP is the first source-language frontend, not the permanent conceptual boundary. The implemented in-memory subset contains only immutable nodes with opaque graph-local keys and open kinds, plus directed open-kind relations between existing nodes. It validates duplicates and endpoints, returns canonical binary-string order, performs exact node lookup, selects immediate outgoing or incoming relations by exact endpoint-key value, and selects whole-snapshot nodes or relations by exact kind-label value. Node-kind and relation-kind selection remain separate domains even when labels are byte-equal. Absent kinds return empty lists; kind results preserve canonical order and the same immutable objects in fresh arrays. Node-kind selection scans `N` nodes in `O(N)` time, relation-kind selection scans `R` relations in `O(R)` time, result allocation is `O(k)`, and no kind index or query cache is retained. Open labels define no vocabulary, schema, hierarchy, or compatibility rules. These narrow read boundaries are not combined filters, multiple-kind selection, predicates, traversal, reachability, paths, neighbor selection, or a generic query system. The snapshot is not yet the full authoritative representation. Stable identities, schemas, metadata, provenance, visibility, serialization, discovery, compilation, persistence, mutation, framework integration, query planning, and performance budgets remain experimental or open.

AI may suggest interpretations and changes, but correctness-critical application structure, authorization, security policy, migration safety, dependency legality, module boundaries, and API contracts must be determined by Kongono-owned, deterministic mechanisms.

## Microkernel and capabilities

Kongono will use a small microkernel with independently installable components that contribute coherent capabilities. Potential capabilities include HTTP, Data, ORM, Migrations, Authentication, Authorization, Events, Queues, Workflows, Cache, Storage, Mail, Notifications, Realtime, Scheduling, AI, Agents, Delegation, MCP, A2A, Frontend integration, Testing, and Developer tooling.

This list is direction, not a package map. The fourteen implemented production
packages are API Metadata, Application Graph, Dependency Injection, Diagnostic
Activation, Diagnostic Handler, Diagnostic Presentation, Diagnostics, HTTP
Message, HTTP Server, Kernel, PHP CGI, Routing, Web Application, and Web Runtime. The
resolver is not a general capability platform and implies none of the other
listed packages.

Neutral ownership of bootstrap API metadata and acyclic package dependencies is
locked and implemented. Phase 0S moved `Api` and `ApiStatus` atomically from the
former `Kongono\Kernel\Api\` namespace to `Kongono\ApiMetadata\` in the fifth
`kongono/api-metadata` package. Kernel, Diagnostics, Graph, and DI depend
directly on that neutral package; Graph, Kernel, and DI independently require
Diagnostics.
The old FQCNs are absent without a shim. Phase 0S does not authorize Kernel
diagnostic context.

## Progressive architecture

MVC remains a friendly default at the HTTP and UI edge. Business logic must not be structurally trapped in controllers. Applications should be able to evolve toward actions or use cases, modular monoliths, domain-driven architecture, ports and adapters, justified CQRS, events, and durable workflows without switching frameworks.

Flexibility at application architecture layers does not weaken framework invariants. Phase 0F implements only explicit typed constructor resolution with transient construction. Broader dependency injection, the authoritative Application Graph, structured CLI contracts, architecture validation, security metadata, telemetry, and other machine-readable contracts remain planned.

## Persistent-runtime-first lifecycle

Core lifecycle design must not assume the process or all application state ends after each HTTP request. Future adapters may support FrankenPHP, RoadRunner, OpenSwoole, PHP-FPM, CLI workers, serverless environments, and future runtimes. No runtime may become a hard kernel dependency.

The implemented Kernel lifecycle boots once, permits overlapping opaque operation scopes owned by Kernel-instance object identity, refuses shutdown while work is active, and exposes no ambient current operation. Web Application now orchestrates exact begin/end cleanup around one application call without changing Kernel. Scoped data, broader resource cleanup, context propagation, cancellation, and runtime adapters remain open.

## Platform boundaries

PHP 8.5 is the primary application language. TypeScript and JavaScript are for the browser and frontend environment. Rust may later be used for high-performance framework tooling, while Go may be selected for specific infrastructure or runtime integration. No Rust, Go, Node.js, or TypeScript tooling is introduced in Phase 0A.

Kongono will not build a JavaScript bundler. Vite is the intended default build integration. Kongono will own an Asset Platform abstraction, with possible future adapters for React, Vue, Svelte, HTMX, Alpine, server-rendered HTML, and native/no-bundler assets.

## Observability and APIs

OpenTelemetry is intended to be native to the framework's instrumentation model. Framework operations should eventually emit structured telemetry hooks, with replaceable exporters. No telemetry package is selected or installed in Phase 0A.

OpenAPI is intended to be generated from authoritative application metadata so contracts do not require independent manual maintenance. No OpenAPI mechanism exists yet.

## Actionable diagnostics and error boundaries

Every deliberate Kongono failure must answer:

```text
What failed?
Why did it fail?
Where did it fail?
What can the developer do next?
```

Deliberate framework failures belong to the package or capability that owns the
violated invariant. Expected application outcomes remain ordinary typed control
flow rather than automatically becoming exceptions. Infrastructure failures are
wrapped only when an owning semantic layer adds meaning, and the original cause
chain must be preserved. Engine and programmer failures must not be disguised as
validation or business outcomes.

Diagnostics must be precise without assuming that identifiers, values, paths,
provider messages, source, request data, or environment data are safe for an
untrusted audience. Protected developer detail and intentionally generic
production output remain separate. A client-controlled request value must never
enable debug disclosure.

Persistent-runtime operation boundaries must eventually guarantee cleanup while
preserving the primary failure, reporting cleanup failure, and retiring runtime
instances whose state becomes uncertain. The exact cleanup and simultaneous-
failure mechanisms remain open.

Phase 0M implements the 42 current assignments through three package-local
Internal catalogs. Each existing Public package marker exposes the stable ID,
title, and remediation as strings while markers remain unrelated and messages
remain unchanged protected detail. Titles, remediation, and messages are prose;
only the ID is stable machine identity. No universal diagnostic interface, safe
structured context, renderer, error page, logger, telemetry integration, retry
engine, cleanup runner, incident system, localization, or transport mapping
exists.

Diagnostic structural context is never an arbitrary bag. Schemas fix
operation, violated invariant, keys, labels, scalar kinds, disclosures, order,
and optionality; direct construction produces eager immutable detached snapshots
bounded to 16 fields and free of ambient state, I/O, or global per-failure
accumulation. Structural validity alone does not make a schema semantically
authorized. The Graph catalog owns exact schemas for its 11 identifiers and
Graph throw sites provide only their approved values. Phase 0T does the same for
all six Kernel lifecycle failures using required Safe fields. Phase 0U completes
the original families with 25 DI schemas and 26 exact throw-site maps.

Disclosure has exactly two cases. Safe means eligible under a later explicit
policy, not automatically public. Protected means trusted developer or
protected operational use only. Secret-bearing, unbounded, or uncertain data is
forbidden from capture rather than assigned a third classification. The four
package catch boundaries remain separate now that `kongono/diagnostics` owns
presentation-neutral values and its own construction failures. AI may
explain diagnostics but cannot classify fields, override disclosure, recover
forbidden material, or become authoritative for interpretation.

The value types, structural validation, all 42 original Kernel/Graph/DI catalog
schemas, their three package-local marker accessors, and exact throw-site capture
are implemented. Diagnostics construction failures remain context-free. No
forbidden-data detection, sanitizer, transport, logger, or telemetry hook is
implemented. Safe and Protected classifications authorize no presentation.

Diagnostic presentation belongs to the separate implemented leaf package,
never to presentation-neutral Diagnostics or an originating semantic package.
Its first implemented model is a pure bounded projection over exactly one Kernel,
Graph, or DI contextual marker. It preserves package ownership, catalog prose,
fixed operation and invariant, canonical field order, value kind, Safe or
Protected disclosure, and truncation state without messages, causes, traces,
source, requests, environment, transport data, I/O, caches, or retained source
objects. It does not create a universal exception marker.

Trusted diagnostic activation is governed by a locked two-gate fail-closed
rule. At the Phase 1 boundary, a future outer composition boundary had to provide affirmative
**trusted bootstrap permission** as an immutable decision for one adapter instance,
and the adapter must independently provide affirmative
**adapter-controlled delivery permission** for the current delivery. Both precede presentation access, projection, copying,
rendering, and delivery. Absence, invalidity, ambiguity, evaluation failure,
denial, or uncertainty prohibits the trusted path. No request or other client
input, raw environment label, truthy string, heuristic, AI output, ambient
global or service state, or prior operation may enable or widen either
permission. Phase 1C implements the Experimental unbacked
`TrustedDeveloperBootstrapPermission` value and Phase 1D adds the Experimental
unbacked `AdapterControlledDeliveryPermission` value in the separate Diagnostic
Activation package. They are neutral typed inputs, not evidence evaluators or
authorization by themselves. Web Runtime now performs the implemented two-gate
fail-closed response-selection and delivery sequence from explicitly supplied
decisions. Raw operator configuration, host evidence evaluation, and production
SAPI integration remain future and unimplemented. API status,
package-local tokens, output escaping, and direct projector or renderer
callability are not authorization. The trusted developer model is prohibited as
a production model; production needs a separate deny-by-default policy and
explicit allowlists. Projection and concrete rendering remain distinct
implemented responsibilities.

Phase 1E's pure conjunction returns true only when both typed permissions are
`Allowed`. The eligibility object is limited to one current delivery, proves no
external authority, and performs no enforcement or presentation access.

Phase 0Z implements the first concrete presentation architecture accepted in
Phase 0Y. Actionable developer failures are deliberately structured into
fixed, scannable regions that state what happened, why, bounded context, and how
to fix it. Output encoding belongs to the final grammar boundary: the implemented
HTML renderer escapes every dynamic text value there and never accepts raw
markup. Its exact template and CSS are fixture-backed and it retains no state.
Escaping and disclosure authorization are separate controls; escaped Protected
data remains Protected. Diagnostic presentation must remain useful without
remote assets, network access, or AI services. Both typed permission decisions
and their neutral Web Runtime enforcement now exist, while raw-input validation,
operator and host evidence evaluation, production SAPI transport, and production
presentation remain future outer-boundary concerns.

## Agents and interoperability

Kongono recognizes Human Experience, Software/API Experience, and Agent Experience as first-class consumption surfaces. An application capability should eventually be definable once and safely exposed through appropriate authorized surfaces.

Agents are potential first-class principals alongside humans, services, organizations, and the system. Planned concepts include delegation, capability, trust, intent, consent, constraints, delegation chains, revocation, audit, and receipts. MCP and A2A are adapters around Kongono concepts; they must not define Kongono's internal model. Provider independence is required, and no AI provider SDK belongs in this phase.

## Secure extension platform

The planned Extension Platform will support core, trusted/in-process, sandboxed, and external extensions. An Extension Host is expected to govern permissions, capabilities, lifecycle, storage, network access, secrets, events, UI contributions, agent capabilities, updates, removal, and health.

Third-party extensions must not automatically receive unrestricted access to databases, filesystems, secrets, shells, processes, or networks. WASM/WASI sandboxing is anticipated as a possible future direction but is neither a v1 requirement nor authorized in Phase 0A.

## Standards-first interoperability

Kongono should use stable open standards when they improve interoperability without surrendering architecture. Likely standards include Composer, PSRs, OpenAPI, OpenTelemetry, OAuth/OIDC, MCP, A2A, and web standards. External protocols must sit behind Kongono-owned abstractions where appropriate.

Phase 2B applies that doctrine by accepting PSR-7 through
`psr/http-message: ^2.0`, the selected server-side PSR-17 factories through
`psr/http-factory: ^1.1`, and PSR-15 for Phase 2E handling. These are
interface-only edge contracts. Kongono retains an implemented first-party
`kongono/http-message` implementation, rejects framework substrate and runtime
factory discovery, keeps factories as explicit composition dependencies, and
keeps the Kernel transport-neutral and PSR-free. Phase 2D routing consumes the
PSR-7 interface without depending on that concrete package. Phase 2E owns the
implemented PSR-15 middleware and terminal-handler behavior; Phase 2F owns the
implemented Web Application bootstrap and Kernel operation-scope composition boundary.

## Structured CLI

The official future executable is `kongo`. Meaningful commands should eventually support machine-readable and automation-safe behavior such as `--json`, `--dry-run`, and `--no-interaction` where applicable. No CLI architecture or executable exists in Phase 0A.

## Smallest correct implementation

Do not build speculative abstractions or future phases early. Public APIs create compatibility obligations. Prefer explicit, inspectable, deterministic behavior over hidden magic and implement only approved scope.

## Phase 2 runnable milestone

Phase 2A through 2H established thirteen production packages; Phase 3C adds PHP
CGI as the fourteenth. The runnable fixture remains root-owned Phase 2 test
infrastructure, not a package or API. It composes the real Public outer delivery port,
one Web Application-owned Kernel scope, deterministic routing and middleware,
fixed generic output, permission-gated trusted HTML, and exact cleanup.

Fixture-runnable does not mean production-ready. The runner selects only fixed
test modes and scenarios and is not a general CLI, listener, emitter,
authorization mechanism, deployment surface, or supported application template.
Production integration and every broader framework capability remain subject to
explicit architect authority.

Phase 3's accepted direction is one supported synchronous production HTTP
integration profile with bounded conversion, exact emission, immutable
operator-owned bootstrap, independent trust inputs, preserved Web Runtime/Web
Application/Kernel ownership, deterministic cleanup, deployment documentation,
and external proof. Phase 3B fixes the first PHP CGI profile's architecture,
Phase 3C implements its bounded explicit inbound converter, Phase 3D implements
bounded response emission, and Phase 3E implements immutable typed operator
bootstrap plus independent current-delivery permission evaluation. Phase 3F
implements a fresh Internal delivery and Experimental thin entry. The delivery
keeps native capture bounded, input lazy, emission single-attempt, cleanup
explicit, and all per-delivery state off ambient framework surfaces. Invalid
caller input reaches Web Runtime's fixed generic 500; Phase 3 creates no semantic
client-error mapping. Phase 3G adds one stateless front-controller entry attempt
followed by one explicit supplied application-shutdown attempt. Its four-way
matrix uses 1019 when both fail, adds no direct PHP-CGI-to-Web-Application edge,
and retains no ambient state. Application composition and partial-boot cleanup
remain caller-owned. `Production` trust remains denied; uncertain emission or
shutdown requires external process retirement. Phase 3H supplies external,
test-owned proof for the selected synchronous PHP-CGI FastCGI RESPONDER profile,
without adding acceptance dependencies to production packages. Process, proxy,
TLS, credentials, logs, monitoring, incidents, and deployment ownership remain
external. Production trusted diagnostics remain denied. Phase 3 is complete,
no next phase starts automatically, and the milestone remains smaller than the full
framework, Stable API, 1.0, or production-hardening certification.

## Rolling current-minor PHP doctrine

The actively developed line supports exactly one stable PHP minor at a time:
the newest minor that has completed Kongono's compatibility gate. PHP 8.5 is
the current certified line, and Phase 5B applies the bounded Composer
expression `>=8.5.0 <8.6.0` to the root and all eighteen packages. PHP 8.5.9
on Windows is the environment proved by this execution; stable eligible 8.5
patches remain within range. Composer admission, publication, or prerelease success
does not establish PHP 8.6 or PHP 9 support.

Deprecations are migration signals, not warnings to suppress. A new minor
requires deterministic dependency, platform, autoload, style, analysis, test,
external acceptance, persistent-runtime, cleanup, host, extension, performance,
memory, and release evidence. See [ADR 0059](adr/0059-rolling-current-minor-php-baseline.md)
and the [PHP support policy](engineering/php-support-policy.md).

## Human-centered developer intelligence

Phase 3 is complete at checkpoint
`6aacafc92f07b567818173d660e2e182dfa83d23`. Phase 4A accepts documentation
architecture for three future optional roles above one versioned, inspectable,
AI-independent knowledge foundation:

- Kongono Chie helps you build it but cannot certify its own candidate.
- Kongono Hantei proves it works from deterministic evidence and cannot repair
  what it judges.
- Kongono Oshie helps you understand it from version-matched accepted sources
  and cannot make generated teaching authoritative.

AI may suggest; Kongono must know. The framework remains usable offline and
without AI, a provider, an account, or a network. Developer companions are
distinct from application Agent Principals. Future answers must identify the
exact version or source state, cite bounded provenance, distinguish fact,
observation, inference, recommendation, candidate, and uncertainty, and fail
closed on missing or ambiguous authority or evidence.

The roles, knowledge artifacts, packages, commands, providers, patch proposals,
auto-fix, autonomous operations, and production access are not implemented.
See [ADR 0060](adr/0060-human-centered-developer-intelligence-family.md) and the
[Phase 4 roadmap](architecture/phase-4-roadmap.md).

## Exact-state knowledge doctrine

[ADR 0061](adr/0061-versioned-knowledge-source-and-provenance-contract.md)
accepts six distinct source-authority classes and six claim classes. Accepted
policy, observed implementation, current explanation, historical evidence,
workspace observation, and derived advice cannot be flattened into one corpus.
Every version-sensitive claim binds to an exact immutable source state and
retains bounded, non-secret provenance.

Derived material and model output never become independent authority.
Unresolved conflicts, staleness, version mismatch, missing validation, and
incomplete evidence remain visible. Deterministic knowledge work stays offline
and AI-independent; source text remains inert and cannot authorize execution.
Official framework knowledge and project-scoped workspace evidence remain
separate. Phase 4B implements no artifact or mechanism; Phase 4C must separately
select the concrete deterministic slice.

## Deterministic artifact implementation

ADR 0062 and Phase 4C implement one bounded consequence of the exact-state
knowledge doctrine. `kongono/knowledge` builds and validates a canonical
31-source framework-foundation artifact without AI, a network, a workspace
reader, or runtime integration. Exact bytes and authority labels remain
inspectable; whole-source references are source-set-specific; stale state fails
visibly. Optional publication is immutable, content-addressed, atomic, and
no-clobber. The artifact is derived evidence and cannot authorize execution,
replace governing sources, or become a complete framework or workspace model.

## Read-only role derivation

## Independent deterministic verification

Hantei owns verification, not repair. A pass requires every fixed relevant
check against one unchanged exact subject. Failures and unavailable evidence
remain visibly distinct, raw hostile output stays outside verdicts, and derived
reports never become accepted framework authority.
ADR 0063 and Phase 4D implement one least-authority consumer of that artifact.
Chie may return exact cited source sections for six closed topics and bounded
literal occurrences of one supplied Safe diagnostic identifier. Its output is
derived material; the supplied identifier is only a current workspace
observation and does not prove occurrence. Retrieved instructions remain inert.
Chie cannot infer authority, recommend or change code, select or certify gates,
impersonate Hantei, access arbitrary workspace state, or use a model, network,
Git, deployment, or production capability.

Phase 4F Oshie teaches only from exact current accepted source sections through one closed package curriculum. Its lessons and answer feedback are derived teaching, never doctrine. Oshie cannot execute examples, retain learner state, mutate a subject, verify correctness, or elevate its own output into authority.

Phase 4G Chie may construct one deterministic, reversible review candidate only
from an explicit current-explanatory-documentation source in the validated
current artifact. Caller-asserted Safe text is not sanitized. The candidate is
derived material, remains unapplied and unverified, and requires explicit human
application plus fresh independent Hantei evidence. Chie cannot write, apply,
certify, use Git, invoke Hantei, or use a model, provider, or network. Oshie
teaching does not supply proposal authority.

Phase 4H closes Phase 4 with root-owned external evidence only. Its exact 28
scenarios prove direct accepted-source availability, one current identity,
offline local Knowledge/Chie/Oshie behavior, real and explicitly synthetic
Hantei distinctions, visible failure, inert retrieved instructions,
non-disclosure, temporary cleanup, and no authority elevation. The harness is
not doctrine, a production service, an application Agent Principal, or a source
of model, provider, network, Git, deployment, or production authority. Phase 5
now begins with documentation-only Phase 5A.

## Supported composition and deterministic build doctrine

Kongono will provide one explicit supported application composition root. It
owns construction, startup and shutdown order, application-lifetime objects,
operation factories, and partial-boot cleanup without becoming Kernel. Kernel
remains lifecycle-neutral and gains no configuration, discovery, routing,
transport, resolver, generated-code, or application-definition responsibility.

Discovery, declaration validation, dependency validation, route assembly, and
generation occur in an explicit deterministic build/check step before runtime,
never on the first request. Production accepts only one complete current
artifact and fails closed on missing, stale, mixed, partial, ambiguous,
unsupported, or unverifiable state. Build inputs and outputs are bounded,
versioned, content-identified, inspectable, atomically published, safe to
rebuild, and free of secrets. Generated output is derived evidence, not
authority.

Generated readable ordinary PHP constructor wiring is the preferred future
production mechanism. Constructor injection and canonical explicit dependency
declarations are the default. Runtime service location, ambient containers,
resolver/container injection, property-hook dependency resolution, request-time
reflection or scanning, hidden autowiring, runtime `eval`, remote generated
code, and implicit lazy lifecycle behavior are rejected.

Application, operation, and transient lifetimes require exact future contracts
for construction, sharing, cleanup, reverse-order release, failure composition,
and concurrency before they become runtime API. The current resolver and
Application Graph remain unchanged. Phase 5A selects no syntax, schema,
package, API, command, artifact, template, runtime integration, or deployment.
Phase 5B changes installability metadata only and adds no runtime semantics,
package, dependency, public API, polyfill, shim, release/LTS promise, or
composition/build capability. PHP 8.6, PHP 9, prerelease, nightly, and automatic
future-minor support remain unclaimed. Phase 5B is accepted and the bounded PHP
constraint is current.

Phase 5C accepts one inert data-only source at
`<application-root>/kongono.composition.json`, with exact schema/version,
declaration grammar, canonical symbol, typed root, lifetime/reachability,
cleanup/failure, bound, validation-order, path-safety, disclosure, and
provenance semantics. The application source is authority and derived state is
evidence. Input values and secrets never enter source or provenance.

Phase 5D implements the deterministic build evidence boundary while preserving
the application source as authority. Raw source provenance and canonical model
identity remain separate. Current composition remains caller-owned; the DI
resolver, Graph, Kernel, routes, and runtime remain unchanged. Phase 5E adds
derived canonical typed wiring and isolated lifecycle support while preserving
source authority. It rejects collection widening and represents plural
collaboration with an explicit application-owned typed aggregate. No schema
file, command, production loader, or later integration capability is
introduced. Phase 5F has not begun. See [ADR 0070](adr/0070-versioned-composition-source-and-provenance.md),
[ADR 0071](adr/0071-roots-bindings-lifetimes-and-cleanup.md), and the
[ADR 0072](adr/0072-deterministic-composition-artifact-and-publication.md), and the
[ADR 0073](adr/0073-generated-typed-wiring-and-lifecycle-boundary.md), and the
[composition source guide](architecture/composition-source.md).
