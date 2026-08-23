# ADR 0063: Read-Only Chie Explanation and Diagnostic-Observation Boundary

- Status: Accepted
- Date: 2026-08-22

## Context

ADR 0060 accepts Kongono Chie as the developer companion whose promise is
"Chie helps you build it." ADR 0061 requires exact source state, semantic claim
classes, bounded provenance, and visible uncertainty. ADR 0062 implements the
deterministic 31-source framework-knowledge artifact that can satisfy those
requirements without AI, a provider, an account, or a network.

Phase 4D needs one useful role runtime without granting the broad authority
implied by conversational assistants, repository agents, or automatic repair.
Retrieved source text is untrusted inert data and cannot authorize tools or
change the role boundary.

## Decision

The acyclic `kongono/chie` package owns the first Chie runtime. It depends only
on API Metadata, Diagnostics, and Knowledge. No existing production package
depends on Chie. Chie accepts an explicit framework root, explicit artifact
bytes, and one closed request. It structurally validates the artifact and then
requires exact current validation against that root before resolving any
answer.

Explanation supports exactly six package-owned topic definitions. Each maps one
token to one source path and one exact level-two heading. Chie returns the
complete contiguous section bytes without paraphrase, completion, translation,
or interpretation and binds the claim to the source reference, relative path,
authority class, source SHA-256, and inclusive line range.

Diagnosis accepts only one syntactically Safe `KGN-...-NNNN` identifier. It
scans the already validated artifact in source and line order using bounded,
literal, case-sensitive token matching. It reads no diagnostic message, trace,
context, log, source file, environment value, or arbitrary workspace content.
The caller-supplied identifier is recorded only as a current workspace
observation; located lines remain accepted-framework-fact claims and do not
prove runtime occurrence.

Responses use the fixed `kongono.chie-response` schema and expose `answered`,
`unsupported`, `unavailable`, `stale`, or `blocked`. Answered and unsupported
responses carry the exact artifact and source-set identities. Artifact failure
states disclose no digest, claim, native message, rejected value, or path.
Phase 4D may emit only accepted framework facts, current workspace
observations, and unresolved uncertainty. Inference, recommendation, and
generated-candidate remain modeled but cannot be emitted.

The command accepts only:

```text
kongono-chie explain <framework-root> <artifact-path> <topic>
kongono-chie diagnose <framework-root> <artifact-path> <diagnostic-id>
```

It reads one ordinary content-addressed artifact through a bounded exact-path
gate. It has no defaults, discovery, prompt, configuration, interaction, cache,
or fallback. Recognized states use canonical stdout; invalid usage and
unexpected internal failure use one fixed safe stderr object.

Chie never generates, publishes, repairs, replaces, or deletes an artifact. It
has no file-write, dependency, Git, process-launch, network, model, provider,
credential, telemetry, retention, deployment, production, or application-
runtime authority. Source instructions are returned only as cited inert bytes.

## Bounds and failure model

Topic tokens and diagnostic identifiers are limited to 64 ASCII bytes. Artifact
bytes are limited to 4,194,304; sections to 32,768; diagnostic lines to 4,096;
diagnostic occurrences to eight; claims to nine; citations per claim and
uncertainty tokens to one; canonical responses to 131,072; and JSON depth to
32. Limit breaches fail closed without truncation or partial success.

Ten `KGN-CHIE-1NNN` diagnostics carry one fixed Safe `violation` field and no
Protected field. Typed exceptions preserve a native cause internally while
safe messages and stringification omit native and rejected data.

## Rejected alternatives

We reject free-form prompting, fuzzy or semantic lookup, ranking, embeddings,
vector storage, RAG, a workspace crawler, error-payload ingestion, ambient
discovery, stale fallback, generated prose, recommendations, patches, auto-fix,
self-certification, Hantei verdicts, and model or provider integration. These
would broaden authority, obscure exact provenance, or prematurely combine
later Phase 4 responsibilities.

## Consequences

Chie is useful but intentionally narrow: it explains six exact documented
topics and locates one supplied Safe diagnostic identifier offline. It does not
converse in free-form language, understand an arbitrary project, recommend a
change, modify code, or prove correctness. Hantei remains planned for Phase 4E,
Oshie for Phase 4F, candidate proposals for Phase 4G, and cross-role acceptance
for Phase 4H. Any provider-neutral intelligence requires later explicit
security, disclosure, provenance, and authority decisions.
