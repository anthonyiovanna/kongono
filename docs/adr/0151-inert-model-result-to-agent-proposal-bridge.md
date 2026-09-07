# ADR 0151: Inert Model-Result to Agent-Proposal Bridge

## Status

Accepted for Phase 14E.

## Context

Phase 14D can return a detached, normalized `InvocationRuntimeResult` after one
bounded provider-neutral attempt and deterministic retirement. The result is
model evidence. It is not a Phase 13 Agent proposal, an admission decision, an
authorization decision, a capability grant, a callable, or proof that an
effect occurred.

Applications need an optional way to reduce this evidence into the exact inert
`AgentProposalResult` shape already consumed by an application-owned
`AgentProposalPort`. That reduction must not bypass the Phase 13 runtime or let
model-generated action arguments become capability input. It must be
deterministic, explicit, stateless, non-disclosing, and denied by default.

## Decision

Add the optional Experimental `kongono/model-invocation-agent-bridge` package.
It depends inward only on Agent Principal Contract, Agent Runtime, API Metadata,
Diagnostics, Model Invocation Contract, and Model Invocation Runtime.

The package exposes one stateless `ModelResultProposalBridge::bridge()`
operation plus two immutable application-owned mapping values:

- `ActionProposalMapping` binds one exact expected action name to one exact
  `CapabilityRequest` and one `ProposalUsageSelector`.
- `ProposalUsageSelector` names one exact usage metric and unit. It performs no
  aliasing, conversion, summation, tokenization, estimation, or fallback.

### Exact data flow

1. The caller supplies one existing Phase 13 `AgentProposalRequest`, one
   detached Phase 14D `InvocationRuntimeResult`, and one mapping.
2. The bridge revalidates retired state, cleanup completion, successful outcome,
   one attempt, non-null result, and exact receipt/result identity, invocation,
   profile, usage, and cost relations.
3. It requires action-suggestion mode and action-suggested finish reason.
4. It compares the normalized action name with the mapping exactly.
5. It requires the mapped capability identity to occur exactly in the request's
   permitted-capability list.
6. It selects exactly one usage quantity by metric and unit and derives output
   bytes as the overflow-safe sum of raw action-name and bounded action-argument
   byte lengths.
7. It projects Private, Protected, or Application visibility only to the
   same-named Phase 13 value.
8. It creates canonical UTF-8 JSON evidence, hashes those exact bytes, and
   constructs one inert `ActionProposal` and `AgentProposalResult`.

The bridge does not implement or call `AgentProposalPort`. Applications decide
whether to return its result from their own port. Every Phase 13 current-
authority, proposal, capability, budget, cancellation, lifecycle, and cleanup
check remains in the existing runtime.

## Invariants

- Only successful, retired, cleanup-complete, one-attempt action-suggestion
  results are eligible.
- Action and capability matching are exact and case-sensitive within the lower
  contracts' accepted grammars.
- Principal, operation, intent, constraints, and evidence-reference objects and
  list order are preserved exactly from the request.
- The mapped `CapabilityRequest` is trusted application configuration, not
  model output and not a grant.
- Raw action arguments influence only their SHA-256 and output-byte count. They
  are absent from proposal fields, capability input, diagnostics, exceptions,
  logs, and callables.
- The bridge is synchronous, stateless, and retains no call data.
- Visibility never widens.
- Failures use bounded closed Safe context and do not disclose identities,
  arguments, prompts, credentials, paths, URLs, or foreign exception messages.

## Deterministic identities

Canonical evidence uses schema `kongono.model-agent-proposal-evidence`, version
`1`, and fixed key order. It binds the normalized result, invocation, and
profile identities; action name; SHA-256 of raw bounded action-arguments JSON;
mapped capability identity; usage selector; projected token usage; derived
output-byte usage; and projected visibility.

The evidence digest is lowercase SHA-256 of the exact canonical bytes. Proposal
identity is `model-proposal.` followed by that digest. Proposal provenance is
`Model`, uses the normalized result identity as source identity, and uses the
same evidence digest. These are deterministic evidence identities, not
external truth or authority.

## Trust and authority boundaries

The model result and its action arguments are untrusted inert evidence. The
mapping is explicit application-owned configuration. The request's permitted
capabilities are existing Phase 13 proposal evidence, not grants. The bridge
performs reduction only: it does not authenticate a principal, evaluate
authorization, establish current authority, invoke a proposal or capability
port, or certify a receipt.

There is no provider SDK, adapter discovery, credential source, network client,
filesystem access, service locator, registry, Reflection discovery, retry,
fallback, application workflow, recording, telemetry, or background work.

## Disclosure model

Diagnostic IDs `KGN-MODEL-BRIDGE-1425` through `1428` use only closed Safe
fields such as violation, stage, mode, outcome, attempt count, permitted count,
and usage count. Unknown local vocabulary normalizes to
`unsupported_violation`. Previous throwable identity may be preserved without
including its message in bridge messages or deterministic stringification.

Native traces may still contain language/runtime call information; Phase 14E
does not claim native trace argument erasure.

## Rejected alternatives

- Letting a model result implement or bypass an Agent proposal port would erase
  the application-owned Phase 13 boundary.
- Treating action suggestions as capability calls or grants would confuse
  evidence with authority.
- Copying raw model-generated arguments into an executable payload would make
  untrusted content capability input.
- Inferring capability by name, prefix, alias, parent, wildcard, or default
  would widen authority.
- Selecting a default usage metric, summing entries, converting units, or
  estimating tokens would fabricate evidence.
- Allowing visibility widening would declassify model evidence.
- Invoking a provider or model runtime inside the bridge would combine distinct
  lifecycle and trust boundaries.
- Adding a registry, service locator, Reflection discovery, or fallback would
  introduce ambient selection.
- Automatic authorization or effect execution would bypass Phase 13.
- Coupling Chie, Hantei, or Oshie to runtime authority would violate developer-
  intelligence separation.

## Consequences

Applications gain one optional deterministic reduction boundary and retain all
selection and authority decisions. They must provide explicit mapping and
usage selector values and handle fail-closed rejection. Model arguments cannot
become general capability parameters through this package.

The production graph becomes 73 packages and 275 local acyclic edges. The
bridge adds no third-party dependency and Kongono remains operable without AI,
a provider, credentials, or this package.

## API status

The bridge, mapping values, and typed rejection are Experimental. The package
exception marker is Public. Diagnostics, the abstract base, construction
failure, and internal projection helpers are Internal. Nothing is Stable.

## Forward compatibility

Phase 14F through 14H remain planned and require separately authenticated
authority. This decision does not select an application workflow, provider
adapter, registry, command, recording mechanism, telemetry integration,
deployment surface, or production-readiness contract.
