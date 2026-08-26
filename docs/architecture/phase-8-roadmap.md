# Phase 8 Roadmap

## Purpose and accepted entry checkpoint

Phase 8 is the Structured CLI milestone. It develops an explicit first-party
developer and operator edge for humans, automation, software clients, and
governed agents without turning terminal behavior, ambient process state, or a
service locator into framework authority.

The accepted entry is Phase 7 checkpoint
`b09b0c654f8b67f7cac3807f839f665e8c5b5469`, tree
`c0b6fed92dbb43d0bb91891b4e4496e84c0c85e4`, with thirty acyclic production
packages. Phase 8A accepts [ADR 0096](../adr/0096-explicit-structured-cli-and-automation-boundary.md),
[ADR 0097](../adr/0097-deterministic-command-lifecycle-and-presentation-separation.md),
the [Structured CLI guide](structured-cli.md), and this fixed roadmap as
documentation architecture only.

Phase 8A through Phase 8H are implemented and the milestone is checkpointed.
Phase 8B accepts
[ADR 0098](../adr/0098-neutral-structured-cli-contracts-and-package-boundary.md)
and adds the neutral `kongono/console` contract package. Phase 8C accepts
[ADR 0099](../adr/0099-deterministic-command-parsing-and-definition-evidence.md),
adds deterministic parsing and `kongono/console-build`. Phase 8D accepts
[ADR 0100](../adr/0100-operation-scoped-command-execution-and-cleanup.md), adds
`kongono/console-runtime`. Phase 8E accepts
[ADR 0101](../adr/0101-deterministic-command-presentation-and-process-adaptation.md),
adds bounded result records and `kongono/console-presentation`. Phase 8F accepts
[ADR 0102](../adr/0102-first-read-only-framework-capability-commands.md), adds
`kongono/console-commands`. Phase 8G accepts
[ADR 0103](../adr/0103-supported-kongo-edge-and-honest-application-build.md),
adds `kongono/console-application`, and brings the current topology to
thirty-six packages. The official executable is exactly `kongo` and implements
exactly `application:build`. Phase 8H accepts
[ADR 0104](../adr/0104-phase-8-external-cli-acceptance-and-checkpoint.md) and
adds only root-owned external acceptance plus the exact local checkpoint.

## Fixed Phase 8 sequence

| Phase | Fixed boundary |
|---|---|
| `8A` | **Implemented.** Accept Structured CLI doctrine, explicit automation/lifecycle/security boundaries, and the fixed A-H roadmap. Documentation only. |
| `8B` | **Implemented.** Implement the minimal neutral command definition, invocation, input, outcome, failure, and lifecycle contracts. No parser, registry/artifact, presenter, executable, or framework command. |
| `8C` | **Implemented.** Bounded deterministic already-tokenized parsing plus explicit command-definition source/model/artifact compilation and exact-current validation. No execution engine, presentation, executable, or capability commands. |
| `8D` | **Implemented.** One operation-scoped command dispatcher/executor with explicit collaborators, cancellation evidence, reverse cleanup, combined failure preservation, and sequential same-process isolation. No terminal/JSON presenter or executable. |
| `8E` | **Implemented.** Separate deterministic human and versioned machine presentation, explicit stream and exit-status adaptation, help derived from definitions, and disclosure-safe rendering. No supported executable or framework commands. |
| `8F` | **Implemented.** The first bounded read-only framework command family over already accepted capabilities, with exact Composition wiring and no mutation, server, deployment, or telemetry behavior. |
| `8G` | **Implemented.** The supported one-shot `kongo` executable/application workflow and exactly `application:build`, with non-mutating `--plan`, explicit `--apply`, and automation-safe `--no-interaction`. No daemon, watcher, shell, package installer, or deployment manager. |
| `8H` | **Implemented.** Externally prove human/machine parity, parsing, exit behavior, nondisclosure, dry-run truth, lifecycle isolation, shell-independent process behavior, documentation, and the exact Phase 8 checkpoint. |

The table fixes order and boundaries only. Each later letter requires a new
independently authenticated architect directive.

## Dependency sequence

```text
Phase 7 checkpoint: thirty acyclic packages and explicit lower capabilities
    -> 8A doctrine and fixed boundaries
        -> 8B neutral contracts
            -> 8C bounded parsing and exact-current definition evidence
                -> 8D operation-scoped execution
                    -> 8E human and machine presentation
                        -> 8F first read-only capability commands
                            -> 8G supported kongo edge and reviewed mutation
                                -> 8H external acceptance and checkpoint
```

Lower capabilities never depend upward on the CLI. The supported executable and
application integration remain an upper leaf and composition root.

## Fixed invariants

- The official executable is exactly `kongo`; no alias or alternate
  binary is accepted.
- Command meaning, one-invocation execution, and presentation/process adaptation
  remain separate.
- The neutral engine receives explicit tokens and ports or detached values and
  reads no ambient process, terminal, environment, filesystem, credential,
  signal, clock, randomness, or registry state.
- Definitions and provenance are deterministic build evidence; runtime scanning,
  Reflection discovery, mutable registries, plugin probing, and network
  discovery are prohibited.
- Missing, stale, partial, mixed, ambiguous, unsupported, or unverifiable
  definition evidence fails closed.
- Human and machine presentations derive from one neutral result and agree on
  outcome and diagnostic identity.
- Machine mode is bounded, versioned, deterministic, and free from banners,
  ANSI, progress animation, debug dumps, and interleaved human prose.
- Prompt capability is declared; machine mode does not silently prompt, and
  `--no-interaction` fails explicitly when required input is absent.
- Mutation, dry-run, destructive intent, confirmation, non-interactive
  authorization, and apply authority remain honest and distinct.
- One invocation owns and releases one operation lifecycle; cleanup is explicit,
  reverse-ordered where applicable, and preserves combined failure.
- Sequential same-process invocations share no static mutable invocation state.
- Diagnostics preserve bounded identity and nondisclosure; no secret, raw
  exception, provider output, path, SQL, credential, environment dump, or
  arbitrary input leaks through machine output.
- Neutral CLI contracts remain independent of capability adapters; handlers use
  explicit typed Composition collaborators without service location.
- Runtime paths use prevalidated immutable evidence and ordinary PHP without
  scanning, Reflection discovery, proxying, `eval`, or unbounded output.

## Acceptance themes

### 8A: architecture truth

Accept the Structured CLI doctrine, automation and lifecycle boundaries,
security and nondisclosure rules, dependency direction, official future
`kongo` identity, and fixed roadmap. Documentation only.

### 8B: minimal neutral contracts

Prove the smallest Experimental definition, invocation, input, outcome,
failure, and lifecycle vocabulary without parsing, discovery evidence,
presentation, executable packaging, or framework commands.

### 8C: bounded parsing and definition evidence

Prove deterministic token parsing plus one explicit source, detached model,
provenance artifact, and exact-current validation. Candidate validation does not
execute generated bytes. Execution and presentation remain absent.

### 8D: operation-scoped execution

Implemented by immutable explicit handler bindings and
`kongono/console-runtime`: one fresh operation per dispatch, one exact
pre-execution cancellation check, neutral result identity preservation, bounded
reverse every-attempt cleanup, combined failure evidence, reentrancy rejection,
and sequential same-process isolation without terminal or machine rendering.

### 8E: human and machine presentation

Prove parity from one neutral result, bounded deterministic human and versioned
machine rendering, disclosure-safe diagnostics, stream adaptation, exit status,
and help derived from definitions. No supported executable exists yet.

### 8F: first read-only capability commands

Implemented as the exact `graph:inspect`, `composition:inspect`, and
`quality:inspect` read-only family over already accepted capabilities with exact
Composition wiring. It adds no mutation, server, deployment, telemetry, ambient
authority, executable, or lower-package dependency.

### 8G: supported executable and reviewed mutation

Implemented by the one-shot `kongo` application workflow and exactly
`application:build`. `--plan` performs complete in-memory generation and
current-output assessment without mutation. `--apply` explicitly authorizes
atomic, content-addressed, contained, initial-only, no-clobber publication and
post-publication identity proof. There is no prompt; `--no-interaction` is
automation-safe. Phase 8F snapshot handlers remain programmatic rather than
receiving fabricated defaults. This phase adds no daemon, watcher, shell,
installer, deployment manager, alias, or second command.

### 8H: external closure

Implemented by the root-owned `composer acceptance:phase8` profile. It proves
direct-source and Composer-proxy behavior through fresh argv-array processes,
human/machine parity, parsing and preconditions, zero-write planning,
identity-matched explicit apply, idempotence, exit-3 conflict no-clobber,
failure recovery, nondisclosure, repository preservation, and complete owned
temporary cleanup. Current execution evidence is Windows; the process contract
is shell-independent and does not claim execution on every operating system.

## Prohibited scope and truthful non-claims

Phase 8A implements no PHP, package, API, definition source, schema, parser,
registry, artifact, dispatcher, presenter, command, process adapter, executable,
installer, scaffolder, migration runner, shell completion, interactive UI,
daemon, watcher, process supervisor, telemetry, deployment, or release surface.
It changes no Application Graph, Composition, Kernel, diagnostic, HTTP,
database, persistence, developer-intelligence, frontend, extension, or agent
runtime behavior.

The roadmap does not make Kongono a full framework, Stable API, 1.0,
production-ready, deployment-ready, or compatible with PHP 8.6 or PHP 9. It
makes no zero-overhead, fastest, or competitor-superiority claim. Command names,
options, schemas, streams, exit codes, and packaging are not Stable merely
because roadmap prose mentions their direction.

## Correction-suffix rule

If an accepted letter needs a narrowly authenticated correction, its directive
and report may use an ordinal suffix. A correction repairs only its named
boundary. It does not create a roadmap letter, reorder the sequence, authorize
later work, or rewrite historical directives, reports, or checkpoints.

## Related authorities

- [ADR 0012: Structured CLI Contract](../adr/0012-structured-cli-contract.md)
- [ADR 0096: Explicit Structured CLI and Automation Boundary](../adr/0096-explicit-structured-cli-and-automation-boundary.md)
- [ADR 0097: Deterministic Command Lifecycle and Presentation Separation](../adr/0097-deterministic-command-lifecycle-and-presentation-separation.md)
- [ADR 0100: Operation-Scoped Command Execution and Cleanup](../adr/0100-operation-scoped-command-execution-and-cleanup.md)
- [ADR 0101: Deterministic Command Presentation and Process Adaptation](../adr/0101-deterministic-command-presentation-and-process-adaptation.md)
- [ADR 0102: First Read-Only Framework Capability Commands](../adr/0102-first-read-only-framework-capability-commands.md)
- [ADR 0103: Supported `kongo` Edge and Honest Application Build](../adr/0103-supported-kongo-edge-and-honest-application-build.md)
- [ADR 0104: Phase 8 External CLI Acceptance and Checkpoint](../adr/0104-phase-8-external-cli-acceptance-and-checkpoint.md)
- [Structured CLI architecture](structured-cli.md)
- [Phase 8 external acceptance](phase-8-external-acceptance.md)
- [Phase 7 roadmap](phase-7-roadmap.md)
- [Phase 7 external acceptance](phase-7-external-acceptance.md)
- [Application Composition and Build](application-composition-and-build.md)
- [Runtime model](runtime-model.md)
- [Error handling and actionable diagnostics](../engineering/error-handling.md)
- [Package layout](../engineering/package-layout.md)
