# Kongono External Service System Resolver

`kongono/external-service-resolver-system` is an optional Experimental adapter that implements the accepted external-service `EndpointResolver` port with PHP 8.5 `ext-sockets`. It performs one bounded operating-system `getaddrinfo()` lookup and returns Phase 15 resolution evidence. It does not connect, authorize network access, or access credentials.

## Installation and environment

Require the package explicitly and enable `ext-sockets`. The active PHP binding must expose `socket_addrinfo_lookup()`, `socket_addrinfo_explain()`, `AF_INET`, `AF_INET6`, `SOCK_STREAM`, positive integer `SOL_TCP`, `AI_NUMERICHOST`, and `AI_NUMERICSERV`. Construction fails with a nondisclosing `SystemResolverEnvironmentException` when that closed capability set is unavailable. The package neither edits PHP configuration nor supplies a fallback.

Create a `SystemResolverProfile` with `AddressFamilyPreference::Ipv4First` or `AddressFamilyPreference::Ipv6First` and an explicit maximum of 1 through 64 native candidates. Construct `SystemEndpointResolver` with that profile and pass it directly where the application owns an `EndpointResolver` collaborator.

## Resolution flow

Each `resolve()` call is fresh and isolated:

1. The resolver passes the exact canonical origin host and decimal port to one `socket_addrinfo_lookup()` call.
2. Its hints contain exactly `ai_socktype => SOCK_STREAM`, `ai_protocol => SOL_TCP`, and `ai_flags => AI_NUMERICSERV`. `AI_NUMERICHOST` is added only for a numeric literal. The `ai_family` key is deliberately omitted; in the PHP binding, omission expresses the fixed unspecified-family policy.
3. The native result count is checked before any entry is explained. A limit-plus-one result fails; it is never truncated.
4. Every entry is explained exactly once and must report `AF_INET` or `AF_INET6`, `SOCK_STREAM`, `SOL_TCP`, the matching address field, and the exact origin port. One malformed entry rejects the complete result.
5. Existing Phase 15 `ResolvedAddress` validation supplies canonical literal validation and conservative classification. Exact duplicates collapse, distinct candidates remain, and candidates are ordered independently of operating-system order: preferred family first, then ordinal `inet_pton()` bytes within a family.
6. The first ordered candidate is returned as the selected member of the complete Phase 15 `ResolutionEvidence` list.

Address categories are evidence, not permission. Public, private, loopback, link-local, multicast, unspecified, documentation, and reserved addresses remain visible so the current Phase 15 policy can examine the complete set and fail closed. There is no local/private exception. The accepted Phase 15 runtime and transport remain responsible for policy enforcement and exact selected-address binding.

## Failures

Ordinary resolution failures return `EgressResolutionResult` with `EgressFailureKind::Resolution`:

| Reason | Meaning |
| --- | --- |
| `system_resolver_lookup_failed` | The one native lookup returned failure. |
| `system_resolver_result_invalid` | At least one entry or address was malformed or incompatible. |
| `system_resolver_candidate_limit` | The native result exceeded the explicit bound. |
| `system_resolver_no_address` | The native result was empty. |

Profile and environment defects throw local package exceptions. Diagnostics retain only closed stage, violation, family-preference, count, limit, and bounded position evidence. Hostnames, ports, addresses, native messages, resolver configuration, credentials, and provider data are omitted.

## Lifecycle and residual risk

There is no cache, memoization, retry, fallback, family-specific second lookup, background refresh, watcher, pool, retained session, or shared mutable result. Sequential calls may legitimately differ. The PHP API exposes no portable per-call deadline or cancellation control, so a system lookup may block according to operating-system resolver policy.

The returned snapshot is not proof of DNS TTL, expiry, freshness, DNSSEC, authenticity, authoritative RRset completeness, resolver-cache identity, hosts-file provenance, search-domain behavior, name-server identity, endpoint availability, certificate identity, provider compatibility, network authority, credential access, or successful connection.

Tests use the package-test-only fake lookup for hostname and adversarial behavior. Native tests are restricted to numeric loopback literals with `AI_NUMERICHOST`; they do not depend on public DNS. Applications that require asynchronous cancellation, explicit resolver provenance, DNSSEC evidence, portable deadlines, or another resolution policy should supply a different separately governed `EndpointResolver` adapter.

Stability: the exception marker is Public; the profile, family preference, resolver, and concrete exceptions are Experimental; all native machinery and diagnostics are Internal. Nothing in this package is Stable or a 1.0 compatibility promise.
