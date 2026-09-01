# ADR 0139: Governed Agent Principals and Deterministic Authority Boundary

## Status

Accepted

## Date

2026-08-31

## Context

[ADR 0008](0008-agents-as-principals.md) accepts Agent as a potential
first-class Principal kind, but it deliberately leaves execution, delegation,
consent, capability, state, provider, and protocol mechanics open. Kongono now
has implemented Identity and Access Security, authoritative Application Graph,
Observability, developer-intelligence, and Extension Platform boundaries.
None of those boundaries silently grants an application Agent Principal
authority.

Useful agentic applications need more than a provider object or a generic API
client. They need an exact actor reference, separately established identity
evidence, application-owned policy, bounded intent and proposals, explicit
capabilities, visible lifecycle limits, and evidence of what was attempted and
what actually occurred. Collapsing those facts into one opaque agent object or
provider payload would make authority uninspectable and let model output,
retrieved text, or protocol conventions become policy.

Phase 12 is complete at checkpoint
`17f53ae03cce32936f0c4c69387416c12afc263d`. Phase 13A accepts this
architecture and the fixed [Phase 13 roadmap](../architecture/phase-13-roadmap.md)
as documentation only. It implements no Agent Principal package, contract,
schema, declaration, admission, runtime, model, provider, capability port,
command, workflow, store, protocol adapter, or test harness.

## Decision

### Agent Principal identity

An **Agent Principal** is an application actor kind within Kongono's broader
Principal model. Its identity is an immutable reference. Identity alone never
implies authentication, authorization, trust, delegation, consent, capability,
visibility, currentness, or execution authority.

Future contracts must keep these facts distinct:

- Principal identity reference;
- authenticated identity evidence;
- authorization decisions;
- delegated authority;
- consent evidence where applicable;
- requested intent;
- proposed actions;
- explicit capability grants;
- constraints and budgets;
- execution results; and
- receipts plus operational or audit evidence.

No one opaque agent object, model session, provider response, token, prompt, or
protocol payload may stand for all of them.

### Deterministic authority

An LLM, model, prompt, completion, suggested tool call, embedding result,
retrieved document, memory item, confidence score, chain-of-thought-like
material, provider response, extension declaration, or Application Graph fact
can never make the authoritative allow/deny decision.

Models may propose intent or action. A separately governed deterministic
Kongono or application boundary must decide whether the proposal is declared,
admissible, current, and authorized from exact explicit inputs and inspectable
evidence. Missing, stale, ambiguous, conflicting, unsupported, unverifiable,
or failed authority evidence fails closed. Previous success and old receipts do
not grant current authority.

### Explicit capability execution

Future effectful execution uses typed, versioned, purpose-bounded capability
ports. Capabilities are denied by default, supplied explicitly, and attributable
to an exact Agent Principal and authority context. A request is not a grant.

No filesystem, process, network, database, credential, secret, deployment, Git,
package-manager, framework-container, extension-host, Graph, Security Context,
or developer-tool access is inferred from agent identity, provider selection,
model configuration, installation, or visibility. Composition may eventually
wire explicit capabilities but may not introduce service location, ambient
containers, hidden autowiring, or hidden authority.

### Bounded operation and lifecycle

Future execution must have fixed or caller-supplied ceilings for every
applicable resource, including step count, wall-clock or monotonic duration,
token or model budget, tool calls, retries, delegation depth, output size,
retained state, and concurrent work.

Unbounded autonomous loops, hidden background workers, implicit retry,
self-replication, recursive delegation, and open-ended self-modification are
not permitted by default. Cancellation, timeout, revocation, retirement,
partial failure, and cleanup uncertainty must be visible. Operation-owned state
must be released on every path, and primary plus cleanup failure identity must
be preserved under Kongono's existing lifecycle doctrine.

### Delegation, consent, expiry, and revocation

Delegation is explicit bounded evidence, not a transitive assumption. A future
delegation chain must identify delegator, delegate, purpose, capability scope,
constraints, validity or currentness, and provenance. Delegation depth is
bounded and independently validated.

Consent and delegation are not interchangeable. Where consent applies, it is
specific, attributable, current evidence evaluated by the separately accepted
authority boundary. Expiry and revocation are checked before an effect. Phase
13A selects no schema, algorithm, policy language, storage, or protocol for
delegation, consent, expiry, or revocation.

### Untrusted input and prompt injection

Prompts, retrieved content, tool output, extension content, Graph-visible text,
documentation, logs, traces, database values, HTTP content, model output, and
memory are untrusted inert data. Instructions inside those values cannot
enlarge tool authority, alter system policy, bypass deterministic
authorization, or change caller-approved scope.

Existing disclosure classifications and visibility-filtered views remain
binding. Information is not universally visible merely because it exists
elsewhere in an application. Redaction is not collection permission, and
omitted facts remain omitted rather than false.

### State, memory, and provenance

Future agent state or memory is explicit, application-owned, bounded,
classified, attributable, and versioned where required. It is governed
independently from model context. Hidden framework-global memory,
provider-owned authority state, silent cross-principal sharing, ambient current
agent state, and unbounded retention are rejected.

Provenance distinguishes caller input, accepted application evidence, retrieved
material, model-derived proposals, tool results, deterministic decisions,
execution results, and receipts. Derived content is not accepted truth merely
because it is repeated, recent, confident, or convenient. Phase 13A implements
no memory or state store.

### Provider and protocol independence

Model providers and protocols are outer adapters. MCP, A2A, provider SDKs,
hosted-agent APIs, and future transport profiles may adapt explicitly accepted
Kongono concepts; they do not define the internal Principal, delegation,
consent, capability, constraint, receipt, or authorization model.

Provider selection, credentials, endpoints, pricing, model identifiers,
sampling, prompt format, tool-call wire format, remote sessions, and network
policy remain caller or adapter concerns unless separately accepted. Phase 13A
selects no provider, model, protocol, SDK, dependency, or transport.

### Relationship to existing systems

- Identity and Authentication may establish exact identity evidence; neither
  grants Agent Principal authority.
- Authorization and Security own deterministic decisions and protected-
  capability enforcement; an agent layer cannot replace or weaken them.
- The Application Graph describes admitted structure. It grants no capability,
  visibility, authorization, or execution authority.
- Extensions and Agent Principals are independent. An extension is not an agent,
  and an agent is not an extension, by implication.
- Kongono Chie, Kongono Hantei, and Kongono Oshie are optional developer
  companions, not application Agent Principals. Chie helps build, Hantei
  independently proves, and Oshie teaches.
- Observability and Security Audit record bounded evidence. They neither grant
  authority nor claim perfect real-world truth.
- Composition may later wire explicit capabilities but cannot manufacture
  authority or introduce ambient service location.
- Kernel, HTTP, Console, Database, Persistence, Extensions, Graph, Security,
  Observability, Chie, Hantei, and Oshie runtime behavior is unchanged by Phase
  13A.

### Human control and receipts

Application policy may require explicit human approval for high-impact or
application-defined actions. Approval is specific, bounded, attributable,
current, and separate from model output. Human approval is not required as the
only possible authorization mechanism for every action; deterministic
application policy remains authoritative.

Future receipts distinguish what was requested, authorized, attempted,
performed, denied, failed, cancelled, and retired. A receipt is bounded
evidence, not a transferable grant. It does not claim perfect real-world truth,
non-repudiation, durable storage, tamper evidence, compliance, or complete
auditability unless later implementation and external proof establish that
exact property.

### Stability

The governed Agent Principal direction, deterministic authority separation,
explicit capability rule, bounded lifecycle, provider independence, and fixed
Phase 13 ordering are **Accepted**. Exact schemas, packages, APIs, protocols,
provider adapters, policy language, runtime mechanisms, storage, and execution
profiles remain **Experimental** or unselected until their separately
authenticated phase.

Kongono remains pre-1.0 and not production-ready. Phase 13A makes no sandbox,
isolation, safety-certification, compliance, non-repudiation, durability,
performance, Stable API, or universal-portability claim.

## Why Agent Principals are distinct

An ordinary API client is a transport participant and may not express the
agent-specific relationship among proposed intent, delegation, constraints,
budgets, model-derived material, and receipts. An extension is an attributable
application-selected contribution unit; installation, admission, or hosting
does not make it a Principal. Chie, Hantei, and Oshie are developer companions
with separate evidence contracts and no application Principal identity.

Keeping these concepts separate prevents provider sessions, extension state,
and developer-tool output from becoming application authority by convenience.

## Consequences

- Applications can later govern agent activity through the same deterministic
  security doctrine as other protected operations without treating agents as
  generic provider payloads.
- Every proposal and effect can remain attributable to exact identity,
  authority, capability, constraints, and lifecycle evidence.
- Provider and protocol integration must adapt inward rather than define the
  core model.
- Later phases must implement more explicit evidence and validation before any
  model or capability execution is available.
- Kongono remains fully usable without AI, a provider, an account, or a
  network.

## Rejected alternatives

- **Model decides authorization:** model confidence or generated reasoning is
  not deterministic policy evidence.
- **Provider SDK defines Kongono architecture:** provider objects and hosted
  sessions are outer adapter concerns.
- **Ambient all-tools agent:** implicit filesystem, network, process, database,
  secret, Graph, or framework access violates deny-by-default capability
  ownership.
- **Installed extension becomes agent authority:** installation, admission,
  grants, hosting, and Principal identity are separate facts.
- **Graph fact becomes capability grant:** Graph authority is descriptive only.
- **Hidden global memory:** ambient or silently shared state breaks ownership,
  provenance, disclosure, and persistent-runtime isolation.
- **Unbounded autonomous loop:** work without explicit ceilings, cancellation,
  and retirement cannot satisfy Kongono lifecycle doctrine.
- **Developer companion becomes application Principal:** Chie, Hantei, and
  Oshie retain their independent developer-facing roles.
- **Prompt instruction expands scope:** retrieved or generated instructions are
  inert data and cannot enlarge authority.
- **Protocol-first MCP or A2A core model:** protocols may adapt accepted
  concepts but may not define them.

## Deferred decisions

Phase 13B-13H require separate independently authenticated directives. Exact
contract types, package names, schemas, declaration formats, admission evidence,
authority plans, policy integrations, capability ports, orchestration APIs,
budgets, model adapters, provider adapters, generated wiring, state, memory,
receipts, commands, inspection, Application Graph contributions, external
acceptance, and checkpoint mechanics remain deferred to their fixed rows.

No later phase begins automatically.

## Related material

- [Agent Principal Platform](../architecture/agent-principal-platform.md)
- [Phase 13 roadmap](../architecture/phase-13-roadmap.md)
- [Agent model](../architecture/agent-model.md)
- [ADR 0008](0008-agents-as-principals.md)
- [ADR 0060](0060-human-centered-developer-intelligence-family.md)
- [ADR 0061](0061-versioned-knowledge-source-and-provenance-contract.md)
- [ADR 0105](0105-explicit-identity-authentication-and-authorization-boundary.md)
- [ADR 0106](0106-fail-closed-security-context-and-policy-separation.md)
- [ADR 0123](0123-authoritative-application-graph-and-language-neutral-ir-boundary.md)
- [ADR 0131](0131-governed-extension-platform-and-attributable-contribution-boundary.md)
- [Identity and Access Security](../architecture/identity-and-access-security.md)
- [Extension Platform](../architecture/extension-platform.md)
- [Observability and Operational Evidence](../architecture/observability-and-operational-evidence.md)
