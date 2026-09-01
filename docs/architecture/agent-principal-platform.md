# Agent Principal Platform

> **Current boundary:** Phase 13B implements the opt-in neutral
> [Agent Principal Contract Foundation](agent-principal-contract-foundation.md),
> Phase 13C implements the opt-in deterministic
> [Agent Declaration Artifact](agent-declaration-artifact.md), Phase 13D
> implements deterministic [Agent Admission and Authority Plan](agent-admission-and-authority-plan.md)
> evidence, and Phase 13E implements the provider-neutral
> [Agent Principal Runtime](agent-principal-runtime.md). Phase 13F implements
> one exact-current [Agent Application Workflow](agent-application-workflow.md).
> Phase 13G implements optional visibility-safe
> [Agent Principal Inspection and Evidence](agent-principal-inspection-and-evidence.md).
> Phase 13H adds root-owned [external acceptance](phase-13-external-acceptance.md)
> and the exact local checkpoint only. Phase 13 is closed.
> No provider SDK or adapter, profile registry, command, live monitor, state
> store, memory store, or receipt store exists.

## Purpose

The future Agent Principal Platform will let applications treat an AI agent as
an explicit actor without treating a model, provider, prompt, or tool suggestion
as authority. It establishes the conceptual boundary among identity, intent,
proposal, authorization, capability execution, results, receipts, and cleanup.

Use this architecture when an application may later allow a bounded agent to
propose or perform actions under deterministic application policy. Do not use
it to describe Kongono Chie, Kongono Hantei, or Kongono Oshie, to turn an
extension into an actor, or to claim that Phase 13A provides runtime behavior.

The governing decision is
[ADR 0139](../adr/0139-governed-agent-principals-and-deterministic-authority-boundary.md).
The implementation dependency order is fixed by the
[Phase 13 roadmap](phase-13-roadmap.md).

## Current reality

Phase 12 is complete at checkpoint
`17f53ae03cce32936f0c4c69387416c12afc263d`. Kongono now implements 69
acyclic production packages and 260 local production edges. Phase 13B adds the
neutral contract package, Phase 13C the declaration-build package, Phase 13D
the admission package, Phase 13E the one-operation runtime package, and Phase
13F the Agent Application package, and Phase 13G the Agent Inspection package.

Kongono currently has one supported exact-current Agent Principal application
workflow. Existing
Principal, Authentication, Authorization, Security Context, Graph, Extension,
Observability, Composition, and developer-intelligence boundaries remain
independent and unchanged. Contract construction validates only local grammar,
bounds, types, uniqueness, and order. Phase 13C artifacts remain declaration
evidence. Phase 13D admission consumes only explicit completed
application-owned authentication and authorization evidence and returns an
inert plan; it does not evaluate policy, grant authority, or execute work.
Phase 13E consumes only an exact matching admitted plan, rechecks explicit
caller-owned currentness, and coordinates at most one proposal and one exact
capability port with budgets, cancellation, receipts, and cleanup. It adds no
provider adapter, ambient tool authority, autonomous loop, retained session, or
sandbox.

Phase 13F binds one exact declaration artifact and matching admitted plan into
one immutable profile, deterministic generated PHP, and one explicitly loaded
application. It transfers operation-port cleanup ownership exactly once to
Phase 13E and permits one active synchronous operation. It adds no second
profile, registry, provider, command, retained session, or background work.

Phase 13G revalidates one explicit exact profile/wiring pair and creates only
bounded Safe aggregate and cross-system evidence. It records nothing, executes
no application, runtime, Graph operation, or developer role, observes no live
application/provider state, establishes no external authority currentness or
Graph relationship, repairs nothing, and grants no authority.

## Future conceptual flow

```text
explicit caller/application evidence
  -> principal and authority validation
  -> bounded intent/action proposal
  -> deterministic admission and authorization
  -> explicit capability invocation
  -> result plus bounded receipt/evidence
  -> cleanup/retirement
```

Phase 13E implements the bounded proposal-through-cleanup portion for one
synchronous operation. Phase 13F implements the explicit generated outer
application workflow; no provider payload or opaque agent object may collapse
the sequence. Phase 13G inspects only completed bounded evidence above this
flow and cannot re-enter or alter it.

## Separate facts

### Principal identity reference

The Agent Principal identifies the application actor. It is not a credential,
provider object, session, policy, capability, delegation, consent record, or
authorization decision. Principal kind membership grants nothing.

### Authenticated identity evidence

Authentication may establish bounded facts about an identity claim. Those
facts remain inputs to later decisions. Authentication success does not grant
authorization, capability access, visibility, or execution authority.

### Requested intent and proposed action

Intent describes what the caller or application asks the agent to pursue. A
proposal describes what a model or deterministic proposer suggests doing.
Neither is a grant. Model output remains derived, untrusted evidence.

### Authorization and authority context

Authorization is a deterministic application/security decision over exact
inputs and current evidence. The authority context may eventually bind
Principal, delegation, consent, constraints, policy, and environmental facts,
but Phase 13A selects no schema. Only the separately accepted boundary can
permit an effect.

### Capability request and grant

A capability request names desired access. A grant explicitly supplies one
typed, versioned, purpose-bounded port under exact constraints. Undeclared or
ungranted capability is denied. Identity, provider configuration, Graph
visibility, extension installation, or previous success cannot imply a grant.

### Execution result

A result describes the bounded capability outcome actually returned by the
execution boundary. It is distinct from the proposal and authorization
decision. Partial effects, cancellation, timeout, cleanup failure, and uncertain
state must remain visible.

### Receipt and operational evidence

A receipt records bounded claims about requested, authorized, attempted,
performed, denied, failed, cancelled, and retired work. Observability and
Security Audit may later record related evidence through their own accepted
contracts. Neither a receipt nor a signal is reusable authority or perfect
real-world truth.

## Deterministic authority boundary

An LLM may generate a proposal. It may not decide that its own proposal is
authorized. The following are never authoritative allow/deny mechanisms:

- model output, confidence, or chain-of-thought-like material;
- prompts, completions, tool-call suggestions, embeddings, or memory items;
- retrieved documents, HTTP content, database values, logs, or traces;
- provider responses, hosted sessions, SDK objects, or protocol messages;
- extension declarations, installed packages, or host state;
- Graph facts, visibility, inspection, or developer-intelligence output; and
- old decisions, receipts, or previous successful effects.

A separately governed deterministic mechanism must validate exact identity,
declaration/admission, current authority evidence, capability, constraints, and
policy before an effect. Missing or uncertain authority fails closed.

## Explicit capabilities

Future capabilities are ordinary typed ports supplied by an application-owned
boundary. Each grant is versioned, purpose-bounded, constrained, attributable,
and denied by default. No ambient tool registry exists.

An Agent Principal receives no implied filesystem, shell, process, network,
database, credential, secret, Git, dependency-manager, deployment, container,
Graph, extension-host, Security Context, or developer-tool access. Composition
may later connect explicit capabilities through generated or explicit wiring;
it may not expose a resolver, ambient container, hidden autowiring, or hidden
authority.

Trusted in-process PHP remains non-sandboxed. Capability ports can constrain
what Kongono intentionally supplies; they cannot isolate arbitrary PHP. Phase
13A selects no sandboxed or external execution profile.

## Bounded work and lifecycle

Every future operation has explicit ceilings for applicable work:

- steps;
- wall-clock or monotonic duration;
- tokens or model spend;
- capability/tool calls;
- retries;
- delegation depth;
- output size;
- retained state; and
- concurrent work.

No unbounded loop, hidden daemon, watcher, background worker, automatic retry,
self-replication, recursive delegation, or open-ended self-modification is
enabled by default. Cancellation, timeout, revocation, retirement, and cleanup
are explicit. Primary and cleanup failures stay distinguishable, and uncertain
cleanup retires further authority rather than pretending success.

## Delegation, consent, and human approval

Delegation is bounded evidence from an exact delegator to an exact delegate for
an exact purpose, capability scope, validity, and constraints. It is not
implicitly transitive. A future chain has a fixed maximum depth and is
independently validated.

Consent is separate from delegation. Application policy decides when consent
or explicit human approval is required. Human approval may be appropriate for
high-impact actions, but it is not the only possible authorization mechanism.
Approval must be specific, bounded, attributable, current, and separate from
model output.

Expiry and revocation are checked before an effect. A stale grant, decision,
approval, or receipt does not remain current by convenience.

## Untrusted content and prompt injection

All prompt text, retrieved material, documentation, extension content, Graph-
visible text, tool output, HTTP bodies, database values, logs, traces, memory,
and model output is inert untrusted data. Embedded instructions cannot:

- enlarge capability scope;
- alter system or application policy;
- grant access to hidden information;
- bypass deterministic admission or authorization;
- change caller-approved purpose or constraints; or
- authorize another tool, model, extension, or Principal.

Safe and Protected disclosure doctrine applies before information reaches an
agent. Visibility is not authorization, and omission is not falsehood.

## State, memory, and provenance

Future agent state and memory are application-owned, explicit, bounded,
classified, attributable, and versioned where necessary. Model context is not
the authority store. Hidden framework-global memory, provider-owned authority
state, silent cross-principal sharing, and unbounded retention are prohibited.

Provenance distinguishes:

1. caller input;
2. accepted application evidence;
3. retrieved material;
4. model-derived intent or proposals;
5. capability/tool results;
6. deterministic admission and authorization decisions; and
7. execution results and receipts.

Derived content remains derived. Phase 13A implements no state, memory,
retrieval, embedding, vector, or persistence mechanism.

## Provider and protocol adapters

Providers and protocols sit outside the neutral platform. MCP, A2A, provider
SDKs, hosted-agent APIs, and remote sessions may later translate wire concepts
to accepted Kongono contracts. They cannot define Principal identity,
delegation, consent, capability, constraint, receipt, or authorization meaning.

Phase 13A selects no model, provider, SDK, protocol, endpoint, credential,
pricing model, sampling setting, prompt format, tool-call wire format, transport,
or network policy.

## Relationship map

| Existing boundary | Agent Principal relationship |
| --- | --- |
| Identity and Authentication | May establish identity evidence; grants no authority. |
| Authorization and Security | Own deterministic decisions and protected-capability enforcement. |
| Application Graph | May later provide admitted visibility-filtered structure; grants no capability or execution. |
| Extension Platform | Extensions are contribution units, not agents by implication; Agent Principals are not extensions by implication. |
| Composition | May later wire explicit capabilities; cannot create ambient authority or service location. |
| Observability and Security Audit | Record bounded evidence; do not grant authority or claim perfect truth. |
| Chie, Hantei, and Oshie | Remain developer companions, not application Agent Principals. |
| Kernel, HTTP, Console, Database, Persistence | Remain unchanged and gain no Agent Principal integration in Phase 13A. |

## Failure and security expectations

Future boundaries fail visibly on missing identity evidence, undeclared or
ungranted capability, stale or conflicting authority, exceeded budgets,
cancellation, timeout, revocation, lifecycle misuse, and uncertain cleanup.
Diagnostics and receipts remain bounded and nondisclosing. Credentials,
secrets, Protected values, raw rejected input, provider payloads, and hidden
reasoning are not copied into general evidence.

This architecture does not claim sandboxing, process isolation, production
safety, compliance, durable audit, tamper evidence, non-repudiation, complete
observability, or perfect knowledge of external effects.

## Stability and implementation status

The architecture is **Accepted**. Exact contracts and mechanisms remain
**Experimental** or unselected until their fixed Phase 13 row. Kongono remains
pre-1.0 and not production-ready.

Phase 13A changes documentation only. Each Phase 13B-13H row requires a
separately authenticated architect directive.

## Related material

- [ADR 0139](../adr/0139-governed-agent-principals-and-deterministic-authority-boundary.md)
- [Phase 13 roadmap](phase-13-roadmap.md)
- [Agent model](agent-model.md)
- [Identity and Access Security](identity-and-access-security.md)
- [Authoritative Application Graph](authoritative-application-graph.md)
- [Extension Platform](extension-platform.md)
- [Developer Intelligence](developer-intelligence.md)
- [Observability and Operational Evidence](observability-and-operational-evidence.md)
- [Phase 13 External Acceptance](phase-13-external-acceptance.md)
- [Architectural doctrine](../doctrine.md)
