# ADR 0155: Explicit Outbound HTTP and Governed Network Egress Boundary

## Status

Accepted.

## Date

2026-09-07.

## Phase

15A.

## Context

Kongono has an accepted inbound HTTP message model and provider-neutral model
invocation boundaries, but it does not yet provide a general outbound HTTP
client or network-egress runtime. Applications may eventually need external
APIs, webhooks, observability exporters, model providers, payment services,
storage services, search services, or other selected endpoints. Those uses
share security and lifecycle concerns that do not belong in any one provider
adapter.

A request URI, provider profile, DNS answer, connection result, response, or
previous success cannot safely stand in for permission. Network input is
untrusted, address resolution can change between validation and connection,
redirects can cross authority boundaries, partial writes create uncertain
effects, and persistent runtimes can leak state across operations. Hidden
proxy, credential, cookie, retry, and connection-pool behavior would make those
risks harder to reason about.

Phase 15A therefore accepts architecture and a fixed roadmap before selecting
contracts or implementation mechanisms. It creates no executable network
behavior.

## Decision

Kongono will place an optional, provider-neutral outbound HTTP and governed
network-egress platform beneath future external-service adapters. The platform
will keep application-owned intent, authority, policy, credentials, endpoint
selection, transport selection, and external-service semantics explicit.

The conceptual dependency direction is:

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

Arrows describe dependency order, not execution authority. No lower package may
depend on a provider SDK, provider-specific request type, global HTTP client,
ambient service locator, mutable registry, framework container lookup, or
environment-driven transport discovery.

## Authority separation

The following are evidence and never grant network authority:

- a syntactically valid URI or PSR-7 request;
- a route, model profile, provider identity, Graph fact, extension manifest,
  configuration file, or generated artifact;
- a DNS answer, IP address, TLS certificate, redirect, response, status code,
  receipt, cache entry, observability signal, or previous success; and
- Chie, Hantei, Oshie, test, inspection, or model output.

Only explicit application-owned policy and authority supplied for the current
operation may permit an attempt. Current authorization must be rechecked before
each connection, redirect, retry, proxy hop, or other possible external effect.
A model result or proposed Agent action cannot authorize network access or
expand a capability grant.

## URI, origin, resolution, and SSRF boundary

Request URIs are untrusted input. The architecture keeps these typed concepts
distinct:

- the requested URI;
- the canonical origin;
- the policy-permitted origin;
- resolution evidence;
- the selected connection address;
- a transport attempt;
- the response origin; and
- bounded attempt receipts.

None silently substitutes for another. A future runtime must fail closed for
malformed authority, user information, fragments, unsupported schemes,
ambiguous host syntax, invalid ports, control characters, conflicting host
metadata, address-family ambiguity, forbidden address classes, or policy
mismatch.

SSRF defense must consider loopback, private, link-local, multicast,
unspecified, documentation/reserved, cloud-metadata, Unix-socket, named-pipe,
and other non-public destinations. There is no ambient local-development or
private-network exception. A future explicit policy may permit a narrowly
bounded destination, but hostname-only allow decisions are insufficient.

Resolution and connection selection remain bound. Validating one address and
then connecting to another is prohibited. Redirects require complete
revalidation. DNS rebinding, resolution races, mixed allowed and forbidden
answers, IPv4/IPv6 differences, and proxy resolution must produce visible,
deterministic outcomes rather than best-effort assumptions.

## TLS, proxies, redirects, cookies, and credentials

HTTPS is the secure default direction. Request data, provider output, redirects,
or hidden environment state cannot disable certificate or hostname
verification. Trust-store, client-certificate, protocol-version, and proxy
choices are explicit host or application policy; they are not request headers
or artifact secrets.

Ambient proxy variables, platform credential stores, `.netrc`, global cookie
jars, automatic cookies, implicit redirects, and automatic authentication are
prohibited. Later phases may accept explicit bounded ports or profiles, but
Phase 15A selects none.

Credentials remain application-owned and operation-scoped. They must not enter
deterministic artifacts, generated code, Graph evidence, logs, diagnostics,
exceptions, receipts, inspection, Chie/Hantei/Oshie output, or test fixtures.
Authorization headers and signed request material must not be copied into
non-secret evidence.

Redirects are denied by default. Any later permitted redirect policy must bound
hop count, origin changes, method and body transformation, credential
forwarding, resolution, and downgrade behavior. HTTPS-to-HTTP downgrade is
prohibited.

## Budgets, streaming, cancellation, and cleanup

Every future operation requires explicit finite ceilings for applicable:

- connection attempts and DNS results;
- redirect hops and retries;
- request, response, header, decompressed, buffered, and retained-evidence
  bytes, including decompression expansion ratio;
- connect, first-byte, and total time; and
- concurrency.

The architecture is stream-aware and backpressure-aware. Content length,
transfer encoding, compression, and protocol framing are untrusted evidence. A
response cannot force unbounded buffering, and limit-plus-one must fail
visibly. Partial request transmission, partial response receipt, no attempt,
and completed success remain distinct outcomes.

Cancellation and monotonic time are explicit collaborators. Cleanup is
deterministic on success, failure, cancellation, budget exhaustion, timeout,
redirect denial, parser rejection, and transport uncertainty. Destructor-only
cleanup is not accepted.

Persistent runtimes may reuse connections only through an explicitly owned,
bounded pool with lifecycle, isolation, retirement, and no cross-operation
credential or response leakage. Static mutable clients and ambient current
requests are prohibited.

## Retry and uncertain effects

There is no automatic retry by default. A later retry profile must be explicit,
finite, observable, budgeted, and aware of methods and bodies. A nominally
idempotent method is not proof that replay is safe. Request-body replayability,
idempotency keys, application semantics, bytes transmitted, response state,
and transport uncertainty all matter.

An unknown effect after a partial write or lost response is not
failure-before-effect and does not authorize replay. Retry, fallback, hedging,
racing, and failover remain separate future decisions; a transport exception
implies none of them.

## HTTP versions and interoperability

Neutral semantics do not assume HTTP/1.1, HTTP/2, or HTTP/3. Negotiated
protocol, multiplexing, connection coalescing, header compression, early data,
and transport capabilities are bounded evidence. They cannot weaken origin,
authority, TLS, isolation, ordering, cancellation, or disclosure rules.

Kongono will reuse accepted immutable PSR-7 message interfaces where their
semantics fit. PSR-18 interoperability is an optional outer adapter concern,
not the core control plane: PSR-18 alone does not express resolver binding,
egress authority, budgets, streaming lifecycle, cancellation, redirects, retry
uncertainty, pool ownership, or cleanup proof.

Phase 15A selects no ext-curl, PHP streams, sockets, event loop, third-party
client, PSR-18 implementation, HTTP/2 stack, or HTTP/3 stack. A later transport
row may select one exact first-party profile under separate authenticated
authority.

## Diagnostics, observability, and developer experience

Future failures must be actionable without exposing full URLs, query strings,
credentials, headers, cookies, bodies, resolved private addresses, proxy
secrets, certificates, filesystem paths, or foreign exception text. Bounded
Safe context should distinguish validation, policy denial, resolution,
connection, TLS, protocol, redirect, budget, cancellation, timeout, response,
cleanup, and uncertain-effect classes.

Operational evidence may contain allowlisted aggregate facts and correlation,
but it is not authority and must not become a secret side channel. Security
Audit may receive bounded decisions without request or response payloads.

The safe developer path is immutable input, explicit policy, one clear
lifecycle, typed outcomes, deterministic cleanup, meaningful remediation,
deterministic local fakes, and no hidden global state. Applications that do not
use outbound HTTP incur no transport requirement. Advanced features do not
burden the default path.

Chie may explain accepted policy and bounded failures. Hantei may verify
deterministic artifacts and offline conformance. Oshie may teach the accepted
model. None may call a network, supply credentials, authorize an origin, alter
policy, retry work, or certify live safety.

## API status and portability

New Phase 15 runtime APIs begin Experimental. Internal validators, parsers,
resolvers, adapters, lifecycle helpers, and platform mechanisms remain Internal
unless separately promoted. Accepted architecture and public marker contracts
do not imply Stable or 1.0 status.

Provider and transport change stays isolated in outer adapters. Neutral
contracts remain independent of vendor SDK release cycles. PHP 8.5 is the
current floor under the accepted support policy; Phase 15A changes no
constraint and makes no PHP 8.6 or PHP 9 support claim.

## Consequences

- Future external-service adapters gain a common security and lifecycle
  foundation without embedding vendor semantics in neutral contracts.
- Applications retain responsibility for current authority, credentials,
  endpoint and transport selection, and service-specific meaning.
- DNS, transport, streaming, redirects, retry, pooling, and uncertainty become
  explicit design work rather than hidden client defaults.
- Implementations require more explicit collaborators and evidence, but remain
  locally testable and replaceable.
- Kongono remains fully usable without outbound networking, AI, a provider,
  credentials, an account, or Internet access.

## Alternatives rejected

- **Put networking inside model-provider adapters.** Rejected because egress
  security and lifecycle apply to many external services and provider semantics
  must remain outside the neutral layer.
- **Adopt a global HTTP client or ambient transport discovery.** Rejected
  because authority, credentials, proxy behavior, lifecycle, and cleanup would
  become hidden.
- **Treat URI validation or DNS allowlisting as authorization.** Rejected
  because syntax and resolution evidence are not current application authority
  and do not prevent address substitution or rebinding.
- **Use PSR-18 as the complete control plane.** Rejected because it does not
  encode Kongono's resolver binding, authority, budgets, cancellation,
  uncertainty, pooling, or cleanup requirements.
- **Choose a transport in Phase 15A.** Rejected because the architecture must
  be accepted before a separately governed transport profile is selected.

## Current and planned behavior

Phase 15A is Accepted documentation architecture only. It implements no HTTP
client, transport, socket, DNS resolver, TLS stack, credential mechanism, retry
engine, provider adapter, command, package, schema, PHP API, or network
operation. Phase 15B and every later row remain planned and require separately
authenticated directives.

The fixed sequence is: 15A architecture, 15B neutral contracts, 15C
deterministic policy evidence, 15D bounded runtime, 15E explicit transport and
interoperability profile, 15F exact-current application workflow, 15G
visibility-safe inspection, and 15H external acceptance and checkpoint. Phase
15 ends at 15H and does not begin Phase 16 automatically.

## Prohibited Phase 15A scope

Phase 15A adds no executable behavior, package, API, schema, dependency,
transport selection, credential source, provider integration, command, test,
network activity, release, compatibility promise, or security certification.
It does not widen Agent, model-invocation, Security, Observability, Graph,
Extension, Composition, Kernel, HTTP Server, Web Runtime, or developer-tool
authority.

## Related material

- [Outbound HTTP and network egress](../architecture/outbound-http-and-network-egress.md)
- [Phase 15 roadmap](../architecture/phase-15-roadmap.md)
- [HTTP message model](../architecture/http-message-model.md)
- [Identity and access security](../architecture/identity-and-access-security.md)
- [Observability and operational evidence](../architecture/observability-and-operational-evidence.md)
- [Provider-neutral model invocation](../architecture/provider-neutral-model-invocation.md)
- [ADR 0154: Phase 14 External Model-Invocation Acceptance and Checkpoint](0154-phase-14-external-model-invocation-acceptance-and-checkpoint.md)
