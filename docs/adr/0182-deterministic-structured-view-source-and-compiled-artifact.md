# ADR 0182: Deterministic Structured View Source and Compiled Artifact

- Status: Accepted
- Date: 2026-09-12
- Phase: 18C

## Context

Phase 18B introduced only neutral logical-view identities, closed presentation
values and schemas, output-context vocabulary, and inert render evidence. It
deliberately supplied no source language, compiler, generated PHP, artifact,
publisher, loader, renderer, or HTTP behavior.

Server-rendered presentation needs a build boundary that is deterministic and explicit
without turning templates into ambient application code. The build must know
the output context of every dynamic value, reject unsupported constructs before
runtime, preserve source-to-operation evidence without retaining source bodies,
and create immutable output that a later separately authorized runtime can
verify. A digest of source alone is insufficient: source identity, semantic
model identity, generated-code identity, and artifact identity describe
different facts.

## Decision

Accept the optional Experimental `kongono/view-build` package. It depends only
on PHP, API Metadata, Diagnostics, and View Contract. No existing production
package depends on it.

The sole source entry point accepts one explicit UTF-8 JSON byte sequence with
exact framing, duplicate-aware decoding, finite nesting, and a closed version-1
grammar. A source set contains 1 through 128 logical views. Each view declares a
closed Phase 18B-compatible record schema and a body composed only of text,
string-value, fixed structural element, and bounded `each` nodes. Element and
attribute names come from fixed allowlists. References are resolved completely
against required schema fields during the build. URL, JavaScript, CSS, raw
markup, event handlers, expressions, arbitrary PHP, calls, includes,
inheritance, and dynamic names are denied.

Compilation ordinal-sorts views, record fields, and attributes. Static text is
encoded bytewise for HTML text or quoted HTML attributes. The model owns a
complete contiguous per-view source map whose entries contain only view
identity, operation index, JSON pointer, operation kind, and applicable output
context.

The builder computes four distinct lowercase SHA-256 identities over exact
bytes: source, canonical semantic model JSON, generated PHP, and artifact
metadata. Metadata embeds the complete canonical model. Generated PHP declares
one deterministic final class with source/model identity accessors and a closed
logical-view `match`. It emits only through the Internal `RenderSink` protocol,
uses compiler-selected numeric loop variables, and preserves dynamic values for
the future contextual sink. It is trusted in-process code evidence, not a
sandbox, and Phase 18C validates its syntax and exact regeneration without
including, requiring, autoloading, reflecting, instantiating, evaluating, or
executing it.

Structure validation is duplicate-aware, canonical, bounded, closed, and checks
all identity relations, syntax, forbidden surfaces, and byte-identical
regeneration. Currentness additionally rebuilds from one explicit supplied
source set and requires every byte sequence and identity to agree.

Publication requires one explicit existing absolute canonical writable
ordinary directory. It rejects traversal and linked or reparse evidence,
creates one exclusive same-parent temporary directory, writes and syncs exact
`artifact.json` and `compiled.php` files, and performs one final directory
rename. An existing content-addressed target is success only when it is an
ordinary exact two-file byte-identical artifact. No overwrite, merge, repair,
discovery, mutable pointer, or recursive cleanup is provided.

## Consequences

- API-only, CLI, worker, agent, and headless applications remain first-class
  and need not install either view package.
- Routing continues to select handlers, never templates.
- Build evidence grants no identity, authority, credential, filesystem,
  network, rendering, or application-effect permission.
- Dynamic escaping, output accumulation, runtime bounds and cleanup, generated
  code loading, HTTP adaptation, application workflow, commands, inspection,
  and external acceptance remain separately planned Phase 18D-H work.
- No Stable, 1.0, provider/browser compatibility, performance, security
  certification, or production-readiness claim follows from this decision.

## Rejected alternatives

- Runtime parsing or compilation, because it creates a second mutable and less
  inspectable execution path.
- PHP templates, `eval`, generic expressions, and third-party template engines,
  because they exceed the closed version-1 language and authority boundary.
- Directory discovery, newest-file selection, mutable current pointers, and
  repair-in-place publication, because they make selection and currentness
  ambient or ambiguous.
- A generic `escape()` promise, because safety depends on the compiler-known
  output context.
- One digest for every layer, because equal source, model, PHP, and metadata are
  distinct evidence relations.

## References

- [ADR 0179](0179-server-rendered-presentation-and-compiled-view-boundary.md)
- [ADR 0180](0180-context-aware-output-safety-and-presentation-lifecycle.md)
- [ADR 0181](0181-neutral-view-contract-and-closed-presentation-values.md)
- [Compiled View Build](../architecture/compiled-view-build.md)
- [Server-Rendered Presentation](../architecture/server-rendered-presentation.md)
- [Phase 18 Roadmap](../architecture/phase-18-roadmap.md)
- [View Authoring](../engineering/view-authoring.md)
