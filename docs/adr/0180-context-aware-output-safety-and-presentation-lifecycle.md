# ADR 0180: Context-Aware Output Safety and Presentation Lifecycle

- Status: Accepted
- Date: 2026-09-12
- Scope: Phase 18A only

## Context

Server-rendered output combines fixed source structure with application data.
A generic HTML escape function is insufficient because HTML text, quoted
attributes, URLs, JavaScript, CSS, event handlers, and raw markup have
different safety rules. Runtime guessing and regex sanitization cannot prove
which interpretation a browser will apply.

Rendering also runs inside persistent PHP processes. Ambient buffers, mutable
template globals, retained request values, or destructor-based cleanup could
cross operation boundaries or turn a failed partial render into apparent
success.

This decision refines the architecture in [ADR 0179](0179-server-rendered-presentation-and-compiled-view-boundary.md).
It accepts doctrine only and implements no escaper, context analyzer, trusted
markup type, source map, renderer, runtime, diagnostic, or HTTP behavior.

## Decision

### Compile-time declared contexts

Escaping is contextual and safe by default. The compiler must know the output
context at build time; rendering may not infer it from runtime values. Plain
strings are always data and never markup.

Phase 18B-C must explicitly select the first closed contexts. The default
direction is HTML text and quoted HTML attribute contexts. URL, JavaScript,
CSS, raw-markup, and event-handler contexts are denied until a separately
authorized row defines typed semantics, grammar restrictions, composition
rules, bounds, diagnostics, and independent tests. Unsupported or ambiguous
context fails closed during build rather than being guessed at render time.

One generic `escape()` promise, runtime context guessing, regex sanitization,
blacklists, automatic URL acceptance, and caller-supplied bypass flags are not
accepted safety mechanisms.

### Trusted markup and composition

Any future trusted-markup value must be explicit, narrowly context-scoped,
provenance-aware, and non-forgeable from an ordinary untrusted string at the
render boundary. Construction authority must be separate from consumption.
Concatenation with an untrusted value must not preserve a trusted status or
silently disable escaping. Phase 18A does not authorize such a type.

Layouts, includes, components, loops, and conditional structure—if later
selected—must preserve compiler-known context across boundaries and remain
inside one exact compiled source set. Dynamic names, arbitrary include paths,
context-changing fragments, and caller-selected raw rendering are denied.

### Data minimization and disclosure

Template names, source locations, input values, rendered fragments, private
paths, stack arguments, credentials, secrets, session material, CSRF evidence,
provider payloads, and security-policy bodies are Protected or denied by
default. Redaction is not collection permission.

Package diagnostics carry stable identifiers, actionable remediation, and
only bounded schema-allowlisted context. Trusted developer presentation may
identify a logical template and bounded source location under the existing
independent activation and delivery-disclosure rules. Production responses
remain generic and non-disclosing. Rendering does not create a new diagnostic
activation path.

### Operation lifecycle

Each render is one synchronous operation with explicit immutable inputs,
finite state, and deterministic retirement. The runtime owns temporary state
after acceptance and releases it on success, invalid input, bound failure,
render failure, adaptation failure, cancellation if later selected, reentry,
and cleanup failure.

No template global state, mutable singleton registry, static current render,
request singleton, implicit native output buffer, shutdown hook, destructor
cleanup, background worker, retained fragment cache, or ambient clock is
permitted. One renderer may serve sequential and overlapping operations only
through immutable exact-current evidence and operation-local state.

Output byte count, nesting depth, iteration count, component count, and any
temporary retained bytes are explicitly finite. Bounds are checked before an
unsafe allocation or emission where possible. Partial output is never returned
as a successful render. Primary and cleanup failures remain distinguishable;
cleanup cannot hide the primary failure or fabricate successful retirement.

### HTTP and browser-security separation

A completed render result contains presentation bytes and bounded metadata,
not response authority. A future HTTP adapter receives application-owned
status, headers, content type, and security policy explicitly. The renderer
does not invent Content Security Policy, CSP nonces, Subresource Integrity,
URL policy, asset policy, cookies, sessions, CSRF evidence, authentication,
authorization, redirects, cache policy, or browser compatibility.

## Security consequences

Context-declared compilation makes unsupported browser interpretation visible
before runtime and keeps ordinary data escaped by default. The design reduces
ambient authority and cross-operation leakage, but it is not a security
certification. Browser parsing, future grammar design, trusted-markup
construction, URL semantics, and HTTP policy still require independent tests
and separately authenticated decisions.

Generated PHP is trusted in-process code, not a sandbox. Exact-current
validation and a closed source/value surface constrain what Kongono accepts;
they do not remove PHP's process capabilities.

## Failure semantics

Future failures distinguish at least unsupported context, ambiguous context,
invalid schema/value, stale artifact, source-map mismatch, exceeded bound,
render failure, output retirement failure, and HTTP adaptation failure.
Diagnostics expose no template value or rendered byte by default. A malformed,
stale, mixed-version, or partially retired operation fails closed.

## Performance consequences

Compile-time context analysis and direct generated calls remove the need for
runtime parsing or generalized escaping decisions. Explicit output bounds make
resource behavior reviewable. No benchmark or cost claim is accepted here;
later cold/warm measurements must include escaping, bounds, failure, and
overlap cases.

## Developer experience consequences

Authors get a simple rule: use ordinary values and let the declared context
escape them; unsupported contexts are compile errors. Any future explicit
trusted value carries visible ceremony because bypassing ordinary escaping is
security-sensitive. Trusted developer diagnostics remain actionable without
changing production disclosure.

## API status

The doctrine in this ADR is Accepted. Future application-selected safety and
render contracts begin Experimental. Context analyzers, escapers, generated
helpers, trusted-markup constructors, source maps, and lifecycle machinery are
Internal by default. No Stable, 1.0, production-readiness, browser-security,
accessibility, or compliance claim is made.

## Alternatives and tradeoffs

- Escape every value with one HTML function: rejected because contexts differ.
- Guess context at runtime: rejected because source structure, not data,
  determines browser interpretation.
- Sanitize after rendering: rejected because regex or generic post-processing
  cannot reconstruct safe browser contexts reliably.
- Permit raw strings by convention: rejected because ordinary data could gain
  markup authority accidentally.
- Use a global output buffer or mutable template scope: rejected because
  partial output, cleanup, overlap, and persistent-runtime isolation become
  ambiguous.
- Let the renderer own CSP, sessions, CSRF, or headers: rejected because those
  are separate application and security authorities.

## Related authorities

- [ADR 0179](0179-server-rendered-presentation-and-compiled-view-boundary.md)
- [Server-rendered presentation](../architecture/server-rendered-presentation.md)
- [View authoring](../engineering/view-authoring.md)
- [Diagnostic disclosure](0028-bounded-diagnostic-context-and-disclosure.md)
- [Persistent runtime](0006-persistent-runtime-first.md)
- [Identity and Access Security](../architecture/identity-and-access-security.md)
- [Browser-session security](../architecture/browser-session-security-and-capability-enforcement.md)
- [Runtime model](../architecture/runtime-model.md)
