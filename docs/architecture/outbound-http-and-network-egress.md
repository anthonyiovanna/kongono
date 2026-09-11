# Outbound HTTP and Governed Network Egress

## Current status

Phase 15A accepts the architecture in [ADR 0155](../adr/0155-explicit-outbound-http-and-governed-network-egress-boundary.md).
Phase 15B accepts [ADR 0156](../adr/0156-neutral-versioned-network-egress-contract-foundation.md)
and the optional Experimental
[neutral contract](network-egress-contract-foundation.md). Phase 15C accepts
[ADR 0157](../adr/0157-deterministic-network-egress-policy-artifact.md) and the
deterministic inert offline [policy-artifact boundary](network-egress-policy-artifact.md).
Phase 15D accepts [ADR 0158](../adr/0158-bounded-provider-neutral-network-egress-runtime.md)
and the optional Experimental [bounded runtime](network-egress-runtime.md) for
one direct attempt over explicit caller-owned collaborators. Phase 15E accepts
[ADR 0159](../adr/0159-explicit-curl-http11-egress-transport-profile.md) and the
optional Experimental [cURL HTTP/1.1 adapter](network-egress-curl-transport.md).
Phase 15F accepts [ADR 0160](../adr/0160-exact-current-single-profile-network-egress-application-workflow.md)
and the optional Experimental exact-current single-profile
[application workflow](network-egress-application-workflow.md). Phase 15G
accepts [ADR 0161](../adr/0161-visibility-safe-network-egress-inspection-and-bounded-evidence.md)
and the optional Experimental offline
[inspection leaf](network-egress-inspection-and-evidence.md). No first-party
resolver, credential provider, provider adapter, live inspection probe,
inspection command, or public compatibility certification exists. Phase 15H
adds the bounded local external harness and exact local checkpoint; Phase 15 is
closed.

Phase 14 is closed at checkpoint
`05bf8367b56e694a8c388ccb96cdc305cb6e40ae`. Its provider-neutral model
invocation packages do not perform network I/O or grant authority.

## Why a general egress layer comes first

Model providers are one possible external service among APIs, webhooks,
observability exporters, payment services, storage services, and search
services. Building network policy directly into a provider adapter would
duplicate security rules, tie neutral behavior to vendor SDKs, and make
credentials and retries harder to audit.

The planned egress layer therefore sits beneath optional service and provider
adapters. It governs how one explicitly authorized operation may reach an
external resource. An outer adapter may translate service-specific meaning,
but it cannot weaken origin, resolution, TLS, budget, cancellation,
disclosure, uncertainty, or cleanup rules.

## The core rule

A URI, request, policy artifact, resolved address, connection, response,
redirect, receipt, provider output, Graph fact, or developer-tool result is
evidence, not permission to access a network resource.

Only current application-owned authority and policy supplied for the operation
may permit an attempt. That authority is checked again before every new
connection, redirect, retry, proxy hop, or other possible external effect.
Model output and Agent proposals remain inert until separately admitted and
authorized; neither can grant network access.

## What applications own

Applications continue to own:

- the business intent and external-service semantics;
- current authorization and policy selection;
- endpoint and canonical-origin selection;
- credentials and their operation-scoped delivery;
- resolver and transport selection;
- decisions about private-network access, redirects, retries, proxies,
  cookies, and connection reuse; and
- interpretation of responses and uncertain effects.

Application ownership does not mean application input bypasses validation.
Requests, policies, credentials, and adapters cross explicit trust boundaries.

## What Kongono is planned to own

Subject to later authenticated directives, Kongono is planned to provide
neutral evidence types, exact-current deterministic policy evidence, a bounded
one-operation coordinator, explicit lifecycle and cleanup, transport
capability boundaries, nondisclosing outcomes, and offline inspection.

Kongono will not own provider accounts, credential discovery, ambient proxy
configuration, a global client, endpoint discovery, automatic authentication,
or service-specific success semantics.

## Conceptual flow

```text
application-owned intent, authority, policy, and credentials
  -> immutable PSR-7 request plus explicit egress operation evidence
  -> deterministic exact-current egress policy evidence
  -> bounded egress coordinator
  -> explicit application-supplied resolver and transport adapter
  -> one governed connection/attempt lifecycle
  -> validated immutable PSR-7 response and bounded attempt receipts
  -> optional outer service/provider adapter
```

Each arrow means "may depend on after validation." It does not mean that an
earlier artifact authorizes the next effect.

## Keep the concepts distinct

The requested URI is what the caller asked for. A canonical origin is a
normalized scheme, host, and port identity used for comparison. A
policy-permitted origin is an origin the current application policy allows.
Resolution evidence reports candidate addresses. The selected connection
address is the exact address a transport will use. A transport attempt is a
particular possible external effect. The response origin is the authority from
which the response is treated as arriving. A receipt is bounded evidence about
that lifecycle.

Conflating any of these creates security gaps. A permitted hostname does not
automatically permit every address it resolves to. A valid certificate does
not grant application authority. A response from one attempt does not permit a
redirect or retry. A receipt does not prove external truth.

## Safe defaults

The accepted direction is deliberately conservative:

- outbound networking is optional;
- HTTPS is the secure default direction;
- certificate and hostname verification stay enabled;
- redirects, retries, cookies, authentication, proxies, and pooling are not
  ambient or automatic;
- destinations and every possible external effect require current explicit
  authority;
- budgets are finite and limit-plus-one fails visibly;
- buffering is bounded and backpressure is respected;
- partial I/O and uncertain effect are reported honestly; and
- cleanup is deterministic on every terminal path.

Later phases may admit explicit bounded policies for selected behavior. They
cannot silently replace these defaults.

## URI and SSRF threats

Request URIs are untrusted. Future validation must reject malformed authority,
user information, fragments, unsupported schemes, ambiguous host syntax,
invalid ports, control characters, conflicting host metadata, address-family
ambiguity, forbidden address classes, and policy mismatch.

SSRF controls must cover loopback, private, link-local, multicast, unspecified,
documentation/reserved, cloud-metadata, Unix-socket, named-pipe, and other
non-public destinations. Local development and private networks need explicit,
narrow application policy; there is no universal exception.

## Resolution binding and DNS rebinding

Resolution is evidence, not authorization. The exact selected address must be
one that was validated for the current operation and then used by the
transport. Validation of one address followed by connection to another is not
allowed.

Mixed allowed and forbidden answers, resolution races, IPv4/IPv6 differences,
proxy-side resolution, and DNS rebinding need deterministic outcomes. A
redirect starts a fresh URI, origin, policy, resolution, and connection review.

## TLS, proxies, redirects, cookies, and credentials

Certificate and hostname verification cannot be disabled by request data,
provider output, a redirect, or hidden environment state. Trust stores, client
certificates, protocol versions, and proxies are explicit host or application
policy.

Ambient proxy variables, `.netrc`, platform credential stores, global cookie
jars, automatic cookies, implicit redirects, and automatic authentication are
prohibited. Redirects are denied by default; any later policy must bound hop
count, origin change, method and body transformation, credential forwarding,
resolution, and downgrade behavior. HTTPS-to-HTTP downgrade is prohibited.

Credentials are application-owned and operation-scoped. They stay out of
artifacts, generated code, Graph evidence, logs, diagnostics, exceptions,
receipts, inspection, developer-tool output, and fixtures. Authorization
headers and signed material cannot become non-secret evidence.

## Budgets, streams, and backpressure

A future operation has finite ceilings for applicable connection attempts,
DNS results, redirects, retries, request and response bytes, header count and
bytes, decompressed bytes and expansion ratio, buffered bytes, retained
evidence, connect time, first-byte time, total time, and concurrency.

Content length, transfer encoding, compression, and framing are untrusted.
Streaming does not mean unbounded memory. Producers and consumers must respect
backpressure, and limit-plus-one is a visible failure rather than silent
truncation. No attempt, partial request transmission, partial response receipt,
and completed success remain different states.

## Cancellation, uncertainty, and cleanup

Cancellation and monotonic time are explicit collaborators. Cancellation does
not erase already transmitted bytes. Cleanup runs deterministically after
success, failure, cancellation, budget exhaustion, timeout, redirect denial,
parser rejection, and transport uncertainty.

An unknown effect after a partial write or lost response must not be reported
as failure-before-effect. It does not authorize replay. Retry requires an
explicit finite profile plus application reasoning about method, body
replayability, idempotency keys, bytes sent, response state, and service
semantics. Retry, fallback, hedging, racing, and failover are separate choices.

## Persistent runtimes and concurrency

Connection reuse is allowed only as a later explicit choice through an owned,
bounded pool. The pool needs clear startup, checkout, return, retirement,
isolation, concurrency, and shutdown rules. It cannot leak credentials,
responses, cancellation, or protocol state across operations.

There is no static mutable client, ambient current request, hidden session, or
destructor-dependent cleanup. Uncertain connection state causes retirement,
not optimistic reuse.

## PSR interoperability and HTTP versions

The existing immutable PSR-7 message interfaces are reused where message
semantics fit. Inbound server handling and outbound egress remain separate
lifecycle and authority boundaries.

PSR-18 compatibility may be provided by an outer adapter, but PSR-18 is not the
core control plane. It does not express resolver binding, current egress
authority, budgets, streaming lifecycle, cancellation, redirects, retry
uncertainty, pool ownership, or cleanup proof.

Neutral semantics do not assume HTTP/1.1, HTTP/2, or HTTP/3. Negotiated
protocol, multiplexing, connection coalescing, header compression, early data,
and transport capabilities are evidence that cannot weaken origin, TLS,
isolation, ordering, cancellation, or disclosure requirements.

## Diagnostics and operational evidence

Failures should separate validation, policy denial, resolution, connection,
TLS, protocol, redirect, budget, cancellation, timeout, response, cleanup, and
uncertain-effect classes. Context must be bounded and Safe. Full URLs, query
strings, credentials, headers, cookies, bodies, resolved private addresses,
proxy secrets, certificates, filesystem paths, and foreign exception text stay
out of ordinary diagnostics.

Observability may receive allowlisted aggregate facts and correlation, and
Security Audit may receive bounded decisions without payloads. Neither is
authority, live endpoint truth, delivery proof, or a secret side channel.

## Developer experience

The straightforward path should need immutable inputs, an explicit policy, one
operation lifecycle, typed outcomes, deterministic cleanup, actionable
remediation, and deterministic local fakes. No hidden global state or provider
account is required. Applications that do not opt into outbound HTTP remain
unaffected.

## Testing direction

Later phases should favor deterministic local fakes and external-process
conformance over public Internet endpoints. Tests will need to cover address
classes, mixed DNS answers, rebinding, redirect revalidation, TLS policy,
partial writes, compression limits, backpressure, cancellation checkpoints,
timeouts, concurrency, pool retirement, cleanup failure, nondisclosure, and
uncertain effects. Phase 15A adds no test or executable fixture.

## Chie, Hantei, and Oshie

Chie may explain accepted egress policy and bounded failures. Hantei may verify
deterministic artifacts and offline conformance. Oshie may teach the accepted
model. Their outputs remain evidence. None may call a network, supply
credentials, authorize an origin, alter policy, retry work, or certify live
safety.

## Current versus planned behavior

Accepted now: ADR 0155 through ADR 0158, this guide, the fixed roadmap, the
optional Experimental neutral contract package, deterministic policy artifact
package, and bounded one-operation runtime. The contract reuses PSR-7 message
interfaces without reading body streams or making PSR-18 the control plane.
The builder uses only one explicit typed source to create, validate, compare,
and optionally publish inert content-addressed evidence. The runtime binds one
selected address to one explicit caller-owned transport attempt.

The cURL transport profile, exact-current application workflow, inspection
leaf, and local external acceptance are Accepted. Phase 15 is closed; Phase 16
requires its own authenticated directive.

## Related material

- [ADR 0155](../adr/0155-explicit-outbound-http-and-governed-network-egress-boundary.md)
- [ADR 0156](../adr/0156-neutral-versioned-network-egress-contract-foundation.md)
- [ADR 0157](../adr/0157-deterministic-network-egress-policy-artifact.md)
- [ADR 0158](../adr/0158-bounded-provider-neutral-network-egress-runtime.md)
- [ADR 0159](../adr/0159-explicit-curl-http11-egress-transport-profile.md)
- [ADR 0160](../adr/0160-exact-current-single-profile-network-egress-application-workflow.md)
- [ADR 0161](../adr/0161-visibility-safe-network-egress-inspection-and-bounded-evidence.md)
- [Network-egress contract foundation](network-egress-contract-foundation.md)
- [Network-egress policy artifact](network-egress-policy-artifact.md)
- [Network-egress runtime](network-egress-runtime.md)
- [Network-egress cURL transport](network-egress-curl-transport.md)
- [Network-egress application workflow](network-egress-application-workflow.md)
- [Network-egress inspection and bounded evidence](network-egress-inspection-and-evidence.md)
- [Phase 15 roadmap](phase-15-roadmap.md)
- [HTTP message model](http-message-model.md)
- [Identity and access security](identity-and-access-security.md)
- [Observability and operational evidence](observability-and-operational-evidence.md)
- [Provider-neutral model invocation](provider-neutral-model-invocation.md)
- [Phase 14 external acceptance](phase-14-external-acceptance.md)

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
inventories 1,514 / 768 / 84. Phase 15H is Accepted and Phase 15 is closed. No first-party
resolver, credential provider, provider adapter, live inspection probe,
inspection command, public compatibility certification, Stable/1.0 claim,
production-readiness claim exists.

## Phase 16A-C relationship

Phase 16A accepts the optional documentation layer described by [ADR
0163](../adr/0163-explicit-external-service-binding-and-secret-lease-boundary.md),
the [external-service binding guide](external-service-binding-and-secret-leases.md),
and the [Phase 16 roadmap](phase-16-roadmap.md). Phase 15A-H remain Accepted
and Phase 15 is closed at commit
`b4cb57ffa01eea130a693daee0be605ae7f0dda5`. Phase 16B accepts
[ADR 0164](../adr/0164-neutral-external-service-binding-and-credential-lease-contracts.md)
and the optional Experimental `kongono/external-service-contract` package.

Phase 16's service binding, endpoint selection, credential reference,
resolution evidence, selected address, credential lease, provider adapter,
and current authority do not replace the Phase 15 policy gates or transport
boundary. Credential access and network access remain independent. Secrets
stay application-owned and out of binding/reference evidence and developer
output. The 27-declaration package reuses Phase 15 origin, capability,
resolver, and resolution evidence and provides ports only. Its borrowed
consumer signature cannot prevent copies or promise PHP zeroization. Phase
16C accepts [ADR 0165](../adr/0165-deterministic-external-service-binding-artifact-and-generated-php.md)
and the optional Experimental `kongono/external-service-binding-build` package.
One explicit typed secret-free source produces one canonical JSON artifact and
deterministic ordinary PHP. Structural validation or validation against one
explicit current source yields only inert evidence. Generated PHP is never
loaded or executed. Publication is explicit, path-safe, content-addressed,
atomic, idempotent for identical bytes, and no-clobber. Credential access and
network authority remain independent and absent. Phase 16D accepts
[ADR 0166](../adr/0166-explicit-bounded-system-resolver-adapter.md) and the
optional Experimental `kongono/external-service-resolver-system` leaf. One
explicit profile bounds one `ext-sockets` lookup using `SOL_TCP` and no
`ai_family` hint. Complete fail-closed validation, Phase 15 conservative
classification, canonical binary ordering, and exact selected membership
produce evidence for the existing exact-address transport boundary. The leaf
does not connect, grant network authority, access credentials, cache, retry,
fallback, or claim TTL, DNSSEC, resolver provenance, or a portable deadline.
Phase 16E accepts [ADR
0167](../adr/0167-operation-scoped-credential-lease-runtime.md). Its optional
runtime coordinates one caller-selected credential source and lease lifecycle
without receiving material or granting network authority; credential and
network permissions remain independent. Phase 16F accepts [ADR
0168](../adr/0168-exact-current-external-service-binding-application.md). The
optional application package validates one exact-current binding and egress
profile before composing one explicit resolver, transport, credential source,
authority-currentness port, cancellation port, and pair of clocks for one
operation. It adds no transport, resolver, provider, retry, fallback, command,
or network bypass. Phase 16G accepts [ADR
0169](../adr/0169-visibility-safe-external-service-inspection-and-bounded-evidence.md)
and adds only an offline visibility-safe evidence leaf over one exact supplied
profile and optional detached completed result. It cannot retrieve credentials,
resolve, connect, load generated PHP, or establish live network/provider truth.
Phase 16H accepts [ADR
0170](../adr/0170-phase-16-external-service-acceptance-and-checkpoint.md), adds
only explicit root-owned local acceptance over a selected loopback address and
an inert credential lease, and closes Phase 16 at one exact local checkpoint.
The proof does not establish public DNS, TLS, credential, provider, remote-
effect, deployment, or production-readiness truth. Stable/1.0 remains
unauthorized; Phase 17A is separately accepted below as documentation only.
Phase 17B adds one offline mapping leaf and Phase 17C composes one governed
one-shot attempt, and Phase 17D consumes one bounded SSE body through the same
attempt. Current topology is 88 packages / 366 local production edges /
acyclic; PHP inventories are 1,685 / 876 / 88.

## Relationship to Phase 17 provider mapping

Phase 17A accepts [ADR 0171](../adr/0171-first-party-openai-responses-adapter-boundary.md)
and the [adapter guide](openai-responses-provider-adapter.md) as documentation
only. Phase 17C accepts [ADR
0173](../adr/0173-one-shot-openai-responses-invocation-adapter.md); its optional
OpenAI Responses adapter uses this accepted egress application, resolver
evidence, exact selected-address binding, budgets, cancellation, and selected
transport. It cannot add direct cURL/socket behavior,
another HTTP or DNS stack, ambient proxy discovery, automatic retry, fallback,
reconnect, or replay.

Current egress authority remains application-owned and independent from a
credential lease or provider profile. Phase 17B adds request bytes as inert
offline evidence. Phase 17C constructs one exact outbound PSR-7 request only
inside borrowed credential access and delegates one governed attempt without
retry or replay. The application still owns the endpoint, credential source,
authority, resolver, and transport. This is not a live-provider compatibility
claim. Phase 17D accepts [ADR
0174](../adr/0174-pull-based-openai-responses-sse-streaming.md) without changing
this egress contract: the adapter advances only the returned PSR-7 body on
consumer pulls and closes it once. The transport may already have buffered the
body, so no immediate first-token, nonblocking, or realtime latency claim is
made. Phase 17E-H remain Planned.
