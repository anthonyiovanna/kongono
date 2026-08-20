# ADR 0047: Explicit PSR-15 Middleware and Terminal Routing

Status: Accepted

Date: 2026-08-16

## Context

Phase 2D implemented deterministic route declaration and matching, but a route
match still invoked nothing. Phase 2E needs one explicit handler boundary and
one ordered middleware boundary without adding bootstrap, Kernel lifecycle,
runtime adaptation, response delivery, or generic production failure output.

The official [PSR-15 specification](https://www.php-fig.org/psr/psr-15/)
defines only interoperable request-handler and middleware signatures over
PSR-7 messages. The accepted packages `psr/http-server-handler:^1.0` and
`psr/http-server-middleware:^1.0` supply interfaces, not a dispatcher, router,
message implementation, adapter, emitter, server, or framework substrate.

## Decision

### Package ownership and dependencies

The eleventh package is `kongono/http-server` at `packages/http-server`, with
production namespace `Kongono\Http\Server\`. Its production dependencies are
exactly API Metadata, Diagnostics, Routing, PHP 8.5, PSR-7, and the two PSR-15
interface packages. It does not depend on Kongono HTTP Message concrete types,
Kernel, Graph, DI, Diagnostic Activation, Diagnostic Presentation, Diagnostic
Handler, an adapter, a container, or another framework. No existing package
depends on HTTP Server.

### Immutable middleware decoration

`MiddlewarePipeline` receives one explicit terminal
`RequestHandlerInterface` and zero through 128 declaration-ordered
`MiddlewareInterface` objects. Construction detaches the list and compiles an
immutable decorator chain once from the final middleware backward. Request
flow follows declaration order; response unwinding follows reverse order.

The 128-component bound fails before layer construction with
`KGN-HTTP-SERVER-1001`. Handling allocates no cursor, queue, stack, iterator,
or replacement layer chain. Middleware may transform requests or responses,
short-circuit, or deliberately call its next handler more than once. Kongono
does not police exactly-once delegation.

There is no priority, tag, discovery, registration order, container lookup, or
first-installed-wins behavior. The explicitly supplied terminal handler is the
only terminal dependency.

### Complete terminal route map

`RoutingRequestHandler` receives one exact `RouteTable`, one explicit unmatched
handler, and a complete route-name-to-handler map. Keys must equal the entire
bounded case-sensitive route-name set with no extras. Key-set validation occurs
before value inspection. Values are then validated and copied in canonical
route-name order, making storage and failures independent of caller array order.

For a successful match, the selected handler receives a derived request with
the fresh `RouteMatch` under the reserved key `RouteMatch::class`. A prior
value at that key is deliberately overwritten without inspection so untrusted
caller input cannot impersonate framework routing output. The match and its
encoded parameters remain untrusted and prove neither authentication nor
authorization.

For an ordinary unmatched request, the exact original request is delegated to
the explicit unmatched handler without a match attribute. HTTP Server chooses
no 404 status, header, body, redirect, method-not-allowed result, or production
failure representation.

### Failure and runtime behavior

Middleware, terminal, matcher, PSR request, attribute, selected-handler,
unmatched-handler, and response-object `Throwable` values propagate unchanged
by identity. The package catches, wraps, logs, maps, retries, renders, and
converts none of them. Although PSR-15 recommends an outer exception-converting
middleware, Kongono supplies no error middleware in Phase 2E.

Pipelines and routing handlers may be long-lived. They retain only immutable
declarations, compiled layers or matcher data, and shallow references to
caller-owned middleware and handlers. Consumer objects may be stateful and are
not thereby made immutable, reentrant, or concurrency-safe. No current request,
response, match, parameter, exception, principal, tenant, permission,
middleware cursor, or operation is stored in ambient or cross-call state.

Pipeline construction is `O(n)` for at most 128 layers, and delegated handling
uses one nested call per layer. Routing keeps Phase 2D bounds. No latency,
throughput, tail-call, or constant-time promise is made.

## Consequences

Kongono now has an explicit transport-free PSR-15 orchestration boundary that
works with any compatible PSR objects. Application composition can later select
concrete middleware, handlers, response factories, and unmatched behavior
without hidden discovery.

Phase 2F and ADR 0048 now implement bootstrap, composition, and application-call
Kernel scope cleanup without changing HTTP Server. Phase 2G still owns outer runtime adaptation, delivery, generic
non-trusted failure output, and fail-closed trusted-diagnostic integration.
Phase 2H remains the first runnable fixture and black-box proof.

## Rejected alternatives

- Symfony, Laravel, or a third-party middleware dispatcher as framework
  substrate, because Phase 2E requires a small Kongono-owned explicit boundary.
- Callable, controller, class-string, reflection, attribute, or service-key
  dispatch magic, because handler identity must be explicit and typed.
- A mutable queue or per-request cursor, because long-lived and reentrant use
  must not share request-progress state.
- Ambient current request, response, match, handler, or middleware state,
  because it can leak between persistent-runtime operations.
- Container lookup, priority sorting, tags, discovery, and first-installed-wins
  behavior, because composition order must remain inspectable.
- Embedding handler or middleware metadata into Phase 2D route declarations,
  because declaration/matching and execution ownership remain separate.
- A hard-coded unmatched or error response, because response and failure
  delivery belong to later composition and adapter authority.

## Related documentation

- [HTTP Server model](../architecture/http-server-model.md)
- [ADR 0048](0048-web-application-bootstrap-and-kernel-operation-ownership.md)
- [Web Application model](../architecture/web-application-model.md)
- [Routing model](../architecture/routing-model.md)
- [HTTP message model](../architecture/http-message-model.md)
- [Runtime model](../architecture/runtime-model.md)
- [Phase 2 roadmap](../architecture/phase-2-roadmap.md)
- [ADR 0043](0043-phase-2-runnable-web-framework-milestone.md)
- [ADR 0044](0044-http-message-and-interoperability-architecture.md)
- [ADR 0046](0046-deterministic-route-declaration-and-matching.md)
