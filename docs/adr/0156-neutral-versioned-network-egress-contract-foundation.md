# ADR 0156: Neutral Versioned Network-Egress Contract Foundation

- Status: Accepted
- Date: 2026-09-07
- Phase: 15B

## Context

ADR 0155 separates application-owned intent and authority from policy,
resolution, transport, response, and receipt evidence. Kongono now needs the
first executable Phase 15 boundary without prematurely choosing a resolver,
transport, client, provider, credential mechanism, or runtime.

PSR-7 already provides request and response message semantics. Reusing those
interfaces avoids inventing a second message model, but a PSR-7 URI or message
must not become the network control plane. In particular, a message does not
authorize its destination and its body stream cannot be treated as deeply
immutable.

## Decision

Accept the optional Experimental kongono/network-egress-contract package with
namespace Kongono\NetworkEgressContract and contract version 1.

The package defines immutable bounded values for operation, attempt, and
receipt identities; canonical HTTP origins; application-owned authority and
exact policy references; finite budgets; cancellation and lifecycle evidence;
declared transport capabilities and negotiated-protocol vocabulary; transfer
evidence; typed outcomes, failures, receipts, operations, and results.

The package depends directly on kongono/api-metadata, kongono/diagnostics, and
psr/http-message only. ExceptionInterface is Public. Consumer-facing values
are Experimental. The abstract exception base, diagnostic enum, and guard are
Internal. Nothing is Stable.

CanonicalOrigin contains only an HTTP or HTTPS scheme, an already-normalized
lowercase ASCII DNS, IPv4, or bracketless canonical IPv6 host, and an effective
port. It performs no DNS or platform lookup. Host and origin content remains
Protected evidence and never enters diagnostic output.

EgressOperation retains one caller-supplied PSR-7 RequestInterface together
with its explicitly matching origin, authority reference, policy reference,
identity, and budget. Construction may inspect URI components but never reads,
rewinds, seeks, writes, stringifies, serializes, hashes, buffers, or copies the
body stream. EgressOperationResult may retain a PSR-7 ResponseInterface only
for a successful receipt, and its construction likewise never reads the body.

Receipts enforce coherent terminal relationships among outcome, lifecycle,
attempt identity and count, transfer state, cancellation, negotiated protocol,
and declared capability evidence. A receipt does not prove remote processing
and never grants retry authority.

Failures retain only one closed kind and one bounded lowercase snake-case
reason. The eight KGN-EGRESS-CONTRACT-1501 through
KGN-EGRESS-CONTRACT-1508 diagnostics expose only closed Safe fields.
Exception stringification is deterministic, bounded, and omits native trace
text and Protected inputs.

## Consequences

Applications gain an inspectable provider-neutral contract that later phases
may consume. The current graph becomes 76 acyclic production packages with 291
local production edges. PHP inventories become 1,386 production files, 702
package-test files, and 80 root-test files.

This phase does not authorize, resolve, connect, negotiate TLS, apply
credentials, send, receive, redirect, retry, meter live streams, manage pools,
load policy, or perform provider work. It adds no PSR-18 dependency or client,
no concrete PSR-7 implementation, and no third-party lock package.

## Alternatives rejected

- Treating PSR-18 as the control plane would collapse application authority,
  policy, and runtime boundaries into a transport call.
- Storing serialized messages or body digests would read caller streams and
  make false immutability and disclosure claims.
- Embedding permission booleans in references would confuse identity with a
  current authorization decision.
- Choosing a concrete transport or provider in this package would violate the
  provider-neutral Phase 15 ordering.

## Security and operational notes

The package performs no I/O and owns no credential, endpoint, resolver,
transport, pool, background work, lifecycle engine, or mutable global state.
Its values are evidence rather than authority or external truth. Runtime
currentness, SSRF policy, address classification, resolution binding,
credentials, live budgets, cancellation, cleanup, and effects remain outside
Phase 15B.

## Status of subsequent work

Phase 15A and Phase 15B are Accepted. Phase 15C remains planned and requires a
separately authenticated directive. No Stable, 1.0, provider, transport,
performance, support, security-certification, PHP 8.6, or PHP 9 promise is
made.

## Related records

- [ADR 0155](0155-explicit-outbound-http-and-governed-network-egress-boundary.md)
- [Network-egress contract guide](../architecture/network-egress-contract-foundation.md)
- [Outbound HTTP and network egress](../architecture/outbound-http-and-network-egress.md)
- [Phase 15 roadmap](../architecture/phase-15-roadmap.md)
