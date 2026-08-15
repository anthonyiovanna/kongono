# ADR 0027: Package-Local Runtime Diagnostic Metadata

## Status

Accepted

## Date

2026-08-13

## Context

[ADR 0026](0026-actionable-diagnostics-and-error-boundaries.md) reserves 42
package-owned diagnostic identities for the deliberate failures currently raised
by the Kernel, Application Graph, and Dependency Injection packages. Runtime
exceptions previously exposed only protected message prose, leaving tooling and
developers without a stable semantic identifier or direct remediation metadata.

The runtime mechanism must preserve the three existing package-specific recovery
boundaries, every deliberate message, native exception state, cause identity,
and persistent-runtime neutrality. It must not prematurely select shared
diagnostic infrastructure, structured context, presentation, transport,
recording, retry, cleanup, or localization policy.

## Decision

Each owning package defines one Internal string-backed enum catalog:

- `Kongono\Kernel\Diagnostic\LifecycleDiagnostic`, with 6 cases;
- `Kongono\ApplicationGraph\Diagnostic\GraphDiagnostic`, with 11 cases; and
- `Kongono\DependencyInjection\Diagnostic\DependencyInjectionDiagnostic`, with
  25 cases.

The 42 backed values, titles, and remediation strings map exactly to ADR 0026.
The backed identifier is the stable semantic identity. Enum case names are
Internal implementation names. Titles, remediation, and existing exception
messages are human diagnostic prose that may improve without changing the
identifier's meaning.

### Exact Kernel catalog

| Enum case | Identifier | Title | Remediation |
| --- | --- | --- | --- |
| `BootWhenNotUnbooted` | `KGN-KERNEL-1001` | Boot requested when the Kernel is not `Unbooted` | Boot each Kernel instance exactly once from `Unbooted` |
| `ShutdownWhenNotBooted` | `KGN-KERNEL-1002` | Shutdown requested when the Kernel is not `Booted` | Shut down only a booted, non-shutdown Kernel |
| `ShutdownWithActiveOperations` | `KGN-KERNEL-1003` | Shutdown requested while operations remain active | End every operation owned by that Kernel before shutdown |
| `BeginOperationWhenNotBooted` | `KGN-KERNEL-1004` | Operation begin requested when the Kernel is not `Booted` | Boot first and do not begin work after shutdown |
| `EndOperationWhenNotBooted` | `KGN-KERNEL-1005` | Operation end requested when the Kernel is not `Booted` | End active scopes while their owning Kernel is booted |
| `InactiveOrForeignOperationScope` | `KGN-KERNEL-1006` | Operation scope is inactive, already ended, or owned by another Kernel | Pass the exact active scope to the Kernel instance that created it |

### Exact Application Graph catalog

| Enum case | Identifier | Title | Remediation |
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

### Exact Dependency Injection catalog

| Enum case | Identifier | Title | Remediation |
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

Each existing Public package exception marker continues to extend only
`Throwable` and declares exactly:

```php
public function diagnosticId(): string;
public function diagnosticTitle(): string;
public function diagnosticRemediation(): string;
```

There is no universal Kongono exception or diagnostic parent interface. Each
Internal abstract package exception base stores exactly one private readonly
instance of its owning diagnostic enum. Its final public constructor accepts the
owning enum, the existing message, and an optional `Throwable` cause, then calls
`parent::__construct($message, 0, $previous)`. Final public accessors return the
enum's backed value, title, and remediation. No Public method returns an Internal
enum.

Every current deliberate production throw supplies its exact owning enum while
retaining its pre-Phase-0M message expression. Existing messages are not
prefixed, suffixed, or parsed. `getCode()` remains zero, `getMessage()` remains
the supplied message, and `getPrevious()` preserves the exact supplied cause.
Current production throws add no cause site because no new semantic wrapper is
introduced. Native, autoloader, and consumer failures remain unwrapped.

The three enum catalogs and three abstract bases are Internal. The three package
markers remain Public. Existing concrete package failures remain Experimental.
Phase 0M adds no ambient or retained mutable state, I/O, reflection-based lookup,
caching, or lifecycle relationship.

## Rationale

Package-local enums give each deliberate failure a typed, inspectable source of
stable identity and fixed catalog prose without erasing ownership. Keeping the
Public contract to three strings prevents Internal types from becoming consumer
compatibility obligations. Centralizing each package's exact mappings avoids
duplicating mutable prose at every throw site while preserving message and cause
behavior.

This mechanism is persistent-runtime-neutral: metadata is immutable, instance
owned by the exception, and free of ambient state or services.

## Consequences

Consumers may catch only the package boundary they can handle and read an exact
identifier, title, and remediation. Tooling must use `diagnosticId()` as machine
identity rather than parse `getMessage()`. Titles and remediation are diagnostic
prose, not stable machine identity.

Messages remain protected detail. They may contain caller-supplied Graph keys,
kinds, service contracts, implementation names, or counts, so runtime access to
fixed catalog metadata does not authorize disclosure of the message, stack, or
cause chain to an untrusted production surface.

All 42 current mappings are covered by production and architecture tests. The
two defensive DI post-construction invariant sites intentionally share
`KGN-DI-1025` because they represent the same semantic reason.

Phase 0U implementation note: Kernel, Graph, and DI now mirror the accepted
bounded-context ownership pattern independently for all 42 original diagnostics.
DI adds 25 exact schemas across 26 existing throw sites without changing any
catalog string, native message, code, cause, validation or resolution behavior,
or package-local recovery boundary. Those three markers expose context;
Diagnostics retains three string methods and remains context-free.

Phase 0Y architecture note: Diagnostic Presentation owns the
Experimental `TrustedDeveloperHtmlRenderer`, and [ADR 0036](0036-accessible-trusted-developer-html-diagnostic-page.md)
selected its first accessible HTML API and format. Phase 0Y did not implement
the renderer; trusted activation, transport, production presentation, optional
providers, and runtime integration remain future.

Phase 0Z implementation note: [ADR 0037](0037-trusted-developer-html-diagnostic-renderer-implementation.md)
implements that pure renderer and its exact fixture-backed grammar without
rendering from exceptions or changing package-owned metadata. Trusted
activation, transport, production presentation, and optional providers remain
future.

## Rejected alternatives

- One universal Kongono exception or diagnostic interface is rejected because
  recovery remains package owned.
- A new shared diagnostics package is rejected because three small catalogs need
  no new package or dependency boundary.
- Returning Internal enum cases from Public exception methods is rejected
  because it would leak an unsupported implementation type.
- Using `Exception::getCode()` is rejected because native exception codes are not
  the diagnostic identity contract.
- Prefixing or parsing `getMessage()` is rejected because messages remain
  protected, improvable prose.
- Duplicating ID, title, and remediation strings at every throw site is rejected
  because it invites drift.
- An arbitrary structured context array is rejected because safe context and
  redaction types remain unresolved.
- Attributes or reflection-based lookup are rejected because direct enum
  ownership is explicit and deterministic.
- JSON or YAML runtime catalogs are rejected because they add parsing, I/O, and
  another runtime source of truth.
- Logging or rendering from exceptions is rejected because exceptions do not
  own recording or presentation.
- Implementing the development error page in this metadata phase is rejected.
  Diagnostic Presentation owns the renderer; Phase 0Z implements it separately
  while every transport integration remains future.

## What remains open

Trusted activation; production allowlists; HTTP, CLI, and other transport
mappings; safe copy/export formats;
documentation lookup and version selection; cause, source, and stack providers;
incident references and storage; localization; logging and telemetry; retry and
cancellation types; and cleanup-failure representation remain open. The first
HTML renderer API, format, and pure implementation are complete.
