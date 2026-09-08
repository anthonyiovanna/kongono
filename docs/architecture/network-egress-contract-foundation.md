# Network-Egress Contract Foundation

## Current status

Phase 15A through Phase 15H are Accepted. The optional Experimental
kongono/network-egress-contract package is implemented as an inert,
provider-neutral value boundary. Phase 15C adds deterministic policy evidence,
and Phase 15D adds the separate bounded runtime.

The current graph is 81 packages with 317 local production edges and is
acyclic. Inventories are 1,514 production PHP files, 768 package-test PHP
files, and 84 root-test PHP files.

## Why this boundary exists

An application needs to describe intended outbound HTTP work without letting a
URI, request, model output, policy label, capability claim, response, or
receipt become permission. The contract keeps six concerns explicit:

1. operation identity and the exact caller-supplied request;
2. canonical origin evidence;
3. application-owned authority and policy references;
4. finite declared budgets;
5. transport, transfer, cancellation, and lifecycle evidence; and
6. one coherent terminal result and receipt.

This separation lets later policy and runtime packages fail closed without
turning an inert value into a hidden service locator or network client.

## Canonical origins

A CanonicalOrigin contains only OriginScheme::Http or OriginScheme::Https, one
lowercase ASCII host, and one effective port from 1 through 65,535. DNS names,
canonical IPv4 literals, and bracketless canonical IPv6 literals are accepted
without lookup. Unicode, implicit IDNA conversion, uppercase, whitespace,
controls, delimiters, zone identifiers, malformed addresses, and ambiguous
authorities are rejected.

Derivation from a PSR-7 URI requires an absolute HTTP or HTTPS URI with no user
information or fragment. Default ports become 80 or 443. Path and query are
not retained by the origin value. Host and origin text is Protected evidence
and is never disclosed by exceptions.

## References and budgets

EgressAuthorityReference identifies one application-owned authority record and
positive revision. It does not contain a permission result or prove
currentness. EgressPolicyReference identifies one exact policy and uppercase
SHA-256 digest. It loads nothing.

EgressBudget requires every connection, redirect, resolution, request,
response, header, buffer, retained-evidence, and timeout limit explicitly.
Limits are finite and timeout relationships are validated at construction.
The value declares intent; it does not meter runtime activity.

## Request and response handling

EgressOperation retains the exact PSR-7 request reference and checks only that
its URI derives the explicitly supplied canonical origin. It does not inspect
headers for credentials, mutate the request, authorize the origin, load
policy, resolve the host, or perform I/O.

No operation or result constructor or accessor calls a request or response body
stream method. Streams are retained by reference and are not claimed deeply
immutable. No message is serialized, stringified, hashed, copied, or buffered.
PSR-7 supplies message semantics; PSR-18 is not the control plane.

## Terminal evidence

Closed enums describe cancellation, lifecycle, capabilities, negotiated
protocol, transfer state, outcome, and failure kind. Capability sets are
nonempty, unique, bounded, and in enum order. NegotiatedProtocol::Unknown is
honest evidence, not a fallback instruction.

Transfer evidence records bounded request and response byte counts with state
relations. Receipts validate terminal lifecycle, attempt, transfer,
cancellation, protocol, and outcome coherence. Successful results require a
response and forbid a failure. Other outcomes forbid a response and require
one bounded failure.

Neither a response nor a receipt proves remote processing, authorizes retry,
grants a capability, or establishes external truth.

## Diagnostics and failure

All deliberate construction failures implement the package exception marker.
Diagnostics use exactly KGN-EGRESS-CONTRACT-1501 through
KGN-EGRESS-CONTRACT-1508. Context fields are closed Safe violation tokens,
enum values, counts, limits, and positions. Raw URI, origin, host, address,
header, cookie, credential, query, path, body, certificate, filesystem path,
arbitrary reason, and foreign exception text are omitted.

## Side effects, lifecycle, and concurrency

Construction is synchronous and side-effect free. The package has no DNS,
socket, TLS, HTTP client, provider, credential, policy loader, redirect or
retry engine, pool, registry, compiler, artifact, command, clock, random
source, process, filesystem access, background work, or mutable static state.

Value objects may be shared like ordinary immutable control-plane evidence.
Caller-owned PSR-7 streams can still be mutable, so callers remain responsible
for their concurrent and lifecycle use.

## Stability and next step

The package is Experimental and optional. It makes no provider, transport,
performance, security-certification, Stable, 1.0, PHP 8.6, or PHP 9 claim.
Phase 15C adds the separate deterministic application-owned policy source and
artifact boundary, Phase 15D the bounded runtime, Phase 15E the explicit cURL
profile, Phase 15F the exact-current application workflow, and Phase 15G the
offline bounded inspection leaf. None widens the neutral contract's authority.

See [ADR 0156](../adr/0156-neutral-versioned-network-egress-contract-foundation.md),
the [general egress architecture](outbound-http-and-network-egress.md), and the
[Phase 15 roadmap](phase-15-roadmap.md).

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
