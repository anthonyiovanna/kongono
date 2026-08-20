# HTTP Message Model

## Decision summary

Kongono implements its own bounded server-side HTTP message implementation
against PHP-FIG interoperability interfaces. PSR-7 defines the message
contracts, the selected server-side PSR-17 interfaces define factories, and
PSR-15 defines the Phase 2E handler and middleware edge. These standards
are contracts at the edge, not an implementation, framework substrate, or owner
of Kongono architecture.

This is Accepted and implemented Phase 2C behavior. After Phase 2E, Kongono has
eleven implemented packages. Routing remains a separate package that consumes
the PSR-7 interface boundary without depending on these concrete message
classes, and HTTP Server uses the PSR-15 interfaces without depending on the
HTTP Message package.

## Standards and package versions

| Standard | Interface package | Constraint | Phase |
| --- | --- | --- | --- |
| PSR-7 | `psr/http-message` | `^2.0` | Message implementation in 2C |
| PSR-17 | `psr/http-factory` | `^1.1` | Selected server factories in 2C |
| PSR-15 handler | `psr/http-server-handler` | `^1.0` | Terminal handling in 2E |
| PSR-15 middleware | `psr/http-server-middleware` | `^1.0` | Middleware in 2E |

The packages contain interfaces only. They provide no message implementation,
router, middleware dispatcher, adapter, emitter, or server. Phase 2B installs
none of them and claims no PSR conformance.

Official sources are [PSR-7](https://www.php-fig.org/psr/psr-7/),
[PSR-15](https://www.php-fig.org/psr/psr-15/),
[PSR-17](https://www.php-fig.org/psr/psr-17/), [RFC
3986](https://www.rfc-editor.org/rfc/rfc3986.html), [RFC
9110](https://www.rfc-editor.org/rfc/rfc9110.html), and [RFC
9112](https://www.rfc-editor.org/rfc/rfc9112.html).

## Implemented first-party roles

| External interface | Kongono role |
| --- | --- |
| `Psr\Http\Message\ServerRequestInterface` | Inbound server-request value |
| `Psr\Http\Message\ResponseInterface` | Outbound response value |
| `Psr\Http\Message\UriInterface` | URI value |
| `Psr\Http\Message\StreamInterface` | Resource-backed body stream |
| `Psr\Http\Message\UploadedFileInterface` | One uploaded-file value |
| `Psr\Http\Message\ServerRequestFactoryInterface` | Explicit server-request factory |
| `Psr\Http\Message\ResponseFactoryInterface` | Explicit response factory |
| `Psr\Http\Message\UriFactoryInterface` | Explicit URI factory |
| `Psr\Http\Message\StreamFactoryInterface` | Explicit stream factory |
| `Psr\Http\Message\UploadedFileFactoryInterface` | Explicit uploaded-file factory |

`ServerRequestInterface` already includes the inherited request and message
contracts. Phase 2 needs no separate client-request object.
`RequestFactoryInterface`, PSR-18, and general client-side HTTP are outside the
milestone.

Phase 2C selects the concrete classes, constructors, defaults, exceptions, and
PHP signatures recorded in [ADR 0045](../adr/0045-first-party-http-message-and-factory-implementation.md).

## Package and dependency graph

The exact implemented package identity is:

```text
packages/http-message
kongono/http-message
Kongono\Http\Message\
Kongono\Http\Message\Tests\
```

Its permitted dependency graph is:

```text
api-metadata ----+
diagnostics -----+
psr/http-message +--> kongono/http-message --> consumed at the root
psr/http-factory +
php 8.5 ---------+
```

The exact future constraints are:

```text
php: ^8.5
kongono/api-metadata: *
kongono/diagnostics: *
psr/http-message: ^2.0
psr/http-factory: ^1.1
```

There is no reverse dependency from API Metadata or Diagnostics. The package
does not depend on Kernel, Dependency Injection, Application Graph, Diagnostic
Activation, Diagnostic Presentation, Diagnostic Handler, PSR-15, routing,
middleware, adapters, runtimes, or another PSR-7 implementation. The Kernel
remains transport-neutral and PSR-free.

## Copy-on-change messages and stateful streams

PSR-7 `with*()` methods retain the original message state and return a value
representing a change. This is sometimes described as message immutability, but
it is not deep immutability.

`StreamInterface` is stateful. Its cursor, resource contents, detach state, and
close state can change. Two message values may share the same body stream, so a
change observable through that stream can affect both views. Phase 2C tests and
documentation must make this shallow boundary visible.

Message existence proves no authentication, authorization, trust, route match,
or delivery. Message and stream values remain explicit call-flow data and are
never retained as ambient current-request state.

## Header rules

- Lookup is case-insensitive, while the originally supplied field-name case is
  preserved for retrieval.
- Repeated values and their order are preserved.
- Names and values are validated before storage.
- CR, LF, NUL, invalid field-name characters, and ambiguous line folding are
  rejected. They are not repaired or silently normalized.
- `Host` and URI-host interaction follows the complete PSR-7 `withUri()`
  contract and requires its full Phase 2C decision-table tests.
- Syntax does not establish trust. Header names and values do not become
  diagnostic Safe data.

## URI rules

URI behavior follows PSR-7 and RFC 3986. Scheme and host canonicalization,
authority composition, percent encoding, path, query, fragment, user
information, ports, and string serialization require exact tests. Reserved
delimiters are not decoded into a new semantic meaning.

The URI value performs no DNS, filesystem, network, base-URI guessing, or
scheme-specific resolution. A syntactically valid URI remains untrusted.

## Streams and uploaded files

A stream exposes its readability, writability, seekability, cursor, size,
metadata, detach state, and close behavior. First-party helpers and tests must
bound every read they initiate. The message package does not parse an unbounded
request body.

The stream factory may perform explicit caller-requested local file I/O. That
is not adapter input parsing or network access, and paths or local file contents
must not enter diagnostic context.

Uploaded-file movement is explicit and one-way. Invalid targets, a second move,
stream failures, and runtime failures fail loudly under the PSR contract. An
uploaded-file value does not decide upload policy, authorization, storage
ownership, or Kernel lifecycle.

## Server-request data domains

Keep these domains distinct:

```text
server parameters
cookies
query parameters
uploaded files
parsed body
attributes
```

The message package stores representations supplied by a caller. It does not
read SAPI globals, parse body content, normalize uploads, choose limits, resolve
duplicates, trust proxies, authenticate, or authorize. Attributes carry
explicit per-request call-flow values; they are not ambient context or
permission upgrades. Mutable consumer values stored in attributes or parsed
bodies remain consumer-owned and are not deeply immutable.

SAPI conversion, input parsing, limits, and rejection behavior belong to Phase
2G after separate decisions.

## Factories are explicit dependencies

Factories enter composition explicitly. There is no discovery package, global
registry, static factory, service locator, first-installed-wins rule, or
ambient lookup. Response-producing collaborators receive the selected response
factory or prototype directly.

This keeps implementation selection inspectable and prevents a dependency's
installation order from changing runtime behavior.

## Implemented PSR-15 flow

Phase 2E uses:

```text
Psr\Http\Server\RequestHandlerInterface
Psr\Http\Server\MiddlewareInterface
```

The implemented transport-free flow is:

```text
explicit ServerRequestInterface
    -> ordered MiddlewareInterface pipeline
        -> terminal RequestHandlerInterface
            -> explicit ResponseInterface
```

Middleware that produces a response receives an explicit response factory or
prototype. `kongono/http-server` owns the immutable middleware pipeline and
complete route-name terminal mapping. Selected matches use the reserved
`RouteMatch::class` attribute but remain untrusted and non-authorizing;
unmatched output is delegated. No error middleware, bootstrap, adapter,
emitter, or runnable runtime is supplied.

## Trust and diagnostics

Every request component remains untrusted until validated for a specific use.
A message value does not prove delivery provenance, proxy trust, identity,
authentication, authorization, or trusted-diagnostic eligibility.

Future package-local failures must follow Kongono's actionable-diagnostic
doctrine and PSR-required invalid-argument or runtime categories where
applicable. Diagnostic context must never contain raw headers, cookies, query
strings, bodies, credentials, tokens, uploaded content, local file contents, or
unbounded URI text. Safe context is limited to bounded structural facts
authorized by the later Phase 2C schema. Phase 2B allocates no diagnostic IDs or
exception class names.

## Persistent-runtime ownership

One future outer adapter owns each inbound delivery and one Kernel operation
scope. Messages, streams, attributes, routes, permissions, and failures travel
through explicit parameters and returns. They do not enter global, static,
singleton, container-current, thread-local, fiber-local, or previous-operation
storage.

Factories may be long-lived only as stateless explicit composition dependencies;
they are not ambient authority. Successful and failing adapter paths eventually
close the owned operation scope exactly once. Cleanup APIs, simultaneous
failures, response emission, cancellation, recovery, and uncertain-runtime
retirement remain later decisions.

## Current versus planned behavior

Current after Phase 2E:

- eleven first-party packages;
- implemented `packages/http-message` and its package manifest;
- selected PSR-7 and PSR-17 Composer dependencies;
- five HTTP message roles and five stateless factory implementations;
- an independently owned routing package that consumes PSR-7 interfaces but no
  Kongono HTTP concrete class;
- installed and used PSR-15 handler and middleware interfaces in the explicit,
  bounded, immutable, transport-free `kongono/http-server` pipeline and routing
  handler;
- explicit Web Application composition and application-call Kernel scope
  cleanup without a supplied error middleware, adapter, emitter, or runnable
  web application; and
- no claim of PSR compliance.

Implemented by Phase 2C: a bounded first-party implementation of the exact five
PSR-7 roles and five PSR-17 factory roles above, with tests and 28 explicit
diagnostic identifiers.

## Explicit deferrals

Concrete message class names, constructors, defaults, exception inventory and
IDs, validation algorithms, and fixtures were implemented by Phase 2C. Phase 2D
now implements bounded deterministic route grammar and matching in the separate
`kongono/routing` package. Phase 2E implements middleware dispatch and terminal
mapping in `kongono/http-server`. Phase 2F implements bootstrap and application-call Kernel operation ownership in `kongono/web-application`.
Adapter conversion, parsing limits, proxy trust, response emission, production
failure delivery, adapter-owned transport cleanup, and broader resource cleanup belong to Phase 2G or later. Final
black-box proof and compatibility promotion belong to Phase 2H or later
explicit authority.

## Related documentation

- [ADR 0044](../adr/0044-http-message-and-interoperability-architecture.md)
- [ADR 0046](../adr/0046-deterministic-route-declaration-and-matching.md)
- [ADR 0047](../adr/0047-explicit-psr-15-middleware-and-terminal-routing.md)
- [HTTP server model](http-server-model.md)
- [ADR 0048](../adr/0048-web-application-bootstrap-and-kernel-operation-ownership.md)
- [Web Application model](web-application-model.md)
- [Routing model](routing-model.md)
- [ADR 0043](../adr/0043-phase-2-runnable-web-framework-milestone.md)
- [Phase 2 roadmap](phase-2-roadmap.md)
- [Runtime model](runtime-model.md)
- [Architecture overview](overview.md)
- [Security policy](../../SECURITY.md)
- [Package layout](../engineering/package-layout.md)
- [Architectural doctrine](../doctrine.md)
- [API status](../engineering/api-status.md)
- [Error handling](../engineering/error-handling.md)

## Phase 2G consumer

The new top-level Web Runtime leaf consumes first-party `Response` and
`StreamFactory` to build fresh fixed failure responses, and its Public delivery
port exchanges PSR-7 requests and responses. HTTP Message remains transport-
neutral and has no reverse dependency on Web Runtime. Protocol framing,
`Content-Length`, concrete emission, and runnable integration remain outside
Phase 2G; Phase 2H is next.
