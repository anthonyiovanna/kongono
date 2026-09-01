# Agent Declaration Artifact

> **Current boundary:** Phase 13C implements only deterministic build, strict
> typed validation, explicit caller-relative currentness, and atomic
> content-addressed no-clobber publication for one application-owned Agent
> declaration. Phase 13D separately implements deterministic
> [Agent Admission and Authority Plan](agent-admission-and-authority-plan.md)
> evidence, and Phase 13E implements the separate provider-neutral
> [Agent Principal Runtime](agent-principal-runtime.md), without changing this
> artifact's authority semantics. Phase 13F's separate
> [Agent Application Workflow](agent-application-workflow.md) binds one exact
> artifact but does not turn it into authority.

## Purpose and context

`kongono/agent-declaration-build` turns one explicit Phase 13B declaration
value into inspectable deterministic evidence. Applications use it when they
need stable bytes and identities for a declaration before any separately
governed admission or authorization step. The package is optional and sits
above Agent Principal Contract; existing packages do not depend on it.

Do not use the artifact as authentication, admission, authorization, a
capability grant, executable configuration, a provider request, a workflow,
or a receipt. Construction and validation prove only local shape, exact bytes,
and equality with an explicit caller-supplied value.

## Declaration contract

An `AgentDeclaration` contains:

1. one lowercase bounded declaration identity;
2. one Agent-kind Principal reference;
3. zero to 32 unique capability requests in bytewise identity order;
4. zero to nine unique constraints in bytewise kind order;
5. zero to 32 unique evidence references ordered by kind and identity;
6. one provenance value; and
7. one visibility value.

Input lists are copied after validation. The package rejects rather than sorts,
deduplicates, coerces, or silently drops values. Capability requests remain
requests, constraints remain inert claims, evidence references do not establish
their claims, provenance is not truth, and visibility is not access control.

## Canonical artifact

The complete root key order is `schema`, `artifact_version`,
`contract_version`, `builder`, `declaration`, and `declaration_sha256`. JSON is
compact UTF-8 with unescaped slashes and Unicode and exactly one terminal LF.
Floats, unsupported values, invalid UTF-8, non-list lists, unknown or reordered
keys, and artifacts over 131,072 bytes fail closed.

`declaration_sha256` identifies the exact canonical declaration object bytes,
including their terminal LF. `artifactSha256()` identifies the exact complete
artifact bytes. The complete digest is not embedded in its own input. Repeated
builds from identical typed values are byte-identical without consulting files,
packages, environment, clocks, randomness, services, models, providers, Graph,
or extensions.

## Validation and currentness

Structural validation checks framing before decode, exact object shapes and
key order, scalar types, list form and bounds, closed enum values, identity and
digest grammar, uniqueness, and canonical order. It reconstructs Phase 13B
values, recomputes the declaration digest, and rebuilds the complete artifact.
Only byte-for-byte equality succeeds.

Currentness first performs structural validation. It then builds from the one
current declaration supplied explicitly by the caller and compares complete
bytes. It does not read a path, load configuration, scan packages, select a
file, or consult runtime state.

## Publication and failure

Publication accepts one explicit existing application-owned directory. It
revalidates the artifact before filesystem I/O. Path admission rejects empty or
control input, relative or ambiguous paths, root substitution, symlinks, and
Windows reparse/junction escapes through any ancestor.

The final content-addressed filename is
`kongono-agent-declaration-v1-<artifact_sha256>.json`. A sibling temporary file
is opened exclusively, completely written and synchronized, atomically linked
without overwrite, and removed on every path. Equal existing canonical bytes
are idempotent; unequal, unreadable, changing, linked, or invalid targets fail
closed. The package has no public loader, scan, glob, newest-file selection,
repair, delete, update, or overwrite operation.

Failures use diagnostic IDs `KGN-AGENT-DECLARATION-1310` through `1314` with a
closed Safe violation vocabulary and optional non-negative counts, positions,
or byte counts. Raw rejected values and paths are not disclosed.

## Stability and relationships

The package is **Experimental** and Kongono remains pre-1.0. Its exception
marker is Public solely for deliberate failure handling. It adds no Graph,
Extension, Security, Authorization, Composition, Kernel, HTTP, Console,
Persistence, Database, Observability, Chie, Hantei, Oshie, provider, model, or
runtime integration.

The separate Phase 13G inspection leaf consumes only accepted bounded evidence
and does not change declaration semantics or expose raw declaration content.
The graph is 69 acyclic production packages with 260 local production edges.
Phase 13H adds only root-owned external acceptance and the exact local
checkpoint. It does not change declaration construction, validation,
publication, currentness, or authority. Phase 13 is closed.

## Related material

- [ADR 0141](../adr/0141-deterministic-agent-declaration-artifact.md)
- [Agent Principal Contract Foundation](agent-principal-contract-foundation.md)
- [Agent Admission and Authority Plan](agent-admission-and-authority-plan.md)
- [Agent Principal Runtime](agent-principal-runtime.md)
- [Agent Application Workflow](agent-application-workflow.md)
- [Agent Principal Inspection and Evidence](agent-principal-inspection-and-evidence.md)
- [Phase 13 External Acceptance](phase-13-external-acceptance.md)
- [Agent Principal Platform](agent-principal-platform.md)
- [Phase 13 Roadmap](phase-13-roadmap.md)
