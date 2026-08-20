# ADR 0044: HTTP Message and Interoperability Architecture

## Status

Accepted

## Date

2026-08-16

## Context

At the time of this decision Kongono had exactly eight implemented first-party
packages and tested component behavior. Phase 2C later added the ninth package and
the selected request, response, URI, stream, upload, and factory implementations.
Phase 2D subsequently adds deterministic encoded method/path routing in a
separate tenth package without changing the HTTP Message boundary. At this
decision point Kongono had no middleware pipeline, web bootstrap, runtime
adapter, response emitter, generic production web failure path, or runnable
fixture application. [ADR 0043](0043-phase-2-runnable-web-framework-milestone.md)
requires an explicit HTTP interoperability decision before Phase 2C implements
the first message foundation.

The decision must preserve Kongono ownership and the existing acyclic package
direction while avoiding a private HTTP vocabulary that isolates application
code from common PHP server interfaces. It must also keep transport input,
parsing, routing, middleware, delivery, and Kernel lifecycle outside a low-level
message package.

## Primary-source evidence

The decision uses the official [PSR-7 HTTP Message
Interfaces](https://www.php-fig.org/psr/psr-7/), [PSR-15 HTTP Server Request
Handlers](https://www.php-fig.org/psr/psr-15/), and [PSR-17 HTTP
Factories](https://www.php-fig.org/psr/psr-17/), together with [RFC
3986](https://www.rfc-editor.org/rfc/rfc3986.html), [RFC
9110](https://www.rfc-editor.org/rfc/rfc9110.html), and [RFC
9112](https://www.rfc-editor.org/rfc/rfc9112.html).

The Composer package observations on 2026-08-16 are:

| Contract | Interface package | Observed line | Kongono constraint |
| --- | --- | --- | --- |
| PSR-7 | [`psr/http-message`](https://packagist.org/packages/psr/http-message) | `2.0` | `^2.0` |
| PSR-17 | [`psr/http-factory`](https://packagist.org/packages/psr/http-factory) | `1.1.0` | `^1.1` |
| PSR-15 handler | [`psr/http-server-handler`](https://packagist.org/packages/psr/http-server-handler) | `1.0.2` | `^1.0` |
| PSR-15 middleware | [`psr/http-server-middleware`](https://packagist.org/packages/psr/http-server-middleware) | `1.0.2` | `^1.0` |

These packages define interfaces. They do not provide an HTTP message
implementation, router, dispatcher, framework, adapter, emitter, or runtime.
PSR-7 requires copy-on-change message methods while explicitly noting that a
body stream remains stateful. PSR-17 provides separate factories for client
requests and server requests. PSR-15 defines server request handlers and
middleware over PSR-7 server requests and responses.

## Decision

### Standards boundary

Kongono accepts:

- PSR-7 through `psr/http-message: ^2.0` as the future HTTP message, server
  request, response, URI, stream, and uploaded-file interoperability contract;
- the server-side portion of PSR-17 through `psr/http-factory: ^1.1` as the
  future message-factory interoperability contract; and
- PSR-15 through `psr/http-server-handler: ^1.0` and
  `psr/http-server-middleware: ^1.0` as the future Phase 2E server-handler and
  middleware interoperability contracts.

The standards are contracts at Kongono's interoperability edge. They do not
own Kongono's architecture, choose an implementation, make the Kernel HTTP
aware, or authorize runtime discovery. Phase 2B installs none of these
packages and claims no PSR conformance.

Phase 2C added only the selected PSR-7 and PSR-17 interface packages under its
separately authenticated implementation directive. At that time the PSR-15
packages remained deferred until Phase 2E.

Phase 2E current-state amendment: [ADR 0047](0047-explicit-psr-15-middleware-and-terminal-routing.md)
installs and uses both PSR-15 packages in the separate eleventh package,
`kongono/http-server`. It implements explicit bounded immutable middleware
sequencing and terminal route mapping without changing this standards boundary.
Phase 2F and ADR 0048 implement the separate `kongono/web-application`
validation-before-boot and Kernel operation-scope boundary without changing the
standards decision. Phase 2G now supplies an outer adapter, a delivery port, and
fixed generic or eligible trusted responses; no concrete emission, supplied
error middleware, or runnable application exists.

### First-party package boundary

Phase 2C implements one first-party package with exactly
this identity:

```text
directory              packages/http-message
Composer package       kongono/http-message
production namespace   Kongono\Http\Message\
test namespace         Kongono\Http\Message\Tests\
```

The package may depend only on:

```text
php: ^8.5
kongono/api-metadata: *
kongono/diagnostics: *
psr/http-message: ^2.0
psr/http-factory: ^1.1
```

The root may later consume `kongono/http-message: @dev` under the existing
monorepo path-repository convention. API Metadata and Diagnostics gain no
reverse dependency. The HTTP message package must not depend on Kernel,
Dependency Injection, Application Graph, Diagnostic Activation, Diagnostic
Presentation, Diagnostic Handler, PSR-15, routing, adapters, runtime servers,
or a third-party PSR-7 implementation. Composition remains at the application
root.

Phase 2B creates none of these package paths, dependencies, namespaces, or
types.

### Exact server-side interface roles

Phase 2C must implement first-party concrete roles satisfying exactly these
PSR-7 interfaces:

```text
Psr\Http\Message\ServerRequestInterface
Psr\Http\Message\ResponseInterface
Psr\Http\Message\UriInterface
Psr\Http\Message\StreamInterface
Psr\Http\Message\UploadedFileInterface
```

`ServerRequestInterface` already extends `RequestInterface`, which extends
`MessageInterface`; the server milestone requires no separate client-request
object.

Phase 2C must implement exactly these PSR-17 factory roles:

```text
Psr\Http\Message\ServerRequestFactoryInterface
Psr\Http\Message\ResponseFactoryInterface
Psr\Http\Message\UriFactoryInterface
Psr\Http\Message\StreamFactoryInterface
Psr\Http\Message\UploadedFileFactoryInterface
```

`RequestFactoryInterface`, PSR-18, a general outgoing request factory, and
client-side HTTP remain outside Phase 2. This decision authorizes no convenience
API, mutable setter API, global factory, discovery hook, `fromGlobals()` method,
emitter, parser, router helper, or framework base class.

### Message and header semantics

PSR-7 `with*()` methods retain the original message state and return a value
representing the requested change. This is copy-on-change behavior, not a claim
of deep immutability. A body `StreamInterface` is stateful, and message copies
may share it; cursor movement or changes to its underlying resource may
therefore be observable through more than one message value.

Header lookup is case-insensitive while the originally supplied field-name case
is preserved for retrieval. Header value order and repeated values are
preserved. Field names and values are validated before storage. Carriage return,
line feed, NUL, invalid field-name characters, and ambiguous line folding are
rejected rather than repaired, replaced, or silently normalized. The full
PSR-7 `Host` and `withUri()` decision table requires Phase 2C tests.

Syntactic validity establishes neither trust nor eligibility for a diagnostic
Safe field. Raw header names and values are not captured as Safe diagnostic
context.

### URI semantics

URI component behavior follows PSR-7 and RFC 3986. Phase 2C must test scheme
and host canonicalization, authority composition, percent encoding, path,
query, fragment, user information, port handling, and string serialization.
Reserved delimiters are not decoded into a different semantic meaning.

A URI value performs no DNS, filesystem, network, base-URI guessing, or
scheme-specific resolution. A syntactically valid URI remains untrusted input.

### Streams and uploaded files

Streams are explicit resource-backed values with observable readability,
writability, seekability, cursor, size, metadata, detach, and close behavior.
Phase 2C must bound reads performed by first-party tests and helpers and must
not introduce an unbounded request-body parser. Stream-factory file access is
explicit caller-requested local I/O; it is not adapter parsing or network
access, and local paths do not become diagnostic data.

Uploaded-file movement is one-way and explicit. Invalid targets, repeated
moves, stream failures, and runtime failures fail loudly under the PSR
contract. Message, stream, and uploaded-file values own no Kernel lifecycle
behavior.

### Server-request data and trust

Server parameters, cookies, query parameters, uploaded files, parsed bodies,
and attributes remain separate PSR-7 domains. The future package stores
already-supplied representations. It does not read `$_SERVER`, `$_GET`,
`$_POST`, `$_COOKIE`, `$_FILES`, input streams, or emitted headers, and it does
not decide parsing, normalization, size limits, duplicate handling, proxy trust,
authentication, or authorization.

Attributes are explicit per-request call-flow data, not ambient context,
authorization proof, or a permission upgrade. Consumer-owned mutable values in
attributes or parsed bodies do not become deeply immutable because a message
contains them. Adapter conversion and input parsing remain Phase 2G decisions.

### Diagnostics and failures

Phase 2C must define package-local typed failures compatible with PSR-required
`InvalidArgumentException` or `RuntimeException` categories where applicable.
Deliberate Kongono failures retain actionable diagnostic identifiers, bounded
context, titles, and remediation under existing doctrine. Phase 2B allocates no
diagnostic identifier and names no exception class.

Diagnostic context must never contain raw headers, cookies, query strings,
body contents, credentials, tokens, uploaded content, local file contents, or
unbounded URI text. Only bounded structural facts expressly authorized by the
Phase 2C schema may be Safe. Consumer, resource, and autoloader failures
propagate unchanged unless Phase 2C explicitly owns and maps them.

### Persistent-runtime ownership

Each future inbound delivery is owned by an outer adapter and one Kernel
operation scope. Message values move through explicit parameters and return
values. No request-specific message, stream, permission, route, or attribute is
retained in global, static, singleton, container-current, thread-local,
fiber-local, or previous-operation state.

The Kernel remains transport-neutral and has no PSR dependency. Factories are
explicit composition dependencies, not ambient singletons, registries, or
service locators. Successful and failing adapter paths eventually close their
owned operation scope exactly once. Cleanup APIs, simultaneous failures,
cancellation, recovery, response emission, and uncertain-runtime retirement
remain later explicit decisions.

### PSR-15 boundary for Phase 2E

Phase 2E must use:

```text
Psr\Http\Server\RequestHandlerInterface
Psr\Http\Server\MiddlewareInterface
```

The future terminal application handler and middleware pipeline speak PSR-7
`ServerRequestInterface` and `ResponseInterface`. Response-producing middleware
receives an explicit response factory or prototype through composition and
does not locate one globally. Router APIs, route results, dispatcher classes,
terminal mapping, error middleware, and owning package names remain Phase 2D
and Phase 2E decisions.

### API status

PHP-FIG interfaces retain their external status and are not Kongono API-status
metadata subjects. Future first-party concrete message and factory types are
Public and Experimental unless a later directive expressly selects otherwise.
Internal validation helpers remain Internal. No Stable Kongono web API is
created here.

## Current versus planned behavior

Current Phase 2B behavior remains the eight-package Phase 1H implementation.
No HTTP package directory, PSR dependency, message class, factory, router,
middleware, adapter, emitter, or runnable web path exists, and the repository
is not PSR compliant merely because this decision is Accepted.

The package identity, dependency direction, interface roles, and semantic rules
above authorize later work. Phase 2C must still receive a separate authenticated
directive before implementing them.

## Consequences for the Phase 2 roadmap

- Phase 2C implements only the bounded first-party PSR-7/PSR-17 message and
  factory foundation and conformance tests.
- Phase 2D may consume server-request and response interfaces while separately
  deciding deterministic routing.
- Phase 2E adopts the two PSR-15 server interfaces and separately decides the
  ordered middleware and terminal-handler architecture.
- Phase 2F composes the selected components and Kernel operation ownership
  without adding HTTP knowledge to Kernel.
- Phase 2G owns SAPI or runtime conversion, parsing limits, delivery evidence,
  response emission, generic failures, and exact-once cleanup.
- Phase 2H proves the complete fixture path and sequential-operation isolation
  from outside the internal packages.

The interface boundaries improve compatibility with server middleware and
application code without importing another framework. The first-party
implementation creates a compatibility and testing obligation but keeps its
behavior inspectable and its dependency graph acyclic.

## Security and performance consequences

Message construction must reject header injection and invalid structural input
before storage. URI, header, cookie, body, upload, and attribute data remain
untrusted. Diagnostics exclude sensitive and unbounded request material.
Factory injection and the rejection of ambient discovery keep authority and
implementation selection explicit.

Copy-on-change message operations require new message values but may share a
stateful stream. Stream operations can perform I/O and must expose their
capabilities and failures. Phase 2C must specify and test exact bounds and costs
without introducing request parsing or unbounded body reads.

## Rejected alternatives

- A private Kongono-only HTTP contract is rejected because it would isolate
  application and middleware code and require needless adapters.
- Symfony HttpFoundation as the framework substrate is rejected because it
  would import another framework's HTTP objects and lifecycle assumptions.
- Guzzle, Laminas, Nyholm, Slim, or another third-party PSR-7 implementation as
  Kongono's required production foundation is rejected. Such packages may
  later be compatibility-test peers but do not own Kongono's implementation.
- Runtime discovery, global registries, service locators,
  first-installed-wins behavior, and ambient factory lookup are rejected.
- Direct SAPI-global access in the message package is rejected because an
  outer adapter owns conversion and parsing.
- Mutable bespoke message objects are rejected in favor of PSR-7
  copy-on-change semantics with the stream caveat kept explicit.
- PSR-18 and general client-side HTTP scope are rejected for this server-side
  milestone.

## What remains open

Phase 2C selected and implemented the bounded first-party message/factory
surface. Phase 2D separately selected and implemented `kongono/routing` over the
PSR-7 interface boundary, without depending on Kongono HTTP concrete classes.
Its bounded route grammar, exact encoded method/path comparison, deterministic
specificity, structural conflicts, immutable match result, and ordinary `null`
unmatched behavior are no longer open. Route matches invoke no handler and prove
neither authentication nor authorization.

Phase 2E selected and implemented the bounded PSR-15 middleware dispatcher and
terminal route mapping in `kongono/http-server`; these are no longer open.
Phase 2F selected bootstrap/composition and application-call Kernel scope
cleanup. Later owning phases retain error-middleware decisions; adapter conversion; parsing and resource limits;
proxy trust; response emission; production failure mapping; cleanup
APIs; cancellation; simultaneous failures; runtime retirement; black-box
fixtures; conformance evidence; and compatibility promotion.

No open item is implicitly selected by this ADR.

## Phase 2H current-state amendment

Phase 2C through 2G separately implemented the selected first-party message,
routing, middleware, application, and outer-runtime boundaries. Phase 2H now
composes them in a root-owned subprocess fixture using explicit first-party
server requests and the Public delivery port. The fixture proves response
delivery, fixed generic failure, eligible trusted HTML, cleanup, and sequential
isolation without changing the PSR interoperability decision or adding a
production emitter/server. Earlier planned-language sections remain historical
phase evidence rather than claims about the current repository.

## Related documentation

- [HTTP message model](../architecture/http-message-model.md)
- [ADR 0046](0046-deterministic-route-declaration-and-matching.md)
- [Routing model](../architecture/routing-model.md)
- [ADR 0047](0047-explicit-psr-15-middleware-and-terminal-routing.md)
- [HTTP server model](../architecture/http-server-model.md)
- [ADR 0048](0048-web-application-bootstrap-and-kernel-operation-ownership.md)
- [Web Application model](../architecture/web-application-model.md)
- [ADR 0043](0043-phase-2-runnable-web-framework-milestone.md)
- [Phase 2 roadmap](../architecture/phase-2-roadmap.md)
- [Architecture overview](../architecture/overview.md)
- [Runtime model](../architecture/runtime-model.md)
- [Package layout](../engineering/package-layout.md)
- [Error handling](../engineering/error-handling.md)
- [API status](../engineering/api-status.md)
- [Architectural doctrine](../doctrine.md)
- [Security policy](../../SECURITY.md)
- [ADR 0050](0050-phase-2-runnable-fixture-and-checkpoint.md)
- [Phase 2 runnable fixture](../architecture/phase-2-runnable-fixture.md)
