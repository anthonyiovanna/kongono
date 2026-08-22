# ADR 0053: Bounded PHP CGI Inbound Conversion

Status: Accepted

Date: 2026-08-21

## Context

ADR 0052 selected PHP `cgi-fcgi`, CGI/1.1, and FastCGI `RESPONDER` as the first production host profile. Phase 3C needs the first runtime slice without giving framework code ambient access to SAPI state, superglobals, the environment, FastCGI framing, or process-wide current-request state.

The current Web Runtime converts any failure from a delivery's future `request()` operation into its fixed generic 500 response. Inbound conversion must therefore fail through closed diagnostics without inventing semantic 4xx mappings or disclosing rejected host bytes.

## Decision

Create `packages/php-cgi`, Composer package `kongono/php-cgi`, namespace `Kongono\PhpCgi\`. Its exact direct dependency subset is API Metadata, Diagnostics, HTTP Message, PSR-7, and PHP. It does not depend directly on Diagnostic Activation, Web Runtime, Web Application, Kernel, DI, Graph, HTTP Server, or Routing.

The Experimental stateless `CgiServerRequestConverter` exposes:

```php
public function convert(
    string $sapi,
    array $server,
    array $post,
    array $files,
    mixed $input,
): ServerRequestInterface;
```

The caller supplies all host evidence explicitly. Package production code does not read `PHP_SAPI`, superglobals, environment variables, or `php://input`. Admission requires exact `cgi-fcgi`, `CGI/1.1`, and `RESPONDER` values. The server snapshot is closed to eight required keys, the authorized optional keys, and syntactically valid `HTTP_*` keys; all values are detached strings and are emitted in canonical profile order. `$post` and `$files` must be empty.

Method values are bounded uppercase HTTP tokens. Protocol mapping is closed to HTTP/1.0, HTTP/1.1, HTTP/2, HTTP/2.0, HTTP/3, and HTTP/3.0. Targets are origin-form, at most 8,192 bytes, contain safe bytes and valid percent triplets, and agree byte-for-byte with the separately supplied query string. `HTTP_HOST` is the sole authority source and `HTTPS` selects only `http` or `https`; the first-party URI implementation performs final component validation and normalization.

Headers are projected in deterministic Host, Content-Type, Content-Length, then bytewise `HTTP_*` order. Ambiguous content headers, Proxy, Transfer-Encoding, duplicates, and unsafe values reject. Limits are 128 distinct headers, 8,192 bytes per value, and 65,536 total name-plus-value bytes.

The query grammar is flat, declaration-ordered, UTF-8, and limited to 128 unique names; names are at most 128 bytes and values at most 4,096 bytes. Only `&`, the first `=`, `+`, and one layer of `%HH` decoding have meaning. Brackets, nesting, controls, malformed encoding, and duplicates reject. Cookie parsing is likewise flat and ordered, with a 4,096-byte raw limit, 64 unique token names, 128-byte names, and 1,024-byte visible-ASCII values. Quoting, unescaping, percent decoding, and attributes are not interpreted.

Content length is canonical unsigned decimal from zero through 1,048,576. Multipart form data rejects; other forms, JSON, MIME, uploads, transfer/content encodings, and deserialization are not parsed. The caller retains its readable attached input resource. The converter reads from the current position in chunks no larger than 8,192 bytes, reads exactly the declared length and no more, and never rewinds or closes the caller resource. Accepted bytes are copied into a fresh first-party `php://temp` body positioned at zero. Package-owned temporary resources close on failure; success transfers body ownership to the returned first-party `ServerRequest`.

Ten Internal diagnostics use the closed `KGN-PHP-CGI-1001` through `KGN-PHP-CGI-1010` range. Each context contains exactly one required Safe text field, `violation`, whose value comes from a closed token vocabulary. Experimental invalid-request and read exceptions retain only their diagnostic, eager detached context, and an explicitly authorized previous failure. Raw rejected values are absent from package-owned message, context, and property surfaces.

Every call produces fresh request, URI, body, and detached arrays. Equivalent inputs produce canonical server/header order regardless of insertion order. No cache, registry, callback, process-global hook, or retained per-call state exists.

## Consequences

The request body is fully buffered, so conversion uses bounded additional memory and copying proportional to the accepted body size, capped at 1,048,576 bytes. Query, cookie, target, header, and snapshot limits bound the remaining work. This cost is accepted so the first-party body is seekable, detached, positioned at zero, and isolated from caller resource ownership.

A future concrete delivery will call conversion from `request()`. Conversion failure will then enter the existing Web Runtime generic-500 path before any response emission. Phase 3C itself has no delivery, response emitter, operator profile, trust evaluation, production entrypoint, deployment, or external acceptance.

## Rejected alternatives

- Direct reads from SAPI constants, superglobals, environment variables, or `php://input`.
- A general PSR server-request adapter abstraction or support for arbitrary SAPIs.
- Form, upload, JSON, MIME, transfer-encoding, or content-encoding parsing.
- Proxy or operator trust evaluation during conversion.
- Response emission, a listener, FastCGI parser, process manager, or production entrypoint.
- Pulling later Phase 3D through 3H work into this package slice.

## Phase 3F integration status

Phase 3D response emission and Phase 3E trust inputs are implemented. Phase 3F
now calls this converter exactly once from a fresh Internal delivery after its
permission decision and lazy input acquisition. Rejection still reaches Web
Runtime's fixed generic 500; no semantic client-error mapping was added. The
delivery retains only the successful first-party request for explicit body
cleanup and clears its capture state after the conversion attempt.

## Phase 3G reconciliation

ADR 0057 composes the unchanged converter/delivery entry with one explicit
caller-supplied application-shutdown attempt. The front controller neither
retries conversion nor emits another response. Correct application composition
and partial-boot cleanup remain caller-owned; uncertain emission or shutdown
requires external process retirement. PHP CGI still has no direct Web
Application edge, and no listener, server configuration, deployment artifact,
or external proof was added. Phase 3H alone is next.

## Phase 3H outcome

Phase 3H drives this unchanged converter through the actual selected PHP-CGI
listener. It proves the closed admission and input matrix, including the
effective external query maximum of 8,190 bytes produced by the unchanged
8,192-byte target ceiling and exact target/query equality. The intrinsic
8,192-byte query check remains package-level authority. No production source,
limit, dependency, API, diagnostic, or runtime semantic changes.
