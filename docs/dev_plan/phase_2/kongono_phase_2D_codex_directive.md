# CODEX IMPLEMENTATION DIRECTIVE - KONGONO PHASE 2D

## Deterministic route declaration and matching foundation

**Directive type:** Architecture decision plus bounded production implementation

**Phase:** 2D

**Status:** Authorized for execution only after caller authentication

**Date:** 2026-08-16

**Repository:** `C:\Users\antho\Dev\kongono`

**Executor role:** Executor, not architect

---

## 1. Mission and exact outcome

Phase 2D establishes Kongono's first deterministic routing boundary. It must:

1. accept the exact routing architecture in this directive and ADR 0046;
2. create the tenth first-party package, `kongono/routing`;
3. implement immutable route declarations, an immutable canonical route table,
   immutable route-match values, and a stateless deterministic matcher;
4. consume only PSR-7 `ServerRequestInterface` and `UriInterface` at the public
   request boundary;
5. implement the exact path-template grammar, method rules, precedence,
   conflict detection, limits, diagnostics, and unmatched behavior below;
6. remain safe for persistent and overlapping runtimes by retaining no
   request-specific or previous-match state in long-lived objects;
7. provide focused production tests and root architecture enforcement;
8. update current-state documentation without presenting later capabilities as
   implemented; and
9. preserve every accepted pre-Phase-2D byte outside the closed authorization
   boundary.

The completed phase answers only this question:

> Given an immutable bounded route table and an explicit PSR-7 server request,
> which one route declaration matches, and what are its bounded encoded path
> parameters?

It does **not** invoke a handler, dispatch middleware, create a response, read
SAPI globals, compose the framework, emit output, or perform I/O.

Phase 2D is not permission to implement Phase 2E or any later phase.

---

## 2. Mandatory authority and no-assumption rule

### 2.1 Read completely before validation or mutation

Read these exact paths in full:

```text
AGENTS.md
README.md
ARCHITECTURE.md
SECURITY.md
composer.json
composer.lock
phpunit.xml.dist
phpstan.neon.dist
.php-cs-fixer.dist.php
docs/doctrine.md
docs/documentation-standard.md
docs/adr/0003-monorepo-initial-development.md
docs/adr/0004-microkernel-capability-architecture.md
docs/adr/0006-persistent-runtime-first.md
docs/adr/0013-documentation-doctrine.md
docs/adr/0014-php-engineering-toolchain.md
docs/adr/0015-root-composer-lock-for-reproducible-tooling.md
docs/adr/0016-strict-types-and-per-cs-style.md
docs/adr/0017-phpunit-as-core-test-runner.md
docs/adr/0018-phpstan-max-without-baseline.md
docs/adr/0019-composer-dependency-security-policy.md
docs/adr/0021-machine-checkable-api-status.md
docs/adr/0026-actionable-diagnostics-and-error-boundaries.md
docs/adr/0027-package-local-runtime-diagnostic-metadata.md
docs/adr/0028-bounded-diagnostic-context-and-disclosure.md
docs/adr/0029-presentation-neutral-diagnostic-value-foundation.md
docs/adr/0031-neutral-api-metadata-ownership-and-acyclic-package-dependencies.md
docs/adr/0043-phase-2-runnable-web-framework-milestone.md
docs/adr/0044-http-message-and-interoperability-architecture.md
docs/adr/0045-first-party-http-message-and-factory-implementation.md
docs/architecture/http-message-model.md
docs/architecture/phase-2-roadmap.md
docs/architecture/runtime-model.md
docs/architecture/overview.md
docs/engineering/api-status.md
docs/engineering/error-handling.md
docs/engineering/package-layout.md
docs/glossary.md
docs/dev_plan/kongono_phase_2C_6_codex_directive_correction.md
docs/task reports/Phase_2C_6_correction.md
```

Then inspect every existing file that Phase 2D may modify and every adjacent
production/test pattern needed to implement the package consistently.

Do not substitute an ADR merely because its number resembles a required path.
Every path above is exact.

### 2.2 Independent directive identity is mandatory

The architect must supply the byte count and SHA-256 of this directive outside
the document. Compare those independently supplied values with:

```text
docs/dev_plan/kongono_phase_2D_codex_directive.md
```

The local file is not self-authenticating. If either independent value is
missing or mismatched:

```text
STOP
ASK
DO NOT GUESS
```

Do not run broad quality checks, edit files, populate the report, stage, or
commit before this gate passes.

### 2.3 Caller report placeholder

The caller must create:

```text
docs/task reports/Phase_2D.md
```

It must begin as an ordinary, untracked, unstaged, zero-byte file absent from
`HEAD`, with SHA-256:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

Keep it zero-byte until every permitted non-report acceptance gate passes.

### 2.4 Mandatory stop-and-ask rule

If any locked identity, starting path, inventory, dependency edge, API shape,
grammar, security boundary, diagnostic mapping, tooling contract, test
requirement, documentation ownership, or final state is unclear or mismatched:

```text
STOP
ASK
DO NOT GUESS
```

Report the exact decision, why it is required, viable options and tradeoffs,
and one recommendation. Do not implement the recommendation without new
authenticated authority.

---

## 3. Accepted starting state

### 3.1 Git and repository identity before caller setup

The accepted pre-Phase-2D state is:

```text
branch: main
local branches: 1
HEAD: d7846f137a1d53f5306f48d19196b916fde44ac2
HEAD tree: bfa1908c619069b699bc9b08668db17ea2555eb3
HEAD parent: 8fe793b124384744ac43887037360154c149241a
commit count: 9
tracked modifications: 18
untracked paths with -uall: 63
total status paths with -uall: 81
staged paths: 0
remotes: 0
tags: 0
package directories: 9
package manifests: 9
production PHP files: 70
test PHP files: 98
```

Exactly one ordinary local branch, `refs/heads/main`, is permitted. Codex app
managed refs under this exact prefix are operational metadata and are exempt
from the one-branch invariant:

```text
refs/codex/turn-diffs/**
```

At observation time 37 such exempt refs existed. Their count and identities
may change because the application owns them. Require zero refs outside
`refs/heads/main` and the exempt prefix. Do not create, update, delete, pack,
or otherwise manipulate exempt refs.

### 3.2 Exact accepted Phase 2C.6 records

```text
docs/dev_plan/kongono_phase_2C_6_codex_directive_correction.md
bytes: 15609
SHA-256: 3F192AE0D02E4311DB0DD42B2C17DA59DC7E80F69EF6904524939D4ABA115B2A

docs/task reports/Phase_2C_6_correction.md
bytes: 15443
SHA-256: 160D135C75F8FB415B445621C3B6A7D8B51C3983E4BB8177657F477187C500D1
```

Both are immutable caller records. Do not normalize, truncate, reuse, stage,
commit, or modify them.

### 3.3 Critical starting identities

```text
composer.json
bytes: 3515
SHA-256: 8E00F05ECF232CE547801884F1AA0F0749160375B5DA16AB49A4C447CE4D7C30

composer.lock
bytes: 187578
SHA-256: 264C1D57B3029A4DE9CB917CE5051819492E642D1BEFE6D787714ADA61A0E99F

docs/architecture/phase-2-roadmap.md
bytes: 10466
SHA-256: 6ADAEC35AE9128E4B679992CB0F3D490D70393FEA798AB2F76C12044B6A51C7F

docs/adr/0043-phase-2-runnable-web-framework-milestone.md
bytes: 14498
SHA-256: 3EBA65333547D6265BD95F2FAD5BC1189449D5BF34A29FDFFDFFA3595EB120FF

docs/adr/0044-http-message-and-interoperability-architecture.md
bytes: 15519
SHA-256: B7EE864E0AF7589D5EA040D508A767C5ED6AADAB4E93B2FFEBA97F82D332D747

docs/adr/0045-first-party-http-message-and-factory-implementation.md
bytes: 6213
SHA-256: 65CA5A335E0430DED1197E4E9F0D70FAD3E851B34913BA77A258A87C88E7FD60
```

### 3.4 Exact accepted status inventory before caller setup

The 81 paths are exactly:

```text
 M .php-cs-fixer.dist.php
 M ARCHITECTURE.md
 M README.md
 M SECURITY.md
 M composer.json
 M composer.lock
 M docs/adr/0004-microkernel-capability-architecture.md
 M docs/adr/README.md
 M docs/architecture/overview.md
 M docs/architecture/runtime-model.md
 M docs/doctrine.md
 M docs/engineering/api-status.md
 M docs/engineering/error-handling.md
 M docs/engineering/package-layout.md
 M docs/glossary.md
 M phpstan.neon.dist
 M phpunit.xml.dist
 M tests/Architecture/ApiStatusConventionTest.php
?? docs/adr/0043-phase-2-runnable-web-framework-milestone.md
?? docs/adr/0044-http-message-and-interoperability-architecture.md
?? docs/adr/0045-first-party-http-message-and-factory-implementation.md
?? docs/architecture/http-message-model.md
?? docs/architecture/phase-2-roadmap.md
?? docs/dev_plan/kongono_phase_1I_1_codex_directive_correction.md
?? docs/dev_plan/kongono_phase_1I_codex_directive.md
?? docs/dev_plan/kongono_phase_2A_codex_directive.md
?? docs/dev_plan/kongono_phase_2B_1_codex_directive_correction.md
?? docs/dev_plan/kongono_phase_2B_2_codex_directive_correction.md
?? docs/dev_plan/kongono_phase_2B_codex_directive.md
?? docs/dev_plan/kongono_phase_2C_1_codex_directive_correction.md
?? docs/dev_plan/kongono_phase_2C_2_codex_directive_correction.md
?? docs/dev_plan/kongono_phase_2C_3_codex_directive_correction.md
?? docs/dev_plan/kongono_phase_2C_4_codex_directive_correction.md
?? docs/dev_plan/kongono_phase_2C_5_codex_directive_correction.md
?? docs/dev_plan/kongono_phase_2C_6_codex_directive_correction.md
?? docs/dev_plan/kongono_phase_2C_codex_directive.md
?? docs/task reports/Phase_1I.md
?? docs/task reports/Phase_1I_1_correction.md
?? docs/task reports/Phase_2A.md
?? docs/task reports/Phase_2B.md
?? docs/task reports/Phase_2B_1_correction.md
?? docs/task reports/Phase_2B_2_correction.md
?? docs/task reports/Phase_2C.md
?? docs/task reports/Phase_2C_1_correction.md
?? docs/task reports/Phase_2C_2_correction.md
?? docs/task reports/Phase_2C_3_correction.md
?? docs/task reports/Phase_2C_4_correction.md
?? docs/task reports/Phase_2C_5_correction.md
?? docs/task reports/Phase_2C_6_correction.md
?? packages/http-message/README.md
?? packages/http-message/composer.json
?? packages/http-message/src/Diagnostic/HttpMessageDiagnostic.php
?? packages/http-message/src/Exception/ExceptionInterface.php
?? packages/http-message/src/Exception/HttpMessageRuntimeException.php
?? packages/http-message/src/Exception/InvalidMessageArgumentException.php
?? packages/http-message/src/Factory/ResponseFactory.php
?? packages/http-message/src/Factory/ServerRequestFactory.php
?? packages/http-message/src/Factory/StreamFactory.php
?? packages/http-message/src/Factory/UploadedFileFactory.php
?? packages/http-message/src/Factory/UriFactory.php
?? packages/http-message/src/Internal/HeaderCollection.php
?? packages/http-message/src/Internal/HttpMessageValidation.php
?? packages/http-message/src/Message/AbstractMessage.php
?? packages/http-message/src/Message/Response.php
?? packages/http-message/src/Message/ServerRequest.php
?? packages/http-message/src/Message/Stream.php
?? packages/http-message/src/Message/UploadedFile.php
?? packages/http-message/src/Message/Uri.php
?? packages/http-message/tests/Unit/Diagnostic/HttpMessageDiagnosticTest.php
?? packages/http-message/tests/Unit/Exception/HttpMessageExceptionTest.php
?? packages/http-message/tests/Unit/Factory/ResponseFactoryTest.php
?? packages/http-message/tests/Unit/Factory/ServerRequestFactoryTest.php
?? packages/http-message/tests/Unit/Factory/StreamFactoryTest.php
?? packages/http-message/tests/Unit/Factory/UploadedFileFactoryTest.php
?? packages/http-message/tests/Unit/Factory/UriFactoryTest.php
?? packages/http-message/tests/Unit/Internal/HeaderCollectionTest.php
?? packages/http-message/tests/Unit/Message/ResponseTest.php
?? packages/http-message/tests/Unit/Message/ServerRequestTest.php
?? packages/http-message/tests/Unit/Message/StreamTest.php
?? packages/http-message/tests/Unit/Message/UploadedFileTest.php
?? packages/http-message/tests/Unit/Message/UriTest.php
```

Quoted porcelain rendering is not authority; the logical paths above are.
Use `git status --porcelain=v1 -uall` and path-safe inspection.

After the caller adds only the Phase 2D directive and zero-byte report, require:

```text
tracked modifications: 18
untracked paths: 65
total status paths: 83
staged paths: 0
```

Any other pre-change mismatch is a stop condition.

### 3.5 Baseline validation

After all authentication and baseline gates pass, run:

```text
composer quality
```

The accepted Phase 2C.6 baseline is:

```text
security advisories: 0
PHP CS Fixer: 0 of 168 files fixable
PHPStan: 0 errors
PHPUnit: 329 tests, 5912 assertions
```

If the locked audit is blocked only by managed network/cache restrictions,
retry the identical command with approved access. Do not skip, weaken, or
replace the audit. Any semantic failure is a stop condition.

---

## 4. Locked architectural decisions

### 4.1 Package ownership

Create exactly:

```text
directory: packages/routing
Composer package: kongono/routing
production namespace: Kongono\Routing\
test namespace: Kongono\Routing\Tests\
```

Routing owns only route declaration validation, canonical route-table
validation, deterministic request matching, immutable match values, and its
own deliberate failure family.

### 4.2 Exact production dependency graph

`packages/routing/composer.json` may require exactly:

```text
kongono/api-metadata: *
kongono/diagnostics: *
php: ^8.5
psr/http-message: ^2.0
```

The package manifest has exactly `name`, `description`, `type`, `require`,
`autoload`, and `autoload-dev` top-level keys, following the established package
format. Its mappings are exactly:

```text
autoload PSR-4: Kongono\Routing\ => src/
autoload-dev PSR-4: Kongono\Routing\Tests\ => tests/
```

It must not require `kongono/http-message`. The production package consumes
PSR interfaces, not Kongono concrete messages. It must not depend on Kernel,
Application Graph, Dependency Injection, Diagnostic Activation, Diagnostic
Presentation, Diagnostic Handler, either PSR-15 package, or any runtime,
adapter, framework, container, server, regex-router, or third-party router.

Root development composition must require:

```text
kongono/routing: @dev
```

No new third-party package may be introduced. Existing PSR-7 installation is
reused.

### 4.3 No terminal handler yet

A route declaration contains no callable, closure, object, controller, action,
service key, class-string, PSR-15 handler, middleware list, container lookup,
or Application Graph identifier.

Its bounded name is the only future mapping token. Phase 2E separately decides
terminal-handler and middleware contracts. Do not pre-implement that mapping.

### 4.4 Persistent-runtime contract

`RouteDefinition`, `RouteTable`, and `RouteMatch` are immutable detached value
objects. `RouteMatcher` is immutable after construction and may be long-lived.

Each `match()` call receives its request explicitly and returns a fresh
`RouteMatch` or `null`. No production routing object may retain the request,
URI, last method, last path, last match, parameters, exception, operation,
principal, tenant, permission, or any other request-specific state after the
call. No global, static mutable, singleton, registry, service-locator,
thread-local, fiber-local, or ambient current-route state is permitted.

The matcher must be safe to call sequentially or concurrently on the same
instance under ordinary PHP value semantics.

### 4.5 Purity and side effects

Routing performs no filesystem, network, stream, environment, session, clock,
random, locale, process, SAPI-global, response, logging, telemetry, event,
container, Kernel, or Graph operation. It must not read a request body, headers,
cookies, query parameters, server parameters, parsed body, uploaded files, or
attributes.

It may call only:

```text
ServerRequestInterface::getMethod()
ServerRequestInterface::getUri()
UriInterface::getPath()
```

Any Throwable raised by those consumer-supplied methods propagates unchanged.

---

## 5. Exact public and internal production inventory

Create exactly these ten production declarations, one declaration per matching
PSR-4 file:

```text
packages/routing/src/Diagnostic/RoutingDiagnostic.php
packages/routing/src/Exception/ExceptionInterface.php
packages/routing/src/Exception/InvalidRouteDefinitionException.php
packages/routing/src/Exception/InvalidRouteRequestException.php
packages/routing/src/Definition/RouteDefinition.php
packages/routing/src/Definition/RouteTable.php
packages/routing/src/Match/RouteMatch.php
packages/routing/src/Matcher/RouteMatcherInterface.php
packages/routing/src/Matcher/RouteMatcher.php
packages/routing/src/Internal/RouteSyntax.php
```

API statuses are exact:

| Declaration | Status |
|---|---|
| `RoutingDiagnostic` | Internal |
| routing `ExceptionInterface` | Public |
| `InvalidRouteDefinitionException` | Experimental |
| `InvalidRouteRequestException` | Experimental |
| `RouteDefinition` | Experimental |
| `RouteTable` | Experimental |
| `RouteMatch` | Experimental |
| `RouteMatcherInterface` | Experimental |
| `RouteMatcher` | Experimental |
| `RouteSyntax` | Internal |

Every declaration must use `strict_types=1`, the exact namespace implied by its
path, and exactly one `#[Api(...)]` attribute. No Stable routing API is created.

No additional production PHP declaration, trait, attribute, enum, interface,
exception, helper, alias, shim, or compatibility layer is authorized.

---

## 6. Exact route grammar and limits

### 6.1 Route names

A route name:

- is 1 through 128 bytes;
- matches exactly `[A-Za-z][A-Za-z0-9._-]*` under ASCII byte semantics;
- is case-sensitive;
- is stored exactly as supplied; and
- must be unique across a `RouteTable`.

No trimming, Unicode normalization, case folding, namespace inference, or
automatic naming is permitted.

### 6.2 Declared methods

The method input must be a PHP list with 1 through 16 entries. Every entry:

- is a string of 1 through 32 bytes;
- is a valid HTTP token;
- is already uppercase ASCII canonical form; and
- is unique within that route.

Do not uppercase or trim caller input. Reject lowercase or mixed-case declared
methods as non-canonical. Detach entries by value before storage, sort the
stored list with binary `strcmp`, and return fresh arrays from accessors.

### 6.3 Path templates

A template:

- is `/` or begins with `/`;
- is 1 through 2048 bytes;
- has at most 32 non-root segments;
- has no trailing slash except the root `/`;
- has no empty segment and therefore no `//`;
- has segments of 1 through 128 bytes; and
- contains only the literal and parameter forms below.

A parameter segment occupies the whole segment and is exactly:

```text
{name}
```

Parameter names are 1 through 64 bytes and match exactly:

```text
[A-Za-z_][A-Za-z0-9_]*
```

Parameter names must be unique within a template.

A literal segment contains one or more RFC 3986 `pchar` byte forms:

```text
unreserved / sub-delims / ":" / "@" / valid "%HH" triplet
```

Raw `{`, `}`, `?`, `#`, slash, control, space, non-ASCII byte, or malformed
percent triplet is invalid in a literal segment. Percent-triplet hexadecimal
case is preserved and compared exactly.

No optional segment, wildcard, catch-all, regular expression, inline
constraint, default, host, scheme, port, query, fragment, decoding rule,
controller syntax, middleware syntax, or generation syntax exists.

### 6.4 Request input limits

The matcher validates the explicit request method and URI path defensively.

Request method:

- 1 through 32 bytes;
- any valid case-sensitive HTTP token;
- compared exactly with the canonical declared method;
- never trimmed or case-normalized.

Request URI path:

- empty string is explicitly treated as `/`;
- otherwise must begin with `/`;
- is at most 8192 bytes;
- has at most 64 segments;
- has segments of at most 1024 bytes;
- contains only slash and valid RFC 3986 `pchar` byte forms;
- permits empty segments and a trailing slash as valid request syntax, but no
  authorized route template can match those empty segments; and
- is never percent-decoded, dot-segment-normalized, slash-collapsed, Unicode-
  normalized, or case-folded.

Encoded parameter values remain exact encoded path-segment bytes. They are
untrusted application input, not diagnostic Safe data.

The query and fragment are outside matching. The explicit PSR request target is
not consulted. Phase 2G owns outer-message construction and consistency.

---

## 7. Exact matching, precedence, and conflict rules

### 7.1 Candidate selection

A candidate must have:

1. an exact case-sensitive method match; and
2. the same path-segment count as the validated request path.

A literal segment matches only exact bytes. A parameter segment matches one
non-empty validated request segment and captures that exact encoded string.

### 7.2 Deterministic specificity

For each template, form a left-to-right specificity vector where:

```text
literal segment = 1
parameter segment = 0
```

Among matching candidates, compare equal-length vectors lexicographically from
left to right. `1` wins over `0` at the first differing position. This makes an
earlier literal more specific than an earlier parameter. Declaration/input
order never breaks ties.

Examples:

```text
/users/new          wins over /users/{id} for /users/new
/a/{value}          wins over /{value}/a for /a/a
```

### 7.3 Construction-time conflicts

For any shared method, two routes conflict when they have:

- the same segment count;
- parameters in the same positions; and
- byte-equal literal segments in every literal position.

Parameter names do not distinguish patterns. Therefore `/users/{id}` and
`/users/{slug}` conflict for a shared method. Disjoint literals such as
`/a/{id}` and `/b/{id}` do not conflict.

Reject conflicts while building `RouteTable`; never choose by input order.

### 7.4 Unmatched behavior

`match()` returns `null` for every valid request having no candidate. This
includes method mismatch, trailing-slash mismatch, repeated-slash mismatch,
literal mismatch, and segment-count mismatch.

There is no automatic `HEAD` fallback, automatic `OPTIONS`, method-not-allowed
classification, redirect, slash repair, decode retry, or default route.

Invalid request method/path structure throws `InvalidRouteRequestException`.
Unmatched is ordinary typed control flow, not an exception.

### 7.5 Deterministic ordering and cost

`RouteTable` stores and returns routes in binary route-name order, independent
of constructor input order. It accepts zero through 1024 routes.

The authorized first implementation may precompile bounded template arrays in
`RouteMatcher::__construct()`. It uses no cache, trie, regex compiler, generated
PHP, serialization, or global index. Construction is `O(R*S)` time and storage;
one match is `O(R*S)` worst case, where `R <= 1024` and `S <= 32`; returned
parameter storage is `O(P)`. Do not claim optimized-router performance.

---

## 8. Exact calling contracts

### 8.1 `RouteDefinition`

```php
final readonly class RouteDefinition
{
    /**
     * @param list<string> $methods
     * @throws InvalidRouteDefinitionException
     */
    public function __construct(string $name, array $methods, string $pathTemplate);

    public function name(): string;

    /** @return list<string> */
    public function methods(): array;

    public function pathTemplate(): string;
}
```

It has exactly three private non-static properties named `name`, `methods`, and
`pathTemplate`. Validate and detach referenced array entries before assignment.

### 8.2 `RouteTable`

```php
final readonly class RouteTable
{
    /**
     * @param list<RouteDefinition> $routes
     * @throws InvalidRouteDefinitionException
     */
    public function __construct(array $routes);

    /** @return list<RouteDefinition> */
    public function routes(): array;
}
```

It has exactly one private non-static property named `routes`. It accepts an
empty table, rejects non-lists/non-route entries/over-limit lists, detaches the
list, validates duplicate names and conflicts, and stores canonical name order.

### 8.3 `RouteMatch`

```php
final readonly class RouteMatch
{
    /**
     * @param array<string, string> $parameters
     * @throws InvalidRouteDefinitionException
     */
    public function __construct(RouteDefinition $route, array $parameters);

    public function route(): RouteDefinition;

    /** @return array<string, string> */
    public function parameters(): array;
}
```

It has exactly two private non-static properties named `route` and
`parameters`. Parameter keys must equal the route template's parameter names.
Input key order may differ; stored/output order follows template order. Values
must be non-empty valid request-segment strings within the request-segment
bound. Detach keys/values by value. Missing, extra, non-string, invalid, or
duplicate-coerced keys are rejected.

### 8.4 Matcher interface

```php
interface RouteMatcherInterface
{
    /** @throws InvalidRouteRequestException */
    public function match(ServerRequestInterface $request): ?RouteMatch;
}
```

The interface declares exactly this one method.

### 8.5 Concrete matcher

```php
final readonly class RouteMatcher implements RouteMatcherInterface
{
    public function __construct(RouteTable $routes);

    /** @throws InvalidRouteRequestException */
    public function match(ServerRequestInterface $request): ?RouteMatch;
}
```

It has exactly two private non-static properties named `routes` and
`compiledRoutes`. `compiledRoutes` is a detached bounded list of internal array
records; it contains declarations and parsed template structure only, never a
request or match. Its exact analyzed shape is:

```php
/**
 * @var list<array{
 *   route: RouteDefinition,
 *   methods: list<string>,
 *   segments: list<array{kind: 'literal'|'parameter', value: string}>,
 *   specificity: string
 * }>
 */
```

### 8.6 Internal syntax helper

`RouteSyntax` is final, stateless, and Internal. It centralizes exact method,
template, request-path, structural-shape, specificity, parameter-name, and
segment validation so production types do not drift. It exposes no public
framework contract beyond what Internal PHP visibility necessarily permits.

It has a private constructor, no properties, and exactly these six public
static methods:

```php
public static function routeName(string $name): string;

/** @param array<array-key, mixed> $methods @return list<string> */
public static function routeMethods(array $methods): array;

/**
 * @return array{
 *   segments: list<array{kind: 'literal'|'parameter', value: string}>,
 *   parameter_names: list<string>,
 *   specificity: string,
 *   structural_pattern: string
 * }
 */
public static function routeTemplate(string $template): array;

public static function requestMethod(string $method): string;

/** @return list<string> */
public static function requestPath(string $path): array;

/**
 * @param array<array-key, mixed> $parameters
 * @return array<string, string>
 */
public static function routeMatchParameters(string $template, array $parameters): array;
```

`routeTemplate()` is the single parser used by definition, table, match, and
matcher code. `structural_pattern` retains literal bytes and replaces every
parameter segment with the exact marker `{}`. Raw braces are forbidden in
literal segments, so this representation is collision-free. For conflict
detection, key the canonical method, one NUL separator, and the structural
pattern; valid methods cannot contain NUL. `specificity` is the exact `1`/`0`
vector from Section 7.2.

Do not add public constants or additional public methods to any authorized
type.

### 8.7 Deterministic validation and access order

Use these exact orders so simultaneous defects select the same failure:

1. `RouteDefinition`: name; method-list shape/count; method entries from input
   position zero upward; template top-level limits; template segments from left
   to right.
2. One method entry: type; empty; length; token syntax; uppercase canonical
   form; duplicate.
3. One template segment: length; exact parameter form when either brace is
   present; duplicate parameter; percent-triplet validity; remaining literal
   pchar validity.
4. `RouteTable`: list shape; count; entry types from zero upward; duplicate
   names/conflicts by earlier/later input position; canonical sort only after
   validation.
5. `RouteMatch`: compile the already-valid route template; key-set equality;
   then values in template-parameter order. Any missing, extra, integer, or
   otherwise non-exact key set is `key_mismatch`; any invalid value is
   `invalid_value`.
6. `RouteMatcher::match()`: call `getMethod()`; validate it; call `getUri()`;
   call `getPath()`; validate/split it; then scan compiled routes in canonical
   route-name order while selecting only by the specificity rule.
7. Request method validation: empty; length; token syntax. Request path
   validation: empty-to-root rule; length; leading slash; segment count; then
   each segment's length, percent triplets, and pchar bytes from left to right.

Do not catch a Throwable from a PSR getter. A method failure occurs before URI
access, and a URI failure occurs before path access.

---

## 9. Exception and diagnostic foundation

### 9.1 Marker and concrete exceptions

`Kongono\Routing\Exception\ExceptionInterface`:

- extends `Throwable`;
- declares exactly `diagnosticContext()`, `diagnosticId()`,
  `diagnosticTitle()`, and `diagnosticRemediation()` with the established
  signatures; and
- is unrelated in both directions to every other package exception marker.

`InvalidRouteDefinitionException`:

- final, Experimental;
- extends native `InvalidArgumentException`;
- implements the routing marker; and
- reports route, table, conflict, and fabricated-match invalidity.

`InvalidRouteRequestException`:

- final, Experimental;
- extends native `RuntimeException`;
- implements the routing marker; and
- reports only invalid PSR request method/path structure.

Both constructors are exactly:

```php
/** @param array<string, string|int|bool> $contextValues */
public function __construct(
    RoutingDiagnostic $diagnostic,
    array $contextValues = [],
    ?Throwable $previous = null,
);
```

Both store exactly two private readonly properties named `diagnostic` and
`diagnosticContext`; use the diagnostic title as the native message; preserve
the previous chain; and retain no raw rejected route/request value. Do not add a
custom `__toString()`.

### 9.2 Exact diagnostic catalog

`RoutingDiagnostic` is an Internal string-backed enum with exactly ten cases:

| Case | ID | Title | Remediation | Operation | Violated invariant | Ordered required Safe fields |
|---|---|---|---|---|---|---|
| `InvalidRouteName` | `KGN-ROUTING-1001` | Invalid route name | Supply a bounded canonical route name | Declare route | Route name must use the canonical bounded grammar | `name_length:Integer`, `violation:Text` |
| `InvalidRouteMethods` | `KGN-ROUTING-1002` | Invalid route method list | Supply a bounded PHP list of canonical HTTP methods | Declare route | Route methods must form a bounded non-empty list | `entry_count:Integer`, `violation:Text` |
| `InvalidRouteMethod` | `KGN-ROUTING-1003` | Invalid route method | Correct the reported route method entry | Declare route | Every declared method must be a unique canonical HTTP token | `method_position:Integer`, `method_length:Integer`, `violation:Text` |
| `InvalidRouteTemplate` | `KGN-ROUTING-1004` | Invalid route path template | Supply a bounded route template using literal or parameter segments | Declare route | Route template must use the bounded Phase 2D grammar | `template_length:Integer`, `violation:Text` |
| `DuplicateRouteParameter` | `KGN-ROUTING-1005` | Duplicate route parameter | Give every route parameter a unique name | Declare route | Parameter names must be unique within one template | `parameter_position:Integer` |
| `InvalidRouteTable` | `KGN-ROUTING-1006` | Invalid route table | Supply a bounded PHP list containing only route definitions | Build route table | Route table must be a bounded list of route definitions | `entry_count:Integer`, `violation:Text` |
| `DuplicateRouteName` | `KGN-ROUTING-1007` | Duplicate route name | Give every route declaration a unique name | Build route table | Route names must be unique within one table | `first_route_position:Integer`, `duplicate_route_position:Integer` |
| `ConflictingRoutePattern` | `KGN-ROUTING-1008` | Conflicting route pattern | Remove or change the overlapping method and structural route pattern | Build route table | One method and structural pattern may identify at most one route | `first_route_position:Integer`, `conflicting_route_position:Integer` |
| `InvalidRouteMatch` | `KGN-ROUTING-1009` | Invalid route match value | Supply exactly the bounded parameters declared by the matched route | Create route match | Route-match parameters must exactly satisfy the route template | `parameter_count:Integer`, `violation:Text` |
| `InvalidRouteRequest` | `KGN-ROUTING-1010` | Invalid request routing input | Supply a PSR request with a bounded valid method and encoded path | Match request | Request routing input must satisfy the bounded encoded-path contract | `component:Text`, `actual_length:Integer`, `violation:Text` |

Every schema field is required and `DiagnosticDisclosure::Safe`. `Safe` means
structurally eligible under later policy, not automatically public.

The enum declares exactly these public methods:

```text
context
contextSchema
remediation
title
```

### 9.3 Closed text vocabularies

The only package-owned `component` values are:

```text
method
path
```

The only package-owned `violation` values are:

```text
empty
too_long
syntax
not_list
too_many
not_string
not_canonical
duplicate
missing_leading_slash
trailing_slash
empty_segment
too_many_segments
segment_too_long
invalid_literal
invalid_parameter
not_route
key_mismatch
invalid_value
invalid_percent_triplet
invalid_path_byte
```

Use them at throw sites exactly as follows:

| Diagnostic | Exact violation mapping |
|---|---|
| `InvalidRouteName` | `empty`, `too_long`, or `syntax` |
| `InvalidRouteMethods` | `not_list`, `empty`, or `too_many` |
| `InvalidRouteMethod` | `not_string`, `empty`, `too_long`, `not_canonical`, `syntax`, or `duplicate` |
| `InvalidRouteTemplate` | `empty`, `too_long`, `missing_leading_slash`, `trailing_slash`, `empty_segment`, `too_many_segments`, `segment_too_long`, `invalid_literal`, `invalid_parameter`, or `invalid_percent_triplet` |
| `InvalidRouteTable` | `not_list`, `too_many`, or `not_route` |
| `InvalidRouteMatch` | `key_mismatch` or `invalid_value` |
| `InvalidRouteRequest` method | `empty`, `too_long`, or `syntax` |
| `InvalidRouteRequest` path | `too_long`, `missing_leading_slash`, `too_many_segments`, `segment_too_long`, `invalid_percent_triplet`, or `invalid_path_byte` |

`DuplicateRouteParameter`, `DuplicateRouteName`, and
`ConflictingRoutePattern` have no `violation` field. For a non-string route
method entry, report `method_length = 0`. Positions are zero-based input-list
positions. Lengths and counts are byte/count values observed at the rejected
boundary. `RouteMatch` reports the input array count before canonicalization.

No route name, method text, template, literal segment, parameter name/value,
request path, URI, query, header, body, filename, server data, class name, file,
line, or trace belongs in diagnostic fields, messages, titles, remediation,
operations, or invariants.

As accepted in Phase 2C.6, native Throwable traces and native stringification
remain protected runtime data outside package-owned diagnostic surfaces. Tests
must not inspect, print, serialize, or assert native trace arguments.

---

## 10. Exact package and test file boundary

### 10.1 New package files

Create exactly these eighteen paths:

```text
packages/routing/README.md
packages/routing/composer.json
packages/routing/src/Diagnostic/RoutingDiagnostic.php
packages/routing/src/Exception/ExceptionInterface.php
packages/routing/src/Exception/InvalidRouteDefinitionException.php
packages/routing/src/Exception/InvalidRouteRequestException.php
packages/routing/src/Definition/RouteDefinition.php
packages/routing/src/Definition/RouteTable.php
packages/routing/src/Match/RouteMatch.php
packages/routing/src/Matcher/RouteMatcherInterface.php
packages/routing/src/Matcher/RouteMatcher.php
packages/routing/src/Internal/RouteSyntax.php
packages/routing/tests/Unit/Diagnostic/RoutingDiagnosticTest.php
packages/routing/tests/Unit/Exception/RoutingExceptionTest.php
packages/routing/tests/Unit/Definition/RouteDefinitionTest.php
packages/routing/tests/Unit/Definition/RouteTableTest.php
packages/routing/tests/Unit/Match/RouteMatchTest.php
packages/routing/tests/Unit/Matcher/RouteMatcherTest.php
```

No fixture file is authorized. Use PHPUnit mocks of PSR interfaces where a
request is required. Do not import a Kongono HTTP concrete class into routing
production source.

### 10.2 New documentation files

Create exactly:

```text
docs/adr/0046-deterministic-route-declaration-and-matching.md
docs/architecture/routing-model.md
```

### 10.3 Existing files authorized for modification

Modify only:

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
docs/architecture/http-message-model.md
docs/architecture/overview.md
docs/architecture/phase-2-roadmap.md
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

The report path may be populated only after every non-report gate passes.

No other path may be created, deleted, renamed, moved, normalized, formatted,
or modified.

---

## 11. Composer, autoload, lock, and tooling expansion

### 11.1 Root Composer changes

Add exactly:

```text
require: kongono/routing => @dev
autoload-dev PSR-4: Kongono\Routing\Tests\ => packages/routing/tests/
validate:packages: composer --working-dir=packages/routing validate --no-check-publish
```

Preserve every policy, plugin, platform, script, and existing requirement.

### 11.2 Lockfile generation

Never edit `composer.lock` manually. After both manifests are correct, run:

```text
composer update kongono/routing --with-dependencies --no-interaction
```

An identical approved-access retry is permitted only for managed network/cache
denial.

The generated lock may contain:

- the root content-hash change caused by `composer.json`;
- the new local `kongono/routing` package record and dependency links; and
- mechanical Composer path-reference refreshes for existing unchanged local
  packages.

No third-party package may be added, removed, upgraded, downgraded, or have its
source/dist/version/requirements changed. Existing local-package manifest
semantics may not change. Report every mechanical reference refresh explicitly.
Any other lock delta is a stop condition.

### 11.3 Tooling registration

Add routing source/tests to:

- PHPUnit suite and source inclusion;
- PHPStan paths;
- PHP CS Fixer finder; and
- root architecture discovery/manifest enforcement.

Final exact inventory:

```text
package directories: 10
package manifests: 10
production PHP files: 80
test PHP files: 104
PHP files covered by PHP CS Fixer: 184
```

Run optimized strict PSR autoload generation. No PSR-4 warning is accepted.

---

## 12. Required production tests

### 12.1 Diagnostic and exception proof

Tests must prove:

- the exact ten-row catalog including every literal, field order, kind,
  disclosure, and required flag;
- unique IDs matching `^KGN-ROUTING-1\d{3}$`;
- exact enum public method inventory;
- exception inheritance, finality, property shape, constructor shape, previous
  chain, and four marker methods;
- pairwise separation from all nine existing package exception markers;
- exact package-owned messages/context; and
- actual invalid raw inputs are absent from every package-owned exception and
  diagnostic surface, without inspecting native traces/stringification.

### 12.2 Route-definition proof

Prove every lower/upper bound and grammar branch for route name, method list,
method entry, template length, segment count/length, literal pchar, percent
triplets, parameters, duplicate parameters, root, and valid complex templates.

Prove method arrays containing referenced entries detach from later caller
mutation and returned arrays cannot mutate stored state. Prove exact readonly
property and public-method inventories.

### 12.3 Route-table proof

Prove empty, 1, 1024, and 1025-route boundaries; PHP-list enforcement;
non-route rejection; input reference detachment; canonical binary name order;
fresh returned arrays; duplicate case-sensitive names; shared-method structural
conflicts; non-conflicting disjoint literals; non-conflicting disjoint methods;
multi-method conflict detection; and input-order-independent results/failures.

### 12.4 Route-match proof

Prove exact route identity, parameter order, encoded values, fresh returned
arrays, input reference detachment, reordered-key canonicalization, and
rejection of missing, extra, invalid-key, non-string, empty, invalid-percent,
invalid-byte, and over-limit parameter values.

### 12.5 Matcher proof

Using PSR interface mocks, prove:

- empty path is root;
- root/static/parameter matches;
- exact case-sensitive method behavior;
- no automatic HEAD/OPTIONS behavior;
- query/request-target/body/headers/attributes are not consulted;
- literal-before-parameter precedence at each position;
- the two examples in Section 7.2;
- exact encoded capture with no percent decoding/case folding;
- query exclusion through `UriInterface::getPath()`;
- every unmatched category returns `null`;
- invalid request method/path limits and syntax use the exact routing request
  exception and bounded context;
- request/URI getter Throwables propagate by identity unchanged;
- equivalent route sets in reversed declaration order produce equal selected
  route names and parameter arrays;
- the same matcher handles multiple alternating requests without state leak;
- every successful call returns a fresh `RouteMatch`; and
- no request or match is retained in matcher properties after completion.

### 12.6 Architecture enforcement

Update `ApiStatusConventionTest.php` to prove:

- exactly 80 production declarations and ten package manifests/source roots;
- exact statuses for all ten routing types;
- total unique diagnostic IDs increase from 96 to 106;
- routing marker separation;
- exact routing exception/property contracts;
- exact routing and root manifest requirements;
- routing test PSR-4 and validate-script registration;
- exact dependency direction;
- no forbidden Kongono package imports in routing production source;
- no PSR-15, third-party framework/router, SAPI-global, I/O, ambient registry,
  or handler invocation surface; and
- all prior architecture assertions remain intact.

Do not weaken expectations, derive expected literals from production accessors,
skip/risk/incomplete tests, add output suppression, use source parsing instead
of behavior where behavior is required, or add production-only test branches.

---

## 13. Documentation and ADR requirements

### 13.1 ADR 0046

Create:

```text
Title: Deterministic Route Declaration and Matching
Status: Accepted
Date: 2026-08-16
```

It must record:

1. package ownership and exact dependencies;
2. PSR-7 input boundary without concrete HTTP-package dependency;
3. exact name/method/template/request grammar and limits;
4. encoded byte-exact matching and lack of decoding/normalization;
5. specificity and structural conflict rules;
6. canonical table order and declaration-order independence;
7. ordinary `null` unmatched result;
8. absence of handler/middleware/controller/Graph/DI mapping;
9. persistent-runtime purity and retained-state prohibition;
10. exception/diagnostic ownership and disclosure boundary;
11. performance/resource consequences;
12. rejected alternatives; and
13. exact items still deferred to 2E-2H.

Rejected alternatives must include declaration-order precedence, regex/callable
routes, implicit normalization/decoding, ambient global router state, hidden
container dispatch, Symfony/Laravel/third-party router substrate, handler
invocation in 2D, and routing inside Kernel or HTTP Message.

### 13.2 Routing model guide

`docs/architecture/routing-model.md` must explain purpose, package boundary,
grammar, examples, precedence, conflicts, unmatched behavior, encoded values,
trust boundary, diagnostics, persistent-runtime lifecycle, concurrency,
performance, extension/API status, prohibited use, current versus planned
behavior, and related ADR/roadmap links.

Include at least these inspectable examples:

```text
GET /
GET /users/new
GET /users/{id}
POST /users/{id}/notes
```

Make clear that a route match selects a declaration and untrusted parameters,
not a handler execution or authorization decision.

### 13.3 Reconciliation

Reconcile every authorized existing document so it says:

- Phase 2D and the tenth package are implemented;
- the route package is separate from HTTP Message and Kernel;
- routing is deterministic, encoded, bounded, and currently method/path only;
- route matches prove neither authentication nor authorization;
- no handler, middleware, bootstrap, adapter, emission, generic production
  failure path, or runnable app exists;
- Phase 2E is next; and
- Phase 2H remains the first runnable black-box milestone.

Amend ADR 0043 and ADR 0044 rather than rewriting their historical decisions.
Their open sections must no longer claim that Phase 2D route grammar,
precedence, parameters, conflicts, result, or package decisions remain open.
Handler/middleware/adapter/composition decisions remain open.

Update the ADR index with ADR 0046. Preserve every valid local link and add no
broken link.

---

## 14. Validation sequence

Run in this order and record exact results:

1. authenticated authority/baseline/caller/file-safety checks;
2. pre-change `composer quality`;
3. create manifests/source/tests/docs only within the authorized boundary;
4. run `php -l` on every new/modified PHP file;
5. run the exact Composer update from Section 11.2;
6. `composer dump-autoload --optimize --strict-psr`;
7. focused routing PHPUnit;
8. root architecture PHPUnit;
9. focused routing PHPStan under existing configuration;
10. dry-run PHP CS Fixer;
11. `composer quality`;
12. direct dependency/source/public-API/diagnostic/vocabulary/route-grammar and
    prohibited-scope inspection;
13. documentation current-versus-planned, ADR status/open-item, UTF-8/LF,
    whitespace, link, and spelling review;
14. final Git/history/ref/mode/stream/reparse/inventory checks;
15. confirm the report is still zero-byte;
16. populate only the Phase 2D report;
17. validate report structure, UTF-8/LF, one final LF, no trailing whitespace,
    no NUL, size, and SHA-256; and
18. return the final report byte count and SHA-256 outside its body.

Final expected status with `-uall` after implementation and report population:

```text
tracked modifications: 18
untracked paths: 85
total status paths: 103
staged paths: 0
```

The twenty added implementation/documentation paths account for the increase
from the 83-path post-caller baseline. Modifying the four already-untracked
Phase 2 documents does not increase path count.

No warning beyond accepted Composer missing-license/unbounded-local-constraint
warnings and the managed inaccessible global-ignore warning may be silently
accepted. Report every warning. No PSR-4, PHP, PHPUnit, PHPStan, style,
architecture, security-advisory, schema, file-safety, or link warning is
accepted.

---

## 15. Strict acceptance criteria

Phase 2D is complete only if every item passes:

1. every exact authority was read completely;
2. independent directive bytes and SHA-256 matched;
3. the report began as the exact zero-byte caller placeholder;
4. Git identity and 81-path pre-caller inventory matched;
5. the post-caller state was exactly 18/65/83/0;
6. all accepted caller records and protected preexisting paths remained exact
   unless expressly authorized;
7. baseline quality passed at 329 tests/5912 assertions;
8. exactly one new package and two new documentation files were created;
9. only the 22 authorized existing paths were modified;
10. production/test/package/file inventories are exactly 80/104/10/10;
11. the exact dependency graph and manifest/tooling registration are enforced;
12. no third-party package version or dependency policy changed;
13. all ten production declarations and statuses are exact;
14. all public methods, properties, finality, readonly status, and exception
    shapes are exact;
15. grammar and resource bounds are exact;
16. canonical route-table order is input-order independent;
17. conflicts are rejected and specificity never uses declaration order;
18. matching is method/path only, encoded, exact, and non-normalizing;
19. unmatched is `null` with no implicit fallback/redirect behavior;
20. route parameters remain untrusted and raw values never enter package-owned
    diagnostic surfaces;
21. matcher retains no request-specific state and performs no prohibited I/O or
    ambient access;
22. focused, architecture, static-analysis, strict-autoload, style, security,
    and canonical quality gates pass;
23. ADR 0046 is Accepted and documentation reconciles present versus future;
24. no handler, PSR-15 pipeline, middleware, bootstrap, adapter, emitter,
    response delivery, SAPI parsing, or Phase 2E+ capability exists;
25. no file outside the authorized boundary changed;
26. final state is exactly 18 modified/85 untracked/103 total/0 staged;
27. HEAD/tree/history/branches/remotes/tags and all non-exempt refs are
    unchanged;
28. no stage, commit, reset, restore, clean, stash, branch, tag, fetch, merge,
    rebase, push, or ref manipulation occurred;
29. the report was populated only after all non-report gates passed;
30. the report has the exact structure and final handoff sentence; and
31. the final report byte count and SHA-256 were returned outside its body.

If any non-report item fails, leave the report zero-byte unless this directive
explicitly requires a blocked report. Report the blocker to the caller outside
the placeholder and stop.

---

## 16. Prohibited scope

Phase 2D must not:

- implement or require PSR-15;
- implement handlers, controllers, actions, middleware, dispatchers, pipelines,
  error middleware, handler maps, handler invocation, or response factories;
- implement application bootstrap/composition, Kernel operation ownership,
  cleanup, cancellation, or scoped lifetimes;
- implement SAPI/runtime adapters, globals parsing, proxy trust, emission,
  delivery evidence, trusted diagnostic delivery, generic production failure
  mapping, or a runnable app;
- add host/scheme/port/query/header/content-negotiation routes;
- add optional segments, wildcards, catch-alls, regex constraints, defaults,
  groups, prefixes, resources, generation, reverse routing, redirects, named
  lookup, route mutation, dynamic registration, caching, compiled files, or
  serialization;
- add authentication, authorization, policies, sessions, CSRF, cookies, ORM,
  database, templates, CLI, frontend, telemetry, agents, extensions, events,
  queues, jobs, cache, mail, storage, or configuration systems;
- integrate routing into Kernel, Application Graph, DI, HTTP Message, Diagnostic
  Handler, or another existing production package;
- change existing diagnostics or API statuses;
- add Stable API;
- add a dependency, plugin, baseline, suppression, ignore, security-policy
  exception, or quality-gate exemption;
- edit `composer.lock` manually;
- modify historical directive/report records;
- stage or commit any path;
- manipulate Codex-managed refs; or
- begin Phase 2E.

---

## 17. Standard executor completion report

Populate exactly:

```text
docs/task reports/Phase_2D.md
```

Use exactly these headings in this order:

```markdown
# PHASE 2D TASK COMPLETION REPORT

## 1. STATUS
## 2. SUMMARY
## 3. BASELINE VERIFIED
## 4. CALLER RECORDS
## 5. FILES CREATED
## 6. FILES MODIFIED
## 7. ARCHITECTURAL DECISIONS
## 8. ROUTING CONTRACT
## 9. DEPENDENCY AND TOOLING CHANGES
## 10. DIAGNOSTIC AND SECURITY REVIEW
## 11. PERSISTENT-RUNTIME REVIEW
## 12. TEST PROOF
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

The report must include:

- every created/modified path and reason;
- exact versions of PHP, Composer, Git, PHPUnit, PHPStan, and PHP CS Fixer;
- exact commands and actual counts/results;
- exact lockfile delta classification including local reference refreshes;
- exact package/source/test/status inventories;
- grammar, precedence, conflict, unmatched, limits, and input-order proof;
- package-owned diagnostic disclosure review;
- persistent-runtime retained-state review;
- prohibited-scope audit;
- every accepted warning;
- PASS/FAIL for all 31 acceptance criteria; and
- verified report bytes/SHA returned outside the report, never guessed inside.

If complete, the final line under `NEXT STEP` must be exactly:

```text
Await architect review before Phase 2E.
```

Do not begin Phase 2E.

---

## 18. Required final outcome

At completion, Kongono has ten first-party packages and one bounded routing
capability: explicit immutable route declarations, a canonical validated table,
deterministic method/path matching, and immutable match values carrying only
the selected declaration and untrusted encoded parameters.

The same route set produces the same result regardless of declaration order.
Specificity is explicit, conflicts fail during table construction, unmatched is
ordinary `null`, and no request-specific state survives a match call.

Kongono is still not a runnable web framework. It has no terminal-handler or
middleware pipeline, no web composition root, no outer adapter, no response
delivery, and no fixture application. Phase 2E is the next bounded decision and
implementation boundary.
