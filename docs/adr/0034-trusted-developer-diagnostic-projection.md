# ADR 0034: Trusted Developer Diagnostic Projection

## Status

Accepted

## Date

2026-08-14

## Implementation

Implemented by Phase 0X through [ADR 0035](0035-trusted-developer-diagnostic-projection-implementation.md).
Phase 0X creates only the leaf package, immutable trusted-developer values, pure
projector, and package-local context-free failure foundation. Phase 0Z adds the
pure trusted HTML renderer without changing projection. Trusted activation,
production presentation, and runtime integration remain future work.

## Context

Before Phase 0X, Kongono had five implemented packages. API Metadata owns compatibility
classification; Diagnostics owns presentation-neutral bounded schemas and
immutable values; and Kernel, Application Graph, and Dependency Injection each
retain a separate Public exception marker while exposing exact package-owned
context for their 6, 11, and 25 deliberate failures. The catalog contains 53
unique identifiers when the 11 context-free Diagnostics construction failures
are included.

Those values alone established no presentation boundary. At that point, no
universal diagnostic interface, trusted-mode selector, renderer, error page,
transport adapter, logger, telemetry hook, incident system, source provider, or
documentation registry existed. A first developer-facing
model needs explicit ownership, trust, information, and lifetime boundaries
before any output grammar or runtime integration is implemented.

## Decision

Implement the Composer package `kongono/diagnostic-presentation`, with PHP
namespace `Kongono\DiagnosticPresentation\`, as the leaf integration and
presentation boundary. Phase 0X creates its package directory, manifest, four
Experimental developer concepts, and context-free failure foundation. The
package owns the implemented developer projection and is the accepted future
owner of presentation models and renderers. Diagnostics remains presentation-neutral and continues to own no
developer model, renderer, trusted-mode logic, HTML, JSON, CLI formatting, or
transport policy. Diagnostic identifiers, titles, remediation, context schemas,
throw-site values, exception meaning, and recovery boundaries remain with their
current owners.

The implemented dependency direction is exactly:

```text
diagnostic-presentation -> api-metadata
diagnostic-presentation -> diagnostics
diagnostic-presentation -> kernel
diagnostic-presentation -> application-graph
diagnostic-presentation -> dependency-injection
```

No existing package may depend on Diagnostic Presentation. There is no reverse
edge, service registration, Kernel binding, DI definition, Graph node, ambient
discovery, or universal exception boundary. The graph remains acyclic. The
framework root consumes the leaf through its exact `@dev` requirement for
monorepo integration and testing; this creates no reverse package edge or
runtime binding.

The first implemented model is a trusted-developer boundary. Trust is a
runtime-composition decision supplied by explicit server-controlled
configuration to a future outer adapter or composition root. It may never be
enabled, widened, or inferred from query, route, body, header, cookie, session,
form, user, database, provider, or other client-controlled data; exception
messages, class names, context fields, environment strings, hostnames, or
heuristics; AI models, prompts, responses, tools, or recommendations; or global,
superglobal, container, service-locator, thread-local, fiber-local, static,
mutable process-wide, or previous-operation state. The pure projection cannot
activate trust or prove caller authorization. A later outer integration must
authorize Protected projection before projection or rendering.

Implement exactly these Experimental concepts:

```text
Kongono\DiagnosticPresentation\Developer\DeveloperDiagnosticOwner
Kongono\DiagnosticPresentation\Developer\TrustedDeveloperDiagnostic
Kongono\DiagnosticPresentation\Developer\TrustedDeveloperDiagnosticField
Kongono\DiagnosticPresentation\Developer\TrustedDeveloperDiagnosticProjector
```

The exact owner vocabulary is `kernel`, `application_graph`, and
`dependency_injection`. The projector accepts exactly one of these
existing contextual markers:

```text
Kongono\Kernel\Exception\ExceptionInterface
Kongono\ApplicationGraph\Exception\ExceptionInterface
Kongono\DependencyInjection\Exception\ExceptionInterface
```

This union creates no universal marker. It excludes arbitrary `Throwable`, the
context-free Diagnostics marker, raw metadata strings, arrays, maps, and generic
objects. Direct arbitrary construction is not part of the Public calling
contract. A source satisfying more than one marker is ambiguous and must be
rejected before any metadata or context accessor is called. Phase 0X implements
the exact package-local, context-free construction failures and identifiers
recorded in ADR 0035.

One `TrustedDeveloperDiagnostic` contains only the derived exact owner;
stable identifier; precise projected title; fixed context operation; fixed
violated invariant or expectation; projected remediation; and context fields in
canonical schema order. One `TrustedDeveloperDiagnosticField` contains
only canonical key, fixed label, exact `DiagnosticValueKind`, already-bounded
`string|int|bool` value, exact Safe or Protected disclosure, and the nullable
text-truncation state supplied by `DiagnosticField`.

The projector copies metadata and values into an immutable detached snapshot.
It retains no exception, context, field, mutable list, cache, registry, or
cross-operation state. Returned lists are detached and preserve canonical order.
Both Safe and Protected fields are included because the model is expressly for
a trusted developer boundary, and each disclosure classification is preserved
exactly. Projection may not promote, reclassify, merge, omit, redact, sanitize,
or infer disclosure. A production adapter must use a separately designed
deny-by-default model with explicit allowlists; it must not reuse this trusted
model and redact afterward.

The source marker determines the only allowed identifier prefix:
`KGN-KERNEL-`, `KGN-GRAPH-`, or `KGN-DI-`; the suffix is exactly four ASCII
decimal digits. Title is non-empty valid UTF-8 of at most 160 bytes and
remediation is non-empty valid UTF-8 of at most 256 bytes. Operation and
invariant retain Diagnostics' existing 160- and 256-byte bounds. Context remains
limited to 16 fields and text remains the already-bounded deterministic ASCII
representation of at most 512 bytes. Owner, identifier, title, remediation,
context, and each field are each read at most once into local values before
construction. Catalog prose is validated before storage, every list is
detached, no catch-all wrapper is used, and an accessor-thrown native
`Throwable` propagates unchanged.

The current 42 Kernel, Graph, and DI identifiers, titles, and remediations fit
these bounds. Boundary validation protects against unsupported custom marker
implementations; it does not turn consumer-defined metadata into a Kongono-owned
catalog entry.

The projection performs no secret detection and makes no sanitization or output-
escaping claim. Forbidden material remains excluded at schema ownership and
capture time. It contains no native message, cause, trace, class name, source
path or excerpt, stack frame, request or operation envelope, header, body,
environment or configuration value, SQL, provider payload, log data, telemetry
attribute, incident reference, documentation URL, localization key, arbitrary
extension metadata, or transport data.

The accepted future developer information architecture is:

```text
Diagnostic header     identifier + precise title + owning package
What happened         fixed operation
Why it happened       fixed violated invariant or expectation
Context               ordered labeled fields with disclosure and truncation state
How to fix it          projected remediation
```

Projection remains separate from rendering. A later renderer must be readable,
accessible, server-deterministic, bounded, and useful without mandatory client-
side JavaScript, but this decision selects no HTML, CSS, template, JavaScript,
Markdown, JSON, terminal grammar, clipboard action, color, typography,
component library, content type, HTTP status, problem-details format, or CLI
exit code. Escaping belongs to the later renderer's exact output grammar; the
projection neither pre-escapes nor emits display markup.

### Phase 0Y amendment

[ADR 0036](0036-accessible-trusted-developer-html-diagnostic-page.md) selects the
planned HTML consumer as
`Kongono\DiagnosticPresentation\Developer\Html\TrustedDeveloperHtmlRenderer`.
It consumes only `TrustedDeveloperDiagnostic` and owns exact escaping at the
final HTML character-data boundary. It preserves the projection's five regions,
canonical fields, kinds, Safe/Protected classifications, and truncation state.
Phase 0Y did not implement the renderer. A future explicit server-controlled
outer boundary still owns authorization before projection or rendering; escaping is
not authorization or declassification. Production remains a separate deny-by-
default model.

### Phase 0Z implementation note

[ADR 0037](0037-trusted-developer-html-diagnostic-renderer-implementation.md)
implements the selected pure renderer, exact static HTML/CSS grammar,
final-boundary escaping, two golden fixtures, and focused tests. It displays the
exact projected title and projected remediation already in the trusted model.
Trusted activation, serving, production presentation, optional providers, and
other formats remain future outer-boundary work.

### Phase 1A architecture note

[ADR 0038](0038-fail-closed-trusted-developer-diagnostic-activation.md)
accepts two independent permissions outside this package: **trusted bootstrap permission**
fixed for one future adapter instance and **adapter-controlled delivery permission**
for the current delivery. Both must be affirmative before
presentation access, projection, field copying, rendering, or delivery. This
projector remains a low-level pure Experimental API; direct callability does not
authorize its use. Phase 1A implements no activation, configuration, adapter,
transport, production, or runtime mechanism and changes no projection behavior.
Enforcement is unimplemented.

### Phase 1C implementation note

[ADR 0039](0039-typed-trusted-developer-bootstrap-permission-foundation.md)
implements the first permission value in the separate Diagnostic Activation
package. Diagnostic Presentation does not depend on that package and retains no
authorization or configuration responsibility. Delivery permission, combined
enforcement, adapters, transports, and production presentation remain future.

### Phase 1D implementation note

[ADR 0040](0040-typed-adapter-controlled-delivery-permission-foundation.md)
implements the second neutral typed input in Diagnostic Activation. Diagnostic
Presentation still does not depend on that package or enforce authorization.
The future adapter retains delivery evidence and evaluation, and the combined
gate, transport, and production presentation remain absent.

### Phase 1E implementation note

[ADR 0041](0041-pure-two-permission-trusted-presentation-eligibility.md)
implements the pure combined AND calculation in Diagnostic Activation. This
package still does not depend on Diagnostic Activation or enforce eligibility;
the projector remains directly callable and unchanged.

## Rationale

A leaf package can integrate the three package-local recovery boundaries without
making presentation a responsibility of Diagnostics or creating reverse
dependencies. A pure, bounded, detached projection gives future renderers one
deterministic developer-facing model while preserving semantic ownership,
disclosure classification, and persistent-runtime isolation. Keeping trust at a
server-controlled outer boundary prevents client, ambient state, or AI output
from becoming authorization.

## Consequences

The implementation preserves the exact one-way package graph, marker
union, owner tokens, projected properties, validation bounds, single-read
behavior, detachment, native accessor-failure propagation, and absence of
ambient state, I/O, and caches. It must test ambiguous multi-marker rejection
before accessor calls and keep its construction failures package-local and
context-free.

The trusted developer model may contain classified Protected values after a
future outer integration authorizes its use. Calling a projector is not
authorization. Production presentation requires a separate model and policy.
Phase 0W changed documentation only. Phase 0X creates the executable pure
projection contract without implementing activation, rendering, production
policy, transport, or runtime integration.

## Rejected alternatives

- Adding presentation behavior to `kongono/diagnostics` is rejected because it
  must remain presentation-neutral.
- Adding presentation to Kernel, Application Graph, or Dependency Injection is
  rejected because none owns cross-package rendering.
- A universal exception or diagnostic marker is rejected because recovery
  remains package-local.
- Accepting arbitrary `Throwable`, strings, arrays, maps, or metadata bags is
  rejected because the source and projected schema must remain exact.
- A global or ambient projector, static registry, cache, or retained current
  operation is rejected for persistent-runtime isolation.
- Request-, environment-string-, heuristic-, or AI-controlled trusted mode is
  rejected because trust is explicit server-controlled runtime composition.
- Reusing the trusted developer model for production and redacting afterward is
  rejected because production requires a separate deny-by-default model.
- Including native messages, traces, source, request, environment, or transport
  data now is rejected because their providers and disclosure rules are open.
- Implementing HTML, HTTP, or CLI before the projection boundary is rejected as
  premature output coupling.
- Empty or speculative package, manifest, namespace, type, test, or source stubs
  were rejected in Phase 0W; Phase 0X creates only the exact implemented
  projection boundary authorized by ADR 0035.

## What remains open

- Server configuration validation and reload, outer adapter, delivery-
  authorization, and combined enforcement mechanisms.
- Safe copy and export formats.
- Native cause-chain policy.
- Version-matched documentation lookup.
- Source and stack-frame provider contracts.
- Production model, allowlists, transport mappings, incident references, and
  status or exit-code policy.
- Localization.
- Logging and telemetry.
- Simultaneous primary, context, projection, and rendering failure
  representation.
- Serialization and cross-process or cross-language representation.
