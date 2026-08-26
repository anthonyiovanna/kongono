# ADR 0099: Deterministic Command Parsing and Definition Evidence

## Status

Accepted

## Date

2026-08-26

## Context

Phase 8B established presentation-neutral command contracts but deliberately
left token interpretation and command-definition evidence unimplemented. Phase
8C needs deterministic parsing of already-tokenized caller input and a
fail-closed build boundary that can prove which immutable command
specifications are current without granting execution or presentation
authority.

## Decision

### Already-tokenized parsing

`kongono/console` now owns an immutable `CommandSpecificationSet` and a pure
`CommandTokenParser`. The parser receives explicit tokens; it does not split a
command line or read argv, streams, environment, a working directory, terminal
state, files, or registries. The first token is one exact command identifier.
There are no aliases, abbreviations, positional values, short options,
response files, quoting rules, environment expansion, or `--` delimiter.

Ordinary inputs have the exact `--name=value` form. Reserved tokens are
`--plan`, `--apply`, and `--no-interaction`. Read-only commands reject plan or
apply. Mutation-capable commands require exactly one of plan or apply. Declared
interaction is permitted unless no-interaction is explicit; undeclared
interaction remains disabled. Tokens are bounded by count, individual bytes,
aggregate bytes, UTF-8 validity, and control-character exclusion. Accepted
inputs are returned in specification order, independent of token order.

Sensitive values cannot arrive through ordinary tokens. They use a separate
explicit carrier of already-constructed sensitive `CommandInput` values.
Wrongly classified, unknown, duplicate, or overlapping secure inputs fail
without retaining their values in diagnostics, exceptions, or parse failure
evidence. Parse rejection returns a neutral bounded `CommandParseResult`; it
does not execute a handler or throw rejected input back to the caller.

### Immutable specifications

Each `CommandSpecification` combines one existing invariant-owning command
definition with zero through 64 unique input definitions. A specification set
contains one through 256 unique commands. Both collections detach and sort by
ordinal identity. They are immutable values, not mutable registries, discovery
results, or service maps.

### Build-time definition evidence

Phase 8C adds the separate Experimental `kongono/console-build` package. It
depends only on PHP, Hash, JSON, API Metadata, Console, and Diagnostics. Its sole
input is one explicit application-owned `kongono.commands.json` version-1
source. That inert source contains command and input semantics only: no handler,
class, callable, service, destination, environment, secret, default, prompt,
alias, or discovery rule.

The closed source schema compiles through Console's invariant-owning value
objects into a detached `kongono.command-model` version-1 model. Source array
order is nonsemantic; commands and inputs use ordinal identity order. Canonical
source identity is therefore calculated from the normalized source semantics,
so two sources that differ only in array order emit identical source, model,
and complete artifact evidence.

The builder emits compact LF-terminated canonical JSON using explicit insertion
order. The `kongono.command-artifact` version-1 evidence binds the fixed source
path, normalized source bytes and digest, canonical model bytes and digest, and
a digest of the closed 16-file builder implementation inventory. It includes no
timestamp, absolute host path, process, environment, randomness, Git, or vendor
state. Building returns bytes in memory and performs no publication.

Structural validation rejects malformed, noncanonical, unknown, missing,
oversized, internally inconsistent, or changed-digest evidence. It rehydrates
the model through Console value objects and requires exact canonical
reserialization. Exact-current validation first proves structure, then rebuilds
from the current source and compares the complete canonical artifact bytes with
`hash_equals()`. An embedded digest or selected fields cannot establish
freshness, and no cache is authoritative.

### Safety boundary

Application roots and source paths are explicit, bounded, ordinary contained
filesystem entries. Stream syntax, path escape, alternate-stream syntax,
symlink or reparse escape, unstable reads, malformed JSON, duplicate keys, BOM,
NUL, invalid UTF-8, trailing content, and excessive nesting fail closed.
Package-owned diagnostics expose only fixed Safe fields. Native exception trace
APIs remain native and are not claimed to be sanitized.

Phase 8C introduces no artifact publication or loader, generated PHP, handler
binding, registry, discovery, dispatcher, executor, concrete operation,
presenter, renderer, help system, process adapter, executable, or framework
command. The official future executable remains exactly `kongo`, but it is not
implemented. Phase 8D has not begun.

## Rejected alternatives

- Symfony Console or another parser/runtime as a production dependency.
- Mutable command registries, runtime scans, Reflection discovery, or implicit
  command contribution.
- Handler class names, service identifiers, callables, or executable behavior
  in the source or artifact.
- Raw sensitive command-line values or treating a sensitivity label as secure
  transport.
- Implicit mutation defaults or permitting both plan and apply.
- Freshness based on selected fields, embedded digests alone, timestamps, or a
  cache-backed claim.
- Publication, loading, execution, or presentation in the build package.

## Consequences

The production topology is now exactly thirty-two acyclic packages. Console can
deterministically normalize explicit tokens against an immutable specification
set, and Console Build can produce and validate exact-current definition
evidence. Existing Application Graph, Composition, Kernel, HTTP, database,
persistence, developer-intelligence, telemetry, deployment, release, and PHP
compatibility boundaries are unchanged.

## Related authorities

- [ADR 0012: Structured CLI Contract](0012-structured-cli-contract.md)
- [ADR 0096: Explicit Structured CLI and Automation Boundary](0096-explicit-structured-cli-and-automation-boundary.md)
- [ADR 0097: Deterministic Command Lifecycle and Presentation Separation](0097-deterministic-command-lifecycle-and-presentation-separation.md)
- [ADR 0098: Neutral Structured CLI Contracts and Package Boundary](0098-neutral-structured-cli-contracts-and-package-boundary.md)
- [Structured CLI architecture](../architecture/structured-cli.md)
- [Phase 8 roadmap](../architecture/phase-8-roadmap.md)
- [`kongono/console` package guide](../../packages/console/README.md)
- [`kongono/console-build` package guide](../../packages/console-build/README.md)
