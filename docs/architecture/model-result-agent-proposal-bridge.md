# Model-Result to Agent-Proposal Bridge

## Purpose and context

The optional Experimental `kongono/model-invocation-agent-bridge` package turns
one eligible normalized Phase 14D action-suggestion result into inert Phase 13
proposal evidence. It exists between model invocation and an application-owned
Agent proposal port; it is not itself a port implementation.

A normalized model result is a bounded claim about one model invocation. An
Agent proposal describes one possible capability request for an existing Agent
Principal operation. Neither is admission, authorization, a capability grant,
a capability invocation, or proof that an effect occurred.

## Use when

Use the bridge when an application has already:

- obtained one successful, retired, cleanup-complete, one-attempt Phase 14D
  action-suggestion result;
- constructed the exact Phase 13 `AgentProposalRequest` for the operation;
- selected one exact action-to-`CapabilityRequest` mapping; and
- selected one exact usage metric and unit.

The application may return the resulting `AgentProposalResult` from its own
explicit `AgentProposalPort`. The existing Phase 13 runtime then performs its
normal current-authority, proposal, capability, budget, cancellation,
lifecycle, and cleanup checks.

## Do not use when

Do not use the bridge to invoke a model, discover an adapter, choose credentials,
authenticate a principal, evaluate authorization, grant or invoke a capability,
translate arbitrary action arguments into capability parameters, record
activity, retry work, or prove an external effect.

Text, failed, cancelled, rejected, incomplete, multi-attempt, cleanup-uncertain,
stale, mismatched, unsupported, or missing-result evidence is rejected.

## Inputs

`ModelResultProposalBridge::bridge()` accepts exactly:

- an `AgentProposalRequest` whose principal, operation, intent, constraints,
  evidence references, and permitted capability identities are already bounded;
- a detached `InvocationRuntimeResult`; and
- an `ActionProposalMapping` containing the expected `ActionName`, exact
  application-selected `CapabilityRequest`, and `ProposalUsageSelector`.

The mapped capability identity must occur exactly in the request's permitted
list. There is no alias, prefix, wildcard, parent, compatibility, or default
mapping. Usage selection likewise performs no conversion, summation,
tokenization, estimation, pricing lookup, or fallback.

## Output

The bridge returns one `AgentProposalResult`. Its `ActionProposal` preserves the
request's exact principal, operation, intent, constraint objects, evidence-
reference objects, and ordinal list order. It uses the exact mapped capability
request and a same-name visibility projection.

The result carries the selected token quantity, derived output-byte usage, and
a deterministic evidence digest. Proposal identity is
`model-proposal.<evidence-sha256>`. Provenance is `Model`, names the normalized
result identity, and uses the same digest.

## Action arguments and capability inputs

Model-generated action arguments remain untrusted normalized result evidence.
The bridge hashes their exact bounded JSON bytes and includes their raw byte
length in output usage. It never copies them into the proposal,
`CapabilityRequest`, diagnostic context, exception text, callable, command, or
example. Application-owned capability inputs remain a separate later concern.

## Canonical evidence

Canonical UTF-8 JSON uses schema `kongono.model-agent-proposal-evidence`, version
`1`, fixed key order, integers rather than floats, and no timestamps, random
values, locale, path, machine, process, provider metadata, or ambient state. It
binds result, invocation, and profile identities; action name; argument digest;
capability identity; selector; projected usage; output bytes; and visibility.

The SHA-256 of those exact bytes determines the evidence and proposal identity.
Argument changes therefore change the digest without disclosing argument bytes.

## Side effects and lifecycle

There are no filesystem, network, process, database, provider, adapter, model,
proposal-port, capability-port, authorization, logging, telemetry, or recording
side effects. The bridge is synchronous and stateless and retains no operation,
result, proposal, arguments, exception, or collaborator between calls. Separate
instances, sequential reuse, and nested use therefore share no call state.

## Security and failure

Eligibility and exact mapping fail closed. Visibility projects only Private to
Private, Protected to Protected, and Application to Application. Diagnostics
use the closed range `KGN-MODEL-BRIDGE-1425` through `1428` with bounded Safe
context. Raw identities, arguments, prompts, provider/model labels, credentials,
paths, URLs, and foreign exception messages are excluded from bridge messages
and deterministic exception stringification.

Native trace argument erasure is not claimed. Trusted in-process PHP is not a
sandbox.

## Performance and concurrency

The operation performs bounded linear scans of the permitted capability and
usage lists, one bounded JSON encoding, and SHA-256 hashing. It performs no I/O
or background work. Because it retains no call state, it adds no shared mutable
concurrency mechanism.

## Extension and Agent contracts

Extensions receive no authority or discovery hook from this package. The
bridge does not implement `AgentProposalPort`; it only prepares a value an
application-owned port may choose to return. Agent admission, authorization,
capability execution, receipts, and cleanup remain owned by their existing
Phase 13 mechanisms.

## Stability and related concepts

The bridge and mapping values are Experimental. See
[ADR 0151](../adr/0151-inert-model-result-to-agent-proposal-bridge.md), the
[provider-neutral invocation guide](provider-neutral-model-invocation.md), the
[model invocation runtime](model-invocation-runtime.md), the
[model invocation application workflow](model-invocation-application-workflow.md), the
[model invocation inspection guide](model-invocation-inspection-and-evidence.md), and the
[Agent Principal runtime](agent-principal-runtime.md).

Phase 14F is implemented separately and never calls this bridge automatically.
Phase 14G is a separate read-only evidence leaf and does not call this bridge.
Phase 14H remains planned. Kongono remains fully usable without AI,
a provider, credentials, the bridge, Chie, Hantei, or Oshie. Phase 14H is now
implemented as external evidence only. It neither implements nor invokes an
`AgentProposalPort`, and raw model arguments never become capability input. See
[Phase 14 external acceptance](phase-14-external-acceptance.md).
