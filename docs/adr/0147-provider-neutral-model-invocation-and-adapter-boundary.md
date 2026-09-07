# ADR 0147: Provider-Neutral Model Invocation and Adapter Boundary

## Status

Accepted for Phase 14A as documentation architecture only.

## Date

2026-08-31

## Context

Phase 13 closed the governed Agent Principal milestone at checkpoint
`63376c802ac4672c21458b67b2fcadaf8e03a019`. It provides neutral principal
values, deterministic declaration and admission evidence, a synchronous
one-operation runtime over caller-supplied ports, an exact-current application
workflow, visibility-safe inspection, and external acceptance. The proposal
port supplied to the Phase 13 runtime is implemented. No Phase 13 package calls
a model provider, selects a model, discovers credentials, owns network
transport, or converts provider output into authority.

Applications may eventually choose to use a model to produce proposal evidence.
That concern requires its own boundary. Treating a provider SDK, a model name,
a prompt, a response, or a tool-call suggestion as part of Agent Principal
authority would collapse evidence and authorization. Treating vendor behavior
as the neutral framework contract would make application policy and lifecycle
dependent on one provider. Treating retries, fallback, credentials, streaming,
or telemetry as ambient adapter behavior would conceal effects and uncertainty.

Kongono must remain fully buildable, testable, diagnosable, documented,
deployable, and operable without AI, a model provider, an account, credentials,
or a network. A provider-neutral invocation platform is therefore optional and
must sit above explicit application policy and beside, not inside, the Phase 13
authority flow.

## Decision

### 1. Boundary and dependency direction

Kongono accepts this conceptual direction for future separately authorized
Phase 14 work:

```text
application-owned configuration and authority evidence
    -> provider-neutral request and plan
    -> bounded coordinator
    -> explicit application-supplied adapter port
    -> explicit transport outside the neutral core
    -> untrusted provider response or events
    -> validated normalized model result
    -> inert Agent proposal evidence
    -> Phase 13 deterministic authority
```

The invocation boundary may help an application obtain proposal evidence. It
does not authenticate a principal, admit a declaration, grant a capability,
authorize an effect, execute a tool, or replace the Phase 13 current-authority
checks. Phase 13 remains the deterministic authority boundary after any model
work is complete.

The neutral core must not depend on a vendor SDK or wire protocol. Concrete
provider adapters are outer leaves selected and supplied by the application.
There is no default provider, ambient adapter, service locator, dynamic
discovery, registry lookup, or environment-driven selection.

### 2. Semantic separation

Future contracts must keep at least these concepts semantically distinct:

1. provider capability description;
2. application-owned model profile reference;
3. credential reference and credential-supply port;
4. immutable invocation request;
5. normalized invocation plan;
6. transport attempt;
7. raw provider response or stream event;
8. validated normalized model result;
9. proposed action or tool-call suggestion;
10. usage and cost evidence;
11. invocation receipt; and
12. cleanup evidence.

These are concept categories, not a selected PHP API, serialized schema, wire
format, package layout, or executable contract. A future directive must define
any concrete representation.

A capability description states adapter evidence about supported behavior; it
does not prove current availability, compatibility, entitlement, account state,
quota, safety, or correctness. A profile reference is application-owned
configuration evidence; it is not a credential, provider object, global
default, model registry entry, or authority grant. A request expresses desired
model work; it does not authorize application effects. A plan is normalized
coordination evidence; it is not a network call or capability grant. Usage,
cost, receipts, and cleanup records are bounded claims, not perfect provider or
billing truth.

Raw responses, streaming events, normalized results, proposed actions, tool
suggestions, citations, safety labels, confidence values, and provider metadata
remain untrusted evidence. None may make an authoritative allow or deny
decision, expand a capability, select a secret, or bypass application policy.

### 3. Application-owned policy and configuration

Applications own whether model invocation is permitted, which exact profile is
in scope, which adapter is supplied, which budgets apply, and whether a
normalized result is eligible to become inert Agent proposal evidence. Kongono
does not infer these choices from a principal kind, a prompt, model output,
environment variables, installed packages, Graph facts, extension state,
inspection, memory, or developer-tool output.

Provider capability requirements and profile compatibility must be explicit,
versioned, and fail visibly when unsupported, ambiguous, stale, mixed, or
lossy. Provider-specific fields cannot silently leak into the neutral contract.
An adapter may preserve bounded provider evidence, but it must not redefine the
meaning of the neutral request or result.

Phase 14A selects no provider, model, SDK, endpoint, transport protocol,
credential source, secret store, pricing source, tokenizer, content policy,
serialization format, package name, command, runtime API, or application
profile.

### 4. Credentials and secrets

Credentials remain application-owned. A future invocation may receive them only
through an explicit narrow supply boundary at the moment they are needed by an
outer adapter. Credential references are not credentials. Profiles, generated
artifacts, Application Graph evidence, diagnostics, logs, traces, metrics,
events, audit evidence, receipts, model inputs, model outputs, caches, and
developer-intelligence material must not contain credential values.

The neutral core must not read ambient environment state, configuration files,
global stores, home directories, process arguments, or service locators to find
credentials. It must not persist, echo, hash for disclosure, serialize, inspect,
or infer credentials. Phase 14A selects no credential mechanism.

### 5. Untrusted content and prompt injection

System instructions, application prompts, user content, retrieved content,
provider responses, tool descriptions, model-generated arguments, citations,
and stream fragments are untrusted inert data at the invocation boundary. Their
text cannot enlarge authority, alter the supplied adapter, discover credentials,
change budgets, enable fallback, select a capability, or instruct Kongono or a
developer tool to perform an effect.

Applications must separate trusted configuration and separately established
authority evidence from model-visible content. Prompt injection is therefore
handled as untrusted-content influence, never as an authority transfer.
Credentials and secrets must not be placed into model-visible content merely
because a prompt requests them.

Hidden provider reasoning is neither required nor accepted as authoritative
evidence. Kongono does not require collection, storage, disclosure, replay, or
interpretation of hidden reasoning. Diagnostics and receipts must not imply
that hidden reasoning was inspected or verified.

### 6. Lifecycle, budgets, and isolation

Future execution must have explicit lifecycle states from accepted input
through planning, adapter acquisition, transport activity, result validation,
completion, failure, cancellation, and cleanup. A future directive must define
the exact state vocabulary and legal transitions before implementation.

Every operation must have explicit bounded ceilings appropriate to its accepted
contract, including time, cancellation, input, output, streaming, attempts,
usage, cost, and retained evidence where applicable. Exceeding a bound fails
closed. Cancellation and deadlines are explicit collaborators, not hidden
global state. Cleanup is deterministic, bounded, and reports uncertainty
without erasing the primary failure.

Invocation state is operation-scoped. No request, response, partial event,
credential, adapter instance, failure, receipt, cost evidence, or cancellation
state may leak into a later operation. Persistent hosts must support sequential
isolation and define reentrancy or concurrency explicitly. Phase 14A selects no
concurrency model or executable lifecycle.

### 7. Streaming

Streaming is a lifecycle mode, not a transport shortcut or authority surface.
Partial events are inert and incomplete. They do not become a normalized final
result, proposal evidence, tool authority, usage truth, or successful receipt.
Future streaming support must define explicit bounds, ordering expectations,
backpressure, cancellation, terminal conditions, validation, finalization,
cleanup, and partial-progress failure evidence.

Phase 14A selects no streaming wire format, callback, iterator, event schema,
buffering strategy, transport, or runtime API.

### 8. Attempts, retry, fallback, and uncertainty

An adapter must not perform silent SDK retries, automatic model changes,
credential rotation, endpoint changes, or provider fallback. Each transport
attempt must remain distinguishable from the logical invocation. Retry, if
later authorized, requires an explicit bounded policy covering idempotency,
attempt ceilings, backoff, clock and cancellation interaction, rate limits,
usage and cost accumulation, disclosure, and uncertain outcomes.

Fallback to another model, provider, profile, credential, region, or endpoint
does not occur unless separately authorized by application policy and a future
directive. Failure must not be relabeled as success merely because partial
content exists. Cleanup or transport uncertainty must remain visible.

### 9. Versioning and compatibility

Neutral invocation contracts are versioned independently from provider SDKs,
provider APIs, model releases, and application profiles. A provider adapter must
declare the exact neutral contract and provider capability evidence it supports.
Unsupported, stale, mixed-version, ambiguous, unverifiable, or lossy mappings
fail visibly before their result is treated as current normalized evidence.

Provider renames, aliases, deprecations, pricing changes, capability changes,
and SDK behavior are external facts. They are not silently normalized into
Kongono truth. Phase 14A makes no compatibility or support promise for any
provider or model.

### 10. Diagnostics and operational evidence

Future diagnostics must be actionable and nondisclosing. They may identify a
bounded stage, failure class, capability mismatch, lifecycle state, or safe
remediation, but must omit raw prompts, responses, stream fragments, tool
arguments, retrieved content, credentials, secrets, private endpoints,
provider payloads, personal data, hidden reasoning, and arbitrary caller data.
Preserving a prior throwable does not claim that native traces or provider
messages are sanitized.

Operational evidence remains separated by purpose. Diagnostics explain
failures; observability reports bounded operation evidence; security audit
records bounded security-relevant claims; receipts summarize an invocation;
usage and cost evidence remain provider-derived claims. Correlation is not
authority, delivery is not durability, and a receipt is not billing truth.

Chie may explain accepted, exact-current architecture. Hantei may independently
verify deterministic evidence. Oshie may teach accepted sources. None invokes a
model on behalf of the application, supplies credentials, selects an adapter,
authorizes an effect, repairs an invocation, or certifies its own output.

### 11. Developer experience

Safe future developer experience uses explicit typed inputs and results,
ordinary interfaces, application-owned provider selection, generated or
explicit wiring, deterministic fake adapters, and bounded evidence. Network
access, retry, state retention, telemetry, credential lookup, provider choice,
and fallback are never hidden conveniences.

Examples and tests must label fakes and synthetic provider evidence clearly.
Passing deterministic tests does not prove live provider compatibility,
availability, latency, pricing, account entitlement, security, or production
readiness.

### 12. Stability and current scope

This decision is Accepted architecture. The mechanisms it describes remain
planned until their roadmap row is separately authorized and implemented.
Phase 14A creates and reconciles Markdown documentation only. It adds no PHP,
package, dependency, manifest, lock-file change, schema, artifact, command,
provider, SDK, endpoint, credential mechanism, model profile, network client,
runtime, adapter, Agent bridge, Graph integration, observability integration,
security integration, test behavior, or production support claim.

The fixed dependency sequence is recorded in the
[Phase 14 roadmap](../architecture/phase-14-roadmap.md). The architectural
contract is explained in the
[Provider-Neutral Model Invocation guide](../architecture/provider-neutral-model-invocation.md).

## Rationale

This boundary preserves provider independence while making future model work
explicit and inspectable. It keeps application policy above neutral mechanics,
transport outside the core, credentials outside declarative evidence, and model
output below deterministic authority. It also exposes the lifecycle and cost
consequences that provider SDK conveniences often hide.

Separating normalized result evidence from Agent proposal evidence lets
applications use the invocation platform without adopting Agent Principals and
lets Agent applications continue to operate with non-model proposal ports.
Keeping Phase 13 authority downstream prevents a provider response from becoming
a capability grant by convention.

## Consequences

- Kongono retains a provider-neutral conceptual contract and optional adoption.
- Applications remain responsible for policy, exact profile choice, adapter
  supply, credentials, and transport authority.
- Provider adapters remain outer leaves and cannot define neutral semantics.
- Model output and provider evidence remain untrusted and non-authoritative.
- Streaming, retry, fallback, usage, cost, receipts, and cleanup require explicit
  bounded treatment rather than hidden SDK defaults.
- Future implementation proceeds only through separately authenticated roadmap
  directives.
- Phase 14A itself changes documentation only.

## Rejected alternatives

### Put provider invocation inside the Phase 13 Agent runtime

Rejected because the Phase 13 runtime intentionally accepts an explicit
caller-supplied proposal port and owns deterministic authority checks. Embedding
a provider would conflate proposal production with authority and make Agent
Principals mandatory for model invocation.

### Adopt one provider SDK as the framework abstraction

Rejected because vendor SDK types, retries, transport, credentials, and release
cadence would define the neutral contract and hide application-owned choices.

### Discover providers, profiles, or credentials automatically

Rejected because ambient discovery conceals authority, makes behavior dependent
on process state, and risks cross-operation credential and configuration leaks.

### Treat tool-call output as executable authority

Rejected because model output is untrusted proposal evidence. Only separately
governed application and Phase 13 mechanisms may admit, authorize, and execute
effects.

### Permit silent retry or fallback

Rejected because it hides attempts, usage, cost, rate-limit interaction,
idempotency risk, provider changes, and uncertain outcomes.

### Store hidden reasoning for audit or authorization

Rejected because hidden reasoning is not required or authoritative, may be
unavailable, and can widen disclosure and retention without improving the
deterministic authority boundary.

## Related decisions

- [ADR 0060: Human-Centered Developer-Intelligence Family](0060-human-centered-developer-intelligence-family.md)
- [ADR 0061: Versioned Knowledge-Source and Provenance Contract](0061-versioned-knowledge-source-and-provenance-contract.md)
- [ADR 0106: Fail-Closed Security Context and Policy Separation](0106-fail-closed-security-context-and-policy-separation.md)
- [ADR 0115: Structured Signals, Context, and Exporter Neutrality](0115-structured-signals-context-and-exporter-neutrality.md)
- [ADR 0123: Authoritative Application Graph and Language-Neutral IR Boundary](0123-authoritative-application-graph-and-language-neutral-ir-boundary.md)
- [ADR 0139: Governed Agent Principals and Deterministic Authority Boundary](0139-governed-agent-principals-and-deterministic-authority-boundary.md)
- [ADR 0143: Provider-Neutral Bounded Agent Principal Runtime](0143-provider-neutral-bounded-agent-principal-runtime.md)
- [ADR 0146: Phase 13 External Agent Principal Acceptance and Checkpoint](0146-phase-13-external-agent-principal-acceptance-and-checkpoint.md)
