# Kongono Console

## Purpose and current status

`kongono/console` is the Experimental presentation-neutral contract package for
Kongono's Structured CLI. Phase 8B implements the neutral command,
invocation, result, lifecycle, diagnostic, and exception foundation. Phase 8C
adds immutable input definitions, command specifications and specification
sets, an accepted/rejected parse result, and a pure parser for already-tokenized
caller input. Phase 8D adds the neutral `CommandCleanup` collaborator and
extends `CommandOperation` with explicit deferred cleanup registration. Phase
8E adds bounded immutable structured result records with exact scalar kinds,
ordinal field names, and Safe or Protected disclosure evidence.

The parser accepts one exact command token, ordinary `--name=value` tokens, and
the reserved `--plan`, `--apply`, and `--no-interaction` tokens within fixed
bounds. Mutation-capable commands require exactly one of plan or apply.
Sensitive values are rejected from raw tokens and may arrive only through the
separate explicit sensitive-input carrier. Accepted invocation inputs use
specification order; rejections expose only neutral nondisclosing failure
evidence.

It is not a command runtime. Definition source/model/artifact compilation lives
in the separate `kongono/console-build` package, while the concrete Phase 8D
operation and dispatcher live in `kongono/console-runtime`; deterministic
presentation lives in the separate upper `kongono/console-presentation`
package. Console contains no mutable registry, discovery, concrete operation,
presenter, process adapter, executable, or framework command. Phase 8F's exact
read-only framework handlers live in the separate upper
`kongono/console-commands` capability package. Phase 8G's separate upper
`kongono/console-application` package owns the official one-shot `kongo`
executable and exactly `application:build`; Console itself still owns no
process or mutation authority.

## Dependency direction

Console depends only on API Metadata, Diagnostics, and the current PHP 8.5
minor. Console Build, Console Runtime, Console Presentation, and Console
Commands depend inward on Console without giving Console outward authority.

```text
api-metadata <- diagnostics
       ^            ^
       +-- console -+
```

Capability adapters may depend inward on these neutral contracts.
Console does not depend outward on Kernel, Dependency Injection, Application
Graph, Composition, HTTP, Routing, Web Runtime, PHP CGI, database, persistence,
developer-intelligence, telemetry, extensions, applications, Symfony Console,
or another third-party runtime package.

## Value construction

Values are constructed only after an outer parser or adapter has normalized
input. This example instantiates neutral values; it does not parse tokens or
execute a command.

```php
use Kongono\Console\CommandDefinition;
use Kongono\Console\CommandExecutionIntent;
use Kongono\Console\CommandIdentifier;
use Kongono\Console\CommandInput;
use Kongono\Console\CommandInputName;
use Kongono\Console\CommandInputSensitivity;
use Kongono\Console\CommandInputValue;
use Kongono\Console\CommandInteractionMode;
use Kongono\Console\CommandInteractionPolicy;
use Kongono\Console\CommandInvocation;
use Kongono\Console\CommandMutationPolicy;
use Kongono\Console\CommandSummary;

$definition = new CommandDefinition(
    new CommandIdentifier('graph:inspect'),
    new CommandSummary('Inspect the current application graph.'),
    CommandInteractionPolicy::None,
    CommandMutationPolicy::ReadOnly,
);

$invocation = new CommandInvocation(
    $definition,
    [
        new CommandInput(
            new CommandInputName('format'),
            new CommandInputValue('summary'),
            CommandInputSensitivity::Ordinary,
        ),
    ],
    CommandExecutionIntent::Observe,
    CommandInteractionMode::Disabled,
);
```

`CommandInputValue` is intentionally opaque and requires explicit `reveal()`.
It has no string, JSON, array, iteration, debug-export, source-carrier, or
redaction-rendering API. Sensitivity is evidence for later policy and does not
make an ordinary process argument secure.

## Bounds and failure

- Command identifiers use 1–128 canonical ASCII bytes.
- Summaries use 1–160 trimmed valid UTF-8 bytes without C0/C1 controls.
- Input names use 1–64 canonical ASCII bytes without `-` or `--` prefixes.
- Input values use at most 8,192 valid UTF-8 bytes without C0/C1 controls.
- Invocations contain at most 64 ordered, detached, uniquely named inputs.
- Failure identifiers use 1–96 canonical ASCII bytes.
- Result records contain 1–64 uniquely named fields in ordinal name order.
- Result text uses at most 8,192 valid UTF-8 bytes without controls; labels use
  1–80 already-trimmed valid UTF-8 bytes.

Invalid values throw the package validation exception without retaining or
presenting rejected material. Neutral failures retain only a category and
diagnostic identifier. Results never render, serialize, select a stream, map an
exit status, or retain raw cause material.

## Lifecycle and extension contract

`CommandCancellation`, `CommandCleanup`, `CommandOperation`, `CommandHandler`,
and `CommandResultData` remain neutral interfaces. Console Runtime implements
only the operation and dispatcher lifecycle. Callers still construct explicit
handlers, cancellation collaborators, and cleanup collaborators; no container,
presenter, stream, or process behavior is supplied.

Handlers receive a `CommandOperation` and return a `CommandResult`. Their own
explicit construction supplies collaborators. They do not receive a resolver,
container, presenter, streams, globals, registry, or process adapter.

## Security and nondisclosure

The package reads no process arguments, globals, environment, streams, current
directory, terminal, signals, clocks, randomness, credentials, registry,
filesystem, or network. It performs no I/O or mutation.

The five Console diagnostics expose only Safe bounded package, failure-category,
and remediation fields. Rejected input, raw exception messages, causes, stacks,
provider output, paths, SQL, credentials, and arbitrary context are excluded.
Safe stringification returns only diagnostic identity and title.

## Stability and absent later capabilities

The package exception marker is Public. The diagnostic catalog and abstract
exception base are Internal. Every other declaration is Experimental. No
Console surface is Stable.

Presentation, help, streams, exit statuses, terminal behavior, JSON or media
schemas, `kongo`, and framework commands belong to later separately
authenticated phases and are absent. Parsing, definition evidence, and the
separate execution lifecycle are implemented only in their owning packages.

## Related authorities

- [ADR 0098](../../docs/adr/0098-neutral-structured-cli-contracts-and-package-boundary.md)
- [ADR 0100](../../docs/adr/0100-operation-scoped-command-execution-and-cleanup.md)
- [ADR 0101](../../docs/adr/0101-deterministic-command-presentation-and-process-adaptation.md)
- [ADR 0103](../../docs/adr/0103-supported-kongo-edge-and-honest-application-build.md)
- [Structured CLI architecture](../../docs/architecture/structured-cli.md)
- [Phase 8 roadmap](../../docs/architecture/phase-8-roadmap.md)
