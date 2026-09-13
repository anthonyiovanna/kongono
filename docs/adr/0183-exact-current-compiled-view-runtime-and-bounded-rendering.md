# ADR 0183: Exact-Current Compiled View Runtime and Bounded Rendering

- Status: Accepted
- Date: 2026-09-12
- Phase: 18D

## Context

Phase 18C produces deterministic, content-addressed `artifact.json` and
`compiled.php` evidence, but deliberately neither loads nor executes generated
PHP. A render runtime must preserve that explicit build/runtime separation. It
must not rediscover source, select a newest artifact, rebuild currentness, or
turn neutral presentation values into ambient application access.

The deployment artifact directory and generated PHP are trusted inputs at this
boundary. Generated PHP executes as ordinary in-process PHP and is not a
sandbox. The runtime can validate exact bytes, containment, identity, and the
closed generated callable surface; it cannot defend against a hostile kernel or
administrator replacing code during execution.

## Decision

Accept the optional Experimental `kongono/view-runtime` package. The caller
selects an absolute canonical artifact root, its one direct content-addressed
child, and an expected `CompiledViewIdentity`. That identity is the currentness
authority. The runtime reads only the fixed children `artifact.json` and
`compiled.php`, with bounded handle reads, ordinary-file and link checks,
shared locking, containment checks, and before/after stability evidence. It
does not enumerate, discover, repair, or choose artifacts.

The loader validates canonical metadata, all source/model/generated/compiled
identity relations, the generated class grammar, and the generated PHP digest.
It then performs the one permitted fixed `require`. The returned class name and
its non-autoloaded callable surface must match the validated envelope. An exact
already-loaded immutable class may be reused only when its source and model
identities still match; a collision fails closed. Callers cannot forge a
`LoadedCompiledViewArtifact` or inject an arbitrary callable.

`CompiledViewRenderer` is stateless. Each synchronous call owns a fresh render
operation and private buffered sink, validates the request/artifact/schema
relationship, invokes the generated program once, and retires its mutable
state in `finally`. Runtime step executions and output bytes are checked before
increment or append. Repeated compile-time loop indexes remain valid. A failed
render returns no partial bytes, and retirement failure preserves the primary
throwable as its previous exception.

Dynamic strings are always data. HTML text escapes `&`, `<`, and `>`; quoted
HTML attributes additionally escape both quotes with an explicit UTF-8 HTML5
primitive and double encoding enabled. URL, JavaScript, CSS, raw markup, event
handler, unquoted-attribute, and unknown contexts remain denied.

Success returns the neutral Phase 18B `RenderResult` with exact output bytes,
digest, byte count, step count, escaped-value count, identities, and completed
lifecycle evidence. Expected defects return a neutral `RenderFailure` with the
narrowest closed failure kind. Eight runtime diagnostics expose only closed,
bounded Safe/Protected context and never paths, source, values, generated code,
partial output, private exception text, or traces.

## Consequences

- Phase 18A-D are Accepted; Phase 18E-H remain Planned.
- API-only, CLI, worker, agent, and headless applications remain first-class
  and need not install any presentation package.
- No source is parsed, validated, or compiled during loading or rendering.
- Routing continues to select handlers, not views. HTTP response status,
  headers, browser-security policy, sessions, and application workflow remain
  outside this package.
- Persistent processes may reuse only the exact authenticated generated class;
  renderer, request, value, output, failure, and counter state remains
  operation-local and is retired after each recoverable outcome.
- The package makes no Stable, 1.0, compatibility, performance, browser or
  security certification, or production-readiness claim.

## Rejected alternatives

- Runtime source parsing or compilation, because it creates a second mutable
  build path and makes exact-currentness ambiguous.
- Source or artifact discovery, newest-file selection, or mutable pointers,
  because selection belongs to the caller/deployment boundary.
- A mutable global artifact or renderer cache, because it retains request and
  deployment state across operations.
- Output-buffer capture, streaming, echo, or partial result objects, because a
  failed operation must expose no partial bytes.
- Arbitrary include paths or public callable injection, because only the
  authenticated fixed generated program is executable.
- Raw-markup bypasses or one generic escape promise, because output context is
  compiler-selected and the accepted contexts are closed.
- HTTP response coupling, because rendering remains neutral and application-
  owned response and browser-security policy is planned separately.

## References

- [ADR 0179](0179-server-rendered-presentation-and-compiled-view-boundary.md)
- [ADR 0180](0180-context-aware-output-safety-and-presentation-lifecycle.md)
- [ADR 0181](0181-neutral-view-contract-and-closed-presentation-values.md)
- [ADR 0182](0182-deterministic-structured-view-source-and-compiled-artifact.md)
- [Compiled View Runtime](../architecture/compiled-view-runtime.md)
- [Compiled View Build](../architecture/compiled-view-build.md)
- [Phase 18 Roadmap](../architecture/phase-18-roadmap.md)
