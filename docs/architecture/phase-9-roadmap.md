# Phase 9 Roadmap

## Purpose and accepted entry checkpoint

Phase 9 is the Identity and Access Security milestone. It begins from the exact
accepted Phase 8 checkpoint `7b627158285ed6d6a7edf4ab7c4f19c40429bb03`,
tree `8861bf9545d1e20cc538ff6e1ee460f71d380a07`, with exactly thirty-six
acyclic production packages. Phase 9A accepts
[ADR 0105](../adr/0105-explicit-identity-authentication-and-authorization-boundary.md),
[ADR 0106](../adr/0106-fail-closed-security-context-and-policy-separation.md),
the [Identity and Access Security guide](identity-and-access-security.md), and
this fixed roadmap as documentation architecture only.

Phase 9A implements no security runtime. Phase 9B is complete through
[ADR 0107](../adr/0107-neutral-identity-and-access-contracts-and-package-boundary.md):
the neutral `kongono/identity-access` package brings the graph to thirty-seven
acyclic production packages. It implements only immutable vocabulary and
non-disclosing validation diagnostics. Phase 9C is now complete through
[ADR 0108](../adr/0108-deterministic-explicit-authentication-orchestration.md):
the separate provider-neutral `kongono/authentication` package brings the graph
to thirty-eight acyclic production packages and implements exact verifier
selection, attempt cleanup, failure composition, and explicit strength/freshness
evaluation only. Phase 9D is now complete through
[ADR 0109](../adr/0109-deterministic-deny-by-default-authorization-evaluation.md):
the separate `kongono/authorization` package brings the graph to thirty-nine
acyclic production packages and implements exact policy-evidence matching,
evaluator selection, fail-closed result preservation, and bounded receipts only.
Phase 9E is now complete through
[ADR 0110](../adr/0110-operation-scoped-security-context-and-generated-composition-inputs.md):
Composition version 2 carries explicit application/operation input lifetimes,
and the separate `kongono/security-runtime` package brings the graph to forty
acyclic production packages with explicit operation-scoped context construction
and generated lifecycle integration. Phase 9F is now complete through
[ADR 0111](../adr/0111-secure-browser-session-transport-and-capability-enforcement.md):
the separate `kongono/security-http` and `kongono/security-enforcement`
packages bring the graph to forty-two acyclic production packages with exactly
one same-origin HTTPS browser-session profile and exact-Permit protected
capability execution. Phase 9G is complete through
[ADR 0112](../adr/0112-deterministic-security-policy-evidence-and-supported-application-workflow.md):
`kongono/security-policy-build` and `kongono/security-application` bring the
graph to forty-four acyclic production packages with rule-free exact-current
policy identity, one supported request-to-operation-to-enforcement workflow, a
copy-once example, and two read-only security commands. Phase 9H accepts
[ADR 0113](../adr/0113-phase-9-external-security-acceptance-and-checkpoint.md)
and adds only root-owned external acceptance plus the exact local checkpoint.
Phase 9A through 9H are complete at that checkpoint. Phase 10 is unimplemented
and requires separate architect authority.

## Fixed Phase 9 sequence

| Phase | Fixed boundary |
|---|---|
| `9A` | Accept Identity and Access Security doctrine, provider-neutral separation, fail-closed lifecycle/security invariants, and the fixed A-H roadmap. Documentation only. |
| `9B` | Implement the minimal neutral principal, authentication-outcome, authorization-decision, action/resource, and immutable security-context contracts. No authenticator, policy engine, session, HTTP adapter, provider, or application integration. |
| `9C` | Implement deterministic explicit authentication orchestration over caller-supplied evidence and typed verifier adapters, with ambiguity, strength/freshness, lifecycle, and non-disclosure rules. No session transport, authorization engine, HTTP integration, or concrete external provider. |
| `9D` | Implement deterministic deny-by-default authorization evaluation over explicit immutable policy evidence, exact action/resource inputs, and bounded decision receipts. No HTTP/session integration, provider policy language, database enforcement, or ambient guard. |
| `9E` | Implement operation-scoped security-context construction and generated Composition integration with exact cleanup and sequential/reentrant isolation. No HTTP credential transport, login flow, cookie/session profile, or deployment behavior. |
| `9F` | Implement one explicitly selected supported HTTP authentication/session transport profile and protected-capability enforcement integration, including CSRF/origin boundaries where applicable. No second provider/profile, UI, account recovery, administration console, or federation expansion. |
| `9G` | Implement one supported application identity/access workflow, deterministic policy build/currentness evidence, a minimal copy-once security skeleton, and narrowly bounded inspect/check tooling. No autonomous policy, broad identity management product, agent delegation runtime, or deployment manager. |
| `9H` | Externally prove authentication/authorization separation, deny-by-default enforcement, non-disclosure, lifecycle isolation, selected-profile security behavior, policy currentness, regressions, documentation, and the exact Phase 9 checkpoint. |

The table fixes order and boundaries only. It does not implement or authorize a
later letter or Phase 10.

Phase 9G policy evidence identifies exact current inputs and builder bytes but
contains no framework policy rules. Application-owned evaluators retain all
decision meaning, and only exact Permit at the enforcer executes. The supported
workflow uses one operation Security Context, calls the enforcer once, and
closes exactly once. The copy-once skeleton's evaluator and no-session verifier
are examples rather than production providers or framework authority. No broad
identity product, second provider/profile, autonomous policy, agent-delegation
runtime, or deployment manager exists. Phase 9H externally proves the selected
boundaries with synthetic fixtures and fresh temporary application state; it
adds no production behavior or authority.

## Dependency sequence

```text
Phase 8 checkpoint: thirty-six acyclic production packages
    -> 9A doctrine and fixed boundaries
        -> 9B neutral security contracts
            -> 9C explicit authentication orchestration
                -> 9D deterministic authorization evaluation
                    -> 9E operation context and Composition integration
                        -> 9F selected HTTP transport and enforcement profile
                            -> 9G supported application workflow and policy evidence
                                -> 9H external acceptance and checkpoint
```

Neutral contracts remain below transport, provider, integration, workflow, and
executable edges. Specific outer adapters may depend inward; lower packages do
not depend upward. Composition owns construction and generated wiring.

The implemented Phase 9B boundary contains exactly principal kinds and opaque
references, typed authentication outcomes, inert authorization decisions,
canonical action/resource names and references, explicit anonymous or
authenticated Security Context, and package-owned diagnostics/exceptions. It
contains no verifier, policy evaluator, credential, session, HTTP/Composition
integration, provider, enforcement, audit, telemetry, or application workflow.

The implemented Phase 9C boundary contains exact canonical mechanisms, explicit
instants and requirements, unnamed ordinal strength, caller-owned opaque
evidence, validated facts, typed verifier/operation SPIs, and one deterministic
orchestrator. Zero/multiple/unmatched evidence becomes
Absent/Ambiguous/Unsupported without hidden fallback; every begun attempt
verifies once and retires once, and primary plus cleanup failures remain visible.
No credential/provider, session or HTTP transport, authorization evaluator,
policy, Security Context construction, Composition integration, audit,
telemetry, or application workflow exists because of Phase 9C.

The implemented Phase 9D boundary contains exact policy name/version/provenance
identity, opaque policy and environmental evidence SPIs, one explicit immutable
request, exact evaluator registration/selection, one deny-by-default engine,
and a bounded non-authoritative receipt. Mismatch and unsupported policy return
Indeterminate without evaluator execution; exact evaluator results are
preserved and failure never permits. No policy language/build/loader,
roles/permissions, Security Context construction/lifecycle, Composition wiring,
HTTP/session transport, ambient guard, protected-capability enforcement,
database enforcement, audit, telemetry, AI authority, or application workflow
exists because of Phase 9D.

The implemented Phase 9E boundary contains Composition version-2 explicit input
lifetimes, bounded positional operation inputs, and the outer
`kongono/security-runtime` package. Version-1 inputs remain application-lifetime.
Generated application state retains no operation context; exact input
validation precedes lease and construction, and ordinary constructor PHP carries
the same context into one typed operation root. Anonymous and authenticated
entry are explicit, non-success authentication never downgrades, and
authentication success grants no authorization.

The implemented Phase 9F boundary contains exactly the same-origin HTTPS
`browser.session.v1` transport profile with `__Host-kongono_session` and
session-bound `X-Kongono-CSRF` evidence, plus atomic protected-capability
enforcement. Session syntax/emission is not session truth, cryptography,
storage, login, or authorization. Only exact Permit over identical context and
equal Action/Resource proceeds; authentication success, routing, middleware,
and receipts grant no authority. Both packages retain no ambient current-user,
session, context, or decision state. No second profile/provider, policy build/
currentness, workflow, skeleton, inspect/check tool, account product, or Phase
9G behavior exists.

## Fixed invariants

- Identity, authentication, authorization, transport, enforcement, disclosure,
  audit, and telemetry remain separate.
- Authentication success grants no authorization.
- Principals are immutable references; principal kind, roles, sessions, routes,
  middleware, bindings, and discovery grant nothing by themselves.
- Only an exact `Permit` for exact explicit inputs may proceed; every other
  state fails closed.
- Enforcement occurs at the protected capability before protected reads or
  effects.
- Security context is immutable, explicit, operation-scoped, and fully released
  at cleanup; no ambient current-user state exists.
- Credentials and cryptographic evidence remain opaque, bounded, and
  nondisclosing; Kongono invents no primitive.
- Policy truth is deterministic, versioned, provenance-bound, exact-current,
  and validated before runtime.
- Diagnostics, receipts, audit, and telemetry are bounded evidence, never
  authority.
- Runtime magic, service location, policy scanning, remote policy loading, AI
  authority, and silent downgrade are rejected.

## Correction-suffix rule

If an accepted letter needs a narrowly authenticated correction, an ordinal
suffix may repair only that letter's named boundary. It does not create a new
roadmap letter, reorder the sequence, authorize later work, or rewrite
historical directives, reports, or checkpoints.

## Related authorities

- [ADR 0105: Explicit Identity, Authentication, and Authorization Boundary](../adr/0105-explicit-identity-authentication-and-authorization-boundary.md)
- [ADR 0106: Fail-Closed Security Context and Policy Separation](../adr/0106-fail-closed-security-context-and-policy-separation.md)
- [ADR 0107: Neutral Identity and Access Contracts and Package Boundary](../adr/0107-neutral-identity-and-access-contracts-and-package-boundary.md)
- [ADR 0108: Deterministic Explicit Authentication Orchestration](../adr/0108-deterministic-explicit-authentication-orchestration.md)
- [ADR 0109: Deterministic Deny-by-Default Authorization Evaluation](../adr/0109-deterministic-deny-by-default-authorization-evaluation.md)
- [ADR 0110: Operation-Scoped Security Context and Generated Composition Inputs](../adr/0110-operation-scoped-security-context-and-generated-composition-inputs.md)
- [ADR 0111: Secure Browser-Session Transport and Capability Enforcement](../adr/0111-secure-browser-session-transport-and-capability-enforcement.md)
- [ADR 0112: Deterministic Security Policy Evidence and Supported Application Workflow](../adr/0112-deterministic-security-policy-evidence-and-supported-application-workflow.md)
- [ADR 0113: Phase 9 External Security Acceptance and Checkpoint](../adr/0113-phase-9-external-security-acceptance-and-checkpoint.md)
- [Identity and Access Security](identity-and-access-security.md)
- [Operation-scoped Security Context](operation-scoped-security-context.md)
- [Browser-session security and capability enforcement](browser-session-security-and-capability-enforcement.md)
- [Security application workflow](security-application-workflow.md)
- [Phase 9 external security acceptance](phase-9-external-acceptance.md)
- [Phase 8 roadmap](phase-8-roadmap.md)
- [Runtime model](runtime-model.md)
- [Web Runtime model](web-runtime-model.md)
- [Package layout](../engineering/package-layout.md)
