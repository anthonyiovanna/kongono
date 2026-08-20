# HTTP Server Model

## Purpose and current boundary

`kongono/http-server` is the implemented Phase 2E boundary for explicit PSR-15
middleware and terminal route-to-handler orchestration. It is the eleventh
first-party package. It consumes PSR interfaces and the separate Routing
package; it does not depend on Kongono HTTP Message concrete classes.

The package performs no bootstrap, Kernel operation management, SAPI or server
conversion, response emission, generic production failure rendering,
authentication, authorization, container lookup, or I/O. It is transport-free
and does not make Kongono runnable.

Phase 2F composes this unchanged lower layer from the separate
`kongono/web-application` package. Web Application validates the routing handler
and pipeline before Kernel boot and owns the Kernel scope around each pipeline
call; HTTP Server itself still owns no lifecycle or cleanup behavior.

## Middleware composition

An empty pipeline is valid:

```php
$pipeline = new MiddlewarePipeline($terminalHandler);
$response = $pipeline->handle($request);
```

An ordered pipeline is explicit at construction:

```php
$pipeline = new MiddlewarePipeline(
    $terminalHandler,
    $outerMiddleware,
    $innerMiddleware,
);
```

Its ordinary call sequence is:

```text
request -> outer enter -> inner enter -> terminal
response <- outer leave <- inner leave <- terminal
```

Construction accepts at most 128 middleware components and compiles the
immutable layer chain once. No request cursor or mutable queue is created.
Middleware may pass the same request, pass a derived request, return a derived
response, short-circuit without delegation, or deliberately call its next
handler more than once.

A short circuit is ordinary PSR-15 behavior:

```php
$deny = new class ($response) implements MiddlewareInterface {
    public function __construct(private ResponseInterface $response) {}

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        return $this->response;
    }
};
```

The later middleware and terminal handler are not reached. A response-producing
middleware receives its response or factory explicitly through its own
composition; HTTP Server performs no ambient factory discovery.

## Terminal route mapping

Routing and handler execution remain separately owned:

```php
$routes = new RouteTable([
    new RouteDefinition('home', ['GET'], '/'),
    new RouteDefinition('user.show', ['GET'], '/users/{id}'),
]);

$terminal = new RoutingRequestHandler(
    $routes,
    [
        'user.show' => $showUserHandler,
        'home' => $homeHandler,
    ],
    $unmatchedHandler,
);
```

The map must contain exactly one `RequestHandlerInterface` for every route and
no other key. Caller order does not matter; valid entries are stored in the
route table's canonical route-name order.

For a successful match, the selected handler reads the fresh match from the
derived request:

```php
$match = $request->getAttribute(RouteMatch::class);

if (!$match instanceof RouteMatch) {
    throw new LogicException('Composition did not supply a route match.');
}

$encodedId = $match->parameters()['id'];
```

The reserved key overwrites any caller-supplied value without reading it. The
`RouteMatch` and `a%2Fb`-style encoded parameter bytes remain untrusted input.
They establish no identity, authentication, authorization, permission,
diagnostic eligibility, or handler safety.

When no route matches, the exact original request goes to the explicit
unmatched handler. HTTP Server adds no attribute and chooses no status, header,
body, redirect, or method-not-allowed response.

## Failure behavior

Composition-definition failures use the package-local HTTP Server marker and
three bounded diagnostics. They contain only counts, canonical positions, and
the closed `not_request_handler` token. Raw route names, request data, handler
classes, callables, objects, response data, and exception messages are absent.

Every consumer or PSR `Throwable` during request handling propagates unchanged.
The pipeline and routing handler do not catch, wrap, log, retry, map, render, or
convert failures. No production error middleware or generic response is
supplied.

## Long-lived reuse

The pipeline retains a detached declaration-order list, its exact terminal
handler, and an immutable compiled entry chain. The routing handler retains the
route table, one compiled matcher, a detached canonical handler map, and its
unmatched handler. Requests, responses, matches, parameters, failures,
principals, tenants, permissions, cursors, and operations remain stack-local.

Caller-owned middleware and handlers are shallow object references. Kongono
does not claim those objects are immutable, reentrant, or concurrency-safe. An
application must review stateful middleware for its runtime model.

## Package graph and absent capabilities

```text
api-metadata -----+
diagnostics ------+
routing ----------+
psr/http-message -+--> kongono/http-server --> framework root
PSR-15 interfaces +
```

There is no reverse package edge. HTTP Server has no production dependency on
HTTP Message concrete classes, Kernel, Graph, DI, Diagnostic Activation,
Diagnostic Presentation, or Diagnostic Handler.

Current HTTP Server behavior ends at explicit `ResponseInterface` return values.
Phase 2F now implements bootstrap/composition and Kernel operation ownership in
the separate Web Application package. Phase 2G
plans the first outer adapter, response delivery, and separate failure paths.
Phase 2H plans the fixture application and first runnable black-box proof.
None of those later capabilities is implemented here.

## Stability and related material

The package marker is Public. Middleware Pipeline, Routing Request Handler, and
the concrete definition exception are Experimental. Internal layer and catalog
types are unsupported implementation details. No Stable HTTP Server API exists.

- [ADR 0047](../adr/0047-explicit-psr-15-middleware-and-terminal-routing.md)
- [ADR 0048](../adr/0048-web-application-bootstrap-and-kernel-operation-ownership.md)
- [Web Application model](web-application-model.md)
- [Routing model](routing-model.md)
- [HTTP message model](http-message-model.md)
- [Runtime model](runtime-model.md)
- [Phase 2 roadmap](phase-2-roadmap.md)
- [Error handling](../engineering/error-handling.md)

## Phase 2H runnable-fixture relationship

Web Runtime does not depend on or call HTTP Server directly. It invokes Web
Application once, leaving PSR-15 middleware and routing ownership unchanged.
The Phase 2 fixture uses the real pipeline and routing handler to prove exact
outer/inner middleware order, attached route matches, and deterministic unmatched
handling. Web Application retains the sole Kernel scope.

The fixture's fixed responses are acceptance behavior, not a general emitter
contract. No production SAPI listener, server, parser, or emitter is supplied.
See the [runnable fixture model](phase-2-runnable-fixture.md).
