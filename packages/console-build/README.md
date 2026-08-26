# Kongono Console Build

## Purpose and current status

`kongono/console-build` is the Experimental build-time package for Phase 8C
command-definition evidence. It reads one explicit application-owned
`kongono.commands.json`, compiles definitions through the invariant-owning
`kongono/console` values, and returns one canonical provenance artifact.

The package is inert. It publishes or loads no artifact, discovers no command,
binds no handler, executes no behavior, presents no result, and creates no
executable or framework command.

## Source, model, and artifact

The version-1 source contains only `schema`, `version`, and a bounded `commands`
list. Commands declare the exact identifier, summary, interaction policy,
mutation policy, and named input definitions. Inputs declare only name,
required state, and sensitivity. Handler names, services, aliases, defaults,
prompts, environment variables, destinations, and secret values are excluded.

Command and input array order is not semantic. Compilation sorts both by
ordinal bytes and constructs every definition through Console value objects.
The canonical artifact binds the normalized source identity, canonical model,
and the closed 16-file builder implementation identity. Structural validation
rehydrates through the same invariant owners; current validation performs a
fresh complete build and compares all canonical artifact bytes.

## Security and file safety

The builder accepts one contained ordinary source path with the exact basename,
enforces byte and JSON bounds, rejects duplicate keys, stream syntax, path
escape, symlink or reparse aliases, BOM, NUL, invalid UTF-8, and input changed
during reading. Diagnostics expose only fixed Safe schema, category, and
current-state fields. Rejected JSON, command identities, input names, values,
host paths, and secrets are not retained or stringified.

## Lifecycle and absent behavior

Build and validation are synchronous, stateless, and perform no network or
process I/O. Only source reads occur. There is no cache, publication directory,
mutable current pointer, runtime registry, generated PHP, Reflection discovery,
Composition integration, dispatcher, presenter, help system, `kongo`, or Phase
8D capability.

## Related authorities

Phase 8G uses this package only at development/build time to compile and
validate Console Application's exact one-command definition. Runtime
`kongono/console-application` consumes the checked definition through its
factory and does not scan or compile definitions dynamically.

- [ADR 0099](../../docs/adr/0099-deterministic-command-parsing-and-definition-evidence.md)
- [ADR 0103](../../docs/adr/0103-supported-kongo-edge-and-honest-application-build.md)
- [Structured CLI architecture](../../docs/architecture/structured-cli.md)
- [Phase 8 roadmap](../../docs/architecture/phase-8-roadmap.md)
