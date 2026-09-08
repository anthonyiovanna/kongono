# HTTP Message Model

## Decision summary

Kongono implements its own bounded server-side HTTP message implementation
against PHP-FIG interoperability interfaces. PSR-7 defines the message
contracts, the selected server-side PSR-17 interfaces define factories, and
PSR-15 defines the Phase 2E handler and middleware edge. These standards
are contracts at the edge, not an implementation, framework substrate, or owner
of Kongono architecture.

This is Accepted and implemented Phase 2C behavior. The repository has exactly
fourteen production packages and a root-owned, test-owned runnable
fixture. Routing remains a separate package that consumes
the PSR-7 interface boundary without depending on these concrete message
classes, and HTTP Server uses the PSR-15 interfaces without depending on the
HTTP Message package. The fixture is not a production SAPI adapter, parser,
emitter, server, listener, application, or deployment profile.

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

The exact implemented constraints are:

```text
php: >=8.5.0 <8.6.0
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

Phase 3B selects those outer decisions for the PHP `cgi-fcgi` profile without
changing this package. Phase 3C and ADR 0053 implement `kongono/php-cgi`, which
projects only a closed caller-supplied host snapshot and caller-owned bounded
input into these existing values. Neither package reads ambient host globals,
and the message package has no reverse dependency on the outer leaf.

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
unmatched output is delegated. HTTP Server itself supplies no error middleware,
bootstrap, adapter, emitter, or runnable runtime. Web Runtime, Web Application,
and the root-owned Phase 2 fixture compose those separate responsibilities
without making them HTTP Message package behavior.

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

Web Runtime owns the neutral per-delivery coordination and cleanup contract
through `WebDeliveryInterface`: request acquisition, delivery-permission
evaluation when bootstrap permits it, one Web Application call, fixed generic
or eligible trusted response selection, one send attempt, and one close
attempt. Web Application alone owns the Kernel operation scope around the
accepted application call. Messages, streams, attributes, routes, permissions,
and failures travel through explicit parameters and returns. They do not enter
global, static, singleton, container-current, thread-local, fiber-local, or
previous-operation storage.

Factories may be long-lived only as stateless explicit composition dependencies;
they are not ambient authority. PHP CGI owns bounded explicit inbound conversion
and fully staged bounded response emission. It does not own ambient capture or a
concrete production delivery. Web Runtime closes the delivery exactly once, while Web
Application closes its owned Kernel scope exactly once. Broader cleanup APIs,
cancellation, recovery, and production emission remain later implementation
work.

## Current versus planned behavior

Current after Phase 2H:

- exactly fourteen first-party production packages plus a root-owned test fixture;
- implemented `packages/http-message` and its package manifest;
- selected PSR-7 and PSR-17 Composer dependencies;
- five HTTP message roles and five stateless factory implementations;
- an independently owned routing package that consumes PSR-7 interfaces but no
  Kongono HTTP concrete class;
- installed and used PSR-15 handler and middleware interfaces in the explicit,
  bounded, immutable, transport-free `kongono/http-server` pipeline and routing
  handler;
- explicit Web Application composition and application-call Kernel scope
  cleanup, Web Runtime delivery/failure orchestration, and a test-owned runnable
  fixture without a production SAPI, parser, emitter, or deployment profile; and
- an Accepted PHP `cgi-fcgi` CGI/1.1 FastCGI Responder architecture and an
  implemented `kongono/php-cgi` bounded explicit inbound converter and bounded
  response emitter, without a concrete delivery; and
- no claim of PSR compliance.

Implemented by Phase 2C: a bounded first-party implementation of the exact five
PSR-7 roles and five PSR-17 factory roles above, with tests and 28 explicit
diagnostic identifiers.

## Explicit deferrals

Concrete message class names, constructors, defaults, exception inventory and
IDs, validation algorithms, and fixtures were implemented by Phase 2C. Phase 2D
now implements bounded deterministic route grammar and matching in the separate
`kongono/routing` package. Phase 2E implements middleware dispatch and terminal
mapping in `kongono/http-server`. Phase 2F implements bootstrap and
application-call Kernel operation ownership in `kongono/web-application`.
Phase 2G implements the ports-only outer Web Runtime, and Phase 2H proves the
complete fixture path. Phase 3C implements bounded explicit host-value
conversion in `kongono/php-cgi`; Phase 3D implements fully staged response
emission with no streaming or rollback/retry after uncertain commitment.
Arbitrary adapters, proxy trust, and deployed integration remain unimplemented. Phase 3E
implements the immutable operator profile and independent trust inputs. Phase
3F implements the exact concrete delivery, Web Runtime composition, production
entry boundary, and one-delivery cleanup. Phase 3G implements the bounded front-
controller lifecycle and documents deployment constraints; Phase 3H completes
external test-owned acceptance and the Phase 3 checkpoint.

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
- [Phase 3 roadmap](phase-3-roadmap.md)
- [PHP CGI FastCGI profile](php-cgi-fastcgi-profile.md)
- [ADR 0052](../adr/0052-php-cgi-fastcgi-responder-profile.md)
- [Runtime model](runtime-model.md)
- [Architecture overview](overview.md)
- [Security policy](../../SECURITY.md)
- [Package layout](../engineering/package-layout.md)
- [Architectural doctrine](../doctrine.md)
- [API status](../engineering/api-status.md)
- [Error handling](../engineering/error-handling.md)

## Web Runtime consumer and Phase 3 boundary

The new top-level Web Runtime leaf consumes first-party `Response` and
`StreamFactory` to build fresh fixed failure responses, and its Public delivery
port exchanges PSR-7 requests and responses. HTTP Message remains transport-
neutral and has no reverse dependency on Web Runtime. Protocol framing,
`Content-Length`, and concrete production emission remain outside Phase 2. The
root-owned fixture proves runnable composition but is not production
integration. Phase 3B selects PHP `cgi-fcgi`, CGI/1.1, and FastCGI
`RESPONDER`. Phase 3C implements caller-supplied closed-snapshot and bounded
stream projection, Phase 3D implements fully staged response emission, and
Phase 3F composes both through a concrete one-delivery PHP CGI adapter. That
adapter retains the successful first-party request only for explicit body
cleanup and captures the exact response body before emission. PHP CGI depends
on Web Runtime, but HTTP Message remains transport-neutral and has no reverse
dependency. Phase 3G now adds the stateless PHP CGI front controller: one entry
attempt followed by one caller-supplied application-shutdown attempt, with 1019
preserving both failures. HTTP Message remains unchanged and transport-neutral.
PHP CGI still has no direct Web Application edge; caller composition and
partial-boot cleanup remain outside it. The selected-profile constraints are
documented without a production listener, server configuration, or deployment
artifact. Uncertain emission or shutdown requires external process retirement.
Phase 3H supplies root-owned external proof without adding a production
dependency on acceptance code. The intrinsic query ceiling remains 8,192 bytes
and exact target/query composition has an 8,190-byte effective external
maximum. Phase 3 is closed without a Stable API or hardening claim.

Phase 5B changes only Composer installability metadata: the root and all
eighteen packages now require `>=8.5.0 <8.6.0`. PHP 8.5 remains the sole
compatibility-certified minor, and PHP 8.5.9 on Windows is the environment
proved by this execution. Stable eligible 8.5 patches stay in range; PHP 8.6,
PHP 9, prerelease, nightly, and automatic future-minor support are not claimed.
No HTTP Message runtime behavior, package, dependency, public API, polyfill,
shim, release/LTS promise, or composition/build capability is added. Phase 5A
remains accepted; Phase 5C is next and has not begun.

## Relationship to governed outbound HTTP

Phase 15A leaves this inbound, transport-neutral HTTP message model unchanged.
Phase 15B implements an inert
[network-egress contract](network-egress-contract-foundation.md) that reuses
PSR-7 message semantics where they fit, while keeping current
egress authority, canonical origin, resolution binding, selected address,
attempt lifecycle, budgets, cancellation, uncertainty, and cleanup outside the
message itself. Request and response body streams are retained by reference,
never read or claimed deeply immutable. PSR-18 compatibility is an optional
future outer adapter, not the core control plane. ADR 0155 and
[ADR 0156](../adr/0156-neutral-versioned-network-egress-contract-foundation.md)
are Accepted; Phase 15C remains planned and no outbound client, transport, or
network behavior exists.
