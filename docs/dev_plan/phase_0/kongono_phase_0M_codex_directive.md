# CODEX IMPLEMENTATION DIRECTIVE - KONGONO PHASE 0M

## Package-Local Runtime Diagnostic Metadata

## Mission

Implement the smallest runtime expression of the actionable-diagnostic doctrine accepted in Phase 0L.

Every deliberate exception currently raised by Kongono's Kernel, Application Graph, and Dependency Injection packages must expose:

```text
stable diagnostic identifier
precise diagnostic title
actionable remediation direction
```

The existing package-specific Public exception markers remain the only recovery boundaries. Internal package catalogs own the exact 42 mappings. Existing exception messages, package separation, state guarantees, native failure propagation, and cause chains remain intact.

Phase 0M does not implement structured context, an error page, HTTP or CLI presentation, logging, telemetry, retry, cleanup orchestration, incident storage, localization, or a universal exception family.

---

# 1. ROLE, AUTHORITY, AND STOP RULE

You are the Phase 0M executor, not the architect.

Read completely before acting:

1. `AGENTS.md`;
2. all locked governance, doctrine, architecture, engineering, security, and documentation guidance;
3. ADRs 0001 through 0026 and the ADR index;
4. all current package manifests, production source, exception types, deliberate throw sites, tests, and package READMEs;
5. all directives, reports, and correction reports through accepted Phase 0L;
6. this Phase 0M directive.

This directive explicitly selects the runtime mechanism left open by ADR 0026. Implement it exactly. Do not redesign it into a shared diagnostic package, universal interface, value object, context bag, attribute, registry service, factory, trait, renderer, or transport abstraction.

## Mandatory stop rule

Stop before editing if:

- the repository or caller-record baseline differs from Sections 3 and 4;
- any accepted Phase 0L content is absent, unexpectedly empty, malformed, or concurrently changed;
- a required diagnostic reason cannot map exactly to Section 9;
- an existing deliberate message or native failure contract would have to change;
- a package-local implementation cannot satisfy the exact API and status rules;
- a dependency, Composer/autoload change, new package, suppression, baseline, or tooling-policy change appears necessary;
- any required validation fails for a repository reason;
- any architecture, security, compatibility, or scope choice remains unclear.

When blocked:

```text
STOP
DO NOT GUESS
DO NOT FIX ADJACENT WORK
DO NOT STAGE OR COMMIT
REPORT THE EXACT BLOCKER
```

---

# 2. EXACT PHASE BOUNDARY

Phase 0M authorizes only:

1. three Internal package-owned backed-enum diagnostic catalogs;
2. three-method diagnostic metadata contracts on the existing Public package exception markers;
3. immutable catalog ownership in the existing Internal abstract exception bases;
4. exact mapping of every current deliberate production throw to one reserved diagnostic;
5. production and architecture tests for API shape, catalogs, mappings, separation, message preservation, and cause preservation;
6. ADR 0027 and narrow documentation reconciliation;
7. unchanged canonical tooling and the standard completion report.

No new Composer package, dependency, namespace root, manifest entry, autoload mapping, or framework integration is authorized.

---

# 3. ACCEPTED PRE-PHASE-0M BASELINE

Require the exact committed baseline:

```text
Branch: main
Only local branch: main
HEAD: 5027939d3de2dd508c74ae6007b9dc5bb524e3a8
HEAD tree: 432a0121a87b225de44e9daa301602be79ebd94c
HEAD parent: 7607e7b1844f65cc4aa6e9bcb2579ea30235f4ce
HEAD parent count: 1
Commit count: 4
HEAD subject: Establish Kongono framework baseline through Phase 0J
Tracked files: 192
Tracked modes: 192 x 100644
Remotes: 0
Tags: 0
Staged paths: 0
```

The accepted Phase 0L content state before Phase 0M caller setup is:

```text
Tracked modifications: 13
Untracked paths: 5
Total status paths: 18
Tracked diff: 13 files changed, 246 insertions, 7 deletions
```

## Exact 13 accepted tracked modifications

```text
ARCHITECTURE.md
README.md
SECURITY.md
docs/adr/README.md
docs/architecture/overview.md
docs/architecture/runtime-model.md
docs/doctrine.md
docs/documentation-standard.md
docs/engineering/README.md
docs/glossary.md
packages/application-graph/README.md
packages/dependency-injection/README.md
packages/kernel/README.md
```

## Exact five accepted untracked paths

```text
docs/adr/0026-actionable-diagnostics-and-error-boundaries.md
    19,617 bytes
    SHA-256 2D0FF8DF53CD5C72217571AE435EC67E2078760BB07C9A26936CE1C892DD8CE6

docs/engineering/error-handling.md
    19,696 bytes
    SHA-256 5CBF61BCD7D865EAA782E57765AD9A3BC5AA62FC951ABAD418141961ED3F3E09

docs/dev_plan/kongono_phase_0L_codex_directive.md
    39,127 bytes
    SHA-256 C7A52B4B08DD56595BC5F4A3893C18ADEEFB13F1AEF0F61D595AB23FC729555F

docs/task reports/Phase_0K.md
    18,034 bytes
    SHA-256 F6EA293C490872B598C9E9F1E0D7AA6B18D1F69A4C68E1434813FE55E8871F6F

docs/task reports/Phase_0L.md
    14,817 bytes
    SHA-256 01AD92F26D9FA5EBBF5F31F715E4D8B0AEEBCFAB39B5067CE09007747788B494
```

All Phase 0L content is accepted. The executor may modify only the Phase 0L content paths explicitly authorized later in this directive. Preserve the Phase 0L directive and both earlier reports byte-for-byte.

Accepted quality baseline:

```text
Production packages: 3
Production PHP files/declarations: 27
Test PHP files: 62
First-party PHP/configuration syntax inventory: 90
PHP CS Fixer inventory: 89
PHPUnit: 127 tests, 567 assertions
Focused ApplicationGraphTest: 36 tests, 132 assertions
Focused ApiStatusConventionTest: 5 tests, 154 assertions
PHPStan: 0 errors
Security advisories: 0
Platform requirements: pass on PHP 8.5.9
```

Composer warnings remain limited to the missing license and unreleased local `@dev` or `*` constraints.

---

# 4. PHASE 0M CALLER RECORDS

The caller will add:

```text
docs/dev_plan/kongono_phase_0M_codex_directive.md
docs/task reports/Phase_0M.md
```

The directive must be a non-empty byte-identical copy of this artifact. Record its size and SHA-256 and never edit it.

The report must initially be an ordinary zero-byte placeholder. Do not read it as a directive, stage it, commit it, rename it, or delete it. Populate it only after every implementation and validation gate passes, following Section 18.

After caller setup and before executor edits, require exactly:

```text
Tracked modifications: 13
Untracked paths: 7
Total status paths: 20
Staged paths: 0
```

The seven untracked paths are the accepted five paths from Section 3 plus the Phase 0M directive and report. No other path is authorized.

---

# 5. EXACT FILE-CHANGE BOUNDARY

## Create exactly these four content files

```text
docs/adr/0027-package-local-runtime-diagnostic-metadata.md
packages/application-graph/src/Diagnostic/GraphDiagnostic.php
packages/dependency-injection/src/Diagnostic/DependencyInjectionDiagnostic.php
packages/kernel/src/Diagnostic/LifecycleDiagnostic.php
```

## Modify exactly these 34 tracked or accepted-untracked content files

### Production source: 13

```text
packages/application-graph/src/Exception/ExceptionInterface.php
packages/application-graph/src/Exception/GraphException.php
packages/application-graph/src/Model/ApplicationGraph.php
packages/application-graph/src/Model/NodeKey.php
packages/application-graph/src/Model/NodeKind.php
packages/application-graph/src/Model/RelationKind.php
packages/dependency-injection/src/Definition/ServiceDefinition.php
packages/dependency-injection/src/DependencyResolver.php
packages/dependency-injection/src/Exception/DependencyInjectionException.php
packages/dependency-injection/src/Exception/ExceptionInterface.php
packages/kernel/src/Exception/ExceptionInterface.php
packages/kernel/src/Exception/KernelException.php
packages/kernel/src/Kernel.php
```

### Tests: 6

```text
packages/application-graph/tests/Unit/ApplicationGraphTest.php
packages/application-graph/tests/Unit/StructuralValueTest.php
packages/dependency-injection/tests/Unit/DependencyResolverTest.php
packages/dependency-injection/tests/Unit/ServiceDefinitionTest.php
packages/kernel/tests/Unit/KernelTest.php
tests/Architecture/ApiStatusConventionTest.php
```

### Documentation: 15

```text
ARCHITECTURE.md
README.md
SECURITY.md
docs/adr/0026-actionable-diagnostics-and-error-boundaries.md
docs/adr/README.md
docs/architecture/overview.md
docs/doctrine.md
docs/documentation-standard.md
docs/engineering/api-status.md
docs/engineering/error-handling.md
docs/engineering/testing.md
docs/glossary.md
packages/application-graph/README.md
packages/dependency-injection/README.md
packages/kernel/README.md
```

No other content file may change.

## Preserve exactly

In particular, do not modify:

```text
composer.json
composer.lock
all package composer.json files
.php-cs-fixer.dist.php
phpstan.neon.dist
phpunit.xml.dist
docs/architecture/runtime-model.md
docs/engineering/README.md
all ADRs except 0026, new 0027, and the ADR index
all concrete exception subclass files
all other production and test files
all earlier directives and reports
docs/dev_plan/kongono_phase_0M_codex_directive.md
```

No staging or commit is authorized.

---

# 6. PACKAGE-LOCAL DIAGNOSTIC DESIGN

Implement exactly three package-local Internal catalogs:

```text
Kongono\Kernel\Diagnostic\LifecycleDiagnostic
Kongono\ApplicationGraph\Diagnostic\GraphDiagnostic
Kongono\DependencyInjection\Diagnostic\DependencyInjectionDiagnostic
```

Each catalog is:

- a string-backed PHP enum;
- `#[Api(ApiStatus::Internal)]`;
- in the owning package's existing PSR-4 source tree;
- free of properties, I/O, ambient state, caching, reflection, localization, logging, rendering, or external dependencies;
- the only production location that maps its package's diagnostic IDs to titles and remediation strings.

Each enum case's backed value is its exact `KGN-*` identifier. Each enum declares exactly these two methods:

```php
public function title(): string
public function remediation(): string
```

Both methods use exhaustive `match ($this)` expressions and return the exact catalog text in Section 9. Do not add a generic catalog interface, trait, base enum, lookup service, array registry, JSON/YAML file, attribute, serialization contract, or `__toString()`.

Enum case names are Internal implementation names. Their exact spelling is locked for this phase in Section 9 but carries no consumer compatibility promise. The backed identifier is the stable semantic identity.

---

# 7. EXACT PUBLIC EXCEPTION-METADATA CONTRACT

Modify each existing Public package marker:

```text
Kongono\Kernel\Exception\ExceptionInterface
Kongono\ApplicationGraph\Exception\ExceptionInterface
Kongono\DependencyInjection\Exception\ExceptionInterface
```

Each continues to extend only PHP `Throwable` and declares exactly:

```php
public function diagnosticId(): string;

public function diagnosticTitle(): string;

public function diagnosticRemediation(): string;
```

Rules:

- no shared parent interface is added;
- no method returns an Internal enum;
- no context array, severity, retryability, HTTP status, incident reference, documentation URL, transport code, or localization value is exposed;
- `getMessage()`, `getPrevious()`, `getCode()`, stack trace, file, and line remain native PHP exception behavior;
- the three markers remain pairwise unrelated and package-specific recovery boundaries;
- exact diagnostic identifiers are the stable machine-readable semantic contract;
- title and remediation are human diagnostic prose and may improve later without changing semantic identity;
- messages remain diagnostic and are not a stable parsing contract.

This is an additive Public metadata contract in a pre-alpha repository. It does not create a universal exception catch boundary.

---

# 8. EXACT ABSTRACT EXCEPTION-BASE CONTRACT

Modify the three existing Internal abstract bases:

```text
KernelException
GraphException
DependencyInjectionException
```

Each base owns exactly one new declared property:

```text
private readonly <OwningDiagnosticEnum> $diagnostic
```

Each base declares this exact constructor shape with its owning enum type:

```php
final public function __construct(
    <OwningDiagnosticEnum> $diagnostic,
    string $message,
    ?Throwable $previous = null,
)
```

The constructor must:

1. retain the exact enum instance;
2. call `parent::__construct($message, 0, $previous)`;
3. perform no other work.

Each base implements the three marker methods as `final public` methods:

```php
final public function diagnosticId(): string
{
    return $this->diagnostic->value;
}

final public function diagnosticTitle(): string
{
    return $this->diagnostic->title();
}

final public function diagnosticRemediation(): string
{
    return $this->diagnostic->remediation();
}
```

No base may add another property or method. Do not store duplicate ID/title/remediation strings. Do not expose the enum. Do not override native message, code, previous, string conversion, trace, serialization, or cloning behavior.

The concrete exception subclasses remain final, Experimental, unchanged files, and inherit the constructor and metadata implementation. Constructing a concrete package exception now requires the owning Internal enum, message, and optional cause. Concrete exception construction is not promoted as a consumer extension point.

---

# 9. EXACT DIAGNOSTIC CATALOGS AND ENUM CASES

The title is exactly the Failure reason column. The remediation is exactly the Required remediation direction column.

## 9.1 `LifecycleDiagnostic`

| Enum case | Identifier | Failure reason / exact title | Required remediation direction |
| --- | --- | --- | --- |
| `BootWhenNotUnbooted` | `KGN-KERNEL-1001` | Boot requested when the Kernel is not `Unbooted` | Boot each Kernel instance exactly once from `Unbooted` |
| `ShutdownWhenNotBooted` | `KGN-KERNEL-1002` | Shutdown requested when the Kernel is not `Booted` | Shut down only a booted, non-shutdown Kernel |
| `ShutdownWithActiveOperations` | `KGN-KERNEL-1003` | Shutdown requested while operations remain active | End every operation owned by that Kernel before shutdown |
| `BeginOperationWhenNotBooted` | `KGN-KERNEL-1004` | Operation begin requested when the Kernel is not `Booted` | Boot first and do not begin work after shutdown |
| `EndOperationWhenNotBooted` | `KGN-KERNEL-1005` | Operation end requested when the Kernel is not `Booted` | End active scopes while their owning Kernel is booted |
| `InactiveOrForeignOperationScope` | `KGN-KERNEL-1006` | Operation scope is inactive, already ended, or owned by another Kernel | Pass the exact active scope to the Kernel instance that created it |

## 9.2 `GraphDiagnostic`

| Enum case | Identifier | Failure reason / exact title | Required remediation direction |
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

## 9.3 `DependencyInjectionDiagnostic`

| Enum case | Identifier | Failure reason / exact title | Required remediation direction |
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

All 42 identifiers, titles, and remediation strings must exactly match ADR 0026 and the Phase 0L engineering guide. The three catalogs contain 42 cases total with 42 unique backed values.

---

# 10. EXACT THROW-SITE MAPPING

Add the owning enum argument to every current deliberate production exception construction. Preserve every existing message expression byte-for-byte apart from the new constructor argument and necessary formatting/imports.

## Kernel

```text
Kernel::boot invalid state                      -> KGN-KERNEL-1001
Kernel::shutdown invalid state                  -> KGN-KERNEL-1002
Kernel::shutdown active operations              -> KGN-KERNEL-1003
Kernel::beginOperation invalid state            -> KGN-KERNEL-1004
Kernel::endOperation invalid state              -> KGN-KERNEL-1005
Kernel::endOperation inactive/foreign scope     -> KGN-KERNEL-1006
```

## Application Graph

```text
NodeKey empty value                             -> KGN-GRAPH-1001
NodeKind empty value                            -> KGN-GRAPH-1002
RelationKind empty value                        -> KGN-GRAPH-1003
ApplicationGraph nodes not list                 -> KGN-GRAPH-1004
ApplicationGraph relations not list             -> KGN-GRAPH-1005
ApplicationGraph non-Node entry                 -> KGN-GRAPH-1006
ApplicationGraph non-Relation entry             -> KGN-GRAPH-1007
ApplicationGraph duplicate node key             -> KGN-GRAPH-1008
ApplicationGraph missing relation source        -> KGN-GRAPH-1009
ApplicationGraph missing relation target        -> KGN-GRAPH-1010
ApplicationGraph duplicate relation triple      -> KGN-GRAPH-1011
```

## Dependency Injection

Map every `ServiceDefinition` and `DependencyResolver` throw according to the exact semantic reason in Section 9. In particular:

```text
validatedServiceContract(): missing             -> KGN-DI-1001
validatedServiceContract(): noncanonical        -> KGN-DI-1002
validatedServiceContract(): resolver             -> KGN-DI-1003
validatedImplementation(): missing              -> KGN-DI-1004
validatedImplementation(): noncanonical         -> KGN-DI-1005
validatedImplementation(): not instantiable     -> KGN-DI-1006
validatedImplementation(): wrong contract       -> KGN-DI-1007
validatedImplementation(): resolver             -> KGN-DI-1008
detachRuntimeDependencies(): not list            -> KGN-DI-1009
detachRuntimeDependencies(): non-string          -> KGN-DI-1010
assertConstructorSignature(): no constructor    -> KGN-DI-1011
assertConstructorSignature(): non-public        -> KGN-DI-1012
assertConstructorSignature(): count mismatch    -> KGN-DI-1013
assertConstructorSignature(): parameter mode    -> KGN-DI-1014
assertConstructorSignature(): parameter type    -> KGN-DI-1015
assertConstructorSignature(): type mismatch     -> KGN-DI-1016
validateRuntimeDefinitions(): not list           -> KGN-DI-1017
validateRuntimeDefinitions(): invalid entry      -> KGN-DI-1018
DependencyResolver duplicate contract            -> KGN-DI-1019
DependencyResolver missing dependency            -> KGN-DI-1020
DependencyResolver cycle                         -> KGN-DI-1021
resolve(): missing requested interface            -> KGN-DI-1022
resolve(): noncanonical/class/resolver request    -> KGN-DI-1023
resolve(): definition absent                      -> KGN-DI-1024
both post-construction invariant defenses         -> KGN-DI-1025
```

DI has 26 deliberate throw sites mapping to 25 semantic identifiers because both defensive post-construction checks intentionally share `KGN-DI-1025`.

Do not add a catch, wrapper, retry, log call, context capture, or message prefix at any throw site.

---

# 11. MESSAGE, CAUSE, AND SECURITY CONTRACT

## Message preservation

Every existing deliberate `getMessage()` value must remain unchanged for the same input. Do not prefix or suffix the message with the ID, title, remediation, package, or formatting. Machine consumers use `diagnosticId()`; humans or future renderers may use all fields.

## Native exception state

Every package exception created through the new constructor must have:

```text
getCode() === 0
getPrevious() === the exact supplied Throwable instance, or null
getMessage() === the exact supplied message
```

No current production throw supplies a `previous` cause because Phase 0M adds no semantic wrapper. Tests must prove that manually supplied causes remain identical without inventing a production wrapping site.

## Disclosure

Titles and remediation strings are fixed catalog prose and contain no caller input. Existing messages may contain caller-controlled Graph keys, kinds, service contracts, implementation names, or counts. They remain protected diagnostic detail and must not be presented directly to an untrusted production surface.

Phase 0M adds no structured actual/expected fields, value sanitization, redaction API, arbitrary context bag, or renderer. Do not claim that accessing a package exception makes its message safe to disclose.

---

# 12. REQUIRED TEST CONTRACT

Modify only the six test files listed in Section 5.

## Catalog tests

Add focused catalog coverage that proves for all 42 enum cases:

- exact enum case name;
- exact backed identifier;
- exact title;
- exact remediation;
- uniqueness within and across all three packages;
- exact domain prefix and four-digit range;
- complete equality with the Section 9 catalog;
- `title()` and `remediation()` are the only declared enum methods;
- no declared property, `__toString()`, serialization, I/O, ambient state, or extra contract.

Do not make production catalogs read Markdown or external data at runtime. Test expectations may duplicate the authorized table explicitly.

## Exception API tests

Prove:

- each marker extends only `Throwable` and declares exactly the three diagnostic methods;
- each abstract base remains abstract and Internal;
- each base declares exactly one private readonly non-static diagnostic property;
- each base declares exactly constructor plus the three final public metadata methods;
- each concrete failure remains final and Experimental;
- metadata accessors return the catalog values;
- message is unchanged, code is zero, and an optional `previous` is preserved by identity;
- package markers remain pairwise separate;
- concrete failures implement only their owning package marker.

## Throw mapping tests

Exercise every normally reachable deliberate failure reason and assert:

```text
exact exception class
exact diagnosticId()
exact diagnosticTitle()
exact diagnosticRemediation()
required reason-specific message content
unchanged state where the existing contract promises it
```

Do not fabricate normally unreachable object state to execute the two `KGN-DI-1025` defenses. Instead:

- verify both source sites pass `DependencyInjectionDiagnostic::ConstructedContractInvariantViolation` through direct/static inspection;
- retain PHPStan proof that the guarded runtime invariants are type-correct;
- test manual metadata/cause behavior using a normal concrete exception construction.

## Native propagation regression tests

Retain and strengthen existing proof that:

- registered-autoloader failures propagate as the exact original object;
- consumer-constructor failures propagate as the exact original class/message and do not implement a Kongono DI marker;
- no native failure gains Kongono diagnostic metadata merely by crossing a package call.

## Counts

Add at least one named catalog/metadata test method to each of:

```text
KernelTest
StructuralValueTest
ServiceDefinitionTest
```

The final root suite must contain at least 130 tests and more than 567 assertions. Report exact actual counts. Do not game counts with meaningless assertions; the catalog and mapping requirements must drive them.

---

# 13. API STATUS AND SOURCE INVENTORY

After Phase 0M:

```text
Production packages: 3
Production PHP files: 30
Production class-like declarations: 30
New declarations: exactly 3
New declaration status: exactly Internal
Test PHP files: 62
First-party PHP/configuration syntax inventory: 93
Expected PHP CS Fixer inventory: 92
```

Update `tests/Architecture/ApiStatusConventionTest.php` and `docs/engineering/api-status.md` from 27 to 30 production declarations.

The exact new status entries are:

```text
Kongono\Kernel\Diagnostic\LifecycleDiagnostic                         Internal
Kongono\ApplicationGraph\Diagnostic\GraphDiagnostic                  Internal
Kongono\DependencyInjection\Diagnostic\DependencyInjectionDiagnostic Internal
```

Every prior type retains its prior status. Do not add per-member status, a new API status, an attribute, or an exception-specific visibility rule.

The Public contract expansion is limited to the existing three package markers' exact methods from Section 7. The enum catalogs and abstract bases remain Internal. Concrete failures remain Experimental.

---

# 14. REQUIRED ADR 0027

Create:

```text
docs/adr/0027-package-local-runtime-diagnostic-metadata.md
```

Use exactly:

```text
Title: Package-Local Runtime Diagnostic Metadata
Status: Accepted
Date: 2026-08-13
Context
Decision
Rationale
Consequences
Rejected alternatives
What remains open
```

Record:

1. the three package-local Internal backed-enum catalogs;
2. exact three-string Public marker contract;
3. no universal exception or diagnostic parent interface;
4. Internal abstract-base storage and constructor shape;
5. stable ID versus improvable title/remediation/message prose;
6. exact mapping to ADR 0026's 42 semantics;
7. messages remain unprefixed and protected;
8. native code/previous/message behavior;
9. no broad wrapping or new current cause sites;
10. exact API-status consequences;
11. no shared diagnostic package, object, context bag, renderer, transport, logger, telemetry, retry, or cleanup implementation;
12. persistent-runtime neutrality and absence of ambient state.

Rejected alternatives must include:

```text
one universal Kongono exception/diagnostic interface
a new shared diagnostics package
returning Internal enum cases from Public exception methods
using Exception::getCode()
prefixing or parsing getMessage()
duplicating ID/title/remediation strings at every throw site
an arbitrary structured context array
attributes or reflection-based lookup
JSON/YAML runtime catalogs
logging or rendering from exceptions
implementing the development error page in this phase
```

What remains open must include structured safe context/redaction types, documentation lookup, renderer/package ownership, HTTP/CLI mappings, incident references/storage, localization, source excerpts, logging/telemetry integration, retry/cancellation types, and cleanup failure representation.

Add ADR 0027 to `docs/adr/README.md` as item 27 with the exact title.

Update ADR 0026 narrowly to state that Phase 0M implements package-local ID/title/remediation accessors and Internal catalogs while all other open mechanisms remain open. Do not rewrite its accepted taxonomy or assignments.

---

# 15. DOCUMENTATION RECONCILIATION

Modify only the 15 documents listed in Section 5.

## Root and architecture documents

Update `README.md`, `ARCHITECTURE.md`, and `docs/architecture/overview.md` so they state:

- Phase 0M implements package-local runtime diagnostic metadata for current deliberate failures;
- exact IDs, titles, and remediation are accessible through package exception markers;
- three Internal catalogs and no universal catch interface exist;
- existing messages remain protected and unchanged;
- no renderer, error page, HTTP/CLI mapping, logging, telemetry, retry, cleanup, or incident system exists.

Update `docs/doctrine.md` from future-only identifier wording to the exact implemented boundary. Keep all broader actionable-diagnostic principles locked and future mechanisms honest.

## Security and documentation standard

Update `SECURITY.md` to distinguish fixed catalog metadata from unsafe caller-bearing messages. Runtime access to an ID/title/remediation does not authorize production disclosure of the message or stack.

Update `docs/documentation-standard.md` to state that current deliberate package diagnostics implement ID/title/remediation, while safe structured context and renderers remain planned. Do not imply all future packages already comply.

## Engineering documents

Update `docs/engineering/error-handling.md` comprehensively:

- current status becomes Phase 0M runtime metadata;
- exact catalogs remain authoritative and unchanged;
- show the three accessor methods;
- explain marker/base/catalog roles and API statuses;
- document protected message behavior and native cause preservation;
- add a small current PHP example that catches only one owning package marker and reads the three methods;
- preserve future renderer wireframe and mark it unimplemented;
- replace Phase 0L future-test wording with implemented test coverage;
- retain every open mechanism and non-goal.

Update `docs/engineering/testing.md` to state exact 30-type architecture inventory and diagnostic catalog/mapping coverage. Update `docs/engineering/api-status.md` to the exact 30-type inventory and role of Internal catalogs.

## Glossary and package READMEs

Update `docs/glossary.md` so Actionable Diagnostic and Diagnostic Identifier describe the Phase 0M runtime accessors, while Incident Reference and Error Boundary remain otherwise accurate.

Each package README must:

- name its Public marker's three metadata methods;
- name its Internal catalog;
- state its exact case count;
- link ADRs 0026 and 0027 plus the engineering guide;
- state that the package's deliberate throws emit the reserved identifiers;
- retain message disclosure warnings;
- state titles/remediation are diagnostic prose, not stable machine identity;
- retain all existing package behavior and non-goals.

---

# 16. PROHIBITED SCOPE

Do not implement or introduce:

```text
universal Kongono exception, diagnostic, or recovery interface
new Composer package or dependency
shared diagnostic value object, DTO, registry service, manager, provider, or factory
arbitrary actual/expected/context arrays or metadata bags
severity, retryability, HTTP status, CLI exit code, or transport codes
incident/reference IDs or persistence
error-page, renderer, template, source excerpt, stack presenter, or debug configuration
HTTP, routing, middleware, response, or problem-details behavior
CLI or console behavior
logging, telemetry, OpenTelemetry hooks, tracing, or exporters
retry, timeout, circuit breaker, cancellation, compensation, or recovery
cleanup orchestration, aggregate failure, or worker retirement implementation
localization or translation catalogs
JSON/YAML diagnostic source files
Application Graph diagnostic nodes, schema, metadata, IDs, or serialization
DI service registration or resolver integration for diagnostics
exception-message prefixing or parsing
native/autoloader/consumer exception wrapping
exception self-logging or self-rendering
new exception subclasses or changes to concrete exception files
Composer, lockfile, autoload, tooling, quality-policy, license, or version changes
unrelated refactoring, formatting, cleanup, or Phase 0N work
Git staging, commit, branch, tag, remote, push, or history mutation
```

---

# 17. VALIDATION AND ACCEPTANCE

## Pre-change verification

Record environment versions, exact Git facts, status inventories, hashes/sizes of all seven caller/accepted untracked paths, Phase 0L diff, manifest/lock hashes, source and test inventories, all current exception families/throw sites/messages, all 42 Phase 0L catalog rows, focused tests, and canonical `composer quality`.

## Focused validation

Run:

```text
syntax checks over all 93 first-party PHP/configuration files
KernelTest.php
all Application Graph unit tests
all Dependency Injection unit tests
ApiStatusConventionTest.php
PHPStan
PHP CS Fixer dry-run
Composer validation for root and all packages
Composer platform requirements
locked security audit
canonical composer quality
```

If only the unchanged advisory network/cache or ignored test-cache write is restricted, rerun the same read-only check with authorized access. Do not weaken or omit it.

## Static/direct inspection

Prove:

- exactly 30 production declarations and exact API statuses;
- exactly three enum catalogs with exact shapes and 42 cases;
- exact enum/case/ID/title/remediation catalog equality across directive, ADR 0026, ADR 0027, engineering guide, production, and tests;
- 42 globally unique identifiers across three domains;
- exactly six Kernel, eleven Graph, and twenty-six DI deliberate throw sites;
- all throw sites map to the intended semantic catalog, including two DI-1025 sites;
- no deliberate exception construction lacks an owning enum;
- every pre-Phase-0M message expression is unchanged;
- no catch/wrap/log/render/retry/context behavior was added;
- no marker cross-inheritance or universal interface exists;
- no Internal enum leaks through the Public accessor return types;
- no manifest, dependency, lock, autoload, tooling, concrete-exception, or prohibited file changes;
- all local Markdown links resolve;
- all changed/new text uses strict UTF-8 without BOM, LF, final newline, no trailing whitespace, NUL, replacement character, mojibake, or conflict marker.

## Expected final status inventory

Before populating the report, expect:

```text
Tracked modifications: exactly 34
Untracked paths: exactly 11
Total status paths: exactly 45
Staged paths: 0
```

The 34 tracked modifications are:

```text
15 documentation files
13 production source files
6 test files
```

The 11 untracked paths are:

```text
6 content files
  docs/adr/0026-actionable-diagnostics-and-error-boundaries.md
  docs/adr/0027-package-local-runtime-diagnostic-metadata.md
  docs/engineering/error-handling.md
  packages/application-graph/src/Diagnostic/GraphDiagnostic.php
  packages/dependency-injection/src/Diagnostic/DependencyInjectionDiagnostic.php
  packages/kernel/src/Diagnostic/LifecycleDiagnostic.php

5 caller records
  docs/dev_plan/kongono_phase_0L_codex_directive.md
  docs/dev_plan/kongono_phase_0M_codex_directive.md
  docs/task reports/Phase_0K.md
  docs/task reports/Phase_0L.md
  docs/task reports/Phase_0M.md
```

## Acceptance criteria

Phase 0M passes only if:

- every baseline and caller record matches;
- only the exact 38 Phase 0M content paths change;
- all accepted Phase 0L content outside the Phase 0M boundary remains byte-identical;
- three exact Internal enum catalogs implement all 42 mappings;
- three exact package markers expose only the three metadata additions;
- three exact abstract bases implement immutable metadata and native cause behavior;
- all current deliberate throws have exact semantic mappings;
- messages remain unchanged and unprefixed;
- package markers remain separate;
- native, autoloader, and consumer failures remain unwrapped and unchanged;
- all new and prior API statuses are exact;
- production count is 30, syntax inventory 93, test-file count 62, and style inventory 92;
- root tests are at least 130 with more than 567 meaningful assertions;
- focused tests, PHPStan, style, platform, manifests, audit, and canonical quality all pass;
- documentation and ADRs accurately distinguish implemented metadata from unimplemented presentation and operations;
- no prohibited capability or Git mutation exists;
- the Phase 0M report satisfies Section 18.

---

# 18. STANDARD EXECUTOR COMPLETION REPORT

Write the final report only to:

```text
docs/task reports/Phase_0M.md
```

Keep it untracked and unstaged. Put the entire report inside one outer `text` fence with no text outside it. Use exactly these 16 sections:

```text
1. STATUS
2. SUMMARY
3. FILES CREATED
4. FILES MODIFIED
5. ARCHITECTURAL DECISIONS
6. DEVIATIONS FROM DIRECTIVE
7. DEPENDENCIES ADDED/REMOVED
8. ENVIRONMENT
9. COMMANDS RUN
10. VALIDATION RESULTS
11. DOCUMENTATION REVIEW
12. WARNINGS / KNOWN ISSUES
13. QUESTIONS / BLOCKERS
14. GIT DIFF SUMMARY
15. ACCEPTANCE CRITERIA
16. NEXT STEP
```

Mandatory evidence includes:

- exact initial/final Git state and proof HEAD/index/history did not change;
- exact accepted Phase 0L and Phase 0M caller hashes/sizes;
- exact created and modified path inventories;
- exact enum cases, IDs, titles, remediation, counts, and API statuses;
- exact Public marker and Internal base reflection shapes;
- all throw-site mappings and message-preservation proof;
- cause identity/code/message proof;
- native/autoload/consumer propagation proof;
- exact source/test/syntax/style inventories;
- exact test counts/assertions and all tool results;
- catalog equality, uniqueness, link, encoding, and prohibited-scope audits;
- confirmation of zero dependency/manifest/lock/tooling/Git changes;
- final report byte count and SHA-256 in the accompanying executor response after its last write, while the report notes why it cannot contain its own final digest;
- `None` for decisions, deviations, dependencies, or blockers when none exist;
- line-by-line PASS/FAIL acceptance;
- Section 16 exactly: `Await architect review before Phase 0N.`

After populating the report, validate its one-fence structure, exact section order, strict UTF-8, LF, final newline, and final unstaged status. Do not stage or commit it.

---

# 19. FINAL EXECUTOR INSTRUCTION

Implement only package-local runtime diagnostic metadata for the 42 already accepted deliberate failure semantics. Preserve package recovery boundaries, message behavior, native causes, security boundaries, and persistent-runtime neutrality.

Do not build the error page or any operational error-handling system. Do not begin Phase 0N. Stop rather than infer any shared diagnostic abstraction, structured context, renderer, transport, logging, telemetry, retry, cleanup, localization, or incident decision.
