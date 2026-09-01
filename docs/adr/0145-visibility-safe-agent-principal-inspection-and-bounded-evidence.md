# ADR 0145: Visibility-Safe Agent Principal Inspection and Bounded Evidence

- Status: Accepted
- Date: 2026-08-31
- Phase: 13G

## Context

Phase 13F binds one exact Agent Principal declaration artifact and admitted
authority plan into one application profile and deterministic generated wiring.
Those values are intentionally evidence rather than ambient authority. A
developer-intelligence consumer needs a way to inspect that already accepted
pair without loading generated PHP, observing a live application, repeating an
operation, or disclosing the raw declaration, proposal, result, or provider
content.

Application Graph intelligence, Observability, Security Audit, Chie, Hantei,
and Oshie already have distinct boundaries. Agent inspection must consume or
project only their neutral bounded evidence. It must not turn cross-system data
into a registry, currentness source, authorization decision, recording action,
or role execution.

## Decision

Kongono adds the optional Experimental `kongono/agent-inspection` upper evidence
leaf. One inspection request accepts exactly one explicit
`AgentApplicationProfile`, one explicit `GeneratedAgentWiring`, and optionally
one completed `AgentRuntimeResult`. It accepts no path, handle, port, recorder,
provider, registry, Graph reader, workspace reader, or currentness source.

The inspector uses the existing Phase 13F validator to revalidate generated
wiring structurally and exact-current relative to the explicit profile. It then
reconciles profile, declaration artifact, authority plan, admission, ordered
permitted capabilities, constraints, and evidence references. Supplied runtime
evidence is read only through its bounded public result and every receipt must
be attributable to the exact declared principal. No application, runtime,
proposal port, capability port, currentness source, or generated PHP executes.

The resulting `kongono.agent-principal-inspection` version 1 value is canonical,
deterministic, immutable, and presentation-neutral. Its identity hashes the
canonical serialization with only the identity field omitted. It contains the
approved technical identities and digests, contract and visibility tokens,
aggregate declaration/admission counts, and an optional completed-runtime
summary with all nine usage counters, bounded outcome/state values, receipt
count, three presence/cleanup booleans, and no payloads.

Every inspection states `exact-supplied-profile` for the profile/wiring proof,
while external authority currentness and a relationship to the Graph remain
`not-established`. Live application and provider state remain `not-observed`;
authority is `evidence-only`; disclosure is `safe-aggregate-only`; trusted
generated and in-process PHP is `not-sandboxed`.

An optional separately supplied Phase 11G
`ApplicationGraphIntelligenceView` must address the same requested role. Only
its existing Safe artifact and Graph identities, contract version, aggregate
node/relation counts, closed vocabulary counts, and fixed limitations are
copied. No raw Graph, fact, artifact, path, currentness proof, authorization, or
agent-to-Graph relationship is accessed or inferred. Absence is explicit.

The package can return one neutral `kongono.agent.inspection` version 1
operational Observation and one `kongono.agent.inspection.security` version 1
Security Audit evidence value with `succeeded` projection outcome and standard
retention intent. Both contain closed Safe attributes. They are inert values:
the package never records, exports, buffers, samples, flushes, retries,
timestamps, stores, or proves delivery, durability, retention duration,
compliance, authorization, external effect, or application security.

The `chie`, `hantei`, and `oshie` role tokens select `explain`, `verify`, and
`teach` views. All views carry the complete authorized inspection, optional
bounded Graph evidence, and fixed no-execution/no-repair/no-hidden-access
semantics. The package does not depend on or invoke those role packages,
Knowledge, a model, a prompt, retrieval, or a provider. An independent verifier
compares eight ordered evidence groups without calling the deriver, normalizing,
repairing, or replacing the candidate. Its fixed PASS/FAIL result is comparison
evidence, not Hantei execution or certification.

Principal IDs; capability identities, purposes, and inputs; constraint values;
evidence identities and provenance; proposal and result content; receipt
identities and digests; generated namespaces, classes, PHP, and raw bytes;
paths, source locations, environment data, messages, traces, credentials,
secrets, tokens, headers, URLs, query strings, database data, and arbitrary
caller text never cross the inspection boundary.

## Security and lifecycle consequences

Inspection is stateless and performs no filesystem, environment, clock,
randomness, network, process, database, provider, container, registry,
Reflection discovery, hidden-global, runtime-monitoring, repair, mutation, or
background work. An inspection identity, Graph evidence, observation, audit
evidence, verifier PASS, runtime result, or receipt is never authentication,
authorization, current policy, a capability grant, proof of an external effect,
or reusable authority.

Fixed diagnostics `KGN-AGENT-INSPECTION-1346` through `1350` describe invalid
inspection, binding, runtime, intelligence, and projection evidence using only
closed Safe component tokens. Translation preserves prior throwables without
claiming that native trace APIs are sanitized.

## Dependency and compatibility consequences

`kongono/agent-inspection` depends only on PHP and the ten accepted Agent
Admission, Agent Application, Agent Declaration Build, Agent Principal
Contract, Agent Runtime, API Metadata, Application Graph Intelligence,
Diagnostics, Observability, and Security Audit packages. PHPUnit is its only
development dependency. It is an upper evidence leaf; no existing production
package depends on it and no existing production PHP changes.

The production graph is 69 packages, 260 local edges, and acyclic. No API is
Stable and no third-party dependency is added.

## Alternatives rejected

- Loading an application or generated PHP would turn inspection into a runtime
  and trusted-code boundary.
- Reading paths, registries, Graph facts, recorders, or ambient currentness
  would add hidden authority and discovery.
- Exposing raw identity, capability, policy, proposal, result, or provider data
  would violate the Safe aggregate disclosure boundary.
- Recording projections would conflate neutral evidence construction with
  delivery and durability authority.
- Calling Chie, Hantei, or Oshie would couple evidence addressing to role
  execution and self-certification.
- Repair, replay, debugger, monitoring, provider, command, UI, and autonomous
  behavior belong outside this phase.

## Current boundary

Phases 13A through 13G are current. Phase 13H external acceptance remains
planned and requires a separately authenticated directive. Kongono remains
pre-1.0, Experimental, and usable without AI, a provider, an account, or a
network.

## Related material

- [Agent Principal Inspection and Evidence](../architecture/agent-principal-inspection-and-evidence.md)
- [Agent Application Workflow](../architecture/agent-application-workflow.md)
- [Application Graph Developer Intelligence](../architecture/application-graph-developer-intelligence.md)
- [Observability and Operational Evidence](../architecture/observability-and-operational-evidence.md)
- [Identity and Access Security](../architecture/identity-and-access-security.md)
- [Phase 13 Roadmap](../architecture/phase-13-roadmap.md)
