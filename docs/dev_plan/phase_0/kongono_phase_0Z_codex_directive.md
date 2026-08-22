# CODEX IMPLEMENTATION DIRECTIVE - KONGONO PHASE 0Z

## Accessible Trusted-Developer HTML Diagnostic Renderer Implementation

**Directive status:** LOCKED for execution
**Phase type:** Narrow production implementation
**Architect:** ChatGPT
**Executor:** Codex
**Repository:** `C:\Users\antho\Dev\kongono`
**Date:** 2026-08-14

---

## 1. Mission

Phase 0Z must implement the first concrete Kongono diagnostic renderer selected
by Accepted ADR 0036:

```text
Kongono\DiagnosticPresentation\Developer\Html\TrustedDeveloperHtmlRenderer
```

The renderer consumes exactly one already-constructed
`TrustedDeveloperDiagnostic` and returns one complete, accessible,
deterministic, bounded HTML5 document string.

The result must make a known framework failure easy to understand without a
remote service, JavaScript, source crawl, AI call, runtime registry, or
transport integration. It must present the exact identifier, owner, projected
title, operation, violated invariant, ordered bounded context, and projected
remediation using the five-region information architecture accepted in Phase
0Y.

Phase 0Z implements presentation only. It does not authorize trusted use,
project exceptions, catch failures, select debug mode, create an HTTP response,
serve a page, implement a production surface, or add any later provider.

The implementation is complete only when:

- the one Experimental renderer declaration exists with the exact class shape;
- the canonical HTML template and static CSS in this directive are implemented;
- all dynamic values are escaped at their final character-data boundary;
- two checked-in golden HTML fixtures and ten focused tests prove the contract;
- API-status architecture enforcement includes the new declaration;
- one Accepted implementation ADR and current documentation describe reality;
- the package graph, Composer metadata, lockfile, and existing runtime behavior
  remain unchanged; and
- canonical quality passes without policy weakening.

---

## 2. Authority and mandatory stop rule

### 2.1 Required reading order

Read these files completely before mutation:

1. `AGENTS.md`;
2. this Phase 0Z directive;
3. `ARCHITECTURE.md`;
4. `SECURITY.md`;
5. `docs/doctrine.md`;
6. `docs/documentation-standard.md`;
7. `docs/engineering/api-status.md`;
8. `docs/engineering/error-handling.md`;
9. `docs/engineering/testing.md`;
10. `docs/adr/0034-trusted-developer-diagnostic-projection.md`;
11. `docs/adr/0035-trusted-developer-diagnostic-projection-implementation.md`;
12. `docs/adr/0036-accessible-trusted-developer-html-diagnostic-page.md`;
13. `packages/diagnostic-presentation/README.md`;
14. every production declaration under
    `packages/diagnostic-presentation/src/`;
15. every existing test under `packages/diagnostic-presentation/tests/`;
16. `tests/Architecture/ApiStatusConventionTest.php`;
17. the accepted Phase 0Y and Phase 0Y.1 directives and reports.

Phase 0Y and ADR 0036 are architectural authority. Phase 0Y.1 corrects only the
projection terminology to `projected title` and `projected remediation`.

### 2.2 Executor posture

The executor must implement the contract exactly. It may make ordinary local
coding choices only where this directive expressly leaves mechanics open and
those choices do not create a new public/protected method, state, behavior,
dependency, failure, output byte, or architectural decision.

If a requirement is contradictory, unavailable, or impossible within the exact
file boundary:

```text
STOP
ASK
DO NOT GUESS
```

Report the exact decision, why it blocks progress, viable options, tradeoffs,
and one recommendation. Do not implement the recommendation without architect
authority.

### 2.3 Caller identity gate

Before mutation, compute the byte count and SHA-256 of:

```text
docs/dev_plan/kongono_phase_0Z_codex_directive.md
```

Compare both with the architect-delivered values supplied outside this file. If
either value is absent or different, stop. The local directive does not
authenticate itself.

The caller must also create this ordinary, untracked, unstaged, zero-byte report
placeholder absent from HEAD:

```text
docs/task reports/Phase_0Z.md
```

It may be populated only after all implementation and validation gates pass.

---

## 3. Locked starting state

### 3.1 Repository identity before caller setup

The accepted post-Phase-0Y.1 state is:

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
untracked paths            32
total status paths         62
staged paths               0
tracked diff               30 files, 829 insertions, 186 deletions
```

After the caller copies this directive and creates the report placeholder, the
exact pre-mutation counts must be:

```text
tracked modifications      30
untracked paths            34
total status paths         64
staged paths               0
```

The two caller-added paths must be exactly:

```text
docs/dev_plan/kongono_phase_0Z_codex_directive.md
docs/task reports/Phase_0Z.md
```

No existing status path may disappear, change classification, become staged,
or be normalized or repaired.

### 3.2 Accepted executable boundary

The pre-change repository contains:

```text
package directories        6
package manifests          6
Composer manifests total   7 including root
production PHP files       48
test PHP files             79
protected PHP/tool files   138
```

The protected set is every PHP file below `packages/` and `tests/`, the root
Composer manifest and lockfile, the six package manifests, and the three root
PHP quality configurations. Its pre-change aggregate identity is:

```text
3B45B766429127708C1D1609C428CA7E73DE9E91E3E3065F2D98FD3975A0AEE9
```

Use the Phase 0Y algorithm: ordinal repository-relative `/` paths, each encoded
as path + NUL + decimal byte length + NUL + uppercase SHA-256 + LF, then SHA-256
the UTF-8-without-BOM concatenation.

`composer.lock` is locked at:

```text
bytes       180654
SHA-256     FFBAB343FB34036BD9F71E3824AD14BCD4E445A5379C676A49B1D0A7C69FB387
```

Phase 0Z may change the protected aggregate only through exactly:

```text
packages/diagnostic-presentation/src/Developer/Html/TrustedDeveloperHtmlRenderer.php
packages/diagnostic-presentation/tests/Unit/TrustedDeveloperHtmlRendererTest.php
tests/Architecture/ApiStatusConventionTest.php
```

The first two are new. The architecture test is already a modified status path.
No other protected file may change.

The successful post-change counts must be:

```text
production PHP files       49
test PHP files             80
protected PHP/tool files   140
PHP CS Fixer files         129
```

The executor must compute and report the new aggregate protected digest; it is
not preselected because it depends on the authorized implementation bytes.

### 3.3 Locked caller and architecture records

These inputs must match:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `docs/dev_plan/kongono_phase_0Y_codex_directive.md` | 49492 | `21E8232B0E305DC03618F3EEA2EC8E02BEE42093B0D1A7355AD92EEA8FFEBD00` |
| `docs/task reports/Phase_0Y.md` | 19190 | `C8DC0AEAB1E2A3D6688FDECD207B98645871F05180965440D2B65DEF0F4A720A` |
| `docs/dev_plan/kongono_phase_0Y_1_codex_directive_correction.md` | 16281 | `6908DE659DA2363CCD83846C626E51A8CC6E487EB416928CC8AAFB86D8F839E4` |
| `docs/task reports/Phase_0Y_1_correction.md` | 11438 | `DC29871D5A4F974029120CCA21E67782E70113077199005207559D180F54C1AD` |
| `docs/adr/0034-trusted-developer-diagnostic-projection.md` | 12486 | `54767A0A4C917F6D833E95950641BD89463426D53563861FB7B61A00CE1C5AC9` |
| `docs/adr/0035-trusted-developer-diagnostic-projection-implementation.md` | 7571 | `0E8C9B73A1173FCAE341EBC627A86BF0CE6C4A5C57D6CAC6FECC2A773996CDB3` |
| `docs/adr/0036-accessible-trusted-developer-html-diagnostic-page.md` | 14436 | `412FE7491AA11F2BE18331E3EEA7EBFFAC0A2D63C48F11298355FBFAEE0A2CD6` |

The directives and reports are immutable. ADR 0036 may receive only its
authorized Phase 0Z implementation note. ADRs 0034 and 0035 may receive only
their authorized current/open-work reconciliation in Section 11.

Stop before mutation on any mismatch. Do not use reset, restore, checkout,
stash, clean, amend, rebase, or history rewriting to manufacture the baseline.

---

## 4. Exact file boundary

### 4.1 Create exactly five files

```text
packages/diagnostic-presentation/src/Developer/Html/TrustedDeveloperHtmlRenderer.php
packages/diagnostic-presentation/tests/Unit/TrustedDeveloperHtmlRendererTest.php
packages/diagnostic-presentation/tests/Fixture/trusted-developer-html-full.html
packages/diagnostic-presentation/tests/Fixture/trusted-developer-html-empty.html
docs/adr/0037-trusted-developer-html-diagnostic-renderer-implementation.md
```

The two HTML files are test fixtures only. They are not production templates,
public assets, or runtime files.

### 4.2 Modify exactly twenty-seven existing files

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
docs/adr/0036-accessible-trusted-developer-html-diagnostic-page.md
docs/adr/README.md
docs/architecture/application-graph.md
docs/architecture/overview.md
docs/architecture/runtime-model.md
docs/documentation-standard.md
docs/doctrine.md
docs/engineering/api-status.md
docs/engineering/error-handling.md
docs/engineering/package-layout.md
docs/engineering/testing.md
docs/glossary.md
packages/diagnostic-presentation/README.md
tests/Architecture/ApiStatusConventionTest.php
```

No other existing or new path may change.

### 4.3 No Composer or tooling edits

The existing package/root PSR-4 mappings, PHPUnit suite, PHPStan paths, and PHP
CS Fixer paths already include the new source and test. Do not modify:

```text
composer.json
composer.lock
packages/*/composer.json
.php-cs-fixer.dist.php
phpstan.neon.dist
phpunit.xml.dist
```

Do not run `composer update`. An optimized strict autoload dump may be used only
as a read/validation operation if canonical quality does not already cover the
needed autoload proof; it must not change committed Composer metadata.

---

## 5. Exact production API and class shape

Create the class with this exact declared public contract:

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

The concrete class must have:

- exactly the namespace and FQCN above;
- one Experimental `Api` attribute and no other class attribute;
- final and readonly modifiers;
- no parent class or implemented interface;
- no declared constructor;
- no instance or static property;
- no class constant;
- exactly one public method, `render()`;
- no protected or magic method;
- only private static helpers if helpers are used;
- no closure, anonymous class, generator, fiber, callback registration, or
  retained callable; and
- no deliberate exception declaration or `@throws` contract.

If helpers are used, their complete allowed inventory is:

```text
renderContext
renderField
ownerLabel
kindLabel
disclosureLabel
formatValue
escape
```

No other private method is permitted. Not every allowed helper is mandatory.

All diagnostic accessors and each field accessor must be read once into local
variables before formatting. The renderer must not mutate, clone, serialize,
reflect on, or retain the diagnostic or its fields.

The only permitted production imports beyond API metadata and the diagnostic
input are:

```text
Kongono\DiagnosticPresentation\Developer\DeveloperDiagnosticOwner
Kongono\DiagnosticPresentation\Developer\TrustedDeveloperDiagnosticField
Kongono\Diagnostics\Context\DiagnosticDisclosure
Kongono\Diagnostics\Context\DiagnosticValueKind
```

Native functions such as `htmlspecialchars()`, `implode()`, and `sprintf()` may
be used locally. No framework service, filesystem, network, environment,
locale, date/time, random, JSON, DOM, template, or output-buffer API may be used
by production code.

---

## 6. Canonical document template

### 6.1 Template rules

The returned string begins with `<!doctype html>` at byte zero, uses LF only,
contains no BOM or NUL, and ends with exactly:

```text
</html>
```

including one final LF.

The following block is the canonical template. `{{...}}` tokens describe
escaped dynamic character data and are not literal output. `{{context}}` is
replaced by exactly one of the context grammars in Section 6.3. The stylesheet
bytes between `<style>` and `</style>` are exact.

```html
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="color-scheme" content="light dark">
  <title>{{identifier}} — {{title}} — Kongono diagnostic</title>
  <style>
    :root {
      color-scheme: light dark;
      --page: #f3f5f8;
      --surface: #ffffff;
      --surface-muted: #f7f8fb;
      --text: #172033;
      --muted: #526079;
      --border: #d8dee9;
      --accent: #3157d5;
      --accent-strong: #203fa7;
      --code: #eef2ff;
      --shadow: 0 1.125rem 3.125rem rgba(31, 42, 68, 0.14);
      font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      font-synthesis: none;
      line-height: 1.5;
    }

    @media (prefers-color-scheme: dark) {
      :root {
        --page: #111722;
        --surface: #192231;
        --surface-muted: #202b3d;
        --text: #f4f7fb;
        --muted: #b9c4d5;
        --border: #3b4960;
        --accent: #9cb4ff;
        --accent-strong: #c7d4ff;
        --code: #273653;
        --shadow: 0 1.125rem 3.125rem rgba(0, 0, 0, 0.35);
      }
    }

    * {
      box-sizing: border-box;
    }

    body {
      min-height: 100vh;
      margin: 0;
      background: var(--page);
      color: var(--text);
    }

    .skip-link {
      position: absolute;
      z-index: 1;
      top: 0.75rem;
      left: 0.75rem;
      padding: 0.625rem 0.875rem;
      transform: translateY(-200%);
      border-radius: 0.375rem;
      background: var(--accent-strong);
      color: var(--surface);
      font-weight: 700;
    }

    .skip-link:focus {
      transform: none;
      outline: 0.1875rem solid currentColor;
      outline-offset: 0.1875rem;
    }

    main {
      width: min(calc(100% - 2rem), 64rem);
      margin: 0 auto;
      padding: clamp(1rem, 4vw, 4rem) 0;
    }

    .diagnostic-card {
      overflow: hidden;
      border: 0.0625rem solid var(--border);
      border-radius: 1rem;
      background: var(--surface);
      box-shadow: var(--shadow);
    }

    .diagnostic-header,
    .diagnostic-section {
      padding: clamp(1.25rem, 4vw, 2.5rem);
    }

    .diagnostic-header,
    .diagnostic-section:not(:last-child) {
      border-bottom: 0.0625rem solid var(--border);
    }

    .eyebrow {
      margin: 0 0 0.75rem;
      color: var(--accent-strong);
      font-size: 0.75rem;
      font-weight: 800;
      letter-spacing: 0.08em;
      text-transform: uppercase;
    }

    .identity {
      display: flex;
      flex-wrap: wrap;
      gap: 0.625rem 1rem;
      align-items: center;
    }

    .identifier {
      padding: 0.25rem 0.5rem;
      border-radius: 0.375rem;
      background: var(--code);
      color: var(--text);
      font-family: ui-monospace, "Cascadia Code", "SFMono-Regular", Consolas, monospace;
      font-size: 0.875rem;
      font-weight: 700;
      overflow-wrap: anywhere;
    }

    .owner {
      margin: 0;
      color: var(--muted);
      font-size: 0.9375rem;
    }

    .owner span {
      color: var(--text);
      font-weight: 700;
    }

    h1,
    h2,
    p {
      overflow-wrap: anywhere;
    }

    h1 {
      max-width: 24ch;
      margin: 1rem 0 0;
      font-size: clamp(1.75rem, 5vw, 3rem);
      line-height: 1.12;
      letter-spacing: -0.025em;
    }

    h2 {
      margin: 0 0 0.75rem;
      font-size: clamp(1.125rem, 3vw, 1.375rem);
      line-height: 1.25;
    }

    .detail,
    .empty-context {
      max-width: 75ch;
      margin: 0;
      color: var(--text);
    }

    .fields {
      display: grid;
      gap: 0.875rem;
      margin: 0;
    }

    .context-item {
      overflow: hidden;
      border: 0.0625rem solid var(--border);
      border-radius: 0.75rem;
      background: var(--surface-muted);
    }

    .context-item dt {
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem 0.875rem;
      align-items: baseline;
      justify-content: space-between;
      padding: 0.875rem 1rem;
    }

    .field-label {
      font-weight: 750;
      overflow-wrap: anywhere;
    }

    .field-key {
      color: var(--muted);
      font-family: ui-monospace, "Cascadia Code", "SFMono-Regular", Consolas, monospace;
      font-size: 0.8125rem;
      overflow-wrap: anywhere;
    }

    .context-item dd {
      margin: 0;
      padding: 1rem;
      border-top: 0.0625rem solid var(--border);
    }

    .field-meta {
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem;
      margin-bottom: 0.75rem;
    }

    .meta-item {
      padding: 0.1875rem 0.5rem;
      border: 0.0625rem solid var(--border);
      border-radius: 999rem;
      background: var(--surface);
      color: var(--muted);
      font-size: 0.75rem;
      font-weight: 700;
    }

    .field-value {
      margin: 0;
      padding: 0.875rem;
      border: 0.0625rem solid var(--border);
      border-radius: 0.5rem;
      background: var(--code);
      color: var(--text);
      font-family: ui-monospace, "Cascadia Code", "SFMono-Regular", Consolas, monospace;
      font-size: 0.875rem;
      line-height: 1.55;
      overflow-wrap: anywhere;
      white-space: pre-wrap;
      word-break: break-word;
    }

    @media (max-width: 36rem) {
      main {
        width: min(calc(100% - 1rem), 64rem);
        padding: 0.5rem 0;
      }

      .diagnostic-card {
        border-radius: 0.75rem;
      }

      .context-item dt {
        display: block;
      }

      .field-key {
        display: block;
        margin-top: 0.375rem;
      }
    }

    @media (forced-colors: active) {
      .diagnostic-card,
      .context-item,
      .context-item dd,
      .field-value,
      .meta-item {
        border-color: CanvasText;
      }

      .skip-link:focus {
        outline-color: Highlight;
      }
    }

    @media print {
      :root {
        color-scheme: light;
        --page: #ffffff;
        --surface: #ffffff;
        --surface-muted: #ffffff;
        --text: #000000;
        --muted: #333333;
        --border: #777777;
        --code: #ffffff;
        --shadow: none;
      }

      body {
        min-height: 0;
      }

      main {
        width: 100%;
        padding: 0;
      }

      .skip-link {
        display: none;
      }

      .diagnostic-card {
        box-shadow: none;
      }

      .context-item {
        break-inside: avoid;
      }
    }
  </style>
</head>
<body>
  <a class="skip-link" href="#diagnostic-main">Skip to diagnostic</a>
  <main id="diagnostic-main">
    <article class="diagnostic-card">
      <header class="diagnostic-header">
        <p class="eyebrow">Kongono diagnostic</p>
        <div class="identity">
          <code class="identifier">{{identifier}}</code>
          <p class="owner">Owner: <span>{{owner}}</span></p>
        </div>
        <h1>{{title}}</h1>
      </header>
      <section class="diagnostic-section">
        <h2>What happened</h2>
        <p class="detail">{{operation}}</p>
      </section>
      <section class="diagnostic-section">
        <h2>Why it happened</h2>
        <p class="detail">{{invariant}}</p>
      </section>
      <section class="diagnostic-section">
        <h2>Context</h2>
{{context}}
      </section>
      <section class="diagnostic-section">
        <h2>How to fix it</h2>
        <p class="detail">{{remediation}}</p>
      </section>
    </article>
  </main>
</body>
</html>
```

### 6.2 Exact dynamic mappings

Owner labels:

```text
kernel                 Kernel
application_graph      Application Graph
dependency_injection   Dependency Injection
```

Field kind labels:

```text
text                   text
integer                integer
boolean                boolean
```

Disclosure labels:

```text
safe                   Safe
protected              Protected
```

Value formatting before escaping:

```text
text                   exact represented string
integer                locale-independent base-10 `(string)` form
boolean true           true
boolean false          false
```

Use the already-validated `DiagnosticValueKind` to choose formatting. A narrow
PHPDoc local refinement may tell PHPStan the value type established by the
final `TrustedDeveloperDiagnosticField` invariant. Do not add coercive fallback,
defensive exception, or unreachable default branch.

### 6.3 Exact context grammar

For an empty field list, `{{context}}` is exactly:

```html
        <p class="empty-context">No additional diagnostic context was captured.</p>
```

For a non-empty field list, `{{context}}` is `<dl>` plus one field block per
field in unchanged canonical order:

```html
        <dl class="fields">
          <div class="context-item">
            <dt>
              <span class="field-label">{{label}}</span>
              <code class="field-key">{{key}}</code>
            </dt>
            <dd>
              <div class="field-meta">
                <span class="meta-item">Kind: {{kind}}</span>
                <span class="meta-item">Disclosure: {{disclosure}}</span>
{{truncated}}              </div>
              <pre class="field-value"><code>{{value}}</code></pre>
            </dd>
          </div>
        </dl>
```

For `textWasTruncated() === true`, `{{truncated}}` is exactly:

```html
                <span class="meta-item">Truncated</span>
```

including its final LF. For `false` or `null`, it is the empty string. There is
no blank line in its place.

Every additional field block follows immediately after the preceding block's
closing `</div>` with one LF and the same indentation. The `<dl>` opens once and
closes once.

### 6.4 Escaping

Every dynamic string, including identifier, owner label, title, operation,
invariant, remediation, field label, key, kind label, disclosure label, and
formatted value, must pass exactly once through:

```php
htmlspecialchars(
    $value,
    ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5,
    'UTF-8',
    true,
)
```

Dynamic values may enter only normal element character data or `<title>` RCDATA.
They may never determine an attribute, tag, ID, class, style, selector, URL,
comment, doctype, raw-text element, script, event, or raw HTML branch.

Do not decode, normalize, trim, case-fold, sanitize, strip, pre-escape, or
double-pass any value. `double_encode: true` must preserve entity-looking input
as literal displayed text.

---

## 7. Golden fixtures

### 7.1 Full fixture input

`trusted-developer-html-full.html` must be the exact canonical rendering of:

```text
owner               application_graph
identifier          KGN-GRAPH-1009
title               Relation <source> & "target" mismatch
operation           Validate relation source & target
invariant           Source <node> must exist & remain "canonical"
remediation         Add the missing relation & retry <carefully>.

field 1
  key                entry_count
  label              Entry count
  kind               integer
  value              3
  disclosure         Safe
  truncation         null

field 2
  key                source_key
  label              Source & key
  kind               text
  value              orders<&>"'&lt;
  disclosure         Protected
  truncation         false

field 3
  key                canonical
  label              Canonical
  kind               boolean
  value              true
  disclosure         Safe
  truncation         null

field 4
  key                received_value
  label              Received value
  kind               text
  value              abcdefgh...[truncated]
  disclosure         Protected
  truncation         true
```

The field-2 value must appear in HTML exactly as:

```html
orders&lt;&amp;&gt;&quot;&apos;&amp;lt;
```

This proves markup characters, both quote forms, and entity-looking input are
encoded correctly with `double_encode: true`.

### 7.2 Empty fixture input

`trusted-developer-html-empty.html` must be the exact canonical rendering of:

```text
owner               kernel
identifier          KGN-KERNEL-1001
title               Kernel boot failed
operation           Boot kernel
invariant           Kernel state must be cold
remediation         Create one fresh kernel instance.
fields              empty list
```

### 7.3 Fixture discipline

The fixture files must:

- be hand-checked expansions of Sections 6 and 7, not runtime-generated during
  tests;
- begin with the doctype and end with one final LF;
- be UTF-8 without BOM, LF-only, with no NUL or trailing whitespace;
- contain no `{{...}}` placeholder;
- remain test-only files never read by production code; and
- be compared byte-for-byte with renderer output using `assertSame()`.

Report each final fixture's byte count and SHA-256.

---

## 8. Focused test contract

Create exactly one new PHPUnit class:

```text
Kongono\DiagnosticPresentation\Tests\Unit\TrustedDeveloperHtmlRendererTest
```

It must be final and contain exactly these ten public test methods, with no data
provider that changes PHPUnit test-case count:

```text
testShapeAndExperimentalStatusAreExact
testFullDiagnosticMatchesGoldenDocument
testEmptyContextMatchesGoldenDocument
testEveryOwnerUsesItsExactVisibleLabel
testDynamicTextIsEscapedExactlyOnceAtEveryInsertionSite
testKindsDisclosuresTruncationAndFieldOrderRemainExact
testRenderingIsDeterministicAndStateless
testMaximumValidDiagnosticRemainsWithinOutputBounds
testDocumentUsesTheExactSemanticAccessibilityGrammar
testDocumentContainsNoActiveExternalOrDynamicAttributeSurface
```

Minimum required assertions:

### Shape/status

- final and readonly;
- no parent/interface, constructor, property, or constant;
- exact Experimental `Api` attribute;
- exactly one public method with exact parameter/return type;
- no protected or magic method;
- private method names are a subset of Section 5's allowlist and every private
  helper is static.

### Golden output

- construct the exact Section 7 full and empty diagnostics;
- read each fixture as a string and compare output byte-for-byte;
- assert one final LF and no CR/NUL/BOM.

### Owner labels

- render one minimal diagnostic for each owner;
- assert exactly the corresponding visible label and no alternate owner label
  within the owner element.

### Escaping

- prove the full fixture's title, operation, invariant, remediation, field
  label, and field value exact entity sequences;
- prove the raw dangerous sequences do not appear at their insertion sites;
- prove `&lt;` input becomes visible literal `&lt;` through `&amp;lt;` source;
- prove no dynamic value appears in an attribute, style, tag, URL, or script.

### Field behavior

- prove the four fields remain in input order;
- prove exact `text`, `integer`, `boolean`, `Safe`, and `Protected` labels;
- prove lowercase `true` and canonical `3` formatting;
- prove `Truncated` occurs only for the true field;
- prove false/null states omit it without a blank placeholder.

### Determinism/lifecycle

- repeated rendering of the same instance and equivalent fresh instances is
  byte-identical;
- interleaving a different render does not change later output;
- class shape proves there is no retained state.

### Bounds

- construct the maximum 16-field valid diagnostic;
- maximize escape expansion using valid bounded `&`, quote, title, operation,
  invariant, remediation, label, and 512-byte text inputs;
- assert the complete output is no more than 98,304 bytes;
- extract the exact stylesheet and verify its bytes;
- derive the canonical static shell plus stylesheet size from the empty fixture
  by subtracting the exact escaped dynamic byte contributions (identifier and
  title each occur twice; owner, operation, invariant, and remediation each
  occur once), then assert that static total is no more than 16,384 bytes;
- assert exactly 16 context-item blocks and linear one-block-per-field growth.

### Semantic/accessibility grammar

Use `DOMDocument`/`DOMXPath` in tests only, with libxml errors contained and
restored. Prove:

- HTML5 doctype, one `html[lang=en]`, exact UTF-8/viewport/color-scheme metadata,
  and exact title;
- one static skip link to `#diagnostic-main`;
- one main, article, h1, and four h2 elements in exact visible order;
- semantic description list with field groups;
- visible kind/disclosure/truncation text;
- exact static CSS contains light/dark, forced-colors, print, focus, wrapping,
  and responsive rules;
- no animation or transition declaration.

### No active/external surface

Prove there is no script, form, button, iframe, image, external stylesheet,
remote font, refresh, redirect, preload, prefetch, beacon, inline style
attribute, event attribute, dynamic URL, or network URL. The only link is the
static fragment skip link.

The ten methods add exactly ten PHPUnit test cases. The expected complete suite
count is therefore exactly 241 tests. Report the actual assertion count; it must
increase materially from 3,973 and every test must contain meaningful
assertions.

Do not use snapshots generated inside the test, reflection to fabricate invalid
diagnostics, mocks, subclasses, serialization, eval, source rewriting, network,
browser automation, or third-party accessibility libraries.

---

## 9. Architecture enforcement update

Modify `tests/Architecture/ApiStatusConventionTest.php` only to:

1. import `TrustedDeveloperHtmlRenderer` in the existing Diagnostic Presentation
   import group; and
2. add exactly:

```php
TrustedDeveloperHtmlRenderer::class => ApiStatus::Experimental,
```

to the Diagnostic Presentation portion of `EXPECTED_STATUS` in logical order
after `TrustedDeveloperDiagnosticProjector`.

Do not otherwise change discovery, counts, marker checks, manifests, package
topology, or architecture-test behavior.

---

## 10. ADR 0037

Create exactly:

```text
docs/adr/0037-trusted-developer-html-diagnostic-renderer-implementation.md
```

Required heading and metadata:

```text
# ADR 0037: Trusted-Developer HTML Diagnostic Renderer Implementation

## Status
Accepted

## Date
2026-08-14
```

Required sections in order:

1. `Context`
2. `Decision`
3. `Rationale`
4. `Consequences`
5. `Rejected alternatives`
6. `What remains open`

Record:

- exact class FQCN, status, shape, sole public method, and no-state lifecycle;
- exact template/CSS and two golden fixtures as the canonical byte grammar;
- final-boundary escaping call and `double_encode: true` rationale;
- exact owner/kind/disclosure/value/truncation mappings;
- five semantic regions and accessibility behavior;
- determinism and 16-field/16-KiB/96-KiB bounds;
- the ten-test contract and API-status enforcement;
- package graph and dependency/tooling invariance;
- no renderer-specific failure or catch-all wrapping;
- the fact that the renderer accepts only an already-projected trusted model;
- the fact that rendering is not trust authorization or production disclosure;
  and
- the exact absence of transport/runtime integration.

Reject:

- rendering arbitrary exceptions or context markers;
- authorization inside the renderer;
- using the trusted model for production;
- dynamic attributes/CSS/URLs/scripts/raw HTML;
- templates, component frameworks, JavaScript, remote assets, AI, reflection,
  source crawling, filesystem, network, environment, locale, and time;
- state, caches, registries, service bindings, Graph nodes, and Kernel hooks;
- renderer-specific fallback/exception recursion; and
- HTTP/CLI/JSON/problem-details ownership in this class.

Keep open only:

- trusted server-controlled capability/configuration and outer adapter;
- HTTP response/status/content-type/cache/CSP ownership;
- safe copy/export formats;
- optional cause/source/stack/documentation providers;
- separate deny-by-default production model and allowlists;
- other renderers/transports and localization;
- logging, telemetry, incidents, serialization, and cross-process forms; and
- simultaneous primary/projection/rendering/cleanup failure behavior.

Do not list renderer implementation, HTML grammar, CSS bytes, escaping,
accessibility baseline, or golden tests as open after Phase 0Z.

---

## 11. Documentation reconciliation

Modify only the twenty-six documentation files listed in Section 4.2.

### `README.md`

State that the pure Experimental renderer now exists and returns a complete
HTML document, while trusted activation, serving/HTTP integration, production
presentation, and providers remain absent.

### `ARCHITECTURE.md`

Move the renderer from planned to implemented inside the existing leaf. Preserve
the exact package graph and explain that no runtime binding or transport edge is
introduced.

### `SECURITY.md`

Record implemented final-boundary escaping and continued absence of trusted
authorization. Escaped Protected data remains Protected; the renderer is not
safe for production.

### `docs/adr/0026` through `0035`

Reconcile every present-tense implementation/open-work statement across the ten
diagnostic ADRs. State that the one pure trusted-developer HTML renderer, exact
template/CSS grammar, escaping, and focused tests are implemented in Phase 0Z.
Preserve every original phase fact, schema, package boundary, and rejection.
Keep trusted activation, transport/serving, production presentation, optional
providers, other formats, and operational integrations future. Do not leave
`renderer implementation`, `exact HTML implementation`, or `static template/CSS
bytes and tests` listed as open. Preserve `projected title` and `projected
remediation` wording in ADR 0034.

### `docs/adr/0036-accessible-trusted-developer-html-diagnostic-page.md`

Add a Phase 0Z implementation note linking ADR 0037. Preserve the original
decision. Move exact template/CSS bytes and implementation tests out of open
work; keep every outer/integration item open.

### `docs/adr/README.md`

Add ADR 0037 as Accepted in numeric order with an accurate summary.

### `docs/architecture/application-graph.md`

Preserve every Graph fact while replacing the stale global claim that all
renderers remain future. The pure trusted HTML renderer is implemented outside
Graph; Graph still owns no renderer, activation, or transport behavior.

### `docs/architecture/overview.md`

State that the renderer now exists below the projector in the same leaf and
retains no state. Do not imply authorization, HTTP, or production integration.

### `docs/architecture/runtime-model.md`

Mark only the pure rendering step implemented. The outer authorization and
transport-adapter steps remain future. Calling the renderer directly remains an
explicit composition action, not a runtime hook.

### `docs/documentation-standard.md`

Reconcile the current-capability paragraph so the pure renderer is implemented
while redaction, served error pages, trusted activation, production output, and
transport representations remain planned. Preserve all documentation rules.

### `docs/doctrine.md`

Add Phase 0Z to the current implemented sequence and change only the renderer's
existence statements. Preserve the doctrine separating authorization, escaping,
and production disclosure.

### `docs/engineering/api-status.md`

Add the renderer to the Experimental examples/current inventory without
promoting related types.

### `docs/engineering/error-handling.md`

Change the selected renderer section from planned to implemented, document the
exact fixture-backed grammar and current limits, and preserve future activation,
transport, production, and provider work. Keep `projected title` and `projected
remediation` terminology.

### `docs/engineering/package-layout.md`

Update only the Diagnostic Presentation package inventory and current renderer
existence. Preserve the exact six-package topology and absent runtime binding.

### `docs/engineering/testing.md`

Record the golden-output, escaping, semantic accessibility, determinism, bounds,
and no-active-surface tests as current. Do not claim browser, assistive-
technology, HTTP, CSP, or production integration tests exist.

### `docs/glossary.md`

Change the trusted-developer HTML page entry to implemented pure renderer,
without implying a served route, debug activation, or production surface.

### `packages/diagnostic-presentation/README.md`

Document the renderer's exact use, output, security boundary, lifecycle, bounds,
failures, status, golden fixtures, and explicit non-goals. Preserve the projector
and package dependency facts.

Every changed document must use valid UTF-8 without BOM, LF only, one final LF,
no NUL, no trailing whitespace, valid repository-relative links, and precise
implemented/planned/future language.

---

## 12. Prohibited scope

Phase 0Z must not:

- add or change a Composer dependency, manifest, lock entry, autoload mapping,
  suite, static-analysis path, fixer path, plugin, or policy;
- create a new package, interface, trait, enum, exception, diagnostic ID,
  registry, factory, builder, template abstraction, or extension point;
- modify the projector, diagnostic values, field values, schemas, disclosures,
  originating exceptions, throw sites, or package markers;
- implement trusted/debug/developer activation, environment/configuration
  selection, middleware, handler, controller, catcher, adapter, service binding,
  Kernel lifecycle hook, Application Graph participation, or DI registration;
- create a served route, HTTP request/response, status mapping, content type,
  CSP/cache/header policy, RFC 9457 output, CLI command, or JSON renderer;
- create a production error model/page, allowlist, redactor, sanitizer, secret
  detector, incident reference, logger, telemetry hook, trace, metric, or store;
- add cause, source, stack, excerpt, documentation, localization, copy, export,
  browser, JavaScript, frontend, asset pipeline, component, or remote resource;
- use output buffering, global/static mutable state, cache, registry, filesystem,
  network, environment, request, locale, time, randomness, reflection, or AI in
  production rendering;
- change exact HTML/CSS bytes, labels, ordering, mappings, fixtures, or bounds
  by aesthetic preference;
- suppress a quality issue, add a PHPStan baseline, weaken tests, skip audit, or
  alter security policy;
- stage, commit, reset, restore, checkout, clean, stash, amend, rebase, tag,
  branch, add a remote, push, or rewrite history; or
- implement any later Kongono capability.

Stop rather than widening the boundary.

---

## 13. Execution and validation sequence

### Gate A: authority and identities

1. Read all Section 2.1 authorities completely.
2. Verify the externally delivered Phase 0Z directive identity.
3. Verify the zero-byte report:

```text
bytes       0
SHA-256     E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

4. Verify all Section 3 identities, counts, status inventories, ordinary-file,
   stream, mode, ref, and index conditions.
5. Stop on any mismatch.

### Gate B: pre-change quality

Run unchanged:

```powershell
composer quality
```

Expected baseline:

```text
security advisories      0
platform requirements    pass on PHP 8.5.9
PHP CS Fixer             0 of 127 files fixable
PHPStan                  0 errors
PHPUnit                  231 tests, 3973 assertions
```

Accepted Composer warnings remain limited to missing license, local unbounded
constraints, and missing curl. If the unchanged fail-closed audit is blocked
solely by managed network/cache access, retry the identical command once with
approved access. Never skip or weaken it.

### Gate C: production and fixtures

1. Create the exact renderer.
2. Create and hand-check both exact fixtures.
3. Create the ten-method test class.
4. Update only the authorized API-status inventory entry.
5. Run syntax checks on the two new PHP files and modified architecture test.
6. Run focused renderer tests.
7. Run the complete Diagnostic Presentation package tests.

### Gate D: ADR and documentation

1. Create ADR 0037.
2. Modify exactly the twenty-six authorized documents.
3. Read every created/modified file completely.
4. Reconcile all renderer current/future statements and open lists.

### Gate E: focused implementation validation

Verify:

- exact created and modified inventories;
- exact class reflection shape and Experimental status;
- only authorized imports/functions and private helpers;
- no forbidden I/O, ambient state, state retention, catch, throw, dependency,
  transport, or active content;
- canonical template/CSS, context grammar, labels, mappings, and one-final-LF;
- both golden fixture comparisons;
- exactly ten new PHPUnit test cases;
- 49 production PHP, 80 test PHP, 140 protected files, and 129 fixer files;
- architecture status inventory exactly includes the renderer;
- maximum output, static-shell-plus-stylesheet, complexity, and field-count
  proofs;
- strict HTML fixture/document encoding and file safety;
- all local Markdown links resolve;
- `git diff --check` passes;
- Composer manifests and lockfile remain byte-identical;
- no unexpected mode, stream, reparse, case, staged, ref, or history change.

### Gate F: post-change canonical quality

Run unchanged:

```powershell
composer quality
```

Required post-change results:

```text
security advisories      0
platform requirements    pass
PHP CS Fixer             0 of 129 files fixable
PHPStan                  0 errors
PHPUnit tests            exactly 241
```

Report the exact assertion total. It must be higher than 3,973. The same narrow
environment-only retry rule applies.

### Gate G: report and final proof

Only after Gates A through F pass:

1. populate `docs/task reports/Phase_0Z.md` using Section 16;
2. verify strict UTF-8/LF/no-BOM/no-NUL/no-trailing-whitespace/one-final-LF;
3. compute final report bytes and uppercase SHA-256 after the last write;
4. verify it remains untracked, unstaged, absent from HEAD, and ordinary;
5. verify the exact final state in Section 14; and
6. return the report identity outside the report body.

If any earlier gate fails, leave the report zero bytes and return a blocked
report in the executor response.

---

## 14. Required final repository state

Successful final state:

```text
branch                     main
local branches             1
HEAD                       7d4df1f4e31b9bdb6cd134b44682719bdb17aa6f
HEAD tree                  b1d0057925158e0f0f37ca13694d1d0d525ce5f0
HEAD parent                75749a37a184996203e569e039bddbc3ec10cdf7
commit count               6
remotes                    0
tags                       0
tracked modifications      32
untracked paths            39
total status paths         71
staged paths               0
```

The five implementation-created paths are exactly Section 4.1. The two caller
paths account for the other increase from the Phase 0Y.1 state. Exactly these
two previously clean tracked documents become modified:

```text
docs/architecture/application-graph.md
docs/documentation-standard.md
```

Every other authorized existing path was already modified or untracked. This
exact transition raises the tracked-modification count from 30 to 32.

The cumulative tracked diff statistic is not preselected. Report its exact
final value. HEAD, index, refs, history, package graph, manifests, lockfile,
unrelated files, and historical records remain unchanged.

---

## 15. Strict acceptance criteria

Every item must pass:

1. All authorities and relevant source/tests were read completely.
2. Directive, zero-byte report, caller records, architecture records, and
   baseline identities matched.
3. Pre-change canonical quality passed exactly.
4. Exactly five authorized files were created.
5. Exactly twenty-seven authorized existing files were modified by Phase 0Z.
6. The renderer FQCN, Experimental status, modifiers, members, imports, and sole
   public method are exact.
7. The renderer accepts only `TrustedDeveloperDiagnostic` and retains no state.
8. Canonical HTML/CSS, five regions, mappings, context grammar, order, and final
   LF are exact.
9. Every dynamic value is escaped once with the exact HTML5 call and never
   enters an active/attribute/style/URL/raw context.
10. Full and empty golden fixtures match byte-for-byte and have strict file
    properties.
11. The ten exact tests cover shape, goldens, owners, escaping, field semantics,
    determinism, bounds, accessibility grammar, and no active surface.
12. Maximum output is at most 98,304 bytes; canonical static shell plus
    stylesheet is at most 16,384 bytes; complexity and 16-field bounds hold.
13. API-status architecture enforcement includes exactly the new Experimental
    declaration.
14. ADR 0037 is Accepted, exact, complete, and leaves only genuine future work.
15. Documentation truthfully moves only the pure renderer to implemented state.
16. Trust authorization, HTTP/transport serving, and production presentation
    remain absent and separate.
17. No renderer-specific exception, fallback recursion, package edge,
    dependency, manifest, lock, tooling, or unrelated behavior changed.
18. Counts are exactly 49 production PHP, 80 test PHP, 140 protected files, and
    129 fixer files.
19. Links, encoding, whitespace, fixtures, modes, streams, ordinary-file, and
    diff checks pass.
20. Post-change quality passes with 0 advisories, 0 fixer findings, 0 PHPStan
    errors, exactly 241 tests, and an increased assertion total.
21. Final Git state is exactly 32 modified, 39 untracked, 71 total, zero staged,
    with unchanged HEAD and refs.
22. Completion report is structurally exact, evidence-backed, externally
    identified, and contains no assumption presented as verification.
23. No prohibited implementation, Git operation, policy weakening, or later
    capability occurred.

Any failed or unverified item makes status `Blocked` or `Partial`, never
`Complete`.

---

## 16. Completion report

Populate only after complete success, with exactly these headings:

```text
# PHASE 0Z TASK COMPLETION REPORT

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

The report must:

- enumerate every created/modified path and its role;
- report environment versions;
- report pre/post quality and focused suite counts;
- report the renderer, both fixtures, and ADR 0037 byte/hash identities;
- report the final protected digest and exact file counts;
- report maximum rendered bytes, stylesheet bytes, and derived canonical static
  shell plus stylesheet bytes;
- report exact link, text, file-safety, status, and diff evidence;
- state no executor-authored architecture or dependency change;
- distinguish the implemented pure renderer from absent activation, transport,
  and production behavior;
- enumerate PASS/FAIL for all 23 acceptance criteria; and
- end exactly:

```text
Await architect review before any next phase.
```

Do not put the report's own final byte count or SHA-256 inside its body. Return
them separately after final read-back.

---

## 17. Required final outcome

Phase 0Z ends with one real but deliberately isolated capability: given a valid
already-projected trusted developer diagnostic, Kongono can synchronously return
one complete, clean, accessible, deterministic, escaped HTML document.

The renderer must be useful enough that a developer can immediately see what
failed, why it failed, the bounded relevant context, and how to fix it. Its
golden fixtures make visual grammar changes explicit and reviewable. Its exact
escaping prevents diagnostic content from becoming markup. Its stateless bounds
remain safe for persistent workers.

That capability is not a served error page. No request reaches it automatically,
no environment enables it, no handler catches exceptions for it, and no
production audience may receive it. Those remain separate future decisions.

Do not begin another phase.
