# PHP CGI FastCGI Responder Profile

## Purpose and current implementation status

This document is the authoritative detailed architecture for Kongono's first
selected production HTTP host profile. [ADR 0052](../adr/0052-php-cgi-fastcgi-responder-profile.md)
accepts PHP `cgi-fcgi` in the FastCGI `RESPONDER` role under CGI/1.1.

Phase 3B selects and documents this profile. Phase 3C and
[ADR 0053](../adr/0053-bounded-php-cgi-inbound-conversion.md) implement bounded
inbound conversion. Phase 3D and [ADR 0054](../adr/0054-bounded-php-cgi-response-emission.md)
implement fully staged bounded response emission. Phase 3E and [ADR 0055](../adr/0055-immutable-php-cgi-operator-bootstrap-and-trust-inputs.md)
implement literal typed operator bootstrap and independent current-delivery
permission evaluation. Phase 3F implements the concrete Internal delivery and
Experimental thin entry into Web Runtime. The bootstrap validator and delivery
capture are the only authorized `PHP_SAPI` readers; only bootstrap reads the ten
fixed INI values. Phase 3G implements the bounded front-controller lifecycle
and documents deployment constraints. Phase 3H completes external test-owned
acceptance through an operator-supplied listener. No generated production entry
script, listener, process manager, or deployment artifact is added.

**Stability:** the selected architecture is LOCKED. Future consumer calling
surfaces are Experimental; host-specific helpers are Internal. No new Public,
Stable, or Deprecated API is selected here.

## Host admission contract

Admission requires all exact values:

```text
$sapi === "cgi-fcgi"
GATEWAY_INTERFACE === "CGI/1.1"
FCGI_ROLE === "RESPONDER"
```

The host invokes one PHP front controller for one synchronous request. One PHP
interpreter handles at most one Kongono delivery at a time. The process may be
reused later, but request, response, permission, exception, stream, and
application objects never cross delivery boundaries.

The supported CGI protocol exposure is closed:

| CGI `SERVER_PROTOCOL` | PSR message protocol version |
| --- | --- |
| `HTTP/1.0` | `1.0` |
| `HTTP/1.1` | `1.1` |
| `HTTP/2` | `2` |
| `HTTP/2.0` | `2.0` |
| `HTTP/3` | `3` |
| `HTTP/3.0` | `3.0` |

Every other value rejects. The web server owns client-facing HTTP framing,
transfer-coding removal, TLS, and HTTP-to-CGI/FastCGI conversion. Kongono
never parses HTTP wire bytes or FastCGI records.

The implemented bootstrap boundary verifies these operator prerequisites before
the first delivery:

```text
enable_post_data_reading = 0
output_buffering = 0
zlib.output_compression = 0
display_errors = 0
display_startup_errors = 0
html_errors = 0
expose_php = 0
auto_prepend_file = empty
auto_append_file = empty
cgi.rfc2616_headers = 0
```

They are admission conditions, not general configuration. Trusted application
bootstrap selects literal `CgiOperatorProfile::Production` or
`CgiOperatorProfile::LoopbackDevelopment`; no string, file, environment, or
request parser exists. The validator checks exact string results and discloses
only a closed violation token. The FastCGI endpoint must be inaccessible to
untrusted clients and reachable only by the operator-selected web server or
acceptance harness.

## Host and framework ownership

```text
operator-selected web server
    owns HTTP connection, TLS, framing, transfer coding, FastCGI listener,
    socket, timeout, process count, restart, privilege, and deployment
        |
        v
implemented CgiWebDelivery in kongono/php-cgi
    validates one CGI/FastCGI snapshot, creates one request, stages and emits
    one response, and closes delivery-owned resources
        |
        v
existing WebRuntimeAdapter
    owns failure selection, one send attempt, one close attempt, and retirement
        |
        v
existing WebApplication
    owns the sole Kernel operation scope for an accepted application call
        |
        v
existing Kernel
    remains transport-, HTTP-, CGI-, FastCGI-, SAPI-, and deployment-neutral
```

Kongono does not own a listener, connection, FastCGI protocol parser, process
manager, worker pool, scheduler, reload mechanism, or deployment supervisor.

## Package dependency graph

Phase 3C creates:

```text
directory: packages/php-cgi
Composer:  kongono/php-cgi
namespace: Kongono\PhpCgi\
role:      outer leaf implementing the selected host profile
```

```text
host/web server
    -> kongono/php-cgi
        -> kongono/api-metadata
        -> kongono/diagnostic-activation
        -> kongono/diagnostics
        -> kongono/http-message
        -> kongono/web-runtime
        -> psr/http-message ^2.0
        -> php ^8.5
```

The leaf must not depend directly on Kernel, Routing, HTTP Server, Web
Application, Application Graph, Dependency Injection, Diagnostic Presentation,
Diagnostic Handler, a web server, a FastCGI library, or another framework. No
lower package may depend on it. Its direct Diagnostic Activation and Web Runtime
edges supply the typed trust inputs and unchanged Public
`WebDeliveryInterface`; there is no direct Web Application edge.

## Closed input-source allowlist

The Phase 3C converter may construct request values only from:

1. a closed validated server array supplied by its caller; and
2. a caller-owned readable stream, read only under the declared canonical content length.

Required server entries must be strings:

```text
GATEWAY_INTERFACE
FCGI_ROLE
REQUEST_METHOD
SERVER_PROTOCOL
REQUEST_URI
QUERY_STRING
HTTP_HOST
REMOTE_ADDR
```

Optional closed scalar entries are:

```text
CONTENT_LENGTH
CONTENT_TYPE
HTTPS
REMOTE_PORT
SERVER_ADDR
SERVER_NAME
SERVER_PORT
SERVER_SOFTWARE
SCRIPT_NAME
HTTP_*
```

Arrays, objects, resources, unknown shapes, duplicate-normalized values,
missing required values, and invalid values reject. The PSR server-parameter
snapshot contains only these validated closed entries; every value remains
untrusted and non-authorizing.

The converter never reads or trusts `$_SERVER`, `$_ENV`, `$_REQUEST`, `$_GET`,
`$_POST`, `$_COOKIE`, `$_FILES`, `getenv()`, `php://input`, arbitrary environment
variables, or an entire global/server dump. The concrete delivery alone projects
the closed `$_SERVER` allowlist, observes only whether `$_POST` and `$_FILES` are
empty, and opens `php://input` lazily during `request()`. No nested form or upload
value is retained or interpreted.

## Request limits

All counts and byte limits are checked before unbounded allocation or
application dispatch. These limits are architecture, not PHP INI mutation.

| Concern | Limit |
| --- | ---: |
| method bytes | 32 |
| request-target bytes | 8,192 |
| raw query bytes | 8,192 |
| query pairs | 128 |
| decoded query key bytes | 128 |
| decoded query value bytes | 4,096 |
| raw Cookie header bytes | 4,096 |
| cookie pairs | 64 |
| cookie name bytes | 128 |
| cookie value bytes | 1,024 |
| distinct request headers | 128 |
| one request header value bytes | 8,192 |
| total request header name/value bytes | 65,536 |
| request body bytes | 1,048,576 |

## Method, target, scheme, and authority

- Method is an uppercase ASCII HTTP token from one through 32 bytes and is
  preserved exactly.
- The request target is origin-form only: an absolute path beginning `/` plus
  optional query. Scheme, authority, userinfo, fragment, whitespace, control
  bytes, NUL, and invalid percent triplets reject.
- `QUERY_STRING` must byte-equal the content after `?` in `REQUEST_URI`, or be
  empty when the target contains no query delimiter.
- Scheme is `https` only when `HTTPS` is exactly `on`. Missing, empty, or
  exactly `off` means `http`; every other value rejects.
- `HTTP_HOST` is mandatory and is the sole authority source. There is no
  fallback to `SERVER_NAME`, forwarded headers, DNS, filesystem state, or a
  configured base URI.
- The existing first-party URI implementation performs final URI validation.

## Header projection

- `CONTENT_TYPE` and `CONTENT_LENGTH` project to `Content-Type` and
  `Content-Length`.
- Each `HTTP_` key becomes one field by removing `HTTP_`, lowercasing,
  replacing `_` with `-`, and title-casing every segment.
- `HTTP_CONTENT_TYPE` and `HTTP_CONTENT_LENGTH` reject as ambiguous duplicates.
- `HTTP_PROXY` rejects so a client `Proxy` field cannot become process proxy
  configuration.
- Each CGI value becomes one PSR header value and is never comma-split.
- CGI/FastCGI has already lost repeated-line and original-case information;
  this profile does not claim to preserve it.
- Names or values violating the existing HTTP Message contract, byte limits,
  CR/LF/NUL rules, or normalized-name uniqueness reject.
- `Forwarded` and `X-Forwarded-*` remain ordinary untrusted fields. They never
  affect scheme, authority, remote identity, or trusted diagnostics.

## Query projection

The profile parses `QUERY_STRING` independently and never uses `$_GET` or PHP
`parse_str` semantics.

- Split only on `&`; empty fields reject.
- A field is `name` or `name=value`; only the first `=` separates it.
- `+` decodes to space and valid `%HH` triplets decode to bytes.
- Decoded names are non-empty UTF-8 strings without NUL or control bytes.
- Decoded values are UTF-8 strings without NUL or control bytes.
- Bracket or nesting syntax rejects.
- Duplicate decoded names reject rather than overwrite or form arrays.
- The PSR query parameter value is a flat detached `array<string, string>`.

The initial profile intentionally excludes nested query structures,
ambiguous duplicates, and locale-dependent decoding.

## Cookie projection

The profile parses the single projected `Cookie` field independently and never
uses `$_COOKIE`.

- Split only on `;` and discard at most one optional space after a separator.
- Every non-empty pair requires `name=value`.
- Names are ASCII HTTP tokens.
- Values preserve bytes but must be visible ASCII excluding semicolon, comma,
  backslash, quote, DEL, and control bytes.
- Values are not percent-decoded.
- Duplicate names reject.
- The PSR cookie parameter value is a flat detached `array<string, string>`.

## Body, forms, and uploads

- `Transfer-Encoding` rejects because CGI must supply a decoded body and
  canonical length.
- Missing or empty `CONTENT_LENGTH` means exactly zero bytes.
- Present length is canonical unsigned decimal with no sign, leading plus,
  whitespace, or leading zero except the single value `0`.
- The declared length may not exceed 1,048,576 bytes.
- Read no more than the declared length from the caller-owned stream; early EOF rejects.
- Never read CGI extension data beyond `CONTENT_LENGTH`.
- The request body remains a readable first-party stream and `parsedBody`
  remains `null`.
- `multipart/form-data` rejects and uploaded files remain an empty array.
- `application/x-www-form-urlencoded` may remain raw body bytes but is not
  automatically parsed.

No upload movement, MIME sniffing, JSON parsing, form parsing, body
deserialization, content decoding, decompression, or streaming upload is
selected.

## Invalid-input outcome and generic-500 limitation

Every invalid, missing, ambiguous, oversized, unsupported, unreadable, or
failed caller input rejects through the implemented converter. The Phase 3F
delivery calls conversion from its `request()` method before `WebApplication::handle()`
and before a Kernel operation begins. Existing `WebRuntimeAdapter` then selects the fixed generic
500 response and still makes one send attempt and one close attempt.

Phase 3 deliberately adds no 400, 405, 413, 415, or 431 mapping and changes no
Web Runtime behavior. Malformed profile input therefore looks like a generic
server failure to the client. A later separately authorized protocol-error
model may add semantic client statuses without changing the accepted
fail-closed security default.

## Response limits

Phase 3D implements these limits.

| Concern | Limit |
| --- | ---: |
| response status | 200 through 599 |
| distinct response headers | 128 |
| values per response header | 32 |
| one response header value bytes | 8,192 |
| total response header name/value bytes | 65,536 |
| staged response body bytes | 1,048,576 |

## Response preflight

Before any SAPI emission call:

- `headers_sent()` must be false;
- status, protocol, headers, values, and body readability must validate;
- every body byte must be read into one package-owned bounded `php://temp`
  staging stream;
- a stalled, failed, oversized, or unreadable body rejects before commit;
- response field order and per-field value order are preserved;
- application-supplied `Status`, `Content-Length`, `Transfer-Encoding`,
  `Connection`, `Keep-Alive`, `Proxy-Authenticate`, `Proxy-Authorization`,
  `TE`, `Trailer`, and `Upgrade` reject as adapter-owned or hop-by-hop; and
- Kongono computes one exact `Content-Length` from staged bytes.

## Response emission sequence

After complete preflight:

1. Set internal state `commit_attempted` before the first SAPI emission call.
2. Set the exact response status through the SAPI.
3. Emit response fields in PSR order, replacing for the first value and using
   append semantics for later values of the same field.
4. Emit computed `Content-Length` exactly once.
5. For `HEAD`, emit no body bytes.
6. Otherwise copy all staged bytes to `php://output`; false, zero, short,
   warning, or thrown writes fail.
7. Set `completed` only after the full body is written.

A bounded locally installed warning-to-failure bridge may wrap only the
package's own SAPI calls. It restores the prior handler in `finally`, does not
swallow a prior-handler failure, and never leaves process-global error handling
changed.

## Committed-output uncertainty

Once `commit_attempted` is set, every failure is committed or uncertain even
if buffering or the web server may not yet have transmitted bytes. The adapter
does not call `header_remove()`, rewind host output, retry emission, emit a
second generic response, or assume rollback. It throws, Web Runtime retires,
and `close()` still runs exactly once. The host determines how to terminate the
FastCGI request or process.

Before `commit_attempted`, failure produces no SAPI output, but existing Web
Runtime still performs no second send attempt. This preserves one-send
ownership and avoids recursive fallback.

## Cleanup sequence

One fresh delivery instance owns its converted request body, captured response
body, and lazily opened native input. Its `close()`:

1. is called exactly once by Web Runtime;
2. detaches retained references before external cleanup;
3. closes each distinct request body, response body, and native input exactly
   once while continuing after a failure;
4. rethrows the first cleanup failure unchanged, except native close false uses
   `KGN-PHP-CGI-1018` with `input_close_failed`; and
5. retains no request, response, permission, input, or failure afterward.

No destructor, shutdown function, global registry, static current request,
container-current value, thread/fiber local, or process-wide mutable cache owns
cleanup.

## Trusted bootstrap and current-delivery boundary

- Bootstrap permission is one immutable operator decision loaded before the
  first delivery. `Production` maps to `Denied`; `LoopbackDevelopment` maps to
  `Allowed` only after all native prerequisites pass.
- Current-delivery permission is calculated fresh by the PHP-CGI evaluator from
  exact host evidence supplied for that delivery.
- Client query, path, header, cookie, form, body, route, session, or token data
  cannot enable or widen either permission.
- `HTTP_FORWARDED`, `HTTP_X_FORWARDED`, and every
  `HTTP_X_FORWARDED_*` key never establish trust; any such key forces
  current-delivery permission `Denied` regardless of value.
- Missing, invalid, ambiguous, proxied, failed, or uncertain evidence is
  `Denied`.
- Delivery `Allowed` is possible only when SAPI is exact `cgi-fcgi`, gateway is
  exact `CGI/1.1`, role is exact `RESPONDER`, and direct-origin `REMOTE_ADDR` is canonical loopback
  `127.0.0.1` or `::1` in a loopback-only non-proxied development deployment.
- Any configured proxy path or inability to prove direct-origin loopback forces
  denial.
- Production and public deployment guidance keeps trusted bootstrap denied.

Phase 3E implements the exact typed syntax and validation without a raw
environment flag, `.env` key, request flag, secret, token, cookie, or heuristic
permission source. Phase 3F's concrete delivery captures fresh evidence and its
thin entry hands that delivery to an already-composed Web Runtime.

## Diagnostics and disclosure

The Internal package catalog owns `KGN-PHP-CGI-1001` through
`KGN-PHP-CGI-1018`. Bootstrap failure uses only `KGN-PHP-CGI-1017`, its fixed
metadata, and one Safe closed `violation` token. Native input-close false uses
`KGN-PHP-CGI-1018` and only `input_close_failed`. Delivery denial is an expected
typed permission result and adds no diagnostic.

Messages and contexts exclude raw methods, targets, hosts, headers, cookies,
query values, bodies, paths, environment values, credentials, tokens, and host
messages. Only bounded structural tokens, counts, lengths, stages, and
violation categories approved by closed schemas may be captured. Failure detail
never enters generic production output, and `Safe` never means public.

Consumer PSR response getter/body `Throwable` values propagate as primary
delivery failures unless a later exact contract explicitly wraps a
package-owned validation failure. Arbitrary failures are not caught merely to
change their type.

## Persistent-process isolation

The host may reuse a PHP interpreter for later FastCGI requests, and the
implemented entry creates a fresh delivery instance each time. No request, response,
permission, route, exception, stream, or application value may survive the
delivery. Process reuse never changes the rule that one interpreter handles at
most one Kongono delivery at a time.

Phase 3H must prove repeated requests through one reused PHP process where the
installed `php-cgi` binary supports it. Every completed delivery must end with
zero active Kernel operations and no leaked state.

## External acceptance matrix

The Phase 3H harness runs an operator-supplied `php-cgi` binary in FastCGI
listener mode and drives it with a test-owned FastCGI client from outside the
production package. The harness is test infrastructure, never production code
or a supported server.

| Area | Required external proof |
| --- | --- |
| Admission | Exact `cgi-fcgi`, CGI/1.1, and `RESPONDER` admission; rejection of every other identity |
| Application flow | Matched and unmatched responses plus the fixed generic application failure |
| Trusted diagnostics | Explicit development bootstrap and direct loopback allow; bootstrap denial, delivery denial, forwarded/proxy uncertainty, and non-loopback denial before protected source access |
| Input grammar | Method, protocol, target, query, host, header, cookie, length, body, form, upload, and profile rejection |
| Request bounds | Boundary-minus-one, boundary, and boundary-plus-one for every meaningful request byte/count limit |
| Response bounds | Boundary-minus-one, boundary, and boundary-plus-one for every meaningful response byte/count limit |
| Emission | Exact status, repeated-field order, per-field value order, computed content length, body, and `HEAD` suppression |
| Disclosure | No raw rejected value or secret sentinel in generic or trusted output |
| Failure state | Pre-commit validation failure and post-commit/uncertain emission failure |
| Cleanup | One send and one close attempt with correct primary-plus-cleanup behavior |
| Kernel | Zero active operations after every completed delivery |
| Reuse | Repeated FastCGI requests through one reused PHP process without state leakage when supported |
| Dependency boundary | No production dependency on the FastCGI test harness or protocol parser |

## Supported and unsupported surface

| Supported by the selected profile | Explicitly unsupported or separate future profile |
| --- | --- |
| PHP SAPI `cgi-fcgi` | PHP-FPM `fpm-fcgi` |
| FastCGI `RESPONDER` | Other FastCGI roles |
| CGI/1.1 meta-variable and response contract | Apache or IIS-specific module behavior |
| One synchronous delivery per interpreter | Concurrent deliveries in one interpreter |
| Host-owned listener and process management | Kongono listener, socket server, process manager, worker pool, or scheduler |
| Delivery-owned closed native projection and bounded lazy input stream | Unselected superglobals, `getenv()`, nested form/upload values, or global dumps |
| Origin-form targets and `HTTP_HOST` authority | Absolute-form, authority-form, asterisk-form, forwarded authority, DNS, or configured base URI |
| Flat query and cookie maps | Nested queries, duplicate names, PHP `parse_str`, automatic forms, or multipart uploads |
| Bounded fully read request bodies | Streaming uploads, decompression, MIME sniffing, JSON/form deserialization |
| Bounded staged response bodies | Streaming responses, ranges, sendfile, SSE, WebSocket |
| HTTP protocol labels mapped to PSR versions | HTTP/2 or HTTP/3 wire framing |
| Generic 500 for conversion rejection | Semantic 400/405/413/415/431 mapping |
| Typed development bootstrap plus independent direct canonical-loopback delivery evidence under Phase 3E | Proxy-derived trust or public trusted diagnostics |
| Test-owned external FastCGI harness | Production FastCGI client/parser dependency |

Also unsupported are `cli-server`, CLI, RoadRunner, FrankenPHP, OpenSwoole,
ReactPHP, Amp, Workerman, arbitrary PSR-7 adapters, proxy trust, TLS
termination, authentication, authorization, sessions, CSRF, rate limiting,
incidents, logging, telemetry, tracing, metrics, secrets management, and a
general configuration system.

## Performance consequences

The first profile chooses bounded full buffering at both sides of the
application boundary. It reads at most 1,048,576 request-body bytes into a
first-party stream and stages at most 1,048,576 response-body bytes before
commit. This enables complete validation before application dispatch or SAPI
emission and makes exact `Content-Length` deterministic.

The tradeoff is per-delivery memory, byte copying, and latency proportional to
the accepted bodies. A host process must budget for those buffers plus the
application's own memory. The profile makes no streaming throughput, zero-copy,
sendfile, range, or large-upload claim. One-delivery-at-a-time execution limits
concurrency to the operator-managed process count.

## Related material

- [ADR 0052: PHP cgi-fcgi FastCGI Responder Profile](../adr/0052-php-cgi-fastcgi-responder-profile.md)
- [ADR 0051: Phase 3 Production HTTP Integration Milestone](../adr/0051-phase-3-production-http-integration-milestone.md)
- [Phase 3 roadmap](phase-3-roadmap.md)
- [Web Runtime model](web-runtime-model.md)
- [HTTP message model](http-message-model.md)
- [Security policy](../../SECURITY.md)
- [RFC 3875: CGI/1.1](https://www.rfc-editor.org/rfc/rfc3875)
- [PHP `$_SERVER`](https://www.php.net/manual/en/reserved.variables.server.php)
- [PHP `php://input`](https://www.php.net/manual/en/wrappers.php.php)
- [PHP `header()`](https://www.php.net/manual/en/function.header.php)
- [PHP `headers_sent()`](https://www.php.net/manual/en/function.headers-sent.php)

## Phase 3F delivery composition

The profile now has an Internal concrete delivery and Experimental thin entry.
Only the closed server allowlist and `HTTP_` keys are projected; non-string keys
become a fixed invalid sentinel, and form/upload state is retained only as
booleans. The native body opens in binary mode during `request()`, after the
delivery permission decision. One emitter call uses the validated method, with
an exact raw `HEAD` as the only safe pre-conversion `HEAD` fallback. Cleanup
detaches and closes distinct request, response, and native-input resources.
`Production` remains denied; development trust still needs exact non-forwarded
loopback evidence. Phase 3G adds one stateless front-controller entry attempt
followed by one explicit supplied application-shutdown attempt. The four
outcomes preserve either failure or both through 1019 without retry or a second
response. Application construction, correct runtime/closure pairing, and
partial-boot cleanup remain caller-owned; PHP CGI still has no direct Web
Application edge. Uncertain emission or shutdown requires external process
retirement. The [deployment contract](../engineering/php-cgi-deployment.md)
adds no production listener, server configuration, or deployment artifact.
Phase 3H supplies external test-owned proof through the operator-supplied
listener and closes Phase 3.

Intrinsic `QUERY_STRING` validation retains the 8,192-byte source ceiling. A
matching nonempty query must occur after `/?` in the complete 8,192-byte-capped
target, so the effective external maximum is 8,190 bytes. Acceptance proves
8,189 and 8,190 bytes accepted and 8,191 rejected as `target_too_long`; it does
not claim isolated external reachability of the intrinsic query check.
