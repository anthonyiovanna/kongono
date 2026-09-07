# Kongono Model Invocation Agent Bridge

This optional Experimental package maps one eligible normalized model action
suggestion into inert Kongono Agent proposal evidence. It is a trust-reduction
boundary: a model result is not an Agent proposal, and an Agent proposal is not
admission, authorization, a capability grant, a capability invocation, or
proof of an effect.

Applications supply one exact action-to-capability mapping and one exact usage
metric/unit selector. The bridge accepts only a successful, retired,
cleanup-complete, one-attempt Phase 14D result. It preserves the Phase 13
request's principal, operation, intent, constraints, and evidence references,
and it requires the mapped capability identity to be present exactly in the
request's permitted capability list.

Model-generated action arguments remain untrusted normalized result evidence.
Their raw bytes contribute only to a SHA-256-bound canonical evidence digest;
they are never copied into the proposal, capability request, diagnostic,
exception, callable, or example.

Phase 14F's optional
[`kongono/model-invocation-application`](../model-invocation-application/README.md)
workflow is separate. It ends at an exact Phase 14D result and never calls or
depends on this bridge; any later reduction is an explicit caller action.

Phase 14G's optional
[`kongono/model-invocation-inspection`](../model-invocation-inspection/README.md)
is also separate. It may summarize a completed detached runtime result but
does not call this bridge, create proposal evidence, or exercise authority.

The package is synchronous and stateless. It performs no provider or model
invocation, adapter discovery, credential lookup, proposal-port call,
authorization, capability execution, filesystem or network access, registry
lookup, retry, fallback, recording, or background work. Applications may pass
the returned `AgentProposalResult` through their own explicit Phase 13 proposal
port, where all existing current-authority, budget, cancellation, lifecycle,
and cleanup checks remain unchanged.

Kongono remains fully usable without this package, AI, a model provider,
credentials, Chie, Hantei, or Oshie.
