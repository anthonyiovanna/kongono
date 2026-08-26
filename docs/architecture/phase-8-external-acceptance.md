# Phase 8 External Acceptance

## Purpose

Phase 8H closes the Structured CLI milestone with real-process evidence for the
supported `kongo` edge. The profile verifies behavior visible to a human,
automation client, or governed agent without adding production authority.

The authoritative decision is
[ADR 0104](../adr/0104-phase-8-external-cli-acceptance-and-checkpoint.md).

## Running the profile

From the repository root run:

```text
composer acceptance:phase8
```

The command accepts no arguments. Success is exactly one canonical JSON line
with the profile name `kongono-phase8-cli`, a positive observed case count, and
`cleanup` equal to `complete`. Failure is nonzero and emits only
`phase8_acceptance_failed` to stderr.

## Process and fixture boundary

The root-owned harness:

- derives the repository from its own fixed location;
- invokes both the direct source and Composer proxy with `PHP_BINARY` and
  explicit argument arrays;
- closes stdin immediately and captures bounded raw stdout and stderr;
- applies a finite deadline and leaves no child process running;
- owns a unique system-temporary application root whose final path contains a
  space;
- keeps fixture source, preload data, and output below that root;
- compares repository Git status byte-for-byte before and after execution; and
- removes every owned path in `finally` before emitting success.

The application invocation uses no shell command string, batch wrapper,
PowerShell alias, terminal prompt, daemon, watcher, network service, credential,
environment DSN, or user configuration.

## Accepted matrix

The profile externally proves:

1. direct-source root help exposes exactly `application:build`;
2. canonical machine help has equivalent identity, summary, inputs,
   interaction, and mutation policy;
3. Composer-proxy command help has the same semantics;
4. malformed or unknown grammar exits 2 with one bounded document;
5. a malformed composition source independently exits 3 without publication;
6. machine plan creates no output and predicts two content identities;
7. human plan reports the same identities and publication decision;
8. explicit `--apply --no-interaction` through the other edge publishes exactly
   the predicted Composition artifact and generated wiring;
9. a plan after apply is write-free and reports publication unnecessary;
10. repeated apply is idempotent;
11. a conflicting target exits 3, preserves its private sentinel, publishes no
    sibling partial output, and discloses no rejected bytes;
12. a healthy invocation succeeds after the failed conflict;
13. the direct source and proxy agree on help, result, and exit semantics;
14. machine documents are canonical single-line JSON plus LF and human
    documents are deterministic UTF-8/LF text;
15. output omits owned paths, fixture namespace/source, private values, raw
    rejected input, environment marker, stack/debug text, and ANSI sequences;
16. repository status remains byte-identical;
17. output and temporary paths are removed; and
18. no process, stream, temporary publication file, cache, lock, coverage file,
    or environment mutation remains.

The conflict and malformed-source cases share exit 3 but remain distinct
semantic proofs. Exit 6 is the Infrastructure category and stays covered by
unit tests that can supply an explicit failing output port; this external
profile does not manufacture one.

## Security and disclosure

All fixture source is inert application input. It cannot grant tool, process,
filesystem, Git, network, deployment, or authorization capability. The harness
compares bounded structural projections instead of exposing child exceptions or
raw process output on failure. Its success document contains no timestamp,
duration, path, command line, identity hash, hostname, username, environment,
or private fixture detail.

## Portability and current evidence

The checkpoint executes on the supported Windows PHP 8.5 profile. The process
contract itself is shell-independent: executable and arguments are distinct
values, paths are passed without shell quoting, presentation is LF-framed, and
temporary ownership uses ordinary filesystem operations. This is not a claim
that Phase 8H was executed on every operating system.

## Stability and non-claims

The harness is root-owned test evidence, not a Public framework API. Phase 8H
does not add another command, alias, interactive shell, installer, deployment
manager, runtime discovery, provider integration, Stable status, release, or
production-readiness guarantee. Phase 9 remains unimplemented.
