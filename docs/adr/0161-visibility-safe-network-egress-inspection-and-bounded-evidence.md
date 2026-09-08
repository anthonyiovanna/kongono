# ADR 0161: Visibility-Safe Network-Egress Inspection and Bounded Evidence

- Status: Accepted
- Date: 2026-09-08
- Stability: Experimental implementation boundary

## Context

Phases 15B through 15F provide neutral network-egress values, deterministic
policy evidence, one bounded provider-neutral runtime, an explicit cURL
transport profile, and an exact-current single-profile application workflow.
Those boundaries intentionally do not provide a visibility-safe way to inspect
one supplied application profile and generated-wiring value or to summarize
optional, already completed runtime evidence.

Inspection must not become a second runtime, a live network probe, a
generated-code loader, monitoring, recording, authority, certification, or a
disclosure path for raw network, request, response, credential, or application
content.

## Decision

Implement the optional Experimental `kongono/network-egress-inspection`
package as an upper evidence leaf. Its immutable request accepts exactly one
`NetworkEgressApplicationProfile`, one `GeneratedNetworkEgressWiring`, and
an optional pair containing the exact corresponding `EgressOperation` and
detached `EgressRuntimeResult`. The optional values are both present or both
absent.

The inspector calls the accepted Phase 15F validator over the supplied
wiring's canonical bytes. It then independently compares the public profile,
policy, source, artifact, required-capability, generator implementation,
namespace, class, canonical-byte, and wiring-digest bindings. It never loads
generated PHP or calls the generated boundary.

Optional runtime evidence is accepted only when the operation policy
reference, exact allowed origin, finite budget, receipt operation identity,
terminal outcome, lifecycle, attempt relationship, transfer, cancellation,
protocol, capability evidence, presence flags, and bounded counters are
coherent under the accepted Phase 15B and 15D contracts. The result is detached
and cleanup-complete. All six outcomes remain exact evidence, including
`CancelledAfterPossibleEffect` and `UncertainEffect`; inspection authorizes
neither replay nor a claim that an external effect is known.

The immutable version-1 inspection uses schema
`kongono.network-egress-inspection`. Its canonical JSON is UTF-8 without BOM,
LF-framed, fixed-order, and terminated by one LF. Its lowercase SHA-256
identity covers the exact payload while excluding only the identity field.
Output is limited to Protected lowercase SHA-256 bindings, Safe bounded
counts, closed policy and optional terminal runtime tokens, boolean presence
and cleanup flags, and fixed limitation tokens.

Raw identifiers, origins, hosts, ports, URIs, addresses, requests, methods,
headers, bodies, credentials, tokens, cookies, CA material, response or failure
objects, status or reason text, foreign messages, paths, generated PHP, and
caller strings are omitted.

## Cross-system evidence

A pure Graph projector accepts only an explicit optional matching-role
validated Phase 11G aggregate view. It copies contract-permitted
Graph/artifact identities, fixed vocabulary counts, limitations, and Safe
aggregates. Absence is explicit and the network-egress-to-Graph relationship
remains `not-established`.

Pure projectors construct one neutral
`kongono.network-egress.inspection` operational event and one
`kongono.network-egress.inspection.security` Safe audit evidence value. They
do not record, export, sample, buffer, flush, store, time, retry, or deliver
anything. Construction proves no delivery, durability, tamper evidence,
compliance, authorization, endpoint state, or external effect.

A closed role enum maps `chie`, `hantei`, and `oshie` to `explain`,
`verify`, and `teach`. Derived views state that role execution, network
access, authority evaluation, repair, replay, and certification were not
performed; hidden-data access is none and authority is evidence-only. An
independent verifier reconstructs and compares the fixed evidence shape
without calling the deriver, repairing the candidate, executing a role, or
self-certifying.

## Failure, lifecycle, and security

Diagnostics `KGN-NET-1549` through `KGN-NET-1553` are fixed,
non-disclosing categories for invalid inspection, binding mismatch, optional
runtime mismatch, intelligence/verifier evidence, and projection failure.
Diagnostic context contains one closed Safe component token. Translated
failures preserve prior throwable identity without copying foreign messages or
claiming native trace argument erasure.

The package is stateless and retains no input or output across calls. It
performs no filesystem, environment, clock, randomness, process, DNS, socket,
TLS, HTTP, resolver, transport, authority, credential, application, runtime,
Graph-read, recording, role-execution, repair, or background operation.

Exact supplied evidence is not authentication, authorization, live
currentness, hostile-host integrity, non-repudiation, provider or account
state, deployment state, compatibility certification, or proof of effect.
Trusted generated and in-process PHP is not sandboxed, although inspection
never loads generated PHP.

## Consequences

Applications and developer tools gain deterministic bounded evidence while
all resolver, transport, credential, runtime, application, authority,
recording, Graph, and role execution responsibilities remain outside the
package. The production graph becomes 81 packages and 317 local edges,
acyclic. PHP inventories become 1,514 production, 768 package-test, and 80
root-test files.

Phase 15H remains Planned and requires separately authenticated authority.
There is no first-party resolver or credential provider, provider adapter,
live inspection probe, public compatibility certification, Phase 15
checkpoint, Stable/1.0 claim, or production-readiness claim.

## Related material

- [Network-Egress Inspection and Bounded Evidence](../architecture/network-egress-inspection-and-evidence.md)
- [Network-Egress Application Workflow](../architecture/network-egress-application-workflow.md)
- [Network-Egress Runtime](../architecture/network-egress-runtime.md)
- [Phase 15 Roadmap](../architecture/phase-15-roadmap.md)
- [ADR 0160](0160-exact-current-single-profile-network-egress-application-workflow.md)
