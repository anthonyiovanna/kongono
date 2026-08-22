# CODEX IMPLEMENTATION DIRECTIVE — KONGONO PHASE 0C

## First Framework Boundary: Monorepo Packages and Kernel Lifecycle

## Mission

You are the implementation executor for Kongono Framework.

Phases 0A and 0B are complete and architecturally accepted. The repository already contains Kongono's locked doctrine, governance, documentation rules, ADR system, PHP 8.5 toolchain, Composer security policy, coding standard, static analysis at maximum strictness, PHPUnit, and the deterministic `composer quality` command contract.

This task creates Kongono's first real production framework boundary. Implement only:

- the initial physical monorepo package layout;
- the `kongono/kernel` Composer package;
- a minimal Kernel state machine;
- explicit lifecycle contracts suitable for persistent runtimes;
- opaque operation scopes that can overlap without global current-operation state;
- machine-checkable Internal, Experimental, and Public API status conventions;
- the Kernel package's exception foundation;
- the first tests of production framework behavior;
- the minimum Composer, autoload, analysis, style, and test expansion needed for the package;
- directly relevant architecture, engineering, glossary, README, and ADR updates.

This is Phase **0C — First Framework Boundary**.

This is not a general kernel build-out. Do not implement later capabilities.

---

# 1. ROLE, AUTHORITY, AND MANDATORY STOP RULE

You are an **executor, not the architect**.

All repository doctrine and accepted ADRs remain authoritative. Read them before editing, including at minimum:

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
docs/architecture/microkernel.md
docs/architecture/runtime-model.md
docs/engineering/toolchain.md
docs/adr/README.md
all Accepted ADRs relevant to the monorepo, dependencies, tooling,
API stability, runtime lifecycle, or package boundaries
```

Do not reinterpret, weaken, silently supersede, or duplicate an accepted decision.

## Mandatory stop rule

If completing the authorized work requires an architectural, security, package-boundary, dependency, public-API, lifecycle, compatibility, protocol, licensing, concurrency, data-model, Application Graph, or release decision not explicitly resolved by this directive or an existing Accepted ADR:

**STOP. DO NOT GUESS.**

Report:

1. the exact decision required;
2. why the authorized work cannot safely proceed without it;
3. the viable options;
4. the tradeoffs of each option;
5. your recommendation, clearly labeled as a recommendation only.

Do not implement the recommendation until architect approval is returned.

## Conflict rule

If this directive conflicts with a LOCKED doctrine statement or Accepted ADR:

1. stop the conflicting portion;
2. identify both statements precisely;
3. continue only with independent work that cannot prejudice the decision;
4. report the conflict.

Do not resolve a conflict by treating the newer text as an automatic override.

---

# 2. PRECONDITIONS AND BASELINE INSPECTION

Before changing files:

1. Read the authority files listed above.
2. Inspect the complete repository tree.
3. Inspect `git status --short` and preserve all existing work.
4. Inspect the current Composer scripts and their exact commands.
5. Inspect the current Composer dependency and security configuration.
6. Inspect the existing test, PHPStan, and code-style paths.
7. Inspect the current ADR numbering and index.
8. Run the existing quality gate once:

```bash
composer quality
```

If the pre-change quality gate fails for a reason caused by the existing repository rather than Phase 0C work, do not conceal it. Record the exact failure and determine whether Phase 0C can safely proceed. Stop if the failure prevents reliable validation.

The known pre-alpha warnings about the missing license and Composer being unable to derive the root package version are accepted. Do not invent a license or hard-code a root version to silence them.

Do not modify, stage, unstage, discard, or overwrite unrelated user work. Do not normalize the Git index unless separately authorized. Report its state exactly.

---

# 3. PREVIOUSLY LOCKED DOCTRINE REMAINS IN FORCE

The following are reminders, not replacements for repository doctrine.

```text
Project:          Kongono
Framework name:   Kongono Framework
Primary domain:   kongono.com
PHP namespace:    Kongono\
Composer vendor:  kongono
Official CLI:     kongo
Minimum PHP:      PHP 8.5
Primary language: PHP
Repository model: Monorepo for initial development
```

Kongono remains:

- explicit over magical;
- typed, inspectable, deterministic, and statically verifiable;
- persistent-runtime-first;
- a small microkernel plus independently installable capabilities;
- designed for humans, software clients, and AI agents;
- governed by “AI may suggest; Kongono must know”;
- governed by the smallest-correct-implementation rule;
- governed by the strict no-assumption rule;
- not a Laravel clone.

The Application Graph remains the future authoritative machine-readable application representation, derived from a language-neutral IR. Its schema, identifiers, serialization, compiler, discovery model, and PHP frontend remain outside this phase.

Do not use Phase 0C as a reason to decide any deferred subject.

---

# 4. PHASE 0C ARCHITECTURAL BOUNDARY — LOCKED

Phase 0C establishes one package and one lifecycle boundary:

```text
Kongono monorepo
└── packages/
    └── kernel/          Composer package: kongono/kernel
```

The Kernel package is responsible only for:

```text
Kernel boot state
Kernel shutdown state
Operation-scope creation
Operation-scope ownership
Operation-scope completion
Active-operation accounting
Lifecycle misuse detection
Kernel exception classification
API-status metadata used by Kongono source
```

The Kernel package is not responsible for:

```text
Dependency resolution
Service registration
Object construction
Request handling
Routing
Middleware
Command dispatch
Events
Queues
Jobs
Workflows
Configuration loading
Environment loading
Application discovery
Module discovery
Graph construction
Graph compilation
Serialization
Caching
Runtime adapter integration
Telemetry emission
Logging
Authorization
Extension loading
Agent execution
```

This boundary is intentionally small. Do not add “helpful” abstractions for future phases.

---

# 5. MONOREPO PACKAGE LAYOUT — LOCKED

Create this package layout:

```text
packages/
└── kernel/
    ├── composer.json
    ├── README.md
    ├── src/
    │   ├── Api/
    │   │   ├── Api.php
    │   │   └── ApiStatus.php
    │   ├── Contract/
    │   │   ├── KernelInterface.php
    │   │   ├── KernelLifecycleInterface.php
    │   │   └── OperationLifecycleInterface.php
    │   ├── Exception/
    │   │   ├── ExceptionInterface.php
    │   │   ├── KernelException.php
    │   │   └── LifecycleException.php
    │   ├── Lifecycle/
    │   │   ├── KernelState.php
    │   │   └── OperationScope.php
    │   └── Kernel.php
    └── tests/
        ├── Architecture/
        │   └── ApiStatusConventionTest.php
        └── Unit/
            └── KernelTest.php
```

Minor additional test fixtures may be created under `packages/kernel/tests/Fixture/` only when a named acceptance test cannot be clear without them. Do not create additional production directories or production symbols without stopping for approval.

## Package identity

`packages/kernel/composer.json` must describe:

```text
name:        kongono/kernel
type:        library
PHP:         >=8.5, consistent with the accepted repository constraint
autoload:    Kongono\Kernel\ => src/
autoload-dev: Kongono\Kernel\Tests\ => tests/
```

Use the repository's established metadata style. Do not add:

- a package version;
- a license not already architecturally selected;
- a minimum-stability setting;
- a Composer plugin;
- an external production dependency;
- an external development dependency;
- a branch alias;
- publishing, split, or release automation.

The Kernel package may later be published independently. Phase 0C establishes a package that is structurally capable of that future, but it does not perform publication or promise a release cadence.

## Root-package integration

Update the root Composer manifest so the root framework consumes the local `kongono/kernel` package through a Composer `path` repository targeting `packages/*`.

Requirements:

1. The root package must require `kongono/kernel` as a production dependency using exactly `"@dev"` as its constraint.
2. The per-package `@dev` stability flag is authorized only for this untagged local package. Do not change the repository-wide minimum stability.
3. Do not hard-code a fake release version.
4. Do not duplicate `Kongono\Kernel\` in the root package's PSR-4 mapping. The package owns its namespace.
5. Composer must resolve the source from `packages/kernel` and generate its autoload metadata.
6. Update `composer.lock` only through Composer.
7. Do not manually edit `composer.lock`.

Use `packages/*` as the path-repository URL and keep the repository canonical. Use the smallest valid Composer path-repository configuration. Do not add repository-splitting metadata or mirroring automation.

---

# 6. API STATUS CONVENTION — LOCKED

Kongono requires a machine-checkable API status for every production class, interface, trait, and enum.

Implement:

```php
Kongono\Kernel\Api\Api
Kongono\Kernel\Api\ApiStatus
```

## `ApiStatus`

`ApiStatus` is a string-backed enum with exactly these cases and values:

```text
Internal      internal
Experimental  experimental
Public        public
```

Do not add Stable or Deprecated in this phase. Those concepts remain part of the broader documentation vocabulary, but their executable/versioning semantics are not being designed here.

## `Api`

`Api` is a final, immutable PHP attribute accepted on class-like declarations:

```text
class
interface
trait
enum
```

It accepts exactly one `ApiStatus` value. It must not be repeatable.

The `Api` attribute and `ApiStatus` enum are the two bootstrap metadata types. They are themselves Public API by definition and must be clearly documented as the only explicit bootstrap exception if PHP's attribute-resolution rules make self-classification inappropriate. Do not create any other exception to classification.

## Status semantics

### Internal

```text
Audience:       Kongono framework internals only
External use:   Unsupported
Compatibility: No backward-compatibility promise
Extension use:  Prohibited unless a later Accepted contract says otherwise
```

Internal does not mean private in PHP visibility terms. It means that external consumers must not depend upon the symbol.

### Experimental

```text
Audience:       Early adopters, evaluators, and future component authors
External use:   Allowed only with explicit awareness of instability
Compatibility: No backward-compatibility promise before promotion
Extension use:  Only as documented by that experimental contract
```

Experimental APIs must not be described as stable or production-guaranteed.

### Public

```text
Audience:       Application and component consumers
External use:   Supported
Compatibility: Governed by Kongono's versioning policy and release maturity
Extension use:  As documented by the specific contract
```

Public does not mean “unchangeable forever.” Before 1.0, compatibility follows the accepted versioning doctrine. Changes must still be deliberate, documented, and reviewed.

## Classification rules

1. Every production class-like symbol under every `packages/*/src` directory must carry exactly one `Api` classification, except the two documented bootstrap metadata types.
2. No default status exists.
3. Missing status is a test failure.
4. Multiple statuses are impossible because the attribute is not repeatable and must also be treated as a test failure.
5. A type's status applies to its public and protected members in Phase 0C.
6. Per-member status attributes are not authorized.
7. File path or namespace alone must not silently determine status.
8. API status is compatibility/exposure metadata. It is not authorization, package visibility enforcement, an Application Graph node, or a security boundary.
9. Do not introduce third-party API-status dependencies.

## Phase 0C classifications

Classify the Phase 0C symbols as follows:

```text
Public
  Kongono\Kernel\Api\Api
  Kongono\Kernel\Api\ApiStatus
  Kongono\Kernel\Exception\ExceptionInterface

Experimental
  Kongono\Kernel\Kernel
  Kongono\Kernel\Contract\KernelInterface
  Kongono\Kernel\Contract\KernelLifecycleInterface
  Kongono\Kernel\Contract\OperationLifecycleInterface
  Kongono\Kernel\Exception\LifecycleException
  Kongono\Kernel\Lifecycle\KernelState
  Kongono\Kernel\Lifecycle\OperationScope

Internal
  Kongono\Kernel\Exception\KernelException
```

Do not promote additional symbols to Public.

---

# 7. KERNEL LIFECYCLE CONTRACT — LOCKED

The Kernel is a small deterministic state machine. It is runtime-neutral and transport-neutral.

## Kernel states

`KernelState` contains exactly:

```text
Unbooted
Booted
Shutdown
```

The exact backed/unbacked enum implementation may follow the repository's existing style. Do not add intermediate states, failure states, timestamps, identifiers, or serialization.

## Lifecycle contracts

Use explicit return and parameter types. The public method contract is:

```php
interface KernelLifecycleInterface
{
    public function state(): KernelState;

    public function boot(): void;

    public function shutdown(): void;
}

interface OperationLifecycleInterface
{
    public function beginOperation(): OperationScope;

    public function endOperation(OperationScope $operation): void;

    public function activeOperationCount(): int;
}

interface KernelInterface extends KernelLifecycleInterface, OperationLifecycleInterface
{
}
```

Do not add arguments, service collections, configuration, callbacks, events, hooks, runtime objects, request objects, container objects, Graph objects, or generic context arrays to these methods.

## Concrete Kernel

`Kongono\Kernel\Kernel` is final and implements `KernelInterface`.

Required behavior:

1. A new Kernel starts in `Unbooted`.
2. `boot()` is valid only from `Unbooted`.
3. Successful boot changes the state to `Booted`.
4. Repeated boot is a lifecycle violation.
5. `beginOperation()` is valid only while `Booted`.
6. Each successful `beginOperation()` returns a new, distinct `OperationScope` instance.
7. The Kernel records ownership of every active scope by object identity.
8. Multiple operation scopes may be active at the same time.
9. There is no global, static, thread-local, fiber-local, or singleton “current operation.”
10. `activeOperationCount()` reports the exact number of scopes currently owned and active in this Kernel.
11. `endOperation()` succeeds only for a currently active scope created by that same Kernel.
12. A scope cannot be ended twice.
13. A scope created by a different Kernel cannot be ended by this Kernel.
14. Ending one scope must not affect any other active scope.
15. `shutdown()` is valid only while `Booted` and only when the active-operation count is zero.
16. Attempting shutdown with active operations is a lifecycle violation and leaves the Kernel booted with those operations active.
17. Successful shutdown changes the state to `Shutdown`.
18. A shutdown Kernel cannot be booted again and cannot begin or end operations.
19. Invalid transitions must not partially mutate state.
20. Kernel construction, state inspection, active-count inspection, boot, operation begin/end, and shutdown perform no filesystem, network, environment, logging, telemetry, time, random-number, process, or global-state access.

Use an instance-owned object-identity collection appropriate for PHP 8.5, with complete PHPStan generic typing. Do not use operation string IDs, UUIDs, counters exposed as IDs, array keys derived from object hashes, or serialized handles.

## `OperationScope`

`OperationScope` is a final opaque object whose identity is the lifecycle token.

It must contain no:

- arbitrary metadata bag;
- request data;
- route data;
- principal or authorization data;
- container or service locator;
- Application Graph reference;
- runtime adapter;
- string or numeric identifier;
- serialization contract;
- user-defined callback;
- cleanup callback;
- static mutable state.

Do not make it responsible for ending itself. The explicit `Kernel::endOperation()` call keeps ownership and transition validation in one place.

## Persistent-runtime interpretation

The state machine establishes these invariants:

```text
Boot once per Kernel lifetime
Begin and end many operation scopes
Permit overlapping operations
Never infer a global current operation
Refuse shutdown while work remains active
Never assume process teardown cleans application state
```

Phase 0C does not define what data belongs inside a future operation scope, how scoped services are reset, or how runtimes propagate operation context. Those remain future decisions.

---

# 8. EXCEPTION FOUNDATION — LOCKED

Create a small Kernel exception family:

```text
Throwable
└── ExceptionInterface        marker interface for catchable Kernel failures
    └── KernelException       abstract RuntimeException foundation
        └── LifecycleException
```

Implementation requirements:

1. `ExceptionInterface` extends `Throwable` and contains no methods.
2. `KernelException` is abstract, extends PHP's `RuntimeException`, and implements `ExceptionInterface`.
3. `LifecycleException` is final and extends `KernelException`.
4. Every exception deliberately thrown by `kongono/kernel` in this phase implements `ExceptionInterface` through this hierarchy.
5. Native engine errors are not wrapped merely to satisfy the hierarchy.
6. Do not catch `Throwable` broadly.
7. Do not add error codes, localization, logging, reporting, rendering, HTTP status, CLI exit status, telemetry, retry semantics, result objects, or exception serialization.
8. Exception messages must identify the invalid transition or invalid operation ownership clearly enough to diagnose misuse.
9. Tests must assert exception type and relevant diagnostic meaning without locking entire prose unnecessarily.

`ExceptionInterface` is the single Public catch boundary introduced by Phase 0C. The concrete lifecycle behavior remains Experimental.

---

# 9. DOCUMENTATION STANDARD FOR PRODUCTION CODE

Apply the existing Kongono documentation doctrine.

Every Phase 0C production symbol must explain, as relevant:

```text
PURPOSE
ARCHITECTURAL CONTEXT
LIFECYCLE
CONCURRENCY
CALLING CONTRACT
FAILURE BEHAVIOR
SIDE EFFECTS
EXTENSION CONTRACT
STABILITY
```

Do not use vague comments such as:

```text
Handles the lifecycle.
Gets the state.
Starts an operation.
Processes shutdown.
```

Prefer precise names and types. Comments must explain context, invariants, ownership, persistent-runtime implications, or non-obvious behavior rather than restating syntax.

Do not manually list exact callers or callees. The Application Graph is intended to provide derived relationships later, but no Graph feature may be implemented in this phase.

Do not add documentation IDs, custom documentation-link attributes, or source-to-doc linking mechanisms. Their exact form remains experimental.

---

# 10. FIRST PRODUCTION TESTS — REQUIRED

The Phase 0B smoke test is no longer sufficient as the repository's primary evidence. Preserve it only if it validates something still useful and non-duplicative; otherwise remove the placeholder test after the production tests pass.

## `KernelTest`

Test at minimum:

1. new Kernel state and zero active operations;
2. successful boot;
3. boot cannot occur twice;
4. operation cannot begin before boot;
5. operation cannot begin after shutdown;
6. each operation scope has distinct object identity;
7. multiple operations may overlap;
8. exact active-operation counts through begin/end transitions;
9. ending one scope leaves other scopes active;
10. ending a foreign scope fails without mutating either Kernel;
11. ending a scope twice fails without count corruption;
12. shutdown with active operations fails and preserves state/count;
13. successful shutdown after all operations end;
14. repeated shutdown fails;
15. ending an operation after shutdown fails;
16. lifecycle failures implement `ExceptionInterface`;
17. invalid transitions do not partially mutate valid state.

Tests must assert behavior, not private implementation details. Do not use reflection to inspect the Kernel's private storage.

## `ApiStatusConventionTest`

Create an architecture test that discovers production class-like declarations under `packages/*/src` and verifies:

1. every discovered type is autoloadable;
2. every type other than the two documented metadata bootstrap types has exactly one `Api` attribute;
3. the attribute contains a valid `ApiStatus` enum;
4. the Phase 0C symbol-to-status mapping matches Section 6 exactly;
5. no undeclared production symbol exists in `packages/kernel/src`;
6. the `Api` attribute is not repeatable and targets class-like declarations;
7. no production type silently defaults to a status.

The discovery logic must be deterministic, cross-platform, and confined to repository test code. Do not add a framework CLI or production scanner.

## Test quality

- Use strict types.
- Use descriptive test names.
- Avoid test-order dependence.
- Avoid sleeps, randomness, network access, environment dependence, and process-global mutation.
- Do not use a PHPUnit baseline, warning suppression, deprecation suppression, or risky-test allowance.
- Do not add mocks where ordinary objects are clearer.

---

# 11. COMPOSER, AUTOLOAD, AND TOOLING EXPANSION

Preserve all Phase 0B security and quality controls.

## Dependencies

No new external dependency is authorized.

The only new root production requirement is the local path package:

```text
kongono/kernel
```

Do not update unrelated locked packages. Do not authorize plugins. Do not weaken advisory, malware, abandoned-package, audit, or lockfile policy.

## Composer scripts

Add an explicit package-manifest validation script named:

```text
validate:packages
```

It must validate `packages/kernel/composer.json` using Composer from the root workflow. Use a cross-platform Composer command, not shell-specific loops.

Update `quality` so it retains every existing Phase 0B stage in its established relative order and adds package validation directly after root manifest validation:

```text
@validate:composer
@validate:packages
@audit:locked
@platform
@cs
@analyse
@test
```

Do not rename the accepted commands. Do not recreate the reserved `validate` or `audit` script collisions corrected in Phase 0B.

## Autoload

After updating Composer configuration:

1. update only what is necessary to lock the local Kernel package;
2. regenerate autoload metadata through Composer;
3. verify strict PSR-4 compliance;
4. verify the root project autoloads Kernel symbols through the installed local package;
5. verify tests autoload with their intended test namespace;
6. verify a clean install from the resulting lockfile does not require network changes beyond already locked dependencies.

Do not create a custom autoloader.

## PHPStan

Expand analysis paths to cover:

```text
packages/kernel/src
packages/kernel/tests
```

Keep PHPStan at the accepted maximum level with strict rules. Do not add:

- a baseline;
- ignored errors;
- path exclusions for Phase 0C code;
- broad mixed types;
- error-suppression annotations;
- dynamic-property allowances;
- weakened generic checks.

## PHP CS Fixer

Expand the existing finder to cover Kernel source and tests. Preserve the accepted PER-CS policy. Do not alter unrelated formatting policy.

## PHPUnit

Expand PHPUnit configuration so package tests run through the root `composer test` command and Kernel production source is included in the configured source boundary where applicable.

Do not introduce a separate test runner or duplicate root tool dependencies inside the Kernel package.

---

# 12. DOCUMENTATION AND ADR UPDATES

Documentation must distinguish:

```text
Implemented now
Contract established but Experimental
Public catch boundary
Still planned
Explicitly prohibited/deferred
```

## Package README

`packages/kernel/README.md` must explain:

- the package's narrow responsibility;
- the state machine;
- operation-scope ownership and overlap;
- persistent-runtime implications;
- API status meaning;
- exception catch boundary;
- what the package explicitly does not provide;
- that no stable general framework API is implied by Experimental types.

Do not advertise unimplemented features.

## Architecture documentation

Create or update the directly relevant architecture documentation so it explains:

- the physical `packages/` layout;
- why Kernel is the first package;
- why the Kernel stays transport-, runtime-, and container-neutral;
- the boot/operation/shutdown lifecycle;
- why overlapping operations are allowed;
- why no global current operation exists;
- why operation scopes are opaque in Phase 0C;
- why shutdown fails with active work;
- how this supports persistent runtimes without selecting one;
- what remains intentionally unresolved.

Prefer updating the existing microkernel and runtime-model documents. Create `docs/architecture/kernel-lifecycle.md` only if the existing documents would become unclear or overly broad without a dedicated page.

## Engineering documentation

Create or update directly relevant engineering documentation for:

- the package directory convention;
- root/path-package Composer integration;
- package manifest validation;
- source and test namespace conventions;
- API status attribute usage and exact semantics;
- the rule that missing API status fails tests;
- the rule that status is not a security boundary.

Create `docs/engineering/package-layout.md` and `docs/engineering/api-status.md` if equivalent documents do not already exist. Do not duplicate existing documents.

## Root documentation

Update only directly relevant portions of:

```text
README.md
ARCHITECTURE.md
CONTRIBUTING.md
docs/doctrine.md
docs/glossary.md
```

Requirements:

- Current reality must now acknowledge the Kernel package and tests.
- Planned capabilities must remain labeled planned.
- The glossary must define Kernel lifecycle, Kernel state, operation scope, API status, Internal API, Experimental API, Public API, and Kernel exception boundary if absent.
- Contributor commands must include package validation if command documentation is present.
- Do not rewrite unrelated doctrine.

## ADRs

Inspect the ADR index and current numbering before creating ADRs. Never reuse an identifier.

1. Update the existing monorepo ADR only to record the implemented physical package convention and local Composer path integration. Do not change its accepted decision or rationale.
2. Create one ADR for the Kernel and operation lifecycle boundary.
3. Create one ADR for machine-checkable API status metadata.
4. Include the exception catch boundary in the Kernel lifecycle ADR unless the repository's ADR doctrine clearly requires a separate decision.

Each new ADR must use the repository's required structure and record:

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

Rejected alternatives must include, where relevant:

- a single root `src/` tree without package ownership;
- making the Kernel a container or service locator;
- request-per-process lifecycle assumptions;
- one global current-operation pointer;
- forbidding overlapping operations;
- opaque, undocumented API stability;
- namespace-only or comment-only status conventions;
- a third-party API-status package;
- operation IDs or serialization in this phase.

Do not mark unresolved future design as Accepted.

---

# 13. EXPLICITLY PROHIBITED SCOPE

Do not implement, scaffold, configure, prototype, or add dependencies for any of the following:

## Dependency injection and services

- DI container;
- service container;
- service provider;
- service registry;
- autowiring;
- binding definitions;
- factories;
- compiled container;
- service locator;
- scoped-service reset orchestration.

## HTTP and transport

- HTTP kernel;
- request or response objects;
- router or routes;
- middleware;
- controllers;
- actions/use-case dispatch;
- PSR-7, PSR-15, or server adapters;
- FrankenPHP, RoadRunner, OpenSwoole, FPM, serverless, worker, or socket adapters.

## Application Graph and compilation

- Application Graph schema;
- Graph or documentation identifiers;
- IR schema;
- Graph nodes or edges;
- serialization formats;
- source discovery;
- reflection scanner;
- attributes for services, routes, actions, models, modules, agents, or extensions;
- compiler passes;
- generated code;
- caches or manifests.

The test-only API-status discovery required by Section 10 is not an Application Graph scanner and must not escape test scope.

## Data and domain capabilities

- database abstraction;
- ORM or query builder;
- models/entities;
- migrations;
- schema system;
- repositories;
- transactions or outbox;
- cache, storage, mail, notifications, events, queues, scheduling, or workflows.

## Identity, agents, and extensions

- authentication or authorization;
- principals, policies, capabilities, delegation, trust, consent, receipts, or audit systems;
- AI providers, agents, tools, MCP, A2A, or agent protocols;
- extension manifests, hosts, permissions, sandboxing, WASM/WASI, registries, or plugin systems.

## Interfaces and operations

- official `kongo` CLI executable or command framework;
- frontend, Vite, Node.js, TypeScript, JavaScript, React, Vue, Svelte, HTMX, or asset tooling;
- OpenAPI;
- OpenTelemetry, tracing, metrics, logging, or health endpoints;
- configuration or environment loaders;
- application bootstrap file or example application;
- package splitting, publishing, signing, release, or deployment automation;
- benchmarks unless a measured Phase 0C acceptance question cannot be answered otherwise and approval is obtained first.

## Process restrictions

- no dependency upgrades unrelated to locking `kongono/kernel`;
- no Composer plugin authorization;
- no security-policy weakening;
- no static-analysis baseline or suppression;
- no invented license;
- no hard-coded root version;
- no unrelated refactor;
- no branch creation;
- no commit;
- no push;
- no remote creation;
- no destructive Git operation.

If an apparently convenient helper would cross one of these boundaries, do not add it.

---

# 14. REQUIRED VALIDATION

Run all relevant commands from the repository root and record exact results.

## Environment and baseline

```bash
php -v
php --ini
composer --version
git --version
git status --short
```

## Composer manifests and dependency security

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

- the root manifest is valid except already accepted warnings;
- the Kernel manifest is valid except the intentionally unresolved license warning if Composer emits it;
- `composer.lock` is current;
- no security advisories are present;
- no malware or abandoned-package policy is weakened;
- no Composer plugin is added or authorized;
- `kongono/kernel` resolves from `packages/kernel`;
- no external package was added or upgraded.

## Autoload

Run Composer's strict PSR-4 autoload generation appropriate to the installed Composer version, then verify through the root `vendor/autoload.php` that at minimum these resolve:

```text
Kongono\Kernel\Kernel
Kongono\Kernel\Api\Api
Kongono\Kernel\Contract\KernelInterface
Kongono\Kernel\Exception\ExceptionInterface
Kongono\Kernel\Lifecycle\OperationScope
```

Do not implement a custom validation script in production code for this check.

## Quality stages

```bash
composer cs
composer analyse
composer test
composer quality
composer -vvv quality
```

Confirm from verbose dispatch that `quality` invokes exactly the required ordered stages:

```text
validate:composer
validate:packages
audit:locked
platform
cs
analyse
test
```

Also confirm:

- PHP CS Fixer reports no files requiring changes;
- PHPStan reports zero errors at the accepted maximum level;
- every production and architecture test passes;
- PHPUnit reports no errors, failures, warnings, risky tests, or deprecations;
- no baseline, ignored error, or suppression was introduced.

## Prohibited-scope audit

Search the changed tree and Composer metadata for accidental introduction of prohibited functionality or dependencies. Report the audit method and result. Do not claim absence without checking.

## Final repository state

```bash
git diff --check
git diff --stat
git status --short
```

Also inspect the staged diff separately if files were already staged before Phase 0C. Clearly distinguish:

```text
pre-existing changes
Phase 0C changes
staged changes
unstaged changes
untracked files
```

Do not alter the index merely to simplify the report.

---

# 15. STRICT ACCEPTANCE CRITERIA

Phase 0C is complete only if every criterion below is true.

## Authority and scope

- [ ] All relevant doctrine, configuration, and ADRs were read before editing.
- [ ] Pre-existing work was preserved.
- [ ] No unresolved decision was guessed.
- [ ] No prohibited later capability was implemented or scaffolded.
- [ ] No unrelated file was changed.

## Package boundary

- [ ] `packages/kernel` exists with the exact authorized production symbols.
- [ ] `packages/kernel/composer.json` identifies `kongono/kernel` as a PHP 8.5+ library.
- [ ] The Kernel package owns `Kongono\Kernel\` PSR-4 autoloading.
- [ ] The root package consumes the Kernel through a local Composer path repository.
- [ ] The lockfile records the local package and remains reproducible.
- [ ] No external dependency or plugin was added.
- [ ] No version or license was invented.

## API status

- [ ] `ApiStatus` contains exactly Internal, Experimental, and Public.
- [ ] `Api` is final, immutable, class-like-targeted, and non-repeatable.
- [ ] Every production type has exactly one machine-readable status except the two documented bootstrap metadata types.
- [ ] The exact Phase 0C mapping matches Section 6.
- [ ] Missing classification fails a deterministic architecture test.
- [ ] Documentation states that status is not authorization or a security boundary.
- [ ] No extra Public API was created.

## Kernel lifecycle

- [ ] Kernel begins Unbooted and can boot exactly once.
- [ ] Operations can begin only while Booted.
- [ ] Multiple distinct operations may overlap.
- [ ] Operation ownership is Kernel-instance-specific and identity-based.
- [ ] No global or ambient current-operation state exists.
- [ ] Active counts remain exact after valid and invalid operations.
- [ ] Foreign and already-ended scopes are rejected.
- [ ] Shutdown is rejected while operations remain active.
- [ ] Successful shutdown is terminal.
- [ ] Invalid transitions do not partially mutate state.
- [ ] OperationScope contains no metadata, ID, callback, context bag, or serialization.
- [ ] Kernel behavior performs none of the prohibited side effects.

## Exceptions

- [ ] `ExceptionInterface` is the Public marker for deliberately thrown Kernel failures.
- [ ] `KernelException` and `LifecycleException` form the exact authorized hierarchy.
- [ ] All deliberate Phase 0C Kernel exceptions implement the marker.
- [ ] Exception messages diagnose misuse without introducing unrelated policy.

## Tests and tooling

- [ ] Tests cover every behavior listed in Section 10.
- [ ] Tests assert public behavior rather than private storage.
- [ ] `validate:packages` is cross-platform and part of `quality`.
- [ ] Root PHPUnit runs package tests.
- [ ] PHPStan and PHP CS Fixer cover package source and tests.
- [ ] Strict PSR-4 autoload generation succeeds.
- [ ] `composer quality` passes end to end.
- [ ] Verbose quality dispatch shows the required command order.
- [ ] No baseline, suppression, warning allowance, or policy weakening exists.

## Documentation and decisions

- [ ] Package, architecture, engineering, glossary, and contributor documentation reflect current reality accurately.
- [ ] Planned features remain labeled planned.
- [ ] Persistent-runtime and concurrency implications are explicit.
- [ ] The existing monorepo ADR records the physical implementation without changing prior doctrine.
- [ ] New ADRs record the lifecycle and API-status decisions.
- [ ] Open questions remain explicitly open.
- [ ] No Application Graph schema, ID, or serialization decision was made.

## Git

- [ ] `git diff --check` passes.
- [ ] No commit, branch, remote, or push was created.
- [ ] The final report distinguishes pre-existing and Phase 0C changes.

If any criterion is false, status is not Complete.

---

# 16. REQUIRED EXECUTOR COMPLETION REPORT

When finished, return exactly this structure:

```text
PHASE 0C TASK COMPLETION REPORT

1. STATUS
Complete / Partial / Blocked

2. SUMMARY
Concise description of the authorized work completed.

3. BASELINE OBSERVED
- pre-change composer quality result
- PHP, Composer, and Git versions
- pre-existing staged, unstaged, and untracked state
- accepted warnings observed

4. FILES CREATED
Exact list with one-line purpose for each file.

5. FILES MODIFIED
Exact list with one-line description of each change.

6. FILES REMOVED
Exact list and reason, or None.

7. PACKAGE AND AUTOLOAD CONTRACT
- final monorepo package tree
- root/path repository configuration
- exact Kernel package identity
- exact root requirement constraint
- lockfile result
- PSR-4 verification result

8. API STATUS INVENTORY
- every production FQCN
- Internal / Experimental / Public status for each
- architecture-test enforcement result
- bootstrap exception, if used

9. KERNEL LIFECYCLE IMPLEMENTED
- states
- valid transitions
- rejected transitions
- overlapping-operation behavior
- ownership behavior
- shutdown behavior
- confirmation that no global current-operation state exists

10. EXCEPTION CONTRACT
- exact hierarchy
- deliberate throw sites
- marker-interface verification

11. TEST RESULTS
- test files
- behaviors covered
- test count
- assertion count
- PHPUnit status including warnings/risky/deprecations

12. COMPOSER AND SECURITY RESULTS
- manifest validation
- locked audit
- platform check
- dependency changes
- plugin changes
- security-policy changes
- accepted warnings

13. STATIC ANALYSIS, STYLE, AND QUALITY RESULTS
- PHPStan result and level
- code-style result
- composer quality result
- verbose quality dispatch order
- baseline/suppression confirmation

14. DOCUMENTATION AND ADR RESULTS
- documentation created/updated
- existing ADR updated
- new ADR identifiers and titles
- open decisions explicitly preserved

15. PROHIBITED-SCOPE AUDIT
- method used
- result for DI/container
- result for HTTP/router
- result for Application Graph/schema/IDs/serialization
- result for data/ORM
- result for agents/extensions
- result for CLI/frontend/telemetry
- result for other later capabilities

16. DEVIATIONS
None, or each deviation with reason and approval status.

17. QUESTIONS / BLOCKERS
None, or exact unresolved items requiring architect action.

18. GIT STATUS SUMMARY
- Phase 0C diff summary
- staged changes
- unstaged changes
- untracked files
- confirmation that no commit or push occurred

19. ACCEPTANCE CHECKLIST
Pass/Fail for every criterion in Section 15.

20. NEXT STEP
Await architect review before Phase 0D.
```

For item 20, state only:

> Await architect review before Phase 0D.

Do not begin Phase 0D or any other follow-up work.

---

# 17. FINAL EXECUTION INSTRUCTION

Implement the smallest correct Phase 0C boundary described here.

Do not turn lifecycle vocabulary into speculative framework machinery. The successful result is deliberately modest:

```text
One monorepo package boundary
One deterministic Kernel state machine
Explicit overlapping operation scopes
One small exception family
Machine-checkable API status
Production behavior tests
Expanded existing quality gates
Accurate documentation and ADRs
No later framework capabilities
```

When implementation and validation are complete, stop and return the required report.
