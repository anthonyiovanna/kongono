# ADR 0036: Accessible Trusted-Developer HTML Diagnostic Page

## Status

Accepted

## Date

2026-08-14

## Implementation

Phase 0Z implements this decision through [ADR
0037](0037-trusted-developer-html-diagnostic-renderer-implementation.md). The
pure Experimental renderer, exact template and CSS, final-boundary escaping,
two golden fixtures, and ten focused tests now exist. Trusted activation,
serving and HTTP policy, production presentation, optional providers, and other
formats remain unimplemented.

Phase 1A accepts the fail-closed activation architecture in [ADR
0038](0038-fail-closed-trusted-developer-diagnostic-activation.md). Both
**trusted bootstrap permission** for a future immutable adapter instance and
independent **adapter-controlled delivery permission** for the current boundary must be
affirmative before presentation access, projection, rendering, or delivery.
This policy has no enforcement implementation and does not change the renderer,
HTML grammar, fixtures, tests, or production separation.

Phase 1C implements only the typed trusted-bootstrap permission in the separate
Diagnostic Activation package through [ADR
0039](0039-typed-trusted-developer-bootstrap-permission-foundation.md). The
renderer does not depend on that package and gains no authorization, delivery,
configuration, adapter, or transport behavior.

Phase 1D implements only the neutral typed adapter-controlled delivery decision
through [ADR
0040](0040-typed-adapter-controlled-delivery-permission-foundation.md). The
future adapter still owns evidence and evaluation. The renderer remains
unchanged, separate, directly callable, and unable to enforce the two-gate rule.

Phase 1E implements the pure two-permission calculation through [ADR
0041](0041-pure-two-permission-trusted-presentation-eligibility.md). The HTML
renderer remains unchanged and cannot inspect or enforce that value; evidence,
adapter composition, transport, and presentation access remain outside it.

## Context

Phase 0X implements the pure Experimental trusted-developer projection in the
`kongono/diagnostic-presentation` leaf. One
`Kongono\DiagnosticPresentation\Developer\TrustedDeveloperDiagnostic` is an
immutable detached snapshot of the package owner, identifier, title, operation,
violated invariant, remediation, and every canonically ordered bounded field
with its exact kind, Safe or Protected disclosure, and truncation state.

That projection deliberately emits no markup and performs no output escaping.
Kongono needs a first concrete presentation boundary that makes a known failure
immediately understandable without remote services, AI, source crawling, or
mandatory client-side behavior. Selecting a renderer also requires an exact
HTML injection boundary, accessibility baseline, deterministic resource bounds,
and an explicit separation between output encoding and disclosure authorization.

This decision originally recorded accepted architecture only. Phase 0Y
implemented no renderer class, HTML page, stylesheet, template, fixture,
trusted-mode selector, adapter, transport, HTTP response, or production surface.

## Decision

### Ownership and planned API

The existing `kongono/diagnostic-presentation` leaf owns the planned
Experimental renderer:

```php
namespace Kongono\DiagnosticPresentation\Developer\Html;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DiagnosticPresentation\Developer\TrustedDeveloperDiagnostic;

#[Api(ApiStatus::Experimental)]
final readonly class TrustedDeveloperHtmlRenderer
{
    public function render(TrustedDeveloperDiagnostic $diagnostic): string;
}
```

The planned class has no declared constructor, instance or static properties,
additional public, protected, or magic methods, inheritance point, or consumer
implementation point. Each synchronous call returns a fresh complete HTML5
document derived only from the supplied immutable diagnostic. It mutates
nothing, retains no input or result, and uses no cache, registry, singleton,
global, static local, ambient state, service locator, or I/O.

The renderer accepts no exception, package marker, Diagnostics value, array,
map, arbitrary object, raw HTML, template, configuration, request, response,
container, or service locator. It does not authorize, project, discover,
reclassify, reorder, omit, merge, redact, enrich, log, transport, or integrate
the diagnostic. The input model already owns those structural invariants.

### Authorization and production separation

The page is a trusted-developer surface because its input contains both Safe
and Protected fields. Its name and its escaping do not prove authorization. A
future server-controlled outer boundary must explicitly authorize trusted
developer use before calling the projector or renderer. The renderer may not
activate or infer trust from client, request, user, database, provider,
environment-string, hostname, heuristic, exception, context, AI, global,
superglobal, container, thread-local, fiber-local, cached, process-wide, or
previous-operation state.

HTML escaping prevents markup injection; it does not declassify Protected data
or make it suitable for an untrusted audience. Future production presentation
requires a separately designed deny-by-default model with explicit allowlists.
It may not consume this trusted model and redact it afterward.

### Standalone document and information architecture

The planned result is one standalone server-rendered HTML5 document containing,
in semantic order, `<!doctype html>`, `<html lang="en">`, a `<head>` with UTF-8
and responsive viewport metadata, a dynamic escaped title, one static embedded
stylesheet, a static skip link to `#diagnostic-main`, one
`<main id="diagnostic-main">`, the five visible regions below, and closing
document elements. It is useful without JavaScript, remote assets, network
access, or an AI service.

The document title is exactly:

```text
{identifier} — {title} — Kongono diagnostic
```

The two dynamic components are escaped separately. The separators and
`Kongono diagnostic` are static template text.

The five visible regions and their order are exact:

1. **Diagnostic header** — static product label `Kongono diagnostic`, exact
   identifier, exactly one page-level `h1` containing the precise title, and
   the owner label.
2. **What happened** — an `h2` with exactly that text and the exact operation.
3. **Why it happened** — an `h2` with exactly that text and the exact violated
   invariant or expectation.
4. **Context** — an `h2` with exactly that text and every projected field in
   canonical order using a semantic description list.
5. **How to fix it** — an `h2` with exactly that text and the exact remediation.

The exact owner labels are:

```text
kernel                 Kernel
application_graph      Application Graph
dependency_injection   Dependency Injection
```

An empty field list renders exactly:

```text
No additional diagnostic context was captured.
```

Every field visibly presents its fixed human label, canonical key, exact scalar
kind, exact disclosure as `Safe` or `Protected`, formatted value, and the word
`Truncated` if and only if `textWasTruncated` is `true`. The exact visible kind
labels are `text`, `integer`, and `boolean`. A false or null truncation state
shows no truncation indicator. Kind, disclosure, and truncation remain visible
text rather than meaning conveyed only through color, icons, tooltips, CSS,
position, or ARIA metadata.

The page adds no native message, exception class, cause, trace, source path or
excerpt, request or environment data, SQL, provider payload, log or telemetry
data, incident reference, documentation URL, localization key, transport data,
or arbitrary metadata.

### Output encoding and scalar formatting

Dynamic values may enter only escaped character data in normal element text or
the `<title>` element's RCDATA content. They may never determine or enter an
element or attribute name, any attribute value, CSS, selector, custom property,
URL, comment, doctype, raw-text element, script, style, executable behavior,
navigation, form behavior, or raw HTML branch. Every element, attribute, ID,
class, label, and stylesheet byte is static.

Each dynamic string destined for text content is escaped at the final HTML
output boundary using exactly:

```php
htmlspecialchars(
    $value,
    ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5,
    'UTF-8',
    true,
)
```

`double_encode` is deliberately true because input is represented text, not
pre-escaped markup. The renderer does not decode entities, normalize Unicode,
trim, case-fold, parse, sanitize, strip tags, or pre-escape elsewhere.
`ENT_SUBSTITUTE` gives deterministic replacement for malformed byte sequences
at this final grammar boundary.

Before escaping, scalar formatting is exact and locale-independent:

```text
text      exact represented string
integer   canonical locale-independent base-10 decimal text
boolean   exact lowercase token true or false
```

Every formatted value, including integer and boolean text, passes through the
same escaping call. The renderer applies no thousands separators, quotes, debug
syntax, JSON encoding, pretty printing, reflection, inference, or locale-
sensitive formatting.

### Accessibility and static design

The planned page uses native semantic `main`, `section`, heading, paragraph,
and description-list structure; exactly one `h1`; ordered, non-skipped `h2`
headings; and a keyboard-visible skip link. It adds no redundant ARIA role,
live region, modal, disclosure widget, or keyboard trap. Decorative elements
must not add meaning or noisy accessible names.

The static design must provide visible focus in forced-colors mode, sufficient
contrast in light, dark, and forced-colors environments, meaning independent of
color, readable reflow at 320 CSS pixels and 200 percent zoom without ordinary-
text page scrolling, aggressive wrapping without clipping or hidden overflow,
selectable diagnostic text, readable print output, and no animation,
transition, parallax, flashing, or motion dependency.

Exactly one local embedded static stylesheet uses a system UI font stack, a
centered responsive reading column, a restrained page and diagnostic card,
visible identifier and owner metadata, strong region hierarchy, bounded static
spacing/border/surface tokens, light and dark media queries, explicit forced-
colors handling, print adjustments, and legibility when CSS is unavailable.
There is no JavaScript, dynamic CSS, inline `style`, remote font, stylesheet,
image, icon, iframe, media, form, button, active content, refresh, redirect,
beacon, preload, prefetch, or speculative connection. The static stylesheet is
compatible with a future hash-based Content Security Policy, but this renderer
owns no HTTP header or policy.

Exact colors, dimensions, spacing, radii, shadows, static template whitespace,
stylesheet bytes, and golden fixtures remain for the later implementation
directive within this contract.

### Determinism, bounds, lifecycle, and failure

For the same immutable diagnostic and installed renderer implementation, output
is byte-identical. It depends on no time, random value, process, memory address,
host, operating system, locale, timezone, environment, request, filesystem,
network, source tree, documentation service, registry, cache, reflection, AI,
previous call, concurrency state, browser, or content negotiation.

The exact bounds are:

```text
input fields                          at most 16
static template plus stylesheet       at most 16 KiB
complete returned HTML                at most 96 KiB
time complexity                       O(field count + dynamic input bytes)
retained state after return            none
```

The renderer produces one complete string synchronously in memory. It selects
no recursion, streaming, chunking, buffering service, compression, source read,
template compilation, pooling, lifecycle registration, cleanup, cancellation,
or asynchronous behavior. Concurrent calls share no mutable renderer state.

No renderer-specific diagnostic catalog, exception family, fallback page,
nested renderer, or catch-all wrapping is selected. Malformed string bytes are
handled by the exact substitution escaping rule. Native engine failures, if
any, retain native identity. Simultaneous primary, projection, rendering, and
cleanup failure representation remains future work.

## Rationale

A pure renderer in the existing leaf keeps HTML grammar ownership at the final
presentation boundary while preserving package-local diagnostic meaning and
presentation-neutral values. A fixed five-region page makes the precise cause
and next action scannable. Native HTML semantics and a local static design keep
the page useful in constrained or offline environments.

Final-boundary escaping prevents diagnostic text from becoming markup, while a
separate outer authorization requirement preserves the harder disclosure rule:
escaped Protected data is still Protected. Deterministic stateless rendering
and hard bounds fit persistent workers without introducing runtime coupling.

## Consequences

Phase 0Z adds only the planned Experimental renderer contract and exact static
template within this architecture. It consumes only
`TrustedDeveloperDiagnostic`, preserves every projected field and
classification, and verifies exact HTML, escaping, accessibility, determinism,
and size behavior.

The renderer will create no new package edge, binding, lifecycle hook, Graph
participation, authorization mechanism, transport response, or production
surface. A future adapter owns transport concerns only after a future outer
boundary has explicitly authorized trusted developer use.

Phase 0Y changed documentation only. Phase 0Z implements the renderer class,
stylesheet grammar, and test-only fixtures and tests. The renderer returns a
document string but creates no served page. The activation boundary, adapter,
and production model do not exist.

## Rejected alternatives

- Rendering directly from arbitrary `Throwable` values or package markers is
  rejected because projection owns the closed developer model.
- Placing HTML behavior in Diagnostics or an originating package is rejected
  because Diagnostics remains presentation-neutral and semantic packages do not
  own cross-package presentation.
- Letting the renderer activate or infer trusted mode is rejected because trust
  belongs to an explicit server-controlled outer boundary.
- Reusing the trusted model for production and redacting after projection is
  rejected because production needs a separate deny-by-default model.
- Unescaped interpolation or dynamic data in attributes, CSS, URLs, scripts, or
  raw HTML is rejected because dynamic data belongs only in escaped text.
- A template engine, component framework, client-side application, or required
  JavaScript is rejected because the first page is a fixed server-rendered
  document.
- Remote assets, fonts, documentation calls, AI calls, source crawling, and
  runtime reflection are rejected for deterministic offline usefulness.
- Discovering raw exception messages, traces, source excerpts, requests,
  environment dumps, SQL, or provider payloads is rejected because those values
  are absent from the input and have separate disclosure concerns.
- Ambient, global, cached, retained, or cross-call renderer state is rejected
  for persistent-runtime isolation.
- Localization is rejected for the first English renderer.
- Copy and export controls are rejected until separate safe formats exist.
- HTTP status, CSP, content type, cache, and transport ownership inside the pure
  renderer are rejected because a future adapter owns those concerns.

## What remains open

- Server configuration validation and reload, outer adapter, delivery-
  authorization, and combined enforcement mechanisms.
- HTTP response ownership, content type, status, cache, and CSP headers.
- Safe copy and export formats.
- Optional cause-chain, source, stack-frame, and version-matched documentation
  providers under separate disclosure design.
- A separate deny-by-default production model and explicit allowlists.
- CLI, JSON/problem details, and other renderers or transports.
- Localization.
- Logging, telemetry, incidents, serialization, and cross-process forms.
- Simultaneous primary, projection, rendering, and cleanup failure behavior.
