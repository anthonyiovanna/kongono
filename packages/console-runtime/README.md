# Kongono Console Runtime

## Purpose and current status

`kongono/console-runtime` is the Experimental presentation-neutral Phase 8D
execution boundary. It binds already-constructed explicit handlers to exact
command identifiers and creates one fresh operation for each dispatch.

Callers remain responsible for exact-current command-definition evidence,
parsing, constructing typed handlers and cancellation collaborators, and later
presentation. The runtime neither loads artifacts nor constructs handlers.

## Execution and lifecycle

One immutable handler set is detached, checked for exact unique identifiers,
and sorted by ordinal bytes. A dispatch performs exact lookup, one explicit
pre-execution cancellation check, at most one handler call, and one retirement
path. A handler-returned neutral result is preserved by object identity.

Operations accept at most 64 cleanup collaborators, unique by object identity.
Retirement becomes observable and detaches invocation-owned references before
fallible cleanup. Every cleanup is attempted once in reverse registration order;
repeat retirement is a no-op. Primary and ordered cleanup failure identities are
preserved in one package-owned safe exception.

An instance-local guard rejects reentrant dispatch on the same dispatcher and is
always cleared. Sequential calls create different operation objects and retain
no prior invocation, cancellation, result, cleanup, exception, or operation.
Caller-owned handler state remains the caller's lifecycle responsibility.

## Security and absent behavior

Diagnostics contain only fixed Safe package, category, boolean/count, and
remediation evidence. Exception messages and safe stringification exclude raw
throwable messages, traces, inputs, command identifiers, handler classes, paths,
provider data, SQL, credentials, and environment state.

The package reads no process, stream, environment, directory, terminal, signal,
clock, randomness, credential, registry, filesystem, or network state. It has no
mutable registry, discovery, Reflection lookup, service location, middleware,
retry, transaction, presenter, JSON schema, help, process adapter, executable,
`kongo`, framework command, or presentation behavior. Phase 8G's separate upper
Console Application composes this runtime for one fresh operation per
`application:build` dispatch; Runtime still reads no process state and owns no
publication authority.

## Stability and related authorities

The exception marker and combined execution exception are Public. The diagnostic
catalog and abstract base are Internal. Other declarations are Experimental;
nothing is Stable.

- [ADR 0100](../../docs/adr/0100-operation-scoped-command-execution-and-cleanup.md)
- [ADR 0103](../../docs/adr/0103-supported-kongo-edge-and-honest-application-build.md)
- [Structured CLI architecture](../../docs/architecture/structured-cli.md)
- [Phase 8 roadmap](../../docs/architecture/phase-8-roadmap.md)
