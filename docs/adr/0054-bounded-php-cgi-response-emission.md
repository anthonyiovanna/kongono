# ADR 0054: Bounded PHP CGI Response Emission

**Status:** Accepted
**Date:** 2026-08-21

## Context

Phase 3C converts an explicit, bounded CGI host snapshot into a first-party PSR-7 server request. The selected PHP `cgi-fcgi`, CGI/1.1, FastCGI `RESPONDER` profile also needs a deterministic outbound boundary, but Kongono still has no concrete `WebDeliveryInterface` implementation, ambient host capture, operator profile, trust composition, production entrypoint, listener, deployment, or external acceptance proof.

SAPI output is not transactional. Once any status, header, or body call is attempted, success cannot be rolled back or inferred from output-buffer state. The response therefore must be fully validated and staged before the first emission call.

## Decision

`kongono/php-cgi` owns the Experimental stateless entrypoint:

```php
CgiResponseEmitter::emit(ResponseInterface $response, string $requestMethod): void
```

Each call creates a fresh Internal native target and retains no request, response, target, body, failure, or commitment state. `CgiEmissionTarget` is an Internal test seam used to verify the exact emission sequence. It is not public dependency injection, consumer configuration, a transport abstraction, or authority to replace the selected SAPI behavior.

Before commitment, the emitter reads the response from its current body position and requires a readable body. It validates and copies the complete remaining body in 8,192-byte chunks to a package-owned first-party temporary stream. The maximum staged body is 1,048,576 bytes. It does not rewind, seek, detach, close, or retain the consumer body.

The accepted response contract is:

- status from 200 through 599;
- protocol `1.0`, `1.1`, or `2`;
- at most 128 distinct header fields and 32 values per field;
- at most 8,192 bytes per header value and 65,536 total header-name/value bytes;
- header names and values must satisfy the existing first-party HTTP Message grammar;
- `Status`, `Content-Length`, `Connection`, `Transfer-Encoding`, `Trailer`, `Upgrade`, `Keep-Alive`, `Proxy-Authenticate`, `Proxy-Authorization`, and `TE` are forbidden regardless of case; and
- a staged body of at most 1,048,576 bytes.

Header and value order is preserved. The emitter computes the exact decimal `Content-Length`; applications cannot supply it. `HEAD` stages and measures the body identically but emits no body bytes.

Immediately before the first SAPI status call, after a second `headers_sent()` proof, internal state changes to commitment attempted. The exact sequence is status, application headers, computed `Content-Length`, and, except for `HEAD`, staged body chunks. Any failure from that point is committed or uncertain. It throws and performs no rollback, header removal, output-buffer manipulation, flush, retry, second response, or generic fallback.

Consumer `Throwable` values from PSR response getters or body reads propagate unchanged as the primary failure. Package-owned validation and staging failures use closed `KGN-PHP-CGI-1011` through `1015` diagnostics. Post-commit or uncertain native emission failures use `KGN-PHP-CGI-1016` and record `commitAttempted=true`. Diagnostic context contains only the one Safe closed violation token.

On success or failure, the package closes only its own staging stream. The consumer owns the response body throughout. The native warning bridge restores the previously installed error handler in `finally`; if a previous handler throws, that same throwable remains primary.

## Rationale

Complete preflight makes validation failures unambiguously pre-commit and makes emitted length deterministic. The explicit target seam permits production ordering and failure-state tests without exposing SAPI replacement as a supported consumer mechanism. Closed diagnostics avoid retaining rejected response data.

## Consequences

Response staging has deterministic `O(n)` time, copying, and additional memory for the remaining body, bounded at 1 MiB. Emission begins only after the entire body has been read, so latency includes full staging. Streaming responses, ranges, sendfile, server-sent events, WebSocket, and unbounded bodies are unsupported.

Post-commit failure leaves the host output uncertain and requires runtime retirement by the future concrete delivery composition. `WebRuntimeAdapter` continues to own one send attempt and one close attempt, but Phase 3D does not connect this emitter to it.

## Rejected alternatives

- Direct or partially validated emission, because a late validation failure cannot be recovered safely.
- Treating output buffering as rollback, because buffering is host configuration rather than a reliable transaction.
- Accepting application-supplied `Content-Length`, because it could disagree with staged bytes or `HEAD` behavior.
- Streaming bodies, because validation and length would cross the commitment boundary.
- Retrying or emitting a generic second response after commitment, because the client-visible state is uncertain.
- Adding concrete delivery or operator composition now, because those belong to later authorized phases.

## Phase 3F integration status

Phase 3E trust inputs and Phase 3F delivery composition are implemented. The
fresh delivery captures the exact response body before one emitter call and
uses validated request method after successful conversion. Before success, only
an exact raw `HEAD` selects `HEAD`; every other value falls back to `GET`, so a
malformed `HEAD` generic response still emits no body. There is no retry,
rollback, or second response.

## Phase 3G reconciliation

ADR 0057 leaves emission unchanged and adds one shutdown attempt after the one
entry attempt. The four outcomes preserve the entry failure, shutdown failure,
or both through 1019 without a second response. Committed or uncertain emission
and escaped shutdown both require external process retirement. The documented
operator constraints are not a listener, server configuration, deployment
artifact, recovery system, or external proof. Phase 3H alone is next.

## Phase 3H outcome

Phase 3H now proves this unchanged staged-emission boundary through an external
PHP-CGI listener: status, protocol, ordered application fields, exact byte/count
limits, one computed `Content-Length`, body staging, HEAD suppression, and
pre-commit no-response behavior. Test-only process retirement covers uncertain
output without claiming rollback or production recovery. The harness adds no
production emitter API, listener, client, deployment artifact, or hardening
certification.
