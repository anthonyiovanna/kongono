# CODEX IMPLEMENTATION DIRECTIVE - KONGONO PHASE 3B

## PHP `cgi-fcgi` FastCGI Responder Production-Profile Architecture

**Directive type:** Architecture and documentation only

**Decision semantics:** LOCKED where stated

**Executor role:** Executor, not architect

**Date:** 2026-08-20

**Implementation phase after acceptance:** Phase 3C

---

## 1. Mission

Phase 3B selects the first exact production host profile for Kongono without
implementing it.

The selected profile is:

```text
PHP cgi-fcgi SAPI
FastCGI Responder role
CGI/1.1 request meta-variable and response semantics
one synchronous delivery at a time per PHP interpreter
host-owned listener and process management
```

Phase 3B must:

1. record the selection in one Accepted ADR;
2. define one precise architecture model for the future profile;
3. reserve the future `kongono/php-cgi` leaf package and
   `Kongono\PhpCgi\` namespace;
4. lock dependency direction, API status policy, input sources, resource
   limits, rejection behavior, response-emission semantics, trust boundaries,
   cleanup ownership, and external acceptance surface;
5. reconcile current documentation so Phase 3B is selected but not
   implemented; and
6. preserve every accepted Phase 2 and Phase 3A production, test, dependency,
   tooling, lockfile, and Git-history byte.

Phase 3B creates no production package, PHP source, test, manifest, dependency,
front controller, configuration file, server file, listener, parser, emitter,
or runtime behavior.

Phase 3C remains the first implementation phase and is limited to the accepted
inbound request boundary.

---

## 2. Authority and authentication

### 2.1 Required authority reading

Before mutation, read completely:

- `AGENTS.md`
- `ARCHITECTURE.md`
- `SECURITY.md`
- `docs/doctrine.md`
- `docs/documentation-standard.md`
- `docs/engineering/error-handling.md`
- `docs/engineering/package-layout.md`
- `docs/architecture/http-message-model.md`
- `docs/architecture/runtime-model.md`
- `docs/architecture/web-application-model.md`
- `docs/architecture/web-runtime-model.md`
- `docs/architecture/phase-2-runnable-fixture.md`
- `docs/architecture/phase-3-roadmap.md`
- `docs/adr/0001-kongono-identity.md`
- `docs/adr/0003-monorepo-initial-development.md`
- `docs/adr/0013-documentation-doctrine.md`
- `docs/adr/0014-php-engineering-toolchain.md`
- `docs/adr/0016-strict-types-and-per-cs-style.md`
- `docs/adr/0026-actionable-diagnostics-and-error-boundaries.md`
- `docs/adr/0028-bounded-diagnostic-context-and-disclosure.md`
- `docs/adr/0031-neutral-api-metadata-ownership-and-acyclic-package-dependencies.md`
- `docs/adr/0038-fail-closed-trusted-developer-diagnostic-activation.md`
- `docs/adr/0039-typed-trusted-developer-bootstrap-permission-foundation.md`
- `docs/adr/0043-phase-2-runnable-web-framework-milestone.md`
- `docs/adr/0044-http-message-and-interoperability-architecture.md`
- `docs/adr/0049-outer-web-runtime-and-safe-failure-delivery.md`
- `docs/adr/0050-phase-2-runnable-fixture-and-checkpoint.md`
- `docs/adr/0051-phase-3-production-http-integration-milestone.md`
- `packages/http-message/src/Factory/ServerRequestFactory.php`
- `packages/http-message/src/Factory/StreamFactory.php`
- `packages/http-message/src/Message/ServerRequest.php`
- `packages/web-runtime/src/Contract/WebDeliveryInterface.php`
- `packages/web-runtime/src/WebRuntimeAdapter.php`
- `packages/web-application/src/WebApplication.php`
- `tests/BlackBox/Fixture/FixtureDelivery.php`
- `docs/dev_plan/kongono_phase_3A_codex_directive.md`
- `docs/task reports/Phase_2H_final_closure.md`
- `docs/task reports/Phase_3A.md`
- this directive; and
- the zero-byte `docs/task reports/Phase_3B.md` placeholder.

Do not substitute similarly numbered or similarly named authorities.

### 2.2 Independent directive identity

The architect must supply this directive's byte count and SHA-256 outside this
file. Compare the independently supplied values with the local file before any
quality command or mutation.

If either differs:

```text
STOP
REPORT EXPECTED AND OBSERVED VALUES
PRESERVE WORKTREE, INDEX, HISTORY, AND CALLER RECORDS
DO NOT GUESS OR SELF-AUTHENTICATE
```

### 2.3 Locked caller records

Require these exact identities:

| Path | Bytes | SHA-256 |
| --- | ---: | --- |
| `docs/dev_plan/kongono_phase_3A_codex_directive.md` | 36,848 | `FD7CB6F72B8C9DF2FD41174C137EDA876A139118B8299870AD9B65D58A8682EA` |
| `docs/task reports/Phase_2H_final_closure.md` | 16,240 | `F187072B7B69327BB2129152AFF1114B36F6D2611A4E986CB06F95955341C245` |
| `docs/task reports/Phase_3A.md` | 19,513 | `28B462AE7066F8E4C783901FC13879A47925B62B707979E53E84B4AA0F4FDB88` |
| `docs/adr/0051-phase-3-production-http-integration-milestone.md` | 13,468 | `B199B14CF306B5869ADB1AA2A3617FA06D0B22DEF16744F496705FA3732CDD2F` |
| `docs/architecture/phase-3-roadmap.md` | 11,601 | `14DF511EED770DB321D1AE243C6A0388E4271064B06E82669AC9A3405B32A3BD` |
| `composer.json` | 4,299 | `99A8B23827C0C5C691EE68A19A9ACD20543ABAF2AE4B7C97DF0FCA0A9B6EC76C` |
| `composer.lock` | 196,324 | `28B03AFDC8FF9C6F2F754E65B97C867E499BAD2D4427E0F1881FBBA88E1E6862` |

`docs/task reports/Phase_3B.md` must initially be an ordinary, untracked,
unstaged, zero-byte file absent from `HEAD`, with SHA-256:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

Populate it only after every non-report criterion passes.

---

## 3. Locked repository entry state

Use:

```powershell
git -c safe.directory='C:/Users/antho/Dev/kongono' ...
```

The accepted checkpoint is:

```text
branch:         main
HEAD:           647053d4bddc4c76090bca9bf3d693faa5880c52
tree:           3ad60da1dc535fc0b7a166ac8522f954d4c67fac
parent:         d7846f137a1d53f5306f48d19196b916fde44ac2
commit count:   10
tracked files:  536
local branches: 1 (main)
remotes:        0
tags:           0
staged paths:   0
```

After the caller copies this directive and creates the Phase 3B placeholder,
status must be exactly:

```text
16 modified tracked paths
7 untracked paths
23 total status paths
0 staged paths
```

The sixteen modified paths must be exactly:

```text
ARCHITECTURE.md
README.md
SECURITY.md
composer.json
docs/adr/README.md
docs/architecture/http-message-model.md
docs/architecture/overview.md
docs/architecture/phase-2-roadmap.md
docs/architecture/routing-model.md
docs/doctrine.md
docs/engineering/package-layout.md
docs/engineering/toolchain.md
docs/glossary.md
packages/diagnostic-handler/README.md
packages/http-server/README.md
packages/kernel/README.md
```

The seven untracked paths must be exactly:

```text
docs/adr/0051-phase-3-production-http-integration-milestone.md
docs/architecture/phase-3-roadmap.md
docs/dev_plan/kongono_phase_3A_codex_directive.md
docs/dev_plan/kongono_phase_3B_codex_directive.md
docs/task reports/Phase_2H_final_closure.md
docs/task reports/Phase_3A.md
docs/task reports/Phase_3B.md
```

If the identity, inventory, modes, ordinary-file state, alternate streams, or
caller records differ, stop before broad validation or mutation.

The inaccessible user-global Git ignore warning is environmental only after
the explicit closed inventories above match.

---

## 4. Executor discipline

Implement the architecture selected here. Do not redesign it.

Stop for a genuine architectural, security, package, API, dependency, protocol,
or accepted-state contradiction. Do not stop merely because a read-only helper
script has a quoting, parser, path, or predicate defect; simplify the helper and
prove the same condition directly.

Within the exact documentation boundary, the executor may correct a discovered
link, heading, wording, formatting, encoding, or stale-current-state defect and
rerun the focused check. This bounded correction authority allows at most two
focused correction cycles and does not authorize a new decision, file, or scope.

No retry is permitted for an architectural mismatch. One identical approved-
access retry is permitted only for an environmental Composer network/cache
failure.

---

## 5. Exact file boundary

### 5.1 Create exactly

```text
docs/adr/0052-php-cgi-fastcgi-responder-profile.md
docs/architecture/php-cgi-fastcgi-profile.md
```

### 5.2 Modify only

```text
README.md
ARCHITECTURE.md
SECURITY.md
docs/adr/README.md
docs/architecture/http-message-model.md
docs/architecture/overview.md
docs/architecture/phase-3-roadmap.md
docs/doctrine.md
docs/engineering/package-layout.md
docs/engineering/toolchain.md
docs/glossary.md
```

### 5.3 Caller-owned report

Populate only after all other gates pass:

```text
docs/task reports/Phase_3B.md
```

### 5.4 Closed outside scope

Do not create or modify any other path. In particular, do not touch:

- production or test PHP;
- any package manifest or root Composer field;
- `composer.lock`;
- PHPUnit, PHPStan, PHP CS Fixer, autoload, or security-policy configuration;
- the Phase 2 fixture;
- any historical ADR, directive, report, commit, tree, ref, branch, tag, remote,
  hook, signing setting, index entry, or Git configuration; or
- `.env`, server, web-server, FastCGI, deployment, front-controller, container,
  CI, or application-template files.

Relative to the authenticated Phase 3B entry snapshot, the six accepted dirty
paths not listed in Section 5.2 must remain byte-identical:

```text
composer.json
docs/architecture/phase-2-roadmap.md
docs/architecture/routing-model.md
packages/diagnostic-handler/README.md
packages/http-server/README.md
packages/kernel/README.md
```

Do not stage or commit Phase 3B.

---

## 6. Locked profile selection

ADR 0052 must have:

```text
Status: Accepted
Date: 2026-08-20
```

Record this exact selected profile:

### 6.1 Host execution contract

- PHP SAPI identity is exactly `cgi-fcgi`.
- FastCGI role is exactly `RESPONDER`.
- CGI gateway contract is exactly `CGI/1.1`.
- The host invokes one PHP front controller for one synchronous request.
- One PHP interpreter handles at most one Kongono delivery at a time.
- A host may reuse the interpreter process for later requests, but request,
  response, permission, exception, stream, and application objects never cross
  delivery boundaries.
- The web server or operator owns the FastCGI listener, socket, connection,
  timeout, process count, restart, privilege, and deployment lifecycle.
- Kongono does not implement or speak FastCGI records and does not own a
  listener or process manager.

Profile admission requires all of:

```text
PHP_SAPI === "cgi-fcgi"
GATEWAY_INTERFACE === "CGI/1.1"
FCGI_ROLE === "RESPONDER"
```

The selected profile does not include PHP-FPM (`fpm-fcgi`), Apache module,
IIS-specific module behavior, `cli-server`, CLI, RoadRunner, FrankenPHP,
OpenSwoole, ReactPHP, Amp, Workerman, a Kongono socket server, or an arbitrary
PSR-7 adapter. Those remain separate future profiles.

### 6.2 Supported protocol exposure

The CGI host may report only:

```text
HTTP/1.0
HTTP/1.1
HTTP/2
HTTP/2.0
HTTP/3
HTTP/3.0
```

Map them respectively to the existing PSR message protocol versions:

```text
1.0
1.1
2
2.0
3
3.0
```

The web server remains responsible for client-side protocol framing,
transfer-coding removal, TLS, and conversion between HTTP and CGI/FastCGI.
Kongono never parses HTTP wire bytes in this profile.

### 6.3 Required operator conditions

Before the first delivery, the later bootstrap boundary must validate at least:

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

These are selected profile prerequisites, not Phase 3B configuration changes.
The exact operator profile syntax, file location, loading API, and deployment
command remain Phase 3E decisions.

The FastCGI endpoint must be inaccessible to untrusted clients and reachable
only by the operator-selected web server or acceptance harness. Phase 3B does
not select proxy trust, TLS termination, authentication, authorization, or a
specific web server.

---

## 7. Future package boundary

Reserve but do not create:

```text
directory: packages/php-cgi
Composer:  kongono/php-cgi
namespace: Kongono\PhpCgi\
role:      outer leaf implementing the selected host profile
```

The future package may depend only on:

```text
kongono/api-metadata
kongono/diagnostic-activation
kongono/diagnostics
kongono/http-message
kongono/web-runtime
php ^8.5
psr/http-message ^2.0
```

It must not depend directly on Kernel, Routing, HTTP Server, Web Application,
Application Graph, Dependency Injection, Diagnostic Presentation, Diagnostic
Handler, a web server, a FastCGI library, or another framework.

The dependency direction is:

```text
host/web server
    -> future kongono/php-cgi leaf
        -> kongono/web-runtime
            -> existing application and lower packages
```

No lower package may depend on `kongono/php-cgi`. Kernel remains unaware of
HTTP, CGI, FastCGI, superglobals, headers, emission, and deployment.

All necessary future calling surfaces are `Experimental`. Helpers and host-
specific implementation details are `Internal`. Phase 3 creates no new
`Public`, `Stable`, or `Deprecated` API and does not change the existing Public
`WebDeliveryInterface` signature.

Phase 3C will select exact inbound class names and signatures within this
boundary. Phase 3B does not invent placeholder PHP symbols.

---

## 8. Locked inbound request contract

The profile constructs request values only from a closed projection of
`$_SERVER` and the raw `php://input` stream. It must not use or trust `$_ENV`,
`$_REQUEST`, `$_GET`, `$_POST`, `$_COOKIE`, `$_FILES`, `getenv()`, arbitrary
environment variables, or an entire server/global dump as request content.

It may inspect only whether `$_POST` and `$_FILES` are empty. They must be empty
under the required `enable_post_data_reading=0` profile; any entry is a profile
violation, and no nested value may be retained or interpreted.

### 8.1 Required request meta-variables

Require string values for:

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

Optional closed scalar inputs are:

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

Array, object, resource, duplicate-normalized, unknown-shape, missing-required,
or invalid values reject the request. Never retain unrelated server/environment
entries.

The resulting PSR-7 server-parameter snapshot contains only the validated
closed values above. Every value remains untrusted and non-authorizing.

### 8.2 Request limits

Lock these initial limits:

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

Counts and byte limits are checked before unbounded allocation or application
dispatch. Limits are architecture, not PHP INI mutation.

### 8.3 Method and target

- Method is an uppercase ASCII HTTP token, one to 32 bytes, preserved exactly.
- Request target is origin-form only: an absolute path beginning `/` plus an
  optional query; no scheme, authority, userinfo, fragment, whitespace, control
  byte, NUL, or invalid percent triplet.
- `QUERY_STRING` must exactly equal the bytes following `?` in `REQUEST_URI`, or
  be empty when no query delimiter exists.
- Scheme is `https` only when `HTTPS` is exactly `on`; absent, empty, or exactly
  `off` means `http`; every other value rejects.
- `HTTP_HOST` is mandatory and supplies the authority. Do not fall back to
  `SERVER_NAME`, forwarded headers, DNS, filesystem, or a configured base URI.
- The existing Kongono URI implementation performs final URI validation.

### 8.4 Header projection

- `CONTENT_TYPE` and `CONTENT_LENGTH` become `Content-Type` and
  `Content-Length`.
- Each `HTTP_` key becomes one deterministic canonical field name by removing
  `HTTP_`, lowercasing, replacing `_` with `-`, then title-casing each segment.
- Reject `HTTP_CONTENT_TYPE` or `HTTP_CONTENT_LENGTH` as ambiguous duplicates.
- Reject `HTTP_PROXY`; a client `Proxy` header must never become process proxy
  configuration.
- Each CGI value is one PSR header value. Never comma-split a CGI value.
- CGI/FastCGI has already lost original repeated-line and casing information;
  this profile makes no claim to preserve it.
- Reject names or values that violate the existing HTTP Message contract,
  limits, CR/LF/NUL rules, or normalized-name uniqueness.
- `Forwarded` and `X-Forwarded-*` remain ordinary untrusted fields and never
  influence scheme, authority, remote identity, or trusted diagnostics.

### 8.5 Query projection

Parse `QUERY_STRING` independently; do not use PHP's parsed `$_GET`.

- Split only on `&`.
- Empty fields reject.
- Each field is `name` or `name=value`; only the first `=` separates them.
- `+` decodes to space and valid `%HH` triplets decode to bytes.
- Decoded names must be non-empty UTF-8 strings without NUL or control bytes.
- Decoded values must be UTF-8 strings without NUL or control bytes.
- Bracket/nesting syntax is unsupported and rejects.
- Duplicate decoded names reject rather than overwrite or become arrays.
- The PSR query-parameter value is a flat detached `array<string, string>`.

This first profile intentionally does not implement PHP `parse_str` semantics,
nested query structures, ambiguous duplicates, or locale-dependent decoding.

### 8.6 Cookie projection

Parse the single projected `Cookie` header independently; do not use
`$_COOKIE`.

- Split only on `;` and discard at most one optional space after a separator.
- Each non-empty pair requires `name=value`.
- Names are ASCII HTTP tokens.
- Values are preserved bytes limited to visible ASCII excluding semicolon,
  comma, backslash, quote, DEL, and control bytes.
- Do not percent-decode cookie values.
- Duplicate cookie names reject.
- The PSR cookie-parameter value is a flat detached `array<string, string>`.

### 8.7 Body, forms, and uploads

- `Transfer-Encoding` rejects; CGI must provide a decoded body and canonical
  length.
- Absent or empty `CONTENT_LENGTH` means exactly zero bytes.
- Present length is canonical unsigned decimal without a leading plus, sign,
  whitespace, or leading zero except the single value `0`.
- Read no more than the declared length from `php://input` and reject early EOF.
- Never read CGI extension data beyond `CONTENT_LENGTH`.
- The declared length must not exceed 1,048,576 bytes.
- The request body remains a readable first-party stream and `parsedBody`
  remains `null`.
- `multipart/form-data` is unsupported and rejects.
- Uploaded files remain an empty array.
- `application/x-www-form-urlencoded` may be read as raw body bytes but is not
  automatically parsed in this profile.

No temporary upload movement, MIME sniffing, JSON parsing, form parsing, body
deserialization, content decoding, decompression, or streaming upload follows
from Phase 3B.

### 8.8 Invalid input outcome

All invalid, missing, ambiguous, oversized, unsupported, unreadable, or failed
host input must fail before `WebApplication::handle()` and before a Kernel
operation begins.

The future delivery must defer conversion failure to its `request()` call so
the existing `WebRuntimeAdapter` selects its fixed generic 500 response and
still performs one send attempt and one close attempt. Phase 3 does not add 400,
405, 413, 415, or 431 response mapping and does not change Web Runtime.

This limitation must be documented plainly. A later separately authorized
protocol-error model may add semantic client-error statuses without changing
this accepted security default.

---

## 9. Locked response-emission contract

Phase 3D will implement this contract; Phase 3B documents it only.

### 9.1 Limits and validation

| Concern | Limit |
| --- | ---: |
| response status | 200 through 599 |
| distinct response headers | 128 |
| values per response header | 32 |
| one response header value bytes | 8,192 |
| total response header name/value bytes | 65,536 |
| staged response body bytes | 1,048,576 |

Before any SAPI emission call:

- `headers_sent()` must be false;
- status, protocol, headers, values, and body readability must validate;
- every response body byte must be read into one package-owned bounded
  `php://temp` staging stream;
- a stalled, failed, oversized, or unreadable body rejects before commit;
- response header order and per-header value order must be preserved;
- `Status`, `Content-Length`, `Transfer-Encoding`, `Connection`, `Keep-Alive`,
  `Proxy-Authenticate`, `Proxy-Authorization`, `TE`, `Trailer`, and `Upgrade`
  are adapter-owned or hop-by-hop and reject when application supplied; and
- Kongono computes one exact `Content-Length` from staged bytes.

### 9.2 Emission order

After complete preflight:

1. mark internal state `commit_attempted` before the first SAPI emission call;
2. set the exact response status through the SAPI;
3. emit response fields in PSR order, using replacement for the first value and
   append semantics for later values of the same field;
4. emit the computed `Content-Length` once;
5. for `HEAD`, emit no body bytes;
6. otherwise copy all staged bytes to `php://output`, treating a false, zero,
   short, warning, or thrown write as failure; and
7. mark `completed` only after the full body is written.

The package may use a bounded, locally installed warning-to-failure bridge only
around its own SAPI calls. It must restore the prior handler in `finally`, must
not swallow a prior-handler failure, and must never leave process-global error
handling changed.

### 9.3 Committed-output uncertainty

Once `commit_attempted` is set, every failure is treated as committed or
uncertain even if buffering or the web server may not yet have transmitted
bytes. Do not call `header_remove()`, rewind host output, retry emission, emit a
second generic response, or assume rollback.

The delivery throws; `WebRuntimeAdapter` retires; `close()` still runs exactly
once. The host decides how to terminate the FastCGI request or process.

Before `commit_attempted`, failure produces no SAPI output, but the existing Web
Runtime still performs no second send attempt. This preserves one-send
ownership and avoids recursive fallback.

### 9.4 Cleanup

One future delivery instance owns exactly one request body, optional staging
stream, and response body reference. `close()`:

- is called exactly once by Web Runtime;
- closes owned request and response/staging streams exactly once;
- releases references even when close fails;
- retains no request, response, permission, or failure value afterward; and
- follows existing primary-plus-cleanup failure composition in Web Runtime.

No destructor, shutdown function, global registry, static current request,
container-current value, thread/fiber local, or process-wide mutable cache owns
delivery cleanup.

---

## 10. Trusted diagnostic boundary

Phase 3B locks the evidence model but not Phase 3E configuration syntax.

- Bootstrap permission is one immutable operator decision loaded before the
  first delivery.
- Current-delivery permission is calculated fresh by the PHP-CGI adapter from
  host evidence it exclusively receives for that delivery.
- Client query, path, header, cookie, form, body, route, session, or token data
  cannot enable or widen either permission.
- `Forwarded` and `X-Forwarded-*` never establish trust.
- Presence of `Forwarded` or any `X-Forwarded-*` field forces current-delivery
  permission `Denied` in this first profile.
- Default, missing, invalid, ambiguous, proxied, failed, or uncertain evidence
  is `Denied`.
- The first profile may return `Allowed` only when later Phase 3E explicitly
  enables trusted developer bootstrap and the current direct-origin
  `REMOTE_ADDR` is canonical loopback (`127.0.0.1` or `::1`) under a
  loopback-only, non-proxied development deployment.
- Presence of any configured proxy path or inability to prove direct-origin
  loopback forces delivery permission `Denied`.
- Production/public deployment guidance must keep trusted bootstrap denied.

Phase 3E must select the exact operator profile syntax and validation. Phase 3B
adds no raw environment flag, `.env` key, request flag, secret, token, header,
cookie, or heuristic permission source.

---

## 11. Diagnostics and failure doctrine

Reserve the future package diagnostic domain:

```text
KGN-PHP-CGI-NNNN
```

Do not reserve individual numbers or create a catalog in Phase 3B.

Future package failures must:

- implement one package-owned exception marker;
- use actionable identifiers, titles, and remediation;
- keep raw methods, targets, hosts, headers, cookies, query values, bodies,
  file paths, environment values, credentials, tokens, and host messages out of
  messages and diagnostic context;
- use only bounded structural tokens, counts, lengths, stages, and violation
  categories approved by closed schemas;
- never expose failure detail through the generic production response; and
- never assume that `Safe` means public.

Consumer PSR response getter/body Throwables propagate as primary delivery
failures unless a later exact contract explicitly wraps a package-owned
validation failure. Do not catch arbitrary failures merely to change their
type.

---

## 12. External acceptance surface

Phase 3H must execute the real selected profile from outside the package using
an operator-supplied `php-cgi` binary in FastCGI listener mode and a test-owned
FastCGI client/harness. The harness is test infrastructure, never production
code or a supported server.

External acceptance must cover at least:

1. exact `cgi-fcgi` / CGI/1.1 / Responder admission;
2. rejection of other SAPI/profile identities;
3. matched and unmatched application responses;
4. fixed generic application failure;
5. trusted diagnostic with explicit development bootstrap plus direct loopback
   delivery evidence;
6. bootstrap denial, delivery denial, forwarded/proxy uncertainty, and
   non-loopback denial before protected source access;
7. method, protocol, target, query, host, header, cookie, length, body, form,
   upload, and profile rejection cases;
8. request and response byte/count limits at boundary-minus-one, boundary, and
   boundary-plus-one where meaningful;
9. exact status, repeated response header order, computed content length, body,
   and `HEAD` body suppression;
10. no raw rejected or secret sentinel in generic or trusted output;
11. pre-commit response validation failure and post-commit/uncertain emission
    failure;
12. one send and one close attempt with correct combined failure behavior;
13. zero active Kernel operations after every completed delivery;
14. repeated FastCGI requests through one reused PHP process with no state
    leakage where the installed binary supports reuse; and
15. no production dependency on the test harness or FastCGI protocol parser.

Phase 3B implements none of this harness.

---

## 13. Required documentation changes

### 13.1 ADR 0052

Create the Accepted ADR with standard sections:

```text
Context
Decision
Rationale
Selected host profile
Package and dependency direction
Inbound policy
Emission and uncertainty policy
Trust and security
API stability
Consequences
Rejected alternatives
What remains open
Related material
```

It must distinguish the selected profile from implemented capability and state
that Phase 3C is next.

### 13.2 Architecture profile

Create `docs/architecture/php-cgi-fastcgi-profile.md` as the authoritative
detailed current decision. Include:

- purpose and current implementation status;
- host admission contract;
- host/framework ownership diagram;
- package dependency graph;
- input-source allowlist;
- all limit tables;
- method/target/scheme/authority/header/query/cookie/body/upload policies;
- invalid-input generic-500 limitation;
- response preflight, emission, uncertainty, and cleanup sequence;
- trusted bootstrap/current-delivery boundary;
- persistent-process isolation requirements;
- external acceptance matrix;
- supported versus unsupported table;
- performance consequences of full bounded request/response buffering; and
- links to ADR 0052, ADR 0051, Web Runtime, HTTP Message, Phase 3 roadmap,
  security policy, RFC 3875, the PHP `$_SERVER`, `php://input`, `header()`, and
  `headers_sent()` manuals.

### 13.3 Current-document reconciliation

Update only the authorized paths so current documentation consistently says:

- Phase 3B selected the PHP `cgi-fcgi` FastCGI Responder architecture;
- the future package is `kongono/php-cgi` / `Kongono\PhpCgi\`;
- the selection is architecture only and no production integration is yet
  implemented;
- Phase 3C is next and owns bounded inbound conversion only;
- Phase 3D owns response emission;
- Phase 3E owns operator bootstrap syntax and trust-input composition;
- Web Runtime, Web Application, and Kernel ownership is unchanged;
- malformed profile input currently maps to the fixed generic 500 by design;
- CGI/FastCGI, upload/form, buffering, proxy, performance, and unsupported-host
  limitations are explicit; and
- Phase 3 remains neither full-framework nor production-hardening
  certification.

Do not rewrite historical ADR 0051 wording that accurately records what Phase
3A had not yet selected.

In `docs/architecture/phase-3-roadmap.md`, retain the exact A-H table and mark
3A and 3B complete in prose or a separate status table. Do not change the A-H
boundary text. Phase 3C must be the only next phase.

---

## 14. Explicitly prohibited scope

Phase 3B must not implement or select beyond this directive:

- production PHP, tests, fixtures, packages, manifests, autoload, lockfile, or
  dependencies;
- exact PHP class, interface, enum, exception, method, property, constructor,
  or source-file inventories for Phase 3C+;
- a FastCGI record parser, listener, socket, process manager, worker pool,
  concurrency scheduler, reload/watch system, or recovery supervisor;
- PHP-FPM, Apache, IIS-specific, built-in-server, CLI, RoadRunner, FrankenPHP,
  OpenSwoole, ReactPHP, Amp, Workerman, or other runtime support;
- proxy trust, TLS termination, DNS validation, authentication, authorization,
  sessions, CSRF, rate limiting, incident handling, logging, telemetry, tracing,
  metrics, secrets management, or general configuration;
- parsed forms, multipart uploads, MIME sniffing, decompression, chunked
  transfer, streaming responses, ranges, sendfile, SSE, WebSocket, HTTP/2 or
  HTTP/3 wire framing;
- error middleware or semantic 4xx/413/415/431 mappings;
- DI/container, Application Graph schema/IDs/serialization, ORM, database,
  migrations, templates, frontend, cache, queue, events, mail, scheduler,
  realtime, agents, extensions, MCP, A2A, CLI, marketplace, or ecosystem work;
- Stable API, semantic versioning, 1.0, Laravel/Symfony compatibility,
  feature-parity, full-framework, or production-readiness claims; or
- staging, commit, tag, remote, push, history rewrite, ref deletion, index
  reconstruction, or Git configuration.

No Symfony, Laravel, Nyholm, Guzzle, Laminas, Slim, FastRoute, or other
framework/runtime component may be added.

---

## 15. Validation sequence

### Gate A - authenticated preflight

1. Authenticate this directive from independently supplied values.
2. Read every authority completely.
3. Verify checkpoint identity, exact caller records, exact `16/7/23/0` entry
   state, closed path inventories, ordinary files, modes, streams, and zero-byte
   report.
4. Verify 13 package directories/manifests, 100 production PHP files, 130 test
   PHP files, 536 tracked files, and unchanged lockfile.
5. Run literal `composer quality`.

Expected baseline quality:

```text
zero advisories
platform requirements pass
0 of 230 style files fixable
PHPStan zero errors
480 tests
8,575 assertions
```

If only network/cache access blocks the audit, rerun the identical literal
command once with approved access. Do not skip or weaken the audit.

### Gate B - documentation implementation

1. Snapshot bytes and SHA-256 of every authorized existing path.
2. Create exactly the two authorized documents.
3. Modify only the eleven authorized current documents.
4. Confirm no production/test/manifest/config/lockfile path changed.

### Gate C - focused architecture validation

Prove directly:

- ADR 0052 is Accepted and dated 2026-08-20;
- only one profile is selected;
- `cgi-fcgi`, `CGI/1.1`, and `RESPONDER` are all explicit;
- `kongono/php-cgi` and `Kongono\PhpCgi\` are consistently spelled;
- all request and response limits match Sections 8 and 9;
- invalid input maps through existing Web Runtime to fixed generic 500;
- no 3C class/signature or runtime implementation was invented;
- the A-H table is byte-identical to its Phase 3A form;
- 3A and 3B are complete and 3C alone is next;
- every local link in changed/new documentation resolves;
- all changed/new text is strict UTF-8 without BOM, LF-only, exactly one final
  LF, no trailing whitespace or NUL, ordinary, non-reparse, default stream only,
  and intended mode 100644;
- `git diff --check` passes; and
- the final status inventory is exact.

Focused checker defects may be corrected and rerun as allowed by Section 4.

### Gate D - canonical final validation

Run literal:

```powershell
composer quality
```

Require the same quality totals as Gate A. Then prove:

```text
normal PHP memory_limit = 128M
PHP_INI_SCAN_DIR absent
composer.lock byte-identical
16 modified tracked paths
9 untracked paths
25 total status paths
0 staged paths
536 tracked files
HEAD/tree/history/refs unchanged
```

The nine untracked paths must be the seven entry callers plus:

```text
docs/adr/0052-php-cgi-fastcgi-responder-profile.md
docs/architecture/php-cgi-fastcgi-profile.md
```

### Gate E - report

Only after Gates A-D pass, populate `docs/task reports/Phase_3B.md`. Recheck its
file quality, untracked/unstaged/absent-from-HEAD state, byte count, and SHA-256.

---

## 16. Completion report contract

Use exactly these headings:

```text
# PHASE 3B TASK COMPLETION REPORT

## 1. STATUS
## 2. SUMMARY
## 3. BASELINE VERIFIED
## 4. CALLER RECORDS
## 5. FILES CREATED
## 6. FILES MODIFIED
## 7. SELECTED HOST PROFILE
## 8. INBOUND POLICY
## 9. EMISSION AND UNCERTAINTY POLICY
## 10. TRUST AND PACKAGE BOUNDARY
## 11. ARCHITECTURAL DECISIONS
## 12. DEVIATIONS FROM DIRECTIVE
## 13. DEPENDENCIES ADDED/REMOVED
## 14. ENVIRONMENT
## 15. COMMANDS RUN
## 16. VALIDATION RESULTS
## 17. DOCUMENTATION REVIEW
## 18. SECURITY AND FILE-SAFETY REVIEW
## 19. WARNINGS / KNOWN ISSUES
## 20. QUESTIONS / BLOCKERS
## 21. FINAL GIT STATUS
## 22. ACCEPTANCE CRITERIA
## 23. NEXT STEP
```

Report exact expected/observed results, every path, whether bounded correction
authority was used, and the separately calculated report byte count/SHA-256.

The exact final sentence is:

```text
Await architect review before Phase 3C.
```

Return the final report byte count and SHA-256 outside the report body as part
of the executor response.

---

## 17. Acceptance criteria

Phase 3B is Complete only when all are true:

1. independent directive identity matches;
2. checkpoint, caller records, and exact entry state match;
3. all authorities are read completely;
4. pre-change canonical quality passes at the locked totals;
5. exactly two documentation files are created;
6. only the eleven authorized current documents are edited;
7. no other path changes relative to the authenticated entry snapshot;
8. ADR 0052 is Accepted and records the exact selected profile;
9. the architecture profile contains every locked policy and limit;
10. the future package identity, direction, and API status policy are exact;
11. Kernel, Web Application, and Web Runtime ownership remains unchanged;
12. request input sources and exclusions are exact;
13. method, target, header, query, cookie, body, form, and upload behavior is
    unambiguous;
14. invalid input is explicitly fixed-generic-500 and pre-application;
15. response preflight, order, bounds, HEAD, and committed-output uncertainty
    are exact;
16. trust defaults denied and cannot be enabled by client input;
17. external acceptance surface is complete and remains future test work;
18. current docs say selected-not-implemented and Phase 3C next;
19. A-H boundaries remain byte-identical;
20. no production/test/dependency/tooling/lockfile change occurs;
21. links and all file-quality gates pass;
22. final canonical quality passes at 480 tests / 8,575 assertions;
23. normal PHP remains 128M with no INI overlay;
24. final state is exactly `16/9/25/0`, 536 tracked, unchanged history; and
25. the completion report satisfies the exact structure, final sentence, and
    independent digest handoff.

Any failed criterion means status cannot be Complete.

---

## 18. Executor handoff

Implement only Phase 3B. Do not begin Phase 3C.

When complete, return the report plus its byte count and SHA-256, then await
architect review.
