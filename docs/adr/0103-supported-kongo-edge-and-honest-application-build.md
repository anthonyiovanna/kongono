# ADR 0103: Supported `kongo` Edge and Honest Application Build

## Status

Accepted and implemented by Phase 8G.

## Context

Phases 8B through 8F established neutral command contracts, deterministic
definition evidence, operation-scoped execution, presentation, and detached
read-only capability handlers. They deliberately provided no supported native
process edge and no mutation-capable command. Kongono now needs one bounded
executable workflow that can build current Composition output without making a
dry run mutate state or exposing unrelated framework commands through invented
application snapshots.

## Decision

Phase 8G adds the Experimental `kongono/console-application` upper package and
the official extensionless Composer binary `kongo`. The supported application
contains exactly one command, `application:build`. Its deterministic definition
is package-owned Console Build evidence, and its factory binds one explicit
handler through Console Runtime and Console Presentation.

`application:build` requires exactly one of `--plan` or `--apply`, plus explicit
application-root and output-directory inputs. `--plan` performs the complete
read, validation, generation, containment, and current-output assessment in
memory and returns the predicted content-addressed basenames and SHA-256
identities without creating, deleting, renaming, touching, or rewriting any
path. `--apply` is the sole mutation authority. It repeats planning, publishes
the Composition artifact and generated wiring through their existing atomic,
content-addressed, initial-only, no-clobber publishers, then replans and proves
that the exact predicted outputs are current.

The one-shot process adapter accepts only the closed help and execution grammar.
It reads process globals and exits only in `bin/kongo`; production collaborators
receive explicit argument and stream values. Human and canonical machine forms
derive from the same neutral result/help projection and exit map. A selected
output port receives one completed document in one write, with no retry after
an output failure. No prompt exists, and `--no-interaction` is accepted as an
automation-safe assertion rather than an interaction mechanism.

The application reports only bounded Safe fields. It does not disclose raw
input, application paths, source bytes, exception text, secrets, ANSI control
data, banners, or debugging output. Expected workflow failures become a bounded
Precondition result; invalid command values become InvalidInput; unexpected
failures become Infrastructure.

Phase 8F snapshot handlers remain available programmatically. The supported
application does not expose them because it has no truthful application-owned
snapshot inputs and will not fabricate defaults.

## Consequences

The production topology has thirty-six acyclic packages. Console Application is
an upper leaf over Console, Console Runtime, Console Presentation, Composition,
API Metadata, and Diagnostics. Console Build is a development-only dependency
used to compile and validate the package-owned command definition. No lower
production package acquires a dependency on Console Application.

Phase 8G adds no alias, second command, daemon, watcher, interactive shell,
installer, package manager, deployment manager, provider selection, runtime
discovery, service locator, or fabricated capability snapshot. Phase 8H remains
planned and owns broader external acceptance and checkpointing.
