# ADR 0075: Minimal Application Skeleton and Workflow

- Status: Accepted
- Date: 2026-08-23
- Phase: 5G

## Context

Phases 5D through 5F provide deterministic Composition artifacts, generated typed PHP, fail-closed loading, and integration with the existing web lifecycle. Applications still need one supported copy-once starting point and an explicit way to build, check, and exercise those boundaries without introducing runtime discovery or deployment behavior.

## Decision

Kongono owns and tests one template at `skeletons/minimal/`. Creating an application is a copy-once action. After copying, all non-generated files are application-owned. `vendor/` and content-addressed files beneath `var/kongono/build/` are derived and replaceable.

`ApplicationBuildWorkflow` is the reusable Experimental framework boundary. `build()` constructs the exact current Composition artifact and generated PHP in memory, publishes both through the existing atomic no-clobber publishers, and rechecks the exact published files. `check()` derives both exact content-addressed paths without scanning and performs no filesystem mutation. Neither operation creates a mutable current/latest pointer, selects old output, or deletes output.

The skeleton owns three Composer scripts:

- `app:build` publishes exact current derived output;
- `app:check` proves that output without writing; and
- `app:run` checks before loading, performs one fixed local request through the installed PHP-CGI executable, and exits.

The application supplies a fresh `KernelInterface`, `GeneratedWebRuntimeBootstrap`, and `RequestHandlerInterface` in canonical native type order. Generated code constructs the existing `WebApplication` and `GeneratedWebRuntimeRoot`. Web Application retains one Kernel operation per request, Web Runtime retains delivery ownership, PHP-CGI retains front-controller ownership, and generated shutdown retains Web Application cleanup.

The run command uses the production-denied trusted-presentation profile. Its fixed CGI snapshot carries the inert query marker `kongono_proof=1` only to retain command-line hardening behavior on the one-shot Windows executable; the route path and application behavior remain independent of it. The command is a bounded development proof, not a listener, resident server, watcher, supervisor, worker manager, socket binder, or deployment process.

Kongono does not automatically upgrade, merge, overwrite, migrate, fetch, or delete an application skeleton. Adopting a later template is an explicit reviewed application diff. Rebuild never edits application-owned source and never automatically removes older derived files.

## Security and failure behavior

Application roots, output directories, and exact derived files must be ordinary, exact-case, contained, stable, and free of links, reparse aliases, or stream syntax. Diagnostics expose only fixed workflow-stage and violation tokens. Command failures use bounded output and never disclose paths, rejected bytes, native messages, environment data, credentials, or secrets.

Missing, partial, stale, tampered, renamed, linked, outside-root, or changed-during-read output fails closed. Runtime never repairs or rebuilds during a request.

## Consequences

The skeleton is intentionally small: one `GET /` route, one 404 handler, the existing middleware and routing path, and one CGI request. Content-addressed output can accumulate until an application owner explicitly reviews and removes it. Phase 5G adds no creation command or deployment automation.

Phase 5H retains the exhaustive external missing/stale/tampered/failure/isolation/security matrix and the Phase 5 checkpoint. Phase 5H is not implemented by this decision.

## Rejected alternatives

- A mutable latest/current file or symlink.
- Directory scanning or fallback selection.
- Build-on-request or request-time reflection.
- A container, resolver injection, service locator, or runtime autowiring.
- A framework installer, automatic skeleton upgrader, or remote template fetch.
- A listener, development server, watcher, supervisor, or deployment workflow.

## Related material

- [ADR 0072](0072-deterministic-composition-artifact-and-publication.md)
- [ADR 0073](0073-generated-typed-wiring-and-lifecycle-boundary.md)
- [ADR 0074](0074-generated-assembly-web-runtime-integration.md)
- [Minimal application workflow](../architecture/minimal-application-workflow.md)
- [Application workflow engineering guide](../engineering/application-workflow.md)
