# ADR 0045: First-Party HTTP Message and Factory Implementation

Status: Accepted

Date: 2026-08-16

## Context

ADR 0044 selected `psr/http-message: ^2.0`, the server-side subset of
`psr/http-factory: ^1.1`, and a Kongono-owned implementation boundary. Phase 2C
must realize that boundary without introducing routing, middleware, transport
adapters, client HTTP, ambient state, or a framework substrate.

## Decision

The ninth package is `packages/http-message`, Composer package
`kongono/http-message`, namespace `Kongono\Http\Message\`. It depends only on
PHP 8.5, API Metadata, Diagnostics, `psr/http-message`, and
`psr/http-factory`. The dependency graph remains acyclic, and Kernel remains
transport-neutral and PSR-free.

The package implements exactly ten PHP-FIG roles: server request, response,
URI, stream, uploaded file, and their five selected PSR-17 factories. It does
not implement a client `RequestInterface`, `RequestFactoryInterface`, PSR-15,
PSR-18, routing, middleware, dispatch, SAPI conversion, or response emission.

The Experimental concrete classes are `Response`, `ServerRequest`, `Uri`,
`Stream`, `UploadedFile`, `ResponseFactory`, `ServerRequestFactory`,
`UriFactory`, `StreamFactory`, `UploadedFileFactory`,
`InvalidMessageArgumentException`, and `HttpMessageRuntimeException`.
`Response` defaults to status 200, protocol 1.1, empty headers/body, and an
empty reason phrase. `ServerRequest` requires an explicit method and URI and
defaults only server parameters, headers, body, and protocol. `Uri` defaults to
the empty reference. `Stream` requires an open stream resource. `UploadedFile`
requires a stream and defaults size to unknown and error to `UPLOAD_ERR_OK`.
The package exception marker is Public; the catalog, header collection,
validation helper, and abstract message are Internal.

Header names use the HTTP token grammar. Identity is ASCII
case-insensitive while selected spelling and value order are retained. Values
are never trimmed, split, or coerced; carriage return, line feed, NUL, other
controls except horizontal tab, and DEL are rejected. Constructor variants
merge under the first spelling. Replacement adopts new spelling; append retains
existing spelling. Host follows the complete PSR-7 constructor and `withUri()`
decision table, excludes URI user information, and includes only non-default
ports.

URI components follow deterministic RFC 3986 validation and encoding. Scheme
and host normalize to lowercase; raw Unicode hosts are rejected; valid percent
triplets are preserved; generated percent hex is uppercase; malformed triplets
fail. Default HTTP and HTTPS ports are suppressed. Serialization obeys
authority/path slash rules and performs no DNS, IDNA, base resolution, network,
or filesystem access.

Every `with*()` and `without*()` operation returns a distinct value. This is
shallow copy-on-change: streams and consumer-owned objects may be shared and
remain stateful. Top-level arrays are detached, but nested values are not
deep-cloned.

Streams wrap explicit resources and expose native cursor, capability, size, and
metadata evidence. Close owns closure; detach transfers the resource. The file
factory opens only the caller-supplied path or stream URI and never exposes it
in diagnostics. Whole-stream methods are caller-invoked and may allocate in
proportion to remaining content.

Uploaded files move one way by replacing a caller-selected local target and
copying chunks of at most 8192 bytes. A seekable source rewinds; a non-seekable
source copies from its current cursor. The package closes only the target it
opens and marks the upload moved only after complete success. It provides no
rollback, atomic replacement, storage ownership, cleanup, or upload policy.

Server parameters, cookies, query parameters, uploaded files, parsed bodies,
and attributes remain independent domains. The package reads no SAPI globals,
request body, environment, or ambient current request.

Rejected caller input uses a final native-compatible invalid-argument
exception; stream state, capability, and local I/O failures use a final
native-compatible runtime exception. Both implement the distinct Public HTTP
marker. The Internal catalog owns 28 `KGN-HTTP-1xxx` identifiers and closed,
bounded structural context. Raw HTTP values, content, paths, metadata,
credentials, and secrets never enter diagnostic messages or context.

Messages, factories, streams, and failures retain no static request, global
registry, Kernel state, DI binding, operation scope, or persistent-runtime
ambient state. Explicit mutable streams and consumer objects remain owned by
their callers.

Composer registers the ninth package and test namespace. PHP CS Fixer, PHPStan,
PHPUnit, strict PSR-4 autoload generation, package validation, security audit,
and the root architecture inventory cover the package. The selected interfaces
are implemented and Kongono's required behavioral suite passes; this ADR does
not claim certified universal PSR conformance.

Security consequences include strict header-injection rejection, bounded safe
diagnostic context, no raw path disclosure, and no ambient authorization signal.
Performance is linear in header count, represented arrays, URI bytes, and
stream content explicitly read or copied. No caching, indexing, buffering
budget, or constant-time promise is introduced.

## Rejected alternatives

Rejected alternatives are adopting another framework or third-party PSR-7
implementation, ambient factory discovery, service locators, global requests,
client HTTP, SAPI parsing, routing, middleware, response emission, automatic
error pages, and folding the package into Kernel.

## Consequences and later ownership

Phase 2C owns only the bounded message and factory implementation. Phase 2D
owns deterministic router decisions. Later authenticated phases own PSR-15
handling, middleware, adapters, SAPI conversion, bootstrap, emission, production
HTTP failure mapping, cleanup coordination, and runnable web acceptance.

## Related decisions

- [ADR 0044](0044-http-message-and-interoperability-architecture.md)
- [HTTP message model](../architecture/http-message-model.md)
- [Phase 2 roadmap](../architecture/phase-2-roadmap.md)
