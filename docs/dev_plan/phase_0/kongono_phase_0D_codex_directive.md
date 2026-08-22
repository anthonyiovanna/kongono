# CODEX IMPLEMENTATION DIRECTIVE — KONGONO PHASE 0D

## Application Graph Structural Core

## Mission

You are the implementation executor for Kongono Framework.

Phases 0A, 0B, and 0C are complete and architecturally accepted. The repository now contains locked doctrine and governance, a strict PHP 8.5 quality toolchain, and the accepted `kongono/kernel` package with its deterministic persistent-runtime lifecycle, exception boundary, API-status convention, and production tests.

This task establishes the next smallest framework boundary: a source-language-neutral, immutable, in-memory structural core for the future Kongono Application Graph.

Implement only:

- the `kongono/application-graph` monorepo package;
- opaque graph-local node keys;
- open node-kind and relation-kind value objects;
- immutable nodes and directed relations;
- an immutable Application Graph snapshot;
- deterministic ordering and lookup;
- structural validation for duplicate nodes, missing endpoints, and duplicate relations;
- a package exception foundation;
- production tests and repository-wide API-status enforcement;
- the required Composer, autoload, test, style, static-analysis, documentation, glossary, and ADR updates.

This is Phase **0D — Application Graph Structural Core**.

This phase does **not** implement a complete Application Graph schema, stable cross-build identifiers, serialization, discovery, reflection scanning, new Graph-domain PHP attributes, compilation, metadata contribution, dependency injection, HTTP, or any domain-specific framework capability. The already accepted `Api` attribute remains mandatory compatibility metadata on production types and is not Graph-domain metadata.

---

# 1. ROLE, AUTHORITY, AND STOP RULE

You are an **executor, not the architect**.

Read the repository authority before editing, including at minimum:

```text
AGENTS.md
ARCHITECTURE.md
CONTRIBUTING.md
SECURITY.md
composer.json
phpstan.neon.dist
phpunit.xml.dist
.php-cs-fixer.dist.php
docs/doctrine.md
docs/documentation-standard.md
docs/glossary.md
docs/architecture/overview.md
docs/architecture/application-graph.md
docs/architecture/microkernel.md
docs/architecture/runtime-model.md
docs/engineering/package-layout.md
docs/engineering/api-status.md
docs/engineering/toolchain.md
docs/adr/README.md
docs/adr/0003-monorepo-initial-development.md
docs/adr/0005-language-neutral-application-graph.md
docs/adr/0020-kernel-operation-lifecycle-boundary.md
docs/adr/0021-machine-checkable-api-status.md
packages/kernel/composer.json
packages/kernel/README.md
all production and test code under packages/kernel
```

All LOCKED doctrine and Accepted ADRs remain authoritative.

## Mandatory stop rule

If implementation requires a decision not explicitly settled by this directive or an existing Accepted ADR—especially a public API, package dependency, Graph identity, Graph schema, serialization, metadata, compiler, source-discovery, compatibility, security, visibility, versioning, or language-frontend decision—then:

**STOP. DO NOT GUESS.**

Report:

1. the exact decision required;
2. why safe progress depends upon it;
3. viable options;
4. tradeoffs of each option;
5. your recommendation, labeled as a recommendation only.

Do not implement the recommendation without architect approval.

## Conflict rule

If this directive conflicts with LOCKED doctrine or an Accepted ADR:

1. stop the conflicting portion;
2. identify the exact conflict;
3. continue only with independent work that cannot prejudice its resolution;
4. report the conflict.

Do not silently treat newer text as an override.

---

# 2. BASELINE AND REPOSITORY-STATE SAFETY

Before editing:

1. Read the authority files listed above.
2. Inspect the complete repository tree.
3. Inspect `git status --short`.
4. Inspect staged and unstaged diffs separately.
5. Record all untracked files.
6. Inspect current root and package Composer manifests.
7. Inspect `composer.lock` and confirm the locked `kongono/kernel` path package.
8. Inspect current API-status architecture enforcement.
9. Inspect current quality-stage order.
10. Run:

```bash
composer quality
```

The baseline must be green before Phase 0D code is introduced. If it is not green for a pre-existing repository reason, record the exact failure and stop if reliable validation is not possible.

The missing-license, root-version detection, local development-version, and explicitly authorized unbound pre-release constraint warnings remain accepted. Do not invent a license or version to silence them.

## Index preservation

The repository currently has a historically mixed staged, unstaged, and untracked state. Preserve it.

Do not:

- stage or unstage files;
- replace the staged form of a file;
- normalize the index;
- discard user changes;
- restore files from Git over user work;
- commit, branch, push, or create a remote.

The known staged/worktree mismatch for the Phase 0C.1 correction report is repository housekeeping for a later first-commit preparation task. Do not “fix” it during Phase 0D.

If a file changes concurrently during execution, stop work on that file, preserve both states, and report the collision.

---

# 3. LOCKED ARCHITECTURAL CONTEXT

The following doctrine remains in force:

```text
Project:           Kongono
PHP namespace:     Kongono\
Composer vendor:   kongono
Minimum PHP:       PHP 8.5
Repository model:  initial monorepo
Architecture:      small microkernel plus independent capabilities
Runtime model:     persistent-runtime-first
Graph direction:   language-neutral IR feeding an authoritative Application Graph
```

Kongono remains explicit, typed, inspectable, deterministic, machine-readable, and statically verifiable.

“AI may suggest; Kongono must know” remains a hard correctness boundary. An LLM may never become the authoritative source of Graph structure, dependency legality, security metadata, contracts, or module boundaries.

The Graph is intended eventually to describe modules, services, dependencies, routes, actions, entities, schemas, events, jobs, workflows, policies, permissions, principals, agents, delegations, capabilities, extension contributions, frontend contracts, external APIs, and documentation relationships.

That intended breadth does not authorize those concepts in Phase 0D.

---

# 4. PHASE 0D BOUNDARY — LOCKED

Phase 0D implements only a generic structural snapshot:

```text
ApplicationGraph
├── Nodes
│   ├── graph-local NodeKey
│   └── open NodeKind
└── Directed relations
    ├── source NodeKey
    ├── open RelationKind
    └── target NodeKey
```

The snapshot answers only:

```text
Which nodes exist?
What opaque kind label does each node carry?
Which directed, typed relations connect existing nodes?
Is the structure internally valid?
What is its deterministic canonical iteration order?
```

It does not answer:

```text
Where did a node come from?
What domain-specific fields does it contain?
How is a stable ID generated?
How is PHP source discovered?
How are components merged?
How is the Graph serialized?
How is compatibility versioned?
Who is allowed to see a node?
How are Graph changes compiled or cached?
How does runtime behavior consume it?
```

The Phase 0D model is a structural prerequisite for the future authoritative Application Graph. It is not yet the complete authoritative representation of a Kongono application.

---

# 5. PACKAGE LAYOUT — LOCKED

Create exactly this production package:

```text
packages/
└── application-graph/
    ├── composer.json
    ├── README.md
    ├── src/
    │   ├── Exception/
    │   │   ├── ExceptionInterface.php
    │   │   ├── GraphException.php
    │   │   └── InvalidGraphException.php
    │   └── Model/
    │       ├── ApplicationGraph.php
    │       ├── Node.php
    │       ├── NodeKey.php
    │       ├── NodeKind.php
    │       ├── Relation.php
    │       └── RelationKind.php
    └── tests/
        └── Unit/
            ├── ApplicationGraphTest.php
            └── StructuralValueTest.php
```

Do not create other production symbols or directories without stopping for approval.

## Package identity

`packages/application-graph/composer.json` must define:

```text
name:         kongono/application-graph
type:         library
PHP:          >=8.5
production:   Kongono\ApplicationGraph\ => src/
development:  Kongono\ApplicationGraph\Tests\ => tests/
```

The package must declare a production dependency on `kongono/kernel` because its production types use the accepted `Api`/`ApiStatus` convention.

Do not use `Kongono\Kernel\Exception\ExceptionInterface` as a general framework exception marker. It remains the catch boundary for failures deliberately raised by `kongono/kernel`; Application Graph failures receive their own package marker below.

Use exactly this temporary internal package constraint:

```json
"kongono/kernel": "*"
```

This unbounded internal constraint is authorized only because Kongono has no releases or accepted inter-package versioning policy yet and both packages are developed atomically in the monorepo. Document it as temporary and unresolved. Do not use `@dev` inside the dependency package because Composer stability flags are root-only.

Do not add:

- any external dependency;
- a package version;
- a license not already selected;
- `minimum-stability`;
- a Composer plugin;
- publishing or split metadata;
- branch aliases;
- replace/provide/conflict declarations;
- runtime, framework, reflection, collection, UUID, assertion, or serialization libraries.

## Root integration

The existing canonical `packages/*` path repository remains unchanged.

Add to the root production requirements:

```json
"kongono/application-graph": "@dev"
```

Preserve:

```json
"kongono/kernel": "@dev"
```

Do not change repository-wide stability. Do not invent versions. Update the lockfile only through Composer and update only the new local package plus metadata Composer must recalculate.

## Root test autoload

Add this development-only root mapping:

```json
"Kongono\\ApplicationGraph\\Tests\\": "packages/application-graph/tests/"
```

Preserve the accepted Kernel and root test mappings. Do not place test namespaces in production autoload.

---

# 6. LANGUAGE-NEUTRAL SEMANTIC RULES — LOCKED

The PHP implementation is one source-language representation of a model whose semantics must remain portable.

Phase 0D production objects must contain only the structural concepts authorized here. They must not contain or accept:

- PHP class names as privileged fields;
- `ReflectionClass`, `ReflectionMethod`, or other reflection objects;
- PHP attributes or AST nodes;
- closures or callables;
- resources;
- services or service-container references;
- Kernel or operation-scope instances;
- arbitrary objects as metadata;
- request, route, model, principal, agent, or extension objects;
- runtime-adapter state;
- filesystem paths as required semantics;
- framework callbacks;
- executable behavior.

The only dependency upon `kongono/kernel` is the repository-wide API-status attribute and the Public deliberate-failure marker. The Graph's semantic model must not depend on Kernel lifecycle state.

Do not call this package an IR serializer, compiler, schema registry, or PHP frontend. It is the first structural in-memory model intended to be usable by a future IR/compiler boundary.

---

# 7. EXACT STRUCTURAL MODEL

All production classes in this section are final. All value and model objects are immutable. Use `readonly` where PHP permits it without weakening invariants.

## 7.1 `NodeKey`

FQCN:

```text
Kongono\ApplicationGraph\Model\NodeKey
```

Contract:

```php
public function __construct(string $value);
public function value(): string;
```

Rules:

1. The value must not be the empty string.
2. Preserve the supplied non-empty string exactly.
3. Do not trim, lowercase, normalize Unicode, split, parse, hash, prefix, or reinterpret it.
4. Equality for graph structure is exact byte-for-byte string equality of `value()`.
5. It identifies a node only within the structural snapshot and producer contract that supplied it.
6. It is not yet a stable cross-build Graph ID, documentation ID, database ID, URI, URN, UUID, package coordinate, PHP symbol, or serialized identifier.
7. Do not add generation helpers, randomness, timestamps, counters, parsing, formatting, or `__toString()`.

The deliberately weak syntax avoids selecting the deferred stable identifier system. Producers will later receive a stronger identity contract.

## 7.2 `NodeKind`

FQCN:

```text
Kongono\ApplicationGraph\Model\NodeKind
```

Contract:

```php
public function __construct(string $value);
public function value(): string;
```

Rules:

1. The value must not be empty.
2. Preserve it exactly.
3. It is an open label, not a PHP enum of authorized application concepts.
4. No built-in kinds such as module, service, route, action, model, event, agent, or extension are authorized.
5. No registry, validation vocabulary, namespace syntax, or compatibility promise is authorized.
6. Do not add `__toString()`.

## 7.3 `RelationKind`

FQCN:

```text
Kongono\ApplicationGraph\Model\RelationKind
```

Contract:

```php
public function __construct(string $value);
public function value(): string;
```

Rules mirror `NodeKind`:

- non-empty;
- exact preservation;
- open label;
- no built-in dependency/call/event/module/security vocabulary;
- no registry or normalization;
- no `__toString()`.

## 7.4 `Node`

FQCN:

```text
Kongono\ApplicationGraph\Model\Node
```

Contract:

```php
public function __construct(NodeKey $key, NodeKind $kind);
public function key(): NodeKey;
public function kind(): NodeKind;
```

Rules:

1. A node contains exactly one key and one kind.
2. It contains no name, label, payload, properties, attributes, source location, documentation link, security metadata, arbitrary metadata bag, or behavior.
3. Node identity inside a snapshot is its `NodeKey` value, not PHP object identity.

## 7.5 `Relation`

FQCN:

```text
Kongono\ApplicationGraph\Model\Relation
```

Contract:

```php
public function __construct(
    NodeKey $source,
    RelationKind $kind,
    NodeKey $target,
);

public function source(): NodeKey;
public function kind(): RelationKind;
public function target(): NodeKey;
```

Rules:

1. Relations are directed from source to target.
2. Relation equality within a snapshot is the exact triple:

```text
source key value
relation kind value
target key value
```

3. Self-relations are structurally valid.
4. Cycles are structurally valid.
5. Multiple relations between the same source and target are valid when their relation-kind values differ.
6. An exact duplicate relation triple is invalid.
7. A relation has no separate ID, payload, order, weight, provenance, security metadata, or arbitrary properties.
8. Duplicate detection must be collision-free for arbitrary non-empty key/kind strings. Do not concatenate tuple parts with an unescaped delimiter, hash them, or serialize them to invent an internal relation ID. Use exact nested lookup or exact tuple comparison.

## 7.6 `ApplicationGraph`

FQCN:

```text
Kongono\ApplicationGraph\Model\ApplicationGraph
```

Exact public contract:

```php
/**
 * @param list<Node> $nodes
 * @param list<Relation> $relations
 */
public function __construct(array $nodes, array $relations);

/** @return list<Node> */
public function nodes(): array;

/** @return list<Relation> */
public function relations(): array;

public function hasNode(NodeKey $key): bool;

public function node(NodeKey $key): ?Node;
```

Do not add factories, builders, mutation methods, removal methods, filtering/query DSLs, traversals, visitors, counts, serialization, hashing, diffing, merging, compilation, or convenience APIs.

## Snapshot validation

Construction must validate all input before exposing a usable object.

Required rules:

1. Empty graphs are valid.
2. `$nodes` and `$relations` must each be runtime lists: zero-based arrays with consecutive integer keys. Reject associative or gapped arrays rather than silently reindexing them.
3. Every `$nodes` entry must be a `Node` at runtime as well as in PHPDoc/static analysis.
4. Every `$relations` entry must be a `Relation` at runtime as well as in PHPDoc/static analysis.
5. Node keys must be unique by exact `NodeKey::value()` equality.
6. Every relation source must reference an existing node key.
7. Every relation target must reference an existing node key.
8. Exact duplicate relation triples are invalid.
9. Self-relations and cycles remain valid.
10. Invalid input throws before a graph instance becomes usable.
11. Do not silently discard, replace, merge, coerce, or normalize invalid input.

PHP's array type does not enforce element types at runtime. Explicit element validation is required. Do not rely solely on PHPStan annotations.

## Deterministic canonical order

The snapshot must return canonical lists independent of input order.

Sort nodes using binary string comparison of:

```text
NodeKey::value()
```

Sort relations using binary string comparison in this order:

```text
1. source NodeKey::value()
2. RelationKind::value()
3. target NodeKey::value()
```

Use deterministic PHP string comparison semantics and document that Phase 0D performs no locale-aware or Unicode normalization.

`node()` and `hasNode()` use exact key-value equality. They must not depend on the caller passing the same `NodeKey` object instance used at construction.

## Immutability

After successful construction:

- nodes and relations cannot be added, removed, or replaced;
- returned arrays cannot mutate internal state;
- returned node/relation/value objects cannot be mutated;
- no static mutable state exists;
- no ambient Graph exists;
- no singleton or global registry exists.

---

# 8. EXCEPTION FOUNDATION — LOCKED

Create:

```text
Throwable
└── Kongono\ApplicationGraph\Exception\ExceptionInterface
    └── GraphException
        └── InvalidGraphException
```

Exact requirements:

1. `Kongono\ApplicationGraph\Exception\ExceptionInterface` extends PHP `Throwable` and contains no methods.
2. `GraphException` is abstract, extends PHP `RuntimeException`, and implements the Application Graph exception interface.
3. `InvalidGraphException` is final and extends `GraphException`.
4. Every exception deliberately thrown by `kongono/application-graph` in Phase 0D uses `InvalidGraphException` and is catchable through the package marker.
5. Graph exceptions must not implement `Kongono\Kernel\Exception\ExceptionInterface`; they are not Kernel-package failures.
6. Native engine errors are not broadly caught or wrapped.
7. Do not catch `Throwable` in production.
8. Do not add numeric codes, localization, logging, telemetry, rendering, transport semantics, retry policy, serialized error payloads, or result objects.
9. Messages identify the violated structural rule and relevant key/kind values without exposing unrelated data.

---

# 9. API STATUS — LOCKED

Use the accepted `Kongono\Kernel\Api\Api` attribute on every new production class-like type.

Classify exactly:

```text
Public
  Kongono\ApplicationGraph\Exception\ExceptionInterface

Experimental
  Kongono\ApplicationGraph\Exception\InvalidGraphException
  Kongono\ApplicationGraph\Model\ApplicationGraph
  Kongono\ApplicationGraph\Model\Node
  Kongono\ApplicationGraph\Model\NodeKey
  Kongono\ApplicationGraph\Model\NodeKind
  Kongono\ApplicationGraph\Model\Relation
  Kongono\ApplicationGraph\Model\RelationKind

Internal
  Kongono\ApplicationGraph\Exception\GraphException
```

Do not add Public Graph model APIs beyond the package exception catch boundary.

Experimental means the model is available for evaluation but carries no compatibility promise before promotion. Do not describe the structural model, value syntax, ordering, or exception text as Stable.

API status remains compatibility metadata, not Graph metadata, authorization, visibility control, or a security boundary.

---

# 10. REPOSITORY-WIDE API-STATUS ARCHITECTURE TEST

The accepted API-status architecture rule now spans more than one package. Keeping its complete inventory inside `kongono/kernel` would make the Kernel package's test suite depend on knowledge of every future sibling package.

Move:

```text
packages/kernel/tests/Architecture/ApiStatusConventionTest.php
```

to:

```text
tests/Architecture/ApiStatusConventionTest.php
```

Change its namespace to:

```text
Kongono\Tests\Architecture
```

Update its repository-root discovery logic without weakening any assertion.

The relocated test must continue to verify:

1. deterministic discovery of every production class-like declaration under `packages/*/src`;
2. autoloadability of every discovered production type;
3. the exact complete production inventory;
4. exactly one `Api` status for every non-bootstrap type;
5. the exact required status of all Kernel and Application Graph types;
6. only `Api` and `ApiStatus` as bootstrap exceptions;
7. exact `ApiStatus` cases and values;
8. the `Api` attribute's final, readonly, class-like, and non-repeatable declaration;
9. failure for undeclared or missing-status production types.

Do not copy the test while leaving the original. Move the repository-wide concern to the root suite.

Do not introduce a production scanner, Graph discovery engine, CLI, third-party architecture-test library, or custom autoload implementation.

The source-token discovery used only by this repository test remains test infrastructure and must not be presented as the future PHP Graph frontend.

---

# 11. REQUIRED PRODUCTION TESTS

## `StructuralValueTest`

Test at minimum:

1. `NodeKey` preserves a non-empty value exactly;
2. empty `NodeKey` is rejected;
3. `NodeKind` preserves a non-empty value exactly;
4. empty `NodeKind` is rejected;
5. `RelationKind` preserves a non-empty value exactly;
6. empty `RelationKind` is rejected;
7. `Node` exposes exactly its key and kind;
8. `Relation` exposes source, kind, and target in the correct direction;
9. every deliberate invalid-value failure implements the Graph exception marker and does not implement the Kernel exception marker;
10. value/model objects are final and readonly where required;
11. no value/model type implements string conversion or serialization.

## `ApplicationGraphTest`

Test at minimum:

1. an empty graph is valid;
2. nodes are returned in canonical key order regardless of input order;
3. relations are returned in canonical source/kind/target order regardless of input order;
4. lookup succeeds with an equivalent but distinct `NodeKey` object;
5. missing lookup returns `null` and `hasNode()` returns false;
6. duplicate node keys are rejected even when represented by different `NodeKey` objects;
7. a relation with a missing source is rejected;
8. a relation with a missing target is rejected;
9. an exact duplicate relation triple is rejected;
10. two different relation kinds between the same endpoints are accepted;
11. a self-relation is accepted;
12. a cycle is accepted;
13. delimiter-like and control-character-containing key/kind values cannot cause distinct relation triples to collide;
14. ending with caller-mutated input arrays cannot alter the constructed snapshot;
15. mutating arrays returned by `nodes()` or `relations()` cannot alter the snapshot;
16. an associative or gapped nodes array is rejected rather than silently reindexed;
17. an associative or gapped relations array is rejected rather than silently reindexed;
18. a non-`Node` item in the nodes list is rejected at runtime;
19. a non-`Relation` item in the relations list is rejected at runtime;
20. invalid construction failures identify their structural category diagnostically;
21. graph validation performs no filesystem, network, environment, time, randomness, process, logging, telemetry, reflection, or global-state access.

## Test discipline

- Use strict types.
- Test behavior rather than private storage.
- Do not use reflection to inspect private graph collections. Reflection is allowed for explicit final/readonly/interface assertions and to invoke the constructor with deliberately invalid container element types that cannot be expressed honestly through the static `list<Node>` / `list<Relation>` contract.
- Avoid mocks.
- Avoid randomness, sleeps, network, filesystem fixtures, environment dependence, or test-order dependence.
- Do not use snapshots whose format would accidentally become a serialization contract.
- Do not introduce baselines, suppressions, warning allowances, or risky-test allowances.

---

# 12. COMPOSER AND TOOLING EXPANSION

Preserve every accepted Phase 0B/0C security and quality control.

## Package validation

Update `validate:packages` so both package manifests are validated explicitly and cross-platform in deterministic lexicographic directory order:

```text
packages/application-graph
packages/kernel
```

Use a Composer script array or explicit Composer commands. Do not introduce shell-specific loops, PowerShell-only logic, Bash-only logic, a production CLI, or an external script dependency.

The root `quality` stage order remains exactly:

```text
validate:composer
validate:packages
audit:locked
platform
cs
analyse
test
```

Do not recreate reserved `validate` or `audit` script collisions.

## Autoload

After Composer changes:

1. update only the local `kongono/application-graph` package and required root metadata;
2. regenerate Composer autoload metadata;
3. run optimized strict PSR-4 validation;
4. verify all new production classes resolve through root `vendor/autoload.php`;
5. verify both Kernel and Application Graph test namespaces resolve through the development autoloader;
6. verify both test namespaces are absent when generating a no-dev autoloader;
7. restore the development autoloader after the no-dev check;
8. verify the existing Kernel mappings remain correct.

Do not add package test namespaces to production autoload.

## PHPStan

Add:

```text
packages/application-graph/src
packages/application-graph/tests
tests/Architecture
```

Preserve maximum level and strict rules. Require complete generic/list typing.

Do not add baselines, ignores, exclusions, broad `mixed`, suppression annotations, dynamic properties, or weakened checks.

## PHP CS Fixer

Add Application Graph source/tests and the relocated root architecture test to the existing finder. Preserve the accepted PER-CS policy.

## PHPUnit

Add the Application Graph package tests to the root suite and source boundary. Ensure the relocated architecture test runs exactly once.

Do not duplicate tool dependencies inside package manifests.

## Lockfile

Generate `composer.lock` through the narrowest Composer update that adds `kongono/application-graph` without updating unrelated external packages.

Verify:

- `kongono/application-graph` is locked as a local path package;
- its dependency upon `kongono/kernel` resolves;
- all existing external package versions remain unchanged;
- no new external transitive dependency appears;
- no plugin is authorized.

---

# 13. DOCUMENTATION AND ADR UPDATES

Documentation must clearly distinguish:

```text
Implemented structural model
Experimental semantics
Future authoritative Graph
Deferred stable identity
Deferred schema and serialization
Deferred discovery and compilation
Prohibited domain-specific capabilities
```

## Package README

`packages/application-graph/README.md` must explain:

- the package's narrow structural role;
- source-language neutrality;
- nodes, keys, kinds, and directed relations;
- canonical ordering;
- structural validation;
- immutability;
- the package exception catch boundary and its deliberate separation from the Kernel marker;
- API status;
- why `NodeKey` is not a stable Graph/documentation ID;
- why open kind labels are not a finalized schema;
- what the package explicitly does not implement.

Do not claim that PHP applications are currently discovered or compiled into a Graph.

## Architecture documentation

Update `docs/architecture/application-graph.md` to record:

- the implemented Phase 0D structural snapshot;
- the exact current invariants;
- its relationship to the language-neutral direction;
- why NodeKey is graph-local and opaque;
- why domain node/relation kinds remain open;
- why serialization, schema versioning, stable IDs, provenance, metadata, visibility, discovery, and compiler mechanics remain unresolved;
- why this model is not yet the full authoritative application representation.

Update `docs/architecture/overview.md` and `ARCHITECTURE.md` current-reality sections only as necessary.

Do not rewrite unrelated doctrine.

## Engineering documentation

Create:

```text
docs/engineering/application-graph-structure.md
```

It must document:

- exact public constructors and accessors;
- canonical ordering;
- exact equality rules;
- structural validation rules;
- exception behavior;
- testing expectations;
- package dependency and temporary `*` constraint;
- correct and incorrect usage;
- explicit non-goals.

Update:

```text
docs/engineering/README.md
docs/engineering/package-layout.md
docs/engineering/toolchain.md
docs/engineering/testing.md
docs/engineering/static-analysis.md
docs/engineering/php-style.md
```

only where current commands, package lists, paths, or architecture-test ownership change.

## Root documentation and glossary

Update directly relevant current-reality statements in:

```text
README.md
CONTRIBUTING.md
docs/doctrine.md
docs/glossary.md
```

The glossary must define or refine:

```text
Application Graph structural snapshot
Node
Node Key
Node Kind
Relation
Relation Kind
Canonical Graph order
Graph exception boundary
```

It must explicitly distinguish Node Key from a stable Application Graph ID and Documentation ID.

## ADRs

Inspect the ADR index and numbering before creating a new decision record.

1. Update ADR 0003 only to record the second physical package and root ownership of repository-wide API-status enforcement.
2. Update ADR 0005 only to record the implemented structural subset without changing or overstating its original decision.
3. Create one new ADR for the immutable Application Graph structural snapshot.
4. Include the package exception boundary, graph-local key semantics, open kinds, deterministic ordering, and structural validation in that ADR.
5. Do not create a serialization, stable-ID, PHP-discovery, compiler, or domain-schema ADR.

The new ADR must include:

```text
Status
Date
Context
Decision
Rationale
Consequences
Rejected alternatives
What remains open
```

Rejected alternatives must cover at minimum:

- binding the model to PHP reflection or attributes;
- using PHP class names as canonical node identity;
- selecting UUIDs, hashes, URIs, or documentation IDs prematurely;
- arbitrary metadata bags;
- domain-specific node and relation enums in this phase;
- mutable Graph objects;
- input-order-dependent iteration;
- silently accepting dangling relations;
- silently replacing duplicate nodes;
- implementing serialization before schema/versioning decisions;
- placing repository-wide package inventory tests inside the Kernel package.

Open decisions must include at minimum:

- stable cross-build identifiers;
- node and relation kind vocabularies;
- domain-specific schemas;
- metadata and provenance;
- visibility and security filtering;
- source frontends;
- PHP attributes/discovery;
- compilation and validation phases;
- merging/contribution rules;
- serialization and schema versioning;
- persistence and caching;
- hashing/fingerprints;
- Graph diffs;
- query/traversal APIs;
- inter-package version constraints;
- API promotion.

---

# 14. EXPLICITLY PROHIBITED SCOPE

Do not implement, scaffold, configure, or add dependencies for any of the following.

## Stable identity and serialization

- stable Application Graph IDs;
- documentation IDs;
- UUID/ULID generation;
- hashes or fingerprints;
- URI/URN schemes;
- Graph schema versions;
- JSON, YAML, XML, MessagePack, PHP serialization, database serialization, or custom wire formats;
- import/export;
- persistence or cache formats;
- snapshots written to disk;
- compatibility readers/writers.

## Graph metadata and domains

- arbitrary metadata/property bags;
- source locations or source maps;
- provenance or attribution;
- visibility/security filters;
- module/service/route/action/model/entity/event/job/workflow/policy/permission/principal/agent/delegation/capability/extension/frontend/API node classes;
- built-in node-kind or relation-kind constants/enums;
- typed domain payloads;
- contract schemas;
- security metadata;
- documentation links.

## Discovery, IR frontend, and compiler

- PHP attributes for Graph concepts;
- reflection or AST source discovery;
- Composer package discovery;
- directory scanners in production;
- annotations;
- compiler passes;
- contribution APIs;
- graph merging;
- validation pipelines beyond the constructor's structural rules;
- generated code;
- cached compilation;
- incremental builds;
- watch mode;
- plugin hooks;
- AI/LLM inference.

The repository architecture test's token-based source discovery remains test-only and is not a Graph frontend.

## Query and mutation systems

- graph builders;
- mutable graphs;
- add/remove/update operations;
- query DSLs;
- traversal engines;
- visitors;
- cycle analysis;
- topological sorting;
- dependency legality rules;
- diff/patch APIs;
- indexing beyond exact node lookup;
- performance cache layers.

## Later framework capabilities

- DI/container/service registry/autowiring/factories;
- HTTP/router/middleware/controllers/actions;
- configuration/environment loading;
- runtime adapters;
- operation-scope data or cleanup;
- ORM/data/migrations/transactions;
- events/queues/jobs/workflows/scheduling;
- authentication/authorization/principals/delegation;
- agents/AI/MCP/A2A;
- extensions/plugins/sandboxing;
- official `kongo` CLI;
- frontend/Vite/Node/TypeScript;
- OpenAPI;
- OpenTelemetry/logging/tracing/metrics;
- deployment, publishing, splitting, release, or signing automation.

## Process prohibitions

- no unrelated dependency upgrade;
- no external dependency addition;
- no Composer plugin authorization;
- no security-policy weakening;
- no static-analysis baseline or suppression;
- no invented license or version;
- no unrelated refactor;
- no Git index normalization;
- no commit, branch, remote, or push;
- no destructive Git action.

---

# 15. REQUIRED VALIDATION

Run every applicable command from the repository root and record exact results.

## Environment and state

```bash
php -v
php --ini
composer --version
git --version
git status --short
```

## Composer

```bash
composer validate
composer validate:composer
composer validate:packages
composer audit
composer audit:locked
composer platform
composer check-platform-reqs
composer install --dry-run --no-interaction
```

Confirm:

- root and both package manifests validate with only accepted warnings;
- lockfile is current;
- both local packages resolve from `packages/*`;
- no external package version changed;
- no external package was added or removed;
- no advisory, malware, abandoned-package, or plugin policy changed;
- no Composer plugin exists or is authorized.

## Autoload

Run optimized strict PSR-4 generation, then directly resolve through root `vendor/autoload.php` at minimum:

```text
Kongono\ApplicationGraph\Model\ApplicationGraph
Kongono\ApplicationGraph\Model\Node
Kongono\ApplicationGraph\Model\NodeKey
Kongono\ApplicationGraph\Model\NodeKind
Kongono\ApplicationGraph\Model\Relation
Kongono\ApplicationGraph\Model\RelationKind
Kongono\ApplicationGraph\Exception\ExceptionInterface
Kongono\Kernel\Kernel
```

Also directly verify development autoload resolves:

```text
Kongono\ApplicationGraph\Tests\Unit\ApplicationGraphTest
Kongono\Kernel\Tests\Unit\KernelTest
Kongono\Tests\Architecture\ApiStatusConventionTest
```

Generate an optimized no-dev autoloader and verify those three test classes do not resolve while production classes still do. Restore the optimized development autoloader afterward and re-verify test resolution.

## Quality

```bash
composer cs
composer analyse
composer test
composer quality
composer -vvv quality
```

Confirm:

- PHP CS Fixer has zero required changes;
- PHPStan at maximum strictness has zero errors;
- all Kernel, Application Graph, architecture, and tooling tests pass;
- PHPUnit has zero errors, failures, warnings, risky tests, deprecations, notices, or incomplete tests;
- verbose quality dispatch retains the exact accepted order;
- no baseline, ignore, suppression, exclusion, or warning allowance was added.

## Behavioral verification

In addition to PHPUnit, use a minimal read-only PHP command through `vendor/autoload.php` to construct a small out-of-order graph and report the returned canonical node and relation order. This is validation evidence only. Do not create a production executable or committed fixture.

## Prohibited-scope audit

Search all Phase 0D production source, manifests, and lock changes for prohibited concepts. Manually review matches rather than assuming every keyword match is a violation. Report method and outcome.

## Repository state

```bash
git diff --check
git diff --cached --check
git diff --stat
git status --short
```

Because most repository files remain untracked, supplement Git diff output with an exact Phase 0D file inventory. Distinguish:

```text
pre-existing staged state
pre-existing unstaged state
pre-existing untracked state
Phase 0D created files
Phase 0D modified files
concurrent changes, if any
```

Do not alter the index to make the report simpler.

---

# 16. STRICT ACCEPTANCE CRITERIA

Phase 0D is complete only if every item passes.

## Authority and scope

- [ ] Required doctrine, ADRs, package code, tests, and configuration were read before editing.
- [ ] Baseline `composer quality` passed.
- [ ] Existing staged, unstaged, and untracked work was preserved.
- [ ] No concurrent edit was overwritten.
- [ ] No unresolved decision was guessed.
- [ ] No prohibited later capability was implemented or scaffolded.

## Package and dependency boundary

- [ ] `kongono/application-graph` exists with exactly the authorized production symbols.
- [ ] Package namespace and test namespace are correct.
- [ ] The package requires PHP >=8.5 and temporary internal `kongono/kernel: *` only.
- [ ] Root requires both local packages with `@dev`.
- [ ] The canonical `packages/*` repository remains unchanged.
- [ ] Composer locks the new local package without changing external package versions.
- [ ] No external dependency or plugin was added.
- [ ] No license, release version, or general versioning policy was invented.

## Structural model

- [ ] NodeKey, NodeKind, and RelationKind reject only empty strings and preserve other values exactly.
- [ ] Node contains exactly key and kind.
- [ ] Relation contains exactly source, kind, and target.
- [ ] ApplicationGraph rejects non-list arrays and validates runtime elements.
- [ ] Empty graphs are valid.
- [ ] Duplicate node keys are rejected by exact string equality.
- [ ] Dangling source and target references are rejected.
- [ ] Exact duplicate relation triples are rejected.
- [ ] Self-relations, cycles, and different relation kinds between the same endpoints are allowed.
- [ ] Node and relation results use the required canonical binary-string order.
- [ ] Lookup uses value equality, not NodeKey object identity.
- [ ] The complete model is immutable and instance-owned.
- [ ] No ambient, static, or global Graph state exists.

## Language neutrality and deferred semantics

- [ ] Production model contains no PHP reflection, attributes-as-data, FQCN semantics, closures, resources, services, runtime state, or arbitrary payloads.
- [ ] NodeKey is documented as graph-local and not a stable Graph/documentation ID.
- [ ] Kinds are open labels and not a finalized domain schema.
- [ ] No serialization, schema version, provenance, security visibility, frontend, discovery, compiler, merge, query, traversal, mutation, hashing, or persistence system exists.
- [ ] The structural model is not falsely described as the complete authoritative Graph.

## Exceptions and API status

- [ ] Exact exception hierarchy is implemented.
- [ ] Deliberate Graph failures are catchable through the Graph marker and do not implement the Kernel marker.
- [ ] Exact Phase 0D API-status mapping is enforced.
- [ ] No additional Public Graph model API was created.
- [ ] Status remains compatibility metadata rather than Graph/security metadata.

## Architecture enforcement

- [ ] API-status convention test moved from the Kernel package to the root architecture suite.
- [ ] The original test no longer exists under the Kernel package.
- [ ] Root architecture test discovers both package source trees.
- [ ] Exact inventory and statuses for both packages are enforced.
- [ ] The test-only source scanner did not become production code.

## Tests and quality

- [ ] Every required behavior in Section 11 is tested.
- [ ] Tests do not depend on private collection implementation.
- [ ] Development test namespaces resolve through root autoload.
- [ ] Test namespaces are absent from no-dev autoload.
- [ ] Production namespaces resolve in development and no-dev modes.
- [ ] Both package manifests are validated by `validate:packages`.
- [ ] PHPStan max/strict, style, PHPUnit, and full quality pass.
- [ ] Quality dispatch order is unchanged.
- [ ] No baseline, suppression, exclusion, policy exception, or weakened gate exists.

## Documentation and ADRs

- [ ] Package README, architecture, engineering, glossary, and contributor documentation match implemented reality.
- [ ] Planned Graph capabilities remain labeled planned.
- [ ] ADR 0003 and ADR 0005 are updated without changing their accepted intent.
- [ ] One new ADR records only the Phase 0D structural decision.
- [ ] Stable IDs, schemas, serialization, discovery, compiler, metadata, visibility, and domain vocabularies remain explicitly open.
- [ ] No false feature claims or vague production documentation exist.

## Git and process

- [ ] `git diff --check` and cached diff check pass.
- [ ] The mixed pre-existing index was preserved.
- [ ] No commit, branch, remote, push, or destructive Git action occurred.
- [ ] Final report distinguishes pre-existing, Phase 0D, and concurrent states.

If any item fails, status is Partial or Blocked—not Complete.

---

# 17. REQUIRED EXECUTOR COMPLETION REPORT

Return exactly this structure:

```text
PHASE 0D TASK COMPLETION REPORT

1. STATUS
Complete / Partial / Blocked

2. SUMMARY
Concise description of authorized work completed.

3. BASELINE OBSERVED
- pre-change quality result
- environment versions
- accepted warnings
- staged, unstaged, and untracked state
- known Phase 0C.1 index mismatch

4. FILES CREATED
Exact paths and one-line purpose.

5. FILES MODIFIED
Exact paths and reason.

6. FILES MOVED / REMOVED
Exact old and new paths and reason.

7. PACKAGE AND DEPENDENCY CONTRACT
- package tree
- package identity
- package requirements
- root requirements
- path repository
- lockfile result
- confirmation of unchanged external packages

8. STRUCTURAL MODEL INVENTORY
- every production FQCN
- exact state held by each model
- exact public methods
- final/readonly status

9. GRAPH INVARIANTS
- key equality
- duplicate behavior
- endpoint validation
- relation equality
- self-relation and cycle behavior
- canonical ordering
- lookup behavior
- immutability

10. LANGUAGE-NEUTRALITY AND DEFERRED-SEMANTICS AUDIT
- PHP coupling audit
- stable-ID audit
- schema/domain audit
- serialization audit
- discovery/compiler audit
- metadata/security/provenance audit

11. EXCEPTION AND API-STATUS CONTRACT
- hierarchy
- deliberate throw sites
- full FQCN-to-status inventory
- Graph marker and Kernel-marker separation verification

12. ARCHITECTURE-TEST RELOCATION
- old path
- new path
- namespace
- discovery scope
- exact inventory result
- confirmation original was removed

13. TEST RESULTS
- test files
- behaviors covered
- test count
- assertion count
- warnings/risky/deprecations/notices/incomplete status

14. COMPOSER, AUTOLOAD, AND SECURITY RESULTS
- manifest validation
- lock result
- direct production autoload
- direct development-test autoload
- no-dev autoload
- dependency audit
- security policy
- plugin status

15. STATIC ANALYSIS, STYLE, AND QUALITY RESULTS
- PHPStan version/level/result
- style result
- quality result
- verbose dispatch order
- baseline/suppression confirmation

16. DOCUMENTATION AND ADR RESULTS
- documentation created/updated
- ADRs updated
- new ADR number/title
- open decisions preserved

17. PROHIBITED-SCOPE AUDIT
- method
- result for identity/serialization
- result for domain metadata
- result for discovery/compiler
- result for query/mutation
- result for DI/HTTP/data/agents/extensions/CLI/frontend/telemetry

18. DEVIATIONS
None, or exact deviation, reason, and approval status.

19. QUESTIONS / BLOCKERS
None, or exact architect action required.

20. GIT STATUS SUMMARY
- pre-existing index/worktree state
- Phase 0D created/modified/moved files
- concurrent changes
- diff checks
- confirmation no commit/push

21. ACCEPTANCE CHECKLIST
Pass/Fail for every Section 16 criterion.

22. NEXT STEP
Await architect review before Phase 0E.
```

For item 22, state only:

> Await architect review before Phase 0E.

Do not begin Phase 0E.

---

# 18. FINAL EXECUTION INSTRUCTION

Implement the smallest correct Phase 0D structural boundary.

The successful result is deliberately limited:

```text
One new local package
Opaque graph-local keys
Open kind labels
Immutable nodes and directed relations
One immutable validated snapshot
Deterministic canonical order
Exact node lookup
One package exception boundary
Repository-wide API-status enforcement
Strict production tests and quality gates
No stable IDs
No domain schema
No serialization
No discovery or compiler
No later framework capabilities
```

When implementation and validation are complete, stop and return the required report.
