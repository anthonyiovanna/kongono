# ADR 0061: Versioned Knowledge-Source and Provenance Contract

## Status

Accepted

## Date

2026-08-22

## Context

Kongono Chie, Kongono Hantei, and Kongono Oshie must eventually reason from one
inspectable knowledge foundation. A collection of files is not a knowledge
contract: policy, implementation, current explanation, historical evidence,
workspace observations, and generated material have different authority and
can disagree without any one of them becoming silently preferable.

Phase 4B therefore accepts architecture semantics for exact source state,
provenance, conflicts, citations, disclosure, and deterministic derivation. It
implements no machine schema, package, artifact, source inventory, digest,
command, generator, validator, model, retrieval system, workspace reader, or
developer-intelligence runtime.

## Decision

Every later knowledge use must declare the semantic authority of its sources,
bind claims to an exact immutable source state, preserve bounded provenance,
classify the claim, and expose unresolved conflict, staleness, incompleteness,
or version mismatch. Derived material is never independent authority.

Accepted framework policy remains distinct from evidence of current bytes.
Framework knowledge remains distinct from private workspace observations. The
deterministic framework and its accepted sources remain usable without AI, a
provider, an account, a network, or a generated artifact.

## Source authority classes

The following six classes are semantic architecture concepts. Phase 4B does
not define an enum, schema row, machine spelling, or exhaustive source list.

### Governing authority

Accepted ADRs, locked doctrine, security policy, contributor and agent policy,
and other explicitly accepted decisions govern what Kongono authorizes or
requires within their stated scope.

- A pathname, directory, date, or filename number does not confer authority.
- A later source supersedes an earlier governing decision only when accepted
  authority explicitly records the relationship and scope.
- Governing authority describes permitted architecture and policy; it does not
  prove that current source bytes implement them.

### Implementation evidence

Exact source, manifests, lockfiles, tests, fixtures, deterministic metadata,
quality configuration, and runtime probes can prove what an identified source
state contains or does.

- Existing code or passing tests do not authorize architecture.
- A result applies only to its exact source state, environment, command, and
  evidence scope.
- A completion report is not current proof after relevant bytes change.

### Current explanatory documentation

Maintained guides, architecture documents, READMEs, glossary entries, and
examples explain accepted architecture and current behavior.

- They must cite or remain reconcilable with governing authority and
  implementation evidence.
- They cannot silently override either class.
- A contradiction is an explicit documentation or implementation conflict,
  not permission to choose the convenient statement.

### Historical evidence

Checkpoint commits, historical ADR context, directives, reports, release
notes, and archived proof describe a bounded earlier state.

- Historical evidence remains immutable and inspectable.
- It is not automatically current because it once passed.
- Historical behavior and constraints retain their time and source-state
  labels when used in a current discussion.

### Workspace observation

Future application-specific source, configuration, diagnostics, and
deterministic tool observations belong to an explicitly scoped workspace
overlay.

- Workspace observations do not rewrite official Kongono framework truth.
- The workspace source state and matching framework source state are separate
  identities.
- Evidence from one project, user, organization, tenant, or workspace cannot
  leak into another.
- Phase 4B reads, indexes, retains, and transmits no application workspace.

### Derived or advisory material

Knowledge artifacts, model output, generated explanations, recommendations,
candidate patches, issue discussion, external pages, and retrieved text are
never independent authority.

- They preserve provenance to the sources they summarize or transform.
- Repetition, confidence, popularity, recency, or another derived source does
  not elevate them.
- They cannot resolve conflicts or authorize tools, writes, security policy,
  release support, or architecture.

Each included source has one declared semantic role for each use. The exact
machine vocabulary and source inventory remain Phase 4C decisions.

## Source-state identity

A claim is version-matched only when bound to an exact immutable source state.
`current`, `latest`, a branch name, mutable tag, filesystem path, or timestamp
alone is insufficient.

The future machine contract must be capable of identifying, as applicable:

1. the Kongono project or repository origin;
2. an exact release identity when a release exists;
3. exact Git commit and tree identity when Git owns the source;
4. every included tracked modification and untracked input by exact content
   identity for a dirty development state;
5. an explicit included-source inventory and explicit excluded scope;
6. relevant root and package manifest and lockfile identities;
7. the governing knowledge-contract or policy identity;
8. the deterministic derivation implementation identity;
9. environment and tool facts only where a claim depends on them;
10. matching framework and workspace states as separate identities; and
11. disclosure, redaction, conflict, validation, and incompleteness state.

Source-state equality requires exact equality of every semantically included
input and contract identity. Similar versions, compatible ranges, equal test
counts, matching timestamps, or a shared branch do not establish equality.
Identity must be content-verifiable and reproducible. The digest, canonical
serialization, field names, schema, version syntax, and dirty-state manifest
representation remain Phase 4C decisions.

## Provenance and claim classes

Every future claim is classified as exactly one of these semantic classes:

1. accepted framework fact;
2. deterministic observation of an exact source state;
3. inference;
4. recommendation;
5. generated candidate content; or
6. unresolved uncertainty or conflict.

These classes are not a Phase 4B API or enum. Provenance must be capable of
recording, where applicable:

- claim class and scope;
- exact source-state identity;
- originating source and semantic authority class;
- exact content identity and resolvable source location;
- derivation steps and deterministic tool or contract identity;
- inclusion, exclusion, redaction, and disclosure decisions;
- validation evidence with environment and command scope;
- supersession and conflict relationships;
- uncertainty, unsupported state, and incomplete evidence; and
- external origin and usage or licensing constraints if a later decision
  permits external material.

Provenance is bounded evidence. It does not copy a secret or Protected value to
prove that the value was excluded. Generated text cannot cite generated text as
the sole authority for a framework fact; the chain must terminate in source
classes appropriate to the claim.

## Conflict, precedence, and supersession

1. Explicit accepted supersession governs only its declared decision and
   scope.
2. Date, filename order, ADR number, directory, commit recency, model
   confidence, majority vote, and search rank are not implicit tie-breakers.
3. Governing authority answers what is authorized; exact implementation
   evidence answers what identified bytes do. A disagreement remains visible
   and invalidates affected combined or current-support claims.
4. Passing tests cannot authorize unaccepted architecture, and an Accepted ADR
   cannot prove unimplemented behavior.
5. Current explanatory documentation cannot silently override governing or
   implementation evidence.
6. Historical evidence remains true only for its historical source state and
   requires fresh proof before it is current evidence.
7. Workspace observations cannot alter framework authority, and framework
   generalizations cannot fabricate workspace facts.
8. Derived or advisory material cannot resolve a source conflict by itself.
9. Two unresolved applicable sources in the same authority class invalidate
   the affected claim unless an accepted deterministic rule resolves them.
10. A consumer may present the conflict with exact provenance but cannot
    collapse it into a confident answer.

Conflicts are claim-scoped. An unrelated conflict need not invalidate an entire
future artifact, but Phase 4C must decide the exact invalidation granularity and
representation.

## Citation and version matching

Future citations must:

- identify the exact source state discussed;
- resolve precisely enough to the originating bytes, symbol, decision,
  diagnostic, test, or command evidence;
- distinguish current, historical, workspace, and derived material;
- remain usable without a model;
- avoid mutable `latest` aliases as the sole reference;
- expose missing, stale, inaccessible, and mismatched sources visibly;
- avoid revealing excluded paths, secrets, or Protected values; and
- never imply that citation grants authority.

Repository-owned citations should resolve offline when their source state is
available. An answer spanning multiple Kongono versions identifies each state
and keeps its claims separate; silent cross-version merging is prohibited. The
exact anchor, line, symbol, URI, identifier, and rendering syntax remain open.

## Deterministic snapshot and derivation

A future Phase 4C implementation must satisfy these boundaries:

- input scope is explicit and closed, never ambient home or machine discovery;
- traversal stays within authorized roots and treats symlinks, reparse points,
  alternate streams, path aliases, and escapes fail-closed;
- source counts, individual and total bytes, recursion, and work are bounded by
  later exact limits;
- ordering and duplicate handling are deterministic;
- source text is inert data and is not executed as commands or instructions;
- generation makes no model call and requires no network;
- the same semantically included bytes, contract, and generator produce the
  same semantic artifact identity;
- clock time, host paths, enumeration order, randomness, process identity, and
  cache state cannot change semantic output;
- validation completes before an artifact can be usable;
- failed or partial generation cannot replace a previously valid artifact;
- publication or replacement is atomic at the accepted artifact boundary;
- caches are derived, discardable, and never authority;
- any relevant source-state change makes the prior exact-match artifact stale;
- stale, mixed-version, conflicting, incomplete, tampered, and unverifiable
  artifacts fail visibly; and
- no production request or Kernel operation depends on generation or an
  assistant being available.

Phase 4B chooses no storage, cache, publication, atomic-replacement, or
incremental-generation mechanism.

## Framework knowledge and workspace overlay

The future framework knowledge set is a versioned view derived from one exact
Kongono framework source state. It may describe accepted architecture, current
implementation, APIs, packages, diagnostics, tests, documentation, PHP support,
and history under this authority and provenance contract.

The future workspace evidence overlay is an explicitly authorized,
project-scoped observation layer for one application workspace. It references
but cannot modify one exact framework knowledge state.

- Official framework knowledge and private workspace evidence stay distinct.
- Workspace evidence cannot be published as Kongono framework truth.
- One workspace cannot contaminate another workspace, user, organization,
  tenant, or source state.
- A dirty workspace requires exact included dirty-byte identity.
- Closing or switching workspaces cannot retain ambient project state in a
  persistent process.
- Retention, indexing, network transmission, training, and cross-project
  learning remain disabled without explicit future authority.
- Phase 4B implements neither layer and reads no application workspace.

The exact overlay API, storage, lifecycle, retention, identity, and permission
model remain open.

## Security and disclosure

Future deterministic ingestion and derivation must enforce:

1. allowlisted source roots and containment before reading;
2. no ambient home, credential, environment, cache, vendor, VCS-internal, or
   unrelated project discovery;
3. fail-closed rejection or explicit later policy for links, reparse points,
   alternate streams, device paths, and path escapes;
4. bounded parsing and no execution of source instructions;
5. dependency and repository content treated as untrusted;
6. prompt-injection text retained only as inert data and never as tool
   authority;
7. exclusion of secrets, tokens, credentials, raw rejected values, and
   unrelated source;
8. Safe and Protected disclosure applied before inclusion and before model
   access;
9. Protected context excluded from a general knowledge artifact by default;
10. no external source, network retrieval, upload, or provider transmission in
    deterministic generation;
11. provenance that proves redaction without copying forbidden values;
12. exact project and workspace isolation in persistent processes;
13. no executable generated artifact or automatic code loading;
14. no hidden source-state downgrade, mixed-version merge, or stale fallback
    for availability;
15. visible failure when authority, provenance, disclosure, or validation is
    incomplete; and
16. no production access or request-path dependency.

This decision does not define a general secret scanner, sandbox, malware
scanner, content policy, cryptographic signature scheme, or production trust
boundary.

## Phase 4C handoff

Phase 4C may implement only a deterministic, AI-independent generation and
validation slice under a separately authenticated directive. That directive
must decide the necessary concrete details, including ownership; source
allowlist and inventory; API status; schema and canonical serialization;
digest and source-state representation; artifact form and location; generator,
validator, command, and exit contract; resource bounds; atomic publication and
stale handling; cache policy; citation resolution; any authenticity mechanism;
any separately accepted Graph input; tests, diagnostics, and proof; and any
explicitly justified Composer or tooling changes.

Phase 4C must not add a model, provider, embedding, retrieval engine, Chie,
Hantei, Oshie, workspace mutation, patching, or network-dependent generation.

## Consequences

Future knowledge can distinguish authorization, implementation, explanation,
history, workspace evidence, and derived advice without pretending conflicts do
not exist. Exact state and provenance make stale or mixed claims detectable.
This rigor requires later generation and validation work before any artifact is
usable.

## Rejected alternatives

- One unclassified corpus with equal authority for every file is rejected.
- Newest-file, date, commit, or model-confidence precedence is rejected.
- Generated knowledge as its own authority is rejected.
- Silently preferring code over policy or policy over observed behavior is
  rejected.
- Reusing historical proof as current without exact state matching is rejected.
- Silently merging multiple Kongono versions is rejected.
- Mixing official framework and private workspace knowledge is rejected.
- Ambient filesystem crawling is rejected.
- Executing instructions found in source text is rejected.
- Including Protected context and redacting only after model access is rejected.
- Network- or model-dependent deterministic generation is rejected.
- Keeping stale artifacts current for availability is rejected.
- Selecting a schema or retrieval stack before Phase 4C authority is rejected.

## What remains open

Every concrete Phase 4C choice named in the handoff remains open, as do later
role consumption, workspace access, retrieval, models, providers, network use,
mutation, and production behavior. No later phase begins automatically.

## Related material

- [ADR 0060](0060-human-centered-developer-intelligence-family.md)
- [Knowledge foundation](../architecture/knowledge-foundation.md)
- [Developer intelligence](../architecture/developer-intelligence.md)
- [Phase 4 roadmap](../architecture/phase-4-roadmap.md)
- [Application Graph](../architecture/application-graph.md)
- [Architectural doctrine](../doctrine.md)
- [Security policy](../../SECURITY.md)
- [Documentation standard](../documentation-standard.md)
- [Error handling and disclosure](../engineering/error-handling.md)
- [Testing](../engineering/testing.md)
