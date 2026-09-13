# Server-Rendered Presentation

## Status

Phase 18A accepts this document, [ADR 0179](../adr/0179-server-rendered-presentation-and-compiled-view-boundary.md),
[ADR 0180](../adr/0180-context-aware-output-safety-and-presentation-lifecycle.md),
the [view-authoring guide](../engineering/view-authoring.md), and the
[fixed Phase 18 roadmap](phase-18-roadmap.md) as documentation architecture
only. Phase 18B accepts [ADR 0181](../adr/0181-neutral-view-contract-and-closed-presentation-values.md)
and the optional Experimental `kongono/view-contract` leaf. Phase 18C accepts
[ADR 0182](../adr/0182-deterministic-structured-view-source-and-compiled-artifact.md)
and implements the optional Experimental `kongono/view-build` leaf. Phase 18D
accepts [ADR 0183](../adr/0183-exact-current-compiled-view-runtime-and-bounded-rendering.md)
and the optional exact-current [compiled-view runtime](compiled-view-runtime.md).
Phase 18E accepts [ADR 0184](../adr/0184-explicit-completed-view-to-psr-7-response-adaptation.md)
and the optional explicit [compiled-view HTTP adapter](compiled-view-http.md).
Phase 18F accepts [ADR 0185](../adr/0185-supported-compiled-view-application-workflow.md),
the optional [compiled-view application workflow](compiled-view-application-workflow.md),
three explicit view commands, and one copy-once minimal example.
Phase 18G accepts [ADR 0186](../adr/0186-visibility-safe-compiled-view-inspection-and-developer-intelligence.md)
and the optional [compiled-view inspection boundary](compiled-view-inspection-and-intelligence.md).
Phase 18H remains Planned and requires separate authenticated authority.

The closed Phase 18C structured grammar, compiler, canonical artifact, inert
generated PHP, validators, and publisher exist. The Phase 18D loader, renderer,
and contextual escaper exist only in the optional upper runtime package. The
Phase 18E adapter maps completed neutral results to PSR-7 under explicit
caller policy. The Phase 18F workflow, commands, and copy-once skeleton compose
the accepted lower boundaries without hidden compilation. Phase 18G validates
one supplied profile structurally in memory and emits only bounded
Safe/Protected evidence without I/O, execution, rendering, recording, or role
execution. No cache, watcher,
daemon, installer, deployment manager, automatic migration, or asset pipeline exists.

## Purpose

The presentation boundary gives a future Kongono application an explicit way
to turn bounded presentation data into deterministic server-rendered bytes.
It preserves application ownership and Kongono's build-before-runtime,
exact-current, fail-closed, and persistent-runtime-safe doctrine.

Use the future capability when an application explicitly needs server-rendered
HTML. Do not use it for JSON APIs, CLI output, workers, agents, headless
processes, arbitrary document conversion, client-side applications, assets, or
as an authorization or application-effect mechanism.

## Boundary map

```text
route match
  -> application-selected handler
  -> application/domain work under existing authority
  -> immutable presentation data
  -> logical view identity and exact compiled identity
  -> neutral render operation
  -> completed bounded render result
  -> optional application-owned HTTP response adaptation
```

Routing ends at handler selection. It does not choose a view. The view
contract is independent of PSR-7. HTTP adaptation is a separate upper package
and cannot change the meaning or success of the render operation.

Template selection, data creation, render initiation, response status, headers,
and browser-security policy remain application decisions. A route, logical
view identity, schema, artifact digest, generated class, result, or rendered
bytes grants no authority.

## Optional package direction

The separately authorized package direction is:

- `kongono/view-contract`: neutral identities, closed values, schemas,
  requests, results, bounds, failures, and diagnostics;
- `kongono/view-build`: explicit source-set validation, closed grammar,
  contextual analysis, deterministic artifact and ordinary PHP generation,
  exact currentness, source mapping, and no-clobber publication;
- `kongono/view-runtime`: explicit contained exact-current loading and bounded
  synchronous rendering;
- `kongono/view-http`: explicit mapping of a completed neutral result to a
  PSR-7 response under application-owned policy;
- `kongono/view-application`: supported build, check, run, skeleton, and
  structured-command composition; and
- `kongono/view-inspection`: supplied-profile structural validation and only
  bounded visibility-safe Safe/Protected evidence.

`kongono/view-contract`, `kongono/view-build`, `kongono/view-runtime`,
`kongono/view-http`, `kongono/view-application`, and `kongono/view-inspection`
are implemented. Inspection establishes no Graph relationship and its neutral
projections remain `constructed-not-recorded`.
Their application-selected surfaces are
Experimental and implementation helpers stay Internal. No lower package
depends outward on presentation.

## Build-time model

One explicit application-owned source set is the entire build input. It is
bounded, contained, versioned, and complete. No directory scan, Composer
package scan, Reflection discovery, newest-file choice, network lookup, remote
registry, or implicit merge participates.

The deterministic build validates source bytes, logical identities, declared
schemas, references, the closed node tree, output contexts, and
bounds before emitting a canonical artifact and ordinary PHP. Equivalent
accepted input produces byte-identical output. Publication is explicit,
content-addressed, atomic, idempotent for identical valid bytes, contained,
initial-only, and no-clobber. Check or plan regenerates and compares in memory
without writing.

Source identity, semantic model identity, artifact identity, generated-PHP
identity, and currentness evidence remain distinct. Currentness is relative
only to the one complete source supplied by the caller. Generated PHP is
validated as inert bytes. Phase 18C does not load or execute it. See the
[compiled-view build guide](compiled-view-build.md).

## Runtime model

The future runtime receives one explicit contained artifact path, the matching
artifact and generated identities, one logical view identity, one closed
context, explicit options, and fixed bounds. It revalidates exact currentness
before one irreversible load and uses direct generated calls afterward.

There is no runtime parser, compiler, interpreter, `eval`, Reflection scan,
directory discovery, dynamic include, arbitrary path, service locator,
container access, request global, environment lookup, mutable registry, or
fallback artifact. Development uses the same explicit build/check evidence;
it does not create a permissive runtime.

Rendering is synchronous and operation-scoped. Immutable shared evidence may
serve sequential and overlapping calls; operation data and temporary output
are never shared. No implicit native output buffer, static current render,
shutdown hook, destructor cleanup, background reader, worker, or retained
request state exists.

## Logical identity and data contract

A logical view identity is canonical and bounded. It identifies a member of
the compiled set; it is not a filename, URI, glob, class name, or user-selected
path. An artifact identity binds the request to one exact compiled source state.

The implemented declared context is closed and permits only bounded string,
integer, boolean, null, list, and record presentation values. Missing, extra,
duplicate, unsupported, type-invalid, stale, or
mixed-version input fails before rendering. No arbitrary object graph,
domain entity, repository, service, container, request, session, security
context, credential, or capability port is exposed to a template.

Future layouts, includes, and components—if selected—resolve by logical
identity inside the same closed artifact. Cycles, depth, count, iteration, and
output bytes have finite limits. Dynamic names and arbitrary include paths are
not permitted.

## Output contexts and safety

Plain strings are data. The compiler knows the output context and assigns the
corresponding escaping operation. Phase 18B exposes exactly HTML text and
quoted HTML attribute values as inert compiler metadata vocabulary; it performs
no escaping.

URL, JavaScript, CSS, raw markup, and event-handler contexts are denied until a
later decision defines typed semantics and tests. Runtime guessing, regex
sanitization, post-render cleaning, and one generic `escape()` function are not
accepted substitutes. Any future trusted-markup value is explicit,
provenance-aware, context-scoped, and cannot be forged or preserved by ordinary
string concatenation.

The renderer does not create CSP, nonces, Subresource Integrity, URL policy,
security headers, sessions, CSRF evidence, authentication, authorization,
redirects, cache policy, or asset behavior.

## Bounds and failure model

Every source set, logical identity, schema, value, string, collection, nesting
level, iteration, component traversal, temporary buffer, and completed output
is finite under separately accepted constants. Partial output is never
success. Validation happens before rendering where possible, and no completed
result is published until the operation is terminal.

Future diagnostic families distinguish source/build failure, unsupported or
ambiguous context, stale artifact, generated identity mismatch, invalid
context, missing or extra value, exceeded bound, rendering failure, cleanup
failure, and HTTP adaptation failure. Package exceptions retain exact previous
throwables where authorized and preserve primary plus cleanup truth.

Production failure presentation is generic. Logical template and bounded
source location are Protected and may appear only through existing trusted
diagnostic activation and disclosure. Values, fragments, rendered bytes,
private paths, stacks, credentials, session/CSRF material, and provider payloads
are never diagnostic context by default.

## Composition, lifecycle, and concurrency

Composition owns application-lifetime immutable compiled evidence and creates
operation-scoped render work. A runtime accepts responsibility exactly once,
retires temporary state on every path, and reports cleanup uncertainty. One
failed, cancelled, malformed, or oversized operation cannot leak data or state
into a later or overlapping operation.

Trusted generated and application PHP retain the technical power of their
process. The view boundary is not a sandbox and grants no capability to
untrusted source. Extension selection, contribution admission, capability
grants, and execution remain governed by the existing Extension Platform.

## HTTP adaptation

Only a completed neutral result can enter `kongono/view-http`
boundary. The application supplies status, headers, content type, and security
policy explicitly. The adapter may create one PSR-7 response; it does not own
routing, controller discovery, middleware magic, sessions, CSRF, CSP, assets,
redirects, negotiation, a server, emission, or error swallowing.

An HTTP adaptation failure does not retroactively turn a failed render into a
success or permit partial rendered bytes to escape as a successful response.

## Developer workflow

The planned workflow is:

1. create or select one application-owned source;
2. declare its closed input schema;
3. run an explicit write-free check or authorized build;
4. inject or construct immutable presentation data;
5. render by logical identity against exact-current evidence; and
6. when needed, explicitly adapt the completed result to HTTP.

Human and machine command output derives from one neutral result. Automation
never prompts silently. See [View authoring](../engineering/view-authoring.md)
for planned pseudocode and status labels.

## Developer intelligence and evidence

Chie may explain accepted doctrine and propose reviewable documentation
changes. Hantei independently verifies accepted deterministic evidence. Oshie
teaches from accepted sources. A future inspection leaf may expose only
Protected digests, closed tokens, and Safe aggregates for Graph, Observability,
Security Audit, and role-addressed views. None executes source, renders,
repairs, applies, records, certifies, or becomes runtime authority.

## Performance direction

Compilation moves parsing, name resolution, structural checks, and context
analysis out of request-time execution. Runtime targets direct ordinary PHP
calls and exact content-addressed invalidation suitable for OPCache. These are
design goals only. A later row must measure representative cold/warm builds and
renders before any latency, throughput, memory, or superiority claim.

## Excluded scope

Phase 18A selects no third-party template engine, frontend framework, Node or
JavaScript runtime, hydration, assets, bundler, CSS/image pipeline, mobile or
desktop UI, forms, validation, localization, mail, queues, scheduling, cache,
deployment, live reload, watcher, daemon, installer, marketplace, remote
template registry, compatibility mode, Stable/1.0 status, accessibility
certification, browser certification, security certification, or production
readiness.

## Related material

- [ADR 0179](../adr/0179-server-rendered-presentation-and-compiled-view-boundary.md)
- [ADR 0180](../adr/0180-context-aware-output-safety-and-presentation-lifecycle.md)
- [Phase 18 roadmap](phase-18-roadmap.md)
- [View authoring](../engineering/view-authoring.md)
- [Progressive architecture](progressive-architecture.md)
- [Routing model](routing-model.md)
- [Runtime model](runtime-model.md)
- [Application composition and build](application-composition-and-build.md)
- [Identity and Access Security](identity-and-access-security.md)
- [Browser-session security](browser-session-security-and-capability-enforcement.md)
- [Observability and Operational Evidence](observability-and-operational-evidence.md)
