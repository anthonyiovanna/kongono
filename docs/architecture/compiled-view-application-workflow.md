# Compiled View Application Workflow

## Status

Phase 18F accepts [ADR 0185](../adr/0185-supported-compiled-view-application-workflow.md),
the optional Experimental `kongono/view-application` package, three supported
`kongo` commands, and the copy-once `skeletons/view-minimal` example. Phase
18G separately accepts [ADR 0186](../adr/0186-visibility-safe-compiled-view-inspection-and-developer-intelligence.md)
and the optional [inspection leaf](compiled-view-inspection-and-intelligence.md).
Phase 18A-H are Accepted and Phase 18 is closed by the separate
[local acceptance](phase-18-server-rendered-presentation-acceptance.md).
Phase 19F does not change this package: application code may explicitly map an
already resolved asset URL and integrity value into ordinary `StringValue`
fields under its own `ViewContextSchema`. No View package depends on assets.

## Boundary

```text
explicit application root + one contained source + output root
  -> write-free plan
  -> explicit build and content-addressed no-clobber publication
  -> write-free exact-current check
  -> authenticated one-artifact load
  -> one bounded render
  -> exact RenderFailure or explicitly policy-owned PSR-7 response
```

The application package composes accepted lower capabilities. It does not own
routing, request parsing, response emission, listener behavior, browser-
security decisions, sessions, authorization, error-page choice, or application
effects.

## Build and currentness

The definition carries three explicit absolute canonical paths: application
root, one contained source file, and one contained existing output directory.
The stable source read rejects linked, replaced, nonordinary, oversized,
malformed, or changed evidence and passes the exact bytes once to the Phase 18C
parser. No scan, glob, extension discovery, include, require, or source
execution occurs.

Plan performs the source read, parse, deterministic artifact build, and exact
target assessment without writing. Build publishes only through
`CompiledViewArtifactPublisher`, preserves no-clobber behavior, and then proves
currentness. Check computes the expected artifact and reads only its exact
`artifact.json` and `compiled.php`; it does not load or execute generated PHP.
No check or run method builds implicitly.

## Load and run

The factory receives one `CurrentViewApplicationBuild` plus explicit PSR-17
factories. The Phase 18D loader authenticates exactly the selected contained
artifact. Source, compiled, model, generated, and view-count evidence is
verified before the application is returned.

Each run first checks the request's compiled identity. It then renders once.
The exact lower `RenderFailure` is returned without HTTP adaptation or a
fabricated error response. A successful result is adapted once under the
supplied `ViewResponsePolicy`. The application retains no request, context,
policy, result, failure, response, or stream state between operations.

## Commands

The closed command family adds `view:build`, `view:check`, and `view:inspect`.
Each requires the ordinary `application-root`, `output-directory`, and
`source-file` inputs and disables interaction. Build is mutation-capable:
`--plan` is write-free and `--apply` explicitly publishes then checks. Check
requires current output. Inspect performs the same deterministic plan and
target assessment without publishing, loading, rendering, repairing, or
requiring current output.

Only fixed schema/version, source/compiled/model/generated identities,
content-addressed basename, bounded view count, current/publication facts, and
applicable execution intent are exposed. Paths, sources, schemas, values,
generated PHP, rendered bytes, headers, reasons, foreign messages, traces,
environment, and timestamps are omitted.

## Copy-once example

`skeletons/view-minimal` owns one view source with one required string and an
explicit 200/`OK` HTML policy. `GET /` renders the LF-terminated visible text
`Hello from Kongono.`; unmatched routing returns the example's explicit plain
404. The additional composition source and build-output ignore file are needed
for the real accepted web-runtime path.

The completed-view adapter verifies its derived `Content-Length` before the
example removes that one transport-framing header at its application-owned CGI
delivery boundary; the accepted CGI emitter computes the final length itself.

The example scripts are exactly `view:build`, `view:check`, and `view:run`.
Build publishes deterministic view and composition artifacts, check is write-
free, and run checks both artifacts before loading. Run performs one bounded
local CGI request; it never listens, binds a port, forks, watches, daemonizes,
hot reloads, deploys, or claims production readiness.

## Diagnostics and exclusions

`KGN-VIEW-APPLICATION-1850` through `KGN-VIEW-APPLICATION-1854` distinguish
invalid definition/profile evidence, unsafe source evidence, workflow failure,
load/currentness failure, and application operation failure. Context uses only
closed Safe tokens. Previous throwables are retained where applicable without
exposing their messages or traces.

There is no runtime compilation, discovery, fallback, retry, mutable pointer,
cache, cleanup/pruning, automatic migration, installer, registry, service
locator, watcher, daemon, asset pipeline, deployment manager, frontend
toolchain, Stable/1.0, or certification claim.

## Related material

- [ADR 0185](../adr/0185-supported-compiled-view-application-workflow.md)
- [Compiled View Build](compiled-view-build.md)
- [Compiled View Runtime](compiled-view-runtime.md)
- [Compiled View HTTP](compiled-view-http.md)
- [Structured CLI](structured-cli.md)
- [Minimal Application Workflow](minimal-application-workflow.md)
- [Phase 18 Roadmap](phase-18-roadmap.md)
