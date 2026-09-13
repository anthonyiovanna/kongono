# ADR 0184: Explicit Completed-View PSR-7 Response Adaptation

- Status: Accepted
- Date: 2026-09-12
- Phase: 18E

## Context

Phase 18D returns a completed neutral `RenderResult` without importing HTTP.
Applications that deliver server-rendered HTML need a narrow bridge to a PSR-7
response while retaining ownership of response and browser policy. Render
success and HTTP adaptation success must remain separate truths.

## Decision

Accept the optional Experimental `kongono/view-http` package. One
`CompletedViewResponseAdapter` receives explicit PSR-17 response and stream
factories. Each call accepts one completed neutral result and one bounded
`ViewResponsePolicy`. The policy explicitly supplies status, reason phrase,
the single accepted UTF-8 HTML content type, and caller headers.

The adapter revalidates completed output byte-count and SHA-256 evidence before
invoking either factory. It creates and verifies an exact readable, seekable,
rewound body stream, creates a response with the exact status and reason,
derives only `Content-Length`, applies `Content-Type` and caller headers through
immutable PSR-7 operations, and verifies the final body identity and complete
response state. A factory-added header, altered status, altered reason, altered
body, or altered header fails closed. No partial response escapes.

Header policy is closed and bounded. Framing, content, host, and hop-by-hop
headers are reserved to the adapter or transport boundary. Values admit only
horizontal tab and printable ASCII and reject CR, LF, NUL, obs-fold, and
non-ASCII bytes. Diagnostics expose only closed tokens and Safe aggregates;
previous throwables are retained but their messages and traces are not rendered.

Production depends only on the PSR HTTP message and factory interfaces. The
Kongono HTTP implementation is a development and test choice, not a required
production implementation.

Application code continues to own view selection, status, reason, caller
headers, content type, browser-security policy, cookies, redirects, caching,
negotiation, error presentation, delivery, and all application effects. The
adapter supplies no renderer, route, controller, middleware, server, emitter,
session, CSRF, CSP, asset, or workflow behavior.

## Consequences

- Phase 18A-E are Accepted; Phase 18F-H remain Planned.
- `view-contract`, `view-build`, `view-runtime`, and `view-http` are implemented
  optional packages.
- A `RenderFailure` is never converted into an HTTP response by this package.
- API, CLI, worker, agent, headless, and client-side applications remain
  first-class and need not install the package.
- No Stable/1.0, production-readiness, performance-superiority, browser-
  certification, or security-certification claim is made.

## Alternatives considered

1. Direct dependency on the concrete Kongono HTTP implementation - rejected
   because it removes PSR implementation choice.
2. Returning raw bytes or an array - rejected because it weakens the explicit
   PSR boundary.
3. Adapting `RenderFailure` to an error response - rejected because failure
   presentation belongs to existing application/trusted-diagnostic policy.
4. Implicit header/security defaults - rejected because browser and application
   policy must remain caller-owned.
5. A routing/controller/middleware integration package - deferred and outside
   this narrow phase.

## References

- [ADR 0183](0183-exact-current-compiled-view-runtime-and-bounded-rendering.md)
- [Compiled View HTTP](../architecture/compiled-view-http.md)
- [Server-Rendered Presentation](../architecture/server-rendered-presentation.md)
- [Phase 18 Roadmap](../architecture/phase-18-roadmap.md)
