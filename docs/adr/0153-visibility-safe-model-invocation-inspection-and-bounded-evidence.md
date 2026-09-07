# ADR 0153: Visibility-Safe Model-Invocation Inspection and Bounded Evidence

- Status: Accepted
- Date: 2026-09-01
- Stability: Experimental implementation boundary

## Context

Phases 14B through 14F provide neutral invocation values, deterministic plan
evidence, one bounded provider-neutral runtime, an independent inert Agent
proposal bridge, and an exact-current single-profile application workflow.
Those boundaries intentionally do not provide a visibility-safe way to inspect
one supplied application profile and wiring pair or summarize optional already
completed runtime evidence for Graph, Observability, Security Audit, or
developer-intelligence consumers.

Inspection must not become a second runtime, a provider probe, a generated-code
loader, monitoring, recording, authority, certification, or a disclosure path
for raw model/application/provider content.

## Decision

Implement the optional Experimental `kongono/model-invocation-inspection`
package as an upper evidence leaf. Its immutable request accepts exactly one
`ModelInvocationApplicationProfile`, one `GeneratedModelInvocationWiring`, and
optional detached `InvocationRuntimeResult` completed before inspection.

The inspector delegates structural and exact-current proof of the supplied
canonical wiring bytes to the accepted Phase 14F validator, then independently
compares the public profile, source, artifact, request, model-profile, provider,
model, adapter, generated implementation, namespace, class, and wiring
bindings. It never loads generated PHP, starts an application, constructs a
runtime, invokes an adapter/provider/bridge/Agent port, reads a Graph, records a
signal, or executes a developer role.

Optional runtime evidence is accepted only when its public shape is retired,
cleanup-complete, terminal, attributable to the exact invocation and profile,
and internally receipt/result consistent. Event relationships are checked
without replay. The resulting canonical version-1 inspection exposes only
fixed version/limitation tokens, Protected digests required for exact
comparison, aggregate message/capability/usage-limit/event/usage-entry counts,
closed terminal tokens, and nonnegative lower-layer counts. Canonical JSON is
UTF-8 without BOM, LF-framed, and ends with one LF; the lowercase SHA-256
identity excludes only its own identity field.

Raw messages, prompts, responses, chunks, actions and arguments,
provider/model/adapter labels, arbitrary profile/plan/invocation/receipt/result
identities, metrics, currencies, credentials, secrets, paths, URLs, headers,
query strings, generated PHP, foreign messages, traces, and hidden reasoning
are excluded.

## Cross-system evidence

One pure Graph projector accepts an explicit matching-role validated Phase 11G
Safe aggregate view. It copies only contract-permitted Graph/artifact
identities, fixed vocabulary counts, and limitations. Absence is explicit and
the model-to-Graph relationship remains `not-established`.

Pure projectors construct one neutral
`kongono.model-invocation.inspection` operational event and one Safe
`kongono.model-invocation.inspection.security` audit evidence value. They call
no recorder, exporter, clock, sampler, buffer, flusher, store, backend, network,
or retry mechanism. Construction proves no delivery, durability,
tamper-evidence, compliance, application security, provider success, authority,
or external effect.

One closed role enum maps `chie`, `hantei`, and `oshie` to `explain`, `verify`,
and `teach`. Derived views state that role execution, repair, and provider
invocation were not performed, hidden-data access is none, and authority is
evidence-only. An independent verifier compares fixed ordered evidence groups
without calling the deriver, normalizing or repairing the candidate, executing
a role, or self-certifying.

## Failure, lifecycle, and security

Diagnostics `KGN-MODEL-INSPECTION-1437` through `1441` are fixed,
non-disclosing categories for invalid inspection, binding mismatch, completed
runtime mismatch, intelligence/verifier evidence, and projection failure.
Diagnostic context contains one closed Safe component token. Translated
failures preserve prior throwable identity without copying its message or
claiming native trace sanitization.

The package retains no input or output across calls and performs no filesystem,
environment, clock, randomness, network, process, database, provider,
container, registry, Reflection discovery, generated-code loading, global
lookup, monitoring, debugging, repair, mutation, command, UI, session, store,
worker, scheduler, watcher, autonomous loop, or background work.

Exact supplied evidence is not authentication, authorization, hostile-host
integrity, non-repudiation, live currentness, provider availability, billing
truth, deployment state, or proof of effect. Trusted generated and in-process
PHP is not sandboxed, although generated PHP is never loaded by inspection.

## Consequences

Applications and developer tools gain deterministic bounded evidence while all
provider, credential, runtime, application, authority, recording, Graph, and
role execution responsibilities remain outside the package. The production
graph becomes 75 packages and 289 local edges, acyclic. PHP inventories become
1,358 production, 686 package-test, and 76 root-test files.

Phase 14H remains planned and requires separately authenticated authority.

## Related material

- [Model Invocation Inspection and Evidence](../architecture/model-invocation-inspection-and-evidence.md)
- [Provider-Neutral Model Invocation](../architecture/provider-neutral-model-invocation.md)
- [Phase 14 Roadmap](../architecture/phase-14-roadmap.md)
- [ADR 0145](0145-visibility-safe-agent-principal-inspection-and-bounded-evidence.md)
- [ADR 0152](0152-exact-current-single-profile-model-invocation-application-workflow.md)
