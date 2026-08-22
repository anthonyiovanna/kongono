# Kongono HTTP Server

`kongono/http-server` owns Kongono's bounded PSR-15 orchestration boundary. It
provides an immutable declaration-ordered middleware pipeline and a terminal
request handler that maps deterministic Phase 2D route names to explicit
PSR-15 handlers.

The package accepts any compatible PSR-7 request and response objects. It
retains no current request, response, route match, middleware cursor, failure,
principal, tenant, permission, or operation state. Consumer middleware and
handlers remain caller-owned objects and may have their own state or side
effects.

Successful route matches are attached to a derived request under
`Kongono\Routing\Match\RouteMatch::class`. The value and its encoded parameters
remain untrusted and establish neither authentication nor authorization.
Unmatched requests are delegated to an explicit handler; this package chooses
no status, header, body, redirect, or generic failure response.

The package performs no bootstrap, Kernel operation ownership, adapter input,
response emission, supplied error middleware, generic production response,
error conversion, container lookup, or I/O. Phase 2F's separate [Web Application
package](../web-application/README.md) composes this unchanged boundary and owns
the Kernel scope around each application call. Web Runtime owns outer delivery,
and Phase 2H's root-owned, test-owned fixture proves the complete composition.
The fixture is not HTTP Server package behavior or a production application,
SAPI adapter, server, listener, emitter, or deployment profile. Phase 2 is
complete at checkpoint `647053d4bddc4c76090bca9bf3d693faa5880c52` with
exactly thirteen production packages.

Phase 3A plans one supported synchronous production HTTP integration profile
without changing this transport-free package or selecting a host, package, API,
parser, emitter, configuration mechanism, or entrypoint. See the [Phase 3
roadmap](../../docs/architecture/phase-3-roadmap.md).

The exception marker is Public. The pipeline, routing handler, and concrete
definition exception are Experimental. Layer and diagnostic mechanics are
Internal. No Stable API is created.
