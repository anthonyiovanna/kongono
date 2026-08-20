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
the Kernel scope around each application call. Phase 2G is next and owns outer
adaptation and delivery; Phase 2H remains the first runnable milestone.

The exception marker is Public. The pipeline, routing handler, and concrete
definition exception are Experimental. Layer and diagnostic mechanics are
Internal. No Stable API is created.
