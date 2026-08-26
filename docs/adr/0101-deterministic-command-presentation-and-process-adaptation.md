# ADR 0101: Deterministic Command Presentation and Process Adaptation

## Status

Accepted

## Date

2026-08-26

## Context

Phases 8B through 8D separate immutable command meaning, already-tokenized
parsing, and operation-scoped execution. Phase 8E needs an upper presentation
boundary without granting presenters execution authority or creating the future
`kongo` executable.

## Decision

`kongono/console` owns one bounded immutable `CommandResultRecord` with ordered
text, integer, and boolean fields whose disclosure is Safe or Protected. The
separate Experimental `kongono/console-presentation` package depends only on
API Metadata, Console, Diagnostics, and PHP.

Human and canonical version-1 machine presenters consume the same neutral
result, definition projection, and exact exit-status map. Human success uses
stdout; human failure uses one bounded stderr line. Machine output is one
LF-terminated canonical JSON document on stdout. Protected values are omitted,
not replaced by redaction text; only their count is disclosed. An unsupported
result-data implementation fails closed without traversal or stringification.

Help derives only from immutable command specifications. Version 1 exposes the
current model's identifier, summary, interaction and mutation policies, and
ordered named-input requirement and sensitivity. It invents no positional,
alias, or repeatability field absent from that model and never discovers a
handler.

One immutable presentation contains exactly one non-empty stream document, its
media type, and exit status. A caller-owned output port accepts exact bytes. The
delivery adapter performs one selected-port write, owns neither port, returns
the exit integer only after success, and preserves a failed write as the
previous throwable behind safe package presentation. It is not a process
adapter and reads no native stream or global host state.

## Consequences

The production topology contains thirty-four acyclic packages. Presentation is
deterministic, versioned, bounded, and disclosure-safe while remaining separate
from parsing and execution. No executable, real command, terminal adaptation,
prompt, styling, completion, process integration, application wiring, or Phase
8F behavior exists.

## Rejected alternatives

- Handler printing or presenter-owned execution.
- Machine output derived from terminal prose.
- Redacting Protected values into placeholders that still reveal field facts.
- Runtime discovery, native stream ownership, fallback writes, or `exit`.
- Adding Symfony Console or another runtime console dependency.

## Related authorities

- [ADR 0096](0096-explicit-structured-cli-and-automation-boundary.md)
- [ADR 0097](0097-deterministic-command-lifecycle-and-presentation-separation.md)
- [ADR 0098](0098-neutral-structured-cli-contracts-and-package-boundary.md)
- [ADR 0099](0099-deterministic-command-parsing-and-definition-evidence.md)
- [ADR 0100](0100-operation-scoped-command-execution-and-cleanup.md)
- [Structured CLI guide](../architecture/structured-cli.md)
- [Phase 8 roadmap](../architecture/phase-8-roadmap.md)
