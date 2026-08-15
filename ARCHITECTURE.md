# Kongono Architecture

## Status and scope

This document is an index of Kongono's authorized architectural direction.
Kongono is pre-alpha. Phase 0X adds the exact Diagnostic Presentation leaf and
pure trusted-developer projection above Phase 0U, which adds the exact acyclic DI-to-Diagnostics
edge and integrates all 25 Dependency Injection diagnostics across 26 throw
sites. Phase 0T integrates all six Kernel lifecycle failures, and Phase 0Q
integrates all 11 Application Graph failures, with exact package-owned bounded
diagnostic context on the presentation-neutral
`kongono/diagnostics` value foundation: bounded immutable schemas and values,
deterministic byte-safe text representation, structural closed-schema capture,
and package-local construction failures. All 42 original failures now own exact
bounded context as 6 Kernel, 11 Graph, and 25 DI schemas. Diagnostics
construction failures remain context-free. Current production behavior otherwise remains the
Phase 0J implementation: the PHP quality toolchain, the narrow
`kongono/kernel` lifecycle package, the immutable structural core with exact
direct-relation and whole-snapshot kind selectors in
`kongono/application-graph`, and explicit typed constructor resolution in
`kongono/dependency-injection`. Other statements about framework behavior
describe planned architecture unless explicitly identified as current
repository behavior.

## Package dependency topology

The current runtime has six packages. `kongono/api-metadata` requires
PHP only and owns `Kongono\ApiMetadata\Api` and `ApiStatus`. Kernel,
Diagnostics, and Dependency Injection each require API Metadata; Kernel,
Application Graph, and Dependency Injection also require Diagnostics. The root consumes all five. This
graph is acyclic, and no package depends on Kernel merely for compatibility
metadata.

Phase 0X implements `kongono/diagnostic-presentation` as a leaf above this
graph. It depends directly on API Metadata, Diagnostics, Kernel, Application
Graph, and Dependency Injection. No existing package depends on it, and it
creates no binding, service registration, Graph node, ambient discovery, or
reverse edge. The framework root consumes all six packages.

The former `Kongono\Kernel\Api\*` identities are unavailable and no alias or
forwarder exists. Graph and Kernel retain their diagnostic integrations exactly.
DI now independently requires Diagnostics for its 25 schemas. Phase 0U adds no
renderer, error page, transport, or other runtime capability.

Kongono Framework targets PHP 8.5+ and is designed for three first-class consumption surfaces: Human Experience, Software/API Experience, and Agent Experience. The framework is not a Laravel clone. It aims to preserve strong developer productivity while favoring explicit, typed, deterministic, observable, modular, persistent-runtime-safe, and AI-native architecture.

## Foundational model

The planned Application Graph is the authoritative, machine-readable representation of an application. It will derive from a language-neutral Intermediate Representation so that PHP, the first source-language frontend, does not permanently define the conceptual model. Phase 0J retains the source-language-neutral in-memory structural snapshot introduced in Phase 0D, the exact outgoing and incoming direct-relation selectors from Phase 0H, and adds only exact whole-snapshot node-kind and relation-kind selection. `nodesOfKind(NodeKind): array` and `relationsOfKind(RelationKind): array` compare kind values exactly within separate domains, return `[]` for absent kinds, and preserve canonical order and stored-object identity in fresh lists. Node selection scans all nodes in `O(N)` time, relation selection scans all relations in `O(R)` time, result allocation is `O(k)`, and no kind index or query cache is retained. Open kind labels define no built-in vocabulary or schema. This is not combined endpoint-and-kind filtering, multiple-kind selection, predicates, traversal, reachability, neighbor selection, or a generic query system. Stable identifiers, domain schemas, metadata, provenance, visibility, serialization, discovery, compilation mechanics, persistence, mutation, framework integration, optimization promises, and the complete authoritative representation remain unresolved.

Kongono uses `packages/kernel` as its first physical monorepo package. Its Experimental state machine boots once, owns overlapping opaque operation scopes by object identity, rejects shutdown while work is active, and shuts down terminally. It is transport-, runtime-, and container-neutral. Independently installable capabilities and application architecture beyond this boundary remain planned.

`packages/dependency-injection` is a separate Experimental component. It maps canonical existing interfaces to explicit concrete implementations and ordered constructor dependencies, validates complete definition sets, and constructs transient instances on every dependency edge. It does not make the Kernel a container, provide PSR-11 or autowiring, select shared/scoped lifetimes, or integrate with the Application Graph.

The lifecycle is persistent-runtime-first: framework design may not assume all application state disappears after each request or operation. Runtime-specific integrations must remain adapters rather than kernel dependencies.

Phase 0M realizes the 42 package-owned diagnostic assignments through three
Internal backed-enum catalogs. The three separate Public package exception
markers expose `diagnosticId()`, `diagnosticTitle()`, and
`diagnosticRemediation()` as strings; no universal catch interface exists.
Existing messages remain unchanged, protected detail, and native causes retain
their identity. No structured context, renderer, development error page,
production error surface, HTTP or CLI mapping, logging, telemetry, retry,
cleanup orchestration, incident system, or localization exists. See the [error-handling engineering
guide](docs/engineering/error-handling.md) and the [ADR
index](docs/adr/README.md).

The presentation-neutral `kongono/diagnostics` package now owns Public
`DiagnosticContext`, `DiagnosticField`, their closed schema types, and the exact
Safe/Protected and text/integer/boolean vocabularies. It validates structural
schemas and creates eager, ordered, detached snapshots with at most 16 fields.
Text is represented deterministically as bounded ASCII tokens with an exact
512-byte ceiling and visible `...[truncated]` marker. Construction uses no ambient
state or I/O and retains no global per-failure values. Diagnostics has its own
fourth package-local marker and 11 construction diagnostic IDs.

Originating packages retain identifier meaning, exact catalog schemas,
throw-site values, and separate Public markers; structural schema validity does
not confer semantic ownership. Phase 0Q makes Graph the first semantic consumer:
its Internal catalog owns all 11 schemas, its throw sites supply only approved
values, and its Public marker adds `diagnosticContext()`. Graph messages and
catch ownership remain local. Phase 0T makes Kernel the second semantic consumer
for its six lifecycle failures. Phase 0U makes DI the third semantic consumer
for 25 schemas across 26 throw sites. Kernel, Graph, and DI markers independently
expose context; Diagnostics construction failures expose no recursive context.
Forbidden-material detection, redaction, activation enforcement, production
allowlists, transport, serialization, source and documentation lookup, logging,
telemetry, and incidents remain future decisions.

The implemented Presentation boundary provides a pure Experimental trusted-
developer projection over exactly the Kernel, Graph, or DI contextual marker.
It derives the exact owner `kernel`, `application_graph`, or
`dependency_injection` and copies only identifier, title, operation, invariant,
remediation, and canonically ordered bounded fields with their unchanged kind,
Safe/Protected disclosure, and truncation state. Multi-marker sources are
rejected before accessor use; arbitrary `Throwable`, the Diagnostics marker,
messages, causes, traces, source, requests, environment, transports, and
metadata bags are excluded. The projection retains no source object, cache, or
cross-operation state and performs no I/O.

Phase 1A accepts the fail-closed activation policy outside Diagnostic
Presentation. A future outer composition boundary must hold an explicit
immutable trusted bootstrap permission for its adapter instance and the adapter
must independently authorize the current delivery. Both must be affirmative
before presentation access, projection, rendering, or delivery; absence,
invalidity, ambiguity, evaluation failure, denial, and uncertainty prohibit the
trusted path. Client input, raw environment labels, heuristics, AI output, and
ambient or previous-operation state cannot enable either permission. The
trusted developer model cannot be reused and redacted for production;
production requires a separate deny-by-default model. Projection and rendering
remain separate. Phase 0Z implements the Experimental
`Kongono\DiagnosticPresentation\Developer\Html\TrustedDeveloperHtmlRenderer`
in the existing Diagnostic Presentation leaf. It consumes only the trusted
projection and returns one complete escaped HTML5 document without adding a
package edge, runtime binding, Graph participation, or reverse dependency. It
is stateless and fixed by two golden fixtures.

The accepted conceptual composition is:

```text
trusted bootstrap permission [Accepted policy; enforcement unimplemented]
    AND adapter-controlled delivery permission [Accepted policy; enforcement unimplemented]
        -> presentation access -> projection -> rendering -> future adapter delivery
```

Both permissions remain outside Diagnostic Presentation. Phase 1A adds no
package edge, service, runtime integration, configuration mechanism, activation
type, adapter, served page, or production surface; the current six-package
graph is unchanged.

## Boundaries

- PHP 8.5+ is the primary application language.
- TypeScript and JavaScript belong to browser/frontend integration.
- Rust may later support high-performance framework tooling.
- Go may be selected later for specific infrastructure or runtime integrations.
- Vite is the intended default bundler integration; Kongono will own an Asset Platform abstraction, not a bundler.
- OpenTelemetry is the intended instrumentation model; exporters will remain replaceable.
- OpenAPI is intended to be generated from authoritative application metadata.
- MCP and A2A are adapters around Kongono concepts and do not define its domain model.

No runtime adapter, protocol adapter, domain Graph schema, complete Graph compiler, capability package, or framework API for those planned boundaries exists yet. The implemented structural snapshot and Kernel lifecycle must not be read as selecting any of them.

## Detailed documentation

- [Architecture overview](docs/architecture/overview.md)
- [Application Graph](docs/architecture/application-graph.md)
- [Microkernel and capabilities](docs/architecture/microkernel.md)
- [Explicit typed dependency resolution](docs/engineering/dependency-injection.md)
- [Error handling and actionable diagnostics](docs/engineering/error-handling.md)
- [Progressive architecture](docs/architecture/progressive-architecture.md)
- [Runtime model](docs/architecture/runtime-model.md)
- [Agent model](docs/architecture/agent-model.md)
- [Extension platform](docs/architecture/extension-platform.md)
- [Architectural doctrine](docs/doctrine.md)
- [Architecture Decision Records](docs/adr/README.md)
