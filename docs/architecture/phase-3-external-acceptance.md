# Phase 3 External PHP-CGI Acceptance

## Purpose and current status

Phase 3H closes Phase 3 with root-owned test infrastructure that drives the
implemented PHP `cgi-fcgi`, CGI/1.1, FastCGI `RESPONDER` profile through an
actual operator-supplied listener. It proves the selected synchronous profile;
it does not add production listener, client, server, process-management, or
deployment behavior.

## Composition

```text
Phase3AcceptanceRunner
  -> PhpCgiProcess -> operator-supplied php-cgi on 127.0.0.1:ephemeral
  -> FastCgiClient -> bounded FastCGI v1 records
      -> production.php or development.php
          -> CgiOperatorBootstrap
          -> Kernel + WebApplication + WebRuntimeAdapter
          -> CgiFrontController -> CgiWebDelivery -> CgiResponseEmitter
          -> explicit WebApplication shutdown
  <- FastCgiResponse + bounded out-of-band cleanup journal
```

All acceptance PHP is under `Kongono\Tests\External\PhpCgi`. No production
package or package manifest depends on it.

## Reproduction

Prerequisites are the repository's locked Composer installation, ordinary PHP
8.5 CLI configuration, and an absolute operator-supplied PHP 8.5 `cgi-fcgi`
executable that can bind direct IPv4 loopback. On the accepted Windows host:

```powershell
composer acceptance:phase3 -- C:\php\php-cgi.exe
```

Success is one LF-terminated JSON object on stdout and empty stderr. It reports
only the closed result/profile/minor, derived case/request/process/reuse/journal
counts, zero active operations, zero disclosure failures, and zero orphan
processes. It never reports a port, PID, timing, temporary location, journal
path, or executable path.

The executable location is operator input, not a portable Kongono contract.

## Request boundaries

The harness sends exact caller-owned FastCGI PARAMS and STDIN records and proves
the selected admission, method, protocol, origin target, scheme, authority,
header, query, cookie, content-length, body, multipart, and transfer-encoding
rules. Every production maximum receives minus-one, boundary, and plus-one
coverage.

PHP-CGI synthesizes a missing `FCGI_ROLE=RESPONDER` from the FastCGI
`BEGIN_REQUEST` role and removes or NUL-truncates a few native environment
inputs before the fixture captures `$_SERVER`. The harness records those
host-normalized outcomes rather than claiming that unreachable bytes reached
Kongono. The deterministic PHP-CGI package tests remain authoritative for the
corresponding missing-role, NUL, and forbidden-proxy validation branches.

Intrinsic `QUERY_STRING` validation retains its 8,192-byte source ceiling. A
matching nonempty query must also occur in a complete origin-form target capped
at 8,192 bytes. The effective external maximum is therefore 8,190 bytes. The
three external cases use exactly:

```text
a=<4096 v bytes>&b=<(N - 4101) v bytes>
REQUEST_URI=/?<that exact query>
QUERY_STRING=<that exact query>
```

Values `N=8189` and `N=8190` are accepted. `N=8191` creates an 8,193-byte
target and is rejected as `target_too_long`. This is intentional composition
evidence, not truncation, a workaround, a production defect, or isolated
external reachability of the intrinsic query check.

## Response and lifecycle proof

The matrix covers status/protocol bounds, ordered repeated fields, prohibited
hop-by-hop fields, exact application-field counts and byte totals, computed
`Content-Length`, exact body bytes, full HEAD preflight without body emission,
pre-commit failure without Kongono-staged response fields or body bytes, and the
staged-body limit. For an uncaught pre-commit failure, PHP-CGI itself writes only
its fixed terminal `Status: 500 Internal Server Error` CGI envelope; the client
matches that host-owned envelope exactly and requires empty FastCGI stderr.
Package tests remain authoritative for private one-send/one-close counters,
getter/read failures, and primary-plus-cleanup exception composition.

External evidence adds one complete client-visible response and FastCGI end
record, explicit shutdown, zero active Kernel operations, sequential reuse of
one listener, independent-process determinism, and retirement after uncertain
output. It does not claim rollback or introspection of private counters.

## Trust and disclosure

`production.php` selects only `Production`; `development.php` selects only
`LoopbackDevelopment`. Request parameters cannot change either choice.
Production trusted diagnostics remain denied. Development trusted output still
requires the independent bootstrap permission plus literal direct loopback and
the absence of `Forwarded` and every `X-Forwarded-*` field.

Rejected request sentinels, application failure detail, exception classes and
messages, source paths, host environment, credentials, executable paths,
temporary paths, ports, and PIDs are absent from client responses, FastCGI
stderr, the success envelope, journal records, and committed documentation.

## Process, network, and temporary-file safety

The client uses only `127.0.0.1`, no DNS, proxy, TLS, or internet. Connection,
read, write, request, startup, and retirement operations are finite and bounded.
Each listener is created from a command array without a shell. A fresh GUID
directory directly below the system temporary directory owns one bounded
journal. Cleanup closes exact sockets, pipes, and the direct child handle,
deletes only that journal, and removes only the verified empty directory.
The ten production-profile prerequisites are varied one at a time. Test-owned
`log_errors=0` and an empty `default_mimetype` keep PHP host diagnostics and
implicit MIME output out of the proof; they do not alter Kongono behavior or
become production configuration. An invalid auto-prepend file prevents the
fixture from executing at all, while the other rejected configurations leave
bounded out-of-band failure evidence.

## Unsupported scope

The proof is not a supported FastCGI client/listener, server, worker manager,
deployment template, health system, monitoring agent, proxy/TLS configuration,
credential system, logging/telemetry system, incident process, recovery
mechanism, universal SAPI claim, Stable API, 1.0 milestone, or production-
hardening certification. Phase 4 does not start automatically.

## Related material

- [ADR 0058](../adr/0058-phase-3-external-php-cgi-acceptance-and-checkpoint.md)
- [ADR 0052](../adr/0052-php-cgi-fastcgi-responder-profile.md)
- [ADR 0057](../adr/0057-bounded-php-cgi-front-controller-lifecycle-and-deployment-constraints.md)
- [PHP-CGI FastCGI profile](php-cgi-fastcgi-profile.md)
- [PHP-CGI deployment constraints](../engineering/php-cgi-deployment.md)
- [Phase 2 runnable fixture](phase-2-runnable-fixture.md)
- [Phase 3 roadmap](phase-3-roadmap.md)
- [Security policy](../../SECURITY.md)
- [Toolchain](../engineering/toolchain.md)
