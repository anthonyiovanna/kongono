# Error Handling and Actionable Diagnostics

## Purpose and current status

This guide is the operational source of truth for Kongono failure behavior.
Every deliberate framework failure must say what failed, why, where, and what
the developer can do next while preserving package ownership and safe
disclosure.

Phase 0M implements the smallest runtime subset of the Phase 0L doctrine. Every
current deliberate Kernel, Application Graph, and Dependency Injection failure
exposes its exact package-owned identifier, title, and remediation. Three
Internal backed-enum catalogs own the 42 mappings, and the three Public package
exception markers remain separate recovery boundaries. Existing messages remain
unchanged protected detail. No universal diagnostic interface, safe structured
context, HTTP or CLI mapping, logger, telemetry integration, retry engine,
cleanup runner, incident system, development error page, or production renderer
is implemented.

## Choose the correct failure category

### Deliberate framework failure

Use when Kongono detects an invalid lifecycle action, an explicit invariant
violation, invalid framework structure or configuration, or an unavailable
framework capability. Fail fast where the current contract requires it and
preserve valid state when validation promises to occur before mutation.

Do not use for routine business decisions or to relabel every native failure.
Catch through the owning package marker only when the boundary can recover,
translate, or add meaningful policy.

### Expected application outcome

Use a focused typed outcome when invalid user input, authorization denial,
business-rule rejection, optimistic conflict, or another unsuccessful result is
an expected part of ordinary application flow.

Do not automatically throw merely because the result is unsuccessful. Do not
invent a framework-wide `Result`, `Either`, `Outcome`, error union, validation
library, or transport envelope under this doctrine; none is selected.

### Infrastructure or external failure

Let filesystem, network, database, process, provider, and protocol failures
propagate until an owning capability or adapter can add real semantic meaning.
When translating, keep the original `Throwable` as `previous` and retain useful
provider detail in protected diagnostics.

Do not wrap solely to make the failure look like a Kongono exception. Do not
send provider detail directly to an untrusted surface. Retry must be an explicit
operation policy rather than an inference from a broad exception class.

### Engine or programmer failure

Treat PHP `Error`, `TypeError`, assertion or invariant defects, and unknown
programmer failures as defects, not expected outcomes. A true outer runtime
boundary may catch `Throwable` to record safely; a narrower boundary may catch
temporarily to guarantee cleanup and must then rethrow.

Do not convert such failures into validation results or reassuring public
responses.

Cancellation, worker stop, timeout, and other control signals remain unselected.
Do not assume they are ordinary errors or infer their propagation, cleanup, or
reporting rules.

## Current package exception boundaries

These separate Public markers own deliberate package failures:

- Kernel: `Kongono\Kernel\Exception\ExceptionInterface`
- Application Graph: `Kongono\ApplicationGraph\Exception\ExceptionInterface`
- Dependency Injection:
  `Kongono\DependencyInjection\Exception\ExceptionInterface`

Their abstract bases remain Internal and concrete failures remain Experimental.
There is no universal Kongono exception marker. Native engine, autoloader,
consumer-constructor, and provider failures are not broadly wrapped to enter a
package family.

Each Public marker declares exactly:

```php
public function diagnosticId(): string;
public function diagnosticTitle(): string;
public function diagnosticRemediation(): string;
```

The owning Internal abstract base stores one private readonly enum case and
implements those methods. The Internal catalogs are
`Kongono\Kernel\Diagnostic\LifecycleDiagnostic`,
`Kongono\ApplicationGraph\Diagnostic\GraphDiagnostic`, and
`Kongono\DependencyInjection\Diagnostic\DependencyInjectionDiagnostic`.
Public methods return strings and do not expose the Internal enums. Every
package exception retains native behavior: `getCode()` is zero,
`getMessage()` is the exact supplied message, and `getPrevious()` is the exact
optional supplied cause. No current production throw adds a wrapping cause.

Catch only the owning marker a boundary can handle:

```php
use Kongono\ApplicationGraph\Exception\ExceptionInterface as GraphFailure;
use Kongono\ApplicationGraph\Model\ApplicationGraph;

try {
    $graph = new ApplicationGraph($nodes, $relations);
} catch (GraphFailure $failure) {
    $id = $failure->diagnosticId();
    $title = $failure->diagnosticTitle();
    $remediation = $failure->diagnosticRemediation();

    throw $failure;
}
```

Reading metadata is current behavior. The example deliberately rethrows because
it does not invent recovery, logging, rendering, or transport policy.

## Diagnostic identifier contract

The exact syntax is:

```text
KGN-<DOMAIN>-<NNNN>
```

- `KGN` is fixed.
- `DOMAIN` is an approved uppercase ASCII owner token.
- `NNNN` is exactly four decimal digits from `1000` through `9999`.
- Comparison is byte-for-byte and case-sensitive.
- Whitespace, underscores, localization, versions, environments, HTTP status,
  severity, timestamps, and instance identifiers are prohibited.
- Once assigned, an identifier is never reused for a different semantic reason;
  removed or replaced identifiers remain reserved.
- Wording and remediation may improve without changing semantic identity.
- A semantic split receives new identifiers. Identical reasons at more than one
  throw site may share an identifier.
- Never parse exception message prose to discover an identifier.
- The identifier is not `Exception::getCode()` and must not be stored in or
  inferred from it.

Diagnostic identifiers are not Graph IDs, Documentation IDs, operation IDs,
trace IDs, request IDs, incident references, database keys, URLs, or transport
error codes.

The implemented current domains are `KERNEL`, `GRAPH`, and `DI`. A new domain needs
an explicit owning package or capability and documented allocation.

### Kernel catalog: `LifecycleDiagnostic` (6 cases)

| Enum case | Identifier | Failure reason | Required remediation direction |
| --- | --- | --- | --- |
| `BootWhenNotUnbooted` | `KGN-KERNEL-1001` | Boot requested when the Kernel is not `Unbooted` | Boot each Kernel instance exactly once from `Unbooted` |
| `ShutdownWhenNotBooted` | `KGN-KERNEL-1002` | Shutdown requested when the Kernel is not `Booted` | Shut down only a booted, non-shutdown Kernel |
| `ShutdownWithActiveOperations` | `KGN-KERNEL-1003` | Shutdown requested while operations remain active | End every operation owned by that Kernel before shutdown |
| `BeginOperationWhenNotBooted` | `KGN-KERNEL-1004` | Operation begin requested when the Kernel is not `Booted` | Boot first and do not begin work after shutdown |
| `EndOperationWhenNotBooted` | `KGN-KERNEL-1005` | Operation end requested when the Kernel is not `Booted` | End active scopes while their owning Kernel is booted |
| `InactiveOrForeignOperationScope` | `KGN-KERNEL-1006` | Operation scope is inactive, already ended, or owned by another Kernel | Pass the exact active scope to the Kernel instance that created it |

### Application Graph catalog: `GraphDiagnostic` (11 cases)

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

### Dependency Injection catalog: `DependencyInjectionDiagnostic` (25 cases)

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

These exact semantic assignments are implemented by the Internal catalogs.
Current deliberate throws emit them through their package marker accessors. The
two defensive DI post-construction checks share `KGN-DI-1025` because they
represent the same semantic reason.

## Required actionable content

The complete actionable-diagnostic doctrine requires every implemented
deliberate diagnostic to provide directly, or through a documented
version-matched registry:

1. stable identifier;
2. precise title;
3. failed operation;
4. exact violated invariant, expectation, or state;
5. safe actual-versus-expected information when available;
6. safe known location, input position, symbol, or component;
7. one short remediation direction;
8. original exception chain after semantic wrapping;
9. owning package or capability; and
10. version-matched documentation location.

Phase 0M directly implements identifier, title, remediation, package ownership,
and optional native cause preservation for the current 42 reasons. Existing
reason-specific messages continue to identify operation, invariant, state, or
location where already present. Safe structured actual/expected context,
redaction types, and version-matched runtime documentation lookup remain open.

Do not ship a deliberate diagnostic that says only `Invalid value`, `Invalid
configuration`, `Operation failed`, `Unexpected error`, `Something went wrong`,
or `Unable to continue`. If such an introductory phrase is necessary, follow it
with the exact cause and remediation.

### Before and after

Vague:

```text
Invalid configuration.
```

Precise future rendered diagnostic:

```text
KGN-GRAPH-1004 - Nodes input is not a PHP list
Application Graph construction could not validate the nodes input because its
keys were not zero-based and consecutive. Expected list keys 0..2; received a
safe representation of keys 0, 2, 3. Reindex the node array as a PHP list.
```

`KGN-GRAPH-1004`, its title, and its remediation are now emitted as runtime
metadata. The combined rendering, safe key representation, and formatting shown
above remain future work.

## Safe context, escaping, truncation, and redaction

- Prefer field names, types, positions, counts, states, or safely represented
  identifiers over raw values.
- Quote and escape displayed values so input cannot alter the presentation.
- Bound every source excerpt and value. Mark truncation explicitly.
- Never include credentials, tokens, cookies, authorization headers, session
  values, encryption material, private keys, full environment dumps, or
  arbitrary request bodies.
- Do not presume that a Graph key, service name, route, SQL value, file path, or
  provider message is safe for an untrusted audience.
- Retain richer protected diagnostics when operationally necessary, but expose
  them only under an explicit disclosure policy.

There is no authorized arbitrary context bag or exact redaction API yet.

## Catch and wrapping guidance

Catch a package marker only when the boundary can recover, translate, or add
policy. The following pseudocode describes intended future boundary behavior; it
is not an implemented Kongono API:

```text
try application operation
catch owning package marker as failure
    if this boundary can recover
        recover under explicit policy
    else
        preserve and rethrow
```

Wrap only across an owning semantic boundary that adds meaningful context:

```text
try provider operation
catch provider failure as cause
    throw capability-specific failure with previous = cause
```

The pseudocode is unimplemented. Do not catch merely to rethrow the same
exception, hide its origin, or force it into a Kongono family. Never discard the
cause chain. Broad `Throwable` catches belong only at a true top-level runtime
boundary or around cleanup that rethrows the primary failure.

## Record once

Exceptions do not log themselves. One outer boundary owns final recording so a
single failure does not produce duplicate log entries at every layer. Preserve
the exception chain. OpenTelemetry is the intended future instrumentation model,
but no logger, telemetry hook, exporter, or incident store exists in Phase 0M.

A diagnostic identifier may correlate protected records later. It is not a
trace ID or opaque incident reference.

## Retry and idempotency

Never infer retry from `RuntimeException`, a package marker, an HTTP status, or a
diagnostic-number range. A future retry decision must be explicit, bounded,
operation-aware, and safe for idempotency. Invalid framework configuration or
structure, lifecycle misuse, and invariant defects are not presumed retryable.

This guide selects no retry policy, timeout type, circuit breaker, cancellation
signal, compensation mechanism, or recovery engine.

## Persistent-runtime cleanup

Every future operation boundary must guarantee scope and resource cleanup even
when application work throws. Apply these rules:

- successful cleanup does not replace or erase the primary failure;
- cleanup failure is never silently discarded;
- uncertain runtime state after cleanup means that worker or runtime instance
  must not quietly accept more work; and
- cancellation and simultaneous primary-plus-cleanup failure representation
  require explicit future contracts.

No cleanup orchestration, runtime adapter, cancellation API, or recovery API is
implemented. The current Kernel and `OperationScope` behavior is unchanged.

## Development and production presentation

### Future development renderer

The intended information architecture is:

```text
+--------------------------------------------------------------------+
| KGN-DOMAIN-NNNN | Precise title                                    |
+--------------------------------------------------------------------+
| What happened                                                     |
| Why it happened                                                   |
| How to fix it                                                     |
+--------------------------------------------------------------------+
| Safely escaped application location and bounded source excerpt     |
| Exception and cause chain                                          |
| Available sanitized operation/request context                      |
| Application frames; collapsible framework/vendor frames            |
+--------------------------------------------------------------------+
| Copy sanitized Markdown/JSON | Version-matched local documentation |
+--------------------------------------------------------------------+
```

The future renderer must be readable and accessible, useful without mandatory
client-side JavaScript, deterministic on the server, escaped and bounded, and
safe against secret disclosure even in development. It must explain known
diagnostics without a remote call, AI request, or internet lookup. Developer
mode must come from trusted server configuration, never a request header, query
parameter, cookie, or other client-controlled input.

### Future production surface

Expose only an intentionally generic response appropriate to the transport and,
when protected recording exists, an opaque incident reference. Never expose
unapproved diagnostic detail, exception class or message, stack trace, source
excerpt, absolute path, environment or configuration value, credentials,
cookies, headers, request body, SQL, provider payload, or internal topology.

An incident reference is not the stable diagnostic identifier. HTTP status and
problem formats, response negotiation, localization, CLI output and exit codes,
templates, and rendering APIs remain open.

Both presentation models are requirements for future work, not current
capabilities.

## Testing expectations

Phase 0M tests verify all 42 exact catalog cases, identifiers, titles and
remediation strings; uniqueness and domain syntax; Public marker and Internal
base shapes; every reachable deliberate throw mapping; both DI-1025 source
sites; unchanged messages and state; optional-cause identity and zero code; and
unchanged native, autoloader, and consumer-constructor propagation.

Later safe-context and presentation work must additionally verify
reason-specific actual/expected or location information, escaping, bounds,
truncation markers, redaction, production non-disclosure, trusted debug-mode
selection, version-matched documentation, and single final recording. Those
mechanisms and tests are not implemented by Phase 0M.

## Non-goals and open decisions

Beyond the exact package-local metadata implemented by Phase 0M, this guide does
not authorize a shared diagnostic interface or package, a universal exception
marker, a generic outcome type, a renderer, transport mapping, HTTP problem
format, CLI behavior, logger,
telemetry hook, incident store, retry or cancellation type, cleanup runner,
source reader, arbitrary context bag, localization, or Application Graph
diagnostic metadata.

Open decisions include exact context and redaction types; renderer and package
ownership; HTTP/problem and CLI mapping;
incident storage; logging and telemetry integration; documentation URL and
version selection; localization; source excerpt provider; retry and
cancellation types; and primary-plus-cleanup failure representation.

## Related documentation

- [ADR 0026](../adr/0026-actionable-diagnostics-and-error-boundaries.md)
- [ADR 0027](../adr/0027-package-local-runtime-diagnostic-metadata.md)
- [Kernel package](../../packages/kernel/README.md)
- [Application Graph package](../../packages/application-graph/README.md)
- [Dependency Injection package](../../packages/dependency-injection/README.md)
- [Runtime model](../architecture/runtime-model.md)
- [Security policy](../../SECURITY.md)
- [Documentation standard](../documentation-standard.md)
- [API status](api-status.md)
