# Network-Egress Runtime

> **Current boundary:** Phase 15D and [ADR 0158](../adr/0158-bounded-provider-neutral-network-egress-runtime.md)
> are Accepted. Phase 15E adds the optional Experimental cURL HTTP/1.1 adapter.
> The production graph is 81 packages / 317 local edges / acyclic; PHP inventories
> are 1,514 / 768 / 84. Phases 15F through 15H are Accepted; Phase 15 is closed.

The optional Experimental `kongono/network-egress-runtime` package coordinates
one bounded outbound HTTP attempt. It is the first Phase 15 boundary that may
invoke caller-owned resolution and transport ports, but it neither implements
those ports nor obtains authority for them.

## Collaborator flow

The application supplies one immutable runtime request containing:

1. an exact Phase 15B `EgressOperation`;
2. the exact Phase 15C policy source and canonical artifact bytes;
3. receipt and attempt identities;
4. an authority-currentness port;
5. a resolver port;
6. one transport port;
7. a cancellation port; and
8. a monotonic-clock port.

The runtime validates exact-current policy evidence and operation coherence,
checks request headers and closed postures, probes authority, resolves once,
classifies every candidate, probes authority again, validates stable transport
capabilities, opens one session, pulls bounded cumulative evidence, requires a
terminal emission plus trailing `null`, and closes the session once.

No collaborator is discovered. No second resolution, transport selection,
redirect, retry, fallback, race, hedge, or background operation occurs.

## Address and rebinding boundary

`ResolvedAddress` accepts only exact lowercase canonical output of binary
`inet_pton`/`inet_ntop` parsing. Hostnames, whitespace, control bytes, brackets,
zone identifiers, leading-zero IPv4, mapped IPv6, and noncanonical IPv6 are
rejected without DNS or socket access.

Classification explicitly distinguishes public, private, loopback, link-local,
multicast, unspecified, documentation, and reserved ranges for IPv4 and IPv6.
Public-only policy admits only public candidates. Explicit-private policy also
admits private and loopback candidates. Every other category is always denied,
and one forbidden member rejects a mixed answer. The selected address must be
one unique member and must equal an IP-literal origin exactly.

The selected value is carried unchanged into `EgressTransportRequest`. The
adapter contract is to connect to that address while retaining the operation's
canonical origin for HTTP authority and TLS identity.

## Transport and transfer evidence

The caller's transport declares capabilities twice so instability is visible.
The accepted set must contain all policy requirements, an HTTP/1.1, HTTP/2, or
HTTP/3 capability, and TLS for HTTPS. The runtime opens no more than one
session.

`next()` is the synchronous backpressure checkpoint. Each emission carries
cumulative transfer state and request/response byte counts, negotiated
protocol, current buffered bytes, retained-evidence bytes, and either no
terminal value, one response, or one failure. Evidence may advance but never
regress; a known protocol may not change. Responses require response-received
evidence and a compatible known protocol.

The exact response object is returned without consuming its body. A 3xx
response is terminal like any other response and is never followed.

## Budgets, cancellation, and results

The operation's finite budget limits connection attempts, resolution candidates,
request and response bytes, header field-value lines and aggregate header bytes,
buffered bytes, retained evidence, connection time, first-byte time, and total
time. The emission sequence is capped at 4,096. The first value beyond any limit
is a visible typed failure rather than truncation.

Cancellation before any request byte returns `CancelledBeforeEffect`.
Cancellation after a request byte returns `CancelledAfterPossibleEffect` and
retains observed transfer evidence. Expected denial or pre-effect failure,
success, cancellation, and uncertain-effect outcomes are represented by
`EgressRuntimeResult`, which contains only bounded aggregate evidence and the
Phase 15B operation result.

| Observation | Result outcome | Effect interpretation |
| --- | --- | --- |
| Policy or authority denied before opening | `RejectedBeforeAttempt` | No connection attempt exists. |
| Resolution or valid pre-transfer terminal failure | `FailedBeforeEffect` | Attempt evidence may exist, but request transfer remains `None`. |
| Cancellation before request bytes | `CancelledBeforeEffect` | Existing attempt evidence is retained without claiming an effect. |
| Terminal response | `Succeeded` | The exact response object and cumulative transfer evidence are returned. |
| Cancellation after any request byte | `CancelledAfterPossibleEffect` | Possible effect is retained and replay is not authorized. |
| Failure after any request byte | `UncertainEffect` | Remote effect cannot be excluded. |

Cancellation is checked before initial admission, around each authority,
resolver, capability, open, and pull boundary, and again before accepting a
terminal result. The observation sequence is finite and recorded as bounded
receipt evidence. A cancellation-probe failure is an unexpected collaborator
failure, not a cancellation request.

## Cleanup and persistent runtimes

Every successfully opened session receives exactly one `close()` attempt on
success, expected failure, cancellation, timeout, budget failure, protocol
failure, and unexpected throwable. No close occurs when `open()` does not
return a session. Cleanup-only and combined operation/cleanup exceptions expose
their exact component throwable objects without placing foreign messages or
traces in presentation. Engine `Error` is rethrown unchanged after cleanup.

The runtime rejects reentrant use, clears its active flag in `finally`, retains
no completed operation state, and supports later sequential operations on the
same instance.

| Operation state | Close state | Result |
| --- | --- | --- |
| No session returned by `open()` | Not called | Preserve the pre-open result or unexpected operation failure. |
| Expected terminal result | Succeeds once | Return that result. |
| Expected terminal result | Fails once | Throw cleanup exception with an uncertain cleanup receipt. |
| Unexpected non-`Error` operation failure | Succeeds once | Throw operation exception retaining the foreign throwable. |
| Unexpected non-`Error` operation failure | Fails once | Throw combined exception with separately inspectable failures. |
| Engine `Error` | Attempted once | Rethrow the exact `Error`; never wrap it. |

## Deterministic fake example

Tests and applications may supply an in-memory resolver that returns one
canonical public address and a transport session whose first pull returns a
terminal response emission and whose second pull returns `null`. The runtime
will validate the exact address and capabilities, return the same response
object in a successful result, and close that session once. This example
demonstrates the collaborator contract; it performs no DNS or network access.

## Security and non-goals

The runtime never reads, rewinds, seeks, stringifies, hashes, copies, or buffers
request or response bodies. It never reads proxy, credential, cookie, or
environment state. Diagnostics contain only closed safe stage, violation,
outcome, category, and bounded numeric evidence; they omit raw origins,
addresses, identities, digests, headers, credentials, bodies, paths, and
foreign presentation.

The package supplies no concrete resolver, DNS, socket, TLS, PSR-18 client,
credential provider, proxy discovery, cookie jar, transport registry, provider
adapter, command, application workflow, telemetry, persistence, or remote
effect proof. Policy, receipts, and runtime results remain evidence rather than
authority or external truth.

See the [package README](../../packages/network-egress-runtime/README.md),
[outbound network-egress architecture](outbound-http-and-network-egress.md),
and [Phase 15 roadmap](phase-15-roadmap.md).

## Phase 15E cURL transport and Phase 15F application workflow status

Phase 15E accepts ADR 0159 and implements the optional Experimental
`kongono/network-egress-curl` HTTP/1.1 adapter. It binds the canonical origin
to the single Phase 15D selected address, uses explicit in-memory CA-bundle
bytes with strict TLS verification, and performs one bounded synchronous
transfer without proxy, redirect, cookie, netrc, automatic authentication,
retry, pooling, or PSR-18 control-plane behavior.

Phase 15F accepts ADR 0160 and implements the optional Experimental
`kongono/network-egress-application` exact-current single-profile workflow.
It derives canonical profile and generated-wiring evidence from one supplied
policy source, validates and publishes the wiring without overwrite, loads one
exact contained generated class, checks required transport capabilities before
resolution, and delegates one terminal synchronous operation to a fresh Phase
15D runtime. Authority currentness, resolver, transport, cancellation, and
clock collaborators remain explicit and caller-owned.

Phase 15G accepts ADR 0161 and implements only the optional Experimental
`kongono/network-egress-inspection` upper evidence leaf. It revalidates exact
supplied profile/wiring evidence and can summarize one exactly paired detached
operation/result without loading generated PHP, networking, authority
evaluation, recording, replay, repair, certification, or role execution.

The current topology is 81 packages / 317 local edges / acyclic, with PHP
inventories 1,514 / 768 / 84. Phase 15 is closed. No first-party
resolver, credential provider, provider adapter, live inspection probe,
inspection command, public compatibility certification, Stable/1.0 claim,
production-readiness claim exists.
