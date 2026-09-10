# Phase 15 External Network-Egress Acceptance

## Purpose and scope

The root-owned `tests/External/NetworkEgress/run.php` harness closes Phase 15 by
exercising the accepted Phase 15B-G production APIs in fresh bounded PHP
processes. It owns test fixtures, deterministic fake collaborators, one local
listener per success child, and unique temporary state outside the repository.

Its only network exception is one IPv4 HTTP/1.1 exchange per success child. The
listener binds to `127.0.0.1` on an operating-system-selected port. The request
uses an explicit `.invalid` host while the runtime selects the loopback address,
so the real first-party cURL adapter proves direct selected-address binding
without ambient DNS, a proxy, credentials, a certificate store, a public
Internet service, or a production endpoint.

## Evidence covered

The harness constructs neutral version-1 Phase 15B values; builds, validates,
publishes, and rejects stale or tampered Phase 15C policy artifacts; runs one
bounded Phase 15D operation; performs the one real Phase 15E loopback exchange;
builds, validates, publishes, explicitly loads, and operates exact-current
Phase 15F application wiring; and performs offline Phase 15G inspection over
the exact profile/wiring and paired detached result.

All six runtime terminal outcome semantics remain explicit, including uncertain
effect and cancelled-after-possible-effect. Deterministic fake transport
evidence covers outcomes not safely inducible through the real exchange. No
outcome triggers retry, replay, repair, authorization, or an external-effect
claim.

The parent harness proves canonical output, fixed nondisclosing failure output,
bounded stdout/stderr and execution time, sequential and concurrent determinism,
later-child health, repository preservation, process closure, listener and cURL
cleanup, and complete removal of its temporary workspace. Concurrent children
own separate directories, listeners, ports, cURL handles, and runtime state.

## Evidence, authority, and disclosure

Policies, artifacts, profiles, generated wiring, currentness, receipts, results,
inspections, Graph evidence, observations, Security Audit values, role views,
and verifier results are inert caller-relative evidence. They are not
authentication, network authority, authorization, certification, compliance,
deployment truth, endpoint availability, non-repudiation, or proof that a
remote system acted.

Public process output omits origins, URIs, hostnames, ports, IP addresses,
headers, request and response bytes, bodies, CA material, credentials,
temporary paths, generated PHP, private identities, foreign messages, stack
traces, and environment values. Chie, Hantei, and Oshie are addressed evidence
roles only and are not executed. Graph, Observability, and Security Audit values
are pure projections and are neither recorded nor exported.

## Limits

The local loopback profile proves only the selected-address HTTP/1.1 execution
path. It does not certify public Internet access, ambient DNS, TLS deployment,
providers, public compatibility, production readiness, or remote effects.
Offline package tests retain responsibility for the fixed TLS option contract.
No default client, resolver implementation, provider, credential source,
proxy/cookie discovery, retry, redirect, fallback, registry, command, retained
session, worker, scheduler, watcher, deployment, or remote service is supplied.

## Checkpoint

ADR 0162 accepts one local checkpoint with parent
`05bf8367b56e694a8c388ccb96cdc305cb6e40ae` and exact subject
`Complete Kongono Phase 15 governed network egress milestone`. The resulting
commit hash is recorded in the external task report. Directives and reports are
excluded, and no push, tag, release, or Phase 16 work occurs.

Phase 15A through Phase 15H are Accepted and Phase 15 is closed. Kongono remains
Experimental and pre-1.0; Phase 16 requires separate authorization.

## Related material

- [ADR 0162](../adr/0162-phase-15-external-network-egress-acceptance-and-checkpoint.md)
- [Phase 15 roadmap](phase-15-roadmap.md)
- [Outbound HTTP and governed network egress](outbound-http-and-network-egress.md)
- [Network-egress cURL transport](network-egress-curl-transport.md)
- [Network-egress inspection and evidence](network-egress-inspection-and-evidence.md)

## Phase 16A handoff

Phase 15A-H remain Accepted and the exact checkpoint is
`b4cb57ffa01eea130a693daee0be605ae7f0dda5`. Phase 16A separately accepts
[ADR 0163](../adr/0163-explicit-external-service-binding-and-secret-lease-boundary.md),
the [external-service binding guide](external-service-binding-and-secret-leases.md),
and the [Phase 16 roadmap](phase-16-roadmap.md) as documentation only. Phase
16B-H are Planned and separately authorized.

No Phase 16 package, resolver implementation, credential provider, provider
adapter, schema, command, or runtime exists. Service binding evidence,
credential references and leases, endpoint and address selection, resolution
evidence, transport, provider semantics, and current authority remain distinct;
secret material remains application-owned and absent from deterministic
evidence and developer output.
