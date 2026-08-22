# ADR 0052: PHP cgi-fcgi FastCGI Responder Profile

Status: Accepted

Date: 2026-08-20

## Context

Phase 2 proves Kongono's thirteen-package web path through a root-owned test
fixture. [ADR 0051](0051-phase-3-production-http-integration-milestone.md)
accepts Phase 3 as the bounded path from that fixture to one supported
synchronous production HTTP integration profile. Phase 3A selected no host,
package, raw-input contract, emitter, configuration mechanism, or deployment
entrypoint.

Phase 3B must select one exact host boundary before conversion or emission code
exists. The selection must preserve the existing ownership chain: Web Runtime
coordinates one delivery and one send/close attempt, Web Application owns the
sole Kernel operation scope, and Kernel remains unaware of HTTP, CGI, FastCGI,
SAPIs, listeners, and deployment.

## Decision

Select one profile only: PHP SAPI identity `cgi-fcgi`, FastCGI `RESPONDER`
role, and the CGI/1.1 gateway contract. One host invocation runs one PHP front
controller for one synchronous request, and one PHP interpreter handles at
most one Kongono delivery at a time. A host may reuse the interpreter for later
requests, but no delivery-local request, response, permission, exception,
stream, or application value crosses that boundary.

Phase 3B is architecture and documentation only. It creates no package,
production PHP, test, manifest, configuration, front controller, listener,
parser, emitter, or runtime behavior. Phase 3C is next and owns only the
accepted bounded inbound conversion.

The authoritative detailed contract is the [PHP CGI FastCGI
profile](../architecture/php-cgi-fastcgi-profile.md).

## Rationale

The `cgi-fcgi` Responder profile gives Kongono a narrow host-provided execution
edge with documented CGI request meta-variables, raw input, and response-header
semantics. It does not require Kongono to parse FastCGI records, own a socket,
or manage PHP processes. Selecting one synchronous delivery at a time also
preserves the implemented Web Runtime and Web Application lifecycle model
without introducing concurrency or ambient current-request state.

## Selected host profile

Admission requires all three exact facts:

```text
PHP_SAPI === "cgi-fcgi"
GATEWAY_INTERFACE === "CGI/1.1"
FCGI_ROLE === "RESPONDER"
```

The CGI host may expose only `HTTP/1.0`, `HTTP/1.1`, `HTTP/2`, `HTTP/2.0`,
`HTTP/3`, or `HTTP/3.0`, mapped respectively to PSR message protocol versions
`1.0`, `1.1`, `2`, `2.0`, `3`, and `3.0`. The web server owns client-side
protocol framing, transfer-coding removal, TLS, and HTTP-to-CGI/FastCGI
conversion. Kongono parses no HTTP wire bytes.

The operator or web server owns the listener, socket, connection, process
count, timeout, restart, privileges, deployment, and process lifecycle.
Kongono neither speaks FastCGI records nor supplies a listener or process
manager.

The later bootstrap boundary must reject the profile before serving unless
`enable_post_data_reading=0`, `output_buffering=0`,
`zlib.output_compression=0`, `display_errors=0`,
`display_startup_errors=0`, `html_errors=0`, `expose_php=0`, empty
`auto_prepend_file`, empty `auto_append_file`, and `cgi.rfc2616_headers=0` are
all established. Phase 3B changes no PHP configuration. Phase 3E will select
the exact operator-profile syntax, location, loading API, and validation.

The FastCGI endpoint must be reachable only by the operator-selected web
server or acceptance harness, never by untrusted clients. This decision does
not select a web server, proxy trust, TLS termination, authentication, or
authorization.

## Package and dependency direction

Reserve, but do not create:

```text
directory: packages/php-cgi
Composer:  kongono/php-cgi
namespace: Kongono\PhpCgi\
role:      outer leaf implementing the selected host profile
```

The future leaf may depend only on PHP `^8.5`, `kongono/api-metadata`,
`kongono/diagnostic-activation`, `kongono/diagnostics`,
`kongono/http-message`, `kongono/web-runtime`, and `psr/http-message ^2.0`.
It must not depend directly on Kernel, Routing, HTTP Server, Web Application,
Application Graph, Dependency Injection, Diagnostic Presentation, Diagnostic
Handler, a web server, a FastCGI library, or another framework.

```text
host/web server
    -> future kongono/php-cgi leaf
        -> kongono/web-runtime
            -> existing application and lower packages
```

No lower package may depend on `kongono/php-cgi`. Kernel remains unaware of
HTTP, CGI, FastCGI, superglobals, headers, emission, and deployment.

## Inbound policy

The future profile constructs a request only from a closed validated
projection of `$_SERVER` and raw `php://input`. It excludes `$_ENV`,
`$_REQUEST`, `$_GET`, `$_POST`, `$_COOKIE`, `$_FILES`, `getenv()`, arbitrary
environment values, and whole global/server dumps. It may inspect only whether
`$_POST` and `$_FILES` are empty; any entry is a profile violation and no
nested value is retained or interpreted.

Required string meta-variables are `GATEWAY_INTERFACE`, `FCGI_ROLE`,
`REQUEST_METHOD`, `SERVER_PROTOCOL`, `REQUEST_URI`, `QUERY_STRING`,
`HTTP_HOST`, and `REMOTE_ADDR`. Optional closed scalar inputs are
`CONTENT_LENGTH`, `CONTENT_TYPE`, `HTTPS`, `REMOTE_PORT`, `SERVER_ADDR`,
`SERVER_NAME`, `SERVER_PORT`, `SERVER_SOFTWARE`, `SCRIPT_NAME`, and `HTTP_*`.
Unknown shapes, missing required values, arrays, objects, resources, ambiguous
normalization, or invalid values reject before application dispatch.

The method, origin-form target, scheme, authority, header, query, cookie,
body, form, upload, and protocol contracts and all exact limits are locked in
the detailed profile. The resulting server parameters contain only the closed
validated projection and remain untrusted and non-authorizing.

Every invalid, missing, ambiguous, oversized, unsupported, unreadable, or
failed host input fails before `WebApplication::handle()` and before a Kernel
operation begins. Conversion failure occurs in the implemented delivery's
`request()` call so the existing `WebRuntimeAdapter` selects its fixed generic
500 response and still attempts one send and one close. Phase 3 adds no 400,
405, 413, 415, or 431 mapping.

## Emission and uncertainty policy

Phase 3D will implement the accepted response contract. Before any SAPI output,
the future adapter must prove headers are uncommitted, validate the complete
status/protocol/header surface, and stage the entire response body into one
package-owned bounded `php://temp` stream. It computes one exact
`Content-Length`; rejects application-supplied adapter-owned and hop-by-hop
fields; preserves header and value order; and suppresses body bytes for `HEAD`.

Internal state becomes `commit_attempted` before the first SAPI call. After
that point every failure is committed or uncertain, even if buffering or the
web server may not yet have transmitted bytes. The adapter never removes
headers, rewinds host output, retries emission, or sends a second generic
response. It throws, Web Runtime retires, and `close()` still runs exactly
once. Before commitment, failure emits no SAPI output, but Web Runtime still
performs no second send attempt.

Cleanup is explicitly owned by one delivery instance. It closes owned request,
response, and staging streams once; releases references even when close fails;
retains no delivery values afterward; and follows Web Runtime's existing
primary-plus-cleanup composition. No destructor, shutdown function, global
registry, ambient current request, thread/fiber local, or process-wide mutable
cache owns cleanup.

## Trust and security

Bootstrap permission is one immutable operator decision loaded before the first
delivery. Current-delivery permission is recalculated by the PHP-CGI adapter
from host evidence it exclusively receives for that delivery. Client query,
path, header, cookie, form, body, route, session, or token data cannot enable or
widen either permission. `Forwarded` and `X-Forwarded-*` never establish trust;
their presence forces current-delivery permission `Denied` in this profile.

Missing, invalid, ambiguous, proxied, failed, or uncertain evidence is
`Denied`. A future Phase 3E profile may allow trusted diagnostics only when it
explicitly enables trusted developer bootstrap and proves a direct,
non-proxied canonical loopback `REMOTE_ADDR` of `127.0.0.1` or `::1` in a
loopback-only development deployment. Any configured proxy path or inability
to prove direct-origin loopback forces denial. Production and public guidance
keeps bootstrap denied.

Future package diagnostics use the reserved domain `KGN-PHP-CGI-NNNN`; Phase
3B reserves no individual number or catalog. Failure messages and diagnostic
context exclude raw methods, targets, hosts, headers, cookies, query values,
bodies, paths, environment values, credentials, tokens, and host messages.
Only bounded structural tokens, counts, lengths, stages, and closed violation
categories may be considered by later exact schemas. Generic production output
never exposes failure detail, and `Safe` never means automatically public.

## API stability

Every necessary future calling surface is Experimental. Host-specific helpers
and implementation details are Internal. Phase 3 creates no new Public,
Stable, or Deprecated API and does not change the existing Public
`WebDeliveryInterface` signature. Phase 3C will select exact inbound class and
method signatures; Phase 3B invents no placeholder PHP symbol.

## Consequences

The first production profile has deterministic bounded memory and input
behavior, but it intentionally buffers the complete request body up to
1,048,576 bytes and the complete staged response body up to 1,048,576 bytes.
This simplifies validation-before-dispatch and validation-before-commit at the
cost of per-delivery memory, copying, and latency. Streaming requests,
streaming responses, multipart uploads, and bodies above those limits are not
supported by this profile.

Malformed profile input currently becomes the fixed generic 500 because the
existing Web Runtime has no semantic protocol-error model. That limitation is
intentional and visible; a later separately authorized model may add client
error statuses without weakening this fail-closed default.

Phase 3H must prove the actual `php-cgi` FastCGI listener profile from outside
the production package through a test-owned client/harness. The harness never
becomes production code or a supported server.

## Rejected alternatives

- PHP-FPM `fpm-fcgi`, Apache and IIS modules, `cli-server`, CLI, RoadRunner,
  FrankenPHP, OpenSwoole, ReactPHP, Amp, Workerman, and arbitrary PSR-7
  adapters are rejected from this first profile; each requires a separate
  future profile.
- A Kongono FastCGI record parser, listener, socket server, process manager,
  worker pool, or scheduler is rejected because the host owns those concerns.
- Reading broad superglobals or environment state is rejected because input
  ownership and trust must be closed and inspectable.
- PHP `parse_str`, automatic forms, multipart uploads, MIME inference,
  decompression, and body deserialization are rejected from the first inbound
  profile because they introduce ambiguity and broader resource policy.
- Emitting before complete bounded preflight or attempting rollback after a
  SAPI call is rejected because commitment becomes uncertain.
- Client-controlled debug activation, forwarded-header trust, and proxy
  inference are rejected because trust must fail closed.
- New semantic 4xx mappings in Phase 3B are rejected because Web Runtime's
  existing generic failure contract is unchanged.

## What remains open

Phase 3C must select and implement only the exact inbound PHP symbols and
bounded conversion within this decision. Phase 3D owns response emission.
Phase 3E owns exact operator-profile syntax, loading, validation, and trust
input composition. Phase 3F owns production composition and entry. Phase 3G
owns integration hardening and deployment constraints. Phase 3H owns external
acceptance and the checkpoint.

Proxy trust, TLS termination, authentication, authorization, sessions, CSRF,
rate limiting, incidents, logging, telemetry, recovery, general configuration,
secrets, broader runtime profiles, streaming, multipart uploads, semantic
protocol errors, and every non-Phase-3 framework capability remain separately
authorized future work.

## Related material

- [PHP CGI FastCGI profile](../architecture/php-cgi-fastcgi-profile.md)
- [ADR 0051: Phase 3 Production HTTP Integration Milestone](0051-phase-3-production-http-integration-milestone.md)
- [Phase 3 roadmap](../architecture/phase-3-roadmap.md)
- [Web Runtime model](../architecture/web-runtime-model.md)
- [HTTP message model](../architecture/http-message-model.md)
- [Security policy](../../SECURITY.md)
- [RFC 3875: CGI/1.1](https://www.rfc-editor.org/rfc/rfc3875)
- [PHP `$_SERVER`](https://www.php.net/manual/en/reserved.variables.server.php)
- [PHP `php://input`](https://www.php.net/manual/en/wrappers.php.php)
- [PHP `header()`](https://www.php.net/manual/en/function.header.php)
- [PHP `headers_sent()`](https://www.php.net/manual/en/function.headers-sent.php)

## Phase 3F implementation

The selected profile now has one concrete Internal delivery and an Experimental
thin entry. Capture selects only the profile's required and optional CGI keys
plus `HTTP_` keys, represents form/upload presence without values, and opens
`php://input` lazily during request conversion. `Production` continues to deny
trusted output. `LoopbackDevelopment` continues to require the independent
literal bootstrap permission and exact direct-loopback, non-forwarded delivery
evidence.

## Phase 3G reconciliation

ADR 0057 preserves this exact profile and adds one bounded front-controller
entry attempt plus one explicit supplied application-shutdown attempt. The four
outcomes are deterministic; 1019 preserves both failures. The deployment guide
records the fixed PHP prerequisites and external-host retirement responsibility
without adding another SAPI, listener, server configuration, deployment
artifact, or external proof. `Production` remains denied, and PHP CGI gains no
direct Web Application edge. Phase 3H alone is next.

## Phase 3H outcome

Phase 3H externally proves the unchanged PHP `cgi-fcgi`, CGI/1.1, FastCGI
`RESPONDER` profile with a bounded test-owned client and an operator-supplied
listener. Intrinsic `QUERY_STRING` validation remains capped at 8,192 bytes;
the matching origin-form target ceiling makes 8,190 bytes the effective
externally representable query maximum. Production trusted diagnostics remain
denied, no production package depends on the harness, and no general listener,
client, process manager, deployment, or hardening support follows.
