# Kongono PHP CGI

`kongono/php-cgi` implements Kongono's bounded inbound conversion, response emission, and operator trust-input boundaries for the PHP `cgi-fcgi`, CGI/1.1, FastCGI `RESPONDER` profile selected by ADR 0052. Phase 3C provides explicit host-snapshot conversion, Phase 3D provides fully staged response emission, and Phase 3E provides immutable operator bootstrap plus independent current-delivery permission evaluation. No phase creates a production entrypoint or a concrete Web Runtime delivery.

## Calling contract

The Experimental stateless `CgiServerRequestConverter::convert()` accepts an explicit SAPI label, closed server-parameter array, post array, file array, and caller-owned input stream. It returns a fresh first-party `Kongono\Http\Message\Message\ServerRequest` behind `Psr\Http\Message\ServerRequestInterface`.

Call only with exact `cgi-fcgi`, `CGI/1.1`, and `RESPONDER` admission values. The server snapshot must contain the eight required profile fields and may contain only the documented optional and valid `HTTP_*` fields. Every value is a string. Post and file arrays must be empty. Conversion code does not read SAPI constants, superglobals, environment variables, or `php://input`.

The converter supports bounded uppercase HTTP methods, the selected six protocol spellings, origin-form request targets, exact target/query agreement, `http`/`https`, authority only from `HTTP_HOST`, deterministic single-valued header projection, flat query pairs, and flat cookie pairs. It does not support absolute/authority/asterisk targets, nested queries, repeated query or cookie names, quoted/unescaped cookies, multipart forms, uploads, JSON/form parsing, decompression, transfer decoding, proxy trust, or arbitrary SAPI adapters.

## Body ownership

`CONTENT_LENGTH` is canonical decimal from zero through 1,048,576. The caller's input must be an attached readable stream. Conversion begins at its current position, reads exactly the declared bytes in bounded chunks, leaves extra bytes unread, and never rewinds, closes, detaches, or retains it. Accepted bytes are copied into a new readable and seekable first-party body at position zero. On failure, only package-owned temporary body state is closed; on success, the returned request owns that body.

Full buffering costs time and additional memory proportional to the accepted body, bounded at 1 MiB. Header, target, query, cookie, and snapshot limits bound the remaining conversion work.

## Failure and disclosure

Invalid structure throws Experimental `InvalidCgiRequestException`; input-resource or exact-read failure throws Experimental `CgiRequestReadException`. Both implement the package Experimental marker. Diagnostics use closed IDs and violation tokens with exactly one Safe field. Package-owned diagnostic and exception surfaces never retain rejected raw host values.

Every call is isolated and returns fresh request, URI, body, and detached arrays. The converter retains no cache or current request, so repeated calls in a persistent PHP process do not share package state.

## Response staging and emission

The Experimental stateless `CgiResponseEmitter::emit(ResponseInterface $response, string $requestMethod): void` validates status, protocol, headers, and body before any SAPI call. It reads a readable response body from its current position, copies at most 1,048,576 bytes into package-owned staging, computes `Content-Length`, and emits status, ordered application headers, computed length, and body in that order. `HEAD` performs the same staging and length computation but emits no body bytes. Streaming is unsupported.

Response status is limited to 200 through 599 and protocol to `1.0`, `1.1`, or `2`. Header limits are 128 fields, 32 values per field, 8,192 bytes per value, and 65,536 total name/value bytes. Adapter-owned `Status` and `Content-Length` and the selected hop-by-hop fields are forbidden. The caller retains ownership of the response body; the package closes only its temporary staging stream.

The commitment-attempt state is set before the first status call. Every later failure is committed or uncertain, throws `CgiResponseEmissionException`, and is never rolled back or retried. Consumer response-getter and body-read throwables propagate unchanged. The Internal target seam exists only for exact production behavior tests; it is not public injection or configuration.

## Status and dependencies

The converter, emitter, entry, operator profile, immutable bootstrap result, and package exceptions are Experimental; the concrete delivery, diagnostic, validation, permission-evaluation, parsing, staging, and native-emission helpers are Internal. Direct dependencies are API Metadata, Diagnostic Activation, Diagnostics, HTTP Message, Web Runtime, PSR-7, and PHP. PHP CGI has no direct Web Application dependency.

`CgiOperatorProfile::Production` maps bootstrap permission to `Denied`; `LoopbackDevelopment` maps it to `Allowed` only after the exact SAPI and ten INI prerequisites pass. There is no scalar, file, environment, or request parser and no default profile. The delivery evaluator independently allows only exact CGI/1.1 RESPONDER evidence with canonical `127.0.0.1` or `::1` and no forwarded marker; every other or uncertain input denies.

Phases 3C through 3G are implemented. `CgiWebDelivery` projects only the closed CGI server allowlist, represents form/upload presence as booleans, opens `php://input` lazily during `request()`, emits once, and releases request, response, and native-input resources during explicit close. `CgiWebEntry` creates one fresh delivery for an already-composed runtime. `CgiFrontController::run(WebRuntimeAdapter, Closure): void` invokes that entry once and then the caller's paired application-shutdown closure once. Success/success returns; one failure is rethrown by identity; simultaneous failure uses `CgiEntryShutdownException` and diagnostic 1019 to preserve the entry failure as native previous and the shutdown failure separately. Safe context contains no throwable detail.

Web Runtime still owns failure selection, one send, and one close; malformed input becomes its fixed generic 500. PHP CGI has no direct Web Application edge. Consumers own application construction, correct runtime/shutdown pairing, and partial-boot cleanup. `Production` remains denied. Uncertain emission or shutdown requires external process retirement. The [deployment contract](../../docs/engineering/php-cgi-deployment.md) records exact operator constraints. Phase 3H's root-owned harness proves the selected synchronous PHP-CGI FastCGI RESPONDER profile externally without adding a dependency from this package. The harness is not a production or supported server/client. This package is not a listener, server, process manager, deployment solution, or broad SAPI adapter. Process, proxy, TLS, credentials, logs, monitoring, incidents, and deployment ownership remain external. Phase 3 is complete, is not the full framework, Stable API, 1.0, universal SAPI support, deployment automation, or production-hardening certification, and starts no next phase automatically.

See [ADR 0053](../../docs/adr/0053-bounded-php-cgi-inbound-conversion.md), [ADR 0054](../../docs/adr/0054-bounded-php-cgi-response-emission.md), [ADR 0055](../../docs/adr/0055-immutable-php-cgi-operator-bootstrap-and-trust-inputs.md), [ADR 0056](../../docs/adr/0056-php-cgi-production-delivery-and-entry-composition.md), [ADR 0057](../../docs/adr/0057-bounded-php-cgi-front-controller-lifecycle-and-deployment-constraints.md), and the [PHP CGI FastCGI profile](../../docs/architecture/php-cgi-fastcgi-profile.md).
