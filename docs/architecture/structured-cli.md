# Structured CLI Architecture

## Purpose and current status

Kongono's Structured CLI is the Accepted developer and operator edge for
humans, automation, software clients, and governed agents. The official
executable is exactly `kongo`; there is no accepted alias.

Phase 8A accepts the architecture. Phase 8B implements the Experimental neutral
`kongono/console` contract package. Phase 8C adds immutable command/input
specifications, deterministic parsing of already-tokenized input, and the
separate Experimental `kongono/console-build` evidence package. Phase 8D adds
the separate Experimental `kongono/console-runtime` execution package. Phase
8E adds bounded result records and the separate Experimental
`kongono/console-presentation` package. Phase 8F adds the Experimental
`kongono/console-commands` upper capability package and three exact read-only
command handlers. Phase 8G adds the Experimental
`kongono/console-application` upper package and one-shot `kongo` executable,
bringing the production topology to thirty-six acyclic packages. The supported
application exposed exactly `application:build` at the Phase 8 checkpoint. Phase
9G adds exactly `security:check` and `security:inspect`; no mutable registry or
runtime discovery exists.

## Current truth and planned truth

Current truth includes [ADR 0012](../adr/0012-structured-cli-contract.md),
[ADR 0096](../adr/0096-explicit-structured-cli-and-automation-boundary.md),
[ADR 0097](../adr/0097-deterministic-command-lifecycle-and-presentation-separation.md),
[ADR 0098](../adr/0098-neutral-structured-cli-contracts-and-package-boundary.md),
[ADR 0099](../adr/0099-deterministic-command-parsing-and-definition-evidence.md),
[ADR 0100](../adr/0100-operation-scoped-command-execution-and-cleanup.md),
[ADR 0101](../adr/0101-deterministic-command-presentation-and-process-adaptation.md),
[ADR 0102](../adr/0102-first-read-only-framework-capability-commands.md),
[ADR 0103](../adr/0103-supported-kongo-edge-and-honest-application-build.md),
the [`kongono/console` guide](../../packages/console/README.md), and the
[Phase 8 roadmap](phase-8-roadmap.md). They accept doctrine, lifecycle,
security, automation, dependency direction, and the exact neutral values.

The current application implements only the closed three-command `kongo` help
and grammar. `application:build` retains `--plan` as its honest non-mutating dry
run and `--apply` as explicit
mutation authority, and `--no-interaction` is automation-safe because no prompt
exists. `security:check` and `security:inspect` require only ordinary
application-root/output-directory inputs, are read-only, accept no plan/apply,
and disclose only bounded artifact/source identities and currentness facts.
Other commands and option surfaces remain unimplemented.

## Three-way separation

```text
immutable command meaning
    -> one explicit invocation and neutral execution result
        -> human presentation
        -> versioned machine presentation
            -> outer host stream and process-status adaptation
```

Command meaning defines bounded inputs and semantics. Execution consumes one
explicit invocation and typed collaborators, then returns a neutral result.
Presentation renders that result without executing behavior or changing its
meaning. The outer executable alone adapts the completed result to host streams
and final process status.

Handlers do not print, style output, read global input, prompt invisibly, or
call `exit`. Presenters do not execute commands, mutate their state, or
reinterpret success.

## Explicit host boundary

The future outer adapter may capture approved process state once, bound and
normalize it, and pass detached tokens, values, and ports inward. The neutral
engine does not implicitly read process arguments, standard streams,
environment, current directory, terminal state, signals, clocks, randomness,
configuration, credentials, or registries.

Ordinary process arguments are not a safe carrier for secrets when listings or
shell histories may expose them. Secure carriers require a later contract. No
Phase 8A example grants credential, filesystem, network, mutation, transaction,
or deployment authority.

## Deterministic definitions and build evidence

Command definitions and provenance will be deterministic build evidence derived
from explicit application or framework authority. Runtime filesystem scanning,
namespace crawling, attribute- or Reflection-based discovery, mutable global
registries, plugin probing, and network discovery are rejected.

```text
explicit source authority
    -> bounded build/check compilation
        -> immutable provenance-bearing definition evidence
            -> exact-current validation before execution
```

Generated evidence is reviewable derived evidence and is not executed during
validation. Missing, stale, partial, mixed, ambiguous, unsupported, or
unverifiable evidence fails closed. Exact formats and ownership remain later
decisions.

## Human and machine output parity

Human presentation is concise, readable, accessible, and actionable. Machine
presentation is a versioned deterministic contract, not terminal prose with
colors removed. Both consume the same neutral result and agree on outcome and
diagnostic identity.

One machine invocation produces one bounded canonical result document on its
designated data stream. It contains no banner, progress animation, ANSI
sequence, debug dump, or interleaved human prose. JSON schema, media type,
compatibility, stream selection, and exit statuses are deliberately open until
a later directive.

## Interaction and dry-run rules

Hidden interaction is prohibited. A command that can prompt declares that
capability. `--no-interaction` disables prompting and fails explicitly when
required input is absent. Machine mode never silently prompts.

Persistent mutation requires visible authority. A mutation-capable command
offers honest `--dry-run` behavior where applicable: it does not perform the
intended persistent mutation, and it does not present uncertain planning as a
successful preview. Destructive intent, confirmation, non-interactive
authorization, and apply authority remain separate. Interactivity cannot repair
a failed precondition or widen authority.

## Invocation lifecycle and cleanup

One invocation owns one bounded operation lifecycle. Inputs, output buffers,
diagnostics, domain objects, service instances, database and persistence state,
and cancellation state are released before completion. Cleanup is explicit,
reverse-ordered where applicable, and preserves primary plus cleanup failures
under existing Kongono error doctrine.

Static mutable invocation state cannot leak between sequential calls. Process
termination is not cleanup. The future supported executable is one-shot, but
the neutral engine must be testable through sequential same-process
invocations. Signals and cancellation are explicit ports or evidence, not
hidden asynchronous global behavior.

Phase 8D implements this execution slice with immutable explicit handler
bindings, one fresh concrete operation per bound dispatch, one exact
pre-execution cancellation check, and bounded cleanup registration. Retirement
is observable and detaches operation-owned references before every cleanup is
attempted once in reverse order. Primary and cleanup throwable identities are
preserved in one safe combined exception. A same-dispatcher reentrant call is
rejected by an instance-local guard that is always cleared for later sequential
reuse. The dispatcher does not perform a late cancellation check or reinterpret
a handler-returned neutral result.

## Diagnostics, security, and nondisclosure

Expected command outcomes are neutral values. Programmer errors, violated
invariants, and infrastructure failures remain distinguishable. Existing
actionable diagnostic identity, bounded context, disclosure, safe
stringification, and failure-composition rules apply unchanged.

Machine output may expose stable bounded error identity and safe fields. It
must not expose raw exception messages, stack arguments, provider output,
secrets, local paths, SQL, credentials, environment dumps, or arbitrary input.
Human developer output may name a safe bounded cause and next step. A trusted or
debug presentation mode neither grants command authority nor becomes available
from a CLI flag alone.

## Dependency direction

The future neutral CLI contracts remain independent of HTTP, Routing, Web
Runtime, PHP CGI, database and persistence providers, Chie, Hantei, Oshie,
telemetry exporters, and applications. Capability adapters may depend inward on
neutral contracts. Neutral contracts never depend outward on adapters.

The outer executable and application integration are an upper leaf and
composition root. Handlers receive explicit typed collaborators through
Composition. They never receive a resolver or container, query a service
locator, or use property-hook dependency resolution. Phase 8A changes no
Application Graph or Composition schema.

## Persistent-runtime and performance constraints

The normal runtime path will use prevalidated immutable evidence, explicit
typed values, and ordinary PHP. Runtime Reflection, scanning, dynamic proxies,
`eval`, and framework-substrate discovery are prohibited. Parsing and rendering
are bounded and deterministically ordered.

Streaming requires an explicit result/output contract that preserves machine
determinism, cleanup, and failure truth. Unbounded captured output is
prohibited. Phase 8A supplies no measurements and makes no zero-overhead,
fastest, or competitor-superiority claim.

## Prohibited behavior

- Any executable identity or alias other than the future `kongo` executable.
- Direct printing, styling, ambient input, hidden prompting, or `exit` inside
  command behavior.
- Runtime discovery, mutable command registries, plugin or network probing,
  service location, hidden autowiring, proxies, or `eval`.
- Hidden interaction, I/O, mutation, repair, retries, transactions, or
  destructive authority.
- Machine output derived from an unstable terminal transcript.
- Process exit used as cleanup or static state shared across invocations.
- Lower packages depending on the CLI or the CLI replacing Kernel,
  Composition, or application architecture.

## Open decisions

Later authenticated directives must select executable packaging and the
mutation workflow. Definition evidence, parsing, cancellation, operation
ownership, presentation, secure input, help, and the first read-only framework
command family are implemented within their accepted Phase 8 boundaries.

Phase 8D is implemented under
[ADR 0100](../adr/0100-operation-scoped-command-execution-and-cleanup.md).
Phase 8E is implemented under
[ADR 0101](../adr/0101-deterministic-command-presentation-and-process-adaptation.md).
Human and machine forms share one result/help projection and exit map;
Protected values are omitted and explicit output ports receive one already-
presented document. Phase 8F implements three exact read-only handlers through
ADR 0102 and generated Composition wiring. Phase 8G implements the separate
application edge through ADR 0103 without fabricating capability snapshots;
those handlers remain programmatic until truthful application inputs exist.

Build publication is content-addressed, atomic, contained, idempotent for exact
current output, initial-only, and no-clobber. Human and machine command forms
share one result and exit map. There is no alias, second command, daemon,
watcher, installer, deployment manager, or shell. Phase 8H externally proves
the direct source and Composer proxy, human/machine parity, non-mutating plan,
explicit apply, exit-3 conflict no-clobber, recovery, nondisclosure, cleanup,
and the exact checkpoint without adding production behavior.

## Related authorities

- [ADR 0012: Structured CLI Contract](../adr/0012-structured-cli-contract.md)
- [ADR 0096: Explicit Structured CLI and Automation Boundary](../adr/0096-explicit-structured-cli-and-automation-boundary.md)
- [ADR 0097: Deterministic Command Lifecycle and Presentation Separation](../adr/0097-deterministic-command-lifecycle-and-presentation-separation.md)
- [ADR 0098: Neutral Structured CLI Contracts and Package Boundary](../adr/0098-neutral-structured-cli-contracts-and-package-boundary.md)
- [ADR 0099: Deterministic Command Parsing and Definition Evidence](../adr/0099-deterministic-command-parsing-and-definition-evidence.md)
- [ADR 0100: Operation-Scoped Command Execution and Cleanup](../adr/0100-operation-scoped-command-execution-and-cleanup.md)
- [ADR 0101: Deterministic Command Presentation and Process Adaptation](../adr/0101-deterministic-command-presentation-and-process-adaptation.md)
- [ADR 0102: First Read-Only Framework Capability Commands](../adr/0102-first-read-only-framework-capability-commands.md)
- [ADR 0103: Supported `kongo` Edge and Honest Application Build](../adr/0103-supported-kongo-edge-and-honest-application-build.md)
- [ADR 0104: Phase 8 External CLI Acceptance and Checkpoint](../adr/0104-phase-8-external-cli-acceptance-and-checkpoint.md)
- [Phase 8 external acceptance](phase-8-external-acceptance.md)
- [Phase 8 roadmap](phase-8-roadmap.md)
- [Runtime model](runtime-model.md)
- [Application Composition and Build](application-composition-and-build.md)
- [Error handling and actionable diagnostics](../engineering/error-handling.md)
