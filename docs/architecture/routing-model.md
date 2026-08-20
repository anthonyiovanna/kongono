# Routing Model

## Purpose and boundary

`kongono/routing` is the implemented Phase 2D boundary for deterministic declaration and selection of a route from an explicit PSR-7 server request. It is the tenth package and depends only on API Metadata, Diagnostics, PHP 8.5, and `psr/http-message`.

Routing is separate from `kongono/http-message` and Kernel. A compatible consumer may supply any `ServerRequestInterface`; the matcher uses only `getMethod()`, `getUri()`, and `getPath()`. A selected route is not a handler call, service lookup, authentication result, authorization decision, middleware pipeline, response, or delivery action.

## Current declarations

Examples of implemented declarations include:

```text
GET  /
GET  /users/new
GET  /users/{id}
POST /users/{id}/notes
```

A route name is a case-sensitive 1–128-byte ASCII identifier matching `[A-Za-z][A-Za-z0-9._-]*`. Declared methods form a non-empty list of at most 16 unique uppercase HTTP tokens, each at most 32 bytes. Stored methods use binary order.

Templates are `/` or leading-slash paths of at most 2,048 bytes and 32 non-root segments. They have no empty segment or trailing slash. A segment is at most 128 bytes and is either a literal RFC 3986 `pchar` sequence or one whole-segment parameter `{name}`. Parameter names are unique within a route and use `[A-Za-z_][A-Za-z0-9_]*`, up to 64 bytes.

No optional segments, wildcards, catch-alls, regular expressions, inline constraints, defaults, host rules, scheme rules, query rules, controller notation, middleware notation, or URL-generation syntax exists.

## Request input and encoded values

Request method comparison is exact and case-sensitive. An empty URI path is treated as `/`. Other paths must start with `/`, are limited to 8,192 bytes and 64 segments, and use segments of at most 1,024 valid encoded `pchar` bytes.

The matcher does not percent-decode or normalize. `/files/a%2Fb`, `/files/a%2fb`, and `/files/a/b` are distinct inputs. Captured parameter values retain the exact encoded segment bytes and remain untrusted application input. Applications must apply their own validation before using a parameter, and route selection itself grants no permission.

Query and fragment data are outside matching because the matcher reads `UriInterface::getPath()`, not the request target or query API.

## Precedence and conflicts

Each segment contributes a specificity bit: literal `1`, parameter `0`. Candidates are compared lexicographically from the first segment. Consequently:

- `/users/new` wins over `/users/{id}` for `/users/new`.
- `/a/{value}` wins over `/{value}/a` for `/a/a`.

Route registration order is irrelevant. Tables are validated before sorting and then stored by binary route-name order. Two declarations conflict when they share a method and have the same structural pattern: the same segment count, parameters in the same positions, and byte-identical literal segments. Parameter names do not distinguish structural ownership.

## Match and unmatched results

A successful call returns a fresh immutable `RouteMatch` holding the exact selected `RouteDefinition` object and parameter map in template order. A syntactically valid request with no candidate returns `null`. There is no automatic `HEAD`, `OPTIONS`, method-not-allowed result, trailing-slash redirect, or fallback dispatch.

## Failure and diagnostics

Definition and table failures use `InvalidRouteDefinitionException`; invalid method/path request inputs use `InvalidRouteRequestException`. The package owns ten Internal diagnostics from `KGN-ROUTING-1001` through `KGN-ROUTING-1010`.

Diagnostic context contains only Safe structural lengths, counts, positions, `method`/`path` component labels, and closed violation tokens. Raw route or request values are excluded. Safe marks structural eligibility under later presentation policy; it does not make captured route parameters safe or public.

## Lifecycle, concurrency, and performance

Definitions, tables, matches, and matchers are immutable. A matcher may be long-lived and reused sequentially or concurrently under ordinary PHP value semantics. It retains only the route table and compiled immutable route data; it never retains a request, URI, path, method, match, parameters, principal, tenant, exception, or operation state.

The operation performs no filesystem, network, stream, environment, session, clock, random, locale, process, SAPI-global, logging, telemetry, event, container, Kernel, or Graph work. Getter failures from consumer PSR objects propagate unchanged.

Tables are limited to 1,024 routes. Candidate scans and comparisons are bounded by declared route, method, segment, and byte limits. This favors inspectability and deterministic behavior; an internal optimization may change later only if observable selection, failures, and bounds stay identical.

## Stability and extension contract

The package exception marker is Public. Route declarations, tables, matches, matcher contract, matcher, and concrete routing exceptions are Experimental. Diagnostic and syntax machinery are Internal. No Stable routing API exists.

Consumers may use the Experimental matcher contract but must not implement or depend on Internal syntax/diagnostic mechanics as durable extension points. A route match may feed later application composition only after explicit authentication, authorization, and handler ownership have been established elsewhere.

## Current versus planned behavior

Phase 2D implements bounded method/path declaration and matching only. Phase 2E implements the separate `kongono/http-server` boundary: an explicit bounded immutable PSR-15 pipeline and complete canonical route-name-to-handler map. A selected `RouteMatch` is placed under the reserved `RouteMatch::class` request attribute but remains untrusted and non-authorizing; unmatched output is delegated. Phase 2F implements the separate `kongono/web-application` validation-before-boot and application-call Kernel scope boundary without changing Routing. Phase 2G implements adapter delivery and generic or eligible trusted failure mapping without a direct Routing dependency. No supplied error middleware, concrete runtime delivery, or runnable application exists; Phase 2H remains the first runnable black-box milestone. See the [Web Application model](web-application-model.md).

## Related material

- [ADR 0046: Deterministic Route Declaration and Matching](../adr/0046-deterministic-route-declaration-and-matching.md)
- [HTTP message model](http-message-model.md)
- [Runtime model](runtime-model.md)
- [Phase 2 roadmap](phase-2-roadmap.md)
- [Error handling](../engineering/error-handling.md)

## Phase 2G relationship

Web Runtime has no direct Routing dependency or import. It passes the exact
converted request to Web Application once; Web Application and HTTP Server retain
their existing routing composition. The adapter adds no route inspection,
authorization, Graph traversal, second Kernel scope, or concrete emitter. The
thirteen-package graph stays acyclic and Phase 2H remains the first runnable
black-box milestone.
