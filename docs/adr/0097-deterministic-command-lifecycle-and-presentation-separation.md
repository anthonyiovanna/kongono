# ADR 0097: Deterministic Command Lifecycle and Presentation Separation

## Status

Accepted

## Date

2026-08-26

## Context

ADR 0012 requires automation-safe Structured CLI behavior, and ADR 0096 fixes
the Phase 8 milestone boundary. A command edge that reads ambient process state,
prints directly, hides interaction, or treats process exit as cleanup would
violate Kongono's deterministic evidence, explicit authority, diagnostic, and
persistent-runtime doctrines.

Command meaning also cannot be safely versioned if parsing, execution, terminal
styling, machine serialization, stream writes, and exit behavior are one
undifferentiated operation. Phase 8 therefore needs an Accepted lifecycle and
presentation boundary before any contracts or runtime are implemented.

## Decision

### One explicit invocation

The neutral command engine accepts caller-supplied tokens plus explicit ports
or detached values. It does not read process arguments, standard streams,
environment variables, current working directory, terminal state, signals,
clocks, randomness, configuration files, credentials, or registries implicitly.
The future outer adapter may capture approved host state once, normalize and
bound it, and supply it inward as explicit evidence.

One invocation owns one operation lifecycle and produces one neutral result.
Expected command outcomes are values. Programmer errors, invariant violations,
and infrastructure failures remain distinguishable from those outcomes.

### Three independent responsibilities

Command definitions own immutable, versioned meaning and bounded inputs.
Execution owns one explicit invocation over typed collaborators and produces a
presentation-neutral result. Presentation and process adaptation render that
same result for a human or machine, select streams, and derive final process
status under a separately accepted contract.

Handlers therefore do not print, style, inspect terminal capability, read
global input, prompt invisibly, or call `exit`. Presenters do not invoke command
behavior, mutate command state, or reinterpret success. The outer executable is
the only future boundary allowed to capture host inputs and perform final stream
and process adaptation.

### Deterministic definition evidence

Definitions and provenance are deterministic build evidence derived from
explicit application or framework authority. Runtime filesystem or namespace
scanning, attribute- or Reflection-based discovery, mutable global registries,
plugin probing, and network discovery are prohibited. Generated evidence is
derived rather than authoritative and is validated without execution.

Missing, stale, partial, mixed, ambiguous, unsupported, or unverifiable
evidence fails closed. Exact source and artifact schemas, compiler, IDs, and
ownership remain later decisions.

### Presentation parity

Human and machine renderers consume the same neutral result and agree on
outcome and diagnostic identity. Human presentation is concise, accessible, and
actionable. Machine presentation is versioned and deterministic, not a terminal
transcript with decoration removed.

A machine invocation emits one bounded canonical result document on its
designated data stream without banners, progress animation, ANSI sequences,
debug dumps, or interleaved human prose. Exact schemas, media types, stream
mapping, and exit statuses remain open and are not implied by Phase 8A.

### Interaction, dry run, and authority

Prompt capability is declared. `--no-interaction` disables prompting and fails
when required input is absent; machine mode never silently prompts. Commands
capable of persistent mutation provide honest `--dry-run` behavior where
applicable: the intended mutation does not occur, and uncertainty is not
reported as successful preview evidence.

Destructive intent, confirmation, non-interactive authorization, and apply
authority are separate. Interactivity cannot broaden authority, silently repair
a precondition, create a hidden transaction, or introduce a hidden retry.

### Cleanup and sequential isolation

Invocation-owned inputs, output buffers, diagnostics, domain objects, service
instances, database and persistence state, and cancellation state are released
before completion. Cleanup is explicit and reverse-ordered where applicable.
When work and cleanup both fail, existing Kongono combined-failure doctrine
preserves the primary and cleanup evidence.

No static mutable invocation state survives between calls. Process termination
is not a cleanup mechanism. The one-shot supported executable direction does
not weaken the requirement that the neutral engine be tested through sequential
same-process invocations. Signal and cancellation behavior enter through
explicit ports or evidence rather than hidden asynchronous globals.

### Diagnostics and disclosure

Existing actionable diagnostic identity, bounded context, disclosure, and safe
stringification rules apply unchanged. Machine results expose only stable
bounded identity and safe fields, never raw exception messages, stack
arguments, provider output, secrets, paths, SQL, credentials, environment
dumps, or arbitrary input. Human developer presentation may provide a safe
bounded explanation and action when evidence supports one.

Trusted or debug rendering grants no command authority and cannot be enabled by
a CLI flag alone. Presentation does not elevate disclosure or execution rights.

### Dependency direction and composition

Neutral CLI contracts remain below capability adapters and independent of HTTP,
Routing, Web Runtime, PHP CGI, database and persistence providers, Chie,
Hantei, Oshie, telemetry exporters, and applications. The outer executable is
an upper leaf and composition root. Handlers receive explicit typed
collaborators through Composition, never a resolver, container, ambient service
locator, or property-hook dependency source.

Phase 8A changes neither the thirty-package graph nor Composition, Application
Graph, Kernel, diagnostics, transport, data, persistence, developer-
intelligence, deployment, or release behavior.

## Rejected alternatives

- Direct printing, terminal styling, global reads, prompting, or `exit` inside
  command handlers.
- A presenter that executes work, changes results, or owns authority.
- A machine contract derived by removing color from human output.
- Runtime discovery, scanning, Reflection, mutable registries, plugin probing,
  network discovery, service location, or hidden autowiring.
- Ambient host state, secret-bearing ordinary arguments, hidden interaction,
  hidden mutation, automatic repair, hidden retries, or hidden transactions.
- Process exit as cleanup or isolation that depends on a fresh process.
- A CLI package dependency imposed on lower capabilities.

## Consequences

Later Phase 8 implementation must make invocation state, authority, lifecycle,
outcomes, cleanup, presentation, streams, and process adaptation visible and
testable. Human experience and machine automation share meaning without sharing
an unstable terminal transcript. Persistent-worker reuse becomes a required
isolation proof rather than an assumption.

The separation introduces implementation work in later letters, but it keeps
compatibility, security, and failure semantics reviewable. No package, schema,
API, parser, dispatcher, presenter, executable, or command is created by this
ADR.

## Open decisions

Later authenticated directives must select the exact neutral values and bounds,
definition evidence format, parsing model, cancellation contract, human and
machine rendering schemas, stream rules, exit-status table, secure input
carriers, help behavior, and process packaging. Phase 8B has not begun.
