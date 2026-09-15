# Asset Application Workflow

## Status

Phase 19F and [ADR 0194](../adr/0194-supported-asset-application-workflow-and-explicit-view-integration.md)
accept the optional Experimental `kongono/asset-application` package, three
supported `kongo` commands, and the copy-once `asset-minimal` slice. Phase 19G
accepts a separate optional [asset-inspection](asset-inspection-and-intelligence.md)
upper evidence leaf over one supplied current result. Phase 19H accepts the
root-owned [external acceptance](phase-19-external-asset-acceptance.md) and
exact checkpoint. Phase 19A-H are Accepted and Phase 19 is closed.

## Package boundary

The package composes only API Metadata, Diagnostics, Asset Contract, Asset
Build, the neutral Asset Adapter Contract, and Asset Resolution. It does not
require Vite, Console, HTTP, View, Application Graph, DI, Kernel, or another
application package. The Public exception-family interface is the package's
only Public declaration; application values and workflows remain Experimental,
and guards, diagnostics, and concrete foundations remain Internal.

## Canonical source document

One application supplies an absolute canonical application root, a contained
source-document path, and a contained existing output directory. The UTF-8 LF
Version 1 JSON document is closed and complete:

```json
{"schema":"kongono.asset-application-source","version":1,"contract_version":1,"declarations":[{"identity":"app.style","kind":"style","content_type":"text/css","source_path":"assets/app.css"}],"dependencies":[],"entry_points":["app.style"]}
```

Paths are strict application-relative names. The loader reads only those
named ordinary files and rejects links, aliases, changes during reading,
oversized inputs, open schemas, duplicates, invalid UTF-8, and invalid closed
contract values. It performs no directory discovery or import parsing.

## Native plan, apply, check, and resolve

Application code constructs the boundary directly:

```php
$definition = new AssetApplicationDefinition($root, $sourceFile, $outputDirectory);
$workflow = new AssetApplicationWorkflow();
$plan = $workflow->plan($definition);       // write-free
$current = $workflow->build($definition);   // explicit publication, then check
$checked = $workflow->check($definition);   // write-free exact-current proof
$resolved = $workflow->resolve($definition, $logicalIdentity, $policy);
```

Plan never creates a directory or artifact. Build uses only the accepted
content-addressed no-clobber publisher. Check requires the exact expected
directory, artifact bytes, and complete ordinary-file inventory, then invokes
lower structural/currentness validation. Resolve accepts no path and performs
no byte serving; it returns bounded URL, content, cache, ETag, optional
integrity, and inert preload metadata under the caller's policy.

The supported process edge exposes:

```text
kongo asset:build --plan  --application-root=... --source-file=... --output-directory=...
kongo asset:build --apply --application-root=... --source-file=... --output-directory=...
kongo asset:check         --application-root=... --source-file=... --output-directory=...
kongo asset:resolve       --application-root=... --source-file=... --output-directory=... --logical-identity=app.style --public-base-url=/assets/ --cache-policy="public, max-age=31536000, immutable" --integrity-policy=require_artifact_sha384 --preload-policy=honor_artifact
```

Human and `--json` modes present the same Safe record. Paths and bytes are
never result fields.

## Explicit adapter injection

An application that separately selects an adapter passes it directly:

```php
$result = $workflow->buildWithAdapter($adapter, $authenticatedRequest);
```

The workflow checks request/result identity relationships, fully validates the
neutral artifact and output set, and normalizes only accepted evidence for
resolution. There is no adapter registry, Vite dependency, shell, plugin
discovery, install, network, retry, or fallback in this package.

## No-asset applications

An application with no browser assets installs none of the Asset Platform.
An empty declaration set is also supported when an application wants the
contract but currently declares no assets. Neither case triggers discovery,
publication, or process activity.

## Explicit View context construction

The application, not either lower package, chooses presentation field names:

```php
$schema = new ViewContextSchema(PresentationValueSchema::record([
    new PresentationFieldSchema('stylesheet_url', PresentationValueSchema::string(2048), true),
]));
$context = new ViewContext([
    'stylesheet_url' => new StringValue($resolvedSummary->publicUrl()),
]);
```

This is ordinary Phase 18 data construction after resolution. It grants no
HTML, response, CSP, nonce, crossorigin, credential, deployment, or effect
authority. Asset code neither selects nor renders a view.

## Copy-once ownership and isolation

`skeletons/asset-minimal` is repository-owned template material. Once copied,
all non-generated files are application-owned and never automatically upgraded
or merged. Its lockfile is generated by Composer, dependencies are mirrored
with path repositories, and generated content is ignored. Build, check, and
resolve emit deterministic LF-terminated JSON and retain no operation state.

Persistent runtimes must construct or reuse only immutable definitions and
policies; each workflow call rebuilds current evidence from explicit inputs.
Diagnostics are bounded and nondisclosing. There is no singleton, mutable
cache, watcher, daemon, server, background worker, hidden build, or cleanup
hook.
