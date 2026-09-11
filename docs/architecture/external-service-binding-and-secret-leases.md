# External-Service Binding and Secret Leases

## Current status

Phase 16A accepts [ADR 0163](../adr/0163-explicit-external-service-binding-and-secret-lease-boundary.md)
and this architecture as documentation. Phase 16B accepts
[ADR 0164](../adr/0164-neutral-external-service-binding-and-credential-lease-contracts.md)
and implements the optional Experimental `kongono/external-service-contract`
package with 27 production declarations and 15 tests. It depends only on PHP,
API Metadata, Diagnostics, Network Egress Contract, and Network Egress Runtime.
It reuses Phase 15 origin, capability, resolver, and resolution evidence.

Bindings and credential references contain no secret material. The borrowed
consumer parameter is the only secret-bearing signature; callers can copy it
and PHP memory zeroization is not promised. The package supplies ports only,
not resolver, credential-source, lease, provider, runtime, command, or network
implementations. Phase 16C accepts [ADR 0165](../adr/0165-deterministic-external-service-binding-artifact-and-generated-php.md)
and implements the optional Experimental `kongono/external-service-binding-build`
package for canonical secret-free artifact and deterministic ordinary-PHP
evidence from one explicit typed source. Generated PHP is never loaded or
executed. Credential and network permissions remain independent and absent.
Phase 16D accepts [ADR 0166](../adr/0166-explicit-bounded-system-resolver-adapter.md)
and the optional Experimental `kongono/external-service-resolver-system` leaf.
One explicit profile bounds one `ext-sockets` lookup; `SOL_TCP` and omission of
`ai_family` express the portable TCP and fixed unspecified-family PHP-binding
policy. Every result is validated as a whole, classified by Phase 15, ordered
deterministically, and returned with one exact selected member. No connection,
network grant, credential access, cache, retry, fallback, provider behavior,
or DNS TTL/DNSSEC/provenance/deadline claim is added. Phase 16E accepts [ADR
0167](../adr/0167-operation-scoped-credential-lease-runtime.md) and the
optional Experimental operation-scoped credential runtime. Applications
explicitly select or implement a source, create a secret-free request, supply a
consumer and monotonic clock, run one lease operation, and handle typed
terminal evidence. Applications without external credentials do not install
it. Phase 16F accepts [ADR
0168](../adr/0168-exact-current-external-service-binding-application.md) and the
optional Experimental one-use application coordinator. It validates one exact-
current selected binding and Phase 15 profile, then composes explicit credential,
resolver, transport, authority, cancellation, clock, and request-factory
collaborators for one terminal operation. The factory owns provider semantics
and may copy borrowed material; PHP provides no reliable zeroization. Phase
16G accepts [ADR
0169](../adr/0169-visibility-safe-external-service-inspection-and-bounded-evidence.md)
and the optional Experimental inspection leaf. It revalidates one supplied
exact-current Phase 16F profile and may summarize one matching detached result
without credential access, DNS, network behavior, generated-code loading,
recording, replay, repair, or certification. Phase 16H accepts [ADR
0170](../adr/0170-phase-16-external-service-acceptance-and-checkpoint.md), adds
the root-owned local external-service acceptance harness, and closes Phase 16
at one exact local checkpoint. Phase 17B-F add one optional mapping, adapter,
and exact-current application leaf over this accepted application boundary.
Current topology is 89 packages / 375 local production edges / acyclic; PHP
inventories are 1,726 / 894 / 88.

## Accepted system resolver adapter

Install the optional `kongono/external-service-resolver-system` package only in
applications that deliberately choose the operating-system resolver. The PHP
runtime must load `ext-sockets` and expose `socket_addrinfo_lookup()`,
`socket_addrinfo_explain()`, `AF_INET`, `AF_INET6`, `SOCK_STREAM`, positive
integer `SOL_TCP`, `AI_NUMERICHOST`, and `AI_NUMERICSERV`. Construction fails
closed with a bounded local exception when this fixed capability set is not
available; the package never changes PHP or operating-system configuration.

The application supplies an immutable `SystemResolverProfile` with either
IPv4-first or IPv6-first ordering and an explicit candidate limit from 1
through 64. Each resolution performs one system lookup for the exact canonical
host and decimal port. Its hint map contains only `SOCK_STREAM`, `SOL_TCP`, and
`AI_NUMERICSERV`, plus `AI_NUMERICHOST` for a numeric literal; the binding-fixed
unspecified-family policy is expressed by omitting `ai_family`.

Before explaining any entry, the adapter rejects a native result larger than
the configured limit. It then validates the complete result, rejects the whole
result if any entry is malformed, constructs Phase 15 `ResolvedAddress` values,
collapses exact duplicates, partitions by the selected family preference,
sorts each family by binary address bytes, and selects the first member. It
does not filter public, private, loopback, link-local, multicast, unspecified,
documentation, or reserved categories. The complete ordered set and selected
member pass to current Phase 15 policy, which retains responsibility for
permission and binding a permitted selected address to transport.

| Condition | Result |
| --- | --- |
| Native lookup returns `false` | `system_resolver_lookup_failed` |
| Any entry is malformed or incompatible | `system_resolver_result_invalid` |
| Native count exceeds the explicit limit | `system_resolver_candidate_limit` |
| Native result is empty | `system_resolver_no_address` |
| Required sockets capability is absent | Construction exception with bounded diagnostic evidence |
| Profile limit is outside 1 through 64 | Construction exception with bounded diagnostic evidence |

The resolver retains only its immutable profile. There is no cache,
memoization, retry, fallback, background refresh, retained answer, or shared
mutable current result, so sequential calls are fresh and may legitimately
differ. A system `getaddrinfo()` call can still block according to host policy:
PHP supplies no portable per-call deadline, cancellation, TTL, DNSSEC, resolver
provenance, or freshness guarantee through this boundary.

Tests should inject the package's Internal lookup seam for hostname cases and
changed-answer sequences. A native test may use only numeric loopback literals
with `AI_NUMERICHOST`; it must not depend on public DNS. Choose a separately
authorized resolver adapter instead when the application requires a portable
deadline, cancellation, explicit DNS source or provenance, DNSSEC evidence,
TTL-aware caching, asynchronous operation, or behavior independent of the
operating-system resolver. Those needs do not enlarge this adapter's contract.

## Use this layer when

Use the optional layer when an application needs to call an external service
and must keep endpoint constraints, secret references, hostname resolution,
network authority, credential acquisition, and provider semantics explicit and
separately testable.

The application remains responsible for current service intent, current
authorization, exact binding selection, credential-source selection,
provider-specific behavior, and the accepted Phase 15 policy inputs.

## Do not use it when

Do not adopt this layer for an application that performs no external-service
operation. Do not use it as a universal remote-service API, a provider SDK
replacement, a credential store, an ambient service locator, a configuration
reader, or a reason to bypass the existing Phase 15 authority and policy gates.

## Concepts that must remain separate

| Concept | Meaning | Does not establish |
| --- | --- | --- |
| Service binding evidence | Immutable, versioned, deterministic, secret-free application evidence naming endpoint constraints, a credential reference, capabilities, and provenance. | Secret existence, endpoint availability, authentication, authorization, or network authority. |
| Credential reference | Bounded opaque application-owned reference naming what one explicit source may be asked to lease. | Secret material, permission to retrieve it, or permission to use the network. |
| Credential lease | Operation-scoped sensitive value acquired for one exact reference and purpose and retired deterministically. | Network authority, provider success, or proof that historical memory was erased. |
| Endpoint selection | Application choice of normalized scheme, host, port, optional base path, and capabilities. | DNS truth, an allowed address, or a connection. |
| Resolution evidence | Bounded complete evidence from one explicit resolution attempt. | A policy exception, connection, endpoint availability, or provider truth. |
| Selected address | One deterministically selected candidate permitted by the current Phase 15 policy. | Authority to connect to a different address. |
| Transport | The mechanism that attempts the exact selected-address connection under Phase 15 bounds. | Service-specific request meaning or provider compatibility. |
| Service/provider adapter | Outer code that owns service paths, authentication application, request/response mapping, versions, capabilities, and rate semantics. | Ambient current authority or neutral lower-layer semantics. |
| Current network authority | Explicit current application-owned authority plus the accepted Phase 15 policy gates for one attempt. | Credential access or provider authentication. |

Credential access and network access are independent permissions. Neither a
retrieved lease nor an allowed address implies the other permission.

## Conceptual flow and ownership

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

Arrows mean dependency direction after validation. They do not discover a
secret, select a provider, grant authority, or prove an effect. One future
operation owns its accepted resources and transfers or retires that ownership
at explicit lifecycle boundaries.

## Secret-free source and artifact examples

The following examples are conceptual placeholders, not an accepted file
syntax or schema:

```text
service profile: <application-service-profile>
endpoint scheme: <approved-scheme>
endpoint host: <application-service-host>
endpoint port: <approved-port>
base path: <provider-owned-base-path>
credential source: <application-source-label>
credential reference: <opaque-application-reference>
required capabilities: <bounded-capability-set>
provenance: <exact-application-source-state>
```

A future deterministic artifact may reproduce only normalized versions of
those non-secret fields plus structural and provenance evidence:

```text
artifact version: <version>
binding identity: <binding-identity>
endpoint constraints: <normalized-endpoint-constraints>
credential reference: <opaque-application-reference>
required capabilities: <bounded-capability-set>
source identity: <exact-source-identity>
```

The reference is not the secret. It must reveal no secret bytes, length, hash,
prefix, suffix, derived identifier, account existence, or provider error body.
Artifact validity does not prove that a secret exists or will work.

## Endpoint, resolution, address, connection, and service semantics

Endpoint selection describes constraints. Resolution asks one explicit
resolver to return a bounded complete answer set for the exact canonical
origin. Phase 15 policy classifies every address candidate. An explicit profile
selects one permitted address deterministically. The transport must connect to
that exact address while preserving the validated origin semantics. Only an
outer service/provider adapter applies service-specific request meaning.

Binding a selected address to transport prevents validation of one address
from silently becoming a connection to another. DNS answers, TTLs, cached
answers, certificates, and previous connections remain evidence rather than
authority. No ambient local or private-network exception exists.

## Credential acquisition, use, and retirement

A future operation asks one explicitly supplied credential-source port for one
exact reference and purpose. It acquires the lease as late as practical, keeps
its lifetime and copies bounded, exposes it only to the explicitly authorized
consumer, and retires it in reverse-order cleanup.

The lease is non-serializable by contract, non-stringifiable, non-cloneable
where PHP permits, and excluded from generic debugging, inspection, logging,
Graph, audit, receipt, fixture, and generated-code surfaces.

PHP cannot promise reliable memory zeroization for ordinary strings. Copies,
allocator behavior, extensions, operating-system memory, crash dumps, and
process inspection can retain bytes after references are released. Retirement
minimizes lifetime and asks the source to destroy or revoke owned state; it is
not proof of physical erasure of all historical bytes.

## Change, rotation, and currentness

Credential rotation and DNS change are normal. Binding evidence identifies an
exact reference and endpoint constraints, not mutable secret bytes or the
continuing truth of a DNS answer. Each operation obtains fresh caller-relative
currentness evidence where required.

An expired lease, changed reference, changed endpoint, stale artifact,
unsupported capability, changed policy, changed resolution, or uncertain
cleanup fails visibly. It does not trigger automatic retry, fallback, refresh,
re-resolution, reacquisition, endpoint failover, or best-effort reuse.

There is no resolver or credential cache by default. A later cache needs
explicit complete keys, finite bounds, expiry, invalidation, isolation,
currentness, retirement, and failure semantics.

## Persistent runtimes and cleanup

Each operation has one owner and deterministic states. Resources are acquired
in order and released in reverse order. Cleanup attempts every required step
while preserving the primary failure and ordered cleanup failures separately.

Sequential, overlapping, cancelled, failed, and cleanup-failed operations in
one process remain isolated. After retirement no operation retains secret
material, resolver answers, provider state, response state, or an ambient
current binding. Phase 16A selects no pool, retained session, background
renewal, watcher, scheduler, or daemon.

## Failure and disclosure

Developer-facing failures identify bounded allowlisted categories, not secret
or resource existence. Useful Safe categories include binding validation,
unsupported version, capability mismatch, source unavailable, credential
absent, credential denied, credential stale, resolution failure,
address-policy denial, lease cleanup failure, and binding currentness mismatch.

Public and trusted developer output omits secret material, full URLs, query
strings, provider bodies, DNS payloads, private addresses, environment values,
filesystem paths, foreign exception text, and stack arguments. Protected or
Internal evidence may carry only bounded references for an explicitly
authorized consumer.

## Deterministic testing

Later phases should test neutral behavior with deterministic fake credential
sources and resolvers. Tests cover complete-answer validation, IPv4/IPv6 and
address-class distinctions, selection binding, currentness, rotation, expiry,
cancellation, sequential and overlapping isolation, reverse cleanup, primary
and cleanup failure preservation, and nondisclosure.

External acceptance remains local only: deterministic fresh processes,
placeholder identities, fake secrets, and loopback transport where a real
socket is required. It uses no public endpoint, production DNS dependency, or
real credential and makes no provider-compatibility claim.

## Optional adoption and provider independence

Applications opt in only when they need external-service access. Neutral lower
packages, if separately authorized, do not depend on a credential-provider
SDK, DNS library, provider SDK, HTTP client, framework container, Agent or model
runtime, command package, or developer-intelligence role. Concrete outer
adapters may depend inward while retaining provider-specific semantics.

## API-status direction

Separately authorized Phase 16 PHP surfaces begin Experimental. Validators,
codecs, loaders, resolver mechanisms, lease internals, adapters, and lifecycle
helpers remain Internal by default. Public compatibility requires a separate
deliberate decision under the [API-status doctrine](../engineering/api-status.md).

## Accepted deterministic build evidence

Phase 16C accepts [ADR 0165](../adr/0165-deterministic-external-service-binding-artifact-and-generated-php.md)
and the optional Experimental `kongono/external-service-binding-build` package.
One explicit immutable secret-free typed source produces a canonical version-1
JSON artifact and deterministic ordinary PHP. Structural validation rebuilds
typed values and exact bytes; current validation compares only with one
explicit current source supplied by the caller.

The JSON and PHP publishers are independent, path-safe, content-addressed,
atomic, idempotent for identical stable valid bytes, and no-clobber. Generated
PHP is validated by reconstruction and exact re-emission and is never loaded or
executed in Phase 16C. Source, artifact, generated PHP, credential references,
provenance, and digests are evidence only. Credential access and network
authority remain independent and absent. PHP strings provide no reliable
secret-memory zeroization claim, although no secret enters the package.

## Fixed Phase 16 roadmap

| Row | Required boundary | Required exclusions |
| --- | --- | --- |
| **16A - Architecture and fixed roadmap** | Accepted documentation-only external-service binding, endpoint-resolution, and operation-scoped credential-lease doctrine. | No package, PHP API, schema, secret, resolver, credential source, provider adapter, command, network activity, or runtime behavior. |
| **16B - Neutral binding and credential contracts** | Accepted optional Experimental immutable service-binding, endpoint, credential-reference, lease-port, resolution-port, lifecycle, result, and bounded diagnostic contracts. | No artifact compiler, secret material in values, concrete resolver/source, provider SDK, network, environment lookup, runtime orchestration, command, or application wiring. |
| **16C - Deterministic secret-free binding artifact** | Accepted optional Experimental exact typed source, canonical versioned artifact, structural or explicit-source-current validation, provenance, deterministic ordinary PHP, and independent path-safe content-addressed atomic no-clobber publication containing references only. Generated PHP remains inert and unloaded. | No secret retrieval, credential runtime, resolver adapter, DNS, network, provider mapping, application workflow, runtime authority, ambient discovery, command execution, generated-code loading, or mutable registry. |
| **16D - Explicit bounded system resolver adapter** | Accepted optional first-party resolver leaf producing bounded classified resolution evidence for exact Phase 15 selected-address binding. | No network authority, credential access, provider semantics, ambient private-network exception, hidden cache, connection, retry, fallback, or DNS-security certification. |
| **16E - Operation-scoped credential lease runtime** | Accepted optional Experimental caller-supplied source coordination, exact purpose/reference/state validation, cooperative finite lifetime checks, typed terminal outcomes, operation isolation, borrowed-material delegation, and exactly-once retirement for each acquired lease. | No bundled secret store, default environment or vault source, provider SDK, network authority, token refresh, background renewal, reliable-zeroization or preemptive-deadline claim, registry, or ambient current secret. |
| **16F - Exact-current external-service binding application** | Accepted optional Experimental exact-current binding/profile validation and one-use composition of explicit credential, resolver, transport, authority, cancellation, clock, and request-factory collaborators for one terminal operation with Phase 16E retirement. | No universal service client, provider default, credential source, resolver or transport implementation, service locator, dynamic discovery, automatic retry/fallback, background work, retained session, command, inspection, or deployment manager. |
| **16G - Visibility-safe inspection and verification** | Accepted optional Experimental offline read-only exact-current binding inspection with bounded role-aware evidence and optional detached completed-result pairing. | No secret retrieval, DNS, networking, generated-code execution, authority evaluation, provider invocation, recording, replay, repair, live probe, or certification. |
| **16H - External acceptance and checkpoint** | Accepted root-owned deterministic local external-process acceptance of the separately implemented Phase 16 boundaries and one exact local checkpoint. | No public endpoint, real credential, production DNS dependency, provider compatibility claim, push, tag, release, Stable/1.0 claim, production-readiness claim, or Phase 17 behavior. |

Phase 16A through Phase 16H are Accepted, and Phase 16 is closed. Phase 17A-G
are Accepted; Phase 17H requires separate authenticated authority and alone may
checkpoint Phase 17.

## Related material

- [ADR 0163](../adr/0163-explicit-external-service-binding-and-secret-lease-boundary.md)
- [ADR 0164](../adr/0164-neutral-external-service-binding-and-credential-lease-contracts.md)
- [ADR 0165](../adr/0165-deterministic-external-service-binding-artifact-and-generated-php.md)
- [ADR 0166](../adr/0166-explicit-bounded-system-resolver-adapter.md)
- [ADR 0167](../adr/0167-operation-scoped-credential-lease-runtime.md)
- [ADR 0168](../adr/0168-exact-current-external-service-binding-application.md)
- [ADR 0169](../adr/0169-visibility-safe-external-service-inspection-and-bounded-evidence.md)
- [ADR 0170](../adr/0170-phase-16-external-service-acceptance-and-checkpoint.md)
- [Phase 16 external acceptance](phase-16-external-acceptance.md)
- [External-service inspection and evidence](external-service-inspection-and-evidence.md)
- [Phase 16 roadmap](phase-16-roadmap.md)
- [Outbound HTTP and governed network egress](outbound-http-and-network-egress.md)
- [Network-egress runtime](network-egress-runtime.md)
- [Network-egress application workflow](network-egress-application-workflow.md)
- [Security policy](../../SECURITY.md)
- [Identity and Access Security](identity-and-access-security.md)
- [Provider-neutral model invocation](provider-neutral-model-invocation.md)
- [Agent Principal platform](agent-principal-platform.md)
- [Error handling](../engineering/error-handling.md)
- [Testing](../engineering/testing.md)

## Relationship to Phase 17 provider adapters

Phase 17A accepts [ADR 0171](../adr/0171-first-party-openai-responses-adapter-boundary.md)
and the [OpenAI Responses adapter guide](openai-responses-provider-adapter.md)
as the fixed architecture. Phase 17C accepts [ADR
0173](../adr/0173-one-shot-openai-responses-invocation-adapter.md): its optional
Responses adapter consumes one exact-current binding and borrows material
through one operation-scoped lease while Phase 15 independently governs the
single network attempt. Provider/model selection,
binding evidence, credential access, network authority, provider output,
action suggestions, and application effects remain separate.

Phase 17B-E supply deterministic mapping and adapter behavior. Phase 17F
accepts [ADR 0176](../adr/0176-exact-current-openai-responses-application-workflow.md)
and binds one exact-current service application profile into an explicit
provider-specific build/check/run path without moving credential or network
authority into the provider leaf. No default provider, ambient endpoint or
credential, SDK, retry, live provider proof, or compatibility claim exists.
Phase 17H remains Planned under the [fixed Phase 17 roadmap](phase-17-roadmap.md).
