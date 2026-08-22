# CODEX IMPLEMENTATION DIRECTIVE - KONGONO PHASE 2F

## Explicit Web Application Bootstrap, Kernel Operation Ownership, and Fail-Closed Retirement

**Directive type:** architecture decision, bounded implementation, tests, and documentation
**Repository:** `C:\Users\antho\Dev\kongono`
**Date:** 2026-08-20
**Executor role:** executor, not architect

---

## 1. Mission

Establish and implement Kongono's first explicit web-application composition
boundary as the twelfth first-party package:

```text
directory              packages/web-application
Composer package       kongono/web-application
production namespace   Kongono\Web\Application\
test namespace         Kongono\Web\Application\Tests\
```

Phase 2F must provide exactly these capabilities:

1. validate and assemble the accepted `RoutingRequestHandler` and
   `MiddlewarePipeline` before booting one explicitly supplied
   `KernelInterface`;
2. expose the resulting in-memory application as one PSR-15
   `RequestHandlerInterface`;
3. create one Kernel `OperationScope` for every accepted application `handle()`
   call and close that exact scope exactly once on success and failure;
4. preserve an application failure unchanged when scope cleanup succeeds;
5. preserve both failure objects when application work and scope cleanup fail
   simultaneously;
6. mark the application retired whenever lifecycle state becomes uncertain and
   reject later handling or shutdown before consulting the Kernel or handler;
7. support multiple sequential and overlapping/reentrant operations without
   storing request-specific state; and
8. expose explicit shutdown while preserving the Kernel's lifecycle rules.

This phase does not make Kongono runnable. It accepts no SAPI input, performs no
request conversion or response emission, evaluates no delivery evidence, maps
no failure to HTTP, enables no trusted diagnostic presentation, and creates no
fixture application. Those remain Phase 2G and Phase 2H work.

Phase 2F must not add DI/container resolution, Application Graph integration,
configuration loading, service scopes, runtime adapters, a supplied error
middleware, or any ambient current-request/current-operation mechanism.

---

## 2. Locked doctrine and authority

### 2.1 Read completely before mutation

Read these exact files completely, in this order:

1. `AGENTS.md`
2. `ARCHITECTURE.md`
3. `SECURITY.md`
4. `docs/doctrine.md`
5. `docs/documentation-standard.md`
6. `docs/adr/0001-kongono-identity.md`
7. `docs/adr/0003-monorepo-initial-development.md`
8. `docs/adr/0006-persistent-runtime-first.md`
9. `docs/adr/0013-documentation-doctrine.md`
10. `docs/adr/0014-php-engineering-toolchain.md`
11. `docs/adr/0016-strict-types-and-per-cs-style.md`
12. `docs/adr/0026-actionable-diagnostics-and-error-boundaries.md`
13. `docs/adr/0028-bounded-diagnostic-context-and-disclosure.md`
14. `docs/adr/0031-neutral-api-metadata-ownership-and-acyclic-package-dependencies.md`
15. `docs/adr/0043-phase-2-runnable-web-framework-milestone.md`
16. `docs/adr/0044-http-message-and-interoperability-architecture.md`
17. `docs/adr/0045-first-party-http-message-and-factory-implementation.md`
18. `docs/adr/0046-deterministic-route-declaration-and-matching.md`
19. `docs/adr/0047-explicit-psr-15-middleware-and-terminal-routing.md`
20. `docs/architecture/microkernel.md`
21. `docs/architecture/runtime-model.md`
22. `docs/architecture/http-message-model.md`
23. `docs/architecture/routing-model.md`
24. `docs/architecture/http-server-model.md`
25. `docs/architecture/phase-2-roadmap.md`
26. `docs/engineering/api-status.md`
27. `docs/engineering/dependency-injection.md`
28. `docs/engineering/error-handling.md`
29. `docs/engineering/package-layout.md`
30. `packages/kernel/README.md`
31. `packages/http-server/README.md`
32. `docs/dev_plan/kongono_phase_2E_codex_directive.md`
33. `docs/task reports/Phase_2E.md`
34. `docs/dev_plan/kongono_phase_2E_1_codex_directive_correction.md`
35. `docs/task reports/Phase_2E_1_correction.md`
36. `docs/dev_plan/kongono_phase_2F_codex_directive.md`

The filenames are exact. Do not substitute an ADR based only on its number.
Historical directives and reports are immutable evidence, not authority to
repeat their mutations or reopen their scope.

### 2.2 Locked decisions

Preserve all accepted Kongono doctrine, including:

- PHP 8.5, strict types, PER-CS, max-level PHPStan, strict PHPUnit, locked
  advisory audit, and an empty Composer plugin authorization map;
- exact package-local namespace ownership and an acyclic declared dependency
  graph;
- neutral Public/Experimental/Internal API metadata;
- package-owned actionable diagnostic IDs, fixed safe prose, bounded typed
  context, and no arbitrary caller input in diagnostic context;
- a transport-neutral PSR-free Kernel with instance-local operation ownership;
- no ambient global, static, singleton, container-current, thread-local, or
  fiber-local request, response, route, match, principal, permission, exception,
  or operation state;
- PSR-7/PSR-17 as HTTP message interoperability contracts and PSR-15 as the
  handler/middleware interoperability contract;
- separate HTTP Message, Routing, and HTTP Server package ownership;
- exact Phase 2D routing and Phase 2E middleware/terminal semantics;
- trusted diagnostic presentation requiring two independent typed permissions,
  with denial before source access; and
- Phase 2H, not Phase 2F, remaining the first runnable black-box milestone.

Stop and ask rather than infer any decision outside this directive.

### 2.3 Phase-boundary reconciliation

The accepted roadmap assigns application composition, Kernel operation
ownership, and persistent-runtime cleanup rules to Phase 2F. Some earlier
Phase 2B wording says Phase 2G owns exact-once cleanup. Reconcile these texts
precisely as follows:

- Phase 2F owns opening and closing the Kernel operation scope around one
  in-memory application-handler invocation;
- Phase 2G owns the outer adapter's inbound conversion, delivery evidence,
  response delivery/emission, generic and trusted failure delivery, and cleanup
  of adapter-owned transport resources;
- the Phase 2G adapter must invoke the Phase 2F application boundary and must
  not open a second Kernel operation scope for the same application call;
- Phase 2F selects only Kernel-scope cleanup and simultaneous application-plus-
  scope-cleanup representation;
- body-stream ownership, uploaded-resource cleanup, cancellation, transport
  cleanup, incident recording, logging, telemetry, and broader resource cleanup
  remain unselected.

This is an explicit ADR 0048 amendment, not permission to weaken exact-once
scope closure or move transport into the application package.

---

## 3. Caller authentication and immutable records

### 3.1 Independent directive identity

Before quality checks, edits, dependency operations, or any other mutation,
compare this directive with the architect-supplied byte count and SHA-256
delivered outside this file.

The local file is not self-authenticating. Do not calculate its identity and
adopt that result as authority. If either independently supplied value is absent
or differs, stop before broad validation or mutation and report the observed
identity.

### 3.2 Caller-managed report

The caller must provide both:

```text
docs/dev_plan/kongono_phase_2F_codex_directive.md
docs/task reports/Phase_2F.md
```

`Phase_2F.md` must initially be an ordinary, untracked, unstaged, zero-byte file
absent from `HEAD`, with SHA-256:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

Do not create, truncate, replace, stage, commit, or populate either caller
record. The report may be populated only after every permitted non-report gate
passes.

### 3.3 Immediate accepted records

These records are immutable:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `docs/dev_plan/kongono_phase_2E_codex_directive.md` | 41768 | `53CD9AA94E6F5A2518E94C4FB1FAA9E3461C5E87A13C02E923BF4F0F38CF270A` |
| `docs/task reports/Phase_2E.md` | 25112 | `44FB493A64FD50C4707E8ABDBBD1C75E923F038C6C48070F56B4F63F756260B5` |
| `docs/dev_plan/kongono_phase_2E_1_codex_directive_correction.md` | 19554 | `C659F596208376B82BE5C0C280950CC6DAB8EE2AD9AC3094A1EE371D5387B928` |
| `docs/task reports/Phase_2E_1_correction.md` | 18561 | `0AE1128FB3735CF298C856C07D8FD1503129B9BD0815EB486A044148558B0C58` |

All earlier directive and report records are also immutable. Record their
pre-change identities and prove they remain exact. Do not normalize historical
line endings or apparently redundant final bytes.

---

## 4. Exact starting state

After the caller provides the Phase 2F directive and zero-byte report, verify:

```text
branch: main
HEAD: d7846f137a1d53f5306f48d19196b916fde44ac2
HEAD tree: bfa1908c619069b699bc9b08668db17ea2555eb3
HEAD parent: 8fe793b124384744ac43887037360154c149241a
commit count: 9
tracked files: 358
tracked modes other than 100644: 0
tracked modifications: 18
untracked paths: 109
total status paths: 127
staged paths: 0
Git-visible paths: 467
package directories: 11
package manifests: 11
production PHP files: 86
test PHP files: 110
PHP files covered by PHP CS Fixer: 196
Composer locked production packages: 15
Composer locked development packages: 66
```

Count untracked paths with full expansion equivalent to
`git status --porcelain=v1 -uall`; do not rely on collapsed directory output.

The repository must have exactly one local branch, no remotes, no tags, and no
unexpected refs. Codex-managed `refs/codex/turn-diffs/**` are environmental and
exempt; enumerate them separately and do not modify them.

The current root/index state is immutable except for the exact authorized
worktree paths and generated lockfile delta:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `composer.json` | 3872 | `50E0D6A1C7BE06B8DD4F3F68AF16D3839BF921D52604D68DAD4891CF909CE476` |
| `composer.lock` | 193651 | `521CFB7BACF023C463B95C7146D6077CC34BD0871E2DFEFB3807DA89CD4552DC` |
| `.php-cs-fixer.dist.php` | 1915 | `60C1DFD78FB517259C34CB8E4D0522372B61DE37ECD312133A94FAE0E49EE1B5` |
| `phpstan.neon.dist` | 1070 | `69DE9749186DC49687ABBEB103FE45F42D5067B71239B45EE331F52890D6FC8E` |
| `phpunit.xml.dist` | 2194 | `57CDE068267FAF05E97995696B4934837BE4A18885C0773FD0DFB878CCE9ED26` |
| `tests/Architecture/ApiStatusConventionTest.php` | 60445 | `678104EE2CB4F7832E0B65D9FD57D9AE9425B08789411D8730285B580DF5F1D4` |

Also verify before mutation:

- `packages/web-application` does not exist;
- `docs/adr/0048-web-application-bootstrap-and-kernel-operation-ownership.md`
  does not exist;
- `docs/architecture/web-application-model.md` does not exist;
- all 12 HTTP Server PHP files retain their accepted Phase 2E.1 bytes;
- all ten Kernel production PHP files and Kernel tests retain their accepted
  bytes;
- all 465 pre-caller Git-visible paths match a recorded path/byte/SHA-256
  snapshot;
- no path is a reparse point, has a non-default NTFS stream, contains a NUL,
  violates strict UTF-8/LF/final-LF/trailing-whitespace rules, or has an
  unauthorized mode; and
- local Markdown links resolve.

If any identity, count, file-safety property, inventory, mode, ref, or Git state
differs, stop. Do not repair, restore, clean, normalize, stage, or infer a new
baseline.

---

## 5. Package boundary and dependency graph

Create exactly one package at `packages/web-application`.

Its `composer.json` must be a PHP library with this exact semantic content:

```json
{
  "name": "kongono/web-application",
  "description": "Explicit web application composition and Kernel operation ownership for Kongono.",
  "type": "library",
  "require": {
    "kongono/api-metadata": "*",
    "kongono/diagnostics": "*",
    "kongono/http-server": "*",
    "kongono/kernel": "*",
    "kongono/routing": "*",
    "php": "^8.5",
    "psr/http-message": "^2.0",
    "psr/http-server-handler": "^1.0",
    "psr/http-server-middleware": "^1.0"
  },
  "autoload": {
    "psr-4": {
      "Kongono\\Web\\Application\\": "src/"
    }
  },
  "autoload-dev": {
    "psr-4": {
      "Kongono\\Web\\Application\\Tests\\": "tests/"
    }
  }
}
```

The dependency graph is exact:

```text
web-application -> api-metadata
web-application -> diagnostics
web-application -> http-server
web-application -> kernel
web-application -> routing
web-application -> psr/http-message
web-application -> psr/http-server-handler
web-application -> psr/http-server-middleware
```

No existing package may depend on Web Application. Web Application must not
depend on HTTP Message concrete implementations, Application Graph, Dependency
Injection, Diagnostic Activation, Diagnostic Presentation, Diagnostic Handler,
PSR-17, a container, configuration package, adapter, runtime, emitter, server,
logger, telemetry package, or another framework.

The package is a leaf composition boundary consumed by the monorepo root. It
uses explicit constructor/static-factory inputs rather than service lookup,
discovery, tags, priorities, reflection, autowiring, Graph traversal, or global
registration.

---

## 6. Exact production inventory and API status

Create exactly these six production declarations:

| Path | Declaration | Status |
|---|---|---|
| `src/Diagnostic/WebApplicationDiagnostic.php` | `Kongono\Web\Application\Diagnostic\WebApplicationDiagnostic` | Internal |
| `src/Exception/ExceptionInterface.php` | `Kongono\Web\Application\Exception\ExceptionInterface` | Public |
| `src/Exception/WebApplicationException.php` | `Kongono\Web\Application\Exception\WebApplicationException` | Internal |
| `src/Exception/OperationCleanupException.php` | `Kongono\Web\Application\Exception\OperationCleanupException` | Experimental |
| `src/Exception/RetiredWebApplicationException.php` | `Kongono\Web\Application\Exception\RetiredWebApplicationException` | Experimental |
| `src/WebApplication.php` | `Kongono\Web\Application\WebApplication` | Experimental |

Every non-bootstrap declaration must carry exactly one
`#[Api(ApiStatus::...)]` attribute matching the table. Do not add another
declaration, alias, trait, interface, enum, helper class, compatibility shim, or
overlapping namespace owner.

---

## 7. Diagnostic and exception foundation

### 7.1 Exact diagnostic catalog

`WebApplicationDiagnostic` is an Internal string-backed enum with exactly these
cases and values:

| Case | ID | Title | Remediation | Operation | Violated invariant |
|---|---|---|---|---|---|
| `ApplicationRetired` | `KGN-WEB-APPLICATION-1001` | `Web application is retired` | `Replace the retired application instance before accepting more work` | `Use web application lifecycle` | `A retired application must not accept handling or shutdown work` |
| `OperationAndCleanupFailed` | `KGN-WEB-APPLICATION-1002` | `Application work and operation cleanup both failed` | `Retire the application instance and inspect both failure objects` | `Complete web application operation` | `Primary and cleanup failures must both remain observable` |

The enum exposes exactly these public instance methods:

```php
public function context(): DiagnosticContext;

/** @return list<DiagnosticFieldSchema> */
public function contextFields(): array;

public function contextOperation(): string;

public function contextViolatedInvariant(): string;

public function title(): string;

public function remediation(): string;
```

`contextFields()` returns `[]` for both cases. `context()` constructs one
`DiagnosticContext` from the exact fixed operation, invariant, and empty schema.
It accepts no caller value or arbitrary context map.

The IDs must be unique repository-wide and match
`^KGN-WEB-APPLICATION-1\d{3}$`.

### 7.2 Public marker

`ExceptionInterface` is Public, extends native `Throwable`, and declares exactly:

```php
public function diagnosticContext(): DiagnosticContext;
public function diagnosticId(): string;
public function diagnosticTitle(): string;
public function diagnosticRemediation(): string;
```

It is distinct from every existing package marker. Do not create a universal
framework exception marker.

### 7.3 Internal base

`WebApplicationException` is an Internal abstract native `RuntimeException`
implementing only the package marker. It owns exactly two private readonly
properties:

```text
WebApplicationDiagnostic $diagnostic
DiagnosticContext $diagnosticContext
```

Its protected constructor accepts one exact diagnostic and an optional previous
`Throwable`, eagerly creates the context, and calls the native parent with the
diagnostic title, integer code `0`, and exact previous object. The four marker
accessors are final and return the stored/fixed values. It has no custom
`__toString()`, serialization, logging, rendering, transport, retry, or recovery
behavior.

### 7.4 Retired failure

`RetiredWebApplicationException` is final and Experimental. Its public
zero-argument constructor selects only
`WebApplicationDiagnostic::ApplicationRetired` and no previous failure.

It is raised only when `handle()` or `shutdown()` is called after the owning
`WebApplication` has entered the retired state. It contains no prior failure,
request, response, route, handler, scope, or caller text.

### 7.5 Simultaneous primary and cleanup failure

`OperationCleanupException` is final and Experimental. It owns exactly two
private readonly properties:

```text
Throwable $primaryFailure
Throwable $cleanupFailure
```

Its exact declared public method inventory is:

```php
public function __construct(Throwable $primaryFailure, Throwable $cleanupFailure);
public function primaryFailure(): Throwable;
public function cleanupFailure(): Throwable;
```

Construction selects only
`WebApplicationDiagnostic::OperationAndCleanupFailed`, passes the exact primary
failure as the native previous exception, and preserves both supplied objects
by identity. Therefore:

```text
$exception->primaryFailure() === $primaryFailure
$exception->cleanupFailure() === $cleanupFailure
$exception->getPrevious() === $primaryFailure
```

The package-owned message, ID, title, remediation, operation, invariant, and
context contain neither failure message, class name, trace, request data, nor
other caller material. The explicit Throwable accessors are an internal failure-
coordination surface for a later outer boundary; they are never safe production
output. Native trace arguments and native exception stringification are not a
production presentation contract. Do not implement redaction or custom
stringification.

---

## 8. `WebApplication` exact contract

### 8.1 Shape

`WebApplication` is final, Experimental, deliberately not readonly, and
implements exactly `RequestHandlerInterface`. It has no parent class.

It owns exactly these private instance properties:

```text
readonly KernelInterface $kernel
readonly MiddlewarePipeline $pipeline
bool $retired = false
```

Its constructor is private and accepts the already-booted Kernel and already-
validated pipeline. Cloning is prohibited by one private `__clone()` method so
two application objects cannot claim independent ownership of the same Kernel
lifecycle. Define no destructor, serialization API, global registry, singleton,
or factory class.

Its exact public method inventory is:

```php
/**
 * @param array<array-key, mixed> $routeHandlers
 */
public static function boot(
    KernelInterface $kernel,
    RouteTable $routes,
    array $routeHandlers,
    RequestHandlerInterface $unmatchedHandler,
    MiddlewareInterface ...$middleware,
): self;

public function handle(ServerRequestInterface $request): ResponseInterface;

public function shutdown(): void;

public function isRetired(): bool;
```

Do not add getters for the Kernel, pipeline, routes, handlers, middleware, or
scope. Do not expose a resolver, container, Graph, arbitrary callable, closure
runner, service key, configuration bag, or ambient context.

### 8.2 Explicit boot and composition order

`boot()` performs these actions in this exact order:

1. construct `RoutingRequestHandler` from the exact routes, raw handler map, and
   unmatched handler;
2. construct `MiddlewarePipeline` from that routing handler and the exact
   declaration-ordered variadic middleware list;
3. call `$kernel->boot()` exactly once; and
4. only after boot succeeds, return one new `WebApplication` containing that
   exact Kernel and pipeline.

All Routing and HTTP Server validation therefore occurs before Kernel mutation.
An invalid route definition/table, route-handler key/value map, or middleware
count propagates its exact existing Throwable and must not call Kernel `boot()`.
Middleware and handlers are not invoked during composition.

A Kernel boot failure propagates unchanged by identity and no application is
returned. Do not catch boot merely to wrap, retry, shut down, or translate it.

The route map and middleware values are detached/validated by their existing
owning components. Do not duplicate their algorithms or invent another limit.

### 8.3 Handling and exact-once scope ownership

`handle()` first checks `retired`. If true, it throws a fresh
`RetiredWebApplicationException` before calling the Kernel, pipeline,
middleware, matcher, or terminal handler.

When not retired:

1. call `$kernel->beginOperation()` exactly once;
2. retain the returned exact `OperationScope` only in the local call frame;
3. call the compiled pipeline exactly once with the exact request;
4. call `$kernel->endOperation($scope)` exactly once for that scope whether the
   pipeline returns or throws; and
5. retain no request, response, match, scope, or failure after the call exits.

Do not put the scope on the request, in a container, static property, registry,
thread/fiber local, middleware attribute, or application property.

### 8.4 Begin-operation failure

If `beginOperation()` throws `LifecycleException`, propagate the exact object
unchanged and leave `retired` false because the Kernel contract guarantees a
deliberate invalid lifecycle action fails before mutation.

If `beginOperation()` throws any other `Throwable`, set `retired` to true and
propagate that exact object unchanged. No scope token exists that the application
can safely close, so later work must fail closed.

Neither begin-failure path may call the pipeline or `endOperation()`.

### 8.5 Application success or failure with successful cleanup

If the pipeline returns a `ResponseInterface` and `endOperation()` succeeds,
return that exact response object unchanged.

If the pipeline throws any `Throwable` and `endOperation()` succeeds, rethrow
that exact primary object unchanged. Do not wrap, map, log, retry, render, or
change its previous chain. Leave `retired` false.

PHP return-type failures and native engine failures from the handler path count
as primary Throwables for cleanup purposes; they are not converted to deliberate
application outcomes.

### 8.6 Cleanup failure

If application work succeeded but `endOperation()` throws, set `retired` to
true and throw the exact cleanup Throwable unchanged. Do not return the response.

If application work threw and `endOperation()` also throws, set `retired` to
true and throw one new `OperationCleanupException` containing both exact
objects as specified in Section 7.5.

Every cleanup failure retires the application, including a
`LifecycleException`, because the scope was not successfully completed by this
boundary. The application retains only the boolean retired state, never either
failure object.

The cleanup call is never retried. Do not call `endOperation()` a second time,
attempt shutdown, discard either failure, or continue serving.

### 8.7 Explicit shutdown

`shutdown()` first checks `retired`. If true, throw a fresh
`RetiredWebApplicationException` before calling the Kernel.

Otherwise call `$kernel->shutdown()` exactly once:

- a deliberate `LifecycleException` propagates unchanged and leaves the
  application non-retired because standard Kernel lifecycle rejection preserves
  state; and
- any other `Throwable` marks the application retired and propagates unchanged
  because shutdown state is uncertain.

Successful shutdown returns `void`. Kernel remains the sole owner of booted,
active-operation, and terminal shutdown semantics. The application adds no
second lifecycle enum or shutdown flag. There is no destructor or automatic
shutdown.

### 8.8 Retirement and persistent-runtime behavior

`isRetired()` returns only the local boolean and performs no call or I/O.

Retirement is irreversible for an application instance. It does not terminate a
process, emit a response, log, recover, reset the Kernel, or prove an incident was
recorded. It guarantees only that this application object refuses later
`handle()` and `shutdown()` work before consulting dependencies. A Phase 2G
outer adapter must stop assigning deliveries to a retired application; the
exact worker/process retirement mechanism remains adapter-owned.

Long-lived application instances retain only the Kernel, compiled pipeline, and
retired boolean. Consumer middleware and handlers may be stateful and remain
responsible for their own concurrency safety. The framework adds no synchronization
primitive, service scope, request cache, or state reset hook.

---

## 9. Required tests

Create exactly these three test files and no fixture files:

```text
packages/web-application/tests/Unit/Diagnostic/WebApplicationDiagnosticTest.php
packages/web-application/tests/Unit/Exception/WebApplicationExceptionTest.php
packages/web-application/tests/Unit/WebApplicationTest.php
```

Tests may use PHPUnit mocks/stubs and anonymous test-local objects. Do not import
concrete `Kongono\Http\Message\*` classes or another package's test fixtures;
exercise production through PSR interfaces and the existing public Routing,
HTTP Server, and Kernel contracts.

### 9.1 Diagnostic tests

Prove:

- the exact two cases, names, IDs, titles, remediation, operations, and
  invariants;
- empty field schemas and empty context fields;
- exact method/property inventory and Internal API status;
- repository-wide ID format and uniqueness integration; and
- no caller value, environment lookup, global state, I/O, or custom
  stringification.

### 9.2 Exception tests

Prove:

- marker method shape, Public status, and separation from every existing package
  marker;
- Internal abstract native `RuntimeException` base shape and exact two stored
  metadata properties;
- final/Experimental concrete types and exact constructor/property/method
  inventories;
- retired failure exact diagnostic and no previous exception;
- simultaneous failure exact primary, cleanup, and native previous identities;
- both raw sentinel messages and class names are absent from every package-owned
  message/diagnostic/context surface;
- neither exception defines custom `__toString()`; and
- the operation exception retains no request, response, handler, middleware,
  route, scope, or arbitrary context map.

Do not assert that native stack traces or native exception stringification are
safe for untrusted output.

### 9.3 Application composition and lifecycle tests

At minimum prove all of these independently:

1. invalid route-handler key composition fails before Kernel boot;
2. invalid route-handler value composition fails before Kernel boot;
3. 129 middleware components fail before Kernel boot;
4. valid composition calls Kernel boot exactly once and invokes no handler or
   middleware during boot;
5. Kernel boot Throwable identity propagates and no application is returned;
6. declaration-ordered middleware surrounds exact selected-route handling and
   returns the exact response;
7. ordinary unmatched handling uses the explicit unmatched handler while still
   owning one Kernel scope;
8. successful handling calls begin once, pipeline once, end once with the exact
   returned scope, and returns the exact response;
9. a deliberate begin `LifecycleException` propagates unchanged, does not call
   pipeline/end, and does not retire;
10. a non-lifecycle begin Throwable propagates unchanged, does not call
    pipeline/end, and retires;
11. primary handler Throwable plus successful end rethrows the exact primary,
    ends once, and does not retire;
12. successful handler plus cleanup Throwable ends once, throws exact cleanup,
    returns no response, and retires;
13. simultaneous primary and cleanup failure ends once, throws the composite,
    preserves both identities, and retires;
14. every later handle on a retired application fails before begin or handler;
15. shutdown on a retired application fails before Kernel shutdown;
16. successful shutdown delegates exactly once;
17. deliberate shutdown `LifecycleException` propagates without retirement;
18. other shutdown Throwable propagates and retires;
19. at least two sequential successful calls receive distinct exact scope
    objects and distinct request/response values without retained operation data;
20. one controlled reentrant/overlapping call proves nested scopes close in
    inner-then-outer order without a global current scope;
21. reflection proves the exact class, property, constructor, clone, interface,
    and public method shape; and
22. failed cleanup is never retried and automatic shutdown/destruction never
    occurs.

Tests must be deterministic, listener-free, clock-free, random-free, and perform
no network, filesystem, environment, process, logger, telemetry, or SAPI access.

### 9.4 Root architecture enforcement

Update `tests/Architecture/ApiStatusConventionTest.php` to enforce:

- all six new declarations and exact statuses;
- the ninth distinct package exception marker and concrete membership;
- exactly 111 repository diagnostic IDs, including exactly two
  `KGN-WEB-APPLICATION-1xxx` IDs;
- exact diagnostic/exception class shapes;
- exact `WebApplication` shape and PSR-15 implementation;
- exact Web Application manifest requirements and absence of forbidden edges;
- exactly six Web Application production PHP files;
- exactly 12 package source directories and 12 package manifests;
- no existing package manifest depends on `kongono/web-application`;
- no forbidden production import, namespace overlap, duplicate declaration,
  compatibility shim, or reverse dependency; and
- root tooling includes the new package source/tests exactly once.

Retain and extend every existing architecture assertion. Do not replace exact
inventory enforcement with a looser discovery-only check.

---

## 10. Composer, autoload, lockfile, and tooling expansion

### 10.1 Root Composer

Update root `composer.json` in deterministic package order:

- require `kongono/web-application: @dev`;
- map `Kongono\Web\Application\Tests\` to
  `packages/web-application/tests/` under `autoload-dev`; and
- validate `packages/web-application` last in `validate:packages`, after
  Routing.

Do not change minimum stability, preferred stability, platform PHP, plugin
authorization, advisory policy, existing requirements, script meanings, or
third-party constraints.

### 10.2 Tooling

Add Web Application source/tests exactly once and last among package paths in:

```text
.php-cs-fixer.dist.php
phpstan.neon.dist
phpunit.xml.dist
```

Add its source to PHPUnit's source include and its tests to the suite. Preserve
all strict failure flags and all existing paths.

### 10.3 Generated dependency state

After manifests and root integration are correct, run exactly:

```powershell
composer update kongono/web-application --with-dependencies --no-interaction
```

If network/cache access alone is blocked by the managed environment, retry the
identical command with approved access. Do not edit `composer.lock` manually.

The accepted lock delta is limited to:

- root content-hash regeneration;
- one new `kongono/web-application` path-package entry at `dev-main` with the
  exact manifest requirements; and
- lock metadata mechanically required for that entry.

Afterward the lock must contain exactly 16 production packages and 66 development
packages. All 15 preexisting production package entries, all 66 development
entries, all versions, requirements, references, source/dist data, and third-
party packages must remain unchanged. If Composer changes any existing local
reference or any third-party package, stop and report the exact delta.

No dependency is added beyond the new local package; all of its third-party and
first-party dependencies are already locked.

---

## 11. Documentation and ADR requirements

### 11.1 New Accepted ADR

Create:

```text
docs/adr/0048-web-application-bootstrap-and-kernel-operation-ownership.md
```

Its exact metadata is:

```text
Status: Accepted
Date: 2026-08-20
```

It must record:

- the twelfth package, namespace, and exact dependency graph;
- why explicit static boot/composition is selected without DI, Graph, discovery,
  or another framework;
- validation-before-boot order;
- the exact `WebApplication` API and absence of exposed Kernel/pipeline getters;
- one local Kernel scope per handle call and exact-once closure;
- primary-only, cleanup-only, and simultaneous-failure behavior;
- the two package diagnostics and separate exception marker;
- fail-closed irreversible retirement and its limited meaning;
- deliberate lifecycle rejection versus uncertain unknown lifecycle failure;
- explicit shutdown and no destructor/automatic cleanup;
- sequential/reentrant persistent-runtime behavior and no ambient state;
- why Phase 2F owns application-scope cleanup while Phase 2G owns adapter/
  transport cleanup and delivery;
- no generic production failure mapping or trusted presentation integration;
- performance proportional only to existing bounded route/middleware behavior
  plus constant lifecycle calls;
- security and disclosure consequences;
- exact rejected alternatives; and
- Phase 2G as next, Phase 2H as first runnable milestone.

Rejected alternatives must include at least:

- putting PSR or web behavior in Kernel;
- using Symfony, Laravel, a third-party dispatcher, or runtime server as the
  foundation;
- service location, autowiring, DI resolver, Graph traversal, tags, priority, or
  ambient discovery;
- booting before composition validation;
- depending on process teardown or a destructor for scope cleanup;
- a global/static/current request or operation scope;
- swallowing/replacing primary or cleanup failure;
- retrying failed cleanup;
- continuing to serve after uncertain cleanup;
- exposing raw failure text through package diagnostics;
- selecting an adapter, emitter, error middleware, HTTP error response, or
  trusted diagnostic path in Phase 2F; and
- adding scoped services, cancellation, resource cleanup, logging, or telemetry.

### 11.2 New architecture model

Create:

```text
docs/architecture/web-application-model.md
```

Document the exact implemented package, dependency graph, boot sequence,
handling sequence, failure matrix, retirement state, shutdown rules, object
retention, persistent-runtime behavior, performance, security boundary,
current-versus-planned behavior, and related links.

Include this failure matrix exactly in meaning:

| Work result | Scope end result | Observable result | Retired |
|---|---|---|---|
| response | success | exact response | no |
| primary Throwable | success | exact primary Throwable | no |
| response | cleanup Throwable | exact cleanup Throwable | yes |
| primary Throwable | cleanup Throwable | `OperationCleanupException` with both identities | yes |

Also document begin and shutdown failure rules separately.

### 11.3 Existing documentation reconciliation

Modify only these existing documentation files:

```text
ARCHITECTURE.md
README.md
SECURITY.md
docs/doctrine.md
docs/glossary.md
docs/adr/README.md
docs/adr/0043-phase-2-runnable-web-framework-milestone.md
docs/adr/0044-http-message-and-interoperability-architecture.md
docs/adr/0047-explicit-psr-15-middleware-and-terminal-routing.md
docs/architecture/overview.md
docs/architecture/runtime-model.md
docs/architecture/phase-2-roadmap.md
docs/architecture/http-message-model.md
docs/architecture/routing-model.md
docs/architecture/http-server-model.md
docs/architecture/microkernel.md
docs/engineering/package-layout.md
docs/engineering/api-status.md
docs/engineering/error-handling.md
packages/kernel/README.md
packages/http-server/README.md
```

Reconcile them so they state accurately:

- Phase 2F and ADR 0048 are implemented and Phase 2G is next;
- exactly 12 packages exist and the dependency graph remains acyclic;
- Web Application owns explicit composition and application-call Kernel scope
  cleanup while Kernel and HTTP Server remain unchanged lower layers;
- broader service/resource/transport cleanup remains open;
- no DI, Graph, Activation, Presentation, Handler, concrete HTTP Message, or
  adapter dependency was introduced;
- two Web Application diagnostics and one separate package marker now exist;
- application retirement is fail-closed refusal, not process termination,
  recovery, response mapping, logging, or incident recording;
- no request-specific state is retained;
- the contradiction identified in Section 2.3 is resolved explicitly;
- no outer adapter, inbound conversion, evidence evaluator, emitter, generic
  production failure response, trusted diagnostic delivery, fixture, or
  runnable app exists; and
- Phase 2H remains the first runnable black-box milestone.

Add ADR/model/package links where relevant. Preserve historical statements when
clearly labeled as past evidence; do not rewrite historical directives/reports.

Search current, nonhistorical documentation for stale unqualified claims such
as `Phase 2F is next`, `exactly eleven packages`, `no web composition`, and
`cleanup orchestration remains wholly unimplemented`. Reconcile only where
Phase 2F materially changes the current state. Do not claim Kernel itself now
owns cleanup orchestration or that all resource-cleanup decisions are resolved.

---

## 12. Exact file boundary

### 12.1 Create exactly these 13 implementation/documentation paths

```text
packages/web-application/composer.json
packages/web-application/README.md
packages/web-application/src/Diagnostic/WebApplicationDiagnostic.php
packages/web-application/src/Exception/ExceptionInterface.php
packages/web-application/src/Exception/WebApplicationException.php
packages/web-application/src/Exception/OperationCleanupException.php
packages/web-application/src/Exception/RetiredWebApplicationException.php
packages/web-application/src/WebApplication.php
packages/web-application/tests/Unit/Diagnostic/WebApplicationDiagnosticTest.php
packages/web-application/tests/Unit/Exception/WebApplicationExceptionTest.php
packages/web-application/tests/Unit/WebApplicationTest.php
docs/adr/0048-web-application-bootstrap-and-kernel-operation-ownership.md
docs/architecture/web-application-model.md
```

### 12.2 Modify exactly these 27 existing paths

```text
.php-cs-fixer.dist.php
ARCHITECTURE.md
README.md
SECURITY.md
composer.json
composer.lock
docs/adr/README.md
docs/adr/0043-phase-2-runnable-web-framework-milestone.md
docs/adr/0044-http-message-and-interoperability-architecture.md
docs/adr/0047-explicit-psr-15-middleware-and-terminal-routing.md
docs/architecture/http-message-model.md
docs/architecture/http-server-model.md
docs/architecture/microkernel.md
docs/architecture/overview.md
docs/architecture/phase-2-roadmap.md
docs/architecture/routing-model.md
docs/architecture/runtime-model.md
docs/doctrine.md
docs/engineering/api-status.md
docs/engineering/error-handling.md
docs/engineering/package-layout.md
docs/glossary.md
packages/http-server/README.md
packages/kernel/README.md
phpstan.neon.dist
phpunit.xml.dist
tests/Architecture/ApiStatusConventionTest.php
```

After every non-report gate passes, populate only:

```text
docs/task reports/Phase_2F.md
```

No other path may be created or modified. Do not change existing Kernel, HTTP
Message, Routing, HTTP Server, DI, Graph, Diagnostics, Activation, Presentation,
or Handler production/test PHP files.

---

## 13. Prohibited scope

Do not implement or alter:

- SAPI, PHP-FPM, FrankenPHP, RoadRunner, OpenSwoole, serverless, socket, listener,
  worker, or other runtime adapters;
- request creation/conversion, superglobal access, proxy trust, parsing,
  response emission, output, headers, body transfer, connection behavior, or I/O;
- generic production HTTP failure status/header/body behavior;
- trusted diagnostic evidence evaluation, eligibility construction, projection,
  rendering, or delivery;
- a supplied error middleware, incident reference/store, logging, telemetry,
  metrics, tracing, retry, fallback, recovery, or recursive error handling;
- DI/container definitions, resolver use, autowiring, service location, service
  lifetimes, scoped services, reset hooks, disposal protocols, or Application
  Graph nodes/relations;
- configuration files, environment readers, feature flags, debug switches,
  secrets, operator/listener identity, authentication, or authorization;
- changes to route grammar, matching, handler maps, middleware semantics,
  messages, factories, diagnostics, public methods, or API status of existing
  packages;
- request, response, permission, principal, tenant, route match, exception, or
  operation data on `OperationScope`;
- cancellation, timeouts, signals, async/fiber APIs, locks, concurrency
  primitives, process termination, restart, or worker retirement mechanism;
- body-stream, uploaded-file, middleware-owned, handler-owned, transport, or
  external-resource cleanup;
- controllers, sessions, cookies, CSRF, templates, frontend, ORM, database,
  migrations, events, queues, cache, storage, mail, agents, extensions, CLI, or
  other later framework capabilities;
- versioning, releases, publishing, package splitting, semantic compatibility
  promotion, production readiness, or framework 1.0; or
- Git staging, commits, refs, branches, tags, remotes, history, or caller records
  beyond final report population.

No adjacent cleanup, spelling normalization, reformatting, generated refresh, or
warning suppression is authorized. Stop rather than guess.

---

## 14. Required validation sequence

### 14.1 Before mutation

After authentication and complete baseline proof, run:

```powershell
composer quality
```

Accepted Phase 2E.1 evidence is:

```text
security advisories: zero
platform requirements: pass
PHP CS Fixer: 0 of 196 files fixable
PHPStan: zero errors
PHPUnit: 411 tests, 7298 assertions
```

If advisory access alone is blocked, retry the identical command with approved
network/cache access. Do not skip or weaken `audit:locked`.

### 14.2 Focused implementation validation

After implementation and the generated lock update, run:

```powershell
php -l packages/web-application/src/Diagnostic/WebApplicationDiagnostic.php
php -l packages/web-application/src/Exception/ExceptionInterface.php
php -l packages/web-application/src/Exception/WebApplicationException.php
php -l packages/web-application/src/Exception/OperationCleanupException.php
php -l packages/web-application/src/Exception/RetiredWebApplicationException.php
php -l packages/web-application/src/WebApplication.php
php -l packages/web-application/tests/Unit/Diagnostic/WebApplicationDiagnosticTest.php
php -l packages/web-application/tests/Unit/Exception/WebApplicationExceptionTest.php
php -l packages/web-application/tests/Unit/WebApplicationTest.php
composer dump-autoload --optimize --strict-psr
php vendor/bin/phpunit --configuration phpunit.xml.dist --display-phpunit-notices packages/web-application/tests
php vendor/bin/phpunit --configuration phpunit.xml.dist tests/Architecture/ApiStatusConventionTest.php
php vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --no-progress --memory-limit=512M packages/web-application/src packages/web-application/tests tests/Architecture/ApiStatusConventionTest.php
composer cs
composer analyse -- --memory-limit=512M
composer quality
```

No test failure, PHPUnit notice, risky/skipped/incomplete test, unexpected test
output, PHPStan error, style finding, security advisory, platform failure, PSR-4
warning, or unaccepted warning is allowed. Existing missing-license and
authorized local unbounded-development constraints remain accepted warning
categories; report every instance accurately.

Exact final test and assertion totals are observational evidence, not architect-
supplied targets. Do not add assertions merely to reach a predicted count.

### 14.3 Direct architecture and behavior probes

Also prove directly:

- exact created and modified path inventories;
- package manifest, namespace, dependency, API-status, exception-marker,
  diagnostic, class-shape, and tooling contracts;
- validation-before-boot and no handler invocation during boot;
- exact begin/pipeline/end call order for success and every failure matrix row;
- end is called exactly once and never retried;
- exact primary, cleanup, response, request, and scope identities;
- retirement before dependency access and no retained failure/request data;
- sequential and reentrant scope isolation;
- explicit shutdown semantics and no destructor/automatic shutdown;
- no use of `$GLOBALS`, superglobals, environment, clock, random source,
  filesystem, network, process APIs, static mutable state, service locator,
  resolver, Graph, concrete HTTP Message classes, diagnostics presentation, or
  transport;
- exactly 12 package directories/manifests, 92 production PHP files, 113 test
  PHP files, and 205 style-covered PHP files;
- exactly 111 unique diagnostic IDs with the exact domain split;
- exactly 16 locked production and 66 locked development packages;
- every existing lock entry remains exact and only the authorized new local
  package entry/content hash was added;
- local Markdown links resolve;
- strict UTF-8, LF-only, one final LF, no trailing whitespace, no NUL, ordinary
  files, no alternate streams/reparse points, and exact modes; and
- `git diff --check` passes.

### 14.4 Protected-state proof and final counts

Compare the pre-change 467-path snapshot with final Git-visible paths. Before
report population, only the 13 created paths and 27 modified paths may differ.

The two additional tracked modifications relative to the accepted Phase 2E.1
status are exactly:

```text
docs/architecture/microkernel.md
packages/kernel/README.md
```

All other tracked paths authorized here were already part of the accepted dirty
tracked set. The 13 implementation/documentation creations are untracked.

Before and after populating the already-existing untracked report, final status
must be exactly:

```text
tracked modifications: 20
untracked paths: 122
total status paths: 142
staged paths: 0
Git-visible paths: 480
```

The index bytes, `HEAD`, tree, parent, commit count, branch, refs, remotes, and
tags must remain unchanged.

---

## 15. Mandatory stop rules

Stop immediately, preserve current state, and report the exact mismatch if:

- independent directive authentication or any locked caller identity fails;
- the zero-byte report prerequisite fails;
- Git, inventory, hash, mode, stream, reparse-point, ordinary-file, or encoding
  baseline differs;
- any unauthorized path would need creation or modification;
- Composer changes an existing local reference or any third-party lock entry;
- satisfying a test requires changing existing Kernel, Routing, HTTP Server,
  HTTP Message, diagnostic, or API behavior;
- exact-once cleanup, failure identity, or retirement behavior is ambiguous;
- a production defect outside the authorized Web Application boundary is found;
- any non-report quality, security, documentation, link, or preservation gate
  fails; or
- final status, history, refs, index, modes, streams, counts, or protected bytes
  differ.

Do not stage, commit, reset, restore, clean, stash, amend, merge, rebase, fetch,
push, switch branches, create/delete refs, or normalize historical records.

Do not populate the report after a failed non-report gate.

---

## 16. Strict acceptance criteria

Phase 2F is complete only when all are true:

1. every authority was read completely;
2. independent directive bytes and SHA-256 matched;
3. the report began as the exact zero-byte caller placeholder;
4. all immediate and historical caller records remained exact;
5. Git identity, status, inventory, modes, refs, streams, file safety, and
   protected snapshot matched;
6. pre-change quality passed at 411 tests / 7298 assertions;
7. exactly the 13 authorized paths were created and 27 existing paths modified;
8. `kongono/web-application` is the sole new package and has the exact manifest,
   namespace, and dependency graph;
9. exactly six production declarations exist with exact API statuses;
10. the two exact diagnostics and empty schemas are implemented;
11. the Public marker is distinct and both concrete failures have exact native,
    property, constructor, and accessor contracts;
12. simultaneous failure preserves primary, cleanup, and previous identities;
13. package-owned diagnostic surfaces contain no raw failure or request data;
14. `WebApplication` has the exact final shape, private constructor/clone,
    properties, interface, and four public methods;
15. composition validates Routing and HTTP Server inputs before Kernel boot;
16. valid boot calls Kernel boot once and performs no application work;
17. each accepted handle call begins one scope and ends that exact scope once;
18. success returns the exact response;
19. primary failure with successful cleanup propagates exact primary unchanged;
20. cleanup-only failure propagates exact cleanup and retires;
21. simultaneous failure throws the composite and retires;
22. known begin/shutdown lifecycle rejection remains non-retiring while unknown
    lifecycle failure retires as specified;
23. retired handling/shutdown fails before dependency access;
24. sequential and reentrant operations prove no ambient or retained operation
    data;
25. shutdown is explicit, no destructor exists, and cleanup is never retried;
26. no adapter, I/O, response emission, failure mapping, trusted presentation,
    DI, Graph, scoped service, configuration, or prohibited capability exists;
27. ADR 0048 is Accepted with the exact decision and rejected alternatives;
28. the Web Application model and every authorized current-state document are
    accurate and reconciled;
29. Composer adds only the local package and preserves every existing lock
    entry/dependency;
30. strict autoload, focused tests, architecture tests, focused/full PHPStan,
    style, audit, platform, and canonical quality pass;
31. final inventories are exactly 12/12/92/113/205 packages/manifests/
    production PHP/test PHP/style PHP;
32. diagnostic total is exactly 111 and lock totals are exactly 16/66;
33. links, encoding, modes, streams, reparse points, NUL, whitespace, and
    `git diff --check` pass;
34. Git history/index/refs remain unchanged and zero paths are staged;
35. final status is exactly 20 modified, 122 untracked, 142 total, 0 staged;
36. only after criteria 1-35 pass, the report is populated;
37. the report has the exact structure below and states no unsupported claim;
38. the populated report's byte count and SHA-256 are returned outside the
    report body.

---

## 17. Standard executor completion report

Populate `docs/task reports/Phase_2F.md` with exactly these top-level headings,
in this order:

```markdown
# PHASE 2F TASK COMPLETION REPORT

## 1. STATUS

## 2. SUMMARY

## 3. BASELINE VERIFIED

## 4. CALLER RECORDS

## 5. FILES CREATED

## 6. FILES MODIFIED

## 7. PACKAGE BOUNDARY

## 8. PRODUCTION IMPLEMENTATION

## 9. BOOT AND COMPOSITION BEHAVIOR

## 10. OPERATION OWNERSHIP AND CLEANUP

## 11. FAILURE AND RETIREMENT BEHAVIOR

## 12. TEST COVERAGE

## 13. ARCHITECTURAL DECISIONS

## 14. DEVIATIONS FROM DIRECTIVE

## 15. DEPENDENCIES ADDED/REMOVED

## 16. ENVIRONMENT

## 17. COMMANDS RUN

## 18. VALIDATION RESULTS

## 19. DOCUMENTATION REVIEW

## 20. SECURITY AND FILE-SAFETY REVIEW

## 21. WARNINGS / KNOWN ISSUES

## 22. QUESTIONS / BLOCKERS

## 23. GIT DIFF SUMMARY

## 24. ACCEPTANCE CRITERIA

## 25. NEXT STEP
```

The report must state:

- the independently supplied Phase 2F directive identity;
- every locked caller identity and baseline/final count;
- every created and modified path;
- exact package manifest, dependency, source, API, diagnostic, and exception
  contracts;
- exact boot order and operation/failure/retirement matrix;
- exact tests, commands, lock delta, and final test/assertion totals;
- unchanged existing production APIs, packages, lock entries, Git history,
  index, and refs;
- every warning, approved-access retry, and environmental limitation;
- whether every acceptance criterion passed; and
- `Await architect review before Phase 2G.` as the exact final sentence.

After writing and validating the report, calculate its byte count and SHA-256
and return those values to the caller outside the report body.

---

## 18. Required final outcome

At completion, Kongono has one explicit transport-free Web Application
composition boundary that boots only after valid composition, owns exact Kernel
scope cleanup for every in-memory application call, preserves all relevant
failure identities, and refuses new work after uncertain lifecycle failure.

Kongono still has no outer web adapter, request conversion, response emission,
generic production failure response, trusted diagnostic delivery, or runnable
fixture application. Phase 2G is next; Phase 2H remains the first runnable
black-box milestone.
