# Agent Application Workflow

> **Current boundary:** Phase 13F implements one optional exact-current Agent
> Application profile, deterministic generated PHP wiring, explicit build and
> write-free check workflows, one-start loading, and one synchronous application
> operation at a time. It adds no provider, registry, second profile, command,
> retained session, or background work. Phase 13G's separate optional
> inspection leaf does not change this runtime boundary.

## Purpose and context

Agent Application is the supported outer composition leaf above the neutral
Agent Principal contract, declaration artifact, admission plan, and bounded
runtime. It converts exact application-owned evidence into deterministic wiring
and makes collaborator ownership visible without widening any lower package's
authority.

The flow is:

```text
exact declaration artifact + matching admitted plan
                    |
             immutable profile
                    |
       canonical generated PHP bytes
                    |
 contained publication + explicit exact-current load
                    |
           one-start application
                    |
 explicit operation ports -> Agent Runtime -> exact runtime result
```

Every item before Agent Runtime is evidence or lifecycle structure. None is an
authority grant. The application owns external authentication, authorization,
revocation, and the currentness port semantics supplied to the runtime.

## Exact profile contract

Use the profile only when the application has one exact-current declaration
artifact and one admitted decision that matches it completely. Construction
rebuilds the declaration and compares artifact identity and digest, principal,
capability identity/order/requirement, constraints, evidence identity/order,
and admission decisions. Required denied capability and any discrepancy fail
before generated loading or effects.

Do not use this boundary to discover, register, select, default, merge, or rank
profiles. Phase 13F supports one immutable profile form and no registry.

## Generation, publication, and loading

Generation is deterministic for the exact profile and package implementation.
The output is bounded strict PHP with canonical LF-only bytes, no BOM, a fixed
interface/class/method shape, and an implementation digest. It contains only
the public canonical identities needed for reconstruction. Generated identity
does not authenticate a principal, authorize an operation, protect against a
host that can replace trusted code, or prove external effects.

Structural validation checks the generated document on its own. Exact-current
validation separately regenerates it from the caller's current profile and
implementation and requires byte equality. Publication uses a content address
inside one explicit existing application-owned directory; it is atomic,
initial-only, idempotent only for equal bytes, no-clobber, and guarded against
path escape and Windows linked/reparse/alternate-stream paths.

Loading receives one explicit expected path. It snapshots and validates the
bytes, rejects an already-defined generated class, loads the one fixed boundary,
checks its exact values and Reflection shape, then reverifies the file snapshot.
The returned handle starts once. There is no directory scan, newest selection,
fallback, reload, or effect merely from loading.

## Build and check

`build()` reconstructs the exact declaration artifact, binds the profile,
generates and validates the bytes, publishes the content-addressed output,
loads that exact file, and reverifies currentness. `check()` repeats the
identity and byte proof without mutation and fails if that exact output is
absent or changed. Neither operation discovers application configuration.

## Operation ownership and cleanup

Each operation supplies a fresh proposal port, ordinal capability-port list,
authority-currentness port, cancellation port, monotonic clock, operation,
intent, and complete nine-dimensional budget. The application accepts one
synchronous operation at a time; overlapping or reentrant work is rejected
before another proposal or capability effect.

Before a valid runtime request exists, the application owns cleanup. A failure
closes every accepted capability port once in reverse order and the proposal
port last, continuing after cleanup errors. Primary and every cleanup throwable
remain separately inspectable through non-disclosing package exceptions. When
the runtime accepts the request, ownership transfers exactly once: runtime
cleanup closes all accepted capability ports and the proposal port, and the
application never double-closes them.

The exact `AgentRuntimeResult` is returned. Cancellation, currentness denial,
budget exhaustion, capability failure, performed effects, usage, and receipts
keep their Phase 13E meaning. Sequential calls and separate applications share
no mutable operation collaborators, results, active flags, or lifecycle state.
Idle close is terminal and idempotent; active close is refused; a post-close
attempt fails while cleaning newly supplied ports.

## Security and operational limits

Trusted generated and in-process PHP are not sandboxed. Supply only ports whose
external authority and behavior the application has separately governed. The
package owns no provider/model SDK, network, credential, ambient filesystem,
environment, global, registry, resolver, container, automatic wiring,
Reflection discovery, prompt system, retrieval, memory/session store, durable
receipt or audit store, queue, scheduler, worker, retry, delegation, recursive
or parallel agent, command, skeleton, endpoint, marketplace, remote service,
deployment manager, Graph integration, inspection, telemetry integration,
Chie, Hantei, or Oshie behavior.

The package is optional. Phase 13G's separate upper evidence leaf may revalidate
an explicit profile/wiring pair without loading or running this package. The
production graph is 69 acyclic packages and 260 local edges. Phases 13A through
13H are current. Phase 13H adds only root-owned external acceptance and the
exact local checkpoint; application behavior is unchanged. Kongono continues to work without
AI, a provider, an account, or a network.

## Related material

- [ADR 0144](../adr/0144-exact-current-single-profile-agent-application-workflow.md)
- [Agent Principal Platform](agent-principal-platform.md)
- [Agent Principal Runtime](agent-principal-runtime.md)
- [Agent Admission and Authority Plan](agent-admission-and-authority-plan.md)
- [Agent Declaration Artifact](agent-declaration-artifact.md)
- [Agent Principal Inspection and Evidence](agent-principal-inspection-and-evidence.md)
- [Phase 13 External Acceptance](phase-13-external-acceptance.md)
- [Phase 13 Roadmap](phase-13-roadmap.md)
