# Kongono Agent Application

`kongono/agent-application` is the opt-in Experimental Phase 13F boundary for
one exact-current Agent Principal application profile. It binds one current
declaration artifact to one matching admitted authority plan, generates
ordinary deterministic PHP wiring, and starts one application that may run one
synchronous operation at a time through the existing Agent Runtime.

## Profile and generated wiring

`AgentApplicationProfileFactory` accepts one explicit declaration artifact and
one explicit admitted decision. It rebuilds the declaration, proves exact
identity, digest, principal, constraints, evidence, capability order,
requirements, and admission decisions, and derives one immutable profile
identity. Denied or mismatched evidence fails before loading or effects. There
is no profile registry, discovery, default profile, selection policy, or second
profile.

`AgentWiringGenerator` emits bounded, LF-only, no-BOM strict PHP for that exact
profile and the package implementation identity. The generated file contains
only the identifiers needed to reconstruct the fixed boundary; declaration
bytes, authorization content, prompts, provider values, capability data,
credentials, and secrets are excluded. Structural validation and
caller-relative exact-current validation are separate operations. Generated
wiring is derived evidence, not authentication, authorization, integrity
against a hostile host, or an authority grant.

Publication accepts one explicit existing application-owned root and one
contained output directory. It is content-addressed, atomic, initial-only,
idempotent for exact bytes, and no-clobber. Loading accepts one explicit path,
snapshots and validates its bytes, requires only the fixed generated boundary,
and reverifies the file before returning a one-start handle. It never scans,
selects the newest file, or performs a proposal or capability effect merely by
loading PHP. Trusted generated and in-process PHP are not sandboxed.

## Build and check workflow

`AgentApplicationBuildWorkflow::build()` rebuilds the exact declaration
artifact, creates the profile, generates and validates the wiring, publishes
it, loads the explicit output, and reverifies currentness before returning the
current build. `check()` performs the same exact-current proof without writing
and fails when the expected content-addressed output is absent or changed.

The declaration artifact and authority plan remain inert evidence. External
authentication, authorization, revocation, and current-authority semantics
remain application-owned. Phase 13E rechecks caller-supplied currentness at its
fixed checkpoints immediately before possible proposal and capability effects.

## Application lifecycle

Each operation supplies its proposal port, ordinal capability ports,
currentness port, cancellation port, clock, intent, operation identity, and
budget explicitly. Before the runtime accepts the request, the application
owns the proposal and capability ports and closes every accepted capability
port in reverse order, then the proposal port, on failure. After request
acceptance, cleanup ownership transfers exactly once to Agent Runtime; the
application never closes runtime-owned ports again.

An application permits one active synchronous operation. Reentrant or
overlapping work is rejected before a second effect. Sequential operations are
isolated and the exact `AgentRuntimeResult` and receipt meaning are returned
unchanged. Idle close is terminal and idempotent; active close is refused
without falsely closing the application; post-close operations fail while
their newly supplied ports are still cleaned.

## Security, side effects, and limits

The package owns no provider or model SDK, network, credential, environment,
global registry, resolver, container, Reflection discovery, hot reload,
command, skeleton, marketplace, remote service, deployment manager, memory or
session store, retained operation, queue, worker, scheduler, retry, delegation,
parallel operation, Application Graph, inspection, or developer-tool authority.
Only explicit operation ports may cause effects, and the Agent Runtime remains
the effect coordinator.

Exceptions use the fixed non-disclosing diagnostic range
`KGN-AGENT-APPLICATION-1340` through `KGN-AGENT-APPLICATION-1345`. Original
operation failures and every cleanup failure remain separately inspectable
without exposing rejected input, paths, authorization content, prompts,
provider values, capability data, credentials, or secrets.

Concrete profile, generated-wiring, workflow, application, and exception types
are Experimental. The package exception marker is Public. Diagnostics and
implementation helpers are Internal. No API is Stable.

Phase 13G's separate Agent Inspection upper evidence leaf does not change this
package's runtime or authority boundary. The production graph is now 69 acyclic
packages and 260 local production edges. Phase 13H remains planned. Kongono
remains usable without AI, a provider, an account, or a network.

## Related material

- [ADR 0144](../../docs/adr/0144-exact-current-single-profile-agent-application-workflow.md)
- [Agent Application Workflow](../../docs/architecture/agent-application-workflow.md)
- [Agent Principal Runtime](../../docs/architecture/agent-principal-runtime.md)
- [Agent Admission and Authority Plan](../../docs/architecture/agent-admission-and-authority-plan.md)
- [Agent Declaration Artifact](../../docs/architecture/agent-declaration-artifact.md)
