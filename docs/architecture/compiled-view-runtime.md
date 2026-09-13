# Compiled View Runtime

## Status and boundary

Phase 18D accepts [ADR 0183](../adr/0183-exact-current-compiled-view-runtime-and-bounded-rendering.md)
and the optional Experimental `kongono/view-runtime` package. Phase 18E accepts
the separate [compiled-view HTTP adapter](compiled-view-http.md). Phase 18A-F
are Accepted, and Phase 18G separately accepts the optional
[compiled-view inspection leaf](compiled-view-inspection-and-intelligence.md).
Phase 18H's separate [local acceptance](phase-18-server-rendered-presentation-acceptance.md)
is Accepted and Phase 18 is closed.

The runtime loads one caller-selected exact-current Phase 18C artifact and
renders one neutral request synchronously. It reads only `artifact.json` and
`compiled.php`, authenticates their identity relationship, performs one fixed
generated-program load, escapes dynamic strings for their compiler-selected
HTML context, enforces explicit byte and step limits, and returns a neutral
result or failure. It does not parse or compile source during loading or
rendering.

Generated code and the deployment filesystem are trusted inputs. Generated PHP
runs as ordinary in-process code, not in a sandbox. Deployments must protect
the selected artifact root against hostile replacement.

## Preconditions

- Install the optional View Contract, View Build, and View Runtime packages.
- Build and publish an exact Phase 18C artifact before runtime startup.
- Supply canonical absolute paths using forward slashes. The artifact directory
  must be the root's direct child named
  `kongono-view-v1-<compiled-sha256>`.
- Supply the exact lowercase 64-hex compiled identity chosen by the application
  or deployment boundary.
- Build the `RenderRequest` from the artifact's exact logical view, exact
  compiled identity, matching closed context schema and values, and explicit
  finite limits.

The loader rejects relative, linked, noncanonical, displaced, missing,
oversized, unstable, malformed, stale, or identity-inconsistent evidence. It
does not scan the root or fall back to another artifact.

## Complete small example

The build/deployment step has already published a view named `welcome` with one
required string field named `name`. The application supplies the published
identity and paths explicitly:

```php
<?php

use Kongono\ViewContract\ContractVersion;
use Kongono\ViewContract\Failure\RenderFailure;
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
use Kongono\ViewRuntime\Artifact\CompiledViewArtifactLoader;
use Kongono\ViewRuntime\Render\CompiledViewRenderer;

$compiled = new CompiledViewIdentity($applicationSelectedSha256);
$loaded = (new CompiledViewArtifactLoader())->load(
    'C:/srv/example/views',
    'C:/srv/example/views/kongono-view-v1-' . $compiled->value(),
    $compiled,
);

$schema = new ViewContextSchema(PresentationValueSchema::record([
    new PresentationFieldSchema('name', PresentationValueSchema::string(80), true),
]));
$request = new RenderRequest(
    ContractVersion::Version1,
    new RenderOperationIdentity('request-0001'),
    new LogicalViewIdentity('welcome'),
    $compiled,
    $schema,
    new ViewContext(['name' => new StringValue('Ada & Lin')]),
    new RenderLimits(maxOutputBytes: 65536, maxRenderSteps: 4096),
);

$outcome = (new CompiledViewRenderer())->render($loaded, $request);
if ($outcome instanceof RenderFailure) {
    // Handle the closed failure kind; there are deliberately no partial bytes.
    handleRenderFailure($outcome->kind());
} else {
    $htmlBytes = $outcome->output()->bytes();
    $sha256 = $outcome->metadata()->outputSha256();
}
```

`CompiledViewArtifactLoader::load()` throws a View Runtime exception when the
deployment evidence cannot be safely authenticated or is stale. Treat that as
a load/deployment failure before rendering. `CompiledViewRenderer::render()`
returns `RenderResult|RenderFailure` for recoverable request, schema, context,
bound, or generated-execution outcomes. A retirement defect throws
`RenderRetirementException` because returning success or replacing a primary
failure would be misleading.

## Rendering and escaping

Each call creates a fresh operation-local buffer and counters. The request's
`RenderLimits` are exact: `maxOutputBytes` ranges from 1 through 8,388,608 and
`maxRenderSteps` from 1 through 1,000,000. Every generated `step()` execution,
including each loop iteration, counts. Additions are rejected before the limit
would be exceeded. Failure exposes no accumulated output.

Only a `StringValue` may reach a dynamic generated value operation. HTML text
encodes `&`, `<`, and `>` while leaving quotes as text. Quoted attributes also
encode both quote characters. Encoding is explicit UTF-8 with double encoding,
so data that looks like `&amp;` becomes `&amp;amp;`. Unsupported and future
contexts fail closed; there is no raw-markup escape hatch.

## Persistent-runtime behavior

An already-loaded generated class is reusable only if its exact class name,
source identity, model identity, and callable surface match the newly validated
artifact. This is immutable PHP class-table reuse, not a mutable registry or
cache. Every render still receives a new operation, sink, buffer, counters, and
retirement path, so sequential and cooperatively Fiber-overlapped operations do
not share presentation state.

## Ownership and exclusions

Applications select handlers, views, compiled identities, presentation data,
limits, and deployment paths. Routing still selects handlers only. HTTP
response status, headers, CSP, sessions, CSRF, authentication, authorization,
assets, commands, workflows, inspection, and external acceptance remain
application-owned or separate; Phase 18G inspection and Phase 18H local
acceptance are accepted separately. API-only, CLI, worker,
agent, and headless applications remain first-class and install no view package
unless they choose this optional capability.

The runtime provides no runtime compiler, source discovery, general include,
Reflection discovery, `eval`, output-buffer ownership, stream, watcher, hot
reload, global cache, container lookup, ambient request state, filesystem
write, network access, or compatibility/performance/production-readiness
claim.
