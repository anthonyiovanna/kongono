# ADR 0141: Deterministic Agent Declaration Artifact

- **Status:** Accepted
- **Date:** 2026-08-31
- **Decision scope:** Phase 13C declaration artifact build and publication only

## Context

ADR 0139 separates Agent Principal identity, declaration, admission,
authorization, capability grants, execution, and receipts. ADR 0140 supplies
inert version-1 contract values but deliberately selects no declaration schema
or artifact mechanism. Phase 13C needs deterministic application-owned
declaration evidence before later admission or authority planning can exist.

Declaration evidence must not become authority by convenience. Installed
packages, files, environment, time, provider/model responses, Graph state,
extension state, and previous artifacts cannot silently determine the current
declaration. A caller must supply the declaration explicitly.

## Decision

Create the opt-in Experimental `kongono/agent-declaration-build` package under
`Kongono\AgentDeclarationBuild\`. It depends only on PHP 8.5, API Metadata,
Diagnostics, and Agent Principal Contract. No existing production package
depends on it. The production topology becomes 65 acyclic packages and 232
local production dependency edges.

The package accepts exactly one `AgentDeclaration` containing an exact
declaration identity, one Agent Principal reference, canonical lists of
capability requests, constraints, and evidence references, provenance, and
visibility. Construction rejects non-lists, wrong types, duplicate keys,
non-binary order, and fixed-bound violations. It neither sorts nor coerces.

The builder emits compact UTF-8 JSON with one terminal LF and fixed insertion
order. The root schema is `kongono.agent-declaration-artifact`, with artifact,
contract, and builder version 1. The declaration projection has its own SHA-256
over its exact canonical bytes. The artifact identity is the lowercase SHA-256
of the complete artifact bytes and is not embedded in the JSON.

Structural validation enforces framing, exact keys and order, scalar and list
types, closed enum values, identities, digests, bounds, uniqueness, canonical
order, and versions. It reconstructs Phase 13B values and rebuilds the entire
artifact byte for byte. Currentness validation compares those validated bytes
only with a fresh build from one declaration supplied explicitly in the same
call.

Publication first revalidates the artifact without filesystem input. It then
admits one explicit existing application-owned ordinary directory, rejecting
ambiguous paths, links, Windows reparse/junction escapes, unsafe ancestors, and
root substitution. The final name is
`kongono-agent-declaration-v1-<artifact_sha256>.json`. Exclusive sibling
temporary creation, complete writes, sync, atomic no-clobber finalization, and
failure cleanup preserve the target. An existing target succeeds only when it
is an ordinary non-link file with exact equal bytes that pass strict validation.

## Authority and security consequences

The artifact is declaration evidence only. It does not authenticate a
Principal, admit an agent, validate external evidence currentness, grant a
capability, enforce a constraint, authorize work, invoke a model/provider,
execute an effect, or issue a receipt. Provenance is not truth and visibility
is not access control.

Diagnostics use only closed Safe violation tokens and optional non-negative
counts, positions, or artifact byte counts. Rejected JSON, identities,
Principal IDs, purpose text, paths, digests, provider/model content,
credentials, secrets, and Protected values do not enter package messages,
contexts, or stringification.

## Rejected alternatives

- **Discover declarations from Composer or directories:** currentness would
  depend on ambient host state.
- **Select the newest artifact:** time and directory order are not authority.
- **Embed the artifact digest:** a complete-byte digest cannot safely include
  itself.
- **Allow overwrite or repair:** publication evidence must remain no-clobber.
- **Treat declaration as admission or grant:** later deterministic evidence is
  required before any authority exists.
- **Add a public loader or command:** Phase 13C owns build, validation, and
  publication only.

## Deferred decisions

Phase 13D admission, authenticated-evidence binding, authority-plan evidence,
authorization, grants, expiry, revocation, delegation and consent validation,
model/provider integration, capability execution, orchestration, workflows,
inspection, stores, and receipts remain unimplemented and require separately
authenticated authority.

## Related decisions

- [ADR 0139: Governed Agent Principals](0139-governed-agent-principals-and-deterministic-authority-boundary.md)
- [ADR 0140: Agent Principal Contract Foundation](0140-neutral-versioned-agent-principal-contract-foundation.md)
- [Agent Declaration Artifact](../architecture/agent-declaration-artifact.md)
- [Agent Principal Platform](../architecture/agent-principal-platform.md)
- [Phase 13 Roadmap](../architecture/phase-13-roadmap.md)
