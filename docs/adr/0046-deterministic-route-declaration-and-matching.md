# ADR 0046: Deterministic Route Declaration and Matching

- **Status:** Accepted
- **Date:** 2026-08-16

## Context

Phase 2C established Kongono-owned PSR-7 server-message values, but a runnable web path also needs a deterministic way to select an application declaration from the explicit request method and URI path. That selection must remain independent from message construction, Kernel lifecycle, dependency resolution, middleware execution, authorization, and transport delivery.

Long-lived PHP runtimes make ambient router state unsafe. Route selection must therefore be a pure, bounded operation over immutable declarations and one explicitly supplied `ServerRequestInterface`. Consumer-supplied PSR objects are an interoperability boundary; routing cannot require Kongono's concrete HTTP-message package.

## Decision

### Package and dependency boundary

The tenth package is `kongono/routing`, namespace `Kongono\Routing\`. Its production dependencies are exactly `kongono/api-metadata:*`, `kongono/diagnostics:*`, `php:^8.5`, and `psr/http-message:^2.0`. It does not depend on `kongono/http-message`, Kernel, Application Graph, Dependency Injection, PSR-15, or a third-party router.

The package owns immutable `RouteDefinition`, `RouteTable`, and `RouteMatch` values, an immutable stateless `RouteMatcher`, an Experimental matcher interface, package-local routing failures, and Internal byte-grammar/diagnostic machinery.

### Exact declaration grammar

Route names are 1–128 ASCII bytes matching `[A-Za-z][A-Za-z0-9._-]*`. A declaration contains a PHP list of 1–16 unique, already-uppercase HTTP-token methods, each 1–32 bytes. Methods are stored in binary `strcmp` order.

A template is `/` or a leading-slash path of at most 2,048 bytes and 32 non-root segments. It has no empty segment or trailing slash. Each segment is 1–128 bytes and is either a literal RFC 3986 `pchar` sequence, including valid `%HH`, or one whole-segment parameter `{name}`. Parameter names are unique, 1–64 bytes, and match `[A-Za-z_][A-Za-z0-9_]*`.

Request methods are valid case-sensitive HTTP tokens of 1–32 bytes. An empty URI path means `/`; otherwise the path begins with `/`, is at most 8,192 bytes, has at most 64 segments, and each segment is at most 1,024 valid encoded `pchar` bytes. Empty request segments are syntactically valid but cannot match a route parameter or literal template segment.

### Matching, precedence, and conflicts

Matching compares the exact request method and exact encoded path-segment bytes. It performs no percent decoding, Unicode normalization, case folding, dot-segment normalization, or slash collapsing. Query, fragment, and request target are not consulted.

Among matching candidates, each template has a left-to-right specificity vector: literal `1`, parameter `0`. The lexicographically greatest vector wins. Thus `/users/new` outranks `/users/{id}`, and `/a/{value}` outranks `/{value}/a` for `/a/a`. Declaration order never selects a result.

A table rejects two routes when a method and structural pattern conflict. Structural patterns retain exact literal bytes and replace each parameter with `{}`, so parameter names do not distinguish conflicts. Route names are unique, and accepted tables are stored in binary route-name order.

A valid request with no candidate returns `null`. A match contains the selected declaration and exact encoded parameter values; it does not execute a handler and proves neither authentication nor authorization.

### Lifecycle and side effects

The matcher calls only `getMethod()`, `getUri()`, and `getPath()`, in that order, and propagates getter failures unchanged. It performs no I/O, clock, random, environment, SAPI-global, session, logging, telemetry, event, Graph, DI, or Kernel work. Each call returns a fresh match or `null`; no request, URI, method, path, parameters, last match, principal, tenant, or other request-specific value is retained.

Route tables contain at most 1,024 routes. Construction may validate pairwise structural ownership through bounded indexes and precompile immutable match data. A match scan is bounded by the route count, method count, segment count, and byte limits. The deliberate simple scan keeps precedence inspectable; later optimization must preserve identical results and resource bounds.

### Failure and disclosure boundary

`kongono/routing` owns `InvalidRouteDefinitionException` and `InvalidRouteRequestException`, both under its Public exception marker. Ten `KGN-ROUTING-1xxx` diagnostics report only bounded structural counts, positions, components, and closed violation tokens. Raw names, methods, templates, literal bytes, paths, parameter names/values, requests, and URIs never enter package-owned diagnostic surfaces. Captured parameters remain untrusted application input.

## Consequences

- Route selection is deterministic across declaration order and compatible PSR-7 implementations.
- Exact encoded matching avoids hidden normalization and ambiguous ownership.
- Conflicts fail during table construction instead of becoming runtime precedence accidents.
- The package remains reusable without Kongono message concretes or runtime orchestration.
- Matching is intentionally limited to method and encoded path; host, scheme, query, content negotiation, and generation are absent.

## Rejected alternatives

- Declaration-order precedence, because registration order would silently change behavior.
- Regex, wildcard, callable, or inline-constraint routes, because they introduce unbounded and premature semantics.
- Implicit decoding or normalization, because it can collapse distinct security-relevant byte sequences.
- Ambient global, singleton, request-local, fiber-local, or current-route state, because it leaks across persistent operations.
- Hidden container dispatch, because route selection is not service resolution or authorization.
- Symfony, Laravel, or another third-party router substrate, because Phase 2D requires Kongono-owned deterministic semantics without a framework dependency.
- Handler invocation in Phase 2D, because ordered middleware and terminal handling belong to Phase 2E.
- Routing inside Kernel or HTTP Message, because lifecycle orchestration, message values, and route selection have separate ownership.

## Phase 2E current-state amendment

ADR 0047 implements ordered PSR-15 middleware and terminal-handler execution in
the separate eleventh package, `kongono/http-server`. It preserves Phase 2D's
pure routing boundary: a selected match is copied under the reserved
`RouteMatch::class` attribute but remains untrusted and non-authorizing, while
unmatched output is delegated. HTTP Server supplies no error middleware,
bootstrap, Kernel web composition, adapter, emission, or generic production
response.

## Deferred work

- Phase 2F: explicit bootstrap, adapter, and Kernel operation composition.
- Phase 2G: outer HTTP message construction, response delivery, and generic production failure mapping.
- Phase 2H: fixture application, black-box acceptance, documentation reconciliation, and the runnable Phase 2 checkpoint.

## Related decisions

- [ADR 0043](0043-phase-2-runnable-web-framework-milestone.md)
- [ADR 0044](0044-http-message-and-interoperability-architecture.md)
- [ADR 0045](0045-first-party-http-message-and-factory-implementation.md)
- [Routing model](../architecture/routing-model.md)
- [Phase 2 roadmap](../architecture/phase-2-roadmap.md)
- [ADR 0047](0047-explicit-psr-15-middleware-and-terminal-routing.md)
- [HTTP server model](../architecture/http-server-model.md)
