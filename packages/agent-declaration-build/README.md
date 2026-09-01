# Kongono Agent Declaration Build

`kongono/agent-declaration-build` is the opt-in Experimental Phase 13C package
for building, strictly validating, and safely publishing one explicit
application-owned Agent Principal declaration artifact.

The package accepts only one typed `AgentDeclaration`. It deterministically
projects that declaration into canonical version-1 UTF-8 JSON, exposes the
SHA-256 of both the declaration projection and complete artifact bytes, and
validates currentness only against another declaration supplied explicitly in
the same call. Build and currentness use no filesystem, package discovery,
environment, clock, randomness, model, provider, Graph, or extension state.

Publication revalidates the supplied artifact before filesystem I/O and writes
only to one explicit existing application-owned directory. The filename is the
content address of the complete artifact. Existing equal canonical bytes are
accepted; links, reparse escapes, mismatches, and unsafe paths fail closed.

## Authority boundary

The artifact is declaration evidence only. It does not authenticate a
Principal, admit an agent, grant a capability, enforce a constraint, authorize
work, invoke a model or provider, execute an effect, create a receipt, or prove
external truth. Evidence references and provenance remain inert caller-supplied
claims. Visibility is not access control.

There is no public loader, scanner, discovery mechanism, command, workflow,
state store, runtime integration, provider adapter, admission, or authority-plan
behavior in this package. Phase 13D admission, Phase 13E runtime, and Phase 13F
Agent Application are separate opt-in packages; none changes declaration-
artifact semantics. Kongono remains usable without these packages,
AI, an account, a provider, or a network. See the
[Agent Principal Runtime](../../docs/architecture/agent-principal-runtime.md).
The outer workflow is documented in
[Agent Application Workflow](../../docs/architecture/agent-application-workflow.md).

## Stability

The package exception marker is Public. Declaration values, artifacts,
builder, validator, publisher, and concrete exceptions are Experimental.
Diagnostics, helpers, and the abstract package exception are Internal. No API
is Stable.
