# ADR 0162: Phase 15 External Network-Egress Acceptance and Checkpoint

## Status

Accepted.

## Context

Phases 15B through 15G implement separate Experimental network-egress
contract, policy-artifact, runtime, cURL transport, application, and inspection
boundaries. Closing Phase 15 requires root-owned external-process evidence that
those boundaries compose without acquiring ambient network authority and one
exact local checkpoint containing only authenticated Phase 15 content.

## Decision

Phase 15H adds one deterministic root harness. It exercises production APIs in
fresh bounded PHP processes and owns a unique system-temporary workspace. Its
only network exception is one IPv4 HTTP/1.1 exchange per success child between
the first-party cURL adapter and a harness-owned listener bound to
`127.0.0.1` on an operating-system-selected port. The request uses an explicit
`.invalid` host and the runtime-selected loopback address, proving selected-
address binding without ambient DNS, a proxy, credentials, or a production
endpoint.

The harness covers neutral Phase 15B values, deterministic Phase 15C policy
artifacts, one bounded Phase 15D runtime operation, the Phase 15E cURL adapter,
exact-current Phase 15F application wiring, and offline Phase 15G inspection.
It retains all six terminal outcome semantics, including uncertain effect and
cancelled-after-possible-effect, without retry, replay, repair, or effect
claims. Deterministic fake transport evidence covers outcomes that the single
real loopback exchange cannot safely induce.

Both process streams, request and response bytes, execution time, and temporary
state are bounded. Sequential and concurrent success children produce
byte-identical public output, intentional failure is fixed and nondisclosing, a
later child remains healthy, all owned streams/processes/transport state are
closed, and temporary state is removed in `finally`.

Policies, artifacts, profiles, wiring, currentness, receipts, results,
inspections, Graph evidence, observations, audit values, role views, and
verifier results remain inert caller-relative evidence. They do not establish
authentication, authority, certification, deployment truth, endpoint
availability, compliance, non-repudiation, or remote effect. Public output
omits origins, URIs, hostnames, ports, addresses, request and response bytes,
headers, body bytes, generated PHP, temporary paths, credentials, exception
details, and environment values.

The local HTTP/1.1 proof does not certify public Internet access, ambient DNS,
TLS deployment, a provider, production readiness, public compatibility, or a
remote system's behavior. Offline package tests remain the evidence for the
fixed TLS option contract.

The Phase 15 checkpoint is one local commit with parent
`05bf8367b56e694a8c388ccb96cdc305cb6e40ae` and exact subject
`Complete Kongono Phase 15 governed network egress milestone`. The resulting
commit identity is recorded in the external completion report because a commit
cannot contain its own identity. Directives and reports are excluded from the
checkpoint.

No dependency, provider SDK, default client, credential source, resolver
implementation, proxy discovery, cookie jar, retry/fallback engine, production
command, background work, deployment, push, tag, release, or Phase 16 behavior
is added. Phase 16 remains separately authorized.

## Consequences

After committed-tree verification, Phase 15A through Phase 15H are Accepted and
Phase 15 is closed. The harness and checkpoint are test evidence, not network
authority, certification, monitoring, recording, repair, deployment truth, or
proof of external effect. Kongono remains Experimental and pre-1.0.

## Related material

- [Phase 15 external acceptance](../architecture/phase-15-external-acceptance.md)
- [Phase 15 roadmap](../architecture/phase-15-roadmap.md)
- [Outbound HTTP and governed network egress](../architecture/outbound-http-and-network-egress.md)
- [Network-egress inspection and evidence](../architecture/network-egress-inspection-and-evidence.md)
