# ADR 0140: Neutral Versioned Agent Principal Contract Foundation

- **Status:** Accepted
- **Date:** 2026-08-31
- **Decision scope:** Phase 13B neutral contract package only

## Context

ADR 0139 accepts governed application Agent Principals while keeping identity,
authenticated evidence, authorization, delegation, consent, intent, proposals,
capability requests and grants, constraints, results, receipts, and operational
evidence distinct. Phase 13B needs a small typed vocabulary for the inert
records that cross those future boundaries. It must not implement any boundary
that decides or performs an effect.

Provider payloads, prompts, completions, retrieved text, memory, extension
state, Application Graph facts, visibility labels, and developer-tool output
cannot define this contract or expand authority. Kongono must remain usable
without AI, a model provider, an account, or a network.

## Decision

Create the opt-in Experimental `kongono/agent-principal-contract` package under
the `Kongono\AgentPrincipalContract\` namespace. It depends only on PHP 8.5,
API Metadata, Diagnostics, and Identity Access. No existing production package
depends on it. The production topology becomes 64 acyclic packages and 229
local production edges.

The package owns contract version 1 and exactly the following value families:

- an Agent-only wrapper for `PrincipalReference` and an opaque operation
  reference;
- intent identity and bounded inert purpose;
- proposal identity and one canonical action proposal;
- capability identity, bounded inert purpose, Required/Optional requirement,
  and one inert request;
- nine closed non-negative constraint dimensions;
- six closed external-evidence kinds, bounded evidence identity, and exact
  caller-supplied digest;
- six provenance-source kinds with bounded source identity and digest;
- Private, Protected, and Application visibility labels; and
- receipt identity, nine closed outcomes, exact relations, evidence digest,
  provenance, and visibility.

## Exact local validity

Operation and all named identities use 1-128 raw bytes matching
`[a-z][a-z0-9]*(?:[._-][a-z0-9]+)*`. Evidence and provenance digests are
exactly 64 lowercase hexadecimal characters. Purpose text is 1-512 raw bytes,
valid UTF-8, control-free, CR/LF-free, and has no leading or trailing
whitespace. Local construction proves only those closed properties.

An action proposal contains one identity, Agent Principal, operation, intent,
capability request, provenance, and visibility. Its constraint list contains
0-9 values, is unique by constraint kind, and is sorted bytewise by the kind
value. Its evidence list contains 0-32 values, is unique by
`kind:value + ':' + identity:value`, and is sorted bytewise by that key.
Non-list input, wrong types, duplicates, non-canonical order, and excess counts
fail closed.

## Authority and lifecycle consequences

All values are immutable and inert. Construction does not authenticate a
Principal, admit an agent, validate evidence currentness, evaluate policy,
grant a capability, invoke a callable, schedule or meter work, filter data,
execute an action, or create an external fact. Delegation and consent evidence
references do not establish delegation or consent. Authorization evidence does
not authorize anything.

Constraints describe claimed ceilings but do not enforce them. Visibility is
not authorization. A proposal is not executable code or a provider request. A
receipt is a bounded attributable claim rather than reusable authority,
non-repudiation, compliance evidence, durable storage, or perfect truth. Even
`Authorized` and `Performed` remain inert outcomes.

The package owns no operation lifecycle, cancellation, revocation, retirement,
cleanup, retry, background work, state, memory, persistence, network, model,
provider, protocol, Graph, Extension, Composition, Kernel, HTTP, Console,
database, or developer-intelligence behavior.

## Diagnostics and disclosure

Seven Internal diagnostics cover invalid Principal kind, identity, purpose,
evidence, proposal, list limits, and receipt. Context is schema-driven and
contains only a closed Safe `violation` token plus optional non-negative list
count or position. Rejected identifiers, purpose text, digests, Principal IDs,
provider/model content, paths, credentials, and Protected values never enter
messages, contexts, prior-message composition, or stringification. Unknown
violation input becomes `unsupported_violation`.

`ExceptionInterface` is Public so consumers can catch deliberate package
failures. Immutable values, backed enums, and the concrete invalid-value
exception are Experimental. The guard, diagnostic enum, and abstract exception
are Internal. No declaration is Stable.

## Alternatives rejected

- **Use provider or protocol payloads as the internal model.** Rejected because
  outer adapters cannot define Kongono identity or authority.
- **Put authorization or evidence validation in value constructors.** Rejected
  because local grammar is not authenticated or current authority evidence.
- **Expose a tool/capability registry or callable in the request.** Rejected
  because ambient execution authority violates deny-by-default doctrine.
- **Make constraints enforce themselves.** Rejected because enforcement and
  runtime lifecycle require later explicit authority.
- **Treat visibility, delegation, consent, or receipts as grants.** Rejected
  because each is evidence with an independent separately governed boundary.
- **Integrate the package into existing application profiles.** Rejected
  because Phase 13B is opt-in and must not widen existing applications.

## Deferred decisions

Phase 13B does not select an agent declaration schema or artifact, builder,
serializer, validator, loader, publisher, admission algorithm, authority plan,
authentication or authorization evaluator, model/provider interface,
capability port, orchestration runtime, application workflow, inspection
command, store, protocol, sandbox, deployment mechanism, or external acceptance
harness. Phase 13C has not begun and requires separately authenticated
architect authority.

## Related decisions

- [ADR 0008: Agents as Principals](0008-agents-as-principals.md)
- [ADR 0028: Bounded Diagnostic Context and Disclosure](0028-bounded-diagnostic-context-and-disclosure.md)
- [ADR 0031: Neutral API Metadata Ownership](0031-neutral-api-metadata-ownership-and-acyclic-package-dependencies.md)
- [ADR 0107: Neutral Identity and Access Contracts](0107-neutral-identity-and-access-contracts-and-package-boundary.md)
- [ADR 0139: Governed Agent Principals](0139-governed-agent-principals-and-deterministic-authority-boundary.md)
- [Agent Principal Contract Foundation](../architecture/agent-principal-contract-foundation.md)
- [Phase 13 Roadmap](../architecture/phase-13-roadmap.md)
