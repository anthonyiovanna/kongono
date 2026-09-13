# Compiled View HTTP Adaptation

## Status

Phase 18E accepts [ADR 0184](../adr/0184-explicit-completed-view-to-psr-7-response-adaptation.md)
and the optional Experimental `kongono/view-http` package. Phase 18A-G are
Accepted; Phase 18G's separate [inspection leaf](compiled-view-inspection-and-intelligence.md)
does not adapt HTTP. Phase 18H's separate
[local acceptance](phase-18-server-rendered-presentation-acceptance.md) is Accepted and Phase 18 is closed.

## Boundary

The package maps one already completed neutral Phase 18 result to one PSR-7
response. It neither renders nor interprets templates and it never accepts a
`RenderFailure`.

```text
application-owned completed RenderResult
  + application-owned ViewResponsePolicy
  + explicit PSR-17 factories
  -> bounded validation
  -> exact body stream proof
  -> immutable PSR-7 assembly
  -> exact final response proof
```

Application code owns view selection, status, reason, caller headers, content
type, browser-security policy, cookies, redirects, caching, negotiation, error
presentation, and delivery. The adapter owns only bounded validation and exact
PSR-7 assembly. Render failure and HTTP adaptation failure remain different
truths, and no partial output or response escapes a failed adaptation.

## Explicit policy

`ViewResponsePolicy` accepts status 200 through 599, an empty reason phrase or
at most 128 printable ASCII bytes, the closed `text/html; charset=utf-8`
content type, and at most 32 caller headers. Header names are unique without
regard to case and use the RFC token grammar. Each header has one through eight
values; each value contains at most 1,024 bytes and permits only horizontal tab
or printable ASCII. Total caller header name and value bytes are at most
16,384.

The caller cannot supply `Content-Type`, `Content-Length`, `Connection`,
`Keep-Alive`, `Proxy-Connection`, `TE`, `Trailer`, `Transfer-Encoding`,
`Upgrade`, or `Host`. CR, LF, NUL, obs-fold, and non-ASCII values fail before a
factory call.

## Adaptation lifecycle

The adapter first proves that the result is completed and that output bytes
match their declared byte count and SHA-256. It asks the caller-selected stream
factory for the exact output bytes and verifies readability, seekability,
length, contents, and rewind position. It asks the caller-selected response
factory for the exact status and reason, attaches the exact body, content
headers, and caller headers through immutable operations, then verifies status,
reason, body identity, every expected header value, and absence of unexpected
headers.

The adapter is synchronous and stateless. Sequential and interleaved calls do
not retain response, stream, policy, or render state.

## Implementations and failures

Production imports PSR interfaces only. `kongono/http-message` is one
interchangeable development/test implementation. Factories remain untrusted
collaborators at this boundary; altered or injected state fails closed.

Diagnostics `KGN-VIEW-HTTP-1840` through `KGN-VIEW-HTTP-1844` separate invalid
policy, invalid completed render evidence, stream creation/verification,
response creation, and final assembly/verification. Context is closed, bounded,
Safe, and non-disclosing. Previous throwables are retained without exposing
their private messages or traces.

## Exclusions

The package supplies no routing, controller, middleware, application workflow,
request conversion, server/listener, SAPI integration, response emission,
output buffering, session, CSRF, CSP or nonce creation, security defaults,
cookies, redirects, caching, assets, negotiation framework, or failed-render
presentation. It makes no production-readiness, performance-superiority,
browser-certification, security-certification, Stable, or 1.0 claim.

## Related material

- [ADR 0184](../adr/0184-explicit-completed-view-to-psr-7-response-adaptation.md)
- [Compiled View Runtime](compiled-view-runtime.md)
- [Server-Rendered Presentation](server-rendered-presentation.md)
- [View Authoring](../engineering/view-authoring.md)
- [Phase 18 Roadmap](phase-18-roadmap.md)
