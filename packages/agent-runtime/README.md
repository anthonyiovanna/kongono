# Kongono Agent Runtime

`kongono/agent-runtime` is the opt-in Experimental Phase 13E boundary for one
synchronous, provider-neutral Agent Principal operation. It consumes one exact
declaration, one matching admitted authority plan, and explicit caller-owned
proposal, capability, currentness, cancellation, and monotonic-clock ports.

## Calling contract

Call the stateless `AgentRuntime::run()` once with an `AgentRuntimeRequest`.
Before any possible effect, the runtime rebuilds the supplied declaration
artifact, proves its identity and digest match the admitted plan, and compares
the principal, constraints, evidence references, and every capability decision.
It accepts only an ordinal, unique, at-most-32 capability-port list whose
identities are declared and permitted. A permitted capability may omit a port
until a validated proposal actually selects it.

The proposal port receives only the principal, operation, intent, permitted
capability identities, constraints, and evidence references. It never receives
the authority plan. The runtime validates the returned proposal against the
exact declaration and request, then invokes at most one exactly matching
capability port. A proposal is evidence, not authority.

## Lifecycle, currentness, and cancellation

One call advances through the closed states `Created`, `Proposing`, `Proposed`,
`Invoking`, `Completed`, `Cancelled`, `Failed`, and `Retired` as applicable.
There is no retained session or continuation. The caller-owned currentness port
must affirm the exact plan and operation immediately before proposal and again
immediately before capability invocation.

Cancellation and monotonic time are checked at four fixed checkpoints: before
proposal, after proposal, before capability, and after capability. A negative or
decreasing clock reading fails visibly. Cancellation stops later effects but
does not erase evidence of an already performed capability.

All accepted capability ports receive exactly one close attempt in reverse
ordinal order, including unused ports, followed by exactly one proposal-port
close attempt. Cleanup-only failures and combined operation-plus-cleanup
failures remain distinct. The runtime treats the operation as retired after
cleanup uncertainty and never closes a port twice.

## Budgets and results

The immutable budget carries all nine declaration constraint dimensions:
steps, duration milliseconds, tokens, capability calls, retries, delegation
depth, output bytes, retained-state bytes, and concurrency. Missing declaration
constraints are zero ceilings. The runtime requires concurrency one, performs
no retry or delegation, and retains no operation state after the call. Exact
limits pass; overflow-safe limit-plus-one observations stop the next possible
effect. Usage reports actual counters and elapsed duration as evidence, not
billing truth.

The result outcome is exactly `Performed`, `CapabilityFailed`, `Cancelled`,
`BudgetExceeded`, or `AuthorityDenied`, with a terminal `Retired` state after
successful cleanup. It preserves the validated proposal, optional capability
result, actual usage, and a deterministic ordered subset of existing
`AgentReceipt` values. Receipts are bounded claims, not grants, credentials,
reusable execution tokens, or perfect external truth. A capability's explicit
`Failed` result never becomes `Performed`.

## Security and side effects

The package owns no ambient registry, service locator, container, autowiring,
dynamic lookup, discovery, filesystem, process, network, database, credential,
secret, Git, Graph, Extension, Security Context, environment, randomness,
telemetry, or application workflow authority. Only the explicitly supplied
proposal and selected capability ports may cause effects. Trusted in-process
PHP is not sandboxed; callers must supply appropriately constrained ports and
own their external security guarantees.

Exceptions use fixed non-disclosing diagnostics. Raw proposal content,
capability output, identities, purposes, evidence payloads, credentials,
secrets, and paths are excluded from exception messages and receipt identities.

## Stability and limitations

Concrete runtime values, ports, enums, and operations are Experimental. The
package exception marker is Public. Diagnostics, the base exception, guards,
budget meter, receipt factory, and lifecycle composer are Internal. No API is
Stable.

This package includes no provider SDK or adapter, model protocol, autonomous
loop, queue, scheduler, background worker,
retained session, general orchestration product, retry engine, delegation
engine, sandbox, or external-effect verification. Phase 13F's separate outer
`kongono/agent-application` package supplies one exact-current generated and
explicit workflow without changing this runtime's contract. Phase 13G and
Phase 13H remain planned and require separate authority.

## Related material

- [ADR 0143](../../docs/adr/0143-provider-neutral-bounded-agent-principal-runtime.md)
- [Agent Principal Runtime guide](../../docs/architecture/agent-principal-runtime.md)
- [Agent Admission and Authority Plan](../../docs/architecture/agent-admission-and-authority-plan.md)
- [Agent Declaration Artifact](../../docs/architecture/agent-declaration-artifact.md)
- [Agent Principal Contract Foundation](../../docs/architecture/agent-principal-contract-foundation.md)
- [Agent Application Workflow](../../docs/architecture/agent-application-workflow.md)
