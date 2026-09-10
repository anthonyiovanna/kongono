# ADR 0163: Explicit External-Service Binding and Secret-Lease Boundary

## Status

Accepted.

## Date

2026-09-08.

## Phase

16A.

## Context

Phase 15 closes Kongono's optional provider-neutral governed network-egress
platform at commit `b4cb57ffa01eea130a693daee0be605ae7f0dda5`. That platform
deliberately leaves resolver implementation, credential access, service
binding, provider adapters, and production endpoint selection to separately
authorized layers and application-owned collaborators.

Applications that call an external service need a safe way to name an endpoint
and a credential reference without placing secret material in deterministic
configuration. They also need resolution evidence that remains tied to the
address actually used by transport and a short-lived credential value whose
ownership and retirement are explicit. These needs do not justify ambient
discovery, a universal service client, or authority inferred from evidence.

Phase 16A establishes documentation architecture only. It creates no package,
PHP API, schema, secret, resolver, credential source, provider adapter,
configuration reader, command, network operation, or runtime behavior.

## Decision

Phase 16 defines an optional external-service binding layer above the accepted
Phase 15 egress platform and below future service/provider adapters.

### Architectural position and dependency direction

```text
application-owned service intent and current authority
  -> exact-current secret-free service-binding evidence
  -> explicit endpoint selection and credential reference
  -> operation-scoped resolver and credential-source ports
  -> bounded resolution evidence plus one credential lease
  -> existing Phase 15 exact-current egress application
  -> optional service/provider adapter with service-specific semantics
  -> bounded result and receipts
  -> deterministic reverse cleanup and lease retirement
```

These arrows describe permitted dependency direction after validation. They do
not grant authority, select a provider, discover a secret, authorize an
endpoint, or prove an external effect.

The layer is not a universal remote-service abstraction or a lowest-common-
denominator request API. Service/provider adapters continue to own
service-specific paths, headers, authentication application, request/response
mapping, versions, capabilities, rate semantics, and compatibility claims.

### Service binding, resolution evidence, and credential lease are distinct

1. **Service binding evidence** is immutable, deterministic, versioned,
   secret-free evidence naming an application-owned service profile, endpoint
   constraints, one exact credential reference, required capabilities, and
   provenance.
2. **Resolution evidence** is bounded evidence for one explicit hostname
   resolution attempt: its complete accepted answer set, classification,
   selection, timing or expiry metadata where available, and selected-address
   binding.
3. **Credential lease** is an operation-scoped sensitive value obtained from
   one explicit caller-supplied source for one exact reference and purpose and
   retired deterministically after use.

None substitutes for another. None is network authority, authentication,
authorization, a capability grant, provider truth, endpoint availability, or
proof of effect.

### Evidence is not authority

Service, profile, and provider names; endpoint data; credential references and
lease metadata; DNS answers; selected addresses; TTLs; certificates;
responses; receipts; and previous success remain evidence. Deterministic
artifacts, generated PHP, Graph and Extension facts, observations, audit
events, inspection, verifier output, model plans and results, action
suggestions, Agent proposals, Agent Principal evidence, tests, documentation,
and developer-tool output remain evidence as well.

Only explicit current application-owned authorization and the accepted Phase
15 policy and authority gates may permit an external attempt. Credential
access and network access are independent permissions. Possessing either never
implies the other.

### Secret-free binding evidence

Deterministic sources and artifacts, generated PHP, Application Graph facts,
inspection, commands, diagnostics, logs, traces, metrics, operational events,
security audit, receipts, tests, fixtures, and completion reports contain no
credential material.

Binding evidence may contain only a bounded opaque credential reference and a
non-secret source or profile identity. It does not contain API keys, bearer
tokens, passwords, client secrets, private keys, certificates with private
material, cookies, signed requests, authorization headers, secret lengths,
secret hashes, secret prefixes or suffixes, secret-derived identifiers, or
provider error bodies.

Artifact validity and exact currentness do not prove that a secret exists, is
valid, belongs to an account, has permission, or will work.

### Credential leases and PHP memory truth

A future credential lease is operation-scoped, purpose-bound, explicitly
owned, non-serializable by contract, non-stringifiable, non-cloneable where PHP
permits, and absent from generic debug and inspection surfaces. It is acquired
as late as practical and retired during deterministic cleanup.

Kongono does not claim reliable memory zeroization of ordinary PHP strings.
Runtime copies, allocator behavior, extensions, operating-system memory, crash
dumps, and process inspection can retain historical bytes. The accepted
direction is to minimize lifetime and copies, prevent ordinary disclosure,
release references promptly, isolate operations, and document residual risk.

A cleanup or retirement method may release owned references and ask an adapter
to destroy or revoke its lease. Success is not proof that every historical byte
was physically erased.

### Credential-source boundary

Credential sources are explicit caller-supplied outer adapters. There is no
ambient environment-variable scan, `.env` loading, platform-keychain lookup,
cloud-metadata call, filesystem search, provider-SDK discovery, default vault,
global secret registry, static current credential, facade, container lookup,
or service locator.

Future environment, file, operating-system, cloud-vault, hardware-backed, or
remote secret-manager adapters require separate selection and threat models.
Phase 16A selects none.

Source failure, absence, denial, stale version, unsupported reference,
ambiguous value, expiry, revocation, rotation, and cleanup failure remain
internally distinguishable typed outcomes while public presentation stays
non-disclosing. A missing credential never causes automatic provider fallback
or selection of a weaker source.

### Endpoint and resolution boundary

Binding sources may describe normalized bounded endpoint constraints only:
scheme, host, port, an optional provider-owned base path, and required
transport capabilities. User information and fragments are prohibited. Query
strings and credential material do not belong in a base endpoint profile.

Endpoint selection, hostname resolution, address selection, transport
connection, and service/provider semantics remain separate decisions. A future
resolver accepts one exact requested canonical origin and returns either one
bounded complete result or a typed failure. It preserves IPv4, IPv6, and
address-class distinctions and rejects malformed, ambiguous, partial,
mixed-policy, truncated, or unverifiable results.

Phase 15 policy classifies every candidate. One exact permitted address is
selected deterministically under an explicit profile and bound to the actual
transport connection so validation of one address cannot lead to connection
to another. DNS answers, TTLs, caches, resolver success, and previous
connections remain evidence and grant no policy exception. There is no ambient
local or private-network exception.

### Caching, rotation, and currentness

No resolver or credential cache exists by default. Any later cache requires
complete explicit keys, finite bounds, expiry, invalidation, isolation,
currentness, retirement, failure semantics, and proof that it cannot widen
authority or leak across operations.

Credential rotation and DNS change are normal. Exact-current binding evidence
therefore identifies references and constraints, not mutable secret bytes or
the truth of a DNS answer. Each operation obtains fresh caller-relative
currentness evidence at the required boundaries.

A stale artifact, expired lease, changed reference, changed endpoint,
unsupported capability, changed policy, changed resolution, or uncertain
cleanup fails visibly. It does not authorize retry, fallback, reuse, or
best-effort continuation.

### Lifecycle and persistent runtimes

Each future operation has one explicit owner and deterministic state
transitions. Resources are acquired in order and released in reverse order.
All required cleanup is attempted while primary and ordered cleanup failures
remain distinct.

After retirement, an operation retains no secret material, resolver answers,
response state, provider state, or ambient current binding. Sequential,
overlapping, cancelled, failed, and cleanup-failed operations in one persistent
process remain isolated.

Phase 16A selects no automatic retry, refresh, re-resolution, credential
reacquisition, provider fallback, endpoint failover, token refresh, background
renewal, watcher, scheduler, daemon, pool, or retained session. Each would
require separate finite policy and uncertainty semantics.

### Failure and disclosure

Failures remain actionable without revealing whether an account, secret,
provider tenant, endpoint, internal hostname, address, vault, or resource
exists. Bounded Safe context may identify allowlisted categories such as
binding validation, unsupported version, capability mismatch, source
unavailable, credential absent, credential denied, credential stale,
resolution failure, address-policy denial, lease cleanup failure, or binding
currentness mismatch.

Protected or Internal evidence may contain bounded references only for an
explicitly authorized consumer. Public and trusted developer presentation
omits raw secret bytes, authorization headers, full URLs, query strings,
provider bodies, DNS payloads, private addresses, foreign exception text,
filesystem paths, environment values, and stack arguments.

### Developer experience

The future safe path remains explicit without becoming burdensome: one
immutable secret-free binding source, one later deterministic build/check
command, one exact credential reference, one explicitly supplied resolver and
credential source, one bounded operation with typed outcomes, deterministic
local fakes, loopback-only external acceptance, and actionable remediation
without secret disclosure. Applications that do not call an external service
do not adopt the layer or carry its outer dependencies.

Future values should be friendly to compile-time checks and static analysis and
use ordinary generated PHP rather than annotations, runtime reflection,
dynamic proxies, magic properties, hidden autowiring, or stringly typed bags.

Chie may explain accepted doctrine, Hantei may validate deterministic
non-secret evidence and offline conformance, and Oshie may teach the
architecture. None may retrieve a secret, resolve a hostname, grant network
authority, change a binding, invoke a provider, or repair production state.

### Package and API-status direction

Separately authorized Phase 16 PHP surfaces begin Experimental. Validators,
codecs, loaders, resolver mechanisms, lease internals, adapters, and lifecycle
helpers remain Internal by default. A Public marker or exception contract
requires an explicit compatibility decision.

Neutral lower packages do not depend on a credential-provider SDK, DNS
library, service/provider SDK, HTTP client, framework container, Agent runtime,
model runtime, command package, Chie, Hantei, or Oshie. Concrete outer adapters
may depend inward on neutral contracts and accepted Phase 15 boundaries.

Phase 16A changes no PHP version constraint and makes no PHP 8.6, PHP 9,
Stable/1.0, performance-superiority, security-certification, compliance, or
production-readiness claim.

## Fixed Phase 16 roadmap

| Row | Required boundary | Required exclusions |
| --- | --- | --- |
| **16A - Architecture and fixed roadmap** | Accepted documentation-only external-service binding, endpoint-resolution, and operation-scoped credential-lease doctrine. | No package, PHP API, schema, secret, resolver, credential source, provider adapter, command, network activity, or runtime behavior. |
| **16B - Neutral binding and credential contracts** | Planned optional Experimental immutable service-binding, endpoint, credential-reference, lease-port, resolution-port, lifecycle, result, and bounded diagnostic contracts. | No artifact compiler, secret material in values, concrete resolver/source, provider SDK, network, environment lookup, runtime orchestration, command, or application wiring. |
| **16C - Deterministic secret-free binding artifact** | Planned exact typed source, canonical versioned artifact, structural/current validation, provenance, generated ordinary PHP, and content-addressed no-clobber publication containing references only. | No secret retrieval, DNS, network, provider mapping, runtime authority, ambient discovery, command execution, or mutable registry. |
| **16D - Explicit bounded system resolver adapter** | Planned optional first-party resolver leaf producing bounded classified resolution evidence for exact Phase 15 selected-address binding. | No network authority, credential access, provider semantics, ambient private-network exception, hidden cache, connection, retry, fallback, or DNS-security certification. |
| **16E - Operation-scoped credential lease runtime** | Planned explicit caller-supplied credential-source port coordination, purpose/reference binding, finite lease lifetime, typed outcomes, operation isolation, and deterministic retirement. | No bundled secret store, default environment or vault source, provider SDK, network authority, token refresh, background renewal, reliable-zeroization claim, registry, or ambient current secret. |
| **16F - Exact-current external-service binding application** | Planned one optional exact-current profile composing secret-free binding evidence, explicit resolver, explicit credential source, current authority/policy, and the accepted Phase 15 application for one terminal operation. | No universal service client, provider default, service locator, dynamic discovery, automatic retry/fallback, background work, retained session, or deployment manager. |
| **16G - Visibility-safe inspection and verification** | Planned offline read-only exact-current binding inspection with bounded role-aware evidence and optional completed-result pairing. | No secret retrieval, DNS, networking, generated-code execution, authority evaluation, provider invocation, recording, replay, repair, live probe, or certification. |
| **16H - External acceptance and checkpoint** | Planned root-owned deterministic local external-process acceptance of the separately implemented Phase 16 boundaries and one exact local checkpoint. | No public endpoint, real credential, production DNS dependency, provider compatibility claim, push, tag, release, Stable/1.0 claim, production-readiness claim, or Phase 17 behavior. |

The roadmap order is fixed. Accepted evidence in one row does not authorize the
next row. Phase 16B requires separate authenticated authority, as does every
later row.

## Consequences

Phase 16A supplies a precise vocabulary and dependency direction for later
work while leaving the repository's runtime and package topology unchanged.
Applications retain endpoint choice, credential-source choice, provider
semantics, current authority, and cleanup ownership. Optional adoption keeps
provider SDKs, resolvers, credential sources, and network dependencies out of
applications that do not need them.

The architecture accepts the residual limits of PHP memory handling, DNS and
credential change, caller-relative evidence, and cleanup uncertainty rather
than hiding them behind a global client or secret facade.

## Rejected alternatives

- Secrets embedded in `.env`-style framework configuration, deterministic
  artifacts, generated code, or Graph metadata are rejected because they
  create durable disclosure paths and conflate configuration with secret use.
- A global credential facade or automatic environment or vault discovery is
  rejected because it creates ambient authority and hidden selection.
- Provider SDKs defining neutral credential or endpoint semantics are rejected
  because provider-specific behavior belongs in outer adapters.
- A global DNS cache or hostname-only SSRF decision is rejected because it can
  detach validation from the address actually connected.
- Treating credential possession, DNS success, TLS success, or previous
  provider success as authority is rejected because evidence grants nothing.
- Reliable zeroization claims for ordinary PHP strings are rejected because
  the runtime cannot prove physical erasure of all historical copies.
- A universal service client is rejected because it erases service/provider
  semantics and encourages lowest-common-denominator behavior.
- Implementing a package or runtime in Phase 16A is rejected because this row
  is documentation architecture only.

## Current and planned status

Phase 15A-H remain Accepted and Phase 15 is closed at commit
`b4cb57ffa01eea130a693daee0be605ae7f0dda5`. Phase 16A is Accepted
documentation architecture. Phase 16B-H are Planned and each requires separate
authenticated authority. Phase 17 is not authorized and does not begin
automatically.

## Prohibited Phase 16A scope

No Phase 16 package, PHP API, schema, credential source, secret store,
resolver, cache, service binding implementation, artifact compiler, provider
adapter, command, inspection surface, network operation, runtime orchestration,
dependency, or generated code is created. The nonexistent
`docs/architecture/api-status.md` is not created; API-status doctrine remains
in [the engineering guide](../engineering/api-status.md).

## Related material

- [External-service binding and secret leases](../architecture/external-service-binding-and-secret-leases.md)
- [Phase 16 roadmap](../architecture/phase-16-roadmap.md)
- [Outbound HTTP and governed network egress](../architecture/outbound-http-and-network-egress.md)
- [Phase 15 roadmap](../architecture/phase-15-roadmap.md)
- [Identity and Access Security](../architecture/identity-and-access-security.md)
- [API status](../engineering/api-status.md)
- [Security policy](../../SECURITY.md)
