# ADR 0026: Actionable Diagnostics and Error Boundaries

## Status

Accepted

## Date

2026-08-13

## Context

Kongono has three separate Public package exception markers and deliberate
failures for Kernel lifecycle, Application Graph structure, and Dependency
Injection definition or resolution errors. Phase 0M now gives those exceptions
package-local identifier, title, and remediation accessors. Without an explicit
doctrine, later renderers, CLI output, logging,
telemetry, and support material could assign incompatible identities, obscure
causes, duplicate recording, or disclose protected information.

Developers must eventually be able to answer four questions for every deliberate
Kongono failure:

```text
What failed?
Why did it fail?
Where did it fail?
What can the developer do next?
```

Phase 0L reserved diagnostic identities and boundary behavior. Phase 0M
implements only package-local runtime ID, title, and remediation metadata; it
does not select a renderer, transport, logger, telemetry integration, retry
mechanism, cleanup mechanism, or generic result abstraction.

## Decision

### Failure taxonomy

Kongono distinguishes four categories:

1. **Deliberate framework failures.** Kongono raises these because a caller
   violated a framework invariant, supplied invalid framework structure or
   configuration, requested an unavailable framework capability, or attempted
   an invalid lifecycle action. The owning package marker remains the catch and
   recovery boundary. Such failures must eventually carry one stable diagnostic
   identifier and actionable content. Configuration, structural, and lifecycle
   misuse normally fails fast and is not presumed retryable. Existing
   pre-mutation validation guarantees remain intact.
2. **Expected application outcomes.** Invalid user input, authorization denial,
   business-rule rejection, optimistic conflict, and similar ordinary decisions
   are not automatically exceptions. A focused typed outcome should be preferred
   when an unsuccessful result is expected control flow. This decision selects
   no framework-wide `Result`, `Either`, `Outcome`, validation library, error
   union, or transport representation.
3. **Infrastructure and external failures.** Filesystem, network, database,
   process, provider, protocol, and similar failures belong to the capability or
   adapter that can add semantic meaning. Preserve the original `Throwable`
   unless translation adds a real owning-layer meaning. Semantic wrapping keeps
   the original as `previous`. Provider detail may be retained in protected
   diagnostics but must not be exposed directly to an untrusted surface. Retry
   is an explicit boundary policy, never an inference from a broad superclass.
4. **Engine and programmer failures.** PHP `Error`, `TypeError`, assertion or
   invariant defects, and unknown programmer failures are not business outcomes
   and must not become validation results or reassuring public errors. Core code
   may catch `Throwable` only at a true outer runtime boundary or temporarily to
   guarantee cleanup before rethrow.

Cancellation, worker stop, timeout, and other control signals require explicit
future contracts. Their types, propagation, cleanup, and reporting semantics
remain open and must not be inferred from ordinary error policy.

The Public exception markers remain separate:

- `Kongono\Kernel\Exception\ExceptionInterface`
- `Kongono\ApplicationGraph\Exception\ExceptionInterface`
- `Kongono\DependencyInjection\Exception\ExceptionInterface`

Phase 0M creates no universal Kongono exception or recovery marker. Native
engine, autoloader, consumer, and provider failures are not wrapped merely to
enter a Kongono family.

### Diagnostic identifier contract

Reserve the exact case-sensitive ASCII syntax:

```text
KGN-<DOMAIN>-<NNNN>
```

`KGN` is fixed. `DOMAIN` is an approved uppercase ASCII owner token. `NNNN` is
exactly four decimal digits from `1000` through `9999`. Identifiers compare
byte-for-byte. They contain no whitespace, underscore, localization, version,
environment, HTTP status, severity, timestamp, or instance identifier.

An assigned identifier is never reused for another semantic reason. Removal or
replacement leaves it reserved. Wording and remediation may improve without
changing semantic identity. A semantic split receives new identifiers; the same
deliberate reason at multiple throw sites may share one identifier. Consumers
must not parse message prose to find an identifier.

Diagnostic identifiers are not PHP exception codes and must not be stored in or
inferred from `Exception::getCode()`. They are also not Application Graph IDs,
Documentation IDs, operation IDs, trace IDs, request IDs, incident references,
database keys, URLs, or transport error codes.

The current reserved owner domains are:

```text
KERNEL  Kongono Kernel lifecycle ownership
GRAPH   Kongono Application Graph structural validation
DI      Kongono Dependency Injection definition and resolution
```

A future domain requires an explicit owning package or capability and documented
allocation. Speculative domains are prohibited.

#### Reserved Kernel catalog

| Enum case | Identifier | Failure reason | Required remediation direction |
| --- | --- | --- | --- |
| `BootWhenNotUnbooted` | `KGN-KERNEL-1001` | Boot requested when the Kernel is not `Unbooted` | Boot each Kernel instance exactly once from `Unbooted` |
| `ShutdownWhenNotBooted` | `KGN-KERNEL-1002` | Shutdown requested when the Kernel is not `Booted` | Shut down only a booted, non-shutdown Kernel |
| `ShutdownWithActiveOperations` | `KGN-KERNEL-1003` | Shutdown requested while operations remain active | End every operation owned by that Kernel before shutdown |
| `BeginOperationWhenNotBooted` | `KGN-KERNEL-1004` | Operation begin requested when the Kernel is not `Booted` | Boot first and do not begin work after shutdown |
| `EndOperationWhenNotBooted` | `KGN-KERNEL-1005` | Operation end requested when the Kernel is not `Booted` | End active scopes while their owning Kernel is booted |
| `InactiveOrForeignOperationScope` | `KGN-KERNEL-1006` | Operation scope is inactive, already ended, or owned by another Kernel | Pass the exact active scope to the Kernel instance that created it |

#### Reserved Application Graph catalog

| Enum case | Identifier | Failure reason | Required remediation direction |
| --- | --- | --- | --- |
| `EmptyNodeKey` | `KGN-GRAPH-1001` | Empty node-key value | Supply a non-empty opaque graph-local key |
| `EmptyNodeKind` | `KGN-GRAPH-1002` | Empty node-kind value | Supply a non-empty open node-kind label |
| `EmptyRelationKind` | `KGN-GRAPH-1003` | Empty relation-kind value | Supply a non-empty open relation-kind label |
| `NodesNotList` | `KGN-GRAPH-1004` | Nodes input is not a zero-based consecutive list | Reindex the node array as a PHP list |
| `RelationsNotList` | `KGN-GRAPH-1005` | Relations input is not a zero-based consecutive list | Reindex the relation array as a PHP list |
| `InvalidNodeEntry` | `KGN-GRAPH-1006` | A nodes-list entry is not a `Node` | Replace the reported entry with a valid `Node` |
| `InvalidRelationEntry` | `KGN-GRAPH-1007` | A relations-list entry is not a `Relation` | Replace the reported entry with a valid `Relation` |
| `DuplicateNodeKey` | `KGN-GRAPH-1008` | Duplicate node-key value | Remove or rename the duplicate within that snapshot |
| `MissingRelationSource` | `KGN-GRAPH-1009` | Relation source does not reference an existing node | Add the source node or correct the source key |
| `MissingRelationTarget` | `KGN-GRAPH-1010` | Relation target does not reference an existing node | Add the target node or correct the target key |
| `DuplicateRelationTriple` | `KGN-GRAPH-1011` | Duplicate source/kind/target relation triple | Remove the duplicate or use a different relation kind when semantically distinct |

#### Reserved Dependency Injection catalog

| Enum case | Identifier | Failure reason | Required remediation direction |
| --- | --- | --- | --- |
| `ContractDoesNotExist` | `KGN-DI-1001` | Service or dependency contract does not name an existing interface | Supply the exact declared interface name and ensure it can autoload |
| `ContractNotCanonical` | `KGN-DI-1002` | Service or dependency contract is not canonical | Use the declaration's exact case and spelling without a leading slash or alias |
| `ResolverContractForbidden` | `KGN-DI-1003` | Resolver contract or subinterface is used as a service/dependency contract | Remove resolver injection and keep resolution at the composition root |
| `ImplementationDoesNotExist` | `KGN-DI-1004` | Implementation does not name an existing class | Supply the exact autoloadable implementation class |
| `ImplementationNotCanonical` | `KGN-DI-1005` | Implementation class name is not canonical | Use the declaration's exact class name without alias or case variation |
| `ImplementationNotInstantiable` | `KGN-DI-1006` | Implementation is not publicly instantiable | Use a named, concrete, publicly instantiable class |
| `ImplementationDoesNotSatisfyContract` | `KGN-DI-1007` | Implementation does not implement the declared service contract | Select an implementation satisfying that exact interface |
| `ImplementationExposesResolver` | `KGN-DI-1008` | Implementation exposes the Kongono resolver contract | Remove resolver implementation/injection from the service graph |
| `DependenciesNotList` | `KGN-DI-1009` | Dependencies input is not a zero-based consecutive list | Reindex dependencies as a PHP list |
| `DependencyEntryNotString` | `KGN-DI-1010` | Dependency-list entry is not a canonical-name string | Supply an interface-name string at the reported position |
| `DependenciesWithoutConstructor` | `KGN-DI-1011` | Implementation has no constructor but declares dependencies | Remove the dependency entries or define a matching public constructor |
| `ConstructorNotPublic` | `KGN-DI-1012` | Implementation constructor is not public | Make the constructor public or choose another implementation |
| `ConstructorArityMismatch` | `KGN-DI-1013` | Constructor parameter count differs from dependency count | Make the ordered definition and constructor arity identical |
| `UnsupportedConstructorParameterMode` | `KGN-DI-1014` | Constructor parameter is optional, defaulted, variadic, or by-reference | Use a required, non-defaulted, non-variadic, by-value parameter |
| `UnsupportedConstructorParameterType` | `KGN-DI-1015` | Constructor parameter lacks one non-nullable interface type | Use one non-nullable named interface type |
| `ConstructorDependencyTypeMismatch` | `KGN-DI-1016` | Constructor parameter type differs from the dependency at that position | Align the exact interface type and ordered dependency |
| `DefinitionsNotList` | `KGN-DI-1017` | Definitions input is not a zero-based consecutive list | Reindex definitions as a PHP list |
| `InvalidDefinitionEntry` | `KGN-DI-1018` | Definition-list entry is not a `ServiceDefinition` | Replace the reported entry with a validated definition |
| `DuplicateContractDefinition` | `KGN-DI-1019` | More than one definition uses the same contract | Retain exactly one definition for that contract |
| `MissingDependencyDefinition` | `KGN-DI-1020` | A declared dependency has no definition | Add the missing contract definition or remove the dependency |
| `DependencyCycle` | `KGN-DI-1021` | Definition graph contains a dependency cycle | Break the reported cycle through explicit design changes |
| `RequestedContractDoesNotExist` | `KGN-DI-1022` | Requested service name does not resolve to an existing interface | Request the exact autoloadable interface name |
| `RequestedContractUnsupported` | `KGN-DI-1023` | Requested interface is noncanonical or is the resolver contract | Use the exact supported service interface and never resolve the resolver itself |
| `RequestedContractUndefined` | `KGN-DI-1024` | No definition exists for the requested service contract | Register an explicit definition at the composition root |
| `ConstructedContractInvariantViolation` | `KGN-DI-1025` | Constructed object violates a previously validated service contract | Treat this as a framework invariant failure and inspect the definition/implementation boundary |

These 42 assignments are implemented by three Internal package catalogs and are
accessible as strings through each package's Public exception marker. No Public
accessor exposes the Internal enum, and no broader structured context exists.

### Actionable content and disclosure

Every future deliberate diagnostic must provide directly, or through a
documented version-matched registry:

1. a stable diagnostic identifier;
2. a precise plain-language title;
3. the operation that failed;
4. the exact violated invariant, expectation, or state;
5. safe actual-versus-expected information when available;
6. a safe known location, input position, symbol, or owning component;
7. one short actionable remediation direction;
8. the original exception chain after semantic wrapping;
9. an owning package or capability; and
10. a version-matched documentation location.

Deliberate messages that say only `Invalid value`, `Invalid configuration`,
`Operation failed`, `Unexpected error`, `Something went wrong`, or `Unable to
continue` are prohibited. Such phrases are acceptable only when followed by the
exact cause and remediation.

Precision never authorizes raw sensitive or unbounded input. Prefer a field
name, type, position, count, state, or safely represented identifier. Displayed
values must be quoted and escaped. Unbounded values must be truncated with an
explicit marker. Credentials, tokens, cookies, authorization headers, session
values, encryption material, private keys, full environment dumps, and arbitrary
request bodies must never appear. Graph keys, service names, routes, SQL values,
file paths, and provider messages are not presumed safe for untrusted output.
Richer protected diagnostics may be retained internally but are rendered only
under an explicit disclosure policy. Exact context and redaction APIs remain
open; this decision does not authorize an arbitrary diagnostic context bag.

### Catching, wrapping, recording, retry, and cleanup

- Catch a package marker only when the caller can add recovery, translation, or
  boundary behavior. Do not catch merely to rethrow the same failure or hide its
  origin.
- Catch `Throwable` broadly only at a true top-level runtime boundary or
  temporarily to guarantee cleanup before rethrow.
- Wrap only when crossing into an owning semantic layer that adds meaningful
  context. Preserve the original as `previous`; never discard the chain or wrap
  merely to force a native, autoloader, consumer, or provider failure into a
  Kongono family.
- Exceptions do not log themselves. One outer boundary owns final recording so
  one failure does not create duplicate log entries. OpenTelemetry remains the
  intended future instrumentation model, but Phase 0M adds no telemetry.
- A diagnostic identifier may correlate protected records later, but it is not
  an incident or trace identifier.
- Retry is never implied by `RuntimeException`, a package marker, an HTTP status,
  or a diagnostic-number range. Future retry policy must be explicit, bounded,
  operation-aware, and safe for idempotency. Framework misuse, invalid
  configuration or structure, and invariant defects are not presumed retryable.
- Every future operation boundary must guarantee scope and resource cleanup when
  application work throws. Successful cleanup must not replace the primary
  failure. Cleanup failure must not be silently discarded. A runtime whose state
  is uncertain after cleanup must not quietly accept more work. Representation
  of simultaneous primary and cleanup failures remains open.

Phase 0M changes no Kernel, operation-scope, runtime, or cleanup behavior and
does not claim cleanup orchestration exists.

### Future development and production experience

A future development renderer should present, in this order:

```text
Diagnostic ID and precise title
What happened
Why it happened
How to fix it
Relevant application location and safely escaped source excerpt
Exception/cause chain
Operation and request context when available
Application frames emphasized
Framework and vendor frames collapsible
Sanitized copyable Markdown or JSON diagnostic report
Version-matched local documentation reference
```

It must be clean, readable, accessible, useful without mandatory client-side
JavaScript, deterministic on the server, escaped and bounded, and safe against
secret disclosure even in development. It must not require a remote call, AI
request, or internet lookup to explain a known diagnostic. Framework/version
information may appear; full environment dumps may not. Developer mode comes
from trusted server configuration, never a request header, query parameter,
cookie, or other client-controlled value.

Future production surfaces expose only an intentionally generic response
appropriate to the transport and, when protected recording exists, an opaque
incident reference. They must not expose unapproved diagnostic detail,
exception class or message, stack traces, source excerpts, absolute paths,
environment or configuration values, credentials, cookies, headers, request
bodies, SQL, provider payloads, or internal topology. The incident reference is
distinct from the stable diagnostic identifier.

The development renderer and production surface are accepted requirements, not
implemented capabilities. No renderer, universal diagnostic API, transport
mapping, incident storage, or error page exists in Phase 0M.

## Rationale

Stable package-owned identifiers make one semantic failure searchable,
testable, documentable, and recognizable without turning mutable message prose
into a machine contract. Separating expected outcomes, deliberate framework
failures, infrastructure failures, and programmer failures prevents validation,
recovery, retry, and production disclosure from collapsing into one unsafe
policy. A protected diagnostic model and a generic production surface preserve
developer usefulness without treating debug detail as public data.

Reserving the catalog before runtime implementation allowed Phase 0M to select
the smallest package-local string-accessor API around known semantic requirements
without exposing a premature universal abstraction.

## Consequences

Future deliberate Kongono failures require stable identifiers, reason-specific
actionable content, owning-package attribution, secure disclosure, and tests.
Package markers remain the recovery boundaries. Message prose may improve, but
tooling may not parse it as identity. Later runtime, renderer, CLI, logging,
telemetry, retry, and cleanup work must preserve cause chains, record once at an
outer boundary, and distinguish development detail from production output.

Phase 0M adds only package-local Internal catalogs, immutable catalog ownership
in existing Internal abstract bases, and three string accessors on each existing
Public marker. Existing deliberate messages, package boundaries, native failure
propagation, and valid-state guarantees remain unchanged.

## Rejected alternatives

- Vague prose without stable identifiers is rejected because it is not reliably
  searchable, testable, or supportable.
- Parsing exception messages as a machine contract is rejected because wording
  must remain improvable.
- Using `Exception::getCode()` as the diagnostic registry is rejected because
  PHP exception codes do not provide the required typed identity contract.
- One universal Kongono exception family now is rejected because it would erase
  package ownership and create premature compatibility obligations.
- Wrapping every `Throwable` is rejected because it hides native ownership and
  creates meaningless framework classification.
- Exceptions that log or render themselves are rejected because they duplicate
  recording and couple failures to presentation or infrastructure.
- Automatic retry inferred from hierarchy or number range is rejected because
  retry depends on operation semantics and idempotency.
- Exposing development diagnostics in production is rejected because protected
  details are not public merely because they help a developer.
- Request-controlled debug mode is rejected because an untrusted client must not
  expand disclosure.
- A framework-wide `Result` abstraction in this phase is rejected because
  expected outcome types require capability-specific evidence and design.
- Implementing the error page before a transport boundary exists is rejected
  because renderer and transport ownership remain unresolved.

## What remains open

Safe structured context and redaction types; renderer and package ownership;
HTTP and problem-detail mapping; CLI
output and exit codes; incident storage; logging and OpenTelemetry integration;
documentation URL and version-selection mechanism; localization; source-excerpt
provider; retry and cancellation types; and representation of simultaneous
primary and cleanup failures remain open. No public API or implementation for
those mechanisms is authorized by this decision.
