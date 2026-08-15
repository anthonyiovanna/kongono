# CODEX IMPLEMENTATION DIRECTIVE - KONGONO PHASE 0Y

## Accessible Trusted-Developer HTML Diagnostic Page Architecture

**Directive status:** LOCKED for execution
**Phase type:** Architecture and documentation only
**Architect:** ChatGPT
**Executor:** Codex
**Repository:** `C:\Users\antho\Dev\kongono`
**Date:** 2026-08-14

---

## 1. Mission

Phase 0Y must select and document the first concrete presentation boundary for
Kongono's trusted-developer diagnostics: a clean, accessible, deterministic,
server-rendered HTML error page.

This phase exists because a technically correct exception is still a poor
developer experience when its cause, location, and remediation are difficult to
understand. Kongono's diagnostic page must be deliberately useful: it must say
what failed, why it failed, which bounded facts are relevant, and what the
developer can do next. It must not make the developer search through vague
messages or reverse-engineer framework internals.

Phase 0Y is an architecture-and-documentation phase. It selects the exact
responsibility, information architecture, escaping rule, accessibility contract,
resource bounds, and security boundary for a later renderer. It does **not**
implement the renderer or any runtime integration.

The selected future renderer is:

```text
Kongono\DiagnosticPresentation\Developer\Html\TrustedDeveloperHtmlRenderer
```

Its selected future input is the already-implemented Experimental value:

```text
Kongono\DiagnosticPresentation\Developer\TrustedDeveloperDiagnostic
```

The page is for an already-authorized trusted developer boundary only. The
renderer is not an authorization mechanism and may never decide whether trusted
diagnostics are enabled.

The required outcome is one new Accepted ADR plus tightly reconciled existing
documentation. No production code, tests, Composer metadata, lockfile, or
tooling configuration may change.

---

## 2. Authority and executor posture

### 2.1 Authority order

Read these authorities completely before mutation, in this order:

1. `AGENTS.md`;
2. this Phase 0Y directive;
3. `ARCHITECTURE.md`;
4. `SECURITY.md`;
5. `docs/doctrine.md`;
6. `docs/documentation-standard.md`;
7. `docs/engineering/api-status.md`;
8. `docs/engineering/error-handling.md`;
9. `docs/adr/0026-actionable-diagnostics-and-error-boundaries.md`;
10. `docs/adr/0028-bounded-diagnostic-context-and-disclosure.md`;
11. `docs/adr/0034-trusted-developer-diagnostic-projection.md`;
12. `docs/adr/0035-trusted-developer-diagnostic-projection-implementation.md`;
13. `packages/diagnostic-presentation/README.md`;
14. the accepted Phase 0X and Phase 0X.1 directives and reports listed in
    Section 3.4.

If an older document conflicts with this directive only on the exact Phase 0Y
decision, this directive controls and the conflict must be reconciled within the
authorized documentation boundary. All other accepted doctrine remains locked.

### 2.2 Executor, not architect

The executor must implement the written documentation decision exactly. It must
not choose a renderer API, HTML grammar, trust mechanism, HTTP integration,
production behavior, additional field, extension point, package, dependency, or
future capability by assumption.

If any required fact is absent, contradictory, or impossible within the exact
file boundary:

```text
STOP
ASK
DO NOT GUESS
```

Report the exact decision required, why it blocks safe progress, viable options,
their tradeoffs, and one clearly labeled recommendation. Do not implement the
recommendation without new architect authority.

### 2.3 Directive identity is a caller-supplied authority

Before any mutation, independently compute the byte count and SHA-256 of:

```text
docs/dev_plan/kongono_phase_0Y_codex_directive.md
```

Compare both values with the architect-delivered values supplied outside this
document. If either value differs, or the external identity was not supplied,
stop before mutation. Do not infer that the observed local file is authoritative.

The completion report is a caller-owned record and must begin as an ordinary,
untracked, unstaged, zero-byte file at:

```text
docs/task reports/Phase_0Y.md
```

It may be populated only after all implementation and validation gates pass.

---

## 3. Locked starting state and caller records

### 3.1 Repository identity

Before the caller copies this directive and creates the report placeholder, the
accepted repository state is:

```text
branch                     main
local branches             1
HEAD                       7d4df1f4e31b9bdb6cd134b44682719bdb17aa6f
HEAD tree                  b1d0057925158e0f0f37ca13694d1d0d525ce5f0
HEAD parent                75749a37a184996203e569e039bddbc3ec10cdf7
commit count               6
remotes                    0
tags                       0
tracked modifications      26
untracked paths            27
total status paths         53
staged paths               0
tracked diff               26 files, 613 insertions, 124 deletions
```

The inaccessible user-level global-ignore warning already observed in the
managed environment is environmental only. It does not authorize relying on an
incomplete status view. Use explicit repository inventories.

### 3.2 Expected post-caller setup state

After the caller copies this directive and creates the zero-byte report, and
before executor mutation, the exact counts must be:

```text
tracked modifications      26
untracked paths            29
total status paths         55
staged paths               0
```

The two added untracked paths must be exactly:

```text
docs/dev_plan/kongono_phase_0Y_codex_directive.md
docs/task reports/Phase_0Y.md
```

No existing status path may disappear, change classification, become staged, or
be replaced by a directory, reparse point, symbolic link, or alternate stream.

### 3.3 Accepted executable and tooling boundary

The accepted repository contains:

```text
package directories        6
package manifests          6
Composer manifests total   7 including the root
production PHP files       48
test PHP files             79
```

The protected executable/tooling set contains exactly 138 ordinary files:

- every `*.php` file under `packages/` and `tests/`;
- root `composer.json` and `composer.lock`;
- root `.php-cs-fixer.dist.php`, `phpstan.neon.dist`, and
  `phpunit.xml.dist`; and
- each of the six package `composer.json` files.

For each protected path, sort its repository-relative path ordinally after
normalizing separators to `/`, then concatenate:

```text
relative path + NUL + decimal byte length + NUL + uppercase SHA-256 + LF
```

The SHA-256 of that UTF-8-without-BOM concatenation is exactly:

```text
3B45B766429127708C1D1609C428CA7E73DE9E91E3E3065F2D98FD3975A0AEE9
```

This count and digest must match before and after Phase 0Y. Do not regenerate,
reformat, or normalize protected files.

`composer.lock` is additionally locked at:

```text
bytes       180654
SHA-256     FFBAB343FB34036BD9F71E3824AD14BCD4E445A5379C676A49B1D0A7C69FB387
```

### 3.4 Locked Phase 0X-family records

The following caller and architectural records are immutable inputs:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `docs/dev_plan/kongono_phase_0X_codex_directive.md` | 47300 | `2F21C87ED746FE616469A638C187D911D91023B206373F25594EFBF0BC33481A` |
| `docs/dev_plan/kongono_phase_0X_1_codex_directive_correction.md` | 13850 | `1EB22C5C116FC010C93A0623AAFB69504DE3D6F1B54C26910219926E7C414679` |
| `docs/task reports/Phase_0X.md` | 19614 | `6035EFA808A8C86477CF6AF3E60C7B8E427A8DC0BF2C3DA645DDC2D5CBDF8FC7` |
| `docs/task reports/Phase_0X_1_correction.md` | 10388 | `38523347418FD6DEEB22E86FDD31DA6C579F0493A8EF6DE724F5980A99998D80` |
| `docs/adr/0034-trusted-developer-diagnostic-projection.md` | 11736 | `5602E6257E5107F69AD87662EFFF4228E30B6E3F9D30E85123D8A5AA50161551` |
| `docs/adr/0035-trusted-developer-diagnostic-projection-implementation.md` | 7149 | `C9F8522CAA058A77681D129878463CEA1E02EA658AF3F4A35F5EA66B8BF0775C` |

The ADRs may be modified only where Section 11 explicitly authorizes their
Phase 0Y reconciliation. The four directives/reports may not be changed.

If any locked identity or starting-state requirement differs, stop before
mutation. Never use reset, checkout, clean, stash, amend, or history rewriting
to manufacture the baseline.

---

## 4. Phase 0Y architectural decision

Phase 0Y accepts the following architecture for the first trusted-developer
HTML diagnostic page.

### 4.1 Responsibility

The future `TrustedDeveloperHtmlRenderer` is a pure presentation component in
the existing `kongono/diagnostic-presentation` leaf package. It receives one
already-constructed `TrustedDeveloperDiagnostic` and returns one complete HTML5
document string.

The renderer:

- presents every already-projected Safe and Protected field;
- preserves the projection's canonical field order and classifications;
- formats exact scalar values for human reading;
- escapes all dynamic text for its HTML character-data context;
- applies a fixed, local, accessible visual design;
- performs no authorization, projection, discovery, I/O, logging, or runtime
  integration; and
- retains no input or cross-call state.

The renderer must not accept exceptions, contextual markers, Diagnostics values,
arrays, maps, arbitrary objects, raw HTML, templates, configuration, requests,
responses, containers, or service locators.

### 4.2 Trust is outside the renderer

The page is a trusted-developer surface because its input includes both Safe and
Protected fields. That name is not proof of authorization.

A future server-controlled outer integration must establish the trusted
developer capability **before** it calls the projector or renderer. Neither
projection nor rendering may inspect or infer trust from:

- query, route, body, header, cookie, session, or form data;
- user or database records;
- hostname, client address, request origin, environment strings, or heuristics;
- exception content, context content, provider output, or AI output;
- mutable global, superglobal, static, process-wide, thread-local, fiber-local,
  cached, previous-operation, or ambient state.

Phase 0Y does not choose the future capability type, configuration source,
outer adapter, or activation sequence. It only locks the rule that authorization
must be explicit, server-controlled, and completed outside the renderer before
Protected data reaches an output boundary.

### 4.3 Production separation

The selected page is never a production fallback and is not safe merely because
its HTML is escaped. Escaping prevents markup injection; it does not make
Protected diagnostic content suitable for an untrusted audience.

Future production output must use a separately designed deny-by-default model
with explicit allowlists. It may not consume this trusted model and redact it
afterward. Phase 0Y does not select or implement that model.

### 4.4 Stability

The planned renderer is Experimental. Its future existence must not change the
Public status of package exception markers or promote any existing Experimental
projection type to Public or Stable.

---

## 5. Selected future renderer contract

Document this as planned architecture, not implemented API.

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

The future implementation contract is exactly:

- final and readonly;
- no constructor declaration;
- no instance or static properties;
- no additional public, protected, or magic methods;
- no inheritance or consumer implementation point;
- one synchronous `render()` call;
- a fresh result derived only from the supplied immutable diagnostic;
- no mutation of the diagnostic or field values;
- no retained reference after return;
- no cache, registry, singleton, global, static local, or ambient state; and
- no deliberate renderer-specific exception family in this first slice.

The input model already enforces its structural invariants. The future renderer
must not re-project, reclassify, reorder, omit, merge, redact, enrich, or
reinterpret fields. Native engine failures, if any, are not caught and wrapped.
Malformed UTF-8 encountered at the HTML boundary is handled by the exact
replacement escaping rule in Section 7; it does not create an alternate error
page or recursive diagnostic.

No container binding, Kernel lifecycle hook, Application Graph node or relation,
service definition, extension mechanism, alias, factory, helper, facade, trait,
interface, template engine, or registry is selected.

---

## 6. Exact information architecture and document grammar

### 6.1 Complete standalone document

The future renderer returns one complete standalone HTML5 document, not a
fragment. It is useful when opened or returned without JavaScript, remote assets,
an AI service, a source crawl, or network access.

The document must contain, in semantic order:

1. `<!doctype html>`;
2. `<html lang="en">`;
3. a `<head>` with UTF-8 metadata, responsive viewport metadata, a dynamic
   escaped title, and the one static embedded stylesheet;
4. a static skip link targeting the page's main content;
5. one `<main id="diagnostic-main">`;
6. the five content regions in Section 6.2; and
7. closing document elements.

The planned document title is exactly the dynamic sequence:

```text
{identifier} — {title} — Kongono diagnostic
```

Each dynamic component is separately escaped. The separators and `Kongono
diagnostic` are static template text.

The canonical implementation template will be LF-only. Phase 0Y locks the
semantic grammar and dynamic insertion sites. A later implementation directive
must lock the exact whitespace, static CSS bytes, and golden output fixtures
without weakening this ADR.

### 6.2 Five visible content regions

The main content order is exact:

1. **Diagnostic header**
   - static product label `Kongono diagnostic`;
   - exact identifier;
   - exactly one page-level `h1` containing the precise title; and
   - owner label.
2. **What happened**
   - one `h2` with exactly that visible text; and
   - the exact operation.
3. **Why it happened**
   - one `h2` with exactly that visible text; and
   - the exact violated invariant or expectation.
4. **Context**
   - one `h2` with exactly that visible text; and
   - every projected field in canonical order using semantic description-list
     structure.
5. **How to fix it**
   - one `h2` with exactly that visible text; and
   - the exact projected remediation.

The exact owner display labels are:

```text
kernel                 Kernel
application_graph      Application Graph
dependency_injection   Dependency Injection
```

An empty context list renders exactly this static sentence:

```text
No additional diagnostic context was captured.
```

### 6.3 Field presentation

Every field visibly presents:

- its fixed human label;
- its canonical key;
- its exact scalar kind;
- its exact disclosure as the word `Safe` or `Protected`;
- its formatted value; and
- the visible word `Truncated` if and only if `textWasTruncated` is `true`.

The exact visible kind and disclosure labels are:

```text
DiagnosticValueKind::Text          text
DiagnosticValueKind::Integer       integer
DiagnosticValueKind::Boolean       boolean
DiagnosticDisclosure::Safe         Safe
DiagnosticDisclosure::Protected    Protected
```

`textWasTruncated === false` and `textWasTruncated === null` do not show a
truncation indicator. The renderer may not infer truncation from value length.

Disclosure, kind, and truncation must be conveyed in visible text, not only by
color, icon, tooltip, CSS class, shape, position, or ARIA metadata. The page may
use decorative badges in addition to the visible text, but decoration cannot be
the sole carrier of meaning.

The renderer must never add native exception message, exception class, cause,
trace, source path, source excerpt, request data, environment data, SQL,
provider payload, log record, telemetry attribute, incident reference,
documentation URL, localization key, transport metadata, or arbitrary extension
metadata. Those values are not in the input model and must not be discovered.

---

## 7. Output encoding, scalar formatting, and injection resistance

### 7.1 Dynamic data may appear only as escaped character data

Every dynamic value may be inserted only as escaped character data in normal
element text or the `<title>` element's RCDATA content. In this directive,
`text content` includes those two safe insertion contexts and no other context.
Dynamic data must never determine or enter:

- element or attribute names;
- any attribute value, including `id`, `class`, `style`, `lang`, `title`,
  `href`, `src`, `data-*`, `aria-*`, or event handlers;
- CSS, selectors, custom properties, URLs, or media rules;
- comments, doctypes, raw-text elements, `<script>`, or `<style>`;
- executable, navigational, downloadable, or form behavior; or
- an unescaped/raw HTML branch.

All element names, attributes, IDs, classes, labels, and stylesheet content are
fixed static template bytes.

### 7.2 Exact HTML escaping rule

Each dynamic string destined for a text node must be escaped at the final output
boundary using exactly:

```php
htmlspecialchars(
    $value,
    ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5,
    'UTF-8',
    true,
)
```

`double_encode` is deliberately `true` because the input is represented text,
not pre-escaped markup. Entity-looking input such as `&lt;` must remain visibly
literal text rather than being interpreted as a character reference. The output
boundary alone owns HTML encoding. The renderer must not decode entities,
normalize Unicode, trim, case-fold, parse, sanitize, strip tags, or perform a
preliminary escape elsewhere.

`ENT_SUBSTITUTE` supplies deterministic replacement for malformed byte
sequences at the output boundary. It does not authorize raw-byte output or a
different encoding.

### 7.3 Exact scalar formatting

Before the escaping step, values are formatted only by their already-validated
kind:

```text
text      exact represented string
integer   canonical locale-independent base-10 decimal text
boolean   exact lowercase token true or false
```

The renderer must not apply locale formatting, thousands separators, quotes,
PHP debugging syntax, JSON encoding, pretty printing, type coercion, reflection,
or value inference. It must not call locale-sensitive formatting functions.

Every formatted value, including integer and boolean text, passes through the
same HTML escaping function before insertion.

### 7.4 Static assets and browser behavior

The future page has exactly one embedded static stylesheet. It has:

- no JavaScript;
- no external stylesheet, font, image, icon, iframe, media, or network asset;
- no form, button, clipboard action, download, link generated from diagnostic
  data, or active content;
- no inline `style` attributes;
- no dynamic CSS; and
- no automatic refresh, redirect, beacon, preload, prefetch, or speculative
  connection.

The static stylesheet must be compatible with a future hash-based Content
Security Policy. Phase 0Y does not select an HTTP header, CSP value, nonce,
content type, status code, cache policy, or response adapter.

---

## 8. Accessible and clean visual contract

The future page is a diagnostic tool, not a raw exception dump. Its visual
design must make the cause and next action easy to scan while retaining native
HTML semantics.

### 8.1 Required accessibility behavior

The selected renderer must provide:

- document language `en`;
- exactly one `h1` and an ordered, non-skipped `h2` hierarchy;
- a keyboard-visible skip link to `#diagnostic-main`;
- semantic `main`, `section`, heading, paragraph, and description-list
  structures;
- no ARIA role where native semantics already provide the correct meaning;
- no live region, modal, disclosure widget, or keyboard trap;
- visible focus styling that survives forced-colors mode;
- sufficient contrast in light, dark, and forced-colors environments;
- meaning that does not rely on color alone;
- readable reflow at 320 CSS pixels and at 200 percent zoom without horizontal
  page scrolling for ordinary text;
- aggressive wrapping for long identifiers, keys, labels, and values without
  clipping or hidden overflow;
- user-selectable diagnostic text;
- a readable print presentation; and
- no animation, transition, parallax, flashing, or motion dependency.

Use native semantics before ARIA. Decorative elements, if any, must not add
meaning or generate noisy accessible names.

### 8.2 Selected design direction

The later static CSS must use:

- a system UI font stack with no remote font request;
- a centered responsive reading column with a comfortable maximum measure;
- a restrained page background and clearly separated diagnostic card;
- visible identifier and owner metadata;
- strong hierarchy for the precise title and five regions;
- bounded spacing, border, and surface tokens expressed only in static CSS;
- light and dark color schemes through static media queries;
- explicit forced-colors handling;
- static print adjustments; and
- preserved legibility when CSS is unavailable.

This is a clean developer page, not a branded marketing page. It must not use
framework mascots, gradients required for meaning, remote branding assets,
ornamental animation, advertisements, analytics, or engagement controls.

Phase 0Y deliberately does not lock exact colors, font sizes, spacing values,
border radii, shadows, or full stylesheet bytes. A later implementation
directive must select those mechanical details within this design contract and
verify them with exact fixtures and browser-oriented accessibility checks.

---

## 9. Determinism, bounds, lifecycle, and failure

### 9.1 Determinism

For the same immutable diagnostic and the same installed renderer
implementation, the future renderer returns byte-identical HTML. Output must
not depend on:

- current time, random values, process identifiers, memory addresses, host,
  operating system, locale, timezone, environment, request, response, or
  filesystem;
- field hash order beyond the diagnostic's canonical list;
- network, source tree, documentation site, registry, cache, reflection, or AI;
- previous calls, concurrency, fibers, threads, workers, or mutable globals; or
- browser user agent or content negotiation.

### 9.2 Resource bounds

The future renderer must preserve these exact bounds:

```text
input fields                          at most 16
static template plus stylesheet       at most 16 KiB
complete returned HTML                at most 96 KiB
time complexity                       O(field count + dynamic input bytes)
retained state after return            none
```

No recursion, streaming, chunking, buffering service, compression, source read,
template compilation, or unbounded aggregation is selected. The complete string
is produced synchronously in memory.

### 9.3 Persistent-runtime safety

The future renderer is safe to instantiate per use or reuse only because it is
stateless and immutable. It retains no diagnostic, field, rendered string,
builder, request, response, cache, registry, operation identifier, global,
thread-local, fiber-local, or previous-failure state.

One render call cannot influence another. Concurrent calls share no mutable
renderer state. Phase 0Y does not select pooling, lifecycle registration,
cleanup, cancellation, or asynchronous behavior.

### 9.4 Failure boundary

No new renderer diagnostic catalog, renderer exception, fallback page, nested
error renderer, or catch-all wrapping is selected. The model is already
structurally valid; HTML substitution handles malformed string bytes without
recursive failure presentation. Native engine failures retain native identity.

How a future outer runtime represents simultaneous primary, projection, and
rendering failures remains open and must not be invented here.

---

## 10. Required ADR 0036

Create exactly:

```text
docs/adr/0036-accessible-trusted-developer-html-diagnostic-page.md
```

It must use the repository ADR format and contain:

```text
# ADR 0036: Accessible Trusted-Developer HTML Diagnostic Page

## Status
Accepted

## Date
2026-08-14
```

It must include these sections in order:

1. `Context`
2. `Decision`
3. `Rationale`
4. `Consequences`
5. `Rejected alternatives`
6. `What remains open`

The ADR must record, without ambiguity:

1. the planned exact renderer FQCN, status, shape, input, and sole method;
2. the existing Diagnostic Presentation leaf package as owner;
3. authorization as a future server-controlled outer-boundary responsibility;
4. the prohibition on renderer trust selection;
5. the exact five-region information architecture and owner labels;
6. full standalone HTML5, English, semantic server rendering, and no mandatory
   JavaScript;
7. dynamic-data insertion only into escaped text content, including the
   document title's RCDATA context;
8. the exact `htmlspecialchars` flags, encoding, and `double_encode` argument;
9. exact text, integer, and boolean formatting;
10. visible disclosure, kind, and truncation state;
11. the static local stylesheet and prohibition on external/dynamic assets;
12. the accessibility, reflow, dark-mode, forced-colors, motion, and print
    requirements;
13. byte determinism, complexity, output-size, template-size, field-count, and
    persistent-runtime bounds;
14. the absence of a renderer-specific failure family;
15. strict separation from the future deny-by-default production model;
16. the fact that Phase 0Y documents architecture only and implements no
    renderer or integration; and
17. the later implementation directive's responsibility to lock exact static
    template/CSS bytes and golden fixtures.

The ADR must reject at least:

- rendering directly from arbitrary `Throwable` or package markers;
- placing HTML behavior in Diagnostics or an originating package;
- allowing the renderer to activate or infer trusted mode;
- using the trusted model for production and redacting after projection;
- unescaped interpolation or dynamic data in attributes/CSS/URLs/scripts;
- a template engine, component framework, client-side application, or required
  JavaScript;
- remote assets, fonts, documentation calls, AI calls, source crawling, or
  runtime reflection;
- raw exception messages, traces, source excerpts, requests, environment dumps,
  SQL, or provider payload discovery;
- ambient/global/cached renderer state;
- localization in the first renderer;
- copy/export controls in the first renderer; and
- HTTP status, CSP, content-type, cache, or transport ownership inside the pure
  renderer.

`What remains open` must list only genuinely future work:

- exact static HTML/CSS template bytes and implementation tests;
- trusted server-controlled capability/configuration and outer adapter;
- HTTP response ownership, content type, status, cache, and CSP headers;
- safe copy/export formats;
- optional cause-chain, source, stack-frame, and version-matched documentation
  providers under separate disclosure design;
- a separate deny-by-default production model and allowlists;
- CLI, JSON/problem details, and other renderers or transports;
- localization;
- logging, telemetry, incidents, serialization, and cross-process forms; and
- simultaneous primary, projection, rendering, and cleanup failure behavior.

Do not list the HTML format selection, escaping rule, five-region layout,
accessibility baseline, or renderer ownership as open; Phase 0Y resolves them.

---

## 11. Exact documentation file boundary

### 11.1 Create exactly one architecture file

Create only:

```text
docs/adr/0036-accessible-trusted-developer-html-diagnostic-page.md
```

Do not create any implementation, test, fixture, stylesheet, template,
screenshot, image, generated report, example output, or auxiliary document.

### 11.2 Modify exactly these twenty existing documentation files

```text
README.md
ARCHITECTURE.md
SECURITY.md
docs/adr/0026-actionable-diagnostics-and-error-boundaries.md
docs/adr/0027-package-local-runtime-diagnostic-metadata.md
docs/adr/0028-bounded-diagnostic-context-and-disclosure.md
docs/adr/0029-presentation-neutral-diagnostic-value-foundation.md
docs/adr/0030-application-graph-diagnostic-context-integration.md
docs/adr/0031-neutral-api-metadata-ownership-and-acyclic-package-dependencies.md
docs/adr/0032-kernel-lifecycle-diagnostic-context-integration.md
docs/adr/0033-dependency-injection-diagnostic-context-integration.md
docs/adr/0034-trusted-developer-diagnostic-projection.md
docs/adr/0035-trusted-developer-diagnostic-projection-implementation.md
docs/adr/README.md
docs/architecture/overview.md
docs/architecture/runtime-model.md
docs/doctrine.md
docs/engineering/error-handling.md
docs/glossary.md
packages/diagnostic-presentation/README.md
```

No other existing file may change.

### 11.3 Required reconciliation by file

#### `README.md`

Add a concise planned-capability statement: Kongono has selected an accessible,
server-rendered trusted-developer HTML diagnostic page architecture, while no
renderer, trusted activation, HTTP adapter, or production page is implemented.

#### `ARCHITECTURE.md`

Place the planned renderer in the existing Diagnostic Presentation leaf. State
that it consumes only the trusted projection and does not add package edges,
runtime bindings, Graph participation, or reverse dependencies.

#### `SECURITY.md`

Explain the two independent controls: explicit trusted authorization before
projection/rendering, then grammar-correct HTML escaping at output. Make clear
that escaping does not declassify Protected data and that production may not use
this model.

#### `docs/adr/0026-actionable-diagnostics-and-error-boundaries.md`

Amend later decision history so the first developer HTML format is now selected
in ADR 0036, while outer runtime activation, production behavior, providers, and
transports remain future.

#### `docs/adr/0027-package-local-runtime-diagnostic-metadata.md`

Preserve the rejection of rendering from exceptions. Reconcile its present-tense
open list: Diagnostic Presentation now owns the planned renderer and ADR 0036
selects the first HTML API/format, while activation, transport, production,
providers, and implementation remain future.

#### `docs/adr/0028-bounded-diagnostic-context-and-disclosure.md`

Reconcile the future presentation language: Safe and Protected classifications
remain unchanged and visible in the trusted page; the page neither authorizes
nor reclassifies them; production separation remains locked.

#### `docs/adr/0029-presentation-neutral-diagnostic-value-foundation.md`

Preserve Diagnostics as presentation-neutral. Reconcile `Open decisions` so the
concrete trusted-developer HTML API and format are selected but unimplemented;
other transports and the production model remain open.

#### `docs/adr/0030-application-graph-diagnostic-context-integration.md`

Preserve every Graph schema and implementation fact. Qualify its present-tense
open work so the trusted HTML page architecture is selected in ADR 0036 while
trusted activation, implementation, production, transports, and providers remain
future.

#### `docs/adr/0031-neutral-api-metadata-ownership-and-acyclic-package-dependencies.md`

Replace the stale open item `Renderer ownership and APIs` with the precise
remaining work after ADRs 0034 through 0036. Preserve neutral metadata ownership
and the exact acyclic package graph.

#### `docs/adr/0032-kernel-lifecycle-diagnostic-context-integration.md`

Preserve every Kernel schema and implementation fact. Reconcile only its
present-tense renderer/open-work language with the selected but unimplemented
HTML page.

#### `docs/adr/0033-dependency-injection-diagnostic-context-integration.md`

Preserve every DI schema and implementation fact. Reconcile only its
present-tense renderer/open-work language with the selected but unimplemented
HTML page.

#### `docs/adr/0034-trusted-developer-diagnostic-projection.md`

Add a Phase 0Y amendment or related-decision reconciliation identifying the
selected HTML consumer, exact output-boundary escaping ownership, and remaining
outer authorization boundary. Do not rewrite Phase 0X as though it rendered.

#### `docs/adr/0035-trusted-developer-diagnostic-projection-implementation.md`

Move only the accessible server-rendered format and escaping selection out of
open work. State that Phase 0Y documents the chosen architecture but does not
implement it. Preserve all Phase 0X implementation facts.

#### `docs/adr/README.md`

Add ADR 0036 to the index with `Accepted` status and an accurate one-sentence
summary. Preserve numbering and order.

#### `docs/architecture/overview.md`

Add the planned pure HTML renderer below the implemented projector. Preserve the
six-package graph exactly and distinguish planned presentation from current
runtime capability.

#### `docs/architecture/runtime-model.md`

Document the future sequence conceptually:

```text
outer boundary explicitly authorizes trusted developer use
    -> projector creates detached trusted model
    -> pure HTML renderer creates complete escaped document
    -> future adapter owns transport response
```

Mark authorization and the adapter unimplemented. Do not imply Kernel lifecycle,
container, HTTP, or Graph integration.

#### `docs/doctrine.md`

Record the durable doctrine: actionable developer failures are deliberately
structured and scannable; output encoding belongs to the final grammar boundary;
escaping and disclosure authorization are separate controls; presentation must
remain useful without remote or AI services.

#### `docs/engineering/error-handling.md`

Replace stale statements that all concrete renderers remain open. Document the
selected planned page contract, exact five regions, text-only dynamic insertion,
escaping rule, scalar formatting, accessibility contract, bounds, and strict
current-versus-planned state. Preserve current projection and schema facts.

#### `docs/glossary.md`

Add one concise glossary entry for `Trusted developer HTML diagnostic page` and,
if needed, reconcile the trusted projection entry. It must identify the planned
pure renderer and explicitly deny authorization or production semantics.

#### `packages/diagnostic-presentation/README.md`

Add a planned renderer section linking ADR 0036. State that the package remains
the owner, but the renderer is not yet implemented. Replace the absolute
statement that the package has no selected HTML grammar with precise
current-versus-planned wording. Preserve the implemented eight-declaration
boundary and all Phase 0X facts.

### 11.4 Documentation quality

Every edited document must:

- say `implemented`, `accepted architecture`, `planned`, and `future` precisely;
- avoid claiming an HTML class, file, stylesheet, page, trusted-mode selector,
  HTTP response, production output, or test currently exists;
- preserve package ownership and API status;
- use repository-relative links and introduce no broken local link;
- use valid UTF-8 without BOM, LF endings, one final LF, no NUL, and no trailing
  whitespace; and
- avoid speculative exact APIs beyond the one planned renderer contract in this
  directive.

---

## 12. Prohibited scope

Phase 0Y must not:

- create or modify PHP production code, tests, fixtures, Composer manifests,
  `composer.lock`, or tooling configuration;
- create `TrustedDeveloperHtmlRenderer.php` or any renderer implementation;
- create HTML, CSS, template, snapshot, golden, image, screenshot, or browser
  fixture files;
- add a dependency or package;
- alter the six-package dependency graph;
- implement trusted/debug/developer mode selection, configuration, capability,
  middleware, adapter, controller, handler, catcher, or top-level boundary;
- implement DI/container bindings, Kernel lifecycle integration, Application
  Graph nodes or relations, extensions, service discovery, or registries;
- implement HTTP routing, request/response abstractions, status mapping,
  content negotiation, CSP/header/cache policy, RFC 9457, or web-server behavior;
- implement production diagnostics, public error pages, allowlists, redaction,
  incident references, logging, telemetry, metrics, tracing, or persistence;
- implement cause, trace, source, excerpt, documentation, localization, copy,
  export, JSON, Markdown, plaintext, terminal, or CLI providers/renderers;
- add JavaScript, a component framework, a template engine, third-party CSS,
  remote assets, or browser build tooling;
- add sanitizers, secret detectors, raw context bags, arbitrary metadata, or
  universal exception/diagnostic markers;
- modify any accepted identifier, schema, disclosure, throw site, exception
  marker, projection value, projector behavior, API status, or package doctrine;
- stage, commit, amend, reset, checkout, clean, stash, rebase, tag, push, create a
  branch, add a remote, alter hooks, or rewrite history; or
- populate the Phase 0Y report before every gate passes.

Do not perform adjacent cleanup, formatting, reordering, spelling repair,
dependency refresh, generated-file update, or documentation modernization
outside the exact boundary.

---

## 13. Execution sequence

Perform these gates in order.

### Gate A: authority and caller identity

1. Read every authority in Section 2.1 completely.
2. Verify the externally supplied Phase 0Y directive byte count and SHA-256.
3. Verify the zero-byte report identity:

```text
bytes       0
SHA-256     E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

4. Verify every locked Phase 0X-family record.
5. Stop on any mismatch.

### Gate B: repository and file-safety baseline

1. Verify Section 3 repository identity and exact status counts/inventories.
2. Verify all status paths are ordinary files/directories as appropriate, with
   no symlink, reparse-point, alternate-stream, submodule, mode, or case-folding
   surprise.
3. Verify no staged path.
4. Verify the 138-path protected digest and `composer.lock` identity.
5. Verify package, production, test, and manifest counts.
6. Stop on any mismatch.

### Gate C: pre-change quality

Run the canonical command unchanged:

```powershell
composer quality
```

The accepted baseline is:

```text
security advisories      0
platform requirements    pass on PHP 8.5.9
PHP CS Fixer             0 of 127 files fixable
PHPStan                  0 errors
PHPUnit                  231 tests, 3973 assertions
```

Composer's accepted missing-license, local unbounded-constraint, and missing-curl
warnings remain warnings only. No other warning is automatically accepted.

If the unchanged fail-closed audit is blocked solely by managed-environment
network or cache access, rerun the identical command once with the environment's
approved access. Do not change Composer policy, skip the audit, use stale output,
or weaken a gate. Stop on any substantive failure or different count.

### Gate D: documentation implementation

1. Create ADR 0036 exactly as Section 10 requires.
2. Modify exactly the twenty documents in Section 11.2.
3. Do not touch any other path.
4. Re-read every changed file completely.
5. Reconcile all current/future statements and open-work lists.

### Gate E: closed-scope validation

Verify:

- exactly one newly created architecture file: ADR 0036;
- exactly twenty authorized existing documents modified by Phase 0Y;
- zero Phase 0Y writes to protected executable/tooling paths;
- protected count and digest unchanged;
- lockfile bytes and SHA-256 unchanged;
- package, production, test, and manifest counts unchanged;
- no PHP namespace, class, interface, enum, trait, method, Composer requirement,
  autoload map, suite, or tooling change;
- ADR 0036 has exact title, Accepted status, date, section order, and all required
  decisions/rejections/open work;
- every planned renderer FQCN is identical;
- every renderer reference says planned/unimplemented;
- no document claims that trusted authorization, an HTTP adapter, or a
  production surface exists;
- the five region labels, owner labels, empty-context sentence, escaping call,
  scalar formatting, size bounds, and security separation are consistent;
- all repository-relative Markdown links resolve;
- all changed files satisfy strict UTF-8/LF/no-BOM/no-NUL/one-final-LF/no-
  trailing-whitespace requirements;
- all status paths remain ordinary and have no alternate data streams; and
- no staged paths, ref changes, history changes, remotes, tags, or branches.

### Gate F: post-change quality

Run the unchanged canonical command again:

```powershell
composer quality
```

It must produce the same accepted results as Gate C. The same narrow
environment-only retry rule applies. Documentation-only work does not authorize
skipping canonical quality.

### Gate G: report population and final proof

Only after Gates A through F pass:

1. populate `docs/task reports/Phase_0Y.md` with Section 16's exact structure;
2. verify it is valid UTF-8 without BOM, LF-only, one final LF, no NUL, and no
   trailing whitespace;
3. compute its final byte count and uppercase SHA-256;
4. verify it remains untracked, unstaged, absent from HEAD, and ordinary;
5. verify the final repository state in Section 14; and
6. return the report identity outside the report body in the executor's final
   response.

If a failure occurs before Gate G, leave the report zero bytes and issue the
blocked report in the executor response. Do not place partial evidence in the
caller-owned report.

---

## 14. Required final repository state

After successful Phase 0Y documentation and report population:

```text
branch                     main
local branches             1
HEAD                       7d4df1f4e31b9bdb6cd134b44682719bdb17aa6f
HEAD tree                  b1d0057925158e0f0f37ca13694d1d0d525ce5f0
HEAD parent                75749a37a184996203e569e039bddbc3ec10cdf7
commit count               6
remotes                    0
tags                       0
tracked modifications      30
untracked paths            30
total status paths         60
staged paths               0
```

The one additional untracked path beyond caller setup must be exactly:

```text
docs/adr/0036-accessible-trusted-developer-html-diagnostic-page.md
```

Sixteen authorized existing documents were already tracked-modified or
untracked before Phase 0Y. These four previously clean tracked ADRs become
modified:

```text
docs/adr/0027-package-local-runtime-diagnostic-metadata.md
docs/adr/0030-application-graph-diagnostic-context-integration.md
docs/adr/0032-kernel-lifecycle-diagnostic-context-integration.md
docs/adr/0033-dependency-injection-diagnostic-context-integration.md
```

That exact transition increases the tracked-modification count from 26 to 30.
The actual cumulative tracked diff statistic will change and must be reported
exactly; it is not preselected here.

The directive and populated report remain untracked and unstaged. HEAD, index,
history, branches, remotes, tags, modes, protected-file identities, and all
unrelated caller records remain unchanged.

---

## 15. Strict acceptance criteria

Every item must pass:

1. All required authorities were read completely.
2. The architect-delivered Phase 0Y directive byte count and SHA-256 matched.
3. The zero-byte report and every locked Phase 0X-family record matched.
4. Repository identity, exact pre-change inventories, counts, and empty index
   matched.
5. The 138 protected paths and aggregate digest matched before and after.
6. `composer.lock` identity matched before and after.
7. Pre-change `composer quality` passed with the accepted exact results.
8. Exactly ADR 0036 was created.
9. Exactly the twenty authorized existing documents were modified by Phase
   0Y.
10. ADR 0036 is Accepted, dated 2026-08-14, and records every required decision,
    rejection, consequence, and open item.
11. The exact planned renderer FQCN and sole method contract are consistent.
12. Every document distinguishes accepted architecture from implemented
    capability.
13. The renderer consumes only `TrustedDeveloperDiagnostic` and has no trust,
    projection, transport, or runtime responsibility.
14. The exact five-region information architecture, owner labels,
    empty-context sentence, visible field metadata, and canonical ordering are
    consistent.
15. Dynamic data is limited to escaped text content and the exact HTML escaping
    rule is documented consistently.
16. Text, integer, and boolean formatting is exact and locale-independent.
17. Accessibility, no-JavaScript, local-static-style, reflow, dark-mode,
    forced-colors, motion, print, determinism, and resource bounds are complete.
18. Escaping is never described as authorization or declassification.
19. Trusted and production presentation remain strictly separate.
20. No renderer, PHP, test, fixture, dependency, manifest, lock, tooling,
    package-graph, HTTP, runtime, production, provider, logging, telemetry, CLI,
    frontend, or later capability was implemented.
21. All local links and strict text/file-safety checks passed.
22. Post-change `composer quality` passed with the same accepted results.
23. Final Git status is exactly 30 modified, 30 untracked, 60 total, and zero
    staged, with unchanged HEAD and refs.
24. The completion report has the exact Section 16 structure, is the only
    caller record populated by Phase 0Y, and its final identity was returned
    outside its body.
25. No prohibited Git, history, staging, cleanup, or adjacent mutation occurred.

Any failed or unverified item makes status `Blocked` or `Partial`, never
`Complete`.

---

## 16. Standard executor completion report

Populate `docs/task reports/Phase_0Y.md` only after complete success. Use exactly
these 18 level-two headings in this order:

```text
# PHASE 0Y TASK COMPLETION REPORT

## 1. STATUS
## 2. SUMMARY
## 3. BASELINE VERIFIED
## 4. CALLER RECORDS
## 5. FILES CREATED
## 6. FILES MODIFIED
## 7. ARCHITECTURAL DECISIONS
## 8. DEVIATIONS FROM DIRECTIVE
## 9. DEPENDENCIES ADDED/REMOVED
## 10. ENVIRONMENT
## 11. COMMANDS RUN
## 12. VALIDATION RESULTS
## 13. DOCUMENTATION REVIEW
## 14. SECURITY AND FILE-SAFETY REVIEW
## 15. WARNINGS / KNOWN ISSUES
## 16. QUESTIONS / BLOCKERS
## 17. GIT DIFF SUMMARY
## 18. ACCEPTANCE CRITERIA AND NEXT STEP
```

Report requirements:

- `STATUS` must be `Complete` only if all 25 acceptance items pass.
- `BASELINE VERIFIED` must include branch, HEAD, tree, parent, commit count,
  exact status counts, index state, protected digest, lock identity, and
  pre-change quality.
- `CALLER RECORDS` must identify this directive and the zero-byte starting
  report plus locked Phase 0X-family records.
- `FILES CREATED` must list only ADR 0036.
- `FILES MODIFIED` must list all twenty authorized documents individually and
  state the reconciliation performed in each.
- `ARCHITECTURAL DECISIONS` must say no executor-authored decisions and summarize
  only the architect-authorized Phase 0Y selection.
- `DEPENDENCIES ADDED/REMOVED` must be `None.`
- `ENVIRONMENT` must report PHP, Composer, Git, PHPUnit, PHPStan, and PHP CS
  Fixer versions actually observed.
- `COMMANDS RUN` must give exact material commands, including any approved
  identical quality retry.
- `VALIDATION RESULTS` must report both quality runs and all focused documentation
  validations with exact observed counts.
- `DOCUMENTATION REVIEW` must explicitly confirm current/planned language,
  accepted/open decisions, package ownership, and absence of false capability
  claims.
- `SECURITY AND FILE-SAFETY REVIEW` must confirm trust-versus-escaping separation,
  production separation, protected identities, links, encoding, modes, streams,
  and ordinary-file checks.
- `GIT DIFF SUMMARY` must report exact final status inventory and actual
  cumulative tracked diff statistics.
- `ACCEPTANCE CRITERIA AND NEXT STEP` must enumerate PASS/FAIL for every Section
  15 item and end with exactly:

```text
Await architect review before Phase 0Z.
```

Do not put the report's own final byte count or hash inside its body. Return
them separately in the executor's final response after the final file write and
read-back verification.

---

## 17. Required final outcome

Phase 0Y is complete only when Kongono has one coherent, Accepted architecture
for a deliberately developer-friendly HTML diagnostic page and still has no
renderer implementation.

The accepted design must make a future failure page immediately understandable:
the identifier, owner, precise title, operation, violated invariant, bounded
context, and remediation appear in a fixed scannable order. It must be accessible
without JavaScript, deterministic without remote services, safe against HTML
injection through exact character-data escaping, bounded for persistent
runtimes, and explicit about Safe, Protected, and truncated values.

The design must also preserve the harder security truth: escaped Protected data
is still Protected. Only a future explicit server-controlled outer boundary may
authorize the projector and renderer. Production requires a different
deny-by-default model.

No code, test, package graph, runtime integration, HTTP behavior, production
surface, provider, logging, telemetry, CLI, frontend, or other later capability
is part of Phase 0Y.
