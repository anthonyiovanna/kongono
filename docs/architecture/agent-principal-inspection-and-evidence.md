# Agent Principal Inspection and Bounded Evidence

## Purpose and context

Phase 13G adds the optional Experimental `kongono/agent-inspection` package as
an upper evidence leaf. Use it when an application already holds one accepted
Phase 13F profile and the exact generated wiring and needs deterministic,
offline, read-only, presentation-neutral Safe evidence. Do not use it to run or
debug an agent, establish current external authority, query the Graph, record
telemetry, certify a role, or decide whether an application is secure.

The package is immutable and stateless. Repeated, nested, and interleaved
operations over equal inputs produce equal values and retain no cross-operation
state.

## Calling contract

`AgentPrincipalInspectionRequest` accepts exactly:

- one explicit `AgentApplicationProfile`;
- one explicit `GeneratedAgentWiring`; and
- optionally one completed `AgentRuntimeResult`.

These are fresh typed references. The request performs no I/O. It cannot accept
a path, directory, stream, callable, application/runtime handle, proposal or
capability port, currentness source, recorder, provider, registry, Graph or
workspace reader, credential, or ambient environment value.

`AgentPrincipalInspector::inspect()` re-runs the Phase 13F structural and
caller-relative exact-current wiring validator against the explicit profile.
It also proves declaration/artifact/plan/wiring identities and digests,
admission identity, complete ordered permitted capabilities, constraints, and
evidence references. Generated PHP is not loaded or executed. The inspector
never runs `AgentApplication`, `AgentRuntime`, a port, recorder, Graph
operation, or developer role.

When a completed runtime result is supplied, only its already bounded public
shape is read. Each receipt must identify the exact profile principal. The
inspection copies outcome, terminal state, all nine non-negative usage
counters, receipt count, proposal/result presence, and cleanup completion; it
does not copy the proposal, capability result, receipt details, or any payload.

## Output and deterministic identity

`AgentPrincipalInspection` serializes as
`kongono.agent-principal-inspection` version 1 with fixed key order and closed
maps. Its lowercase SHA-256 identity is derived from the full canonical value
with only `inspection_identity` omitted.

The value includes only the authorized profile, declaration artifact,
declaration, admission, and generated-wiring identities/digests; contract and
visibility values; aggregate capability, constraint, and evidence counts; the
optional bounded runtime summary; and these fixed limitations:

| Claim | Value |
| --- | --- |
| scope | `agent-principal-v1` |
| disclosure | `safe-aggregate-only` |
| profile currentness | `exact-supplied-profile` |
| external authority currentness | `not-established` |
| authority | `evidence-only` |
| application state | `not-observed` |
| provider state | `not-observed` |
| Graph relationship | `not-established` |
| trusted-code isolation | `not-sandboxed` |

Exact supplied-profile currentness means only that the explicit profile and
wiring match under the accepted validator. It does not establish current
authentication, authorization, consent, delegation, revocation, policy,
credentials, external effects, deployment state, live application state, live
provider state, or runtime-process state.

## Disclosure boundary

Inspection and every downstream view omit principal IDs, capability identities
and purposes, constraint values, evidence identities/digests and provenance,
prompts, proposals, proposal content, capability inputs/outputs, result
payloads, receipt identities/digests, provider/model values, generated
namespaces/classes/PHP, raw declaration/artifact bytes, paths, source locations,
environment data, exception messages/traces/arguments, credentials, secrets,
tokens, headers, URLs, query strings, database content, and arbitrary caller
text.

The included identities and digests are technical binding evidence. They are
not authentication, authorization, hostile-host integrity, non-repudiation,
perfect external truth, or grants.

## Optional Application Graph context

`AgentGraphEvidenceProjector` accepts the inspection, one requested Phase 13G
role, and optionally one already validated Phase 11G
`ApplicationGraphIntelligenceView`. The supplied view must address the same
role. Only its existing Safe artifact/Graph identities, contract version,
aggregate counts, closed vocabulary counts, and fixed semantics are copied.

No raw Graph, Graph artifact, path, fact, field, provenance, Composition state,
generated wiring, or Graph currentness/authority operation is accessed. An
absent view is `graph-evidence-not-supplied`; a supplied view remains separate
evidence. Both say `agent-graph-relationship-not-established`. Neither asserts
that the profile appears in, is current with, is authorized by, or is
executable from the Graph.

## Neutral projections

`AgentInspectionObservationProjector` receives an explicit
`ObservabilityContext` and returns one neutral `kongono.agent.inspection`
version 1 `operational_event` with `ok` status. Its attributes are a fixed Safe
subset of schema version, aggregate counts, runtime-presence/outcome, and
limitation tokens.

`AgentInspectionSecurityAuditProjector` returns
`kongono.agent.inspection.security` version 1 evidence with a fixed Safe schema,
`succeeded` outcome, and `standard` retention intent. Succeeded describes only
successful construction of the inspection projection. It does not say that
authentication or authorization succeeded, a capability was granted, an
effect occurred, or the application is secure.

Neither projector calls a recorder or runtime. They do not export, buffer,
sample, flush, retry, persist, timestamp, or claim delivery, storage,
durability, retention duration, compliance, non-repudiation, tamper evidence,
authorization, or external effects.

## Chie, Hantei, Oshie, and verification

`DeveloperIntelligenceRole` contains only `chie`, `hantei`, and `oshie`, mapped
to `explain`, `verify`, and `teach`. A derived view contains the complete
authorized inspection serialization, optional bounded Graph evidence, and
fixed `not-performed` execution/repair, `evidence-only` authority, and `none`
hidden-data-access tokens.

The package does not depend on or execute Chie, Hantei, Oshie, Knowledge, a
model, prompt, retrieval system, or provider. Separately authorized consumers
may use Chie to explain, Hantei to independently verify, or Oshie to teach from
these values without changing the role boundaries.

`AgentIntelligenceVerifier` independently compares a supplied candidate across
eight ordered groups: role/purpose, inspection identity/schema, aggregate
evidence, runtime summary, Graph evidence, disclosure/currentness/authority,
role execution/repair/hidden access, and deterministic serialization. It never
calls the deriver, repairs, normalizes, or replaces the candidate. Failure uses
fixed ordered tokens only. PASS is comparison evidence, not Hantei execution,
certification, authorization, or a grant.

## Failure, lifecycle, and security

Diagnostics `KGN-AGENT-INSPECTION-1346` through `1350` use closed Safe context
tokens for request/value, binding, completed-runtime, intelligence, and
projection failures. Prior throwables are retained where translated; callers
must not infer that native trace APIs are sanitized.

The package has no filesystem, environment, clock, randomness, network,
process, database, provider, container, registry, Reflection discovery, global,
hidden state, command, UI, debugger, repair, auto-fix, monitor, worker, session,
store, background loop, remote service, or deployment authority. Trusted
generated and in-process PHP remains not sandboxed.

The production graph is 69 packages and 260 local edges and remains acyclic.
Phases 13A through 13H are current. Phase 13H adds only root-owned external
acceptance and the exact local checkpoint; inspection behavior is unchanged.
Phase 13 is closed. Kongono remains pre-1.0, Experimental, and usable without AI, a
provider, an account, or a network.

## Related material

- [ADR 0145](../adr/0145-visibility-safe-agent-principal-inspection-and-bounded-evidence.md)
- [Agent Application Workflow](agent-application-workflow.md)
- [Application Graph Developer Intelligence](application-graph-developer-intelligence.md)
- [Developer Intelligence](developer-intelligence.md)
- [Observability and Operational Evidence](observability-and-operational-evidence.md)
- [Identity and Access Security](identity-and-access-security.md)
- [Phase 13 External Acceptance](phase-13-external-acceptance.md)
- [Phase 13 Roadmap](phase-13-roadmap.md)
