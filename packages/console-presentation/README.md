# Kongono Console Presentation

## Purpose and current status

`kongono/console-presentation` is the Experimental Phase 8E boundary that
renders existing neutral command results and immutable command definitions. It
provides deterministic plain human documents, canonical version-1 JSON, one
shared exit-status map, explicit output ports, and a thin one-write delivery
adapter.

Presentation remains separate from parsing and execution. The package neither
invokes handlers nor reads process, stream, environment, directory, terminal,
signal, clock, randomness, credential, registry, filesystem, or network state.
Phase 8G's separate Console Application uses these presenters and the explicit
output-port contract for the one-shot `kongo` workflow. This package still owns
no native process reads, stream lifecycle, command execution, or publication.
Phase 8F's real framework capability commands remain implemented separately.

## Result and disclosure contract

Human and machine result documents use the same neutral `CommandResult`, exit
map, and bounded `CommandResultRecord`. Safe text, integer, and boolean values
retain their exact scalar meaning. Protected fields are omitted entirely; only
their integer count crosses the boundary. Unknown `CommandResultData`
implementations fail closed without traversal or stringification.

The human success form begins with `Succeeded.` and writes Safe fields in
ordinal field-name order. Failures are one nondisclosing stderr line containing
only the normalized category and diagnostic identifier. Machine result output
uses the canonical `kongono.command-result` version-1 schema and exact media
type documented by ADR 0101.

## Help contract

Human and machine help project the same immutable `CommandSpecification`
values. Version 1 exposes command identifier, summary, interaction and mutation
policies, plus each ordered named input's requirement and sensitivity. The
current neutral model has no positional-input, alias, or repeatability fields,
so Phase 8E invents none. Help never discovers or inspects handlers.

## Output and lifecycle

`CommandPresentationAdapter` writes one already-completed document exactly once
to the selected caller-owned port and returns the exit integer only after the
write succeeds. It does not normalize bytes, retry, flush, close, retain ports,
perform fallback output, call `exit`, or own native streams. Output failure
retains the original throwable as a previous cause behind fixed safe
presentation.

## Dependencies, stability, and absent scope

The package depends only on API Metadata, Console, Diagnostics, and PHP 8.5.
Its exception marker and output exception are Public; helpers and diagnostic
infrastructure are Internal; all other declarations are Experimental.

Terminal discovery, interaction, completion, styling, and paging remain
unimplemented. Phase 8F's three read-only framework capability commands are
implemented in `kongono/console-commands`; this package still neither discovers
nor executes them. Console Application adapts its already-completed document to
one explicit native stream with one write. Phase 8H remains planned.

## Related authorities

- [ADR 0101](../../docs/adr/0101-deterministic-command-presentation-and-process-adaptation.md)
- [ADR 0103](../../docs/adr/0103-supported-kongo-edge-and-honest-application-build.md)
- [Structured CLI architecture](../../docs/architecture/structured-cli.md)
- [Phase 8 roadmap](../../docs/architecture/phase-8-roadmap.md)
