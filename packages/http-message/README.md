# Kongono HTTP Message

`kongono/http-message` is Kongono's first-party implementation of the selected
server-side PSR-7 message roles and PSR-17 factory roles. It implements
`ResponseInterface`, `ServerRequestInterface`, `UriInterface`,
`StreamInterface`, and `UploadedFileInterface`, plus their five selected
factories. It deliberately provides no client request, `RequestFactoryInterface`,
PSR-15 middleware, routing, SAPI parsing, response emission, or HTTP client.

Messages use shallow copy-on-change semantics. Header names are validated HTTP
tokens, compared case-insensitively, and retain selected spelling and exact
value bytes. Header values reject injection-capable control bytes. Body streams
and consumer objects remain shared state; this package does not claim deep
immutability.

`Uri` implements deterministic RFC 3986 component parsing, validation,
percent-encoding, default-port normalization, and string assembly without DNS,
IDNA, filesystem, or network access. `ServerRequest` keeps server parameters,
cookies, query data, uploaded files, parsed bodies, and attributes separate and
never reads SAPI globals.

`Stream` wraps one explicit PHP stream resource. Caller-invoked whole-stream
operations may consume substantial memory. `StreamFactory` is the only factory
that opens caller-requested files or stream URIs. `UploadedFile::moveTo()`
performs bounded-chunk local copying, never closes its caller-owned source, and
becomes unavailable only after a complete move. It defines no rollback,
storage, cleanup, or upload-policy system.

Deliberate failures are caught through the Public package-local
`Kongono\Http\Message\Exception\ExceptionInterface`. Invalid caller values use
the native-compatible `InvalidArgumentException` category; resource and I/O
failures use `RuntimeException`. The Internal 28-ID HTTP catalog exposes only
bounded structural context and never raw headers, URI text, request targets,
bodies, upload metadata, paths, server values, attributes, credentials, or
secrets.

All concrete messages, streams, uploads, URIs, exceptions, and factories are
Experimental. The exception marker is Public; helpers and the diagnostic
catalog are Internal. Factories are stateless explicit composition values, not
ambient registries or service locators. The Kernel remains transport-neutral
and PSR-free.

See [ADR 0045](../../docs/adr/0045-first-party-http-message-and-factory-implementation.md)
and the [HTTP message model](../../docs/architecture/http-message-model.md).
