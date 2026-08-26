# ADR 0096: Explicit Structured CLI and Automation Boundary

## Status

Accepted

## Date

2026-08-26

## Context

Phase 7 is accepted at checkpoint
`b09b0c654f8b67f7cac3807f839f665e8c5b5469`, tree
`c0b6fed92dbb43d0bb91891b4e4496e84c0c85e4`, with thirty acyclic production
packages. ADR 0012 selected a Structured CLI direction but deliberately left
the architecture, lifecycle, machine contract, and executable packaging open.
Phase 8 needs an explicit boundary before any command package or executable can
be implemented.

The CLI must serve humans, automation, software clients, and governed agents
without treating terminal prose, implicit process state, or framework magic as
authority. It must remain an optional first-party edge over existing explicit
capabilities rather than becoming the Kernel, an application architecture, a
container, a daemon, or a dependency imposed on lower packages.

## Decision

### Official identity and bounded purpose

The official future executable is exactly `kongo`. No `kgo`, `artisan`,
compatibility alias, shell function, or alternate binary is accepted. The CLI
is a first-party developer and operator edge over explicit Kongono
capabilities. Lower capabilities remain directly usable without it.

Phase 8A accepts architecture only. It implements no command system, package,
schema, parser, executable, installer, scaffolder, migration runner, process
supervisor, shell completion, interactive interface, telemetry, or deployment
surface.

### Command meaning, execution, and presentation

The architecture separates three responsibilities:

1. **Command meaning** is an immutable, versioned, bounded definition of a
   command and its inputs.
2. **Command execution** is one explicit invocation that produces one
   presentation-neutral result.
3. **Presentation and process adaptation** renders that result for a human or
   machine, selects streams according to an accepted contract, and translates
   completion to one process exit status.

Handlers do not print, style terminal text, read global input, or call `exit`.
Presenters do not execute command behavior or reinterpret success. Only the
outer executable captures process inputs and adapts one completed result to
host streams and process status.

### Explicit invocation and host authority

The neutral engine receives caller-supplied tokens and explicit ports or
detached values. It does not implicitly read `$argv`, standard streams,
environment variables, current working directory, terminal state, signals,
clocks, randomness, configuration files, credentials, or global registries.
A later outer adapter may capture approved host state once, bound and normalize
it, then pass detached values inward.

Secrets must not be placed in ordinary command arguments when process listings
or command histories could expose them. Secure input carriers require a later
authenticated contract; Phase 8A selects none.

### Deterministic definitions and discovery

Runtime filesystem scanning, namespace crawling, attribute- or Reflection-based
command discovery, mutable global registries, plugin probing, and network
discovery are rejected. Definitions and their provenance will be deterministic
build evidence derived from explicit application or framework authority.

Generated evidence is reviewable derived evidence, never source authority, and
validation may not execute candidate bytes. Production startup and execution
must fail closed when evidence is missing, stale, partial, mixed, ambiguous,
unsupported, or unverifiable. Exact source and artifact schemas, identifiers,
compiler mechanics, and package ownership remain later decisions.

### Human and machine contracts

Human presentation remains concise, readable, accessible, and helpful. Machine
mode is a versioned deterministic contract rather than human terminal output
with styling removed. Meaningful commands support a machine-readable mode such
as `--json` where applicable, consistent with ADR 0012.

One machine invocation produces one bounded canonical result document on its
designated data stream. It contains no banner, progress animation, ANSI
sequence, debug dump, or interleaved human prose. Human and machine
presentations derive from the same neutral result and agree on outcome and
diagnostic identity. JSON schema, media type, compatibility rules, stream
mapping, and the exit-code table remain later authenticated decisions.

### Interaction and mutation safety

Hidden interaction is prohibited. Commands that can prompt must declare that
capability. `--no-interaction` disables prompting and fails explicitly when
required input is absent; machine mode never silently prompts.

Commands capable of persistent mutation expose an honest `--dry-run` contract
where applicable. Dry-run planning does not perform the intended persistent
mutation, and uncertainty is not represented as a successful preview.
Destructive intent, confirmation, non-interactive authorization, and apply
authority remain explicit and separate. Interaction cannot repair a failed
precondition implicitly or broaden command authority.

### Invocation lifecycle and persistent runtimes

One invocation owns one bounded operation lifecycle. Invocation input, output
buffers, diagnostics, domain objects, service instances, database and
persistence state, and cancellation state are released before completion.
Cleanup is explicit, reverse-ordered where applicable, and preserves combined
failure according to existing Kernel and operation error doctrine.

Static mutable invocation state may not leak between sequential same-process
invocations. Process termination is not cleanup. Although the supported future
executable is one-shot, the neutral engine must be testable through sequential
same-process invocations. Signals and cancellation enter only through explicit
ports or evidence; exact semantics remain later work.

### Outcomes, diagnostics, and disclosure

Expected command outcomes are presentation-neutral values rather than
exceptions used for ordinary control flow. Programmer errors, invariant
violations, and infrastructure failures remain distinguishable. Existing
diagnostic identity, bounded context, disclosure, safe stringification, and
combined-failure rules apply unchanged.

Machine results expose stable bounded error identity and safe fields. They do
not expose raw exception messages, stack arguments, provider output, secrets,
paths, SQL, credentials, environment dumps, or arbitrary command input. Human
developer output may explain the safe bounded cause and next action when that
evidence exists. Trusted or debug presentation does not grant command authority
and cannot be enabled by a CLI flag alone.

### Dependency direction and composition

The future neutral CLI contract package remains independent of HTTP, Routing,
Web Runtime, PHP CGI, database providers, persistence providers, Chie, Hantei,
Oshie, telemetry exporters, and application packages. Capability adapters may
depend inward on neutral CLI contracts; neutral contracts never depend outward
on them. The outer executable and application integration form an upper leaf
and composition root.

Handlers receive explicit typed collaborators through Composition. They do not
receive a resolver or container, query a service locator, or use property-hook
dependency resolution. Application Graph and Composition may provide
deterministic evidence later, but Phase 8A changes neither schema nor runtime
behavior. The current graph remains thirty acyclic production packages.

### Versioning, performance, and developer experience

Public machine contracts create compatibility obligations and require explicit
versioning before Stable status. New Phase 8 implementation begins Experimental
unless a later directive proves and promotes a surface. Parser, presenter,
registry, artifact, and host-adapter internals remain Internal unless explicitly
selected otherwise. A mentioned command, option, output, exit code, or packaging
shape is not implicitly Stable.

The normal runtime path will consume prevalidated immutable evidence, explicit
typed values, and ordinary PHP. It will not perform runtime Reflection,
scanning, dynamic proxying, `eval`, or framework-substrate discovery. Parsing
and rendering are bounded and deterministically ordered. Streaming requires an
explicit result/output contract that preserves machine determinism, cleanup,
and failure truth; unbounded captured output is prohibited.

Helpfulness remains explicit rather than magical. Safe errors identify the
failed command or input category and a next action. Help and discovery derive
from the same versioned definitions used for parsing. Exact help, usage,
suggestion, shell-completion, localization, styling, and interactive behavior
remain open. Automation must work without prompts, colors, cursor control, or
terminal detection. Phase 8A makes no zero-overhead, fastest, or competitor-
superiority claim.

## Fixed Phase 8 sequence

| Phase | Fixed boundary |
|---|---|
| `8A` | Accept Structured CLI doctrine, explicit automation/lifecycle/security boundaries, and the fixed A-H roadmap. Documentation only. |
| `8B` | Implement the minimal neutral command definition, invocation, input, outcome, failure, and lifecycle contracts. No parser, registry/artifact, presenter, executable, or framework command. |
| `8C` | Implement bounded deterministic parsing plus explicit command-definition source/model/artifact compilation and exact-current validation. No execution engine, presentation, executable, or capability commands. |
| `8D` | Implement one operation-scoped command dispatcher/executor with explicit collaborators, cancellation evidence, reverse cleanup, and sequential same-process isolation. No terminal/JSON presenter or executable. |
| `8E` | Implement separate deterministic human and versioned machine presentation, stream and exit-status adaptation, help derived from definitions, and disclosure-safe rendering. No supported executable or framework commands. |
| `8F` | Implement the first bounded read-only framework command family over already accepted capabilities, with exact Composition wiring and no mutation, server, deployment, or telemetry behavior. |
| `8G` | Implement the supported one-shot `kongo` executable/application workflow and a narrowly reviewed mutation-capable build workflow with honest dry-run and no-interaction behavior. No daemon, watcher, shell, package installer, or deployment manager. |
| `8H` | Externally prove human/machine parity, parsing, exit behavior, nondisclosure, dry-run truth, lifecycle isolation, cross-platform process behavior, documentation, and the exact Phase 8 checkpoint. |

The sequence fixes order and boundaries only. Every later letter requires a new
independently authenticated architect directive. A correction suffix repairs
only its named boundary and does not create a roadmap letter.

## Rejected alternatives

- A human-only terminal contract or machine mode produced by stripping color.
- Any executable identity other than exactly `kongo`, including aliases.
- Handlers that print, read globals, style output, prompt invisibly, or exit.
- Presenters that execute behavior or redefine success.
- Runtime scanning, Reflection discovery, mutable global registries, plugin or
  network probing, hidden autowiring, service location, proxies, or `eval`.
- Ambient environment, working-directory, credential, signal, clock, or
  terminal authority inside the neutral engine.
- Hidden interaction, hidden mutation, implicit repair, or a dry run that
  performs intended persistent mutation or conceals uncertainty.
- Process termination as cleanup, static invocation state, or one-shot-only
  lifecycle testing.
- Making lower capabilities depend on the CLI or making the CLI the Kernel,
  container, application architecture, daemon, or ambient authority source.

## Consequences

Kongono now has Accepted Structured CLI architecture and a fixed Phase 8
roadmap without a runtime implementation. Later work has explicit constraints
for authority capture, deterministic discovery, machine parity, mutation
safety, lifecycle isolation, diagnostics, dependency direction, versioning,
and bounded performance.

This architecture adds no production package or dependency and changes no
Graph, Composition, Kernel, diagnostic, HTTP, database, persistence, developer-
intelligence, telemetry, deployment, or release behavior. Kongono remains
pre-1.0, Experimental where implemented, on the accepted PHP 8.5 current-minor
line, and not production-ready.

## Prohibited scope

Phase 8A introduces no PHP, public API, command inventory, option schema,
parser, source schema, artifact schema, JSON schema, media type, exact exit-code
table, package, executable, installer, scaffolder, migration runner, shell
completion, interactive UI, process manager, daemon, watcher, telemetry, or
deployment behavior. It does not begin Phase 8B.

## Open decisions

- Exact neutral contract package ownership and public surface.
- Definition source, model, artifact, identifiers, compiler, and provenance.
- Token grammar, bounds, parsing diagnostics, and help or suggestion behavior.
- Machine schema, media type, version negotiation, stream mapping, and exit
  status table.
- Secure input carriers, terminal capability, signal, cancellation, and
  cross-platform process adaptation.
- The first bounded read-only command family and the later reviewed mutation
  workflow.

These decisions require later independently authenticated directives.
