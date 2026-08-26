# ADR 0104: Phase 8 External CLI Acceptance and Checkpoint

## Status

Accepted and implemented by Phase 8H.

## Context

Phases 8A through 8G established the Structured CLI doctrine, neutral command
contracts, deterministic definition evidence, operation-scoped execution,
human and machine presentation, bounded capability handlers, and the supported
one-shot `kongo` application. Package and integration tests prove those parts in
process, but milestone closure also requires evidence at the real executable
boundary without widening production authority.

External proof must distinguish the direct Composer source binary from the
installed Composer proxy, demonstrate that `--plan` is genuinely non-mutating,
and exercise explicit `--apply` publication. It must also preserve caller and
repository state while testing paths containing spaces, output conflicts,
failure recovery, deterministic presentation, and nondisclosure.

## Decision

Phase 8H adds one root-owned, test-only external profile under
`tests/External/Console`. The profile launches the official source
`packages/console-application/bin/kongo` and `vendor/bin/kongo` through explicit
argument arrays using `PHP_BINARY`. Every invocation is a fresh bounded process
with closed stdin, captured raw stdout and stderr, a finite deadline, and no
shell string, prompt, watcher, daemon, or background child.

The profile owns one unique system-temporary application root whose final path
contains a space. All fixture classes, composition source, preload bytes, and
published outputs remain strictly below that root and are removed in `finally`.
Repository Git status is captured before the first executable case and must be
byte-identical after cleanup.

The matrix proves exactly one exposed command, human and canonical version-1
machine help parity, invalid grammar, invalid source, predicted plan identities,
human plan parity, explicit noninteractive apply through the other executable
edge, current-plan zero-write behavior, repeated-apply idempotence, conflict
no-clobber behavior, recovery, edge parity, bounded output, and nondisclosure.

A conflicting pre-existing content-addressed target is an accepted workflow
precondition failure and exits 3. Its sentinel bytes remain unchanged, the
sibling output is not partially published, and no raw value is disclosed. Exit
6 remains reserved for genuine Infrastructure behavior such as an output-port
failure and continues to be proved by accepted unit tests; the external profile
does not fabricate a broken process port.

The root Composer script is exactly `acceptance:phase8`, and the Hantei profile
records the same command for deterministic repository-quality evidence. On
success the harness emits one canonical LF-terminated object containing only
`status`, `profile`, observed `case_count`, and `cleanup`. On failure it emits
only the bounded token `phase8_acceptance_failed` to stderr.

## Consequences

Phase 8A through 8H are implemented and checkpointed as one Structured CLI
milestone. `kongo` still exposes exactly `application:build`; Phase 8H adds no
production PHP, package, dependency, diagnostic, command, option, executable,
runtime integration, deployment behavior, or Stable API claim.

The external profile is current Windows evidence plus a shell-independent
process contract. It does not claim that every operating system has been run in
this checkpoint. Portability rests on explicit PHP argument arrays, ordinary
files, bounded streams, LF documents, and no shell-specific syntax in the
application invocation.

The checkpoint is local evidence only. It creates no push, tag, release,
deployment, second command, Phase 9 behavior, or production-readiness claim.
