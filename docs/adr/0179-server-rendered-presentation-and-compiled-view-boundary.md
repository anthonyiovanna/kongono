# ADR 0179: Server-Rendered Presentation and Compiled View Boundary

- Status: Accepted
- Date: 2026-09-12
- Scope: Phase 18A only

## Context

Kongono has accepted HTTP messages, routing, a PSR-15 application pipeline,
generated Composition, diagnostics, security, persistence, structured CLI,
the Application Graph, extensions, and governed external services. It has no
supported application view or template boundary. Applications can construct
PSR-7 responses directly, but the framework offers no explicit, deterministic
path from application-owned presentation data to server-rendered HTML.

Adding that path carelessly would allow templates to become hidden
controllers, service locators, security authorities, runtime parsers, or
ambient request state. It could also make API-only, CLI, worker, agent, and
headless applications depend on presentation machinery they do not need.

Phase 18A accepts architecture only. No view contract, source grammar,
compiler, generated PHP, loader, renderer, command, HTTP adapter, application
workflow, skeleton, cache, watcher, asset pipeline, or runtime behavior exists
as a result of this decision.

## Decision

### Optional upper capability and ownership

Server-rendered presentation is an optional upper capability. Applications
that do not render views install no Phase 18 package. Application code selects
a logical view, supplies explicit bounded presentation data, and decides when
to render. Routing selects a handler; it does not select a template. A handler
may render, return data, delegate to application logic, or construct a response
without a view.

Future neutral view contracts remain independent of PSR-7. Mapping one
completed neutral render result to an HTTP response belongs to a separate
upper adapter under application-owned status, header, and browser-security
policy. A template, compiled artifact, logical identity, render result, or
rendered byte sequence is evidence only. None establishes identity,
authentication, authorization, capability, policy, Graph truth, or an
application effect.

### Deterministic compiled production path

The preferred production path compiles one explicit application-owned source
set during a separately invoked build or check step. The output is
deterministic, content-addressed ordinary PHP plus immutable metadata. A later
runtime may use only one explicit, contained, exact-current artifact whose
identity matches the supplied source evidence.

Request-time discovery, directory scanning, newest-file selection, Reflection
discovery, parsing, interpretation, compilation, `eval`, remote templates, and
network loading are prohibited. Development convenience must invoke the same
deterministic build/check pipeline explicitly; it may not create a second
permissive runtime mode.

Generated view code uses direct ordinary PHP calls and immutable metadata. It
may not resolve services, read an ambient container, access globals,
environment variables, request singletons, sessions, credentials, filesystem
state, or clocks. Generated PHP remains trusted in-process code rather than a
sandbox, so its source set, exact identity, loading boundary, and generated
surface require strict validation.

### Closed data boundary

A future render request names one canonical logical view identity, one
exact-current artifact identity, one bounded declared context, and explicit
render options. A logical identity is not a path and cannot be used to select
an arbitrary local file.

Inputs are closed, schema-declared, bounded presentation values. Missing,
extra, duplicate, unsupported, stale, mixed-version, or type-invalid values
fail before rendering. Templates receive no arbitrary object graph. The first
contract direction is a closed scalar, list, and record presentation domain or
separately accepted immutable view models. Domain entities, repositories,
services, containers, request objects, capability ports, and security context
do not enter templates by implication.

If layouts, includes, or components are later selected, they resolve only
inside the same compiled closed source set and are subject to explicit cycle,
depth, count, iteration, and output-byte limits.

### Dependency and package direction

If separately authorized, Phase 18 proceeds through optional upper packages:
neutral `kongono/view-contract`, deterministic `kongono/view-build`,
exact-current `kongono/view-runtime`, explicit `kongono/view-http`, supported
`kongono/view-application`, and a separate visibility-safe inspection leaf.
Each begins Experimental. Lower HTTP, routing, Kernel, security, Composition,
Graph, persistence, and developer-intelligence packages do not depend outward
on view packages.

The permitted conceptual direction is:

```text
application handler and immutable presentation data
  -> logical view identity plus exact-current compiled evidence
  -> optional neutral render contract
  -> exact-current compiled-view runtime
  -> completed neutral render result
  -> optional explicit PSR-7 response adaptation
```

The arrows describe composition after validation, not authority or current
implementation.

### Developer experience

The intended developer path is explicit: create or select a source, declare
its input schema, run build or write-free check, construct an immutable view
model, render by logical identity, and explicitly adapt the completed result
to HTTP when desired. Build failures identify the source and rule only through
trusted developer disclosure. Runtime failures distinguish stale artifact,
invalid context, exceeded bound, rendering failure, and HTTP adaptation
failure without exposing protected values.

Human and versioned machine command forms must derive from the same neutral
outcome. Check or `--plan` behavior is non-mutating; publication authority is
explicit; automation does not prompt silently. Chie may explain accepted
doctrine and propose reviewable documentation changes, Hantei may independently
verify, and Oshie may teach. None becomes compiler, renderer, runtime authority,
or self-certifier.

## Security

Plain strings are data, never markup. Contextual output safety, trusted-markup
rules, diagnostics, and lifecycle are controlled by [ADR 0180](0180-context-aware-output-safety-and-presentation-lifecycle.md).
Templates receive no ambient authority. Content Security Policy, nonce
creation, asset integrity, URL policy, browser security headers, sessions,
CSRF, authentication, and authorization remain separate application and
security concerns.

## Lifecycle, concurrency, and failure

Future rendering is operation-scoped, synchronous, deterministic, bounded,
and stateless across calls. Immutable exact-current evidence must support
sequential and overlapping operations without template globals, mutable
singletons, implicit output buffers, shutdown hooks, destructor cleanup,
background workers, or retained request state.

Partial output is not successful output. Temporary state is released on every
path, and primary plus cleanup failure truth is preserved. Failures use
package-owned actionable diagnostics with bounded safe context; production
presentation remains generic and non-disclosing.

## Performance

Compilation is intended to move parsing, name resolution, structural
validation, and output-context analysis out of request-time execution. Runtime
direction favors direct ordinary PHP calls without scanning, Reflection,
parsing, service location, or a generalized expression interpreter.
Content-addressed artifacts can support OPCache and exact invalidation.

These are architectural directions, not benchmark results. Phase 18A makes no
latency, throughput, memory, zero-overhead, superiority, or production-capacity
claim. A later authorized row must measure representative cold and warm builds
and renders before performance expectations are documented.

## API status

This architecture is Accepted doctrine. Future application-selected contract
surfaces begin Experimental. Parsers, compiler internals, generated-code
helpers, loaders, contextual escapers, source maps, and lifecycle helpers are
Internal by default. Nothing in Phase 18 is Stable, 1.0,
production-certified, browser-certified, or a compatibility promise.

## Consequences

Kongono now has an accepted direction for pleasant server-rendered HTML that
preserves optionality, explicit application ownership, build-before-runtime
determinism, exact-current loading, and existing authority boundaries. The
cost is a deliberately narrow value domain, explicit schema work, build steps,
strict output contexts, and separate HTTP adaptation.

Phase 18B-H remain Planned and separately unauthorized. Phase 18A changes no
production behavior or package topology.

## Alternatives and tradeoffs

- Runtime template parsing was rejected because it creates request-time
  discovery, drift, performance, and persistent-runtime state risks.
- Arbitrary PHP templates were rejected as the default contract because they
  admit ambient scope and cannot enforce a closed presentation domain.
- A mandatory full-stack view bundle was rejected because non-HTML
  applications remain first-class.
- Routing-owned template selection was rejected because it conflates matching
  with application presentation policy.
- PSR-7 in the neutral view contract was rejected because rendering and HTTP
  response policy are distinct.
- A service-locating template container was rejected because dependencies and
  authority would become hidden.
- Selecting Twig, Blade, Latte, Plates, or another third-party engine was
  deferred; Phase 18A authorizes no dependency or compatibility commitment.

## Related authorities

- [ADR 0180](0180-context-aware-output-safety-and-presentation-lifecycle.md)
- [Server-rendered presentation](../architecture/server-rendered-presentation.md)
- [Phase 18 roadmap](../architecture/phase-18-roadmap.md)
- [View authoring](../engineering/view-authoring.md)
- [Routing model](../architecture/routing-model.md)
- [Runtime model](../architecture/runtime-model.md)
- [Application composition and build](../architecture/application-composition-and-build.md)
- [Generated wiring](../engineering/generated-wiring.md)
- [ADR 0068](0068-supported-application-composition-and-build-boundary.md)
- [ADR 0069](0069-generated-typed-wiring-and-no-runtime-service-location.md)
- [ADR 0073](0073-generated-typed-wiring-and-lifecycle-boundary.md)
