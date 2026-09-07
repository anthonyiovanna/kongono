# Kongono Agent Principal Contract

`kongono/agent-principal-contract` is the opt-in Experimental Phase 13B package
for neutral, versioned Agent Principal values. It depends only on API Metadata,
Diagnostics, Identity Access, and PHP. Existing packages and application
profiles do not depend on it.

Phase 14E's separate optional bridge can construct these inert proposal values
from one eligible normalized model result through trusted application mapping.
That construction does not authenticate, admit, authorize, grant, invoke, or
prove an effect, and raw model arguments do not become capability inputs.

Phase 13C's separate opt-in `kongono/agent-declaration-build` package may
project selected contract values into deterministic declaration evidence. That
artifact does not change this package's authority semantics: construction and
serialization do not authenticate, admit, authorize, grant, or execute.

## What the package contains

- contract version 1;
- an Agent-only wrapper around `IdentityAccess\PrincipalReference` and an
  opaque operation reference;
- bounded intent, proposal, capability-request, constraint, external-evidence,
  provenance, visibility, and receipt values;
- canonical proposal lists with at most 9 unique constraint kinds and 32
  unique evidence references; and
- seven bounded non-disclosing diagnostic outcomes.

Operation, intent, proposal, capability, evidence, provenance-source, and
receipt identities use 1-128 bytes of lowercase ASCII matching
`[a-z][a-z0-9]*(?:[._-][a-z0-9]+)*`. Digests are exactly 64 lowercase
hexadecimal characters. Intent and capability purposes are 1-512 raw bytes of
valid UTF-8 without C0/C1 controls, NUL, CR/LF, or leading/trailing whitespace.

Constraints are inert non-negative integers paired with one of the nine closed
budget dimensions. They do not meter, enforce, schedule, cancel, or authorize
work. Evidence references identify authentication, authorization, delegation,
consent, declaration, or policy evidence outside this package. They do not
load, validate, accept, or make current any external claim.

## Authority boundary

A capability request is never a grant. An `ActionProposal` is inert derived
evidence, not admission, authorization, executable code, a callable, a tool
call, a provider request, or proof that work occurred. `AgentVisibility`
classifies intended visibility without filtering or access control.

An `AgentReceipt` is a bounded attributable claim. `Authorized` and
`Performed` outcomes are not reusable authority, non-repudiation, compliance
evidence, durable storage, or perfect knowledge of an external effect. The
package does not create, store, sign, verify, transmit, or retrieve receipts.

The package performs no authentication, authorization, admission, capability
grant, execution, orchestration, model/provider call, network request,
persistence, discovery, serialization, publication, Graph integration,
Composition integration, Kernel integration, or developer-companion work.

## Stability

`ExceptionInterface` is Public. Contract values, backed enums, and
`InvalidAgentPrincipalContractException` are Experimental. The guard,
diagnostic enum, and abstract base exception are Internal. No API is Stable.

Phase 13C declaration evidence, Phase 13D deterministic admission evidence,
Phase 13E's bounded one-operation runtime, and Phase 13F's exact-current Agent
Application workflow are implemented in separate opt-in packages. None changes
this package's neutral authority semantics. See
[ADR 0140](../../docs/adr/0140-neutral-versioned-agent-principal-contract-foundation.md)
and the
[Agent Principal Contract Foundation guide](../../docs/architecture/agent-principal-contract-foundation.md).
The Phase 13D boundary is documented in
[Agent Admission and Authority Plan](../../docs/architecture/agent-admission-and-authority-plan.md).
The Phase 13E boundary is documented in
[Agent Principal Runtime](../../docs/architecture/agent-principal-runtime.md).
The Phase 13F outer boundary is documented in
[Agent Application Workflow](../../docs/architecture/agent-application-workflow.md).
