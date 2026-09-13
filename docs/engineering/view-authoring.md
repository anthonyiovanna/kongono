# View Authoring

## Status

Phase 18A accepts this engineering contract as documentation. Phase 18B adds
the callable neutral value, schema, identity, request, result, failure, limit,
output-context, lifecycle, diagnostic, and exception contracts described by
[ADR 0181](../adr/0181-neutral-view-contract-and-closed-presentation-values.md).
Phase 18C accepts [ADR 0182](../adr/0182-deterministic-structured-view-source-and-compiled-artifact.md)
and the exact structured-source and build APIs below. Phase 18D accepts
[ADR 0183](../adr/0183-exact-current-compiled-view-runtime-and-bounded-rendering.md)
and the exact loader/renderer API documented in the
[compiled-view runtime guide](../architecture/compiled-view-runtime.md). Phase
18E accepts [ADR 0184](../adr/0184-explicit-completed-view-to-psr-7-response-adaptation.md)
and the exact completed-result adapter documented in the
[compiled-view HTTP guide](../architecture/compiled-view-http.md). Phase 18F
accepts [ADR 0185](../adr/0185-supported-compiled-view-application-workflow.md),
the exact-current application workflow, explicit view commands, and copy-once
example documented in the [application workflow guide](../architecture/compiled-view-application-workflow.md).
Phase 18G accepts [ADR 0186](../adr/0186-visibility-safe-compiled-view-inspection-and-developer-intelligence.md)
and the offline, read-only, stateless evidence boundary documented in the
[inspection guide](../architecture/compiled-view-inspection-and-intelligence.md).
Phase 18H accepts the root-owned [local acceptance](../architecture/phase-18-server-rendered-presentation-acceptance.md),
and Phase 18 is closed.

The closed grammar, compiler, canonical artifact, generated PHP evidence,
validators, explicit publisher, exact-current loader, contextual escaper, and
bounded stateless renderer, explicit application workflow, three view commands,
and copy-once skeleton exist. No source is parsed or compiled during rendering.
Generated code and the deployment filesystem are trusted inputs. Phase 18G inspection receives one already
exact-current profile, performs structural validation in memory without
execution, and emits only the declared Safe/Protected evidence schema.

## Intended workflow

The authoring path is deliberately explicit through closed Phase 18:

1. create or select one application-owned view source;
2. declare its closed bounded input schema;
3. run the deterministic write-free check or authorized build;
4. review stable actionable diagnostics;
5. construct immutable presentation data;
6. load the one caller-selected exact-current contained artifact and render one
   logical identity under explicit byte and step limits; and
7. optionally adapt the completed neutral result to PSR-7
   under explicit application policy.

Routing does not select templates. Templates do not query repositories,
services, containers, request globals, sessions, credentials, clocks, or the
filesystem. API-only, CLI, worker, agent, and headless applications need not
install the optional presentation packages.

## Current Phase 18B contract example

The neutral request boundary is callable without selecting a source format or
performing rendering:

```php
use Kongono\ViewContract\ContractVersion;
use Kongono\ViewContract\Identity\CompiledViewIdentity;
use Kongono\ViewContract\Identity\LogicalViewIdentity;
use Kongono\ViewContract\Identity\RenderOperationIdentity;
use Kongono\ViewContract\Render\RenderLimits;
use Kongono\ViewContract\Render\RenderRequest;
use Kongono\ViewContract\Schema\PresentationFieldSchema;
use Kongono\ViewContract\Schema\PresentationValueSchema;
use Kongono\ViewContract\Schema\ViewContextSchema;
use Kongono\ViewContract\Value\StringValue;
use Kongono\ViewContract\Value\ViewContext;

$schema = new ViewContextSchema(PresentationValueSchema::record([
    new PresentationFieldSchema('title', PresentationValueSchema::string(160), true),
]));

$request = new RenderRequest(
    ContractVersion::Version1,
    new RenderOperationIdentity('account-summary-render-1'),
    new LogicalViewIdentity('account.summary'),
    new CompiledViewIdentity($applicationSuppliedLowercaseSha256),
    $schema,
    new ViewContext(['title' => new StringValue('Account')]),
    new RenderLimits(1_048_576, 100_000),
);
```

This constructs and validates inert evidence only. The compiled identity does
not select or load anything, and no compiler, renderer, or HTTP adapter is
invoked.

## Current Phase 18C source and build example

The accepted source is one explicit duplicate-aware JSON byte sequence with
exactly one terminal LF. This is a complete version-1 example:

```json
{"schema":"kongono.view-source-set","version":1,"views":[{"id":"account.summary","context":{"kind":"record","fields":[{"name":"title","required":true,"schema":{"kind":"string","max_bytes":160}},{"name":"notices","required":true,"schema":{"kind":"list","max_items":20,"item":{"kind":"string","max_bytes":500}}}]},"body":[{"kind":"element","name":"section","attributes":[{"name":"aria-label","value":{"kind":"value","path":"$.title"}}],"children":[{"kind":"value","path":"$.title"},{"kind":"each","path":"$.notices","as":"notice","max_items":20,"body":[{"kind":"element","name":"p","attributes":[],"children":[{"kind":"value","path":"@notice"}]}]}]}]}]}
```

Logical identity `account.summary` is not a path. The declared context admits
only the named values and exact types. Missing, extra, duplicate, unsupported,
oversized, stale, or mixed-version input fails before rendering.

Build and in-memory currentness validation are explicit:

```php
use Kongono\ViewBuild\Artifact\CompiledViewArtifactBuilder;
use Kongono\ViewBuild\Artifact\CompiledViewArtifactValidator;
use Kongono\ViewBuild\Source\ViewSourceParser;

$source = (new ViewSourceParser())->parse($applicationOwnedBytes);
$artifact = (new CompiledViewArtifactBuilder())->build($source);
$validated = (new CompiledViewArtifactValidator())->validateCurrent(
    $source,
    $artifact->metadataBytes(),
    $artifact->generatedPhpBytes(),
);
```

This code performs no generated-PHP loading or rendering. Publication is a
separate explicit `CompiledViewArtifactPublisher::publish()` call requiring one
existing absolute canonical writable ordinary directory.

The Phase 18B `OutputContext` vocabulary contains exactly HTML text and quoted
HTML attributes, but performs no escaping. URL, JavaScript,
CSS, raw markup, and event-handler contexts are denied until later typed rules
and tests are accepted.

## Planned build and check pseudocode

```text
PLANNED PSEUDOCODE - COMMANDS DO NOT EXIST

kongo view:check --source=<explicit-source> --machine
kongo view:build --source=<explicit-source> --output=<explicit-directory>
```

Check or plan is non-mutating. Build publication requires explicit authority
and produces deterministic, content-addressed, ordinary PHP and metadata by an
atomic, contained, initial-only, no-clobber operation. Both paths use the same
validation pipeline. Runtime compilation and hidden development recompilation
are prohibited.

A future successful neutral outcome should identify bounded source/model/
artifact/generated identities and counts without exposing source bodies,
values, rendered fragments, or private paths. Human and versioned machine
presentation derive from that same outcome; automation never prompts silently.

## Planned application pseudocode

```php
<?php

// PLANNED PSEUDOCODE - THESE APPLICATION SERVICES AND METHODS DO NOT EXIST.
$viewModel = AccountSummaryViewModel::fromPresentationValues(
    title: 'Account',
    ownerName: $displayName,
    notices: $notices,
);

$renderRequest = RenderRequest::forLogicalView(
    logicalView: 'account.summary',
    exactArtifact: $compiledViewIdentity,
    context: $viewModel,
    options: $explicitRenderOptions,
);

$renderResult = $renderer->render($renderRequest);

$response = $viewHttpAdapter->toResponse(
    result: $renderResult,
    policy: $applicationOwnedResponsePolicy,
);
```

This example communicates ownership only. Application code constructs the
view model and chooses the logical identity. The renderer receives no domain
entity, repository, container, request, security context, session, or
capability port. HTTP status, headers, security policy, and response creation
remain outside the neutral render contract.

## Data rules

Prefer immutable presentation-specific records whose fields exactly match the
declared context. Convert domain state before entering presentation. Keep
authorization and protected reads in application or capability boundaries;
rendering already-authorized data does not authorize its acquisition.

Do not pass:

- entities with lazy loading or behavior;
- repositories, database sessions, HTTP requests, containers, or services;
- arbitrary arrays or object graphs with undeclared members;
- credentials, secrets, session or CSRF evidence, security-policy bodies, or
  provider payloads; or
- callables, capability ports, clocks, random sources, streams, or filesystem
  handles.

Future lists and records have explicit item, depth, and byte bounds. Layouts,
includes, and components—if selected—resolve only inside the compiled source
set and have cycle, depth, traversal, and count limits.

## Output-safety rules

Use ordinary strings for ordinary data. The declared compile-time context owns
escaping. Do not pre-escape values, concatenate markup, pass a raw flag, depend
on regex sanitization, or assume one escaping rule works for every browser
context.

Any future trusted-markup value requires a separate accepted construction
boundary. It must be explicit, context-scoped, provenance-aware, non-forgeable
from ordinary strings, and invalidated by concatenation with untrusted data.
No such value exists in Phase 18B.

The renderer does not establish URL safety, CSP, CSP nonces, asset integrity,
security headers, sessions, CSRF, authentication, authorization, redirects, or
browser compatibility. Supply those through their separately governed
application and HTTP boundaries.

## Diagnostics and failure review

Future trusted developer diagnostics distinguish:

- invalid or unsupported source/context;
- stale or mismatched artifact;
- missing, extra, duplicate, or type-invalid context;
- exceeded source, depth, iteration, component, temporary, or output bound;
- rendering or retirement failure; and
- HTTP adaptation failure.

Review the stable diagnostic identifier, bounded logical identity or source
location when disclosure is independently allowed, remediation, and exact
evidence version. Never place input values, rendered bytes, private paths,
credentials, session/CSRF material, provider payloads, or native stack
arguments in reports, logs, Graph facts, or command output.

Production presentation remains generic and non-disclosing. A build or render
failure does not authorize trusted diagnostics, partial output, fallback
templates, retry, or a stale artifact.

## Lifecycle and concurrency review

One render is synchronous and operation-scoped. The runtime accepts and
releases operation data exactly once. Partial output is failure, not a result.
Primary and cleanup failures remain visible and distinct.

Review sequential, overlapping, malformed, oversized, and cleanup-failed
operations in one persistent process. They must share only immutable
exact-current evidence. Reject template globals, mutable registries, static
current render state, implicit output buffers, shutdown hooks, destructor
cleanup, background workers, hot reload, and retained request or fragment state.

## Review checklist

- The change is within the separately authorized Phase 18 row.
- Current implementation and planned pseudocode are labeled accurately.
- Application code, not routing, selects the logical view and data.
- Neutral rendering has no PSR-7 dependency.
- Inputs are closed, typed, bounded, and presentation-specific.
- Context is known at compile time; unsupported contexts fail closed.
- No runtime parsing, compilation, discovery, Reflection, `eval`, service
  location, ambient state, or dynamic include exists.
- Artifacts and generated PHP are deterministic, content-addressed,
  exact-current, contained, and no-clobber before loading.
- Output, depth, iteration, component, and temporary-state bounds are explicit.
- Partial output cannot become success and cleanup truth is preserved.
- Diagnostics are actionable, bounded, and non-disclosing.
- HTTP and browser-security policy remain application-owned.
- API-only and non-HTML applications remain unaffected.

## Performance review

Expect build-time parsing and validation and direct ordinary PHP calls at
runtime, but make no performance claim without representative cold and warm
measurements. Measurements must include contextual escaping, bounds, failures,
sequential reuse, and overlap. OPCache suitability and content-addressed
invalidation are directions, not current guarantees.

## API and compatibility review

Future application-selected contracts begin Experimental. Grammar, parser,
compiler, generated helpers, loaders, contextual escapers, source maps, and
lifecycle internals remain Internal by default. Do not document Stable, 1.0,
production readiness, template-engine compatibility, browser certification,
security certification, accessibility certification, or automatic migration.

## Related material

- [ADR 0179](../adr/0179-server-rendered-presentation-and-compiled-view-boundary.md)
- [ADR 0180](../adr/0180-context-aware-output-safety-and-presentation-lifecycle.md)
- [Server-rendered presentation](../architecture/server-rendered-presentation.md)
- [Phase 18 roadmap](../architecture/phase-18-roadmap.md)
- [Routing model](../architecture/routing-model.md)
- [Runtime model](../architecture/runtime-model.md)
- [Generated wiring](generated-wiring.md)
- [Error handling](error-handling.md)
- [API status](api-status.md)
