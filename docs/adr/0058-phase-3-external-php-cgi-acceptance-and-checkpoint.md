# ADR 0058: Phase 3 External PHP-CGI Acceptance and Checkpoint

- Status: Accepted
- Date: 2026-08-21

## Context

Phases 3B through 3G select and implement one synchronous production HTTP
integration profile: PHP `cgi-fcgi`, CGI/1.1, FastCGI `RESPONDER`. Package tests
prove the bounded converter, emitter, immutable operator bootstrap, concrete
delivery, thin entry, Web Runtime composition, cleanup, and front-controller
shutdown rules. Phase 3 still required proof through an actual external
operator-supplied PHP-CGI listener without turning listener or client machinery
into production framework code.

The complete request target has an intrinsic 8,192-byte ceiling and must embed
the exact `QUERY_STRING`. Consequently, a nonempty externally representable
query using `/?<query>` has an effective 8,190-byte maximum. The package-level
8,192-byte intrinsic query check remains unchanged; external acceptance proves
the composed 8,189 / 8,190 / 8,191 boundary, with the last case rejected as
`target_too_long`.

## Decision

Kongono accepts a root-owned test harness under `tests/External/PhpCgi`. The
harness alone owns a bounded FastCGI v1 client, direct-loopback process wrapper,
closed fixtures, acceptance journal, and runner. Its dependency direction is:

```text
test runner -> test client/process/fixtures -> public and experimental Kongono APIs
production packages -X-> acceptance code
```

The process wrapper accepts one absolute operator-supplied `php-cgi` executable,
verifies PHP 8.5 `cgi-fcgi`, supplies the ten selected INI prerequisites as
literal arguments, and binds only an ephemeral `127.0.0.1` endpoint. It passes
only `SystemRoot`, `TEMP`, `TMP`, and the test journal path to the listener. All
children, handles, sockets, journal files, and directly owned temporary
directories are retired after each group.

The harness also disables PHP host error logging and its implicit default MIME
field so rejected pre-commit cases cannot disclose local details or invent an
application response. PHP-CGI still supplies its fixed terminal 500 status
envelope for an uncaught request failure; the client matches that host-owned
envelope separately from Kongono's zero staged field/body bytes. PHP-CGI also
synthesizes the missing FastCGI role and normalizes certain native NUL/proxy
inputs before `$_SERVER`; package tests remain the deterministic authority for
those unreachable internal rejection branches.

Actual acceptance was performed with PHP 8.5.9 ZTS Visual C++ 2022 x64 on
Windows. That observation is evidence, not a permanent binary-location or
platform contract.

The external matrix proves:

- profile admission and all ten fixed INI prerequisites;
- bounded request grammar, including the effective query composition boundary;
- bounded response preflight, computed content length, field ordering, body
  staging, and HEAD suppression;
- fixed generic production failures and independently gated direct-loopback
  trusted developer diagnostics;
- no disclosure of rejected inputs, exception detail, host paths, process data,
  environment values, or acceptance sentinels;
- real Kernel, Web Application, Web Runtime, PHP-CGI delivery, emission, close,
  explicit application shutdown, and zero active-operation completion;
- five sequential requests through one listener process without state leakage;
- independent-process deterministic client-visible results; and
- direct process retirement after deliberately uncertain client output.

The Phase 3 checkpoint uses the exact subject:

```text
Complete Kongono Phase 3 production HTTP integration milestone
```

Phase 3 closes only after the checkpoint's complete post-commit proof and the
separate Phase 3H.1 correction report pass.

## Consequences

Phase 3 supplies one supported synchronous production integration profile. The
acceptance client, listener wrapper, fixtures, and journal remain test-only and
are not supported production servers, clients, process managers, application
templates, deployment artifacts, health checks, or monitoring systems.

No production FastCGI parser, client, listener, socket abstraction, process
manager, or dependency on test code is added. Production trusted diagnostics
remain denied. Proxy and TLS termination, credentials, authentication,
authorization, sessions, CSRF, rate limiting, logs, telemetry, monitoring,
incident response, deployment automation, recovery, broader SAPIs, Stable API,
1.0 readiness, and production-hardening certification remain outside this
decision.

## Related material

- [PHP-CGI FastCGI profile](../architecture/php-cgi-fastcgi-profile.md)
- [Phase 3 external acceptance](../architecture/phase-3-external-acceptance.md)
- [Phase 3 roadmap](../architecture/phase-3-roadmap.md)
- [PHP-CGI deployment constraints](../engineering/php-cgi-deployment.md)
- [ADR 0052](0052-php-cgi-fastcgi-responder-profile.md)
- [ADR 0057](0057-bounded-php-cgi-front-controller-lifecycle-and-deployment-constraints.md)
- [Security policy](../../SECURITY.md)
