# Kongono Console Commands

`kongono/console-commands` is the Experimental Phase 8F upper capability-adapter
package. It binds one exact deterministic definition set to three read-only
handlers without owning parsing, presentation, process adaptation, or an
application workflow.

Use this package when an already-composed application needs neutral structured
results for an explicitly injected immutable Application Graph, Composition
artifact, or Hantei report. Definitions must first be compiled and validated by
Console Build and passed to `ReadOnlyFrameworkCommandFamily`.

Do not use it to run Hantei, build artifacts, inspect files, discover framework
state, mutate an application, or adapt native process input/output. The handlers
perform no I/O and retain no invocation state. Graph output contains counts
only; Composition output contains hashes only; Hantei output contains verdict
and outcome counts only. A `FAIL` or `BLOCKED` Hantei verdict remains successful
inspection data.

The family validates all identifiers, summaries, policies, empty input sets,
count, and canonical order before it retains handlers. Invalid direct
invocations return the bounded `KGN-CONSOLE-COMMANDS-1002` InvalidInput result
without disclosing rejected values.

The package is an upper leaf over Console, Console Runtime, Application Graph,
Composition, and Hantei. No lower package depends on it. Generated Composition
wiring may construct the family from explicit typed object inputs. Phase 8G's
separate Console Application implements `kongo` with exactly
`application:build`; it does not expose these handlers because it has no
truthful application-owned snapshots and does not fabricate defaults.

Related decisions:

- [ADR 0096](../../docs/adr/0096-explicit-structured-cli-and-automation-boundary.md)
- [ADR 0099](../../docs/adr/0099-deterministic-command-parsing-and-definition-evidence.md)
- [ADR 0100](../../docs/adr/0100-operation-scoped-command-execution-and-cleanup.md)
- [ADR 0101](../../docs/adr/0101-deterministic-command-presentation-and-process-adaptation.md)
- [ADR 0102](../../docs/adr/0102-first-read-only-framework-capability-commands.md)
- [ADR 0103](../../docs/adr/0103-supported-kongo-edge-and-honest-application-build.md)
