# Provider-Neutral Model Invocation

> **Phase 14G current boundary:** ADR 0150 implements the optional Experimental
> `kongono/model-invocation-runtime` over one exact-current plan and one directly
> supplied identity-matching adapter. ADR 0151 adds only the optional inert
> model-result to Agent-proposal bridge over one eligible result and one exact
> application mapping. ADR 0152 adds only one exact-current single-profile
> application workflow with deterministic inert generated wiring, one directly
> supplied matching adapter, and one terminal Phase 14D operation. ADR 0153
> adds an explicit offline, read-only, stateless inspection leaf over one exact
> supplied profile/wiring pair and optional completed detached runtime evidence.
> The current graph is 75 packages / 289 local edges / acyclic, with 1,358
> production / 686 package-test / 76 root-test PHP files. Phase 14H remains
> planned.

> **Phase 14B checkpoint context:** Phase 14B implements only the optional neutral
> contract vocabulary in [ADR 0148](../adr/0148-neutral-versioned-model-invocation-contract-foundation.md)
> and the [contract guide](model-invocation-contract-foundation.md). The graph is
> 71 packages / 265 local edges / acyclic; PHP inventories are 1,273 production
> / 648 package-test / 76 root-test files. Phase 14C deterministic plan evidence
> is implemented by the optional [plan-artifact package](model-invocation-plan-artifact.md); at that
> checkpoint Phase 14D execution remained planned. No provider SDK, network/model execution,
> credential mechanism, adapter, Agent bridge, registry, serialization,
> authority effect, or runtime enforcement exists in Phase 14B.

## Purpose

This guide explains the Accepted Phase 14A architecture for optional,
provider-neutral model invocation. It describes where future model work belongs,
which concepts must remain separate, and how model evidence can reach the
existing Agent Principal platform without becoming authority.

Phase 14A is documentation only. The guide does not define a PHP API, package,
schema, artifact, command, provider adapter, SDK, transport, endpoint,
credential mechanism, model profile, runtime, or executable Agent bridge in
Phase 14A. Later implemented mechanisms remain behind separately authorized rows in the
[Phase 14 roadmap](phase-14-roadmap.md).

Phase 14E's [inert bridge](model-result-agent-proposal-bridge.md) does not make
model output authoritative. A normalized result remains distinct from an Agent
proposal; the proposal remains distinct from admission, authorization, a
capability grant, a capability invocation, and proof of effect. Trusted
application mapping is separate from untrusted model arguments, and the bridge
does not implement `AgentProposalPort`.

Phase 14F's [application workflow](model-invocation-application-workflow.md)
does not select a provider or adapter. It binds one explicit exact-current
profile to one directly supplied adapter and delegates one terminal operation
to Phase 14D. Generated wiring is inert trusted PHP, not a sandbox, and the
workflow does not call the Phase 14E bridge.

Phase 14G's [inspection leaf](model-invocation-inspection-and-evidence.md)
revalidates exact supplied profile/wiring evidence without loading generated
PHP and optionally summarizes a completed detached runtime result without
replay. Its bounded Graph, Observability, Security Audit, and role-addressed
values are inert evidence, not calls, recording, monitoring, certification,
authority, or proof of live provider or application state.

## Current reality

Phase 13 is closed at checkpoint
`63376c802ac4672c21458b67b2fcadaf8e03a019`. Its runtime accepts one explicit
caller-supplied proposal port and, after exact binding and current-authority
checks, may coordinate at most one explicit capability port. That proposal port
can be implemented without AI. Phase 13 contains no model profile, provider
adapter, provider SDK, credential discovery, network client, model invocation,
or automatic tool execution.

Phase 14A accepts [ADR 0147](../adr/0147-provider-neutral-model-invocation-and-adapter-boundary.md)
and this guide as architecture only. Kongono still performs no provider request.
Phase 14B now implements only the neutral contract vocabulary; no provider,
adapter, plan, invocation, Agent bridge, or authority behavior exists.

## Architectural position

The future invocation platform is optional. It sits above application-owned
configuration and policy and beside the Phase 13 authority flow:

```text
Application policy and configuration
             |
             v
Provider-neutral request and plan
             |
             v
Bounded invocation coordinator
             |
             v
Explicit application-supplied adapter port
             |
             v
Explicit provider transport outside the neutral core
             |
             v
Untrusted response or stream events
             |
             v
Validated normalized model result
             |
             v
Optional inert Agent proposal evidence
             |
             v
Phase 13 admission, current-authority checks, and capability boundary
```

The invocation path and Agent path are not the same lifecycle. An application
may use model invocation without creating an Agent Principal. An Agent
application may supply proposal evidence without invoking a model. If an
application chooses to bridge the two, the normalized result becomes only inert
proposal evidence; Phase 13 remains responsible for deterministic authority.

## Non-authority rule

The following are evidence and grant nothing by themselves:

- a provider or model identifier;
- a profile reference or capability description;
- a prompt, instruction, tool description, retrieved passage, or memory item;
- a provider request, response, stream event, finish reason, safety label, or
  confidence value;
- a tool-call suggestion, proposed action, or generated argument;
- usage, token, latency, cost, retry, or rate-limit evidence;
- a receipt, citation, provenance claim, Graph fact, inspection view, trace,
  audit record, or developer-tool result.

These values cannot authenticate a principal, authorize a capability, expand a
budget, select a credential, override application policy, or execute an effect.
Only separately governed deterministic Kongono or application mechanisms may
make authority decisions.

## Conceptual records

The architecture distinguishes twelve semantic concept groups. The names below
describe responsibilities, not selected class names or serialization keys.

### Provider capability description

This is bounded evidence about behavior an adapter claims it can represent for
an exact neutral contract version. It may cover such concerns as result modes,
streaming, usage evidence, or limits when a future directive authorizes their
representation. It is not live availability, entitlement, account state,
pricing truth, or proof that a model will behave correctly.

Unsupported, unverifiable, stale, ambiguous, or lossy capability mappings fail
visibly. An adapter must not silently drop a neutral requirement.

### Application-owned model profile reference

A profile reference identifies exact application-owned configuration without
containing credentials. It is not a global default, registry entry, provider
object, discovered model, authority grant, or promise that external state is
current. Future currentness evidence must remain caller-relative and explicit.

### Credential reference and supply port

A credential reference is non-secret application configuration that can be
matched to an application-owned supply capability. The supply boundary provides
credential material only to the outer adapter at invocation time. The neutral
core neither knows the material nor discovers its source.

Phase 14A does not choose environment variables, files, vaults, operating-system
stores, cloud identity, tokens, keys, or any other credential mechanism.

### Immutable invocation request

A request captures bounded desired model work and application-supplied options
under a versioned neutral meaning. It contains no authority to perform an
application capability. Request content remains untrusted even when supplied by
a trusted caller because it may include user, retrieved, or generated text.

### Normalized invocation plan

A plan is deterministic coordination evidence derived from one accepted request,
one exact profile, explicit capabilities, application policy, and explicit
budgets. It is not a transport attempt and does not prove external provider
currentness. Phase 14A defines no build artifact or plan schema.

### Transport attempt

A transport attempt is one distinguishable interaction requested through an
application-supplied adapter. Logical invocation identity and attempt identity
remain separate so retry, cost, partial progress, and uncertainty cannot be
hidden. The neutral core does not own sockets, HTTP clients, provider SDKs, or
other transport mechanisms.

### Raw provider response or event

Raw provider material is adapter-boundary input. It is untrusted, potentially
malformed, incomplete, oversized, duplicated, reordered, provider-specific, or
hostile. It is never placed directly into authorization, diagnostics, Graph
evidence, logs, metrics, audit context, or receipts.

### Validated normalized model result

A normalized result is a bounded provider-neutral representation that has
passed the exact structural, lifecycle, size, and compatibility checks selected
by a future contract. Validation proves only those checks. It does not prove
truth, safety, intent, originality, policy compliance, authority, or successful
application effect.

### Proposed action or tool-call suggestion

A model may suggest an action and bounded arguments. The suggestion remains
inert. An application must map it deliberately into its own typed proposal
boundary, and Phase 13 must separately admit and authorize any capability. An
unknown, ambiguous, undeclared, malformed, or unpermitted suggestion is denied
without execution.

### Usage and cost evidence

Usage and cost values are bounded claims from application or provider evidence.
They may be incomplete, delayed, estimated, rounded, externally revised, or
absent. They support budgets and operations but are not perfect billing truth.
Applications own any pricing source and account reconciliation.

### Invocation receipt

A receipt summarizes bounded claims about one completed or failed invocation and
its attempts. It is not a capability token, replay token, provider acceptance,
delivery guarantee, billing record, security certification, or proof that a
model result was correct.

### Cleanup evidence

Cleanup evidence records bounded claims about adapter, transport, stream, and
operation retirement. It preserves failure and uncertainty rather than claiming
resource release that was not observed. It does not grant permission to retry.

## Application responsibilities

An application owns:

- whether model invocation is allowed for the operation;
- the exact model profile reference and its currentness evidence;
- the adapter instance and the authority to use its transport;
- credential policy and the narrow credential-supply capability;
- input eligibility, content policy, disclosure classification, and retention;
- all accepted budgets, deadlines, cancellation, retry, and fallback policy;
- whether a normalized result may be converted into Agent proposal evidence;
- any later Phase 13 admission, authorization, capability mapping, and effect;
- provider-specific support, account, pricing, compliance, and deployment claims.

Kongono does not infer these responsibilities from installation, dependency
presence, configuration conventions, environment variables, principal kind,
model content, Graph relationships, or extension manifests.

## Adapter responsibilities

A future provider adapter is an explicit outer leaf. It maps between one exact
neutral contract version and provider-specific transport behavior. It must:

- receive one explicit operation context and application-supplied capabilities;
- declare bounded supported mapping evidence;
- reject unsupported or lossy requirements visibly;
- avoid ambient provider, model, credential, region, endpoint, retry, fallback,
  telemetry, and state selection;
- keep provider-specific types outside the neutral contract;
- validate and bound raw provider material before normalization;
- report attempts, usage, cost, terminal state, and cleanup evidence honestly;
- release owned resources deterministically and preserve uncertainty;
- retain no operation state across sequential invocations unless a later
  explicitly authorized lifecycle contract says otherwise.

An adapter is trusted executable application input, not a sandbox. Supplying an
adapter does not make provider content trustworthy or grant application effects.

## Credentials and secret handling

Credential material is outside profiles and deterministic artifacts. It is also
excluded from:

- requests, plans, normalized results, proposed actions, receipts, and cleanup
  evidence;
- Application Graph nodes or edges;
- diagnostics, exception messages, stringification, and remediation text;
- traces, metrics, events, security-audit context, and inspection views;
- generated code and publication paths;
- Chie, Hantei, Oshie, prompts, retrieved content, and model output.

The narrow supply boundary should minimize credential lifetime and scope. A
future adapter may receive only the credential material needed for the exact
attempt. Cleanup must release references promptly. The architecture does not
claim memory erasure, native trace sanitization, or provider-side deletion.

## Untrusted inputs and prompt injection

Model-visible content crosses a trust boundary. Any part of it may attempt to
reframe data as instructions, request secrets, claim authority, change tools,
disable safeguards, enlarge budgets, or redirect transport. Kongono treats
those attempts as inert content.

Trusted configuration, provider selection, credentials, capability grants,
budget policy, and Phase 13 authority evidence stay out of the instruction
channel. Model content cannot write to those structures. A tool description is
descriptive evidence only; it is not a callable, resolver, service locator, or
grant.

Applications must validate normalized output against their own typed and
purpose-bounded proposal contract. Free-form text does not become executable by
naming a command, path, URL, tool, package, provider, or capability.

## Hidden reasoning

The platform neither requires nor treats hidden provider reasoning as a neutral
contract field. It must not claim to capture, inspect, validate, replay,
authorize from, or disclose hidden reasoning. Explanations explicitly returned
as ordinary model content remain untrusted visible output subject to the same
bounds and disclosure rules as other result content.

## Lifecycle model

Future runtime work must specify exact states and transitions. The accepted
direction includes these conceptual stages:

1. accept explicit current inputs and collaborators;
2. validate request, profile evidence, adapter capabilities, and budgets;
3. form a normalized plan without transport effects;
4. acquire the exact application-supplied adapter and narrow credential supply;
5. begin a distinguishable transport attempt;
6. validate bounded response or stream events;
7. finalize one normalized terminal result or explicit failure;
8. record bounded usage, receipt, and cleanup claims;
9. retire operation state before returning or throwing.

Cancellation and monotonic time checks must be placed explicitly in a future
runtime contract. There is no ambient current operation. An operation cannot be
reused after terminal success, failure, cancellation, or uncertain cleanup.

## Budgets

The architecture requires explicit finite budgets, with exact categories fixed
by later contract work. Applicable categories include:

- accepted input and instruction size;
- output and accumulated stream size;
- event count and buffering;
- wall or monotonic duration;
- transport attempt count;
- usage and cost ceilings;
- retained evidence size;
- diagnostic and receipt size;
- cleanup work.

A budget is a ceiling, not a target or grant. Provider-reported usage cannot
retroactively make an exceeded budget acceptable. If precise enforcement is
impossible before an external effect, the resulting uncertainty must be stated
and bounded rather than concealed.

## Streaming and backpressure

Streaming introduces a longer-lived lifecycle and partial progress. A future
stream boundary must make these concerns explicit:

- event ordering, duplication, and provider-specific event mapping;
- bounded buffering and consumer backpressure;
- cancellation while waiting, receiving, validating, or finalizing;
- cumulative content, event, usage, time, and cost limits;
- a single terminal outcome and the treatment of late events;
- partial-content disclosure and retention;
- transport and adapter cleanup after every terminal path.

Partial events never authorize tools or become a completed normalized result.
An application may present partial content only through a separately governed
presentation boundary; presentation does not change authority.

## Retry, rate limits, and fallback

Provider SDK defaults cannot own retry. If future work authorizes retry, the
application supplies an explicit bounded policy that addresses:

- which failures are eligible;
- whether the operation is idempotent;
- maximum total attempts;
- explicit backoff and monotonic time;
- cancellation and deadline precedence;
- provider rate-limit evidence;
- cumulative input, output, usage, and cost;
- uncertain completion or duplicate provider work;
- per-attempt receipts and cleanup.

Fallback is a separate application policy choice, not a retry detail. Moving to
another model, provider, profile, credential, endpoint, or region can change
semantics, disclosure, cost, jurisdiction, and capability. It is denied unless
later explicitly represented and authorized. Phase 14 includes no automatic
fallback direction.

## Versioning and provider honesty

Neutral versions express Kongono meanings, not vendor release numbers.
Provider capability evidence binds one adapter mapping to exact external facts
known to the application. Model aliases and provider marketing names are not
stable identifiers by assumption.

When an external capability changes, currentness must be re-established by the
application. A prior successful request, test, receipt, or artifact does not
prove current provider behavior. Mapping a richer provider feature into a
narrower neutral result is acceptable only when the neutral contract explicitly
permits the loss and exposes it; otherwise the adapter fails closed.

## Failure and diagnostics

Future failures must distinguish validation, currentness, capability,
credential supply, transport, provider response, streaming, budget,
cancellation, normalization, usage, receipt, and cleanup concerns without
inventing success. Primary and cleanup failures remain separately inspectable.

Diagnostics may expose safe bounded tokens, counts, states, and remediation.
They must not expose raw provider or caller values. Native provider exceptions
may carry unsafe messages or traces; retaining a prior throwable does not make
it safe for every audience.

No diagnostic may recommend weakening validation, changing provider silently,
disabling budgets, logging secrets, revealing prompts, or bypassing Phase 13
authority.

## Operational evidence

Observability, audit, inspection, receipts, and usage evidence answer different
questions:

- observability describes bounded operation behavior;
- security audit records bounded security-relevant claims;
- inspection presents current supplied evidence without observing live provider
  or application state;
- a receipt summarizes the invocation lifecycle;
- usage and cost evidence supports application budgets and reconciliation.

None is authority. Correlation identifiers do not grant access. Export success
does not prove durable storage. Provider receipt evidence does not prove
application effect. Raw model content and secrets are denied from these
channels by default.

## Developer-intelligence separation

Chie, Hantei, and Oshie remain optional developer companions over accepted
version-matched sources:

- Chie may explain or diagnose the accepted model-invocation architecture.
- Hantei may independently verify deterministic evidence once such evidence is
  separately implemented.
- Oshie may teach the accepted distinctions and failure boundaries.

They do not invoke providers, supply credentials, choose models, run adapters,
translate model output into grants, execute tools, repair invocation state, or
certify their own material. Their outputs are inert evidence and cannot expand
the authority of an application Agent Principal.

## Testing direction

Future focused tests should use deterministic explicit fake adapters to prove
neutral behavior without a network, provider account, or credential. Fake
evidence must be labeled synthetic. Tests should cover exact inputs, invalid and
lossy mappings, bounds, cancellation, streaming, retry absence, cleanup,
failure preservation, nondisclosure, sequential isolation, and deterministic
results.

Provider-specific live proof, if ever authorized, belongs outside the neutral
core and must name the exact provider, adapter, external conditions, account
authority, and observed limitations. It cannot convert a provider into a
framework default or a Stable support promise.

Phase 14A adds no tests because it adds no executable behavior. Canonical
repository quality remains the documentation acceptance gate.

## Use when

Use this architecture when an application needs optional model invocation while
keeping provider transport, credentials, policy, and effect authority explicit.
It is also the reference boundary for future neutral contracts, deterministic
planning evidence, bounded runtime coordination, application wiring, Agent
proposal bridging, inspection, and external acceptance.

## Do not use when

Do not use model invocation as:

- authentication, authorization, policy evaluation, capability admission, or
  enforcement;
- a replacement for deterministic application logic;
- an ambient service locator or secret-discovery mechanism;
- an automatic tool executor or autonomous loop;
- a source of Graph, extension, inspection, or developer-tool authority;
- evidence that a provider, model, price, account, or external policy is
  currently supported;
- a requirement for Kongono applications that do not need AI.

## Stability

The architectural boundary is Accepted. Implemented Phase 14B-14G mechanisms
remain Experimental; Phase 14H remains planned. Phase 14A makes no
Stable, 1.0, production-readiness, compatibility, performance, cost, provider,
compliance, or security-certification claim.

## Related sources

- [ADR 0147: Provider-Neutral Model Invocation and Adapter Boundary](../adr/0147-provider-neutral-model-invocation-and-adapter-boundary.md)
- [ADR 0152: Exact-Current Single-Profile Model-Invocation Application Workflow](../adr/0152-exact-current-single-profile-model-invocation-application-workflow.md)
- [Model Invocation Application Workflow](model-invocation-application-workflow.md)
- [Phase 14 roadmap](phase-14-roadmap.md)
- [Agent Principal Platform](agent-principal-platform.md)
- [Agent Principal Runtime](agent-principal-runtime.md)
- [Agent Application Workflow](agent-application-workflow.md)
- [Identity and Access Security](identity-and-access-security.md)
- [Observability and Operational Evidence](observability-and-operational-evidence.md)
- [Developer Intelligence](developer-intelligence.md)
- [Phase 14 External Acceptance](phase-14-external-acceptance.md)

Phase 14A-H are implemented and Phase 14 is closed. The external harness uses
one explicit offline fake adapter as test evidence only; it adds no provider
integration, credential mechanism, network authority, or external effect.
