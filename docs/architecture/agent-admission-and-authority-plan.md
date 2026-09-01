# Agent Admission and Authority Plan

> **Current boundary:** Phase 13D implements one opt-in deterministic,
> application-owned admission boundary. It validates one exact current
> declaration artifact and reconciles it with explicit completed authentication
> and authorization evidence. Its output is inert evidence, not a grant,
> enforcement mechanism, execution token, model request, or tool effect.

## Purpose and context

`kongono/agent-admission` sits above the Phase 13B neutral contracts and Phase
13C declaration artifact. Use it when an application has already authenticated
an Agent Principal and already evaluated authorization for an explicit ordered
set of requested capabilities, and needs deterministic evidence of whether that
exact declaration can be admitted.

Do not use it to authenticate credentials, evaluate policies, discover tools,
create a Security Context, enforce budgets, invoke a provider, execute a
capability, or persist authority. Those responsibilities are outside Phase 13D.

## Input and calling contract

The caller supplies one immutable `AgentAdmissionRequest` with:

1. one typed declaration and the exact artifact bytes claimed for it;
2. one completed authentication outcome and bounded fact references;
3. an ordered, unique requested-capability list; and
4. ordered, unique `CapabilityAuthorizationEvidence` records for requested
   capabilities.

The artifact must pass structural validation and exact caller-relative
currentness against the supplied declaration. No directory, registry, command,
environment, process, credential store, network service, policy engine, or
ambient current principal is consulted.

Authentication and authorization evaluation remain application-owned. The
application is responsible for credential validation, policy selection, receipt
currentness, expiry, revocation, delegation, consent, and external truth. The
package consumes completed neutral outcomes; it does not call an authentication
or authorization engine.

## Deterministic reconciliation

Declared capability order is authoritative for declared decisions. Requested
undeclared capabilities follow in caller order so every deny reason remains
visible and deterministic.

| Condition | Capability status | Admission effect |
| --- | --- | --- |
| Declared and requested with `Permit` evidence | `Permitted` | Effective only if identity also authenticates exactly |
| Declared but not requested | `NotRequested` | Denies admission only when the declaration marks it required |
| Requested with no receipt | `MissingEvidence` | Deny |
| Requested with `Deny` receipt | `Denied` | Deny |
| Requested with `Indeterminate` receipt | `Indeterminate` | Deny |
| Requested but undeclared | `Undeclared` | Deny |

Required and optional declaration semantics remain distinct. Every required
capability must be requested and permitted. Optional capabilities may be
omitted. An optional-only declaration with an empty request is admitted with no
effective permitted capability.

Principal identity is exact: a successful authentication outcome must name the
same Agent Principal declared by the artifact. Agent kind membership alone is
not authentication.

## Output and identity

`AgentAdmissionDecision` reports ordinary admit or deny results without turning
them into exceptions. Its `AgentAuthorityPlan` contains immutable capability
decisions and preserves canonical declaration constraints and evidence
references. `AgentAdmissionIdentity` derives deterministic canonical JSON bytes
and a lowercase SHA-256 digest from the exact declaration identity, principal,
authentication outcome and facts, requested list, complete authorization
receipt fields, decisions, constraints, and evidence references.

This identity binds evidence; it does not accept the evidence as external truth.
It cannot be called, resolved, exchanged, or reused as authorization. The plan
contains no handler, callable, executable token, service locator, engine,
evaluator, tool registry, or credential.

## Failure and disclosure

Malformed input, invalid or non-current artifacts, inconsistent evidence, and
canonicalization failures use `KGN-AGENT-ADMISSION-1320` through `1323`.
Diagnostic context is restricted to closed Safe/Protected metadata. Raw
artifact bytes, rejected values, identities, purposes, policy payloads,
credentials, secrets, and paths are not disclosed. Ordinary authorization deny
outcomes are values, not exceptional failures.

## Side effects, lifecycle, and concurrency

The package has no filesystem, process, network, database, Git, environment,
clock, randomness, publication, model, provider, Graph, Extension, Composition,
Security Context, command, telemetry, or persistence side effect. Objects are
immutable and caller-owned. The package starts no lifecycle and requires no
cleanup. Independent calls share no mutable state.

## Security boundary

Admission evidence is not authorization, a capability grant, constraint
enforcement, or execution. Declaration evidence, visibility, Graph facts,
extension state, prompts, completions, retrieved content, memory, provider
responses, and developer-tool output cannot expand authority. No ambient tool
or container authority exists. An LLM may suggest a request, but it cannot
authenticate itself, produce authoritative policy truth, grant a capability, or
cause an effect through this package.

## Stability and limitations

All concrete Phase 13D values and operations are Experimental; the package
exception interface is Public; internal guards, canonicalization, diagnostics,
and base exception are Internal. No API is Stable.

Phase 13D itself does not validate external evidence currentness, enforce
constraints, execute work, invoke a model, manage budgets or cancellation,
issue operational receipts, or integrate an application runtime. Phase 13E's
separate [Agent Principal Runtime](agent-principal-runtime.md) consumes exact
matching admitted evidence and coordinates one bounded operation over explicit
caller-owned ports; it does not change admission semantics. Phase 13F's separate
[Agent Application Workflow](agent-application-workflow.md) binds one exact
plan into generated and explicit wiring without making it a grant. Phase
13G's separate [Agent Principal Inspection and Evidence](agent-principal-inspection-and-evidence.md)
can count and bind the plan only as Safe evidence; it does not reevaluate or
currentize authorization. Phase 13H adds only root-owned external acceptance
and the exact local checkpoint; it does not change admission or plan semantics.
Phase 13 is closed.

## Related material

- [ADR 0142](../adr/0142-deterministic-agent-admission-and-authority-plan-evidence.md)
- [ADR 0143](../adr/0143-provider-neutral-bounded-agent-principal-runtime.md)
- [Agent Principal Runtime](agent-principal-runtime.md)
- [Agent Application Workflow](agent-application-workflow.md)
- [Agent Principal Inspection and Evidence](agent-principal-inspection-and-evidence.md)
- [Phase 13 External Acceptance](phase-13-external-acceptance.md)
- [Agent Declaration Artifact](agent-declaration-artifact.md)
- [Agent Principal Contract Foundation](agent-principal-contract-foundation.md)
- [Identity and Access Security](identity-and-access-security.md)
- [Phase 13 Roadmap](phase-13-roadmap.md)
