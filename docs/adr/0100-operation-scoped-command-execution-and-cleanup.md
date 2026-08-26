# ADR 0100: Operation-Scoped Command Execution and Cleanup

## Status

Accepted

## Date

2026-08-26

## Context

Phase 8C established deterministic already-tokenized parsing and exact-current
command-definition evidence without execution authority. Phase 8D needs one
presentation-neutral execution boundary that remains explicit, bounded, safe in
persistent processes, and independent of host and application composition.

The boundary must preserve neutral result truth while accounting for caller-
owned cancellation collaborators and fallible operation cleanup. It cannot rely
on process exit, mutable registration, service location, discovery, or static
current-invocation state.

## Decision

### Separate upper runtime package

Phase 8D adds Experimental `kongono/console-runtime` under
`Kongono\ConsoleRuntime\`. It depends only on PHP, API Metadata, Console, and
Diagnostics. Console remains the neutral lower contract package; Console Runtime
owns the concrete operation and dispatch lifecycle. Console Build remains a
separate build-only sibling and is not a runtime dependency.

The root supplies an immutable `CommandHandlerSet` containing explicit
`CommandHandlerBinding` values. Each binding combines one exact command
identifier with one already-constructed typed handler. The set detaches, checks
one through 256 exact unique identifiers, sorts by ordinal bytes, and performs
case-sensitive lookup. It is not a mutable registry and performs no discovery,
construction, Reflection, autowiring, container lookup, or fallback selection.

### One fresh operation per dispatch

Every bound dispatch creates a fresh `CommandExecutionOperation` containing the
exact invocation and cancellation collaborator. The dispatcher checks explicit
cancellation exactly once immediately before handler execution. Requested
cancellation produces a neutral cancelled result without calling the handler.
Otherwise the exact handler executes once through the neutral `CommandOperation`
contract.

A missing binding returns a neutral failed Capability result without creating an
operation or calling cancellation or handler behavior. Handler-returned
succeeded, rejected, failed, or cancelled results remain unchanged by object
identity when cleanup succeeds.

Cancellation during handler work is cooperative through the explicit operation
collaborator. The dispatcher deliberately performs no late cancellation check:
relabeling a completed result could falsely claim that completed mutation was
cancelled. Cancellation collaborators that throw are exceptional primary
failures.

### Retirement and deferred cleanup

The neutral operation contract gains `defer(CommandCleanup): void`; Console owns
only the neutral cleanup collaborator interface. A concrete operation accepts at
most 64 collaborators unique by object identity and preserves registration
order.

Retirement transitions to Retired and detaches the invocation, cancellation,
cleanup stack, and identity evidence before invoking fallible cleanup. Cleanup is
then attempted exactly once for every collaborator in strict reverse order.
Failures do not stop later cleanup. Repeated retirement is a no-op and never
retries a release. Access and registration after retirement fail through the
package-owned safe lifecycle exception.

### Failure preservation and safe diagnostics

Handler or cancellation throwables remain exact primary failure identities.
Cleanup throwables remain exact identities in observed reverse-execution order.
Primary-only, cleanup-only, and combined shapes produce one Public
`CommandExecutionException` with diagnostic 1004, 1005, or 1006. Its previous
exception is the primary when present and otherwise the first cleanup failure.

Safe exception messages, stringification, and diagnostic context contain only
fixed package, category, primary-presence, cleanup-count, and remediation
evidence. They do not include command identifiers, handler types, invocation
input, result data, throwable messages or traces, paths, SQL, credentials,
provider data, or environment state. Native trace and previous-exception APIs
retain their normal PHP meaning and are not represented as sanitized.

### Persistent-process isolation

The dispatcher retains only its immutable handler set between completed calls.
Each invocation has a different operation and releases operation-owned
references before completion. An instance-local guard rejects reentrant dispatch
on the same dispatcher and is always cleared in `finally`, so every later
sequential call remains available after success, cancellation, or failure.
Different dispatcher instances remain independent. Caller-owned handler state
and handler lifetime remain caller responsibilities.

## Rejected alternatives

- Mutable handler registries, defaults, aliases, or fallback handlers.
- Runtime filesystem, namespace, attribute, Reflection, plugin, or network discovery.
- Container injection, service location, ambient resolvers, or hidden autowiring.
- Ambient signal or other global cancellation state.
- Reinterpreting a completed result from a late cancellation check.
- Cleanup deferred to process exit or stopped after the first failure.
- Hidden cleanup retry, command middleware, or automatic transaction ownership.
- Handler printing, stream writes, terminal adaptation, or direct process exit.
- Symfony Console as the execution runtime.
- Static mutable current invocation, operation, handler, result, or failure state.

## Consequences and prohibited scope

The production topology is now exactly thirty-three acyclic packages. Phase 8A
through Phase 8D are implemented. The runtime supplies no definition loader,
presenter, serializer, JSON or human schema, streams, exit mapping, help,
terminal behavior, process adapter, executable, `kongo`, real framework command,
capability adapter, application bootstrap, Composition integration, transaction,
retry, or host I/O. Phase 8E through Phase 8H remain planned and separately
authorized work.

This decision changes no parser or definition-artifact semantics, Application
Graph or Composition schema, Kernel lifecycle, HTTP, database, persistence,
developer-intelligence, telemetry, deployment, or release behavior.

## Related authorities

- [ADR 0096: Explicit Structured CLI and Automation Boundary](0096-explicit-structured-cli-and-automation-boundary.md)
- [ADR 0097: Deterministic Command Lifecycle and Presentation Separation](0097-deterministic-command-lifecycle-and-presentation-separation.md)
- [ADR 0098: Neutral Structured CLI Contracts and Package Boundary](0098-neutral-structured-cli-contracts-and-package-boundary.md)
- [ADR 0099: Deterministic Command Parsing and Definition Evidence](0099-deterministic-command-parsing-and-definition-evidence.md)
- [Structured CLI architecture](../architecture/structured-cli.md)
- [Phase 8 roadmap](../architecture/phase-8-roadmap.md)
- [`kongono/console-runtime` package guide](../../packages/console-runtime/README.md)
