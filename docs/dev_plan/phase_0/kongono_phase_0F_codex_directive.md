# CODEX IMPLEMENTATION DIRECTIVE — KONGONO PHASE 0F

## Explicit Typed Dependency Resolution Core

## Mission

Implement Kongono's first deliberately narrow typed dependency-injection boundary as a third physical monorepo package.

Implement only:

- `kongono/dependency-injection`;
- explicit immutable service definitions;
- interface contracts mapped to concrete implementations;
- explicit ordered constructor dependencies;
- complete definition-set validation;
- deterministic missing-definition and cycle rejection;
- transient object construction with no service-instance cache;
- one package-specific exception boundary;
- exact API-status classifications;
- production tests, Composer/autoload/tooling expansion, documentation, and one ADR.

This phase explicitly authorizes the smallest useful dependency-resolution mechanism. It does not authorize a general container platform.

This phase does **not** implement or select:

- Kernel-owned dependency injection or a Kernel/container relationship;
- PSR-11 or another container interface in Phase 0F; future interoperability remains unselected rather than permanently rejected;
- reflection autowiring or dependency discovery;
- factories, callables, configuration values, scalar injection, aliases, names, qualifiers, tags, decorators, lazy services, proxies, or compiled containers;
- singleton, shared, operation, request, worker, process, or custom service scopes;
- cleanup, reset, disposal, context propagation, cancellation, or runtime adapters;
- Kongono-provided service-locator access, a global container, or an ambient current resolver;
- Application Graph dependency kinds, schema, metadata, discovery, compilation, or integration;
- HTTP, routing, middleware, ORM, data access, authentication, authorization, events, queues, workflows, agents, extensions, CLI, frontend, telemetry, logging, or any later framework capability.

Do not begin Phase 0G.

---

# 1. ROLE, AUTHORITY, AND STOP RULE

You are the implementation executor, not the architect.

Before editing, read completely:

1. `AGENTS.md`
2. `README.md`
3. `ARCHITECTURE.md`
4. `CONTRIBUTING.md`
5. `SECURITY.md`
6. `docs/doctrine.md`
7. `docs/documentation-standard.md`
8. `docs/glossary.md`
9. every file under `docs/architecture/`
10. every file under `docs/engineering/`
11. `docs/adr/README.md`
12. ADRs 0001–0022
13. the accepted Phase 0C and Phase 0D directives and reports
14. the Phase 0E directive, both Phase 0E corrections, and both blocked reports
15. the Phase 0E.2 completion report
16. all root and package Composer manifests and tooling configuration
17. all existing production source and tests
18. this Phase 0F directive

The following decisions are architect-authorized by this directive:

- the `kongono/dependency-injection` package boundary;
- the exact production symbols and API statuses in this directive;
- canonical existing PHP interface names as the only service identities in Phase 0F;
- explicit concrete-class implementations and explicit ordered interface dependencies;
- reflection only for validating supplied type names, implementation compatibility, and exact constructor signatures;
- transient construction on every dependency edge;
- immutable definitions and resolver state;
- the exception behavior, tooling changes, documentation changes, and ADR 0023 specified here.

Earlier documents that prohibited dependency injection were correct for their phases. This directive supersedes those prohibitions only for the exact Phase 0F boundary described here. It does not reopen any other earlier decision.

## Mandatory stop rule

If any required behavior, package boundary, public API, type identity, constructor rule, lifecycle rule, exception rule, dependency rule, security rule, compatibility rule, or repository-state requirement is unclear or conflicts with another authority:

```text
STOP
ASK
DO NOT GUESS
```

Report:

1. the exact decision required;
2. why safe progress depends on it;
3. viable options;
4. tradeoffs of each option;
5. a clearly labeled recommendation.

Do not implement the recommendation without architect approval.

Do not use “reasonable,” “standard DI behavior,” a third-party container, PSR-11, Symfony, Laravel, PHP-DI, or another framework as missing authority. This directive is the contract.

## Conflict rule

When two repository authorities appear inconsistent:

1. identify both exact passages;
2. determine whether this directive explicitly supersedes the older phase-limited rule;
3. continue only work that cannot prejudice resolution;
4. stop before any disputed mutation.

Newer text is not automatically an override. Only explicit scope language authorizes supersession.

---

# 2. ACCEPTED BASELINE AND REPOSITORY-STATE SAFETY

Phase 0E.2 established the accepted baseline:

```text
Branch: main
Branch count: 1
Commit count: 1
HEAD: 136822352a0caac199506cbddb4c9a539001c592
Tree: 88d0f199e95e6891739df5c4a4d7cc139ab12cae
Parent count: 0
Subject: Establish Kongono framework baseline through Phase 0D
Tracked files: 104
Tracked modes: 104 × 100644
Remotes: 0
Tags: 0
```

The index and tracked worktree were clean at Phase 0F authoring time.

Three caller-managed untracked records existed:

```text
docs/task reports/Phase_0E_2_correction.md
docs/dev_plan/kongono_phase_0F_codex_directive.md
docs/task reports/Phase_0F.md
```

Classify them exactly:

### Prior Phase 0E.2 report

```text
Path: docs/task reports/Phase_0E_2_correction.md
Expected size: 11,603 bytes
Expected SHA-256: D6DE57DE2A2C2087156784EB5DBF35E48B565A43C6FB4E85763BB52A50DCF6E4
Role: caller-managed copy of the accepted Phase 0E.2 executor report
```

Verify the size and hash. Preserve the file byte-for-byte. Do not edit, stage, commit, rename, or delete it.

### Current Phase 0F directive

```text
Path: docs/dev_plan/kongono_phase_0F_codex_directive.md
Role: caller-supplied architect directive for this execution
```

The zero-byte placeholder must have been populated by the caller with this directive before execution. Verify that it is non-empty and begins with the exact title in this file. Read it in full. Do not edit, stage, commit, rename, or delete it.

### Current Phase 0F report placeholder

```text
Path: docs/task reports/Phase_0F.md
Expected pre-execution size: 0 bytes
Role: caller-managed placeholder for the report returned after this execution
```

Preserve it exactly. Do not populate, edit, stage, commit, rename, or delete it. Return the completion report to the caller in your response. The caller will archive it afterward.

If the Phase 0F report path is non-empty before execution, stop and report its size and SHA-256. Do not overwrite a report that appears to predate its execution.

## Preflight requirements

Before any mutation:

1. verify the exact branch, commit, tree, parent, subject, tracked-file count, modes, remotes, and tags above;
2. verify `git diff --exit-code` passes;
3. verify `git diff --cached --exit-code` passes;
4. verify no tracked deletion or modification exists;
5. verify the three caller-managed paths are the only untracked paths;
6. verify the Phase 0E.2 report hash;
7. verify the Phase 0F directive and report-placeholder classifications;
8. record PHP, Composer, Git, PHPUnit, PHPStan, and PHP CS Fixer versions;
9. run the full existing `composer quality` gate and require it to pass;
10. record a pre-change SHA-256 for every tracked file and all three caller records in a task-specific temporary location outside the repository;
11. record the locked Composer package inventory and versions for later comparison.

Stop before editing if the accepted baseline does not match or the existing quality gate fails for a repository defect.

An environmental network or sandbox denial is not permission to skip a required audit. Report it and use the authorized environment/approval mechanism if available. Never weaken Composer policy or substitute an unlocked audit.

## Git restrictions

Phase 0F authorizes worktree changes only.

Do not:

- stage or unstage anything;
- commit or amend;
- reset, restore, checkout, clean, stash, or rebase;
- create, rename, switch, or delete a branch;
- create a remote or tag;
- push;
- change local or global Git configuration;
- alter hooks or signing configuration;
- use destructive Git commands to obtain a clean state.

At completion, HEAD, the tree reachable from HEAD, the branch set, the index, remotes, and tags must remain identical to the accepted baseline. Only authorized Phase 0F worktree files and the three preserved caller records may appear in status.

---

# 3. LOCKED ARCHITECTURAL CONTEXT

Preserve all accepted Kongono doctrine.

In particular:

1. Kongono remains PHP 8.5+ and pre-alpha.
2. The microkernel remains small and runtime-neutral.
3. `kongono/kernel` remains a lifecycle package, not a container, resolver, registry, service locator, or object factory.
4. Dependency injection is implemented as an independently owned component.
5. Persistent-runtime safety forbids hidden shared service state and request-per-process assumptions.
6. The Application Graph remains language-neutral and authoritative in planned scope, but Phase 0F creates no Graph schema or Graph integration.
7. AI may assist, but deterministic Kongono mechanisms own correctness-critical definitions.
8. Every production class-like declaration has one explicit API status except the two accepted bootstrap metadata types.
9. Public API status is compatibility metadata, not authorization or runtime access control.
10. No framework capability is inferred from a package name, a transitive dependency, or a planned doctrine statement.
11. Public APIs create compatibility obligations; the smallest correct surface is required.

The new package's production source may statically depend on `kongono/kernel` only for the accepted `Api` and `ApiStatus` metadata. It must not import, invoke, inspect, special-case, or otherwise depend on Kernel lifecycle behavior. The root-owned cross-package architecture test and package-marker assertions explicitly authorized later are test-only policy checks, not production dependencies.

The new package must not depend on `kongono/application-graph`, and the Application Graph package must not depend on it.

---

# 4. PHASE 0F BOUNDARY — LOCKED

Create exactly one new production package:

```text
Composer package: kongono/dependency-injection
Production namespace: Kongono\DependencyInjection\
Test namespace: Kongono\DependencyInjection\Tests\
Physical path: packages/dependency-injection/
PHP: >=8.5
Package type: library
API maturity: Experimental except the Public exception marker and Internal base
```

The package owns only:

- immutable explicit service definitions;
- validation of supplied interface and implementation names;
- validation of exact constructor dependency signatures;
- a complete immutable map of definitions;
- missing-definition validation;
- dependency-cycle validation;
- transient recursive construction;
- package-specific deliberate failures.

It owns no configuration system, application registry, discovery mechanism, source scanner, Graph metadata, service lifetime beyond transient construction, or runtime integration.

The term “dependency injection” in this phase means only constructor injection from explicit definitions. It does not imply a complete container ecosystem.

---

# 5. EXACT PACKAGE LAYOUT AND MANIFEST

Create exactly:

```text
packages/dependency-injection/
├── composer.json
├── README.md
├── src/
│   ├── Contract/
│   │   └── ResolverInterface.php
│   ├── Definition/
│   │   └── ServiceDefinition.php
│   ├── Exception/
│   │   ├── DependencyInjectionException.php
│   │   ├── ExceptionInterface.php
│   │   ├── InvalidDefinitionException.php
│   │   └── UnknownServiceException.php
│   └── DependencyResolver.php
└── tests/
    ├── Fixture/
    │   └── [strictly test-only fixture declarations]
    └── Unit/
        ├── DependencyResolverTest.php
        └── ServiceDefinitionTest.php
```

Create only the fixture declarations required to express the Section 12 cases. Every fixture file must:

- live directly under `packages/dependency-injection/tests/Fixture/`;
- use `Kongono\DependencyInjection\Tests\Fixture\`;
- contain exactly one named class-like declaration;
- use a filename matching that declaration under PSR-4;
- contain only the minimal interface, constructor, identity state, or deliberate throw needed by a required test;
- carry no production `Api` metadata;
- expose no filesystem, network, environment, time, randomness, process, Kernel, Graph, or other unrelated behavior.

Descriptive fixture names are test implementation details and are explicitly delegated to the executor. This is not authority to create another production type or test category.

Do not use `eval`, runtime code generation, anonymous types as substitutes for canonical valid fixtures, multi-declaration fixture files, Composer `files` autoload, or test classmaps. The existing test PSR-4 convention must remain strict and optimized-autoload-safe.

Do not add another production or test path.

The package manifest contract is:

```json
{
  "name": "kongono/dependency-injection",
  "description": "Explicit typed dependency resolution for Kongono Framework.",
  "type": "library",
  "require": {
    "kongono/kernel": "*",
    "php": ">=8.5"
  },
  "autoload": {
    "psr-4": {
      "Kongono\\DependencyInjection\\": "src/"
    }
  },
  "autoload-dev": {
    "psr-4": {
      "Kongono\\DependencyInjection\\Tests\\": "tests/"
    }
  }
}
```

Do not add a package version, license, scripts, binaries, repositories, plugin controls, minimum-stability setting, configuration, extra metadata, or external dependency.

The `kongono/kernel: *` constraint is the same temporary unreleased-monorepo policy already used by `kongono/application-graph`. Phase 0F extends that temporary policy only to this package. It does not settle independent package versioning.

---

# 6. SERVICE IDENTITY AND TYPE RULES — LOCKED

## Service contract

A Phase 0F service contract is exactly the canonical declared fully qualified name of an existing PHP interface.

Requirements:

- it is supplied as a string;
- normal registered PHP autoloading may load the named interface;
- `interface_exists()` must succeed;
- reflection must report an interface;
- the supplied string must exactly equal `ReflectionClass::getName()`;
- it contains no leading `\`;
- its namespace and identifier case exactly match the declaration;
- it is not a class alias;
- it is not an abstract or concrete class, trait, enum, callable, object instance, scalar key, arbitrary alias, or package coordinate.
- it is not `Kongono\DependencyInjection\Contract\ResolverInterface`, nor does it extend that interface.

The resolver contract is a composition-root API, not a resolvable application service. Excluding it and all subinterfaces as contracts or dependencies, together with rejecting every implementation for which `is_a($implementation, ResolverInterface::class, true)` is true, prevents a definition from injecting Kongono resolver access under either its declared identity or another contract.

PHP resolves type names case-insensitively, but Kongono requires the canonical declared spelling so identity remains deterministic and duplicate definitions cannot hide behind alternate case.

## Service implementation

A Phase 0F service implementation is exactly the canonical declared fully qualified name of an existing concrete instantiable PHP class.

Requirements:

- `class_exists()` must succeed through normal autoloading;
- the supplied string exactly equals `ReflectionClass::getName()`;
- it has no leading `\`, case variation, or class alias;
- `ReflectionClass::isInstantiable()` is true;
- reflection reports it as not anonymous;
- it implements the declared service contract;
- `is_a($implementation, ResolverInterface::class, true)` is false, so neither `ResolverInterface` nor a class implementing it can enter the resolved object graph under another contract;
- it is not an interface, abstract class, trait, enum, anonymous-class alias, or non-publicly constructible class.

## Dependency identity

Every declared dependency is another service contract under the exact rules above.

Dependencies are an ordered zero-based list. Position is semantic: dependency position `n` maps to constructor parameter position `n`.

The same dependency contract may appear more than once. Each occurrence is a distinct transient dependency edge and receives a newly constructed object.

Do not normalize, lowercase, trim, alias, infer, or rewrite any supplied type name.

---

# 7. `ServiceDefinition` CONTRACT — LOCKED

Create:

```text
Kongono\DependencyInjection\Definition\ServiceDefinition
```

It is:

- `final`;
- `readonly`;
- `#[Api(ApiStatus::Experimental)]`;
- instance-owned;
- free of static mutable state.

Its exact public contract is:

```php
/**
 * @param class-string              $contract
 * @param class-string              $implementation
 * @param list<class-string>        $dependencies
 *
 * @throws InvalidDefinitionException
 * @throws \Throwable when registered autoloading fails
 */
public function __construct(
    string $contract,
    string $implementation,
    array $dependencies,
);

/** @return class-string */
public function contract(): string;

/** @return class-string */
public function implementation(): string;

/** @return list<class-string> */
public function dependencies(): array;
```

Use precise PHPStan-compatible PHPDoc formatting; do not weaken native parameter or return types.

The object contains exactly three private instance properties corresponding to those values. It preserves the canonical strings and dependency order exactly.

Validate the dependency array's list shape and each raw entry's string runtime type first. Immediately copy those strings value-by-value into a new zero-based list so caller-held element references are detached. Perform every interface, canonical-name, and constructor-signature check against that detached list, then assign only the detached validated list. This order is mandatory because registered autoloading may execute external code; no caller-held reference may change a value between semantic validation and storage. A readonly array property is not sufficient by itself because PHP arrays may contain references. Mutating the caller's original array, a variable referenced by an original array element, or an array returned by `dependencies()` must not change the definition or a later `dependencies()` result.

## Definition validation

Construction must reject through `InvalidDefinitionException`:

1. a nonexistent contract;
2. a contract that is not an interface;
3. a noncanonical, differently cased, leading-backslash, or aliased contract name;
4. a nonexistent implementation;
5. a noncanonical, differently cased, leading-backslash, or aliased implementation name;
6. an interface, abstract class, trait, enum, or otherwise non-instantiable implementation;
7. an implementation that does not implement the declared contract;
8. a dependency array that is not a zero-based consecutive list;
9. a dependency entry that is not a string;
10. a nonexistent or non-interface dependency;
11. `ResolverInterface` or any subinterface as contract or dependency;
12. an implementation class for which `is_a($implementation, ResolverInterface::class, true)` is true, even when the declared service contract is unrelated;
13. a noncanonical, differently cased, leading-backslash, or aliased dependency name;
14. an implementation constructor whose parameter count differs from the dependency count;
15. a non-public constructor;
16. an optional, defaulted, variadic, or by-reference constructor parameter;
17. an untyped constructor parameter;
18. a builtin, nullable, union, intersection, `mixed`, `object`, `self`, or `parent` parameter;
19. a constructor parameter whose exact named interface type differs from the declared dependency at that position.

An implementation with no constructor is valid only with an empty dependency list. A public zero-parameter constructor is valid only with an empty dependency list.

A public inherited constructor is valid when its reflected signature satisfies every rule above. Constructor declaration ownership does not change the positional dependency contract.

Reflection is authorized only to validate these explicitly supplied names and signatures. Do not inspect constructor bodies, parameter names, attributes, default values as configuration, source files, docblocks, or unrelated methods.

Do not infer dependencies from constructor types. The supplied dependency list is authoritative, and reflection only checks that the explicit list agrees exactly with the constructor.

If a registered autoloader throws while a supplied definition name is being checked, propagate that exact failure unchanged. It is an external autoload failure, not a deliberate invalid-definition result. The `InvalidDefinitionException` rules above apply when normal autoload and reflection checks complete normally.

---

# 8. RESOLVER CONTRACT AND BEHAVIOR — LOCKED

## `ResolverInterface`

Create:

```text
Kongono\DependencyInjection\Contract\ResolverInterface
```

It is `#[Api(ApiStatus::Experimental)]`, extends no other interface, declares no constants, and declares exactly one public method:

```php
/**
 * @template T of object
 *
 * @param class-string<T> $contract
 *
 * @return T
 *
 * @throws UnknownServiceException
 * @throws InvalidDefinitionException if a constructed object violates the validated definition invariant
 * @throws \Throwable when registered autoloading or the resolved implementation constructor fails
 */
public function resolve(string $contract): object;
```

Use an equivalent PHPStan-valid method template if formatting must change. Do not change the semantic contract or add methods.

The broad `Throwable` declaration documents propagation, not a package wrapping policy. `UnknownServiceException` is the expected requested-contract failure. `InvalidDefinitionException` is authorized only as the defensive post-construction invariant failure described below; it is not an alternate unknown-service result.

This resolver is intended for an explicit application composition root that requests top-level contracts. `ResolverInterface` and its subinterfaces cannot be defined or injected through this package. Passing a resolver into resolved services by another mechanism or exposing it as ambient application state is unsupported service-locator use.

There is no `get()`, `has()`, `make()`, `build()`, `register()`, `set()`, `bind()`, `definitions()`, or additional service-locator shortcut.

## `DependencyResolver`

Create:

```text
Kongono\DependencyInjection\DependencyResolver
```

It is:

- `final`;
- `readonly`;
- `#[Api(ApiStatus::Experimental)]`;
- an implementation of `ResolverInterface`;
- instance-owned;
- immutable after successful construction.

Its exact public contract is:

```php
/**
 * @param list<ServiceDefinition> $definitions
 *
 * @throws InvalidDefinitionException
 */
public function __construct(array $definitions);

/**
 * @template T of object
 *
 * @param class-string<T> $contract
 *
 * @return T
 *
 * @throws UnknownServiceException
 * @throws InvalidDefinitionException if a constructed object violates the validated definition invariant
 * @throws \Throwable when registered autoloading or the resolved implementation constructor fails
 */
public function resolve(string $contract): object;
```

Only those two public methods are authorized.

## Resolver construction

The resolver constructor must:

1. require a zero-based consecutive runtime list;
2. reject any non-`ServiceDefinition` entry;
3. accept an empty list;
4. create a private contract-to-definition map;
5. reject more than one definition for the same exact contract;
6. require a definition for every dependency named by every definition;
7. reject direct self-cycles;
8. reject every indirect dependency cycle;
9. validate the complete definition graph before publishing the resolver;
10. retain definitions only, never resolved service instances.

Invalid list structure, element types, duplicates, missing dependency definitions, or cycles throw `InvalidDefinitionException`.

Definition input order must not affect successful resolution behavior or which canonical contract graph is stored. Sort or otherwise process definitions in binary `strcmp()` contract order before cross-definition validation. Dependency order inside one definition remains its declared constructor order.

When multiple invalid graph conditions exist, validation must be deterministic. Apply this cross-definition order:

1. runtime list shape;
2. runtime element types;
3. canonical contract ordering;
4. duplicate contracts;
5. missing dependency definitions, by canonical owning contract then dependency position;
6. cycles, by canonical root contract then declared dependency order.

Error text must identify the failure category and relevant contract names, but exact wording is not a Stable compatibility contract.

## Resolution

`resolve()` must:

1. require the requested name to be an existing canonical interface name under Section 6;
2. throw `UnknownServiceException` if the name is invalid, noncanonical, not an interface, or has no definition;
3. recursively resolve dependencies in the exact declared order;
4. instantiate the explicit implementation with positional constructor arguments;
5. return an object implementing the requested interface;
6. perform no definition mutation or service-instance retention.

The concrete method must repeat the interface method's generic PHPDoc. The final constructed object must be checked against the requested contract before return so both runtime behavior and PHPStan can justify `T`. A post-construction mismatch is a violated validated-definition invariant and must throw `InvalidDefinitionException`; it must not return a wrong object. Do not add a public API solely to satisfy analysis.

If a registered autoloader throws while a supplied name is being checked, propagate that exact failure unchanged. Do not misclassify external autoloader failure as `InvalidDefinitionException` or `UnknownServiceException`. The package-specific invalid/unknown rules apply when normal autoload and reflection checks complete normally.

Transient semantics are exact:

- every top-level `resolve()` call creates a fresh root instance;
- every dependency-list occurrence creates a fresh instance;
- repeated use of one contract in the same constructor creates distinct instances;
- a diamond graph creates separate lower-level instances for each dependency edge;
- no instance is memoized within a resolution call;
- no instance is cached between resolution calls;
- no singleton or shared identity promise exists.

The complete definition set is cycle-free before resolution, so runtime recursion must not introduce an alternate cycle policy.

If an implementation constructor throws any `Throwable`, propagate that exact failure unchanged. Do not wrap it merely to place consumer/application behavior inside the dependency-injection exception family. Do not retry construction.

The resolver itself performs no filesystem, network, environment, time, randomness, logging, telemetry, process, Application Graph, or global-state access. Beyond its static API-status metadata import, it performs no Kernel-specific import, inspection, invocation, special case, or lifecycle access. Generic validation and construction of a consumer-supplied type—including a Kernel interface explicitly selected by the consumer—remain allowed under Section 12. Normal PHP autoloading and the side effects of consumer constructors are outside this guarantee and must be documented honestly.

---

# 9. EXCEPTION FOUNDATION — LOCKED

Create exactly:

```text
Kongono\DependencyInjection\Exception\ExceptionInterface
Kongono\DependencyInjection\Exception\DependencyInjectionException
Kongono\DependencyInjection\Exception\InvalidDefinitionException
Kongono\DependencyInjection\Exception\UnknownServiceException
```

The hierarchy is exact:

```text
Throwable
└── ExceptionInterface                         Public marker

RuntimeException
└── DependencyInjectionException              Internal abstract base
    ├── InvalidDefinitionException             Experimental final
    └── UnknownServiceException                Experimental final
```

Implementation contract:

- `ExceptionInterface` extends `Throwable` and contains no methods.
- `DependencyInjectionException` extends `RuntimeException`, implements the marker, and is abstract.
- both concrete exceptions are final.
- no exception adds state, error codes, context arrays, rendering, localization, logging, telemetry, retry, transport, serialization, or HTTP semantics.
- every failure deliberately raised by this package implements its own marker.
- none of these exceptions implements the Kernel or Application Graph marker.
- native engine failures, registered-autoloader failures, and constructor-thrown consumer failures are not broadly wrapped.

Use `InvalidDefinitionException` only for invalid definition objects, definition lists, definition graphs, or the defensive post-construction violation of an already validated definition's contract.

Use `UnknownServiceException` only when `resolve()` receives a contract that cannot be resolved under the exact Phase 0F contract.

---

# 10. PERSISTENT-RUNTIME, OWNERSHIP, AND CONCURRENCY RULES

The package must be safe to retain in a persistent process without retaining operation-specific service objects.

Requirements:

1. `ServiceDefinition` and `DependencyResolver` are immutable after construction.
2. Resolver state contains definitions only.
3. No resolved object is stored in a property, static variable, singleton, cache, weak map, object storage, registry, global, thread-local, fiber-local, or ambient context.
4. No “current container” or “current resolver” exists.
5. Overlapping calls on the same resolver share only immutable definitions, not resolved instances.
6. Resolution has no Kongono-supplied or Kernel-specific relationship to `Kernel`, `OperationScope`, boot, shutdown, or active-operation accounting; generic handling of a consumer-selected Kernel interface creates no special lifecycle behavior.
7. The package defines no cleanup, disposal, reset, close, shutdown, cancellation, or failure-recovery hook.
8. The package does not claim thread safety for arbitrary consumer constructors.
9. Constructor side effects and lifecycle belong to the consumer implementation; Phase 0F does not govern them.

Transient-only behavior is a deliberate safe boundary, not a default lifetime system. All shared and scoped lifetime decisions remain open.

---

# 11. API STATUS — LOCKED

Apply the existing `#[Api(...)]` convention exactly.

Add these exact classifications to the root-owned architecture inventory:

```text
Kongono\DependencyInjection\Contract\ResolverInterface
    Experimental

Kongono\DependencyInjection\Definition\ServiceDefinition
    Experimental

Kongono\DependencyInjection\DependencyResolver
    Experimental

Kongono\DependencyInjection\Exception\ExceptionInterface
    Public

Kongono\DependencyInjection\Exception\DependencyInjectionException
    Internal

Kongono\DependencyInjection\Exception\InvalidDefinitionException
    Experimental

Kongono\DependencyInjection\Exception\UnknownServiceException
    Experimental
```

Do not change the status of any existing production type.

After Phase 0F, the exact repository production inventory is 27 class-like declarations:

- the existing 20 Kernel and Application Graph types;
- the seven types above.

`Api` and `ApiStatus` remain the only bootstrap exceptions to self-classification.

No new Public type is authorized except the dependency-injection package exception marker. Public does not mean Stable or production-ready.

Do not add per-member API metadata, Stable, Deprecated, package-level status, Graph metadata, authorization behavior, or visibility enforcement.

---

# 12. REQUIRED PRODUCTION TESTS

Use PHPUnit 13 and the package test namespace. Test behavior, not private implementation collections.

## `ServiceDefinitionTest`

Cover at minimum:

1. a canonical interface, compatible concrete implementation, and empty dependency list;
2. exact preservation of contract, implementation, and dependency order;
3. mutation of the caller array does not alter the definition;
4. mutation through a caller-held referenced element does not alter the definition;
5. mutation of a returned dependency array does not alter a later result;
6. final and readonly model status;
7. exact private, non-static `contract`, `implementation`, and `dependencies` property inventory with no retained reflection/cache state;
8. exact public `__construct()`, `contract()`, `implementation()`, and `dependencies()` method inventory;
9. nonexistent contract rejection;
10. class-as-contract rejection;
11. leading-backslash, wrong-case, and class-alias contract rejection;
12. `ResolverInterface` and a subinterface are rejected as contracts;
13. nonexistent implementation rejection;
14. leading-backslash, wrong-case, and class-alias implementation rejection;
15. abstract, interface, trait, enum, anonymous, private-constructor, and otherwise non-instantiable implementation rejection where expressible;
16. implementation/contract mismatch rejection;
17. rejection of a dual-interface implementation fixture that implements both an otherwise valid consumer contract and `ResolverInterface`;
18. associative and gapped dependency-array rejection;
19. non-string dependency-entry rejection through a runtime boundary;
20. nonexistent and non-interface dependency rejection;
21. `ResolverInterface` and a subinterface are rejected as dependencies;
22. leading-backslash, wrong-case, and class-alias dependency rejection;
23. constructor/dependency count mismatch rejection;
24. non-public constructor rejection;
25. optional, defaulted, variadic, and by-reference parameter rejection;
26. untyped and builtin parameter rejection;
27. nullable, union, intersection, `self`, and `parent` parameter rejection where expressible;
28. exact parameter/dependency interface mismatch rejection;
29. no-constructor, zero-parameter, and valid inherited-constructor definitions;
30. a registered-autoloader failure propagates unchanged and is not wrapped;
31. every deliberate failure is catchable through the package marker and not through the Kernel marker.

Use focused test fixtures. Test-only fixtures do not carry production API metadata and must remain outside `src/`.

## `DependencyResolverTest`

Cover at minimum:

1. an empty definition list is valid;
2. associative and gapped definition lists are rejected;
3. non-definition list entries are rejected through a runtime boundary;
4. duplicate contract definitions are rejected;
5. missing transitive dependency definitions are rejected;
6. direct self-cycles are rejected;
7. two-node and longer indirect cycles are rejected;
8. equivalent invalid sets containing multiple missing dependencies select the same canonical failure when definition input order is reversed;
9. equivalent invalid sets containing multiple disjoint cycles select the same canonical root/category when definition input order is reversed;
10. equivalent valid definition sets behave the same regardless of input order;
11. a dependency-free service resolves to its explicit implementation;
12. ordered constructor dependencies resolve into the correct positions;
13. nested acyclic dependencies resolve;
14. each top-level resolution returns a distinct root instance;
15. duplicate dependency edges receive distinct instances;
16. a diamond graph does not share its lower dependency;
17. nonexistent, class, aliased, leading-backslash, wrong-case, and undefined requested contracts throw `UnknownServiceException`;
18. resolution returns an object satisfying the requested interface;
19. an implementation-constructor exception propagates unchanged and is not wrapped;
20. repeated resolution after a constructor failure performs a new construction attempt and retains no partial object;
21. the resolver is final and readonly and implements `ResolverInterface`;
22. the resolver exposes exactly `__construct()` and `resolve()` as public methods;
23. the interface exposes exactly `resolve()`;
24. a registered-autoloader failure during requested-contract checking propagates unchanged.

Do not inspect the resolver's private definition map or recursion state. Use public behavior plus reflection only for explicit final/readonly/interface/public-method assertions.

Reflection is also allowed in tests to invoke an authorized public constructor or `resolve()` with a deliberately invalid runtime value that cannot be expressed honestly through the declared `class-string` or `list` PHPDoc contract. Do not use it to invoke non-public production behavior or inspect private storage.

An autoloader-failure test must use a unique test-only type name, register the narrowest possible throwing autoloader, and unregister that exact callable in a `finally` block. It must not change Composer autoload configuration or leak process-global test state.

Do not assert exact full exception messages. Assert exception class, marker membership, relevant category, and relevant contract name.

Do not introduce skipped, incomplete, warning-producing, risky, or output-producing tests.

Package tests must not import or reference Application Graph production types. Verify dependency-injection/Graph exception-marker separation in the root-owned architecture test, where cross-package policy already belongs. The new package's direct tests verify its own marker and its authorized Kernel dependency boundary only.

Consumer definitions may use another existing interface—including a Kernel lifecycle interface—as an ordinary consumer-selected contract when all Phase 0F definition rules pass. That does not make the resolver Kernel-aware: it applies no special behavior, imports no lifecycle API, and creates no Kernel integration contract. Tests must not add a special Kernel binding merely to imply integration.

The defensive post-construction contract-mismatch branch is unreachable through any valid immutable definition and must not be fabricated through private mutation, serialization, mocks, source rewriting, or invalid test state. Section 17 direct source inspection and PHPStan must verify that the `instanceof` guard and `InvalidDefinitionException` branch exist. Likewise, Kernel-lifecycle and Graph-import absence belongs to Section 17 source/dependency audit, not a filesystem-scanning package unit test.

---

# 13. COMPOSER, AUTOLOAD, LOCKFILE, AND TOOLING EXPANSION

Modify the root tooling so the new package participates everywhere existing packages participate.

## Root Composer manifest

Add:

```json
"kongono/dependency-injection": "@dev"
```

to root production requirements.

Add:

```json
"Kongono\\DependencyInjection\\Tests\\": "packages/dependency-injection/tests/"
```

to root `autoload-dev.psr-4`.

Composer does not import a dependency package's `autoload-dev`; both the package-local and root mappings are required.

Update `validate:packages` to run exactly in lexicographic package-directory order:

```text
packages/application-graph
packages/dependency-injection
packages/kernel
```

Do not change the remaining `composer quality` stage order:

```text
validate:composer
validate:packages
audit:locked
platform
cs
analyse
test
```

## Lockfile

Composer alone must update `composer.lock`.

Use a targeted Composer update for the newly required local package, with minimal changes and normal security policy. One acceptable command is:

```bash
composer update kongono/dependency-injection --with-dependencies --minimal-changes --no-interaction
```

If Composer requires a materially different command, stop and explain why before broad dependency resolution.

The resulting lock change may add exactly one local production package: `kongono/dependency-injection`.

Requirements:

- existing external package versions, source/dist references, and integrity data remain unchanged;
- existing `kongono/kernel` and `kongono/application-graph` lock records remain semantically unchanged except root-content metadata Composer must regenerate;
- no external production dependency is introduced;
- no Composer plugin is introduced or authorized;
- `allow-plugins` remains empty;
- security policy remains fail-closed;
- no repository, mirror, credential, platform-ignore, stability, or minimum-stability change occurs;
- the transitive development presence of `psr/container` does not authorize production use or a `Psr\Container` reference.

Do not edit `composer.lock` manually.

## PHPStan

Add exactly:

```text
packages/dependency-injection/src
packages/dependency-injection/tests
```

to analysis paths.

Keep PHPStan 2.2, PHP 8.5.0 analysis, maximum level, strict rules, no baseline, no ignored errors, no inline suppression, and no exclusion.

## PHP CS Fixer

Add the new package `src` and `tests` directories to the existing finder.

Do not change the ruleset, risky-rule decision, cache policy, or exclude any new file.

## PHPUnit

Add the new package test directory to the root test suite and its production source directory to the source include.

Do not weaken any failure setting or add coverage claims, process isolation, ordering dependencies, skips, groups that avoid the normal suite, or another runner.

## API architecture test

Keep `tests/Architecture/ApiStatusConventionTest.php` at the root. Add the exact seven-type status map from Section 11. Do not move or duplicate the scanner.

Extend this same root-owned cross-package test to verify that all three package exception markers are pairwise distinct and that each concrete deliberate exception belongs only to its own package marker. This test-only cross-package assertion creates no production dependency and does not authorize package tests or production code to import sibling packages.

## Autoload verification

Verify:

1. the development autoloader resolves all seven new production types;
2. the development autoloader resolves both new test classes;
3. optimized strict PSR autoload generation succeeds;
4. a no-dev install/autoloader resolves production types;
5. the no-dev autoloader does not expose `Kongono\DependencyInjection\Tests\...`;
6. development dependencies and autoload are restored before the final quality run;
7. no generated `vendor`, nested lock, cache, or autoload artifact is added to repository status.

---

# 14. DOCUMENTATION AND ADR UPDATES

Documentation must distinguish implemented behavior from planned capability and follow `docs/documentation-standard.md` proportionately.

## Create

Create:

```text
packages/dependency-injection/README.md
docs/engineering/dependency-injection.md
docs/adr/0023-explicit-typed-dependency-resolution.md
```

## Package README

Document:

- package purpose and exact Phase 0F boundary;
- explicit interface contracts and concrete implementations;
- explicit ordered constructor dependencies;
- canonical type-name rules;
- definition-set validation and failure timing;
- transient-per-edge construction;
- composition-root-only resolver use; the prohibition on defining or injecting `ResolverInterface` or its subinterfaces; and rejection of implementation classes that implement that resolver contract under another identity;
- constructor failure propagation;
- normal autoloading implications;
- persistent-runtime and concurrency boundaries;
- exception catch boundary and API statuses;
- absence of any Kongono-supplied, special, or preconfigured Kernel lifecycle binding or resolution behavior; the production source's sole static Kernel dependency on `Api` / `ApiStatus` metadata; the generic consumer-contract allowance stated in Section 12; and absence of all Application Graph integration;
- every major prohibited capability from this directive;
- pre-alpha/Experimental stability.

Do not present this package as a complete container or a production-ready DI platform.

## Engineering guide

`docs/engineering/dependency-injection.md` must explain:

- why the explicit boundary exists;
- use and non-use cases;
- exact definition and resolution contracts;
- composition-root use, resolver-contract and resolver-implementation exclusion from definitions, and why arbitrary resolved services must not receive Kongono resolver access;
- constructor signature restrictions;
- validation ordering and deterministic failures;
- transient identity examples, including duplicate and diamond dependencies;
- a non-benchmark performance warning that allocation and constructor work scale with the expanded dependency-edge construction tree rather than the number of unique definitions, repeated diamond branches multiply work, and deep graphs use recursive resolution;
- constructor side effects and failure propagation;
- lifecycle, persistent-runtime, concurrency, and security considerations;
- API status and exception boundary;
- deferred PSR-11, autowiring, lifetime, Graph, and runtime decisions.

## Existing documentation

Update only as needed for accurate current reality and cross-links:

```text
README.md
ARCHITECTURE.md
CONTRIBUTING.md
SECURITY.md
docs/doctrine.md
docs/glossary.md
docs/architecture/overview.md
docs/architecture/progressive-architecture.md
docs/architecture/microkernel.md
docs/architecture/runtime-model.md
docs/engineering/README.md
docs/engineering/api-status.md
docs/engineering/package-layout.md
docs/engineering/php-style.md
docs/engineering/static-analysis.md
docs/engineering/testing.md
docs/engineering/toolchain.md
docs/adr/README.md
docs/adr/0003-monorepo-initial-development.md
docs/adr/0004-microkernel-capability-architecture.md
docs/adr/0020-kernel-operation-lifecycle-boundary.md
```

Required corrections include:

- current status becomes Phase 0F rather than Phase 0D;
- the repository has three production packages;
- the Kernel remains non-container lifecycle code;
- typed dependency resolution exists only in the new package and exact boundary;
- the Graph has no DI integration and does not yet authoritatively describe service dependencies;
- tooling validates and analyzes all three packages;
- Security documentation no longer says the repository contains documentation rather than runnable framework functionality;
- glossary definitions distinguish dependency injection, service contract, service definition, dependency resolver, and transient resolution from planned service scopes.

Do not rewrite unrelated architecture or imply later features.

## ADR 0023

Create `ADR 0023: Explicit Typed Dependency Resolution Boundary` with the repository-required structure.

Set its status and date exactly to:

```text
Status: Accepted
Date: 2026-08-12
```

It must record:

1. the separate `kongono/dependency-injection` package boundary;
2. why the Kernel remains separate;
3. canonical existing interfaces as Phase 0F service identities;
4. explicit implementations and ordered dependencies;
5. reflection validation without autowiring;
6. exact constructor restrictions;
7. complete duplicate, missing-definition, and cycle validation;
8. transient construction on every edge;
9. no service-instance retention;
10. composition-root-only resolution, the explicit exclusion of `ResolverInterface` and its subinterfaces as service contracts or dependencies, and rejection of any implementation class satisfying `is_a($implementation, ResolverInterface::class, true)`;
11. package-specific exception semantics;
12. the temporary `kongono/kernel: *` metadata dependency;
13. no Application Graph relationship in Phase 0F;
14. PSR-11 is deferred and unselected for Phase 0F—not rejected as a possible future interoperability boundary—while its implementation in this phase is rejected;
15. advanced container features are deferred and their Phase 0F implementation is rejected;
16. every material open decision preserved by this directive.

Update ADR 0003 only to record the third authorized package. Update ADR 0004 and ADR 0020 only to record that the implemented resolver is a separate component with no Kongono-supplied, special, or preconfigured Kernel binding or lifecycle behavior; its production source's sole static Kernel dependency is the explicit API-status metadata dependency. A consumer may explicitly use a Kernel interface as an ordinary contract under Section 12 without creating a framework-owned integration. Do not silently rewrite the ADRs' historical rationale.

Add ADR 0023 to the ADR index. Do not create ADR 0024.

---

# 15. AUTHORIZED FILE INVENTORY

## Files created by Phase 0F

Create exactly these 13 required files:

```text
docs/adr/0023-explicit-typed-dependency-resolution.md
docs/engineering/dependency-injection.md
packages/dependency-injection/README.md
packages/dependency-injection/composer.json
packages/dependency-injection/src/Contract/ResolverInterface.php
packages/dependency-injection/src/Definition/ServiceDefinition.php
packages/dependency-injection/src/DependencyResolver.php
packages/dependency-injection/src/Exception/DependencyInjectionException.php
packages/dependency-injection/src/Exception/ExceptionInterface.php
packages/dependency-injection/src/Exception/InvalidDefinitionException.php
packages/dependency-injection/src/Exception/UnknownServiceException.php
packages/dependency-injection/tests/Unit/DependencyResolverTest.php
packages/dependency-injection/tests/Unit/ServiceDefinitionTest.php
```

In addition, create only the strictly necessary one-declaration-per-file fixtures authorized by Section 5 under:

```text
packages/dependency-injection/tests/Fixture/*.php
```

List every actual fixture path and its single test purpose in the completion report. No other created path is authorized.

## Existing files authorized for modification

Modify only:

```text
.php-cs-fixer.dist.php
ARCHITECTURE.md
CONTRIBUTING.md
README.md
SECURITY.md
composer.json
composer.lock
docs/adr/0003-monorepo-initial-development.md
docs/adr/0004-microkernel-capability-architecture.md
docs/adr/0020-kernel-operation-lifecycle-boundary.md
docs/adr/README.md
docs/architecture/microkernel.md
docs/architecture/overview.md
docs/architecture/progressive-architecture.md
docs/architecture/runtime-model.md
docs/doctrine.md
docs/engineering/README.md
docs/engineering/api-status.md
docs/engineering/package-layout.md
docs/engineering/php-style.md
docs/engineering/static-analysis.md
docs/engineering/testing.md
docs/engineering/toolchain.md
docs/glossary.md
phpstan.neon.dist
phpunit.xml.dist
tests/Architecture/ApiStatusConventionTest.php
```

Do not modify any Kernel or Application Graph production source or test.

Do not modify `AGENTS.md`, the documentation standard, earlier directives, task reports, memory handoffs, unrelated ADRs, or any other file.

If accurate implementation requires another file, stop and request explicit scope expansion before editing it.

---

# 16. EXPLICITLY PROHIBITED SCOPE

## Kernel and lifecycle

Do not:

- add a resolver property or method to `Kernel`;
- change any Kernel interface, state, operation scope, exception, or test;
- put services, context, cleanup, or resolver state into `OperationScope`;
- couple resolution to boot, shutdown, or active-operation accounting;
- add an ambient Kernel or resolver.

## Advanced dependency injection

Do not implement or scaffold:

- PSR-11 or `Psr\Container` production use;
- autowiring, implicit constructor discovery, or attribute-based DI;
- mutable registration or post-construction binding;
- class contracts or concrete-class resolution keys;
- string aliases, names, qualifiers, tags, priorities, groups, or registries;
- factories, closures, callables, method calls, property injection, setter injection, or field injection;
- scalar, array, enum, configuration, environment, secret, or value injection;
- optional, nullable, union, intersection, variadic, or defaulted dependency semantics;
- service decoration, middleware, interceptors, proxies, lazy loading, virtual services, or generated subclasses;
- shared, singleton, weak, memoized, operation, request, command, job, worker, process, tenant, session, or custom lifetimes;
- disposal, close, reset, shutdown, cleanup, rollback, cancellation, or async behavior;
- compiled containers, generated PHP, source scanning, cache files, hashes, warmup, optimization, or serialization;
- parent/child containers, scopes, modules, providers, bundles, extension contributions, or package discovery;
- a Kongono-provided locator contract or Kongono resolver access from arbitrary resolved application services.

## Application Graph

Do not add or change:

- stable IDs, Documentation IDs, hashes, URIs, UUIDs, or cross-build identity;
- built-in service/dependency node or relation kinds;
- domain schema, metadata, provenance, visibility, security filtering, or authorization;
- DI definitions or resolved instances as Graph payload;
- discovery, reflection frontends, attributes, IR compilation, merging, contributions, persistence, serialization, caches, queries, traversal, or diffs;
- a dependency from either package to the other.

## Later framework capabilities

Do not implement or scaffold:

- modules, profiles, application bootstrapping, or capability composition;
- HTTP, routing, middleware, controllers, requests, responses, or sessions;
- databases, ORM, migrations, transactions, or repositories;
- authentication, authorization, principals, permissions, delegation, or policy;
- events, queues, schedules, workflows, jobs, or messaging;
- cache, storage, mail, notifications, or realtime systems;
- agents, AI providers, MCP, A2A, tools, or model integrations;
- extension hosts, manifests, permissions, sandboxing, or distribution;
- CLI commands or the `kongo` executable;
- frontend, Vite, assets, JavaScript, TypeScript, Rust, or Go;
- OpenTelemetry, logging, metrics, tracing, profiling, or exporters;
- OpenAPI, protocol adapters, runtime adapters, deployment, CI/CD, releases, or publishing.

## Dependency and quality policy

Do not:

- add an external dependency;
- authorize a Composer plugin;
- use a transitive dev dependency as an undeclared production dependency;
- manually edit the lockfile;
- change dependency-security policy;
- ignore platform requirements;
- create a PHPStan baseline or suppression;
- lower static-analysis level or remove strict rules;
- weaken PHPUnit failure settings;
- exclude new files from style, analysis, or tests;
- add skips, incompletes, expected warnings, or output to hide missing behavior.

## Release and process

Do not:

- select a license;
- invent a package or framework version;
- define semantic-versioning or promotion policy;
- silence accepted pre-alpha Composer warnings with false metadata;
- create a benchmark or performance claim;
- stage, commit, branch, tag, add a remote, or push;
- modify the current directive or report placeholders.

---

# 17. REQUIRED VALIDATION

Run all checks from the repository root unless the command explicitly uses a package working directory.

## Baseline and environment

Before editing, record and verify:

```text
php -v
php --ini
composer --version
git --version
vendor/bin/phpunit --version
vendor/bin/phpstan --version
vendor/bin/php-cs-fixer --version
git branch --show-current
git for-each-ref --format="%(refname:short)" refs/heads
git rev-parse HEAD
git rev-parse HEAD^{tree}
git rev-list --count HEAD
git rev-list --parents -n 1 HEAD
git log -1 --format=%s
git remote
git tag
git status --short --untracked-files=all
git diff --exit-code
git diff --cached --exit-code
composer quality
```

Use PowerShell-safe quoting where necessary. The semantic checks are mandatory even if command spelling requires shell adaptation.

## Composer mutation and validation

After creating the package and editing root Composer configuration:

1. run the targeted Composer update authorized in Section 13;
2. inspect the complete root manifest and lock diff;
3. compare the pre-change and post-change external package inventory;
4. verify only the new local package was added;
5. run:

```text
composer validate
composer validate:composer
composer validate:packages
composer --working-dir=packages/application-graph validate --no-check-publish
composer --working-dir=packages/dependency-injection validate --no-check-publish
composer --working-dir=packages/kernel validate --no-check-publish
composer audit
composer audit:locked
composer platform
composer check-platform-reqs
composer install --dry-run --no-interaction
```

Accepted existing warnings are limited to:

- the intentionally undecided license;
- unreleased local `@dev` / `*` constraints.

The accepted commit now lets Composer derive `dev-main`; a root-version detection/default warning is unexpected and must be reported rather than accepted. Report all warnings; do not hide them by adding a root version, license, stability policy, or bounded inter-package version that has not been selected.

## Autoload

Verify optimized strict development autoload, direct production/test resolution, no-dev production resolution, no-dev test absence, and restored development autoload.

Record the direct checks and outcomes. Do not rely only on PHPUnit indirectly loading classes.

## PHP syntax and architecture

Run syntax checks across every first-party PHP file.

Verify through deterministic tests and direct inspection:

- exact 27-type production inventory;
- exact seven new statuses;
- no status changes to existing types;
- exact public method inventories;
- exact final/readonly/abstract/interface relationships;
- exact exception-marker separation;
- no unexpected static mutable state;
- no Kernel or Graph production source/test changes, except the explicitly authorized root-owned cross-package architecture assertion;

## Focused behavior

Run focused package tests before the full suite. Verify every Section 12 behavior, including duplicate-edge and diamond transient identity, deterministic invalid-set handling, and constructor-failure propagation.

## Quality

Run:

```text
composer cs
composer analyse
composer test
composer quality
composer -vvv quality
```

Report:

- style file count and result;
- PHPStan version, maximum level, strict-rules presence, and zero-error result;
- PHPUnit version, tests, assertions, and outcome counts;
- exact verbose quality dispatch order;
- absence of baselines, suppressions, exclusions, policy weakening, and plugin authorization.

## Documentation and file safety

Validate:

- UTF-8 for every changed/new text file;
- final newline for every changed/new text file;
- no trailing whitespace;
- no merge-conflict markers;
- valid JSON manifests and lockfile;
- all local Markdown links;
- exact ADR sequence 0001–0023;
- no zero-byte Phase 0F implementation file, excluding the mandatory caller-managed zero-byte `docs/task reports/Phase_0F.md` placeholder;
- no binary, symlink, junction, reparse point, executable-mode, nested repository, generated artifact, credential, secret, machine-local path, or oversized artifact in Phase 0F changes;
- documentation distinguishes implemented Phase 0F behavior from planned features.

Historical caller records may contain machine-local paths as truthful reports. Preserve and classify them; do not rewrite history.

## Prohibited-scope audit

Search all Phase 0F production source, tests, manifests, lock changes, and documentation for prohibited concepts. Manually classify matches; a documentation statement saying a feature is absent is not a violation.

Report separate results for:

- Kernel/lifecycle coupling;
- PSR-11 and service locator;
- autowiring/discovery/reflection inference;
- shared/scoped lifetimes and cleanup;
- factories/values/advanced DI;
- Graph schema and integration;
- HTTP/data/security/events/workflows;
- agents/extensions/CLI/frontend/telemetry;
- external dependencies and plugins;
- release/version/license behavior.

## Final repository state

Run and report:

```text
git diff --check
git diff --cached --check
git diff --cached --exit-code
git status --short --untracked-files=all
git branch --show-current
git for-each-ref --format="%(refname:short)" refs/heads
git rev-parse HEAD
git rev-parse HEAD^{tree}
git rev-list --count HEAD
git rev-list --parents -n 1 HEAD
git log -1 --format=%s
git ls-tree -r HEAD
git remote
git tag
```

Verify:

- HEAD and its tree are unchanged;
- branch remains `main` and is the only branch;
- commit count remains one;
- index remains empty;
- remotes and tags remain absent;
- the Phase 0E.2 report remains byte-identical;
- the Phase 0F directive remains byte-identical;
- the Phase 0F report placeholder remains zero bytes;
- only the exact authorized Phase 0F worktree changes and three caller records exist;
- no commit or push occurred.

---

# 18. STRICT ACCEPTANCE CRITERIA

Phase 0F is complete only if every criterion passes.

## Authority and baseline

- [ ] All required authority and implementation files were read completely.
- [ ] The accepted baseline commit, tree, branch, index, file count, modes, remotes, and tags matched.
- [ ] The three caller-managed records were classified and preserved correctly.
- [ ] The pre-change quality gate passed.
- [ ] No unresolved decision was guessed.
- [ ] No prohibited later capability was implemented or scaffolded.

## Package and dependencies

- [ ] Exactly one new package exists at `packages/dependency-injection`.
- [ ] Package name, namespaces, manifest, and exact file layout match this directive.
- [ ] Root path-repository strategy remains unchanged.
- [ ] Root requires the new package with exact `@dev`.
- [ ] Package requires only PHP and Kernel metadata under the authorized constraints.
- [ ] No dependency exists between Dependency Injection and Application Graph.
- [ ] Composer alone generated the lock change.
- [ ] Exactly one local production package was added to the lock.
- [ ] No external package version or integrity reference changed.
- [ ] No external production dependency or Composer plugin was added.
- [ ] The security policy remains unchanged, and Composer warnings remain limited to the authorized categories in Section 17.

## Service definitions

- [ ] Service identity is restricted to canonical existing interfaces.
- [ ] Implementations are canonical compatible instantiable classes.
- [ ] Dependencies are explicit ordered canonical interface lists.
- [ ] Definition objects are final, readonly, immutable, and exact.
- [ ] Every local definition and constructor-signature invariant is validated.
- [ ] Reflection validates explicit declarations only and performs no autowiring.
- [ ] No alias, class-key, scalar, value, factory, callable, or metadata contract exists.

## Resolver

- [ ] Resolver input requires a runtime list of definitions.
- [ ] Empty sets are valid.
- [ ] Duplicates, missing definitions, and cycles fail deterministically.
- [ ] Resolver state is immutable and retains definitions only.
- [ ] Resolution follows declared constructor order.
- [ ] Every top-level call and dependency edge is transient.
- [ ] Duplicate and diamond dependencies are not shared.
- [ ] No service instance is cached or retained.
- [ ] Unknown requested contracts fail through `UnknownServiceException`.
- [ ] Consumer constructor failures propagate unchanged.
- [ ] No global or ambient integration exists; no Kongono-supplied, special, or preconfigured Kernel lifecycle binding or resolution behavior exists; production source statically depends on Kernel only for API-status metadata; and no Application Graph, runtime, I/O, logging, or telemetry integration exists. An explicit consumer-selected Kernel interface remains governed only as an ordinary contract under Section 12.

## Exceptions and API status

- [ ] The exact four-type exception hierarchy exists.
- [ ] Every deliberate package failure implements the package marker.
- [ ] Package exceptions do not implement Kernel or Graph markers.
- [ ] Existing production statuses remain unchanged.
- [ ] Exactly seven new production types have the exact authorized statuses.
- [ ] Production inventory is exactly 27 types.
- [ ] The dependency-injection exception marker is the only new Public type.
- [ ] Root architecture enforcement remains authoritative.

## Tests and tooling

- [ ] Every required definition behavior has a production test.
- [ ] Every required resolver behavior has a production test.
- [ ] Tests inspect public behavior rather than private storage.
- [ ] Root and all three package manifests validate.
- [ ] Development and no-dev autoload contracts pass.
- [ ] PHPStan max/strict passes with zero errors and no suppression.
- [ ] PHP CS Fixer passes without excluding Phase 0F files.
- [ ] PHPUnit passes with no problematic outcomes.
- [ ] Full and verbose quality gates pass in unchanged stage order.
- [ ] Dependency and platform audits pass without weakening policy.

## Documentation and ADRs

- [ ] Package and engineering guides explain the exact contract and non-goals.
- [ ] Root, architecture, security, engineering, and glossary documentation reflect Phase 0F reality.
- [ ] Kernel and Graph responsibilities remain accurate and separate.
- [ ] ADR 0023 exists with exact required decisions and open questions.
- [ ] ADR index is complete through 0023.
- [ ] Existing ADR updates record current reality without rewriting history.
- [ ] No documentation claims implemented PSR-11, autowiring, scopes, Graph integration, production readiness, or later capabilities.

## Git and process

- [ ] Exact authorized created and modified file inventories were respected.
- [ ] File-safety, encoding, newline, link, secret, and machine-local audits pass.
- [ ] Both diff checks pass.
- [ ] Index remains unchanged and empty.
- [ ] Baseline HEAD/tree/branch/commit count remain unchanged.
- [ ] No remote or tag exists and nothing was pushed.
- [ ] All three caller records remain correctly preserved.
- [ ] No commit, staging, destructive Git action, or Git-configuration change occurred.
- [ ] The completion report contains every required numbered section, including `NEXT STEP`.

If any criterion fails, report `Partial` or `Blocked`, not `Complete`.

---

# 19. REQUIRED EXECUTOR COMPLETION REPORT

Return exactly this complete structure:

```text
PHASE 0F TASK COMPLETION REPORT

1. STATUS
Complete / Partial / Blocked

2. SUMMARY
- exact implementation delivered
- explicit non-goals preserved

3. BASELINE OBSERVED
- PHP, loaded configuration, Composer, Git, PHPUnit, PHPStan, and PHP CS Fixer versions
- branch, HEAD, tree, parent count, subject
- tracked files and modes
- index/tracked-worktree state
- remotes and tags
- three caller records, sizes, hashes, and classifications
- pre-change quality result

4. FILES CREATED
- exact paths and purposes

5. FILES MODIFIED
- exact paths and reasons

6. FILES MOVED / REMOVED
None, or exact authorized paths.

7. PACKAGE, DEPENDENCY, AND AUTOLOAD CONTRACT
- package identity and manifest
- root integration
- dependency and lockfile delta
- external-package comparison
- development/no-dev autoload results

8. PRODUCTION SYMBOL AND API INVENTORY
- exact seven new FQCNs
- exact API status for each
- exact total production count
- public-method inventories

9. SERVICE-DEFINITION CONTRACT
- identity rules
- implementation compatibility
- dependency-list and constructor rules
- reflection boundary
- validation outcomes

10. RESOLUTION AND LIFECYCLE INVARIANTS
- definition-set validation
- missing/cycle behavior
- resolution order
- transient root/edge/duplicate/diamond behavior
- constructor-failure behavior
- persistent-runtime and concurrency boundary

11. EXCEPTION CONTRACT
- hierarchy
- throw sites/categories
- package-marker checks
- Kernel/Graph separation
- native/consumer failure behavior

12. TEST RESULTS
- focused and full commands
- tests/assertions/outcomes
- required behavior coverage

13. COMPOSER AND SECURITY RESULTS
- every manifest validation
- targeted update command
- lock diff
- native and locked audits
- platform and dry-run install
- dependency/plugin/policy confirmation
- accepted warnings

14. STATIC ANALYSIS, STYLE, AND QUALITY RESULTS
- PHPStan version/level/result
- baseline/suppression/exclusion confirmation
- style result and file count
- normal and verbose quality results
- exact quality dispatch order

15. DOCUMENTATION AND ADR RESULTS
- files created/updated
- ADR 0023 title and decisions
- earlier ADR updates
- reality/plans and open-decision review

16. FILE-SAFETY AND PROHIBITED-SCOPE AUDIT
- UTF-8/newline/whitespace/link/conflict results
- secret/machine-local/binary/mode/symlink/nested-repository results
- separate prohibited-scope results for every Section 17 category

17. COMMANDS RUN
- exact commands in execution order
- environment or approval distinctions

18. ARCHITECTURAL DECISIONS AND DEVIATIONS
None beyond those explicitly authorized, or exact issue and approval status.

19. QUESTIONS / BLOCKERS
None, or exact architect action required.

20. GIT STATUS SUMMARY
- exact final status
- created/modified/caller-managed classification
- HEAD/tree/index/branch/remotes/tags comparison
- diff checks
- confirmation no stage/commit/push/configuration change

21. ACCEPTANCE CHECKLIST
PASS / FAIL for every criterion in Section 18.

22. NEXT STEP
Await architect review before Phase 0G.
```

For item 22, state only:

> Await architect review before Phase 0G.

Do not omit, merge, or renumber report sections. Do not begin Phase 0G.

---

# 20. FINAL EXECUTION INSTRUCTION

Implement the smallest correct Phase 0F typed dependency-resolution boundary.

The successful result is deliberately limited:

```text
One new independent package
Canonical interface service contracts
Explicit concrete implementations
Explicit ordered constructor dependencies
Reflection validation without autowiring
Complete duplicate/missing/cycle validation
Transient construction on every edge
No cached service instances
One package exception boundary
Exact Experimental/Public/Internal status map
Strict production tests and quality gates
Accurate documentation and ADR 0023
No Kongono-supplied, special, or preconfigured Kernel binding/lifecycle behavior; production source statically depends on Kernel only for Api/ApiStatus metadata
No Application Graph integration
No PSR-11
No ambient or injectable Kongono `ResolverInterface`; consumer-defined service-locator semantics remain unsupported and outside this phase's guarantee
No shared or scoped lifetimes
No later framework capabilities
No Git commit or push
```

When implementation and validation are complete, stop and return the required report to the caller.
Read
