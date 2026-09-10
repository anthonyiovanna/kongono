# ADR 0166: Explicit bounded system resolver adapter

- Status: Accepted
- Date: 2026-09-09

## Context

Phase 15 accepted a provider-neutral resolver port, conservative address classification, and exact selected-address transport binding. Phase 16A-C added secret-free external-service binding evidence without supplying a concrete endpoint resolver. Applications need an optional adapter that can obtain operating-system address candidates while preserving the separation between resolution evidence, network authority, credential access, and transport effects.

PHP 8.5 `ext-sockets` exposes `socket_addrinfo_lookup()` and `socket_addrinfo_explain()` over the operating system's `getaddrinfo()` result. This supports IPv4 and IPv6 without introducing a third-party dependency or implementing DNS protocol behavior. The active portable PHP binding uses `SOL_TCP` for TCP and expresses an unspecified-family lookup by omitting the `ai_family` hint; passing `ai_family => 0` is not portable to Windows PHP.

The native API does not expose DNS TTL, DNSSEC validation, authoritative RRset completeness, resolver-cache identity, hosts-file provenance, search-domain provenance, or a portable per-call deadline or cancellation control. A truthful adapter must not fabricate those claims.

## Decision

Create the optional Experimental `kongono/external-service-resolver-system` leaf package. It implements the existing external-service `EndpointResolver` port and depends inward on the API metadata, diagnostics, external-service contract, network-egress contract, and network-egress runtime packages plus PHP and `ext-sockets`. No existing package depends on it.

One explicit immutable profile selects IPv4-first or IPv6-first ordering and a maximum of 1 through 64 native candidates. Each resolution performs exactly one `socket_addrinfo_lookup()` call with the exact canonical origin host, decimal port, `SOCK_STREAM`, `SOL_TCP`, and `AI_NUMERICSERV`; numeric literals additionally use `AI_NUMERICHOST`. The hints contain exactly those three keys and omit `ai_family` to express the fixed unspecified-family policy.

The adapter checks the native count before explaining entries, explains every entry exactly once, and fails the complete operation for any malformed, unsupported-family, wrong-type, wrong-protocol, wrong-port, invalid, mapped, zone-bearing, noncanonical, family-mismatched, or unclassifiable result. It never skips an entry or returns partial success. Exact duplicate canonical addresses collapse. All distinct accepted candidates are ordered deterministically by the explicit family preference and ordinal `inet_pton()` bytes; the first ordered address is the exact selected member.

The adapter reuses Phase 15 `ResolvedAddress` validation and conservative classification. It does not filter public, private, loopback, link-local, multicast, unspecified, documentation, or reserved categories. Existing current Phase 15 policy examines the complete candidate set, and the Phase 15 runtime and transport retain responsibility for exact selected-address binding.

The package has no cache, memoization, retry, fallback, second family lookup, connection, socket I/O, transport, provider behavior, service discovery, credential access, network-authority decision, generated code, command, registry, Graph integration, inspection, telemetry, persistence, or background work. Resolution and selected-address evidence grant neither network authority nor credential access.

The exception marker is Public. The family preference, profile, resolver, and concrete profile/environment exceptions are Experimental. Diagnostics, the abstract package exception, and native implementation declarations are Internal. Nothing is Stable.

## Consequences

Applications can explicitly opt into a small operating-system resolver adapter that returns a complete bounded snapshot compatible with Phase 15 policy and transport boundaries. Tests can inject the Internal lookup port to prove deterministic behavior without public DNS, while native coverage is limited to numeric loopback literals.

System resolution may block according to operating-system policy because the PHP boundary supplies no portable per-call deadline or cancellation mechanism. Sequential calls may differ. A result establishes no TTL, expiry, freshness, DNSSEC, resolver provenance, anti-spoofing, endpoint availability, certificate identity, provider compatibility, connection success, authority, or credential access.

## Rejected alternatives

- `gethostbyname()` and `gethostbynamel()` are IPv4-oriented and do not provide the required explained dual-family result.
- `dns_get_record()` exposes DNS-record mechanics rather than the selected operating-system `getaddrinfo()` policy and still would not grant authority.
- cURL, streams, or raw sockets would mix resolution with transport or connection behavior.
- Subprocesses and FFI would expand process and native-code authority.
- Handwritten DNS would introduce protocol, parser, transport, and security scope.
- A third-party resolver would add an unrequested dependency and a different lifecycle and trust boundary.
- Cache, retry, racing, fallback, or background refresh would introduce unapproved currentness and concurrency semantics.

## Phase boundary

Phase 16D ends at detached system-resolution evidence. Phase 16E remains separately planned for credential-lease runtime behavior and is not authorized by this decision.
