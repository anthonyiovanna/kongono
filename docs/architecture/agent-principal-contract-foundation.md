# Agent Principal Contract Foundation

> **Current boundary:** Phase 13B implements the opt-in neutral
> `kongono/agent-principal-contract` value package. Phase 13C now implements the
> separate opt-in [Agent Declaration Artifact](agent-declaration-artifact.md)
> build/validation/publication boundary. Phase 13D adds deterministic
> [Agent Admission and Authority Plan](agent-admission-and-authority-plan.md)
> evidence. Phase 13E adds the provider-neutral
> [Agent Principal Runtime](agent-principal-runtime.md), and Phase 13F adds the
> exact-current [Agent Application Workflow](agent-application-workflow.md).
> Phase 13G adds separate visibility-safe
> [Agent Principal Inspection and Evidence](agent-principal-inspection-and-evidence.md). Authorization
> evaluation and currentness remain application-owned; provider SDKs,
> profile registries, live monitoring, and external acceptance do not exist.

## Purpose and context

The contract foundation gives applications a typed way to carry bounded Agent
Principal intent, proposals, requests, evidence references, constraints,
provenance, visibility, and receipt claims without treating any of them as
authority. It sits above Identity Access and beside future Phase 13 boundaries.
It is not an agent runtime.

The package is optional. Existing Kongono packages, the Kernel, and supported
application profiles do not depend on it. The graph is now 69 acyclic
production packages with 260 local production dependency edges. Kongono still
works without AI, a provider, an account, or a network.

## Dependency direction and stability

```text
agent-principal-contract
  -> identity-access
  -> diagnostics
  -> api-metadata
  -> PHP 8.5
```

There is no dependency on Authentication, Authorization, Security Runtime,
Application Graph, Extensions, Composition, Kernel, Console, HTTP, persistence,
observability, Chie, Hantei, Oshie, a provider SDK, or a protocol package. No
existing package depends on the contract package.

`ExceptionInterface` is Public. All values, backed enums, and
`InvalidAgentPrincipalContractException` are Experimental. The diagnostic enum,
abstract exception, and guard are Internal. Nothing is Stable.

## Common identity grammar

The following values use the same 1-128 raw-byte lowercase ASCII grammar:

```text
[a-z][a-z0-9]*(?:[._-][a-z0-9]+)*
```

- `OperationReference`
- `IntentIdentity`
- `ProposalIdentity`
- `CapabilityIdentity`
- `EvidenceIdentity`
- `ReceiptIdentity`
- the opaque source identity inside `AgentProvenance`

The first byte is a lowercase letter. Separators are `.`, `_`, or `-`, appear
only between non-empty alphanumeric segments, and cannot repeat or terminate a
value. Equality is exact and bytewise.

Every digest is exactly 64 lowercase hexadecimal characters. The package does
not hash input, load referenced bytes, validate signatures, establish
currentness, or accept the claim named by a digest.

## Agent Principal and operation references

`AgentPrincipalReference` wraps one existing Identity Access
`PrincipalReference`. Only `PrincipalKind::Agent` is locally admissible. The
exact reference remains accessible and equality delegates to the wrapped
reference. This check proves a kind label only; it does not authenticate the
Principal or grant authorization, delegation, consent, capability, visibility,
or execution.

`OperationReference` is an opaque token. It does not look up an operation,
Security Context, lifecycle, process, request, or transaction.

## Intent and capability request

`AgentIntent` contains exactly an `IntentIdentity` and `IntentPurpose`.
`CapabilityRequest` contains exactly a `CapabilityIdentity`,
`CapabilityPurpose`, and Required/Optional requirement.

Purpose text is 1-512 raw bytes of valid UTF-8. NUL, all C0/C1 controls,
CR/LF, and leading or trailing Unicode whitespace are rejected. Purpose text is
inert descriptive data: it is not a prompt, system instruction, policy,
authorization decision, grant, tool handle, resolver, or callable.

A required request describes application intent only. It does not require an
authority boundary to permit the request and does not create a capability.

## Constraints

`AgentConstraint` pairs one closed kind with an integer from zero through
`PHP_INT_MAX`:

| Kind | Meaning carried as data |
| --- | --- |
| `step_limit` | maximum claimed steps |
| `duration_milliseconds_limit` | maximum claimed duration |
| `token_limit` | maximum claimed token budget |
| `capability_call_limit` | maximum claimed capability calls |
| `retry_limit` | maximum claimed retries |
| `delegation_depth_limit` | maximum claimed delegation depth |
| `output_bytes_limit` | maximum claimed output bytes |
| `retained_state_bytes_limit` | maximum claimed retained-state bytes |
| `concurrency_limit` | maximum claimed concurrent work |

These values do not count, meter, schedule, retry, cancel, revoke, retire,
clean up, authorize, or enforce anything.

## Evidence, delegation, and consent references

`EvidenceReference` contains one of Authentication, Authorization, Delegation,
Consent, Declaration, or Policy; one bounded identity; and one exact digest. It
points to external caller-supplied evidence without parsing, loading,
validating, or accepting it.

Delegation and consent remain separate. Their references do not establish a
delegation chain, scope, current consent, expiry, or revocation state.
Authorization evidence does not authorize an effect. Authentication evidence
does not authenticate the wrapped Agent Principal.

## Provenance and visibility

`AgentProvenance` records a Caller, Application, Model, Retrieval, Capability,
or Deterministic source classification plus opaque source identity and digest.
It preserves bounded source evidence but is not truth or authority. Model and
retrieval provenance remain explicitly derived/untrusted.

`AgentVisibility` is Private, Protected, or Application. It states intended
visibility only. The enum does not filter data, declassify values, grant access,
or perform authorization.

## Canonical action proposals

An `ActionProposal` contains exactly one proposal identity, Agent Principal,
operation, intent, capability request, provenance, visibility, and two lists.

The constraint list:

- contains 0-9 `AgentConstraint` values;
- is unique by `ConstraintKind::value`; and
- is sorted bytewise by that value.

The evidence list:

- contains 0-32 `EvidenceReference` values;
- is unique by `kind:value + ':' + identity:value`; and
- is sorted bytewise by that key.

Both arrays must be zero-based consecutive lists. Wrong types, duplicates,
non-canonical order, and limit excess fail closed. Accessors return detached
list arrays while preserving the exact admitted immutable objects.

The proposal is inert derived evidence. It is not admission, authorization, a
grant, executable PHP, a callable, a tool call, a provider request, a promise,
or proof that an action occurred.

## Receipt claims

`AgentReceipt` contains a receipt identity, Agent Principal, operation,
proposal identity, outcome, evidence digest, provenance, and visibility. The
closed outcomes are Requested, Proposed, Authorized, Attempted, Performed,
Denied, Failed, Cancelled, and Retired.

A receipt is an attributable claim. `Authorized` is not a reusable Permit and
`Performed` is not perfect knowledge of an external effect. No outcome proves
non-repudiation, compliance, persistence, delivery, current authorization, or
real-world truth. The package does not create, store, sign, verify, transmit,
or retrieve receipts.

## Diagnostics and disclosure

| ID | Case |
| --- | --- |
| `KGN-AGENT-CONTRACT-1301` | invalid non-Agent Principal kind |
| `KGN-AGENT-CONTRACT-1302` | invalid bounded identity or digest grammar |
| `KGN-AGENT-CONTRACT-1303` | invalid purpose text |
| `KGN-AGENT-CONTRACT-1304` | invalid evidence or provenance reference |
| `KGN-AGENT-CONTRACT-1305` | invalid proposal value or list |
| `KGN-AGENT-CONTRACT-1306` | proposal list limit exceeded |
| `KGN-AGENT-CONTRACT-1307` | invalid receipt evidence or relation |

Contexts contain only a closed Safe `violation` token and, when relevant,
non-negative `entry_count` or `entry_position`. Rejected raw input, purpose
text, identities, digests, Principal IDs, provider/model content, paths,
credentials, and Protected values never enter messages, contexts, previous
message composition, or deterministic stringification. Unknown violation
strings become `unsupported_violation`.

## Example without implied authority

Constructing a proposal that requests `document.read`, cites consent evidence,
and carries a step constraint proves only that those values satisfy the local
contract. It does not prove that the consent is current, that the document may
be read, that a capability exists, or that a later caller invoked anything.
Applications must use separately accepted deterministic admission,
authentication, authorization, enforcement, and runtime boundaries when those
boundaries are implemented.

## Current limitations and next boundary

Phase 13B itself includes no declaration source, artifact, builder, serializer,
validator, exact-current check, loader, publisher, admission, authority plan,
policy language, evaluator, model or provider port, capability executor, or
runtime loop. Phase 13C adds the separate declaration artifact boundary but no
public loader, admission, authority plan, application workflow, command,
inspection, memory, persistence, audit store, Graph or Extension integration,
sandbox, remote service, or external acceptance harness.

Phase 13D adds a separate opt-in deterministic admission package, Phase 13E
adds a separate opt-in one-operation runtime over explicit caller-owned ports,
Phase 13F adds a separate exact-current application workflow, and Phase 13G
adds a separate optional upper evidence leaf. None changes this package or
turns its values into execution authority. Phase 13H adds only root-owned
external acceptance and the exact local checkpoint; it does not change this
package or its authority semantics. Phase 13 is closed, and a
roadmap row is not automatic authorization. See
[ADR 0140](../adr/0140-neutral-versioned-agent-principal-contract-foundation.md),
the [Agent Principal Platform](agent-principal-platform.md), the
[Agent Admission and Authority Plan](agent-admission-and-authority-plan.md), the
[Agent Principal Runtime](agent-principal-runtime.md), and the
[Agent Application Workflow](agent-application-workflow.md),
[Agent Principal Inspection and Evidence](agent-principal-inspection-and-evidence.md), and
[Phase 13 External Acceptance](phase-13-external-acceptance.md), and
[Phase 13 Roadmap](phase-13-roadmap.md).
