# Error Handling and Actionable Diagnostics

## Purpose and current status

This guide is the operational source of truth for Kongono failure behavior.
Every deliberate framework failure must say what failed, why, where, and what
the developer can do next while preserving package ownership and safe
disclosure.

Phase 0M implements package-local ID, title, and remediation for every current
deliberate Kernel, Application Graph, and Dependency Injection failure. Three
Internal backed-enum catalogs own those 42 mappings. Phase 0P additionally
implements a presentation-neutral Diagnostics package with closed bounded
schemas and immutable values plus 11 package-local construction diagnostics.
Phase 0Q integrates all 11 Graph failures, Phase 0T integrates all six Kernel
lifecycle failures, and Phase 0U integrates all 25 DI diagnostics across 26 throw
sites. Those three packages own exact catalog schemas, capture exact throw-site
values, and expose one eager immutable context through their separate markers.
Diagnostics construction failures
remain context-free. The six Public package exception markers remain separate
recovery boundaries, and all 42 existing messages remain unchanged protected
detail.
No universal diagnostic interface, HTTP or CLI mapping, logger, telemetry
integration, retry engine, cleanup runner, incident system, development error
page, or production renderer is implemented.

Phase 0X implements the separate Diagnostic Presentation leaf, four immutable
Experimental developer concepts, pure three-marker projector, and 15 context-
free package failures. It adds no renderer, trusted-mode selector, production
model, transport, service binding, or other runtime integration.

Phase 0S implements the ownership topology accepted in Phase 0R.
`kongono/api-metadata` now owns `Kongono\ApiMetadata\Api` and `ApiStatus` and
requires PHP only. Kernel, Diagnostics, Graph, and DI depend directly on it;
Graph, Kernel, and DI also require Diagnostics independently. The old metadata-only Kernel edges and
`Kongono\Kernel\Api\*` FQCNs are absent without a shim. Phase 0T adds the exact
acyclic Kernel-to-Diagnostics edge and six Kernel contexts without changing the
metadata extraction. Phase 0U adds the exact acyclic DI-to-Diagnostics edge and
25 DI contexts without changing resolver behavior.

All 53 prior diagnostic IDs and the 42 Kernel/Graph/DI assignments remain
unchanged. Presentation adds 15 unique IDs for 68 repository-wide. All 42
original failures expose context: six Kernel, 11 Graph, and 25 DI. The pure
trusted-developer projection and trusted HTML renderer are implemented;
Phase 1A accepts their fail-closed two-gate activation architecture, but
Phase 1C implements the typed trusted-bootstrap permission value and Phase 1D
adds the typed adapter-controlled delivery-permission value. Delivery-evidence
evaluation, adapter composition, transport, delivery, response behavior, and
production rendering remain future.

Phase 1E implements only the pure two-permission eligibility calculation. The
four-row AND truth table is executable, but external provenance validation and
adapter composition remain future outer-adapter work.

Phase 1H implements the transport-neutral trusted diagnostic handler and tests
its exact eligibility-first sequencing. It adds no binding, adapter, evidence
evaluation, transport, delivery, response, or production model.

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
- Diagnostics: `Kongono\Diagnostics\Exception\ExceptionInterface`
- Diagnostic Presentation:
  `Kongono\DiagnosticPresentation\Exception\ExceptionInterface`
- HTTP Message: `Kongono\Http\Message\Exception\ExceptionInterface`
- Routing: `Kongono\Routing\Exception\ExceptionInterface`
- HTTP Server: `Kongono\Http\Server\Exception\ExceptionInterface`
- Web Application: `Kongono\Web\Application\Exception\ExceptionInterface`

The HTTP marker covers only rejected HTTP message arguments and stream/upload
runtime failures. Its two final Experimental exceptions retain native
invalid-argument and runtime categories and expose one of 28 bounded
`KGN-HTTP-1xxx` diagnostics. Raw headers, URI text, request targets, content,
metadata, paths, server data, attributes, credentials, and secrets never enter
their context or messages.

Their abstract bases remain Internal and concrete failures remain Experimental.
There is no universal Kongono exception marker. Native engine, autoloader,
consumer-constructor, and provider failures are not broadly wrapped to enter a
package family.

The Diagnostics marker declares exactly:

```php
public function diagnosticId(): string;
public function diagnosticTitle(): string;
public function diagnosticRemediation(): string;
```

The Kernel, Graph, and DI markers independently declare the same three string methods plus:

```php
public function diagnosticContext(): DiagnosticContext;
```

The Diagnostics Internal base stores one private readonly enum case. The Kernel,
Graph, and DI bases additionally store one private readonly
`DiagnosticContext`, constructed eagerly from package-approved values. The
Internal catalogs are
`Kongono\Kernel\Diagnostic\LifecycleDiagnostic`,
`Kongono\ApplicationGraph\Diagnostic\GraphDiagnostic`,
`Kongono\DependencyInjection\Diagnostic\DependencyInjectionDiagnostic`, and
`Kongono\Diagnostics\Diagnostic\DiagnosticsDiagnostic`, and
`Kongono\DiagnosticPresentation\Diagnostic\DiagnosticPresentationDiagnostic`.
Public methods do not expose the Internal enums. Every
package exception retains native behavior: `getCode()` is zero,
`getMessage()` is the exact supplied message, and `getPrevious()` is the exact
optional supplied cause. No current production throw adds a wrapping cause.
Diagnostics construction failures intentionally expose no context accessor.

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
    $context = $failure->diagnosticContext();

    throw $failure;
}
```

Reading Graph metadata and context is current behavior. The example deliberately rethrows because
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

The implemented current domains are `KERNEL`, `GRAPH`, `DI`, and `DIAGNOSTICS`.
A new domain needs an explicit owning package or capability and documented
allocation.

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

## Implemented Phase 0P value foundation

`kongono/diagnostics` owns presentation-neutral structural values and depends on
API Metadata only. Graph, Kernel, and DI independently depend on it as semantic
consumers; Diagnostics has no reverse dependency or originating-package
ownership.

The exact Public construction surface is:

```php
new DiagnosticFieldSchema(
    string $key,
    string $label,
    DiagnosticValueKind $kind,
    DiagnosticDisclosure $disclosure,
    bool $required = true,
)

new DiagnosticContextSchema(
    string $operation,
    string $violatedInvariant,
    array $fields = [],
)

new DiagnosticField(DiagnosticFieldSchema $schema, string|int|bool $value)
new DiagnosticContext(DiagnosticContextSchema $schema, array $values = [])
```

Field-schema accessors are `key()`, `label()`, `kind()`, `disclosure()`, and
`isRequired()`. Context-schema accessors are `operation()`,
`violatedInvariant()`, and `fields()`. Field accessors are `key()`, `label()`,
`kind()`, `value()`, `disclosure()`, and `textWasTruncated()`. Context accessors
are `operation()`, `violatedInvariant()`, and `fields()`.

Keys are non-empty lowercase ASCII snake case matching
`^[a-z][a-z0-9]*(?:_[a-z0-9]+)*$` and at most 64 bytes. Labels are non-empty
valid UTF-8 and at most 96 bytes. Operation and invariant prose are non-empty
valid UTF-8 and at most 160 and 256 bytes. A context schema is a detached PHP
list of at most 16 field schemas with exact binary-unique keys in supplied order.
Kinds are exactly Text (`text`), Integer (`integer`), and Boolean (`boolean`);
disclosures are exactly Safe (`safe`) and Protected (`protected`).

Context values use only exact schema-approved string keys. Integer and unknown
string keys fail before missing or kind checks; deterministic selection prefers
the numerically smallest integer, then the binary-smallest unknown string.
Required fields must be present, absent optional fields are omitted, native kinds
must match exactly, and fields are constructed in schema order. Schemas, caller
arrays and reference slots, stored snapshots, and returned arrays are detached.

Text accepts arbitrary bytes and stores only a deterministic ASCII
representation. Printable bytes `0x20` through `0x7E` except backslash remain
literal. Backslash, tab, line feed, and carriage return become `\\`, `\t`, `\n`,
and `\r`. Every other byte becomes `\xHH` with uppercase hexadecimal. Complete
tokens are accumulated only until the first token would exceed 512 represented
bytes. Truncation retains the longest complete-token prefix that leaves room for
the exact 14-byte `...[truncated]` marker. The original string is not retained.
This is binary-safe representation, not secret detection, redaction,
sanitization, HTML/JSON/shell escaping, or transport encoding.

The Diagnostics construction catalog is exact:

| Case | Identifier | Title | Remediation |
| --- | --- | --- | --- |
| `InvalidFieldKey` | `KGN-DIAGNOSTICS-1001` | Diagnostic field key violates the canonical key contract | Use non-empty lowercase ASCII snake case containing at most 64 bytes |
| `InvalidFieldLabel` | `KGN-DIAGNOSTICS-1002` | Diagnostic field label violates the fixed-label contract | Use non-empty valid UTF-8 containing at most 96 bytes |
| `InvalidOperation` | `KGN-DIAGNOSTICS-1003` | Diagnostic context operation violates its prose contract | Use non-empty valid UTF-8 containing at most 160 bytes |
| `InvalidInvariant` | `KGN-DIAGNOSTICS-1004` | Diagnostic context invariant violates its prose contract | Use non-empty valid UTF-8 containing at most 256 bytes |
| `FieldSchemasNotList` | `KGN-DIAGNOSTICS-1005` | Diagnostic field schemas are not a zero-based consecutive list | Reindex field schemas as a PHP list |
| `InvalidFieldSchemaEntry` | `KGN-DIAGNOSTICS-1006` | Diagnostic schema list contains a non-field-schema entry | Replace the reported entry with a DiagnosticFieldSchema |
| `TooManyFieldSchemas` | `KGN-DIAGNOSTICS-1007` | Diagnostic context schema exceeds the field limit | Define at most 16 field schemas |
| `DuplicateFieldSchemaKey` | `KGN-DIAGNOSTICS-1008` | Diagnostic context schema contains a duplicate field key | Keep each canonical field key exactly once |
| `InvalidContextValueKey` | `KGN-DIAGNOSTICS-1009` | Diagnostic context values contain an unapproved field key | Supply values only for exact keys in the closed schema |
| `MissingRequiredContextValue` | `KGN-DIAGNOSTICS-1010` | Diagnostic context is missing a required field value | Supply every value required by the closed schema |
| `ContextValueKindMismatch` | `KGN-DIAGNOSTICS-1011` | Diagnostic field value does not match its schema kind | Supply the exact string, integer, or boolean type required by the field schema |

These failures implement only the fourth Diagnostics marker and expose no
recursive context accessor. Before Presentation, the four catalogs contain 53
unique IDs: 6 Kernel, 11 Graph, 25 DI, and 11 Diagnostics. The Presentation
catalog adds 15 context-free IDs for 68 repository-wide.

Structural validation does not prove that a schema belongs to an identifier.
Originating packages still own semantic schema authorization and forbidden-data
exclusion. Safe remains eligibility, not actual disclosure. Construction is
eager, immutable, bounded, performs no I/O or ambient lookup, and retains no
mutable global state.

## Implemented Kernel, Graph, and DI existing-exception integration

All 11 Graph, six Kernel, and 25 DI schema instances and exact throw-site values
are implemented. Their separate markers add
`diagnosticContext(): DiagnosticContext`; each Internal base eagerly owns one
context and repeated access returns the same object. IDs, titles, remediation,
messages, codes, causes, failure priority, success behavior, lifecycle state,
ordering, selectors, definition validation, and resolver behavior remain
unchanged. Diagnostics construction failures remain context-free.

### Ownership and conceptual values

A presentation-neutral `kongono/diagnostics` package now owns these Public value
concepts and their schema and kind companions:

```text
Kongono\Diagnostics\Context\DiagnosticContext
Kongono\Diagnostics\Context\DiagnosticField
Kongono\Diagnostics\Context\DiagnosticDisclosure
```

The shared package does not own Kernel, Graph, or DI diagnostic meaning and
will not define a universal exception or catch marker. Each originating package
continues to own its identifiers, title and remediation prose, context schema,
throw-site values, and Public exception marker. Kernel, Graph, and DI catch
boundaries remain pairwise unrelated.

`DiagnosticDisclosure` has exactly two semantic cases:

- `Safe` is catalog-approved information eligible for intentional disclosure
  only after a future presentation or transport policy permits it.
- `Protected` is diagnostic information available only within a trusted
  developer or protected operational boundary.

There is no `Secret` case. Forbidden material is not captured.

A `DiagnosticContext` is an immutable, ordered, bounded structural snapshot. An
integrated Kernel, Graph, or DI deliberate exception constructs it eagerly. It contains
one fixed catalog operation, one fixed catalog violated invariant or expectation,
and zero or more ordered fields. A `DiagnosticField` contains a canonical key, a
fixed catalog label, a bounded scalar value, its fixed disclosure, and whether a
text representation was truncated. Phase 0P fixes the direct constructors and
accessors above; package marker integrations are now complete for the original
three semantic families.

### Schema, key, value, and bound contract

Context is not an arbitrary map or bag. For every diagnostic identifier, the
owning Internal catalog defines fixed operation and invariant prose plus the
exact allowed field keys, labels, scalar kinds, disclosures, canonical order,
and required/optional status. Graph implements this ownership for its 11 cases,
Kernel for its six lifecycle cases, and DI for its 25 cases across 26 throw
sites. A throw site supplies only approved values. It
cannot invent a key, label, disclosure, or order. An unavailable optional field
is omitted; no `null` placeholder is used.

Field keys must be unique within one context, use lowercase ASCII snake case,
match `^[a-z][a-z0-9]*(?:_[a-z0-9]+)*$`, and contain at most 64 bytes. A reused
key retains one semantic meaning. Keys never encode values, indexes, locales,
versions, or environments. Fixed English catalog labels contain at most 96
UTF-8 bytes. Labels are not stable machine identity and may be localized only
under a later decision.

Allowed semantic scalar kinds are exactly `text`, `integer`, and `boolean`.
The stored representation uses `string|int|bool`. Floats, `null`, arrays, maps, lists,
objects, enums, resources, callables, closures, `Throwable` values, lazy
suppliers, serialized values, raw JSON, arbitrary stringified objects, and
nested context are prohibited. Multiple facts require multiple approved fields;
a list cannot be smuggled into delimited text.

The Phase 0P runtime contract enforces:

- at most 16 fields in one context;
- at most 64 bytes in a key;
- at most 96 UTF-8 bytes in a fixed label;
- at most 512 bytes in the bounded representation of a text value;
- exactly one truncation flag for each text field;
- no truncation flag for integer or boolean fields; and
- deterministic truncation with an explicit visible marker.

The exact byte-oriented representation and truncation algorithm is implemented
as specified above.
Integers must fit PHP's native integer range on the supported 64-bit platform.
Counts are non-negative wherever their schema describes a count.

Field order is schema order, never caller insertion, hash, reflection, locale,
or alphabetical order unless the schema explicitly chooses it. Context and
fields are immutable detached snapshots: no reference, object identity,
closure, mutable collection, ambient service, or later state lookup can affect
them. Repeated access to one Graph exception returns the same context object with
semantically identical operation, invariant, fields, order, values, disclosures,
and truncation flags.

### Disclosure and forbidden capture

Safe fields include catalog-controlled lifecycle states, non-negative counts,
zero-based positions, broad non-identifying PHP value-kind tokens, fixed
expected values, and fixed rejection-reason tokens. Safe is eligibility, not an
instruction to expose. Every future production surface still needs an explicit
allowlist and transport policy.

Protected fields may reveal application structure or caller-controlled data,
including Graph keys and open kind labels, service interface and implementation
names, dependency-cycle contracts, arbitrary array keys, provider or adapter
identifiers, and source or configuration locations. They may be used only by a
trusted developer surface or protected record and are excluded from untrusted
production output by default.

Never capture any of the following, even as Protected:

- passwords, password hashes, passphrases, or PINs;
- API keys, access tokens, refresh tokens, or bearer tokens;
- cookies, session identifiers, CSRF values, authorization headers, or other
  credentials;
- private, signing, or encryption keys and recovery codes;
- database connection strings containing credentials;
- complete environment or configuration dumps;
- complete request or response bodies and arbitrary headers;
- raw SQL with bound values and full provider payloads;
- file contents, source excerpts, serialized objects, or memory dumps; and
- any value whose secrecy is uncertain and whose capture is not required by an
  Accepted schema.

A future boundary may record that forbidden material was omitted; it may never
record the material itself. Disclosure belongs to the schema, not a throw-site
caller, renderer, request, environment value, or AI system. Opaque consumer
text defaults to Protected and cannot be promoted because it resembles a class,
path, key, route, or identifier. AI may explain or suggest remediation but may
not classify a field, override disclosure, recover forbidden data, or become
necessary to interpret a known diagnostic.

### Persistent-runtime and failure contract

Graph capture is synchronous and eager at the deliberate failure site and
reads only explicit arguments and already-owned immutable framework state. It
consults no global request, superglobal, container, current user, thread-local,
fiber-local, environment, clock, random source, filesystem, network, database,
logger, tracer, or AI call. Context creation performs no I/O.

Per-failure values live no longer than their owning exception. No static
registry accumulates them, and no global cache retains them. Schema metadata may
later be immutable process-wide data. Capture has bounded memory and work
proportional to at most 16 fields. A context-capture failure must never silently
replace the primary deliberate failure; simultaneous-failure representation
remains an explicit later decision.

Phase 0P selects no PHP exception serialization, process or queue transfer,
worker-restart behavior, or cross-language representation.

## Accepted context schemas

This table is the implemented normative 42-schema contract. Its six Kernel, 11
Graph, and 25 DI rows are current. The operation and violated
invariant are fixed catalog prose. All fields are required unless marked
`optional`, and their written order is canonical schema order. The syntax is
`key:value kind:disclosure:requirement`.

| Diagnostic | Fixed operation | Fixed violated invariant or expectation | Ordered fields |
| --- | --- | --- | --- |
| `KGN-KERNEL-1001` | Boot Kernel | Boot requires `Unbooted` | `actual_state:text:Safe`; `expected_state:text:Safe` |
| `KGN-KERNEL-1002` | Shut down Kernel | Shutdown requires `Booted` | `actual_state:text:Safe`; `expected_state:text:Safe` |
| `KGN-KERNEL-1003` | Shut down Kernel | Shutdown requires zero active operations | `active_operation_count:integer:Safe`; `expected_active_operation_count:integer:Safe` |
| `KGN-KERNEL-1004` | Begin operation | Beginning work requires `Booted` | `actual_state:text:Safe`; `expected_state:text:Safe` |
| `KGN-KERNEL-1005` | End operation | Ending a scope requires `Booted` | `actual_state:text:Safe`; `expected_state:text:Safe` |
| `KGN-KERNEL-1006` | End operation | Scope must be active and owned by this Kernel | `scope_condition:text:Safe` |
| `KGN-GRAPH-1001` | Create node key | Node-key value must be non-empty | `actual_length:integer:Safe` |
| `KGN-GRAPH-1002` | Create node kind | Node-kind value must be non-empty | `actual_length:integer:Safe` |
| `KGN-GRAPH-1003` | Create relation kind | Relation-kind value must be non-empty | `actual_length:integer:Safe` |
| `KGN-GRAPH-1004` | Construct Application Graph | Nodes input must be a PHP list | `entry_count:integer:Safe`; `first_unexpected_key:text:Protected:optional` |
| `KGN-GRAPH-1005` | Construct Application Graph | Relations input must be a PHP list | `entry_count:integer:Safe`; `first_unexpected_key:text:Protected:optional` |
| `KGN-GRAPH-1006` | Validate node entry | Every nodes-list entry must be a `Node` | `entry_position:integer:Safe`; `actual_value_kind:text:Safe` |
| `KGN-GRAPH-1007` | Validate relation entry | Every relations-list entry must be a `Relation` | `entry_position:integer:Safe`; `actual_value_kind:text:Safe` |
| `KGN-GRAPH-1008` | Validate node uniqueness | Node keys must be unique within a snapshot | `node_key:text:Protected` |
| `KGN-GRAPH-1009` | Validate relation source | Relation source must reference an existing node | `relation_position:integer:Safe`; `source_key:text:Protected` |
| `KGN-GRAPH-1010` | Validate relation target | Relation target must reference an existing node | `relation_position:integer:Safe`; `target_key:text:Protected` |
| `KGN-GRAPH-1011` | Validate relation uniqueness | Source/kind/target triples must be unique | `source_key:text:Protected`; `relation_kind:text:Protected`; `target_key:text:Protected` |
| `KGN-DI-1001` | Validate service contract | Contract must name an existing interface | `subject_role:text:Safe`; `contract_name:text:Protected` |
| `KGN-DI-1002` | Validate service contract | Contract must use its canonical declared name | `subject_role:text:Safe`; `contract_name:text:Protected` |
| `KGN-DI-1003` | Validate service contract | Resolver contract cannot enter the service graph | `subject_role:text:Safe`; `contract_name:text:Protected` |
| `KGN-DI-1004` | Validate implementation | Implementation must name an existing class | `implementation_name:text:Protected` |
| `KGN-DI-1005` | Validate implementation | Implementation must use its canonical declared name | `implementation_name:text:Protected` |
| `KGN-DI-1006` | Validate implementation | Implementation must be publicly instantiable | `implementation_name:text:Protected` |
| `KGN-DI-1007` | Validate implementation | Implementation must satisfy the declared contract | `contract_name:text:Protected`; `implementation_name:text:Protected` |
| `KGN-DI-1008` | Validate implementation | Implementation must not expose the resolver contract | `implementation_name:text:Protected` |
| `KGN-DI-1009` | Validate dependency list | Dependencies must be a PHP list | `entry_count:integer:Safe`; `first_unexpected_key:text:Protected:optional` |
| `KGN-DI-1010` | Validate dependency entry | Dependency entry must be a contract-name string | `dependency_position:integer:Safe`; `actual_value_kind:text:Safe` |
| `KGN-DI-1011` | Validate constructor dependencies | Dependency declarations require a constructor | `implementation_name:text:Protected`; `dependency_count:integer:Safe` |
| `KGN-DI-1012` | Validate constructor | Constructor must be public | `implementation_name:text:Protected` |
| `KGN-DI-1013` | Validate constructor arity | Constructor and definition arity must match exactly | `implementation_name:text:Protected`; `expected_parameter_count:integer:Safe`; `actual_parameter_count:integer:Safe` |
| `KGN-DI-1014` | Validate constructor parameter mode | Parameter must be required, by-value, non-variadic, and non-defaulted | `implementation_name:text:Protected`; `parameter_position:integer:Safe`; `unsupported_mode:text:Safe` |
| `KGN-DI-1015` | Validate constructor parameter type | Parameter must have one non-nullable interface type | `implementation_name:text:Protected`; `parameter_position:integer:Safe`; `actual_type_shape:text:Safe` |
| `KGN-DI-1016` | Validate constructor dependency type | Parameter type must equal the ordered dependency contract | `implementation_name:text:Protected`; `parameter_position:integer:Safe`; `expected_contract:text:Protected`; `actual_contract:text:Protected` |
| `KGN-DI-1017` | Validate definition list | Definitions must be a PHP list | `entry_count:integer:Safe`; `first_unexpected_key:text:Protected:optional` |
| `KGN-DI-1018` | Validate definition entry | Every definitions-list entry must be a `ServiceDefinition` | `definition_position:integer:Safe`; `actual_value_kind:text:Safe` |
| `KGN-DI-1019` | Validate definition uniqueness | Each service contract may have one definition | `contract_name:text:Protected` |
| `KGN-DI-1020` | Validate definition graph completeness | Every dependency contract must have a definition | `owner_contract:text:Protected`; `missing_dependency:text:Protected` |
| `KGN-DI-1021` | Validate definition graph acyclicity | Definition graph must be acyclic | `cycle_contract:text:Protected` |
| `KGN-DI-1022` | Resolve service | Requested service must name an existing interface | `requested_contract:text:Protected` |
| `KGN-DI-1023` | Resolve service | Requested contract must be canonical and not the resolver contract | `requested_contract:text:Protected`; `rejection_reason:text:Safe` |
| `KGN-DI-1024` | Resolve service | Requested service must have a definition | `requested_contract:text:Protected` |
| `KGN-DI-1025` | Construct service | Constructed object must satisfy the validated contract | `contract_name:text:Protected`; `implementation_name:text:Protected` |

Every current identifier appears once in this table: 6 Kernel, 11 Graph, and 25
DI schemas. No identifier is added, removed, renamed, or repurposed. Context
does not change identity. The two DI post-construction invariant defenses retain
one identifier and one schema. Optional `first_unexpected_key` is omitted when
no single key can be identified safely and deterministically. Protected names
retain exact diagnostic value before bounded representation but are not claimed
safe for production. Kernel, Graph, and DI exceptions expose their respective
exact rows now.

### Controlled Safe tokens

Safe text fields are closed tokens, not arbitrary strings:

- `actual_state` and `expected_state` use exact Kernel state names;
- `scope_condition` is `inactive`, `already_ended`, `foreign_owner`, or
  `inactive_or_foreign` until a later semantic split is authorized;
- `subject_role` is `service_contract` or `dependency_contract`;
- Graph and DI `actual_value_kind` are exactly `null`, `boolean`, `integer`, `float`,
  `string`, `array`, `object`, `resource`, `closed_resource`, or defensive
  `unknown`, never a class or resource-type name;
- `unsupported_mode` is a deterministic combination of `optional`, `defaulted`,
  `variadic`, and `by_reference`;
- `actual_type_shape` is `missing`, `builtin`, `union`, `intersection`,
  `nullable`, or `non_interface`; and
- `rejection_reason` is `noncanonical` or `resolver_contract`.

Graph's token algorithm, Kernel's exact state/scope tokens, and DI's mode,
type-shape, role, kind, and requested-contract rejection algorithms are
implemented.

### Implemented Kernel throw-site values

Kernel captures only these exact values before any state mutation:

- boot outside `Unbooted`: current `KernelState` case name and expected
  `Unbooted`;
- shutdown outside `Booted`: current state name and expected `Booted`;
- shutdown with active work: one exact `SplObjectStorage` count used by the
  condition, unchanged message, and context, plus expected count `0`;
- operation begin or end outside `Booted`: current state name and expected
  `Booted`; and
- inactive, ended, or foreign scope: `scope_condition = inactive_or_foreign`.

Kernel intentionally retains no owner or ended-scope metadata and therefore does
not split the conservative scope token. Every field is required and Safe, which
means eligible for later policy rather than automatically disclosed. Context
construction failure propagates as a Diagnostics-package integration defect.

### Implemented Graph throw-site values

Graph captures only these exact values:

- empty key or kind: `actual_length = strlen($value)`;
- non-list nodes or relations: exact `entry_count` and optional
  `first_unexpected_key`;
- invalid node or relation entry: zero-based `entry_position` and closed
  `actual_value_kind` token;
- duplicate node: exact canonically selected `node_key` bytes;
- missing endpoint: caller-list `relation_position` before sorting plus exact
  `source_key` or `target_key` bytes; and
- duplicate relation: exact canonically selected `source_key`, `relation_kind`,
  and `target_key` bytes.

For a non-list input, Graph walks keys in insertion order from expected integer
zero and stops at the first mismatch. An integer mismatch becomes canonical
base-10 text. A string mismatch is omitted entirely; Graph does not retain,
hash, redact, truncate before capture, or inspect later keys. Numeric strings
already normalized by PHP to integers use the integer branch.

The broad kind algorithm maps native `NULL`, boolean, integer, double, string,
array, object, resource, and closed resource to the exact tokens above. It does
not call `get_debug_type()` and does not expose value content. These algorithms
make context construction total for every reachable deliberate Graph failure;
Diagnostics construction failures are not caught or replaced.

### Implemented Dependency Injection throw-site values

DI captures only facts already used by each existing failure: explicit
`service_contract` or `dependency_contract` roles; exact contract and
implementation names; bounded counts and zero-based positions; deterministic
duplicate, missing, and cycle contracts; requested contracts and closed
rejection reasons; and both definition contract and implementation names at the
two shared `KGN-DI-1025` invariant guards.

Dependency and definition non-list inputs use the same first-mismatch algorithm
as Graph: capture `entry_count`, inspect keys in insertion order, represent the
first integer mismatch as canonical base-10 Protected text, omit a string
mismatch, and inspect no later key. Invalid entries use the exact broad native
kind tokens without class or resource-type names.

Constructor parameter mode predicates are each evaluated once, and every true
token is joined without spaces in fixed `optional`, `defaulted`, `variadic`,
`by_reference` order. Type-shape failures use exact missing, union,
intersection, nullable, builtin, then defensive non-interface precedence.
Noncanonical requested contracts take priority over `resolver_contract`. Context
capture adds no traversal, sorting, reflection, autoloading, instantiation,
state, or side effect beyond work already performed by the existing failure.
Registered-autoloader and consumer-constructor failures remain native and
context-free.

### Implemented structural example

The following describes current Graph context returned through
`diagnosticContext()`. Phase 0Z can render its trusted projection as the fixed
HTML document; transport rendering remains future.

```text
diagnostic KGN-GRAPH-1009
operation: Validate relation source
invariant: Relation source must reference an existing node
fields in schema order:
  relation_position = 4 [integer, Safe]
  source_key = "orders.write" [text, Protected]
```

Safe does not make `relation_position` automatically public, and the Protected
Graph key is available only under a future trusted policy.

Forbidden values are omitted at capture, not copied into a field and labeled
redacted:

```text
explicit approved field: requested_contract = "Example\\Contract" [text, Protected]
forbidden input: authorization header
captured context: requested_contract only
boundary record: forbidden credential material omitted
```

No header value, hash, prefix, suffix, redacted copy, or reversible derivative
enters diagnostic context.

## Implemented trusted developer projection

Phase 0X implements the leaf Composer package
`kongono/diagnostic-presentation` and namespace
`Kongono\DiagnosticPresentation\`. The leaf may depend directly on API Metadata,
Diagnostics, Kernel, Application Graph, and Dependency Injection. No existing
package may depend on it, and there is no reverse edge, registration, binding,
Graph node, or ambient discovery. Diagnostics remains
presentation-neutral.

The first pure Experimental boundary implements exactly:

```text
Kongono\DiagnosticPresentation\Developer\DeveloperDiagnosticOwner
Kongono\DiagnosticPresentation\Developer\TrustedDeveloperDiagnostic
Kongono\DiagnosticPresentation\Developer\TrustedDeveloperDiagnosticField
Kongono\DiagnosticPresentation\Developer\TrustedDeveloperDiagnosticProjector
```

The owner tokens are exactly `kernel`, `application_graph`, and
`dependency_injection`. The projector accepts exactly one of:

```text
Kongono\Kernel\Exception\ExceptionInterface
Kongono\ApplicationGraph\Exception\ExceptionInterface
Kongono\DependencyInjection\Exception\ExceptionInterface
```

This union creates no universal marker and excludes arbitrary `Throwable`, the
context-free Diagnostics marker, raw strings, arrays, maps, and generic objects.
A trusted diagnostic's direct constructor is an Experimental implementation
surface, not a Public supported extension or authorization boundary.
A source satisfying more than one accepted marker is ambiguous and must be
rejected before any metadata or context accessor is called. The package-local
context-free catalog implements exactly `KGN-PRESENTATION-1001` through
`KGN-PRESENTATION-1015`.

One `TrustedDeveloperDiagnostic` contains only the exact derived owner;
stable identifier; projected title; fixed context operation; fixed violated
invariant or expectation; projected remediation; and fields in canonical schema
order. One `TrustedDeveloperDiagnosticField` contains only canonical
key, fixed label, exact `DiagnosticValueKind`, already-bounded
`string|int|bool` value, exact Safe or Protected disclosure, and nullable text-
truncation state exactly as supplied by `DiagnosticField`.

Projection copies an immutable detached snapshot and retains no originating
exception, context, field, list, cache, registry, or cross-operation state.
Returned lists are detached. Both Safe and Protected fields are present because
this is expressly trusted-developer data, but classification is preserved
exactly: projection may not promote, reclassify, merge, omit, redact, sanitize,
or infer disclosure.

The marker determines the exact allowed identifier prefix `KGN-KERNEL-`,
`KGN-GRAPH-`, or `KGN-DI-`, followed by exactly four ASCII decimal digits.
Title must be non-empty valid UTF-8 of at most 160 bytes; remediation must be
non-empty valid UTF-8 of at most 256 bytes. Operation and invariant retain the
implemented Diagnostics bounds of 160 and 256 bytes. Fields remain limited to
16 and text remains the implemented deterministic ASCII representation of at
most 512 bytes. Owner, identifier, title, remediation, context, and every field
are each read at most once into local values before construction. Catalog prose
is validated before storage, every list is detached, no catch-all wrapper is
used, and a native `Throwable` raised by an accessor propagates unchanged.

All 42 current Kernel, Graph, and DI catalog entries fit these bounds. The
checks guard the presentation boundary from unsupported custom marker
implementations; structurally valid consumer metadata does not become a
Kongono-owned catalog entry.

The projection performs no I/O, secret detection, redaction, sanitization, or
output escaping. It contains no native message, cause, trace, class name, source
path or excerpt, stack frame, request or operation envelope, header, body,
environment or configuration value, SQL, provider payload, log data, telemetry
attribute, incident reference, documentation URL, localization key, arbitrary
extension metadata, or transport data. Forbidden material remains excluded at
schema ownership and capture time.

Trust is a future outer-runtime composition decision supplied by explicit
server-controlled configuration before Protected data is projected or rendered.
The pure projector neither activates trust nor proves authorization, and calling
it is not authorization. Query, route, body, header, cookie, session, form,
user, database, provider, exception, context, environment string, hostname,
heuristic, AI output, global, superglobal, container, service locator, thread-
local, fiber-local, static, mutable process-wide, or previous-operation state
cannot enable or widen trust.

Production must use a separately designed deny-by-default model with explicit
allowlists; it may not reuse this trusted developer model and redact afterward.
Projection remains separate from concrete rendering. Phase 0X implements no output
grammar or runtime integration.

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
location where already present. Phase 0P implements the bounded structural value
foundation, Phase 0Q implements the 11 Graph schema instances, throw-site
context, and Graph marker accessor, Phase 0T implements the equivalent six
Kernel lifecycle integrations, and Phase 0U implements all 25 DI schemas across
26 throw sites. A sanitizer and version-matched documentation lookup are not
implemented.

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
keys were not zero-based and consecutive. Expected 3 zero-based consecutive
entries; first unexpected key 2. Reindex the node array as a PHP list.
```

`KGN-GRAPH-1004`, its title, remediation, Safe entry-count field, and optional
Protected numeric first-unexpected-key field are current runtime values. The
combined prose formatting shown above remains future rendering work.

## Current safety rule for unstructured detail

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

There is no authorized arbitrary context bag or sanitizer. Closed structural
values, exact byte representation, and the 11 Graph schemas are implemented;
they do not detect forbidden material or authorize disclosure.

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
- simultaneous application-plus-Kernel-scope cleanup failure must preserve both
  objects; and
- cancellation and broader cleanup failures require explicit future contracts.

Phase 2F implements only Web Application's exact begin/end orchestration around
one in-memory application call. The current Kernel and `OperationScope` behavior
is unchanged. Runtime-adapter cleanup, transport resources, cancellation,
broader resource cleanup, recovery, logging, and telemetry remain unimplemented.

## Development and production presentation

### Implemented projection and trusted-developer HTML renderer

The implemented trusted developer projection maps exactly:

```text
Diagnostic header     identifier + precise title + owning package
What happened         fixed operation
Why it happened       fixed violated invariant or expectation
Context               ordered labeled fields with disclosure and truncation state
How to fix it          projected remediation
```

Projection produces no markup and does not pre-escape values. Phase 0Z
implements the Experimental pure renderer accepted by ADR 0036:
`Kongono\DiagnosticPresentation\Developer\Html\TrustedDeveloperHtmlRenderer`.
Its sole method accepts one `TrustedDeveloperDiagnostic` and returns a
complete standalone HTML5 document. It performs no authorization, projection,
transport, I/O, logging, runtime integration, or production presentation.

The implemented page grammar uses exactly the five visible regions above. Its owner labels
are `Kernel`, `Application Graph`, and `Dependency Injection` for the exact
tokens `kernel`, `application_graph`, and `dependency_injection`. An empty
context displays exactly `No additional diagnostic context was captured.` Every
field remains in canonical order and visibly shows its fixed label, canonical
key, exact `text`/`integer`/`boolean` kind, `Safe` or `Protected` disclosure,
formatted value, and `Truncated` only when the text truncation state is true.

Dynamic data may enter only escaped character data in ordinary element text or
the `<title>` RCDATA context. It never enters an attribute, CSS, URL, script,
comment, raw HTML branch, or active behavior. Each dynamic string is escaped at
the final grammar boundary using exactly:

```php
htmlspecialchars(
    $value,
    ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5,
    'UTF-8',
    true,
)
```

Text uses its exact represented string, integers use canonical locale-
independent base-10 decimal text, and booleans use the lowercase token `true` or
`false`; all three then pass through the same escaping call. Escaping prevents
HTML injection but does not authorize disclosure or declassify Protected data.

The implemented English document uses native semantic structure, exactly one `h1`, an
ordered non-skipped `h2` hierarchy, a keyboard-visible skip link, visible focus,
meaning independent of color, sufficient light/dark/forced-colors contrast,
reflow at 320 CSS pixels and 200 percent zoom, aggressive text wrapping,
selectable text, readable print output, and no motion. It requires no JavaScript,
remote asset, network, AI, source crawl, or reflection and uses exactly one
embedded static stylesheet.

For the same model and installed renderer implementation, output is byte-
identical. Input is bounded to 16 fields, the static template plus stylesheet to
16 KiB, complete returned HTML to 96 KiB, and work to
`O(field count + dynamic input bytes)`, with no retained state after return. No
renderer-specific failure family or fallback page is selected.

The exact static HTML/CSS bytes, renderer implementation, and full and empty
golden fixtures are current. Trusted server-controlled activation, HTTP or other
adapter, production model, optional providers, and other formats remain future.
No trusted selector, served route, adapter, or production surface exists.

### Accepted fail-closed trusted diagnostic activation

[ADR 0038](../adr/0038-fail-closed-trusted-developer-diagnostic-activation.md)
accepts the policy that must govern any future adapter. [ADR
0039](../adr/0039-typed-trusted-developer-bootstrap-permission-foundation.md)
implements the first typed input as the Experimental unbacked
`Kongono\DiagnosticActivation\TrustedDeveloperBootstrapPermission` enum. [ADR
0040](../adr/0040-typed-adapter-controlled-delivery-permission-foundation.md)
implements the second as the Experimental unbacked
`Kongono\DiagnosticActivation\AdapterControlledDeliveryPermission` enum. These
values represent already-made decisions and evaluate no underlying evidence. No
raw configuration reader, delivery-evidence evaluator, adapter, HTTP behavior,
served page, transport authorization, or delivery exists.

Phase 1E adds the final readonly Experimental
`TrustedDeveloperPresentationEligibility`. It receives the two decisions for
one current delivery and returns true only for `Allowed` plus `Allowed`. Direct
construction and a true result prove no authority. The value does not access a
diagnostic, projector, renderer, request, adapter, transport, configuration, or
external evidence, and it performs no side effect or enforcement.

[ADR 0042](../adr/0042-transport-neutral-trusted-diagnostic-handler.md) defines
the implemented eighth leaf, `kongono/diagnostic-handler`, and its sole
production declaration:

```php
use Kongono\ApplicationGraph\Exception\ExceptionInterface as GraphExceptionInterface;
use Kongono\DependencyInjection\Exception\ExceptionInterface as DependencyInjectionExceptionInterface;
use Kongono\DiagnosticActivation\TrustedDeveloperPresentationEligibility;
use Kongono\Kernel\Exception\ExceptionInterface as KernelExceptionInterface;

final readonly class TrustedDeveloperDiagnosticHtmlPresenter
{
    public function renderIfEligible(
        TrustedDeveloperPresentationEligibility $eligibility,
        KernelExceptionInterface|GraphExceptionInterface|DependencyInjectionExceptionInterface $source,
    ): ?string;
}
```

The method checks eligibility before any source accessor. False returns
`null` immediately; passing the source argument itself is not source access.
True invokes the existing trusted projector and then the existing HTML renderer,
returning the renderer's exact string unchanged. `null` means only that trusted
developer HTML was not produced because combined eligibility was false. It is
not a response, status, production model, incident or logging instruction,
handled-failure proof, retry signal, empty document, fallback, or success result.
The presenter catches nothing: projector, renderer, and every other `Throwable`
propagate unchanged without wrapping, logging, retry, fallback, or recursion.

This handler sequences already-created inputs only. It does not establish input
provenance, authenticate, authorize, evaluate evidence, select the non-trusted
path, or own transport or delivery. It is stateless and per delivery, with no
ambient, retained, cached, static, thread-local, fiber-local, or cross-delivery
state. Direct calls to the Experimental handler or current lower-level APIs do
not prove authority. Diagnostic Handler is the only current package depending
on both independent lower layers, Diagnostic Activation and Diagnostic
Presentation.

Trusted presentation requires two independent affirmative permissions:

1. **Trusted bootstrap permission** is an already validated, typed, immutable,
   server-owned decision supplied when a future adapter instance is constructed
   before it serves work.
2. **Adapter-controlled delivery permission** is the outer adapter's separate
   decision that the current delivery boundary is eligible for trusted output.

One permission never implies the other. The exact truth table is:

| Bootstrap permission | Delivery permission | Trusted projection/rendering |
| --- | --- | --- |
| denied or absent | any value | prohibited |
| allowed | denied or uncertain | prohibited |
| allowed | allowed | eligible, subject to the later adapter contract |
| invalid or evaluation failure | any value | prohibited; never default to allowed |

Both permissions are evaluated before the adapter reads package exception
metadata or context for presentation, invokes the projector, copies Safe or
Protected fields, invokes the renderer, or delivers, stores, logs, or transmits
the HTML. An exception may already own its eager immutable bounded context;
denial leaves that existing package-local value unchanged while prohibiting
later presentation access and copying. Projecting or rendering optimistically
and discarding after denial is prohibited.

Absence is disabled. Invalid or ambiguous raw bootstrap configuration is
rejected before serving rather than coerced into enabled behavior. Diagnostic
Presentation reads no raw configuration, and raw environment labels or truthy
strings are not authority. Query parameters, routes, paths, headers, cookies,
forms, bodies, sessions, user-supplied tokens, and other client-controlled
values cannot authorize delivery or upgrade denied bootstrap permission.
Database or provider values, arbitrary callbacks, extensions, plugins, agents,
ambient state, and previous-operation state cannot self-authorize. Uncertainty
takes the separately designed non-trusted path; its exact response, incident,
logging, and transport behavior remains open.

The projector and renderer remain low-level pure Experimental APIs. Direct PHP
calls, their API status, output escaping, or a package-local token do not prove
authorization and cannot substitute for the future outer boundary. The
bootstrap decision is immutable for one adapter-instance lifetime; changed raw
configuration requires explicit restart or construction of a new instance.
Delivery permission is isolated to the current delivery and is never stored in
static, ambient, or prior-operation state. Recursive trusted rendering,
fallback activation, and replacing the primary failure with activation failure
are prohibited; exact multi-failure representation remains future.

The exact typed bootstrap and delivery APIs and Diagnostic Activation ownership
are now implemented. The delivery value alone proves neither freshness nor
current-boundary identity and never authorizes presentation without affirmative
bootstrap permission. The Accepted policy still does not select the raw
configuration source or loader, validation failure, reload lifecycle, delivery
evidence sources or evaluator, adapter package, listener or operator identity,
transport authentication, HTTP contract, production model, operational
integration, or cleanup behavior.

### Future production surface

Expose only an intentionally generic response appropriate to the transport and,
when protected recording exists, an opaque incident reference. Never expose
unapproved diagnostic detail, exception class or message, stack trace, source
excerpt, absolute path, environment or configuration value, credentials,
cookies, headers, request body, SQL, provider payload, or internal topology.

An incident reference is not the stable diagnostic identifier. HTTP status and
problem formats, response negotiation, localization, CLI output and exit codes,
templates, and rendering APIs remain open.

The production model and every production renderer remain future work, not
current capabilities. Production must not consume the trusted developer
projection or its HTML renderer.

## Planned Phase 2 outer web error boundary

Phases 2C through 2E implement transport-free request/response values, routing,
middleware sequencing, and terminal routing, but no adapter, status mapping,
emitter, or production failure model. Phase 2E delegates unmatched output to an
explicit handler without choosing status, headers, or body. A normal application
failure must follow a separately modeled generic
non-trusted path by default; it must never reuse or redact the trusted developer
model.

The future outer adapter must establish current-delivery evidence before any
diagnostic source accessor is used for presentation. Trusted output is eligible
only when both independent typed permissions are affirmative. Denial must occur
before source access and must never fall back to trusted output. Authorization,
evidence, activation, projection, rendering, transport, or cleanup failure must
not recursively enable trusted rendering or replace the generic non-trusted
path with Protected output.

The outer boundary also owns response conversion or delivery and the exact-once
closure of its Kernel operation scope on success and failure. Exact production
status, headers, body, incident behavior, delivery evidence, cleanup API, and
simultaneous-failure representation remain open for later explicit decisions.

## Testing expectations

Phase 0M tests verify all 42 exact catalog cases, identifiers, titles and
remediation strings; uniqueness and domain syntax; Public marker and Internal
base shapes; every reachable deliberate throw mapping; both DI-1025 source
sites; unchanged messages and state; optional-cause identity and zero code; and
unchanged native, autoloader, and consumer-constructor propagation.

Phase 0P tests the six Public structural types, exact bounds and scalar kinds,
canonical order, immutability, byte representation and truncation, 11 package
construction diagnostics, four-way marker separation, and 53-ID uniqueness.
Phase 0Q tests the exact 11 Graph schemas and throw-site values, optional string-
key omission, numeric-key capture, closed kind tokens, message and behavior
preservation, same-object context access, detachment, bounds, Graph-to-Diagnostics
dependency, and marker/base shapes. Phase 0T tests the exact six Kernel schemas
and maps, closed state and scope tokens, lifecycle/message preservation,
same-object context access, detachment, marker/base shapes, and the independent
Kernel-to-Diagnostics edge. Phase 0U tests all 25 DI schemas, 26 throw-site maps,
list omission, closed token algorithms, same-object ownership, detachment,
native propagation, marker/base shapes, and the independent DI-to-Diagnostics
edge. Later presentation work must verify production non-disclosure, trusted
debug-mode selection, version-matched documentation, and single final recording.

Phase 0X Diagnostic Presentation tests cover all
three marker families; exact owner and identifier-prefix mapping; rejection of
an ambiguous multi-marker source before any accessor call; at-most-once accessor
reads; exact projected properties; canonical order and full detachment; all
title, remediation, operation, invariant, field-count, and text bounds; exact
Safe/Protected and truncation-state preservation; native accessor-failure
propagation; absence of ambient state, I/O, caches, and cross-operation
retention; and continued separation of all four current package markers.

Phase 0Z adds exactly ten renderer tests covering exact class shape and
Experimental status, full and empty golden documents, owner labels,
final-boundary escaping, field kinds/disclosures/truncation/order, deterministic
stateless rendering, 16-field and byte bounds, semantic accessibility grammar,
and absence of active or external surfaces. These tests do not claim browser,
assistive-technology, HTTP, CSP, trusted-activation, or production integration
coverage.

Phase 1C tests cover the exact trusted-bootstrap enum shape, cases, truth table,
API status, identity, and absence of scalar coercion. Phase 1D applies the same
checks to the adapter-controlled delivery enum. These unit tests validate the
neutral typed values only; they do not claim configuration, evidence evaluation,
combined enforcement, adapter, transport, or production coverage.

Phase 1E tests all four eligibility rows and the exact immutable Experimental
class shape. They prove the pure calculation only, not external validation or
enforcement.

Phase 1H tests the exact presenter shape, every denied permission combination
before source access, exact existing-pipeline HTML for real Kernel, Graph, and DI
failures, unchanged sentinel `Throwable` identity, and repeated/interleaved call
isolation. These tests prove neutral sequencing, not adapter evidence,
authorization, transport, delivery, or production behavior.

## Non-goals and open decisions

Beyond the package-local metadata, structural value foundation, and all 42
original package-owned context integrations, this guide does not authorize a
shared diagnostic interface, universal
exception marker, generic outcome type, additional renderer, transport mapping, HTTP
problem format, CLI behavior, logger, telemetry hook, incident store, retry or
cancellation type, cleanup runner, source reader, arbitrary context bag,
localization, or Application Graph diagnostic metadata.

The two-gate fail-closed semantics and exact typed bootstrap- and
delivery-decision APIs and ownership are resolved. Open decisions include the
server configuration source, schema, loader, validation failure, and reload
lifecycle; outer adapter package and composition contract; delivery-evidence
sources and evaluation mechanism, listener or operator identity; simultaneous
primary, activation, projection, rendering,
transport, and cleanup failure representation; other renderer APIs and formats;
HTTP/problem and CLI mapping; production model and allowlists; incident storage;
logging and telemetry; documentation lookup and version selection; localization;
cause, source, and stack-frame providers; retry and cancellation; and
serialization or cross-process and cross-language form.

## Routing failures

Phase 2D adds the separate Routing exception marker, two final Experimental
invalid-input exceptions, and ten Internal `KGN-ROUTING-1xxx` diagnostics.
Definition failures cover bounded name, method, template, parameter, table, and
match-value structure; request failures cover only bounded method and encoded
path syntax. Context fields are required Safe counts, lengths, positions,
`method`/`path` components, and closed violation tokens. Raw route names,
methods, templates, literal segments, parameter names/values, request paths,
requests, and URIs are excluded from package-owned surfaces.

Getter failures from a consumer PSR request or URI propagate unchanged. An
ordinary valid unmatched request returns `null`, not an exception. A match
contains untrusted encoded parameters and establishes neither authentication nor
authorization. Generic production failure mapping remains deferred to Phase 2G.

## HTTP Server failures

Phase 2E adds the separate HTTP Server marker and one final Experimental native
`InvalidArgumentException` subtype. Three Internal `KGN-HTTP-SERVER-1xxx`
diagnostics cover only construction-time composition failures:

- `KGN-HTTP-SERVER-1001`: required Safe integer `middleware_count` and
  `maximum_middleware` fields;
- `KGN-HTTP-SERVER-1002`: required Safe integer `route_count`, `handler_count`,
  `missing_count`, and `unexpected_count` fields; and
- `KGN-HTTP-SERVER-1003`: required Safe integer `handler_position` and Safe text
  `violation`, whose closed vocabulary is `not_request_handler`.

Middleware, terminal handler, matcher, request accessor, request decoration, and
response failures propagate unchanged. The package catches no `Throwable` and
supplies no error middleware or generic production response. Diagnostic context
retains no middleware object, handler object, route name, request, response,
match, exception message, or other caller value. Phase 2F now composes this
unchanged lower layer with Kernel operation ownership.

## Web Application failures

Phase 2F adds the separate Web Application marker, Internal abstract native
`RuntimeException` base, and two final Experimental failures. The two Internal
diagnostics have fixed prose and empty field schemas:

- `KGN-WEB-APPLICATION-1001` reports a later handle or shutdown call on an
  irreversibly retired application; and
- `KGN-WEB-APPLICATION-1002` reports simultaneous application work and exact
  Kernel-scope cleanup failure.

`OperationCleanupException` preserves the exact primary and cleanup objects and
uses the primary as its native previous exception. Neither failure message,
class, trace, request, response, route, handler, scope, nor arbitrary caller data
enters package-owned diagnostic surfaces. A primary-only failure propagates
unchanged after successful cleanup; cleanup-only failure propagates unchanged
and retires; simultaneous failure throws the composite and retires. Cleanup is
never retried. Known Kernel lifecycle rejection during begin or shutdown remains
non-retiring; an unknown lifecycle failure retires because state is uncertain.
Retirement refuses later dependency access but does not terminate a worker,
recover, log, or record an incident. Phase 2G now owns bounded outer failure
response selection, a delivery send port, and exact adapter-owned transport
cleanup; concrete emission remains outside the package.

## Related documentation

- [ADR 0026](../adr/0026-actionable-diagnostics-and-error-boundaries.md)
- [ADR 0027](../adr/0027-package-local-runtime-diagnostic-metadata.md)
- [ADR 0028](../adr/0028-bounded-diagnostic-context-and-disclosure.md)
- [ADR 0029](../adr/0029-presentation-neutral-diagnostic-value-foundation.md)
- [ADR 0030](../adr/0030-application-graph-diagnostic-context-integration.md)
- [ADR 0033](../adr/0033-dependency-injection-diagnostic-context-integration.md)
- [ADR 0034](../adr/0034-trusted-developer-diagnostic-projection.md)
- [ADR 0035](../adr/0035-trusted-developer-diagnostic-projection-implementation.md)
- [ADR 0036](../adr/0036-accessible-trusted-developer-html-diagnostic-page.md)
- [ADR 0037](../adr/0037-trusted-developer-html-diagnostic-renderer-implementation.md)
- [ADR 0038](../adr/0038-fail-closed-trusted-developer-diagnostic-activation.md)
- [ADR 0039](../adr/0039-typed-trusted-developer-bootstrap-permission-foundation.md)
- [ADR 0040](../adr/0040-typed-adapter-controlled-delivery-permission-foundation.md)
- [ADR 0041](../adr/0041-pure-two-permission-trusted-presentation-eligibility.md)
- [ADR 0042](../adr/0042-transport-neutral-trusted-diagnostic-handler.md)
- [Diagnostic Activation package](../../packages/diagnostic-activation/README.md)
- [Kernel package](../../packages/kernel/README.md)
- [Application Graph package](../../packages/application-graph/README.md)
- [Dependency Injection package](../../packages/dependency-injection/README.md)
- [Diagnostic Presentation package](../../packages/diagnostic-presentation/README.md)
- [Runtime model](../architecture/runtime-model.md)
- [Phase 2 roadmap](../architecture/phase-2-roadmap.md)
- [Routing model](../architecture/routing-model.md)
- [ADR 0046](../adr/0046-deterministic-route-declaration-and-matching.md)
- [ADR 0047](../adr/0047-explicit-psr-15-middleware-and-terminal-routing.md)
- [HTTP server model](../architecture/http-server-model.md)
- [ADR 0048](../adr/0048-web-application-bootstrap-and-kernel-operation-ownership.md)
- [Web Application model](../architecture/web-application-model.md)
- [Web Application package](../../packages/web-application/README.md)
- [Security policy](../../SECURITY.md)
- [Documentation standard](../documentation-standard.md)
- [API status](api-status.md)

## Phase 2 runnable failure proof

The Phase 2H black-box fixture exercises the real Web Runtime failure boundary
without adding error middleware or changing production diagnostics. Unsupported
native failure produces only the fixed generic 500 response. A supported
test-owned Kernel specimen uses reserved fixture identifier `KGN-KERNEL-9001`:
bootstrap denial skips delivery evidence, delivery denial evaluates it once,
and only both affirmative permissions produce the existing trusted HTML and
security headers. The fixture identifier is absent from production enums and
catalogs.

Request URI, path parameter, scenario, mode, native message, trace, local path,
credential, and other request-derived values never enter trusted output. Every
delivery performs one send and close attempt, closes both bodies, and returns to
zero active Kernel operations. This is deterministic acceptance behavior, not a
production incident, logging, telemetry, recovery, or authorization system.
