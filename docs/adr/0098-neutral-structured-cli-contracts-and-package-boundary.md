# ADR 0098: Neutral Structured CLI Contracts and Package Boundary

## Status

Accepted

## Date

2026-08-26

## Context

Phase 8A accepted the Structured CLI doctrine, command-lifecycle separation,
automation and nondisclosure rules, and the fixed A-through-H roadmap. It added
no implementation. Phase 8B needs the smallest typed vocabulary that later
parsing, execution, and presentation layers can share without giving those
later capabilities authority prematurely.

## Decision

### Package and dependency direction

Phase 8B adds the Experimental `kongono/console` package under
`Kongono\Console\`. It depends only on `kongono/api-metadata`,
`kongono/diagnostics`, and PHP `>=8.5.0 <8.6.0`. No existing production package
depends on Console. The root consumes it for framework distribution and tests.

```text
api-metadata <- diagnostics
       ^            ^
       +-- console -+
```

Console remains neutral and below every future command capability adapter. It
does not depend on Kernel, Dependency Injection, Application Graph,
Composition, HTTP, Routing, Web Runtime, PHP CGI, database, persistence,
developer-intelligence, telemetry, extensions, application code, Symfony
Console, or another third-party runtime package.

### Immutable definition and input values

`CommandIdentifier` accepts 1–128 ASCII bytes in canonical colon-separated and
hyphenated form. `CommandSummary` accepts 1–160 already-trimmed valid UTF-8
bytes without C0/C1 controls. `CommandDefinition` combines those values with
explicit interaction and mutation policies and owns no parser schema, handler,
registry position, alias, help text, service, or process behavior.

Inputs are already normalized before construction. `CommandInputName` accepts
1–64 canonical ASCII bytes without token prefixes. `CommandInputValue` is an
opaque valid UTF-8 value of at most 8,192 bytes with no C0/C1 controls and can
be accessed only through `reveal()`. Sensitivity is explicit evidence for a
later policy; it is not a secure argument carrier or presentation contract.

### Invocation, intent, and lifecycle

`CommandInvocation` stores one definition, at most 64 ordered detached unique
named inputs, explicit observe/plan/apply intent, and explicit disabled or
permitted interaction mode. Read-only definitions accept only observation;
mutation-capable definitions accept only planning or application. Commands
with no declared interaction accept only disabled interaction.

`CommandCancellation`, `CommandOperation`, and `CommandHandler` are interfaces
only. `CommandOperation` exposes invocation, cancellation evidence, active or
retired state, and explicit retirement. Phase 8B creates no concrete operation,
cancellation source, handler, cleanup coordinator, dispatcher, or executor.
Detailed cleanup and combined-failure composition remain Phase 8D work.

### Outcomes and bounded failure evidence

One `CommandResult` has a succeeded, rejected, failed, or cancelled outcome.
Success may carry only typed `CommandResultData`. Rejection accepts invalid
input, precondition, or denial; failure accepts capability or infrastructure;
cancellation accepts only cancellation. Non-success results carry no data.

`CommandFailure` retains only one category and one 1–96 byte canonical
diagnostic identifier. It stores no message, cause, stack, input, provider
output, path, SQL, credential, or arbitrary context. Results do not render,
serialize, choose streams, or map exit statuses.

### Diagnostics, exceptions, and API status

Console owns exactly five Internal diagnostic identifiers,
`KGN-CONSOLE-1001` through `KGN-CONSOLE-1005`, for validation, definition,
invocation, failure, and lifecycle. Their contexts contain only the Safe
bounded fields `package`, `failure_category`, and `remediation`.

The package-local exception marker is Public. The abstract exception base and
diagnostic catalog are Internal. All other Phase 8B declarations are
Experimental. Exceptions retain no rejected value; safe stringification emits
only diagnostic identity and title and never a native trace.

## Security and lifecycle consequences

Caller input remains untrusted even after structural validation. Sensitive
input must not be logged, serialized, included in diagnostics, or placed in an
ordinary process argument when that would expose it through process listings
or shell history. Sensitivity classification does not itself provide secure
transport or authorization.

Every future invocation owner must retire operation state explicitly and
release invocation-owned references for persistent-runtime isolation. Phase 8B
defines the contract only; it reads no globals, environment, streams, current
directory, terminal, signals, clocks, randomness, credentials, registries,
filesystem, or network.

## Rejected alternatives

- Combining parsing, execution, presentation, and process behavior in one API.
- Runtime discovery, mutable registries, Reflection scanning, service location,
  ambient containers, hidden autowiring, proxies, or `eval`.
- Raw messages, causes, stacks, inputs, paths, SQL, credentials, or provider
  output in neutral failure evidence.
- Stringification, JSON serialization, iteration, array conversion, or debug
  export on opaque input or result-data values.
- A concrete operation, dispatcher, presenter, executable, or framework command
  in the neutral contract phase.
- Symfony Console or another third-party runtime dependency.

## Consequences and prohibited scope

The production topology is now exactly thirty-one acyclic packages. Phase 8A
and Phase 8B are implemented; Phase 8C has not begun.

No token or argv parser, option or argument schema, source/model/artifact,
registry, compiler, discovery mechanism, dispatcher, executor, middleware,
concrete operation, presenter, renderer, help system, stream adapter, exit-code
mapping, process adapter, executable, or framework command exists. The official
future executable remains exactly `kongo`, but it is not implemented.

This decision changes no Application Graph or Composition schema, Kernel
lifecycle, HTTP, database, persistence, developer-intelligence, telemetry,
deployment, release, or Stable API behavior.

## Related authorities

- [ADR 0012: Structured CLI Contract](0012-structured-cli-contract.md)
- [ADR 0096: Explicit Structured CLI and Automation Boundary](0096-explicit-structured-cli-and-automation-boundary.md)
- [ADR 0097: Deterministic Command Lifecycle and Presentation Separation](0097-deterministic-command-lifecycle-and-presentation-separation.md)
- [Structured CLI architecture](../architecture/structured-cli.md)
- [Phase 8 roadmap](../architecture/phase-8-roadmap.md)
- [`kongono/console` package guide](../../packages/console/README.md)
