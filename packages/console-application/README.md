# Kongono Console Application

`kongono/console-application` is the Experimental upper application
and native-process boundary for the official `kongo` executable. It exposes
exactly three supported commands: `application:build`, `security:check`, and
`security:inspect`.

Use it for a one-shot Composition build when the caller supplies an explicit
application root and output directory. `--plan` is a non-mutating dry run: it
validates and generates in memory, assesses the exact output paths, and reports
stable predicted basenames and SHA-256 identities. `--apply` grants explicit
mutation authority to publish exactly the Composition artifact and generated
wiring. Publication remains content-addressed, atomic, contained, idempotent
for exact current output, initial-only, and no-clobber.

The security commands accept exactly ordinary application-root and output-
directory inputs. They are read-only, accept no plan/apply switch, and return
only bounded artifact/source identities plus a publication/currentness fact.
They never disclose source text, policy values, or raw paths.

The commands have no prompt. `--no-interaction` is safe for automation and does
not change command semantics. Human and canonical machine output share one
neutral result and exit map, and the selected output stream receives one
completed document in one write. Errors disclose only bounded Safe fields, not
raw values, paths, source text, exception messages, secrets, or debug output.

Only `bin/kongo` reads native process globals or terminates the process. The
application and its collaborators receive explicit tokens and streams, retain
no invocation state between runs, and retire each Console Runtime operation.

Do not use this package as a daemon, watcher, interactive shell, installer,
deployment manager, service locator, or general command registry. Phase 8F's
read-only capability handlers remain programmatic because this application has
no truthful snapshots to supply; it does not expose fabricated defaults. Phase
8H externally proves the direct source and Composer proxy, human/machine parity,
zero-write planning, explicit/idempotent apply, exit-3 conflict no-clobber,
recovery, nondisclosure, and cleanup. The proof is root-owned and adds no
package runtime authority. Phase 9G adds no external acceptance; Phase 9H is
the only next architect-review handoff.

Related decisions:

- [ADR 0096](../../docs/adr/0096-explicit-structured-cli-and-automation-boundary.md)
- [ADR 0099](../../docs/adr/0099-deterministic-command-parsing-and-definition-evidence.md)
- [ADR 0100](../../docs/adr/0100-operation-scoped-command-execution-and-cleanup.md)
- [ADR 0101](../../docs/adr/0101-deterministic-command-presentation-and-process-adaptation.md)
- [ADR 0103](../../docs/adr/0103-supported-kongo-edge-and-honest-application-build.md)
- [ADR 0104](../../docs/adr/0104-phase-8-external-cli-acceptance-and-checkpoint.md)
- [ADR 0112](../../docs/adr/0112-deterministic-security-policy-evidence-and-supported-application-workflow.md)
- [Security application workflow](../../docs/architecture/security-application-workflow.md)
- [Phase 8 external acceptance](../../docs/architecture/phase-8-external-acceptance.md)
