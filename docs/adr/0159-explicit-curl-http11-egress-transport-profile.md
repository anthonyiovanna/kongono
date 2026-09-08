# ADR 0159: Explicit cURL HTTP/1.1 egress transport profile

- Status: Accepted
- Date: 2026-09-07

## Context

Phase 15D defines a provider-neutral one-operation coordinator but deliberately
supplies no resolver or transport implementation. The first first-party
transport must preserve the runtime's selected-address binding, bounded
evidence, failure honesty, and cleanup ownership without creating a global HTTP
client or new control plane.

Handwritten TLS or HTTP framing would duplicate security-critical protocol
implementations. A framework-scale client would add dependency and behavior
surfaces—discovery, redirects, retries, middleware, pooling, and generic target
selection—that are outside this phase. Generic PSR-18 is useful at an outer
application boundary, but its contract cannot prove exact selected-address
binding, authority rechecks, transfer budgets, cleanup, or possible-effect
evidence.

## Decision

Accept the optional Experimental `kongono/network-egress-curl` package. It
implements the Phase 15D `EgressTransport` port with one fresh PHP `ext-curl`
easy handle per session and offers exactly HTTP/1.1, TLS, and request streaming.

The URL retains the canonical origin hostname. `CURLOPT_RESOLVE` binds that host
and port to the single Phase 15D selected IPv4 or IPv6 literal, preserving the
hostname for the Host field, SNI, and strict certificate verification. Only
`http` and `https` are admitted. Peer verification remains enabled and hostname
verification remains strict.

The caller supplies 1 to 1,048,576 bytes of CA-bundle PEM material directly.
The adapter copies those bytes and supplies them through
`CURLOPT_CAINFO_BLOB`; it never reads a path, environment variable, operating
system store, credential provider, or remote source. Plain HTTP requires an
explicit adapter opt-in in addition to Phase 15C policy.

The request body is read exactly once from its current cursor. The adapter does
not rewind, clone, stringify, hash, restore, retry, or replay it. Response status
and headers are validated before a first-party PSR-7 response is constructed.
The transfer-decoded, still content-encoded body is retained only in bounded
`php://memory` storage and starts at position zero for the caller.

Proxy use, redirects, cookies, netrc, automatic authentication, automatic
content decoding, retries, fallback, racing, pooling, sharing, global handles,
and caller-supplied cURL options are absent. Numeric cURL codes and observed
transfer state map to existing neutral failure kinds and closed reason tokens.
Localized cURL errors, URLs, addresses, headers, bodies, CA bytes, certificates,
paths, and foreign messages are never retained in diagnostics or deterministic
exception strings.

The exception marker is Public. The trust, profile, transport, and two concrete
exceptions are Experimental. Diagnostics, the abstract exception, and all
native collaborators are Internal. The package depends inward on API metadata,
diagnostics, HTTP messages, the neutral contract, and the runtime; no existing
package depends on this optional adapter.

## Consequences

Kongono reuses a maintained native HTTP/TLS implementation while keeping policy,
resolution, authority, budgets, uncertainty, and lifecycle coordination in
Phase 15D. One lazy synchronous execution yields one terminal emission and then
`null`; explicit close is idempotent. The fixed profile does not advertise
HTTP/2, HTTP/3, response streaming, mid-transfer cancellation, proxying, mutual
TLS, trailers, pooling, or generic provider support.

Offline tests prove exact options, mapping, limits, nondisclosure, and lifecycle
without performing a request. Loading `ext-curl` alone is not a compatibility
certification. Phase 15H must provide local external conformance before any
broader supported-platform claim.

## Rejected alternatives

- Handwritten sockets, TLS, or HTTP framing: duplicates security-critical stacks.
- Symfony HttpClient, Guzzle, HTTPlug, or an async runtime: enlarges dependencies
  and introduces behavior not authorized by this profile.
- PSR-18 as the Phase 15D transport: cannot establish the required address,
  evidence, budget, and cleanup contracts.
- Ambient CA paths or operating-system trust fallback: makes trust selection
  external and unverifiable.
- HTTP/2 or HTTP/3 negotiation: would overstate current capability evidence.

## Boundary

Phase 15E adds no resolver, application workflow, generated wiring, inspection
command, provider adapter, or public compatibility certification. Phase 15F is
implemented separately under ADR 0160 and Phase 15G's offline evidence leaf
under ADR 0161. Phase 15H remains Planned.
