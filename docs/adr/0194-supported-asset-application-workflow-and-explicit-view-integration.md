# ADR 0194: Supported asset application workflow and explicit View integration

## Status

Accepted.

## Context

Phases 19B-E provide neutral declarations, deterministic native and adapter
build evidence, and exact-current resolution. Applications still need a
supported composition boundary that reads one explicit source document,
publishes only through the accepted no-clobber publisher, checks currentness,
and resolves metadata under application-owned browser policy. That composition
must remain optional and must not couple assets to Console, HTTP, View, Vite,
or framework-global state.

## Decision

Add the optional Experimental `kongono/asset-application` package. It accepts
one absolute application root, one contained canonical source-document path,
and one contained existing output directory. The closed Version 1 source
document contains complete declarations, dependencies, entry points, and
explicit application-relative source paths. Source bytes remain separate.
Only the named ordinary files are read, with containment, size, link,
encoding, and stable-read checks; there is no scan, glob, discovery, import
parsing, environment lookup, network access, or mutable registry.

The native workflow has four explicit operations. `plan` reconstructs the
expected native result and assesses the exact target without writing. `build`
publishes through the Phase 19C content-addressed no-clobber publisher and then
checks the result. `check` reads exactly the expected artifact and complete
output inventory and revalidates it against freshly loaded current sources.
`resolve` first checks currentness and then invokes the Phase 19E resolver with
the caller's logical identity and explicit public-base, cache, integrity, and
preload policy. No operation retains mutable state between calls.

The application package also accepts a directly supplied neutral
`AssetBuildAdapter` and authenticated `AdapterBuildRequest`. It invokes no
adapter by name and performs no discovery. Adapter output is complete untrusted
evidence until the neutral validator accepts it. The package does not depend on
the Vite implementation.

Extend the supported `kongo` edge with exactly `asset:build`, `asset:check`, and
`asset:resolve`. Build is plan/apply capable; check and resolve are read-only.
Human and JSON presentation consume the same bounded Safe result record.
Failures map to the existing stable process categories without paths, source
bytes, output bytes, foreign messages, or exception text.

Add the copy-once `asset-minimal` application slice. It uses the native path,
publishes only beneath its ignored application-owned output directory, exposes
exactly three matching Composer scripts, and owns its explicit policy. Its
View example maps an already resolved URL and integrity value into ordinary
Phase 18 presentation values in application code. Asset code does not discover
views, emit tags, mutate templates, inject globals, or make any View package
depend on an asset package.

## Consequences

No-asset applications install no asset package. Native/no-bundler applications
need no Node-family runtime. Adapter users inject a selected neutral adapter;
the Vite package remains separately optional. Copying the skeleton transfers
ownership of its non-generated files to the application, with no automatic
upgrade, merge, install, or migration behavior.

Rejected alternatives include a global manifest, hidden or request-time
builds, a Vite dependency, service location, mutable current pointers,
newest-artifact selection, stale fallback, watcher/HMR, tag-emitting helpers,
automatic installation, and direct View-package coupling.

This decision adds no Phase 19G inspection or intelligence, Phase 19H external
acceptance/checkpoint, HTTP byte serving, response/header emission, CDN or
storage deployment, CSP/nonce choice, frontend framework, accessibility,
performance, security certification, Stable/1.0 promise, or Phase 19 closure.

## Related material

- [Asset application workflow](../architecture/asset-application-workflow.md)
- [Asset Platform](../architecture/asset-platform.md)
- [Exact-current asset resolution](../architecture/exact-current-asset-resolution.md)
- [Compiled-view application workflow](../architecture/compiled-view-application-workflow.md)
- [Phase 19 roadmap](../architecture/phase-19-roadmap.md)
