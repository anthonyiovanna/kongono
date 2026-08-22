# ADR 0057: Bounded PHP CGI Front-Controller Lifecycle and Deployment Constraints

- Status: Accepted
- Date: 2026-08-21

## Context

Phase 3F supplied one Internal `CgiWebDelivery` and the Experimental
`CgiWebEntry`, but the consumer still had to coordinate the entry with explicit
application shutdown. There was no package-owned four-way entry/shutdown
failure contract or consolidated deployment and operator boundary. Directly
depending on Web Application from PHP CGI would have reversed the intended
composition seam and made the outer adapter own application construction.

## Decision

PHP CGI owns a stateless Experimental `CgiFrontController`. Its sole operation
is `run(WebRuntimeAdapter $runtime, Closure $shutdown): void`, where the closure
has the semantic signature `Closure(): void`. A consumer supplies it only after
selecting a literal `CgiOperatorProfile`, loading bootstrap permission,
explicitly constructing and booting its application, and constructing the
runtime. The consumer must pair the runtime with that application's first-class
`shutdown()` closure. Kongono does not infer or repair an invalid pairing.

The closure avoids a direct PHP-CGI-to-Web-Application dependency. The consumer
also owns application-specific construction and cleanup when bootstrap,
construction, or boot fails before the front controller is entered.

The front controller performs exactly one `CgiWebEntry::run($runtime)` attempt,
then exactly one shutdown attempt. It retains no state and has this closed
matrix:

| Entry | Shutdown | Result |
| --- | --- | --- |
| success | success | return `void` |
| failure `P` | success | throw exact `P` |
| success | failure `S` | throw exact `S` |
| failure `P` | failure `S` | throw `CgiEntryShutdownException(P, S)` |

The simultaneous case uses `KGN-PHP-CGI-1019`. The composite retains `P` as
`primaryFailure()` and native `previous`, and retains `S` as
`shutdownFailure()`. Its Safe context contains only the fixed
`entry_and_shutdown_failed` token. The throwable accessors are explicit
developer-inspection surfaces, not Safe metadata; no throwable message, trace,
argument, file, line, code, or string form is copied into diagnostics.

There is no retry, second response, rollback, logging, incident recording,
automatic recovery, or failure swallowing. An uncertain emission or escaped
shutdown failure makes reuse unsafe by inference; the external host must retire
the process according to its policy. The controller does not terminate it.

The selected deployment profile remains PHP `cgi-fcgi`, CGI/1.1, FastCGI
`RESPONDER`. The fixed PHP prerequisites, bounded request/response profile,
`Production` denial, and independent direct canonical loopback without
forwarded evidence for development remain unchanged. Kongono owns bounded
capture, conversion, response selection and staging/emission through the
existing layers, delivery close, and the one shutdown attempt. The application
owns Kernel, routes, middleware, handlers, construction, boot, partial-boot
cleanup, and the supplied shutdown closure. The external host owns listening,
FastCGI framing, TLS and proxy policy, permissions, PHP process lifetime,
timeouts and external resource ceilings, logs, monitoring, retirement/restart,
deployment, secrets, and incident response.

`CgiFrontController` and `CgiEntryShutdownException` are Experimental;
`PhpCgiDiagnostic` remains Internal. The existing acyclic dependency graph is
unchanged: PHP CGI depends on Web Runtime and has no direct Web Application,
Kernel, HTTP Server, Routing, Dependency Injection, or Application Graph edge.

## Rejected alternatives

- A direct Web Application dependency would violate the outer-leaf dependency
  boundary.
- A destructor or registered shutdown hook would make ordering and failure
  ownership implicit.
- A generic cleanup registry, hook, event, or `callable` would widen this
  application-shutdown seam into a new extension surface.
- Service location would introduce ambient application/runtime ownership.
- A second response, rollback, retry, logging, swallowing, or automatic
  recovery would contradict uncertain-output and primary-failure semantics.
- A listener, deployment generator, generated entrypoint, server snippet, or
  process manager would exceed the selected profile boundary.

## Consequences and limits

Phase 3G documents exact operator constraints but provides no Apache, Nginx,
Caddy, IIS, container, service-manager, or cloud configuration; no generated
entry script; no listener; and no production-hardening certification. It makes
no arbitrary concurrent runtime-reuse claim. Documentation is not proof of a
served deployment.

Phase 3H external CGI/FastCGI acceptance and the Phase 3 checkpoint are the only
next phase.

## Related material

- [ADR 0056: PHP CGI Production Delivery and Entry Composition](0056-php-cgi-production-delivery-and-entry-composition.md)
- [PHP CGI deployment contract](../engineering/php-cgi-deployment.md)
- [PHP CGI FastCGI profile](../architecture/php-cgi-fastcgi-profile.md)
- [Phase 3 roadmap](../architecture/phase-3-roadmap.md)

## Phase 3H outcome

Phase 3H externally proves the unchanged one-entry/one-shutdown controller
through the selected PHP-CGI listener. Completed journal records show explicit
shutdown and zero active Kernel operations; uncertain client output causes
retirement of the exact test-owned listener before a replacement succeeds.
This is acceptance evidence, not production rollback, automatic recovery,
deployment automation, listener support, monitoring, or hardening
certification. Phase 3 is closed and Phase 4 does not start automatically.
