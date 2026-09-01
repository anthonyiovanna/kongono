# Phase 13 Roadmap

> **Current boundary:** Phase 13 is closed. Phase 13B implements the opt-in neutral
> [Agent Principal Contract Foundation](agent-principal-contract-foundation.md),
> Phase 13C implements the opt-in deterministic
> [Agent Declaration Artifact](agent-declaration-artifact.md), Phase 13D
> implements deterministic [Agent Admission and Authority Plan](agent-admission-and-authority-plan.md)
> evidence, and Phase 13E implements the provider-neutral
> [Agent Principal Runtime](agent-principal-runtime.md). Phase 13F implements the
> exact-current single-profile [Agent Application Workflow](agent-application-workflow.md).
> Phase 13G implements optional visibility-safe
> [Agent Principal Inspection and Evidence](agent-principal-inspection-and-evidence.md).
> Phase 13H adds root-owned external acceptance and the exact local checkpoint.
> Provider SDKs, profile registries, and live monitoring remain absent.

## Purpose and entry state

Phase 12 is complete at checkpoint
`17f53ae03cce32936f0c4c69387416c12afc263d`. Phase 13G brings the production
graph to 69 acyclic packages and 260 local production dependency edges.

Phase 13 establishes an ordered path from neutral Agent Principal contracts to
deterministic declaration and admission evidence, bounded provider-neutral
orchestration, one supported application workflow, visibility-safe inspection,
and external acceptance. Contracts and deterministic authority evidence must
exist before any model or capability execution.

[ADR 0139](../adr/0139-governed-agent-principals-and-deterministic-authority-boundary.md)
governs the architecture. The [Agent Principal Platform guide](agent-principal-platform.md)
explains the future conceptual flow.

## Fixed dependency order

| Phase | Boundary |
| --- | --- |
| `13A` | Accept governed Agent Principal architecture, deterministic authority separation, security/lifecycle doctrine, and this fixed roadmap. Documentation only. |
| `13B` | Implement a neutral versioned Agent Principal contract foundation for bounded identities/references, intent, action proposals, capability requests, constraints, delegation/consent evidence references, provenance, visibility, and receipts. No model/provider/runtime or authorization. |
| `13C` | Implement one explicit application-owned deterministic agent declaration artifact with strict build, exact-current validation, and content-addressed no-clobber publication. No discovery, model invocation, grants, or execution. |
| `13D` | Implement deterministic application-owned agent admission and explicit authority-plan evidence that binds exact declarations to exact authenticated/authorized application evidence and denies undeclared capability by default. No model execution or tool effects. |
| `13E` | Implement a provider-neutral bounded Agent Principal orchestration runtime over explicit caller-supplied proposal/model and capability ports, with exact lifecycle, budgets, cancellation, cleanup, and receipts. No provider SDK, ambient tools, autonomous background loop, or application integration. |
| `13F` | Implement one supported application workflow and generated/explicit wiring boundary for the accepted agent profile, including exact-current loading and operation ownership. No second profile, marketplace, remote agent service, deployment manager, or broad automation product. |
| `13G` | Implement visibility-safe read-only inspection plus bounded Application Graph, Security, Observability, Chie, Hantei, and Oshie evidence without role execution, self-authorization, repair, or hidden data access. |
| `13H` | Add root-owned external acceptance and the exact local Phase 13 checkpoint only. No production expansion, provider, release, Stable/1.0 claim, push, tag, or Phase 14 behavior. |

The table is dependency order, not automatic authorization. Every row requires
a separately authenticated architect directive. Completion of one row does not
begin or authorize the next.

## Current versus planned

Phases 13A-13H are implemented to their separately accepted boundaries. The
neutral contract, deterministic declaration artifact, inert admission plan,
synchronous provider-neutral one-operation runtime, exact-current
single-profile application workflow, and visibility-safe bounded inspection
are current. Inspection is evidence-only and does not observe live state,
record projections, execute developer roles, repair, or establish external
authority currentness or a Graph relationship. Provider SDKs and adapters,
profile registries, commands, live monitoring, external acceptance, retained
sessions, durable workflows, and autonomous operation do not exist. Phase 13H
adds only the deterministic offline root harness and exact local checkpoint;
Phase 13 is closed and Phase 14 has not begun.

## Preserved boundaries

- Agent identity is not authentication, authorization, delegation, consent,
  capability, visibility, or execution authority.
- Model, provider, prompt, retrieval, memory, extension, Graph, inspection, and
  developer-tool output cannot make the authoritative allow/deny decision.
- Capabilities are explicit, typed, versioned, purpose-bounded, attributable,
  constrained, and denied by default.
- Requests, proposals, admission, authorization, execution, results, and
  receipts remain distinct.
- Future work is bounded by explicit steps, duration, budgets, calls, retries,
  delegation depth, output, retained state, concurrency, cancellation,
  retirement, and cleanup.
- Chie, Hantei, and Oshie remain developer companions, not application Agent
  Principals.
- Application Graph, Extensions, Security, Observability, Composition, Kernel,
  HTTP, Console, Database, and Persistence retain their accepted independent
  authority and lifecycle boundaries.
- Kongono remains usable without AI, a provider, an account, or a network.

## Outside Phase 13A

Phase 13A implements or selects no PHP, package, manifest, dependency, schema,
artifact, builder, validator, loader, publisher, command, skeleton, model,
provider, SDK, prompt, tool registry, capability executor, orchestration loop,
memory, vector store, retrieval engine, policy language, delegation or consent
engine, receipt format, audit store, frontend, deployment behavior, marketplace,
MCP, A2A, remote service, sandbox, or external execution profile.

It makes no production-readiness, safety-certification, isolation, compliance,
non-repudiation, durability, performance, Stable API, 1.0, PHP 8.6, or PHP 9
claim.

## Related material

- [ADR 0139](../adr/0139-governed-agent-principals-and-deterministic-authority-boundary.md)
- [Agent Principal Platform](agent-principal-platform.md)
- [Agent Principal Runtime](agent-principal-runtime.md)
- [Agent Application Workflow](agent-application-workflow.md)
- [Agent Principal Inspection and Evidence](agent-principal-inspection-and-evidence.md)
- [Phase 13 External Acceptance](phase-13-external-acceptance.md)
- [Agent model](agent-model.md)
- [Identity and Access Security](identity-and-access-security.md)
- [Extension Platform](extension-platform.md)
- [Authoritative Application Graph](authoritative-application-graph.md)
- [Developer Intelligence](developer-intelligence.md)
- [Observability and Operational Evidence](observability-and-operational-evidence.md)
