# ADR 0170: Phase 16 External-Service Acceptance and Checkpoint

- Status: Accepted
- Date: 2026-09-09
- Phase: 16H

## Context

Phases 16B through 16G separately established the Experimental neutral
external-service contract, deterministic secret-free binding artifacts,
numeric-capable system resolver, operation-scoped credential lease runtime,
exact-current application composition, and visibility-safe inspection. Those
boundaries needed one root-owned external-process proof that they compose
without turning local evidence into credential authority, network authority,
provider compatibility, or deployment truth.

The accepted Phase 15 platform already owns governed network egress and the
real first-party HTTP/1.1 cURL adapter. Phase 16H therefore adds acceptance
evidence above those production APIs, not another client, adapter, resolver,
credential source, policy, command, or runtime.

## Decision

Accept a root-test-only harness at
`tests/External/ExternalService/run.php` and the explicit Composer entry point
`composer acceptance:phase16`. The command is local external acceptance and is
not one of the eight canonical `composer quality` or Hantei checks.

Each success child owns fresh state and performs exactly one bounded IPv4
loopback HTTP/1.1 exchange. A listener binds only `127.0.0.1` on an
operating-system-assigned ephemeral port. The service binding and canonical
origin use an RFC-reserved `.invalid` hostname. A test-owned resolver supplies
the selected address `127.0.0.1` explicitly, so the real
`CurlEgressTransport` reaches the listener without ambient hostname lookup.
The policy opts into `AddressScope::ExplicitPrivateAllowed`,
`TransportSecurity::ExplicitHttpAllowed`, operation-scoped credentials, and
HTTP/1.1. This loopback exception does not authorize another destination or a
production plain-HTTP posture.

The harness separately invokes `SystemEndpointResolver` once for numeric
literal `127.0.0.1` under an explicit bounded IPv4-first profile. That proof
requires `ext-sockets`, performs no hostname lookup, opens no connection, and
does not supply the result to the `.invalid` application exchange.

One root-test-owned inert credential source and lease prove the exact
acquire/use/retire lifecycle. Material is borrowed once inside the synchronous
request-factory callback and is not retained or disclosed. Every acquired
lease receives exactly one retirement attempt. Successful evidence is
`Succeeded`, `Retired`, one acquisition, one material-use attempt, one
retirement attempt, and cleanup complete. Failure evidence remains typed,
terminal, isolated, and nondisclosing. Successful retirement is evidence, not
a claim of PHP string zeroization or physical memory erasure.

The acceptance child also proves the Phase 16B-G boundaries through their
production APIs:

1. immutable contract identities, endpoint, credential, resolver, lifecycle,
   outcome, failure, and diagnostic relations;
2. deterministic binding artifacts and inert generated PHP with structural and
   currentness validation plus independent atomic content-addressed no-clobber
   publication;
3. numeric-only native resolver evidence and deterministic selected membership;
4. bounded credential acquisition, borrowed use, failure, retirement, and
   sequential isolation;
5. one exact-current Phase 16F application operation over the accepted Phase
   15 runtime and real cURL adapter;
6. offline Phase 16G inspection, Chie/Hantei/Oshie evidence views, independent
   verification, relationship-not-established Graph evidence, and pure
   Observation/Security Audit value construction; and
7. fail-closed stale, tampered, mismatched, unsupported-capability, budget,
   lifecycle, uncertain-effect, and disclosure-adversarial cases.

Generated Phase 16C PHP is validated and published only as inert bytes. It is
never loaded or executed. The Phase 15F application continues to load only its
own exact contained accepted wiring.

The parent proves repeated sequential success, two concurrent success
children, generic failure, later-child health, bounded stdout/stderr, exact
child exit behavior, repository fingerprint preservation, and complete
temporary cleanup. Public success is one canonical LF-terminated JSON line;
public failure is only `phase16_acceptance_failed` followed by LF. Public
streams omit dynamic ports, addresses, temporary paths, request/response data,
headers, bodies, credential values and references, identities, exception
messages, stack traces, environment values, and foreign errors.

## Checkpoint

Phase 16A through Phase 16H are Accepted. Phase 16 is closed by one ordinary
non-amending local commit with subject
`Complete Kongono Phase 16 external service milestone` and parent
`b4cb57ffa01eea130a693daee0be605ae7f0dda5`. The resulting commit and tree
identities are completion evidence recorded in the Phase 16H task report, not
architectural input.

The closed topology remains 87 production packages, 355 local production
edges, and acyclic. PHP inventories are 1,647 production, 851 package-test,
and 88 root-test files.

## Security and evidence limits

The harness uses no real credential, bundled production credential source,
provider adapter or SDK, ambient production DNS, public endpoint, proxy,
certificate service, account, or non-loopback network interface. No artifact,
profile, resolver result, lease, receipt, inspection, Graph value,
Observation, audit value, or successful local exchange grants credential or
network authority.

Local conformance does not certify public DNS, TLS deployment, credentials,
providers, remote effects, compatibility, compliance, deployment, production
readiness, Stable/1.0 status, or release readiness. Possible effects remain
uncertain where the accepted outcome says they are uncertain.

## Consequences

Phase 16 has a deterministic, explicit, locally repeatable acceptance command
and an exact local checkpoint. Canonical quality remains independent from the
loopback proof. Consumers still supply their own credential sources, provider
semantics, authority evidence, resolver policy, transport collaborators, and
deployment decisions.

Phase 17 is not begun or implied. It requires separate authenticated architect
authority.

## Related material

- [Phase 16 external acceptance](../architecture/phase-16-external-acceptance.md)
- [External-service bindings and secret leases](../architecture/external-service-binding-and-secret-leases.md)
- [External-service inspection and evidence](../architecture/external-service-inspection-and-evidence.md)
- [Outbound HTTP and governed network egress](../architecture/outbound-http-and-network-egress.md)
- [Phase 16 roadmap](../architecture/phase-16-roadmap.md)
- [ADR 0163](0163-explicit-external-service-binding-and-secret-lease-boundary.md)
- [ADR 0164](0164-neutral-external-service-binding-and-credential-lease-contracts.md)
- [ADR 0165](0165-deterministic-external-service-binding-artifact-and-generated-php.md)
- [ADR 0166](0166-explicit-bounded-system-resolver-adapter.md)
- [ADR 0167](0167-operation-scoped-credential-lease-runtime.md)
- [ADR 0168](0168-exact-current-external-service-binding-application.md)
- [ADR 0169](0169-visibility-safe-external-service-inspection-and-bounded-evidence.md)
