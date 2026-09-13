# ADR 0185: Supported Compiled-View Application Workflow

- Status: Accepted
- Date: 2026-09-13
- Phase: 18F

## Context

Phases 18B-E provide neutral view contracts, deterministic compiled artifacts,
an exact-current bounded renderer, and explicit completed-result-to-PSR-7
adaptation. Applications still need one supported composition boundary that
reads one explicit source, publishes only by explicit build authority, proves
currentness before load, and leaves routing and response policy application-
owned.

## Decision

Accept the optional Experimental `kongono/view-application` package. One
`ViewApplicationDefinition` names an explicit canonical application root, one
contained ordinary source file, and one contained existing artifact output
root. `ViewApplicationBuildWorkflow::plan()` is write-free, `build()` delegates
publication to the accepted Phase 18C publisher and then checks exact
currentness, and `check()` reads exactly the two content-addressed artifact
files without loading or executing generated PHP.

Source handling is single-file and bounded. Relative, noncanonical, outside-
root, linked, reparse, nonordinary, missing, changed, oversized, invalid UTF-8,
BOM, CR, NUL, and incorrectly terminated input fails closed. There is no
directory discovery, glob, newest selection, fallback, mutable current pointer,
cleanup, repair, migration, or implicit compilation.

`ViewApplicationFactory` accepts only exact-current workflow evidence and
explicit PSR-17 response and stream factories. It authenticates and loads the
one selected artifact through Phase 18D and verifies every identity before
returning a reusable operation-stateless application. Each run verifies the
request's compiled identity, renders once, returns the exact `RenderFailure`
unchanged, or adapts one successful `RenderResult` once under the explicit
application-owned response policy.

The official `kongo` family gains exactly `view:build`, `view:check`, and
`view:inspect`. All accept the exact ordinary application-root, output-
directory, and source-file inputs. Build retains write-free `--plan` and
explicit `--apply`; check and inspect are read-only and observe-only. Human and
machine forms derive from one bounded Safe result and never expose supplied
paths or private bytes.

Accept the copy-once `skeletons/view-minimal` example. It owns one structured
HTML view with one required bounded string, composes the real routing,
middleware/web-runtime, PHP-CGI, HTTP-message, view-application, and response
emission paths, and returns `Hello from Kongono.` with one terminal LF. Its
scripts are exactly `view:build`, `view:check`, and `view:run`. Run checks
currentness before loading and never compiles.

## Consequences

- Phase 18A-F are Accepted; Phase 18G-H remain Planned.
- The graph contains 94 acyclic production packages and 394 local production
  dependency edges.
- Application code still owns logical-view choice, bounded presentation data,
  routing, matched and unmatched response policy, browser security, delivery,
  and failure presentation.
- API-only, CLI, worker, agent, headless, and client-side applications remain
  first-class and need not install any view package.
- No listener, watcher, daemon, live reload, cache, asset pipeline, installer,
  deployment manager, automatic migration, Stable/1.0, certification, or
  production-readiness claim is added.

## Alternatives considered

1. Runtime compilation or compile-on-miss - rejected because run must consume
   only explicit exact-current evidence.
2. Source-directory discovery or a mutable latest pointer - rejected because
   caller selection and content identity must remain explicit.
3. Routing or error-page behavior inside the package - rejected because those
   policies remain application-owned.
4. A server or watcher in the skeleton - rejected because the example is one
   bounded copy-once development proof.

## References

- [ADR 0182](0182-deterministic-structured-view-source-and-compiled-artifact.md)
- [ADR 0183](0183-exact-current-compiled-view-runtime-and-bounded-rendering.md)
- [ADR 0184](0184-explicit-completed-view-to-psr-7-response-adaptation.md)
- [Compiled View Application Workflow](../architecture/compiled-view-application-workflow.md)
- [Phase 18 Roadmap](../architecture/phase-18-roadmap.md)
