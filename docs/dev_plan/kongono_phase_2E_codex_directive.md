# CODEX IMPLEMENTATION DIRECTIVE - KONGONO PHASE 2E

## Explicit PSR-15 Middleware and Terminal Routing

**Directive type:** architecture decision, bounded implementation, tests, and documentation
**Repository:** `C:\Users\antho\Dev\kongono`
**Date:** 2026-08-16
**Executor role:** executor, not architect

---

## 1. Mission

Establish and implement Kongono's first explicit PSR-15 middleware and terminal
request-handler boundary as the eleventh first-party package:

```text
directory              packages/http-server
Composer package       kongono/http-server
production namespace   Kongono\Http\Server\
test namespace         Kongono\Http\Server\Tests\
```

Phase 2E must provide exactly two orchestration behaviors:

1. an immutable, bounded, declaration-ordered PSR-15 middleware pipeline around
   one explicitly supplied terminal `RequestHandlerInterface`; and
2. a transport-free PSR-15 request handler that performs Phase 2D route
   matching, maps the selected bounded route name to one explicitly supplied
   request handler, attaches the fresh `RouteMatch` to a derived PSR-7 request,
   and delegates ordinary unmatched requests to an explicit unmatched handler.

This phase adopts the two PSR-15 interface packages already accepted by ADR
0044. It does not adopt a third-party middleware implementation or framework.

Phase 2E does not create a composition root, start a Kernel operation, read
runtime input, emit a response, catch application failures, select production
error output, or make Kongono runnable. Those remain Phase 2F through 2H work.

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
8. `docs/adr/0013-documentation-doctrine.md`
9. `docs/adr/0014-php-engineering-toolchain.md`
10. `docs/adr/0016-strict-types-and-per-cs-style.md`
11. `docs/adr/0026-actionable-diagnostics-and-error-boundaries.md`
12. `docs/adr/0028-bounded-diagnostic-context-and-disclosure.md`
13. `docs/adr/0031-neutral-api-metadata-ownership-and-acyclic-package-dependencies.md`
14. `docs/adr/0043-phase-2-runnable-web-framework-milestone.md`
15. `docs/adr/0044-http-message-and-interoperability-architecture.md`
16. `docs/adr/0045-first-party-http-message-and-factory-implementation.md`
17. `docs/adr/0046-deterministic-route-declaration-and-matching.md`
18. `docs/architecture/http-message-model.md`
19. `docs/architecture/routing-model.md`
20. `docs/architecture/runtime-model.md`
21. `docs/architecture/phase-2-roadmap.md`
22. `docs/engineering/api-status.md`
23. `docs/engineering/error-handling.md`
24. `docs/engineering/package-layout.md`
25. `docs/dev_plan/kongono_phase_2D_codex_directive.md`
26. `docs/task reports/Phase_2D.md`
27. `docs/dev_plan/kongono_phase_2D_1_codex_directive_correction.md`
28. `docs/task reports/Phase_2D_1_correction.md`
29. `docs/dev_plan/kongono_phase_2E_codex_directive.md`

These filenames are exact. Do not substitute an ADR based only on its number.
Historical directives and reports are evidence, not permission to repeat their
mutations or reopen closed decisions.

### 2.2 Locked decisions

Preserve all accepted Kongono doctrine, including:

- strict PHP 8.5, PER-CS, max-level PHPStan, strict PHPUnit, locked audit, and
  empty Composer plugin authorization;
- monorepo ownership with exact package-local namespaces and declared edges;
- neutral API Metadata ownership and exact Public/Experimental/Internal status;
- actionable diagnostic IDs, fixed prose, bounded typed context, and no raw
  rejected input in package-owned diagnostic surfaces;
- transport-neutral Kernel and explicit lifecycle ownership;
- no ambient current request, response, match, handler, middleware cursor,
  principal, tenant, permission, exception, or operation state;
- PSR-7/PSR-17 as message interoperability contracts and PSR-15 as the accepted
  server handler/middleware interoperability contract;
- Kongono-owned HTTP Message and Routing implementations remaining separate;
- exact Phase 2D route grammar, encoded matching, specificity, conflict,
  canonical order, immutable match, diagnostics, and ordinary `null` unmatched
  behavior;
- a route match proving neither authentication nor authorization; and
- Phase 2H, not Phase 2E, remaining the first runnable black-box milestone.

Stop and ask rather than infer any decision outside this directive.

### 2.3 Official interoperability evidence

The architecture record must cite the official PHP-FIG PSR-15 specification:

```text
https://www.php-fig.org/psr/psr-15/
```

PSR-15 fixes only these signatures:

```php
RequestHandlerInterface::handle(ServerRequestInterface $request): ResponseInterface

MiddlewareInterface::process(
    ServerRequestInterface $request,
    RequestHandlerInterface $handler,
): ResponseInterface
```

The observed accepted package line on 2026-08-16 is `1.0.2` for both interface
packages. Constraints remain `^1.0`. The packages supply interfaces only; they
do not supply dispatch, routing, messages, an adapter, an emitter, or a server.

---

## 3. Caller authentication and immutable records

### 3.1 Independent directive identity

Before quality checks, edits, dependency operations, or any other mutation,
compare this directive with the architect-supplied byte count and SHA-256
delivered outside this file.

The local file is not self-authenticating. If either external value is absent
or differs, stop before broad validation or mutation and report the observed
identity.

### 3.2 Caller-managed report

The caller must provide both:

```text
docs/dev_plan/kongono_phase_2E_codex_directive.md
docs/task reports/Phase_2E.md
```

`Phase_2E.md` must initially be an ordinary, untracked, unstaged, zero-byte file
absent from `HEAD`, with SHA-256:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

Do not create, truncate, replace, stage, commit, or populate caller records.
The report may be populated only after every permitted non-report gate passes.

### 3.3 Immediate accepted records

These are immutable:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `docs/dev_plan/kongono_phase_2D_codex_directive.md` | 54316 | `7A0F803802704EA8E95B7DFA8E4A49809264493CC941686648621079EBEA58FF` |
| `docs/task reports/Phase_2D.md` | 26060 | `834582F8A0A18510F72F89F5800BA2CA9DDE460A5DAF3D780B4F81AAD0367EAD` |
| `docs/dev_plan/kongono_phase_2D_1_codex_directive_correction.md` | 23051 | `1C745CDC6F4F920EE67355FD2C2525A738631A7FB8A11ABB58587AC56DD03A92` |
| `docs/task reports/Phase_2D_1_correction.md` | 19893 | `9880FBB39CB2C7B6A659C51F468633A64D8DC82E5A11BAB7A2E97B7FFA8A9580` |

All earlier directive/report caller records are also immutable. Record their
pre-change identities and prove they remain exact; do not normalize even an
apparently redundant final line ending.

---

## 4. Exact starting state

After the caller provides the two Phase 2E records, verify:

```text
branch: main
HEAD: d7846f137a1d53f5306f48d19196b916fde44ac2
HEAD tree: bfa1908c619069b699bc9b08668db17ea2555eb3
HEAD parent: 8fe793b124384744ac43887037360154c149241a
commit count: 9
tracked files: 358
tracked modes other than 100644: 0
local branches: 1
remotes: 0
tags: 0
tracked modifications: 18
untracked paths: 89
total status paths: 107
staged paths: 0
package directories: 10
package manifests: 10
production PHP files: 80
test PHP files: 104
PHP files covered by PHP CS Fixer: 184
unique framework diagnostic IDs: 106
```

Use full untracked expansion equivalent to
`git status --porcelain=v1 -uall`. Codex-managed
`refs/codex/turn-diffs/**` are environmental and exempt from the no-unexpected-ref
count; enumerate them separately and do not alter them.

Critical starting artifacts are:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `composer.json` | 3685 | `E32CB2D057E83C01DE77E0A6ED9B6B6D42AFAEAC62DACAFD9108D52EF4367255` |
| `composer.lock` | 188589 | `B058A44C1AE4087203B85319273B5B5417975954E4FCA1A95B50291DB3AF24FA` |
| `.php-cs-fixer.dist.php` | 1821 | `E2A6F02EEE1C5F038194DEF853FCD5CA929638D3B986B9AA14088FA6D6F48A64` |
| `phpstan.neon.dist` | 998 | `4103E9E2556EA337AD0229A361E6F450CD3DD7D2F9E05A7307DFA053EC2351E3` |
| `phpunit.xml.dist` | 2072 | `EAA2344AFBCF6B7E6A50EF18376094FCC266BB94B4CE38820E35390E837D123A` |
| `tests/Architecture/ApiStatusConventionTest.php` | 50187 | `F8750CA0E82A98C2E6BBD2A0D7081D375F84533B287969056799AEB5BF041B01` |

The accepted cumulative tracked binary diff hash is:

```text
7c173dd726aa4e0de3822d9a7d9b39ef57f88dd3
```

Compute it from `git diff --binary | git hash-object --stdin` before mutation.

Verify every status path is an ordinary file, has no reparse point, and has no
non-default NTFS stream. If anything differs, stop without repair.

Before edits, run `composer quality`. The accepted baseline is:

```text
PHP CS Fixer: 0 of 184 files fixable
PHPStan: zero errors
PHPUnit: 387 tests, 6721 assertions
security advisories: zero
```

An identical approved-access retry is allowed only for managed network/cache
denial. The locked audit must never be skipped or weakened. If a PHPStan
parallel worker alone reaches the existing 128 MiB environmental limit, run
the existing full analysis once with `--memory-limit=512M`, then retry the
unchanged canonical gate; report every attempt.

---

## 5. Exact file boundary

### 5.1 Create exactly these 16 implementation/documentation paths

```text
packages/http-server/composer.json
packages/http-server/README.md
packages/http-server/src/Diagnostic/HttpServerDiagnostic.php
packages/http-server/src/Exception/ExceptionInterface.php
packages/http-server/src/Exception/InvalidHttpServerDefinitionException.php
packages/http-server/src/Internal/MiddlewareLayer.php
packages/http-server/src/MiddlewarePipeline.php
packages/http-server/src/RoutingRequestHandler.php
packages/http-server/tests/Fixture/CallbackMiddleware.php
packages/http-server/tests/Fixture/CallbackRequestHandler.php
packages/http-server/tests/Unit/Diagnostic/HttpServerDiagnosticTest.php
packages/http-server/tests/Unit/Exception/HttpServerExceptionTest.php
packages/http-server/tests/Unit/MiddlewarePipelineTest.php
packages/http-server/tests/Unit/RoutingRequestHandlerTest.php
docs/adr/0047-explicit-psr-15-middleware-and-terminal-routing.md
docs/architecture/http-server-model.md
```

Every named PHP declaration must be in its matching PSR-4 file. No eval,
generated declaration, multi-type fixture file, or autoload exception is
permitted.

### 5.2 Modify only these 24 existing paths

```text
.php-cs-fixer.dist.php
ARCHITECTURE.md
README.md
SECURITY.md
composer.json
composer.lock
docs/adr/0004-microkernel-capability-architecture.md
docs/adr/README.md
docs/adr/0043-phase-2-runnable-web-framework-milestone.md
docs/adr/0044-http-message-and-interoperability-architecture.md
docs/adr/0046-deterministic-route-declaration-and-matching.md
docs/architecture/http-message-model.md
docs/architecture/overview.md
docs/architecture/phase-2-roadmap.md
docs/architecture/routing-model.md
docs/architecture/runtime-model.md
docs/doctrine.md
docs/engineering/api-status.md
docs/engineering/error-handling.md
docs/engineering/package-layout.md
docs/glossary.md
phpstan.neon.dist
phpunit.xml.dist
tests/Architecture/ApiStatusConventionTest.php
```

After every non-report gate passes, populate only:

```text
docs/task reports/Phase_2E.md
```

No other file may change. Create no extra fixture, convenience class, interface,
adapter, exception, diagnostic, example application, config file, script,
baseline, suppression, compatibility layer, or generated artifact.

---

## 6. Package and dependency contract

`packages/http-server/composer.json` is exact in meaning:

```json
{
  "name": "kongono/http-server",
  "description": "Explicit PSR-15 middleware and terminal routing for Kongono.",
  "type": "library",
  "require": {
    "kongono/api-metadata": "*",
    "kongono/diagnostics": "*",
    "kongono/routing": "*",
    "php": "^8.5",
    "psr/http-message": "^2.0",
    "psr/http-server-handler": "^1.0",
    "psr/http-server-middleware": "^1.0"
  },
  "autoload": {
    "psr-4": {
      "Kongono\\Http\\Server\\": "src/"
    }
  },
  "autoload-dev": {
    "psr-4": {
      "Kongono\\Http\\Server\\Tests\\": "tests/"
    }
  }
}
```

Root development composition adds exactly:

```text
require: kongono/http-server: @dev
autoload-dev: Kongono\Http\Server\Tests\ -> packages/http-server/tests/
validate:packages: packages/http-server
```

Add the new source/test paths to PHP CS Fixer and PHPStan, and the new test and
source paths to PHPUnit. Preserve every existing path and policy.

Use exactly:

```powershell
composer update kongono/http-server --with-dependencies --no-interaction
```

The authorized dependency result is:

- one new local path package, `kongono/http-server`;
- `psr/http-server-handler` at the accepted `^1.0` line;
- `psr/http-server-middleware` at the accepted `^1.0` line;
- the mechanically required root lock content-hash/reference data.

No existing third-party package may update, downgrade, or disappear. No
existing local package manifest, requirements, version, or dependency metadata
may change. If Composer regenerates an existing local path-package reference
without a source-manifest change, stop and report the exact reference delta
rather than silently broadening this contract.

No plugin, framework, router, PSR-7 implementation, middleware dispatcher,
container integration, or other dependency is authorized.

---

## 7. Exact production API inventory

Create exactly six production declarations:

| Declaration | Status |
|---|---|
| `Kongono\Http\Server\Diagnostic\HttpServerDiagnostic` | Internal |
| `Kongono\Http\Server\Exception\ExceptionInterface` | Public |
| `Kongono\Http\Server\Exception\InvalidHttpServerDefinitionException` | Experimental |
| `Kongono\Http\Server\Internal\MiddlewareLayer` | Internal |
| `Kongono\Http\Server\MiddlewarePipeline` | Experimental |
| `Kongono\Http\Server\RoutingRequestHandler` | Experimental |

Every production file must declare strict types, use its exact namespace, and
carry exactly one `#[Api(...)]` attribute. No Stable API is created.

PHP-FIG interfaces keep their external status and receive no Kongono API
metadata.

---

## 8. Middleware pipeline contract

### 8.1 Public shape

`MiddlewarePipeline` is `final readonly` and implements
`Psr\Http\Server\RequestHandlerInterface`.

Its public constant is exactly:

```php
public const int MAX_MIDDLEWARE = 128;
```

Its constructor and methods are exactly:

```php
public function __construct(
    RequestHandlerInterface $terminalHandler,
    MiddlewareInterface ...$middleware,
);

/** @return list<MiddlewareInterface> */
public function middleware(): array;

public function terminalHandler(): RequestHandlerInterface;

public function handle(ServerRequestInterface $request): ResponseInterface;
```

It has exactly three private instance properties:

```text
middleware
terminalHandler
entryHandler
```

`middleware` is the detached declaration-order list. `terminalHandler` is the
exact caller object. `entryHandler` is the immutable compiled outer entry.

### 8.2 Construction and bound

Zero middleware is valid and delegates directly to the terminal handler.
One through 128 entries are valid. A 129th entry throws the exact package
definition exception with `TooManyMiddleware` before building a layer.

Detach variadic entries into a fresh list by value before storage. A caller
array containing referenced elements may later replace those references without
altering the pipeline list. Returned arrays cannot mutate stored order.

Build the chain once during construction, from the final middleware backward.
Do not allocate or mutate a cursor, queue, stack, iterator, or layer chain per
request.

### 8.3 Execution

For declared middleware `[A, B, C]`, one ordinary delegating call is exactly:

```text
A enter
  B enter
    C enter
      terminal
    C leave
  B leave
A leave
```

Each layer passes its exact immutable next handler to
`MiddlewareInterface::process()`. A middleware may:

- delegate the same request;
- delegate a derived request;
- return the downstream response;
- return a derived or independent response;
- short-circuit without delegation; or
- deliberately invoke the next handler more than once.

The pipeline neither requires nor pretends to prove exactly-once delegation.
It adds no per-call mutable state to count or police consumer behavior.

Every consumer middleware/terminal Throwable propagates by identity unchanged.
Do not catch, wrap, log, map, retry, render, or convert it.

### 8.4 Internal layer

`Internal\MiddlewareLayer` is `final readonly`, implements
`RequestHandlerInterface`, and has exactly two private properties:

```text
middleware
nextHandler
```

It declares only constructor and `handle()`. `handle()` performs exactly:

```php
return $this->middleware->process($request, $this->nextHandler);
```

No other behavior, state, branch, or catch is allowed.

---

## 9. Terminal route-to-handler contract

### 9.1 Public shape

`RoutingRequestHandler` is `final readonly` and implements
`RequestHandlerInterface`.

Its public constant is exactly:

```php
public const string ROUTE_MATCH_ATTRIBUTE = RouteMatch::class;
```

Its constructor and methods are exactly:

```php
/** @param array<array-key, mixed> $handlers */
public function __construct(
    RouteTable $routes,
    array $handlers,
    RequestHandlerInterface $unmatchedHandler,
);

public function routes(): RouteTable;

/** @return array<string, RequestHandlerInterface> */
public function handlers(): array;

public function unmatchedHandler(): RequestHandlerInterface;

public function handle(ServerRequestInterface $request): ResponseInterface;
```

It has exactly four private instance properties:

```text
routes
matcher
handlers
unmatchedHandler
```

`matcher` is one package-created `RouteMatcher` over the exact supplied route
table. The other object properties retain exact caller identities.

### 9.2 Exact handler map

Handler keys are the bounded case-sensitive Phase 2D route names. The map must
contain exactly one `RequestHandlerInterface` for every route and no other key.
An empty route table requires an empty map and is valid.

Validation order is exact:

1. derive expected route names from the canonically ordered `RouteTable`;
2. compare the complete key set before inspecting any handler value;
3. if keys differ, throw `InvalidRouteHandlerKeys` with counts only;
4. otherwise inspect handler values in canonical route-name order;
5. the first non-handler throws `InvalidRouteHandlerValue` at its canonical
   zero-based position; and
6. copy valid entries by value into canonical route-name order.

Map construction and failures are independent of caller array order. No raw
route name, class name, object serialization, callable, service key, or handler
identity enters a diagnostic surface.

Returned handler arrays cannot mutate stored mapping. Consumer handler objects
remain consumer-owned shallow references; the package makes no claim that they
are themselves immutable, reentrant, or concurrency-safe.

### 9.3 Selected request

`handle()` calls the internal matcher with the exact supplied request.

For a successful match:

1. choose the handler by `RouteMatch::route()->name()`;
2. call `withAttribute(self::ROUTE_MATCH_ATTRIBUTE, $match)` exactly once on
   the original request;
3. do not inspect the prior attribute value;
4. pass the returned derived request to the selected handler exactly once; and
5. return the exact `ResponseInterface` returned by that handler.

The reserved attribute key intentionally overwrites any caller-supplied value
at that key with the freshly computed `RouteMatch`. This prevents an untrusted
preexisting request attribute from impersonating framework routing output.
The original request remains unchanged under the PSR-7 contract.

The match and its encoded parameters remain untrusted. The attribute proves
neither authentication, authorization, delivery evidence, handler safety, nor
diagnostic eligibility.

### 9.4 Ordinary unmatched request

If matching returns `null`, call the exact unmatched handler once with the exact
original request. Do not add the route-match attribute. Return its exact
response.

The package does not select a 404 status, header, body, redirect, method-not-
allowed result, or production failure representation. The unmatched handler is
an explicit composition dependency whose concrete response belongs to later
application composition/delivery authority.

### 9.5 Failure propagation and state

Matcher/request/URI access, `withAttribute()`, selected handler, unmatched
handler, middleware, and response-object Throwables propagate unchanged.

Do not catch application failures or provide an error middleware. Although
PSR-15 recommends an outer exception-converting middleware, Kongono's generic
production failure model and fail-closed trusted diagnostic delivery remain
Phase 2G decisions.

The handler may be long-lived. It retains only route declarations, compiled
matcher data, handler composition objects, and the unmatched handler. It never
retains the current request, URI, method, path, match, parameters, response,
exception, middleware cursor, principal, tenant, permission, or operation.

---

## 10. Exact diagnostic and exception foundation

### 10.1 Marker and exception

`Kongono\Http\Server\Exception\ExceptionInterface`:

- extends `Throwable`;
- declares exactly `diagnosticContext()`, `diagnosticId()`,
  `diagnosticTitle()`, and `diagnosticRemediation()` with established
  signatures; and
- is unrelated in both directions to all ten existing package exception
  markers.

`InvalidHttpServerDefinitionException`:

- is final and Experimental;
- extends native `InvalidArgumentException`;
- implements only the HTTP Server package marker beyond native inheritance;
- stores exactly private readonly `diagnostic` and `diagnosticContext`;
- uses the diagnostic title as native message and code zero;
- preserves optional previous Throwable identity;
- has no custom `__toString()`; and
- uses the established exact constructor shape:

```php
/** @param array<string, string|int|bool> $contextValues */
public function __construct(
    HttpServerDiagnostic $diagnostic,
    array $contextValues = [],
    ?Throwable $previous = null,
);
```

### 10.2 Exact three-row catalog

`HttpServerDiagnostic` is an Internal string-backed enum with exactly:

| Case | ID | Title | Remediation | Operation | Violated invariant | Ordered required Safe fields |
|---|---|---|---|---|---|---|
| `TooManyMiddleware` | `KGN-HTTP-SERVER-1001` | Too many middleware components | Reduce the middleware pipeline to the supported maximum | Build middleware pipeline | A middleware pipeline may contain at most 128 components | `middleware_count:Integer`, `maximum_middleware:Integer` |
| `InvalidRouteHandlerKeys` | `KGN-HTTP-SERVER-1002` | Invalid route handler keys | Map every declared route name to exactly one request handler | Build terminal route handler | Handler keys must equal the complete declared route-name set | `route_count:Integer`, `handler_count:Integer`, `missing_count:Integer`, `unexpected_count:Integer` |
| `InvalidRouteHandlerValue` | `KGN-HTTP-SERVER-1003` | Invalid route handler value | Supply a PSR-15 request handler for every declared route | Build terminal route handler | Every mapped route value must implement RequestHandlerInterface | `handler_position:Integer`, `violation:Text` |

The only `violation` token is:

```text
not_request_handler
```

Each enum case supplies exactly the established six public methods:

```text
title
remediation
context
contextFields
contextOperation
contextViolatedInvariant
```

All fields are required, `Safe`, bounded integers or the one closed token. No
route name, method, path, match parameter, header, cookie, query, body, request
attribute, handler class, callable, object, response, exception message, file,
or secret may enter package-owned diagnostics.

---

## 11. Persistent-runtime and security contract

- Pipeline and routing-handler composition is immutable after construction.
- Chain layers are compiled once and contain no request cursor.
- Sequential and reentrant calls use only stack-local request/match/response
  values.
- Consumer middleware may be stateful; the pipeline does not bless it as safe.
- No global/static/singleton/container-current/thread-local/fiber-local request
  or handler state is authorized.
- No container lookup, service locator, class-string instantiation, callable
  invocation adapter, reflection construction, attribute discovery, priority,
  sorting, tag, registry, or first-installed-wins behavior exists.
- No request input enables trusted diagnostics or upgrades a permission.
- No package source reads request headers, body, query, cookies, uploads, server
  params, environment, filesystem, network, clock, random, locale, session,
  process, SAPI globals, logs, events, telemetry, Kernel, Graph, or DI.
- Handler mapping uses only bounded framework-owned route names already
  validated by Phase 2D.
- Response creation remains the responsibility of explicit consumer handlers or
  middleware. A response-producing component receives its factory/prototype
  through its own composition; the pipeline performs no ambient discovery.
- Pipeline construction is O(n) with at most 128 layers. Request handling uses
  one nested call per delegated layer. Routing cost retains Phase 2D bounds.
  No latency, throughput, tail-call, or constant-time claim is made.

---

## 12. Required production tests

### 12.1 Fixtures

The two fixture classes are final, PSR-4 compliant generic callback adapters
used only by tests:

- `CallbackMiddleware` implements `MiddlewareInterface` and delegates its exact
  arguments to a constructor-supplied typed `Closure`;
- `CallbackRequestHandler` implements `RequestHandlerInterface` and delegates
  its exact request to a constructor-supplied typed `Closure`.

They add no production callable adapter and no multi-declaration file.

### 12.2 Diagnostic and exception proof

Prove:

- exact three cases, IDs, fixed prose, operations, invariants, ordered fields,
  kinds, disclosures, required flags, and closed violation vocabulary;
- IDs are unique and match `^KGN-HTTP-SERVER-1\d{3}$`;
- exact enum public method inventory;
- exact native parent, marker separation, finality, properties, constructor,
  previous chain, message/code, status, and no custom stringification; and
- actual rejected composition inputs do not enter any package-owned diagnostic
  surface, excluding native trace/stringification.

### 12.3 Pipeline proof

Prove:

- 0, 1, 128, and 129 middleware boundaries;
- exact declaration-order entry and reverse response unwinding;
- terminal request and response identity;
- derived-request delegation;
- downstream-response replacement;
- short circuit prevents later middleware and terminal access;
- a middleware may call next twice and receives two terminal results without a
  shared cursor;
- middleware and terminal Throwable identity propagation, including a
  downstream failure after delegation;
- caller arrays with referenced entries detach before storage;
- returned arrays cannot mutate stored order;
- repeated alternating and reentrant operations leak no request/response state;
- any PSR-compatible objects work without Kongono HTTP concrete types; and
- exact readonly property, constant, public-method, and internal-layer shapes.

### 12.4 Routing-handler proof

Prove:

- empty routes plus empty handlers delegate unmatched;
- exact complete mappings in arbitrary caller order store in canonical route
  order and detach referenced entries;
- missing, extra, combined missing/extra, numeric-key, and non-handler values
  use exact diagnostics without raw keys/values;
- key and value failures are input-order independent;
- static and parameterized matches select the exact mapped handler;
- the selected derived request contains the exact fresh `RouteMatch` under the
  exact reserved key;
- an existing value at that key is not read and is overwritten;
- the original request remains unchanged;
- exact encoded parameters reach the selected handler and remain untrusted;
- ordinary unmatched uses the exact original request and does not add an
  attribute;
- invalid request routing inputs continue to throw the Routing package request
  exception before any handler call;
- `withAttribute()`, selected-handler, and unmatched-handler Throwables
  propagate by identity unchanged;
- no unrelated PSR request accessor is consulted by HTTP Server source beyond
  the Routing matcher and the exact one `withAttribute()` call;
- multiple alternating matches/unmatched calls leak no state; and
- exact constant, readonly property, public-method, dependency, and API-status
  shapes.

### 12.5 Root architecture enforcement

Update `ApiStatusConventionTest.php` to prove:

- exactly 86 production declarations and 11 package manifests/source roots;
- exact status for all six HTTP Server declarations;
- exact package manifest dependencies and absence of reverse package edges;
- exact PSR-15 interface implementations and method shapes;
- exact native exception parent and marker separation from all ten siblings;
- total framework diagnostic IDs increase from 106 to 109 with no duplicates;
- exact three HTTP Server schemas and closed vocabulary;
- root/package Composer, autoload, PHPUnit, PHPStan, and style integration;
- no `kongono/http-message`, Kernel, Graph, DI, Diagnostic Activation,
  Diagnostic Presentation, or Diagnostic Handler production dependency; and
- no extra production declaration or namespace overlap.

---

## 13. Documentation and ADR work

### 13.1 ADR 0047

Create `docs/adr/0047-explicit-psr-15-middleware-and-terminal-routing.md` with:

```text
Status: Accepted
Date: 2026-08-16
```

Record:

1. the `kongono/http-server` ownership and exact dependency direction;
2. PSR-15 interface adoption, official evidence, and no framework substrate;
3. immutable decoration-based chain rather than a mutable queue/cursor;
4. 128-layer construction bound and diagnostics;
5. declaration-order request flow and reverse response flow;
6. explicit terminal handler and no discovery/priority sorting;
7. complete route-name handler map and canonical validation;
8. `RouteMatch::class` reserved request attribute and overwrite semantics;
9. explicit unmatched handler with no status/body decision;
10. unchanged Throwable propagation and no supplied error middleware;
11. persistent-runtime, shallow consumer-object, and performance consequences;
12. rejection of Symfony/Laravel/third-party dispatchers, callable/controller
    magic, mutable queues, ambient state, container lookup, and route metadata
    embedded into Phase 2D declarations; and
13. exact deferral of bootstrap/Kernel scope to 2F, adapter/failure delivery to
    2G, and runnable proof to 2H.

### 13.2 HTTP Server model

Create `docs/architecture/http-server-model.md` with inspectable examples of:

- empty and ordered pipeline composition;
- outer/inner/terminal request and response sequence;
- short-circuit behavior;
- route table plus name-to-handler mapping;
- selected `RouteMatch` attribute access and its untrusted meaning;
- unmatched delegation;
- Throwable propagation;
- long-lived reuse and consumer middleware responsibility;
- package graph and exact absent capabilities; and
- current versus planned Phase 2F-2H behavior.

Do not present future bootstrap, adapter, 404 body, trusted diagnostics, or
emission as implemented.

### 13.3 Reconcile existing documentation

Within the exact file list, reconcile all current-state claims so they state:

- Phase 2E and the eleventh package are implemented;
- PSR-15 handler/middleware interfaces are installed and used;
- Middleware Pipeline is explicit, bounded, immutable, and transport-free;
- Routing Request Handler maps bounded route names to explicit PSR-15 handlers;
- selected matches are placed under the reserved attribute and remain
  untrusted/non-authorizing;
- unmatched output is delegated, not hard-coded;
- no supplied error middleware or generic production response exists;
- no bootstrap, Kernel web composition, adapter, emission, or runnable app
  exists;
- Phase 2F is next; and
- Phase 2H remains the first runnable black-box milestone.

Preserve historical context. Use amendments/current-state sections rather than
rewriting what an earlier ADR decided at that time. Remove Phase 2E middleware,
terminal mapping, package ownership, and PSR-15 installation from open-item
lists while retaining every genuine later decision.

Add ADR/model links wherever the existing related-material pattern requires.
Verify every local Markdown link.

---

## 14. Explicit prohibited scope

Do not implement or add:

- a composition root, application builder, service provider, configuration
  loader, container registration, DI binding, Graph node, or Kernel hook;
- Kernel operation-scope opening/closing, cleanup, cancellation, retirement, or
  simultaneous-failure representation;
- SAPI/global conversion, request parsing, proxy trust, server runtime,
  listener, adapter, emitter, response delivery, or I/O;
- generic production failure response, status/header/body mapping, incident ID,
  error middleware, or trusted-diagnostic delivery;
- authentication, authorization, session, cookie, CSRF, principal, tenant,
  controller base class, action resolver, callable adapter, reflection dispatch,
  annotation/attribute discovery, route cache, URL generation, or route groups;
- changes to Phase 2D route syntax, names, matching, conflicts, precedence,
  diagnostics, public types, or limits;
- route-specific middleware metadata in `RouteDefinition`;
- third-party framework, middleware dispatcher, PSR-7 implementation, router,
  server, container, or plugin;
- CLI, ORM, database, migrations, events, queue, cache, mail, templates,
  frontend, telemetry, agents, extensions, release/versioning, or deployment;
- Stable APIs, compatibility aliases, shims, deprecated names, suppressions,
  baselines, or weakened quality/security policy; or
- staging, commit, history, ref, branch, tag, remote, fetch, merge, rebase,
  reset, restore, clean, stash, or push operations.

---

## 15. Validation sequence

After implementation and the exact Composer update, run:

```powershell
composer dump-autoload --optimize --strict-psr
php vendor/bin/phpunit --configuration phpunit.xml.dist packages/http-server/tests
php vendor/bin/phpunit --configuration phpunit.xml.dist --display-phpunit-notices packages/http-server/tests
php vendor/bin/phpunit --configuration phpunit.xml.dist tests/Architecture/ApiStatusConventionTest.php
php vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --no-progress packages/http-server/src packages/http-server/tests tests/Architecture/ApiStatusConventionTest.php
composer cs
composer analyse -- --memory-limit=512M
composer quality
git -c safe.directory='C:/Users/antho/Dev/kongono' diff --check
```

Also lint every new/modified PHP file individually.

No PSR-4 warning, PHPUnit notice/risk/skip/incomplete/output, PHPStan error,
style finding, advisory, platform failure, unexpected Composer warning, broken
link, encoding defect, trailing whitespace, NUL, alternate stream, reparse
point, wrong mode, or unauthorized path is accepted.

Final inventories must be exactly:

```text
package directories: 11
package manifests: 11
production PHP files: 86
test PHP files: 110
PHP files covered by PHP CS Fixer: 196
unique framework diagnostic IDs: 109
```

Before and after report population, final status must be:

```text
tracked modifications: 18
untracked paths: 105
total status paths: 123
staged paths: 0
```

The 24 existing authorized paths may already be tracked-modified or untracked;
classification must not be inferred from path age. The closed created-path and
full status inventories are authoritative.

Snapshot every preexisting Git-visible file before mutation. Prove all paths
outside the authorized existing-file set and caller report retain exact bytes.
Reverify all historical caller records independently.

Report exact final versions, hashes, lock deltas, tests/assertions, diagnostics,
inventories, warnings, retries, and Git evidence. Test totals are observed
results, not architect-supplied target numbers.

---

## 16. Mandatory stop rules

Stop immediately and preserve the current state if:

- directive identity or a caller record differs;
- the report is not the exact zero-byte prerequisite;
- baseline Git/hash/inventory/file-safety evidence differs;
- pre-change quality has a code/policy failure;
- Composer would alter anything beyond Section 6;
- any extra production/test/documentation path is required;
- PSR-15, PSR-7, Routing, diagnostic, API, or lifecycle behavior is ambiguous;
- a production defect outside the exact authorized boundary is exposed;
- a test can pass only by weakening an assertion or changing a locked contract;
- any non-report validation, preservation, or final-state gate fails; or
- a prohibited mutation has occurred or would be required.

Do not repair adjacent work. Do not populate the report after a failed
non-report gate. Explain the exact mismatch, viable bounded options, and a
recommendation.

---

## 17. Strict acceptance criteria

Phase 2E is complete only if every item passes:

1. all authorities were read completely;
2. independently delivered directive bytes/hash matched;
3. zero-byte report and immutable records matched;
4. exact Git/hash/status/inventory/file-safety baseline matched;
5. pre-change quality passed at 387 tests / 6721 assertions;
6. created paths equal the exact 16-path list;
7. modified existing paths are confined to the exact 24-path list;
8. package identity, namespace, requirements, and dependency direction are
   exact;
9. only the two accepted PSR-15 interface packages and local package were
   added to dependency resolution;
10. production inventory is exactly six declarations with exact statuses;
11. middleware pipeline shape, bound, detachment, compilation, and order are
    exact;
12. middleware short-circuit, request/response transformation, repeated-next,
    and Throwable propagation are exact;
13. no per-request cursor or retained request/response state exists;
14. route-handler key set is complete, canonical, detached, and order
    independent;
15. selected route invokes the exact mapped handler with the exact fresh match
    attribute;
16. the reserved key overwrites without inspecting a prior value;
17. unmatched delegates the exact original request without a match attribute;
18. route matches/parameters remain untrusted and non-authorizing;
19. exact three diagnostics, schemas, fixed prose, vocabulary, and exception
    contract pass;
20. raw route/request/handler data is absent from package diagnostics;
21. all consumer/PSR Throwables propagate unchanged;
22. root architecture proves 11 packages, 86 production types, 109 diagnostics,
    exact edges/statuses/shapes, and no reverse dependency;
23. focused tests, root architecture, focused/full PHPStan, strict PSR autoload,
    style, audit, platform, and canonical quality pass;
24. ADR 0047 is Accepted and the model/docs fully reconcile current/future
    state;
25. no bootstrap, Kernel web lifecycle, adapter, delivery, emission, generic
    failure response, or runnable app was introduced;
26. final inventories are exactly 11/11/86/110/196/109;
27. final status is exactly 18 modified / 105 untracked / 123 total / 0 staged;
28. all protected bytes, caller records, modes, streams, refs, history, and
    index remain exact;
29. only after items 1-28 pass is the report populated;
30. the report uses the exact structure below, contains no unsupported claim,
    ends with the exact next-step sentence, and its final byte count/SHA-256 are
    returned outside its body.

---

## 18. Standard executor completion report

Populate `docs/task reports/Phase_2E.md` with exactly these top-level headings:

```markdown
# PHASE 2E TASK COMPLETION REPORT

## 1. STATUS

## 2. SUMMARY

## 3. BASELINE VERIFIED

## 4. CALLER RECORDS

## 5. FILES CREATED

## 6. FILES MODIFIED

## 7. ARCHITECTURAL DECISIONS

## 8. PACKAGE AND DEPENDENCY CONTRACT

## 9. MIDDLEWARE PIPELINE CONTRACT

## 10. TERMINAL ROUTING CONTRACT

## 11. DIAGNOSTIC AND SECURITY REVIEW

## 12. PERSISTENT-RUNTIME REVIEW

## 13. DEVIATIONS FROM DIRECTIVE

## 14. DEPENDENCIES ADDED/REMOVED

## 15. ENVIRONMENT

## 16. COMMANDS RUN

## 17. VALIDATION RESULTS

## 18. DOCUMENTATION REVIEW

## 19. WARNINGS / KNOWN ISSUES

## 20. QUESTIONS / BLOCKERS

## 21. GIT DIFF SUMMARY

## 22. ACCEPTANCE CRITERIA

## 23. NEXT STEP
```

The report must enumerate every created/modified path, exact package and lock
delta, every contract/proof, all command outcomes, actual versions and test
totals, protected-state evidence, report timing, warnings/retries, and each
acceptance item.

Its exact final sentence is:

```text
Await architect review before Phase 2F.
```

After validating the populated report, return its exact byte count and SHA-256
to the caller outside the report body.

---

## 19. Required final outcome

Kongono ends Phase 2E with an explicit, bounded, immutable PSR-15 middleware
pipeline and a deterministic terminal route-to-handler boundary. Composition,
Kernel operation ownership, runtime adaptation, response delivery, production
failure handling, and runnable black-box behavior remain absent and require
their later authenticated phases.
