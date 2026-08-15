# ADR 0037: Trusted-Developer HTML Diagnostic Renderer Implementation

## Status

Accepted

## Date

2026-08-14

## Context

[ADR 0034](0034-trusted-developer-diagnostic-projection.md) and [ADR
0035](0035-trusted-developer-diagnostic-projection-implementation.md) establish
the immutable trusted-developer projection in the
`kongono/diagnostic-presentation` leaf. [ADR
0036](0036-accessible-trusted-developer-html-diagnostic-page.md) selects the
first concrete presentation grammar, its five visible regions, exact final-
boundary escaping, accessibility baseline, determinism, and resource bounds.

Before Phase 0Z, the projection existed but no renderer, HTML fixture, trusted
activation boundary, transport adapter, or production presentation existed.
Phase 0Z implements only the pure renderer selected by ADR 0036. It does not
authorize use of Protected data or connect rendering to a request or runtime.

## Decision

Implement the Experimental final readonly class
`Kongono\DiagnosticPresentation\Developer\Html\TrustedDeveloperHtmlRenderer`.
It has no constructor, property, constant, parent, interface, protected or magic
method, or retained state. Its sole public method is:

```php
public function render(TrustedDeveloperDiagnostic $diagnostic): string;
```

Each call reads the already-projected diagnostic and every field accessor once,
then returns a fresh complete HTML5 document. The renderer accepts no exception,
context marker, request, response, configuration, container, template, or raw
HTML. It performs no projection, authorization, discovery, reclassification,
redaction, I/O, logging, transport, or runtime integration.

The canonical document bytes are fixed by the renderer and the checked-in
`trusted-developer-html-full.html` and `trusted-developer-html-empty.html`
golden fixtures. They contain the exact static HTML template and embedded CSS,
LF-only layout, one final LF, and these five semantic regions in order:

```text
Diagnostic header     identifier + projected title + owning package
What happened         fixed operation
Why it happened       fixed violated invariant or expectation
Context               ordered labeled fields with disclosure and truncation state
How to fix it          projected remediation
```

Owner labels are exactly `Kernel`, `Application Graph`, and `Dependency
Injection`. Kind labels are exactly `text`, `integer`, and `boolean`.
Disclosure labels are exactly `Safe` and `Protected`. Text uses its represented
string, integers use locale-independent base-10 text, and booleans use `true` or
`false`. `Truncated` appears only when a text field's truncation state is true.
An empty field list uses the exact accepted empty-context sentence.

Every dynamic string enters only normal element character data or `<title>`
RCDATA and is escaped once at the final boundary with:

```php
htmlspecialchars(
    $value,
    ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5,
    'UTF-8',
    true,
)
```

`double_encode: true` ensures entity-looking represented input remains visible
literal text instead of becoming markup. Dynamic values never enter an
attribute, tag, ID, class, style, selector, URL, comment, doctype, raw-text
element, script, or raw-HTML branch.

The document uses English native HTML semantics, one `h1`, four ordered `h2`
elements, a static keyboard-visible skip link, semantic description lists,
visible field metadata, responsive wrapping, light and dark color schemes,
forced-colors focus handling, and print rules. It contains one embedded static
stylesheet and no JavaScript, animation, transition, form, remote resource, or
active/external surface.

Rendering is deterministic and uses work proportional to field count plus
dynamic input bytes. The existing projection bounds input to 16 fields and
bounded prose and values. The canonical static shell plus stylesheet remains at
most 16 KiB and the complete result remains at most 96 KiB. No state survives a
call, so concurrent and persistent-runtime use shares no mutable renderer data.

Exactly ten focused tests enforce reflection shape and Experimental status,
both golden outputs, owner labels, final-boundary escaping, field semantics and
order, stateless determinism, maximum bounds, semantic accessibility grammar,
and absence of active or external surfaces. Root API-status enforcement includes
the renderer as Experimental.

The package graph, Composer manifests, lockfile, autoloading, quality tooling,
originating failures, projection values, and existing package behavior do not
change. No renderer-specific diagnostic, exception, fallback, or catch-all
wrapper is introduced; native engine failures retain native identity.

Rendering is not trust authorization. The input may contain Protected fields.
A future explicit server-controlled outer boundary must authorize trusted use
before projection or rendering. Escaping prevents markup injection but neither
declassifies Protected data nor makes this trusted model suitable for
production. Production requires a separate deny-by-default model and explicit
allowlists.

## Rationale

A pure renderer in the existing leaf keeps HTML grammar at the final
presentation boundary without moving it into Diagnostics or an originating
package. Fixed bytes and golden fixtures make presentation changes explicit and
reviewable. Native semantic HTML and local static CSS keep the diagnostic useful
offline and without JavaScript, while exact escaping prevents projected content
from becoming markup. Stateless deterministic bounds fit persistent workers
without a runtime binding or cache.

## Consequences

Consumers that already possess an authorized `TrustedDeveloperDiagnostic` may
explicitly call the Experimental renderer and receive one complete standalone
document. The output preserves every projected field, classification, and
truncation state in canonical order.

The capability remains isolated. No request reaches it automatically, no
configuration enables it, no handler catches failures for it, and no HTTP or
production response is created. The two fixtures are test-only canonical byte
records, not runtime templates or public assets.

Phase 1A architecture note: [ADR
0038](0038-fail-closed-trusted-developer-diagnostic-activation.md) accepts the
two-gate fail-closed policy outside this renderer. **Trusted bootstrap permission**
for the future immutable adapter instance and independent
**adapter-controlled delivery permission** for the current delivery must both be
affirmative before presentation access, projection, rendering, or
delivery. The renderer remains unchanged, pure, low-level, and Experimental;
direct calls and escaped output do not establish authorization. No enforcement,
activation type, configuration reader, adapter, served page, or production
behavior is implemented.

## Rejected alternatives

- Rendering arbitrary exceptions, package markers, Diagnostics contexts, or
  metadata bags is rejected because projection owns the closed input model.
- Authorization inside the renderer is rejected because trust belongs to a
  future explicit server-controlled outer boundary.
- Reusing the trusted model for production is rejected because production needs
  a separate deny-by-default model and allowlists.
- Dynamic attributes, CSS, URLs, scripts, raw HTML, and active behavior are
  rejected because dynamic values belong only in escaped character data.
- Template engines, component frameworks, JavaScript, remote assets, AI,
  reflection, source crawling, filesystem, network, environment, locale, and
  time are rejected for deterministic offline rendering.
- State, caches, registries, service bindings, Application Graph nodes, and
  Kernel hooks are rejected because the renderer is a pure leaf value
  transformation.
- Renderer-specific exceptions, fallback pages, recursive rendering, and
  catch-all wrapping are rejected because native failure identity must remain
  visible.
- HTTP, CLI, JSON, RFC 9457, content type, status, cache, and CSP ownership in
  this class are rejected because those belong to future outer adapters.

## What remains open

- Exact typed bootstrap decision, server configuration validation and reload,
  outer adapter, and delivery-authorization mechanisms.
- HTTP response ownership, status, content type, cache, and CSP headers.
- Safe copy and export formats.
- Optional cause-chain, source, stack-frame, and version-matched documentation
  providers under separate disclosure design.
- A separate deny-by-default production model and explicit allowlists.
- Other renderers and transports, including CLI, JSON/problem details, and
  localization.
- Logging, telemetry, incidents, serialization, and cross-process forms.
- Simultaneous primary, projection, rendering, and cleanup failure behavior.
