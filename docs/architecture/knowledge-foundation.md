# Knowledge Foundation

## Purpose and status

This document is the current architecture entrypoint for Kongono's versioned,
inspectable, AI-independent developer-knowledge foundation. [ADR
0061](../adr/0061-versioned-knowledge-source-and-provenance-contract.md) accepts
the semantic source, state, provenance, conflict, citation, derivation, and
disclosure contract.

Phase 4B remains the accepted semantic architecture. Phase 4C implements its
bounded 31-source deterministic artifact, Phase 4D implements the separate
read-only Chie consumer, Phase 4E implements independent Hantei, Phase 4F
implements cited Oshie teaching, and Phase 4G implements one explicit Chie
documentation candidate. No index, general retrieval system, model, arbitrary
workspace reader, automatic patch application, or production runtime exists.

## Source authority matrix

The six classes are semantic concepts, not implemented enum cases or schema
rows.

| Source class | Answers | Cannot establish by itself |
|---|---|---|
| Governing authority | What accepted policy and architecture authorize or require in scope | That current bytes implement the decision |
| Implementation evidence | What an exact source state contains or does under exact evidence conditions | Architectural permission merely because code exists or tests pass |
| Current explanatory documentation | How accepted architecture and current behavior are explained | A silent override of policy or implementation evidence |
| Historical evidence | What a bounded earlier state and its proof recorded | Current behavior after relevant state changes |
| Workspace observation | What one explicitly scoped application workspace deterministically shows | Official Kongono framework truth or facts about another workspace |
| Derived or advisory material | A transformation, explanation, inference, recommendation, or candidate | Independent authority, conflict resolution, or permission to act |

A path, directory, date, filename number, commit recency, model confidence,
popularity, or repetition does not elevate a source. Each source has one
declared role for each use.

## Exact source-state semantics

Version matching requires an exact immutable source state. A future machine
identity must be capable of including, as applicable:

1. project or repository origin;
2. exact release identity when one exists;
3. exact Git commit and tree;
4. exact content identity for every included tracked modification and
   untracked input in a dirty state;
5. included-source inventory and excluded scope;
6. relevant root and package manifest and lockfile identities;
7. governing knowledge-policy identity;
8. deterministic derivation implementation identity;
9. environment and tool facts only where the claim depends on them;
10. separate framework and workspace identities; and
11. disclosure, redaction, conflict, validation, and incompleteness state.

Equality means exact equality of all semantically included inputs and contract
identities. A branch, mutable tag, path, timestamp, compatible range, equal test
count, or similar version is insufficient. Phase 4C must choose the concrete
identity representation and digest.

## Claim classes

Every future claim is semantically one of:

| Claim class | Meaning |
|---|---|
| Accepted framework fact | Supported by applicable governing authority and version-matched sources |
| Deterministic observation | Directly established for one exact source state and evidence scope |
| Inference | Derived from cited facts or observations but not itself accepted truth |
| Recommendation | Advice requiring human evaluation and applicable authority gates |
| Generated candidate content | Proposed material that remains unaccepted until required review and proof |
| Unresolved uncertainty or conflict | Missing, stale, inaccessible, mismatched, contradictory, incomplete, or unsupported evidence |

These are architecture concepts, not an API or serialized vocabulary.

## Provenance requirements

Future derived claims preserve, where applicable:

- claim class and scope;
- exact source-state identity;
- originating source and authority class;
- exact content identity and resolvable location;
- derivation steps and deterministic tool or contract identity;
- inclusion, exclusion, redaction, and disclosure decisions;
- validation evidence with environment and command scope;
- supersession and conflict relationships;
- uncertainty, unsupported state, and incompleteness; and
- external origin and usage or licensing constraints if external material is
  separately permitted.

The chain terminates in sources suitable for the claim. Generated text cannot
use generated text as the sole authority for a framework fact. Provenance is
bounded and non-secret: it records that forbidden material was excluded without
copying the value.

## Conflict and supersession decisions

| Situation | Required result |
|---|---|
| Accepted authority explicitly supersedes an earlier decision | Apply supersession only to the declared scope |
| Sources differ only by date, number, path, commit recency, confidence, vote, or rank | Do not infer precedence |
| Governing authority and exact implementation evidence disagree | Preserve the conflict; fail affected combined or current-support claims |
| Tests pass for architecture not accepted by governing authority | Record behavior evidence; do not infer authorization |
| An Accepted ADR describes behavior not implemented | Record accepted policy; do not claim current implementation |
| Current explanatory documentation conflicts with governing or implementation evidence | Expose a documentation conflict; do not override silently |
| Historical proof is cited for a changed current state | Keep it historical until fresh exact-state proof exists |
| Workspace observation conflicts with a framework generalization | Preserve the workspace fact and framework authority separately |
| Derived or advisory material proposes a conflict resolution | Treat it as advice, never as the deciding authority |
| Two applicable sources in one authority class remain unresolved | Invalidate the affected claim unless an accepted deterministic rule resolves them |
| A consumer reports an unresolved conflict | Present exact provenance and uncertainty; do not collapse it into confidence |

Conflict invalidation is claim-scoped. Phase 4C must choose the concrete
granularity and representation.

## Citations and multiple versions

A future citation identifies the exact source state; resolves with sufficient
precision to source bytes, symbol, decision, diagnostic, test, or command
evidence; labels current, historical, workspace, and derived material; remains
usable without a model; exposes stale, inaccessible, missing, and mismatched
sources; and avoids leaking excluded paths, secrets, or Protected values.

A mutable `latest` alias is not sufficient. A citation proves traceability, not
authority. Claims spanning multiple Kongono versions keep each exact state and
its claims separate. Silent cross-version merging is prohibited. Concrete
anchor, symbol, URI, and rendering syntax remain open.

## Framework knowledge and workspace overlay

```text
exact Kongono framework source state
              |
      framework knowledge set
              |
              +---- referenced by ---- exact application workspace state
                                      |
                              workspace evidence overlay
```

The framework knowledge set is the future versioned view of accepted
architecture, current implementation, APIs, packages, diagnostics, tests,
documentation, PHP support, and history for one exact framework state.

The workspace evidence overlay is a future explicitly authorized,
project-scoped observation layer. It references but cannot modify the exact
framework state. It cannot be published as framework truth or contaminate
another project, user, organization, tenant, or source state. Dirty bytes are
identified exactly, and persistent processes cannot retain ambient project
state after workspace closure or switching.

Phase 4B implements neither layer and reads no application workspace.
Retention, indexing, network transmission, training, and cross-project
learning remain disabled without future explicit authority.

## Deterministic lifecycle

The accepted conceptual path is deliberately mechanism-neutral:

```text
explicit authorized source set
            |
deterministic bounded snapshot
            |
source-state identity + conflict/disclosure validation
            |
derived versioned knowledge artifact
            |
future Chie / Hantei / Oshie consumers

invalid / conflicting / stale / mixed-version / incomplete
            |
visible unusable state outside the consumer path
```

Input scope is closed and contained within authorized roots. Links, reparse
points, alternate streams, device paths, aliases, and path escapes fail closed
unless a later explicit policy accepts them. Counts, bytes, recursion, and work
are bounded. Ordering and duplicate handling are deterministic. Text remains
inert data and cannot execute instructions.

Generation uses no model or network. Equivalent included bytes, contract, and
generator produce the same semantic identity; time, host paths, enumeration
order, randomness, process identity, and caches cannot change it. Validation
must finish before usability. A failed or partial build cannot replace a valid
artifact, and accepted publication or replacement is atomic. Caches are
discardable derived data. Relevant source changes make the old exact-match
artifact stale. Stale, mixed-version, conflicting, incomplete, tampered, and
unverifiable states fail visibly rather than falling back for availability.

No production request or Kernel operation depends on this lifecycle or on an
assistant.

## Security and disclosure threat model

- Read only from explicit allowlisted roots after containment checks.
- Do not discover ambient home, credentials, environment, caches, vendor,
  VCS-internal state, or unrelated projects.
- Treat repository, dependency, documentation, issue, and retrieved text as
  untrusted data.
- Parse within exact future bounds and never execute embedded commands, code,
  or agent instructions.
- Prompt-injection content remains inert and never expands tool authority.
- Exclude secrets, tokens, credentials, raw rejected values, and unrelated
  source.
- Apply Safe and Protected classification before inclusion and model access.
- Exclude Protected diagnostic context from a general artifact by default.
- Deterministic generation performs no external retrieval, upload, provider
  transmission, or model call.
- Record redaction provenance without retaining forbidden values.
- Isolate exact projects and workspaces in persistent processes.
- Do not automatically load or execute generated artifacts.
- Do not hide source downgrade, stale fallback, or mixed-version merging.
- Fail visibly when authority, provenance, disclosure, or validation is
  incomplete.
- Grant no production access and create no request-path dependency.

Phase 4B does not select a general secret scanner, sandbox, malware scanner,
content policy, signature scheme, or production trust boundary.

## AI-independent and offline behavior

Accepted sources, deterministic mechanisms, citations, and validation remain
usable without AI, a provider, an account, or a network. Future roles may
consume a valid artifact, but cannot make it authoritative, authorize their own
tools, repair conflicts by confidence, or conceal unavailable evidence.

## Current, planned, and deferred

| Concern | Current after Phase 4B | Planned Phase 4C | Deferred or prohibited here |
|---|---|---|---|
| Contract | ADR 0061 semantic authority, state, provenance, conflict, citation, derivation, and disclosure architecture | Concrete deterministic representation under separate authority | Reinterpreting the contract through generated output |
| Artifact | None | One bounded AI-independent generation and validation slice | Model- or network-dependent generation |
| Machine design | None | Explicit ownership, schema, identity, bounds, command, validation, and lifecycle decisions | Premature package, schema, digest, index, or retrieval selection |
| Workspace | No read, index, retention, or transmission | No Phase 4C workspace mutation | Ambient crawling, cross-project learning, or workspace contamination |
| Roles | Names and boundaries only; no runtime | Still no model integration in Phase 4C | Chie, Hantei, Oshie, patching, or production access in Phase 4B/4C |

## Phase 4C open-decision handoff

A separately authenticated Phase 4C directive must select all concrete details
needed for its bounded slice:

- ownership boundary;
- exact source allowlist and included inventory;
- API status;
- schema, fields, vocabularies, identifiers, versioning, and canonical
  serialization;
- digest and source-state manifest representation;
- artifact location, extension, structure, and commit policy;
- generator and validator language, entrypoint, command, and exit contract;
- count, byte, recursion, memory, and time limits;
- atomic publication and stale-artifact mechanism;
- cache and incremental or complete-generation policy;
- citation syntax and resolution;
- any authenticity mechanism;
- any separately accepted Application Graph export input;
- tests, fixtures, diagnostics, and external proof; and
- explicitly justified Composer, autoload, or tooling changes.

Phase 4C cannot add a model, provider, embedding, retrieval engine, role
runtime, workspace mutation, patching, or network-dependent generation.

## Deliberate exclusions

Phase 4B selects no file, package, namespace, type, schema, identifier, digest,
serializer, command, index, cache, storage, retrieval engine, model, provider,
workspace reader, external source, network call, role runtime, mutation,
production access, or Graph extension. The Phase 4 A-H sequence remains
strictly sequential.

## Related material

- [ADR 0061](../adr/0061-versioned-knowledge-source-and-provenance-contract.md)
- [ADR 0060](../adr/0060-human-centered-developer-intelligence-family.md)
- [Developer intelligence](developer-intelligence.md)
- [Phase 4 roadmap](phase-4-roadmap.md)
- [Application Graph](application-graph.md)
- [Architectural doctrine](../doctrine.md)
- [Security policy](../../SECURITY.md)
- [Documentation standard](../documentation-standard.md)
- [Error handling and disclosure](../engineering/error-handling.md)
- [Testing](../engineering/testing.md)

## Implemented bounded artifact slice

Phase 4C implements one exact subset of this Accepted architecture in
`kongono/knowledge`. The canonical inventory contains 31 framework-foundation
sources and admits only governing authority, implementation evidence, and
current explanatory documentation. Exact source bytes, authority labels,
source-set identity, inventory identity, ADR 0061 contract identity, generator
identity, and whole-source citations are embedded in canonical JSON.

Generation and current validation always regenerate completely in memory.
Optional publication creates only an immutable content-addressed initial file
through an atomic no-clobber operation. No generated artifact is checked in.
Historical evidence, workspace observation, and derived or advisory material
remain excluded from this artifact; workspace knowledge, retrieval, models,
Graph input, mutation, and production use remain unimplemented.

Phase 4D's `kongono/chie` package consumes an explicit current artifact without
changing this foundation. It returns exact cited sections for six closed topics
or bounded occurrences of one supplied Safe diagnostic identifier. Those
responses are derived material, not authority, and cannot replace governing
sources or deterministic verification. See [read-only Chie](read-only-chie.md).

Phase 4F adds Oshie as a separate read-only consumer of this foundation. Its exact lessons and exercise answers remain derived teaching bound to governing authority or current explanatory documentation; they cannot establish framework truth. See [read-only Oshie](read-only-oshie.md).
## Phase 4G derived candidate use

The shared Knowledge foundation remains authoritative only according to each
source's accepted authority class. Phase 4G Chie may derive one review candidate
from current explanatory documentation; the candidate cannot elevate itself,
change the source, or become accepted knowledge. Hantei and Oshie consume the
foundation independently and neither certifies nor supplies the unapplied
candidate.

## Phase 4 closure evidence

The Phase 4H external harness proves current artifact/source-set identity across
the implemented roles, direct readability of accepted sources without a role,
offline local behavior, visible missing/stale/invalid states, inert embedded
instructions, and non-disclosure. Synthetic Hantei evidence tests only verdict
evaluation; the checkpoint separately requires real current-repository PASS.
No model, provider, retrieval system, workspace overlay, external transmission,
or authority elevation is introduced. Phase 4 is closed; Phase 5 was not begun.
