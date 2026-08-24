# Kongono Documentation Standard

## Purpose

Kongono documentation must let a developer understand a component without reverse-engineering it. Brevity does not justify vagueness. Documentation should state present behavior precisely and label planned behavior, experimental mechanisms, and open decisions honestly.

## Context model

Use the relevant subset of these concerns for a concept, component, or public symbol:

| Concern | Question to answer |
| --- | --- |
| Purpose | Why does this exist? |
| Context | Where does it fit in Kongono? |
| Use when | What scenarios justify it? |
| Do not use when | What scenarios need something else? |
| Input | What is semantically expected? |
| Output | What is guaranteed? |
| Side effects | What database, filesystem, network, event, or state changes occur? |
| Security | What permissions, trust boundaries, sensitive information, or attack implications exist? |
| Lifecycle | What scope and persistent-runtime rules apply? |
| Concurrency | What happens during concurrent execution? |
| Failure | What failures are possible, who owns their boundary, and what can a developer do next? |
| Performance | What costs or constraints matter? |
| Calling contract | What must be true before use? |
| Extension contract | May extensions replace, use, or implement it? |
| Agent contract | May agents interact with it, and under what authority? |
| Stability | Is it Internal, Experimental, Public, Stable, or Deprecated? |
| Related concepts | What other framework concepts or documents provide context? |

Not every symbol needs every concern. Coverage should reflect complexity, exposure, security sensitivity, lifecycle risk, and non-obvious behavior. Public and security-sensitive contracts need more context than self-explanatory private details.

## Comments and names

Prefer, in order:

1. A good type.
2. A good name.
3. A good structure.
4. A contextual comment where necessary.

Do not use comments such as "Increment count," "Handle the request," "Get the value," or "Process the data." They repeat code without conveying knowledge. Useful comments explain why a choice exists, architectural context, invariants, security requirements, non-obvious side effects, lifecycle constraints, concurrency constraints, deliberate tradeoffs, or compatibility history.

## Relationships

Human-authored documentation should explain conceptual relationships, not attempt to maintain exhaustive lists of callers, callees, dependencies, emitted events, or consumers. Such lists become stale. The planned Application Graph is intended to supply authoritative machine-derived relationships.

## Documentation identity and links

Kongono intends to support stable documentation identifiers connecting a source symbol, Application Graph node, documentation concept, IDE, CLI, and AI tooling. A conceptual example is `principals.resolution`, but no syntax or mechanism is selected. Documentation IDs are experimental. Do not add custom PHP attributes, annotations, or stable identifiers until authorized.

Future navigation may connect a symbol or comment to version-correct documentation explaining purpose, usage, examples, security, warnings, non-use cases, and related concepts. The linking mechanism remains open.

## Testability

Documentation should be written so future tooling can validate broken references, invalid examples, missing public API documentation, missing security coverage, deprecated API references, and invalid symbol links. Phase 0A does not implement those checks.

## Stability language

- **Internal:** not a supported consumer contract.
- **Experimental:** mechanism or API may change and must not be treated as stable.
- **Public:** intended for consumers but not necessarily stable unless explicitly stated.
- **Stable:** governed by an approved compatibility policy; no such framework API exists in Phase 0A.
- **Deprecated:** still present temporarily but scheduled for replacement or removal under an approved policy.

Always distinguish repository reality from planned architecture. Avoid present-tense claims that imply unimplemented framework functionality exists.

## Failure documentation

When a deliberate diagnostic is implemented, its documentation must identify
the stable diagnostic identifier, exact semantic cause, safe relevant context,
actionable remediation, owning package or capability, catch or translation
boundary, chain-preservation behavior, and material retry or cleanup policy.
Document expected application outcomes separately from exceptional framework,
infrastructure, engine, or programmer failures.

Do not document a deliberate failure only as `Invalid value`, `Invalid
configuration`, `Operation failed`, `Unexpected error`, `Something went wrong`,
or `Unable to continue`. State the exact cause and what the developer can change.
Do not present sensitive or unbounded context as diagnostic precision.

Current deliberate Kernel, Graph, and DI failures expose package-owned ID,
title, remediation, and exact bounded context through their package exception
markers. The 42 exact mappings live in three Internal catalogs; messages remain
unchanged protected detail. Phase 0Q integrates all 11 Graph failures, Phase 0T
integrates all six Kernel lifecycle failures, and Phase 0U integrates all 25 DI
diagnostics across 26 throw sites. Documentation may show
`diagnosticContext()` for those three packages but must not imply universal
access. Diagnostics construction failures remain context-free. The pure
Experimental trusted-developer HTML renderer is implemented with exact escaping
and golden fixtures. Redaction, served error pages, trusted activation,
production output, and transport representations remain planned and must be
labeled as such.
Documentation ID remains a separate concept from a diagnostic identifier.

For a deliberate diagnostic that uses structured context, documentation must
state its fixed operation and
violated invariant, exact field keys and labels, `text`/`integer`/`boolean`
value kind, Safe or Protected disclosure, canonical order, required or optional
status, applicable bounds, and forbidden-capture constraints. It must identify
the owning package and keep its catch boundary distinct. Structurally valid
`DiagnosticContextSchema` and `DiagnosticFieldSchema` instances do not establish
that semantic ownership. Examples may show direct structural construction,
current Graph, Kernel, or DI context access, and direct use of the implemented
pure trusted renderer. Sanitization, activation, transport, and production-
presentation examples must remain labeled future pseudocode.

## Versioned and generated developer-intelligence material

Documentation used by future Kongono Chie, Kongono Hantei, or Kongono Oshie
must cite the exact Kongono version or source state and the applicable source,
ADR, diagnostic, symbol, test, or command evidence. Do not combine stale or
mixed-version facts silently. Missing, unverifiable, unsupported, or ambiguous
knowledge must be labeled rather than completed by confident prose.

Generated teaching, explanations, recommendations, and candidate changes are
derived material, never accepted repository truth. Label accepted framework
fact, deterministic current-workspace observation, inference, recommendation,
generated candidate content, and unresolved uncertainty distinctly. A role's
output, confidence, or repetition cannot replace deterministic evidence or
authorize itself.

Phase 4A accepts this documentation rule only. The developer-intelligence
roles, knowledge artifacts, and patch proposals are not implemented, and
ordinary accepted documentation remains usable offline and without AI. See
[ADR 0060](adr/0060-human-centered-developer-intelligence-family.md) and the
[developer-intelligence architecture](architecture/developer-intelligence.md).

## Source-state, authority, and provenance labels

Where version, implementation state, or evidence affects meaning,
documentation must identify the exact source state and distinguish governing
authority, implementation evidence, current explanation, historical evidence,
workspace observation, and derived or advisory material. Claims must also
distinguish accepted fact, deterministic observation, inference,
recommendation, generated candidate, and unresolved uncertainty or conflict.

Derived material traces to exact originating sources and cannot cite other
generated text as the sole authority for a framework fact. Historical evidence
retains its historical state label. Multiple versions remain separated, and
conflicts are disclosed rather than resolved by recency, confidence, or
convenience. Citations must not reveal excluded paths, secrets, or Protected
values. See [ADR 0061](adr/0061-versioned-knowledge-source-and-provenance-contract.md)
and the [knowledge foundation](architecture/knowledge-foundation.md).

Phase 4C's generated framework artifact follows these rules mechanically for
one closed 31-source set. It embeds exact current source bytes and their
authority labels, binds whole-source citations to one exact source-set digest,
and fails current validation when any included state changes. The artifact is
derived evidence rather than documentation authority and is not checked in.
Line, symbol, inferred, cross-version, workspace, retrieval, and generated-
teaching references remain outside the implemented contract.

Phase 4D adds bounded line citations without changing documentation authority.
Each Chie citation binds one exact excerpt or diagnostic line to its artifact
source reference, relative path, authority class, source SHA-256, and inclusive
line range. The cited bytes are returned unchanged; Chie does not summarize or
complete them. A response remains derived material and cannot replace its
source, hide stale state, turn a workspace observation into framework truth, or
certify correctness. See [read-only Chie](architecture/read-only-chie.md).

## Composition-build and generated-wiring documentation

Phase 5A documentation must label three states separately:

- current caller-owned application construction and the current Experimental
  runtime resolver;
- Accepted composition-root, build-before-runtime, generated-wiring, security,
  lifecycle, and Phase 5 roadmap architecture; and
- planned implementation whose syntax, schema, package, API, command, artifact,
  and runtime integration are not yet selected.

Do not use present tense for a composition build, generated wiring, application
skeleton, lifetime API, or build/check/run workflow until it is implemented.
Do not describe generated output as authority, promise zero overhead, or claim
an Application Graph integration that has not been separately Accepted.

Future generated artifacts and their documentation must identify exact input
and output state, invalidation behavior, publication boundary, lifecycle and
cleanup ownership, security/disclosure limits, deterministic failure, and
whether examples are conceptual or executable. Property hooks must never be
documented as Kongono dependency resolution.

Phase 5B documentation states the current Composer fact exactly: the root and
all eighteen packages require `>=8.5.0 <8.6.0`; PHP 8.5 is the sole active
compatibility-certified minor; and PHP 8.5.9 on Windows is the environment
proved by this execution. Stable eligible 8.5 patches remain in range, while
PHP 8.6, PHP 9, prerelease, nightly, and automatic future-minor support remain
unclaimed. This is installability metadata only, not runtime semantics, a
package, dependency, public API, polyfill, shim, release/LTS promise, or
composition/build capability. Phase 5B is accepted and the bounded PHP
constraint is current.

Phase 5D documentation must now distinguish:

- **Current:** caller-owned runtime composition plus the Experimental
  `kongono/composition` strict build/artifact package; the resolver, structural
  Graph, lifecycle-neutral Kernel, routes, and runtime remain unchanged;
- **Accepted:** the exact inert version-1
  `<application-root>/kongono.composition.json` grammar, canonical declarations,
  roots, lifetimes, cleanup, bounds, validation, safety, disclosure, and
  provenance contracts; and
- **Implemented:** Phase 5D canonical model, raw provenance artifact,
  structural/current validation, and content-addressed publication; Phase 5E
  corrected typed wiring and isolated lifecycle support; and
- **Planned:** Phase 5F runtime integration, Phase 5G workflow, and Phase 5H
  acceptance.

Label source JSON examples inert and non-executable. State accurately that the
Phase 5D build package parses and validates them before runtime, and Phase 5E
derives canonical typed PHP. No CLI, schema file, production runtime loader, or
cache convention exists. Phase 5F has not begun.

See [ADR 0068](adr/0068-supported-application-composition-and-build-boundary.md),
[ADR 0069](adr/0069-generated-typed-wiring-and-no-runtime-service-location.md),
[ADR 0070](adr/0070-versioned-composition-source-and-provenance.md),
[ADR 0071](adr/0071-roots-bindings-lifetimes-and-cleanup.md),
[ADR 0072](adr/0072-deterministic-composition-artifact-and-publication.md),
the [composition guide](architecture/application-composition-and-build.md),
the [composition source guide](architecture/composition-source.md),
[declaration guide](engineering/composition-declarations.md), and
[generated wiring guide](engineering/generated-wiring.md).
