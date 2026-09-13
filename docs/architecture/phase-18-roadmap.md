# Phase 18 Roadmap: Server-Rendered Presentation and Compiled Views

## Status and entry state

Phase 17A-H are Accepted and Phase 17 is closed at checkpoint
`e746a368ee884fd7475e1d74893d06903f6311a2`. Phase 18A accepts
[ADR 0179](../adr/0179-server-rendered-presentation-and-compiled-view-boundary.md),
[ADR 0180](../adr/0180-context-aware-output-safety-and-presentation-lifecycle.md),
the [presentation guide](server-rendered-presentation.md), and the
[view-authoring guide](../engineering/view-authoring.md) as documentation
architecture only.

Phase 18B accepts [ADR 0181](../adr/0181-neutral-view-contract-and-closed-presentation-values.md)
and implements only the optional Experimental `kongono/view-contract` neutral
leaf. Phase 18C accepts [ADR 0182](../adr/0182-deterministic-structured-view-source-and-compiled-artifact.md)
and implements only the optional Experimental `kongono/view-build` leaf.
Phase 18D accepts [ADR 0183](../adr/0183-exact-current-compiled-view-runtime-and-bounded-rendering.md)
and implements only the optional Experimental `kongono/view-runtime` leaf
described by the [runtime guide](compiled-view-runtime.md). Phase 18E accepts
[ADR 0184](../adr/0184-explicit-completed-view-to-psr-7-response-adaptation.md)
and implements only the optional Experimental `kongono/view-http` leaf described
by the [HTTP adapter guide](compiled-view-http.md). Phase 18A-H are Accepted;
Phase 18F accepts [ADR 0185](../adr/0185-supported-compiled-view-application-workflow.md)
and the optional Experimental `kongono/view-application` workflow described by
the [application guide](compiled-view-application-workflow.md). Phase 18G accepts
[ADR 0186](../adr/0186-visibility-safe-compiled-view-inspection-and-developer-intelligence.md)
and the optional Experimental `kongono/view-inspection` leaf described by the
[inspection guide](compiled-view-inspection-and-intelligence.md). Phase 18H
accepts [ADR 0187](../adr/0187-phase-18-server-rendered-presentation-acceptance-and-checkpoint.md)
and the root-owned [local acceptance](phase-18-server-rendered-presentation-acceptance.md).
Phase 18A-H are Accepted and Phase 18 is closed. No
cache, watcher, daemon, installer, deployment manager, automatic migration, or
asset pipeline exists.

## Fixed Phase 18 roadmap

| Row | Required boundary | Explicit exclusions |
| --- | --- | --- |
| **18A - Architecture and fixed roadmap** | Accept ADRs 0179-0180, the presentation guide, authoring guide, security/lifecycle doctrine, package direction, and this fixed roadmap as documentation only. | No PHP, package, dependency, template grammar, compiler, renderer, command, runtime, response adapter, application workflow, skeleton, or checkpoint. |
| **18B - Neutral view contracts (Accepted)** | Add an optional Experimental `kongono/view-contract` leaf with logical identities, closed context/schema values, render request/result/failure contracts, bounded output metadata, and package diagnostics. | No source syntax, parser, compiler, generated PHP, loader, escaping implementation, HTTP dependency, filesystem access, command, or rendering. |
| **18C - Deterministic source and compiled artifact (Accepted)** | Add optional Experimental `kongono/view-build` with one explicit bounded source set, first closed grammar/context set, deterministic validation, canonical artifact and generated ordinary PHP, exact currentness, source mapping, and atomic content-addressed no-clobber publication. | No runtime execution, `eval`, scanning/discovery, arbitrary PHP expressions, remote templates, HTTP integration, watcher, asset pipeline, or hidden build. |
| **18D - Exact-current render runtime (Accepted)** | Add optional Experimental `kongono/view-runtime` that validates and loads one explicit contained exact-current compiled artifact, renders synchronously with contextual escaping and explicit bounds, and proves isolation and cleanup. | No runtime parsing/compilation, ambient data/services, dynamic include, HTTP response, output-buffer ownership, streaming, global cache, worker, or hot reload. |
| **18E - Explicit HTTP presentation adapter (Accepted)** | Add optional Experimental `kongono/view-http` mapping one completed neutral render result into an explicit PSR-7 response under application-owned status/header/security policy and existing trusted diagnostic rules. | No routing/controller discovery, middleware magic, session/CSRF authority, CSP fabrication, assets, redirects, negotiation framework, server/listener, or error swallowing. |
| **18F - Supported application workflow and commands (Accepted)** | Add optional Experimental `kongono/view-application`, deterministic exact-current build/check/run composition, copy-once minimal server-rendered application slice, and explicit `view:build`, `view:check`, and bounded inspection commands through the existing `kongo` edge. | No interactive shell, watcher, daemon, package installer, runtime recompilation, mutable pointer, deployment manager, frontend toolchain, or automatic migration. |
| **18G - Visibility-safe inspection and developer intelligence (Accepted)** | Add optional Experimental offline/read-only/stateless inspection of one supplied exact-current view artifact and bounded Safe/Protected evidence for Graph, Observability, Security Audit, Chie, Hantei, and Oshie purposes with independent verification. | No template/value/rendered-byte disclosure, source execution, generated-code execution, rendering, filesystem discovery, repair, apply, role execution, production observation, or certification. |
| **18H - External acceptance and checkpoint (Accepted)** | Add root-owned deterministic external acceptance proving build/currentness, escaping, bounds, isolation, HTTP adaptation, CLI parity, diagnostics, nondisclosure, cleanup, regressions, and one exact Phase 18 local checkpoint. | No public deployment, browser certification, live server requirement, performance certification, push, tag, release, Stable/1.0, frontend/mobile integration, or Phase 19 behavior. |

Completion of a row never authorizes the next row. Only a separately
authenticated directive and accepted completion evidence may change a Planned
row to Accepted. Phase 18H alone may authorize a Phase 18 checkpoint.

## Dependency direction

```text
application-selected handler and presentation data
  -> optional neutral view contracts
  -> deterministic compiled source evidence
  -> exact-current synchronous render runtime
  -> completed neutral result
  -> optional explicit HTTP response adapter
```

Presentation packages point inward only to the minimum accepted neutral
foundations required by their row. Routing, HTTP, Kernel, Composition,
security, Graph, extensions, and other lower packages do not point outward.
Dependency direction expresses composition, not authority.

## Exit conditions

- Phase 18A exits with the two ADRs, two guides, fixed roadmap, and thirteen
  current-document reconciliations, with no implementation change.
- Phase 18B proves inert closed contract values and diagnostics without
  source syntax, filesystem behavior, escaping, or rendering.
- Phase 18C proves one deterministic bounded grammar/context set,
  canonical artifact and PHP bytes, source maps, exact currentness, and
  explicit no-clobber publication without executing generated output.
- Phase 18D proves explicit contained loading, contextual escaping, bounds,
  operation isolation, partial-output rejection, and cleanup without runtime
  compilation or ambient state.
- Phase 18E proves explicit neutral-result-to-PSR-7 adaptation while leaving
  application and browser-security policy caller-owned.
- Phase 18F must prove one supported exact-current build/check/run path,
  copy-once slice, and human/machine command parity without a watcher, daemon,
  hidden build, or mutable pointer.
- Phase 18G proves offline visibility-safe inspection and independent
  verification without source/value disclosure, execution, rendering, repair,
  recording, or certification.
- Phase 18H proves the accepted stack and regressions through root-owned
  deterministic external evidence and creates the checkpoint.

## Required invariants

- Presentation remains optional; API-only, CLI, worker, agent, and headless
  applications remain first-class.
- Application code selects views and data. Routing selects handlers only.
- Neutral view contracts remain independent of PSR-7.
- Source sets, schemas, contexts, identities, artifacts, bounds, and lifecycle
  are explicit, typed, deterministic, and fail closed.
- Runtime discovery, scanning, parsing, compilation, Reflection discovery,
  `eval`, service location, globals, and ambient request state remain absent.
- Plain strings remain data and contextual escaping is safe by default.
- Unsupported browser contexts are denied until separately specified.
- Generated PHP is exact-current trusted in-process code, not a sandbox.
- Partial output is never success; cleanup preserves primary and cleanup truth.
- Diagnostics remain bounded and non-disclosing.

## Package and API direction

Application-selected `view-contract` surfaces are Experimental. Planned
surfaces in `view-runtime` and the explicit HTTP/application boundaries begin
Experimental. Parser, compiler,
generated-code helpers, loaders, contextual escapers, source maps, and
lifecycle helpers remain Internal by default. No Phase 18 surface is Stable,
1.0, production-certified, browser-certified, or a compatibility promise.

## Validation direction

Later rows require deterministic unit and integration evidence for malformed
and duplicate input, exact currentness, output contexts, boundary exhaustion,
cycle/depth/iteration limits, sequential/overlapping isolation, cleanup,
nondisclosure, HTTP separation, and command parity. Phase 18H adds only the
root-owned external proof and regressions. Performance expectations require a
separate measured decision and are not an exit condition of Phase 18A.

## Deferred scope

Third-party template engines, frontend frameworks, hydration, assets,
bundling, CSS and image pipelines, Node/npm, mobile/desktop UI, forms,
localization, cache, deployment, live reload, watcher, daemon, installer,
marketplace, remote templates, compatibility modes, automatic migration,
Stable/1.0, accessibility certification, browser certification, security
certification, and production readiness remain outside the milestone unless a
later authenticated directive explicitly changes the boundary.

## Related material

- [ADR 0179](../adr/0179-server-rendered-presentation-and-compiled-view-boundary.md)
- [ADR 0180](../adr/0180-context-aware-output-safety-and-presentation-lifecycle.md)
- [Server-rendered presentation](server-rendered-presentation.md)
- [Compiled View HTTP](compiled-view-http.md)
- [Compiled View Application Workflow](compiled-view-application-workflow.md)
- [Compiled-View Inspection and Intelligence](compiled-view-inspection-and-intelligence.md)
- [View authoring](../engineering/view-authoring.md)
- [Progressive architecture](progressive-architecture.md)
- [Routing model](routing-model.md)
- [Runtime model](runtime-model.md)
- [Phase 17 roadmap](phase-17-roadmap.md)
