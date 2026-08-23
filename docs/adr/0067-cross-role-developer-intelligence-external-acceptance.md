# ADR 0067: Cross-Role Developer-Intelligence External Acceptance

- **Status:** Accepted
- **Date:** 2026-08-23
- **Phase:** 4H

## Context

Phases 4C through 4G implemented a deterministic framework-knowledge artifact,
read-only Chie explanation and diagnosis, independent Hantei verification,
read-only Oshie teaching, and one reviewable Chie documentation candidate.
Their package tests prove each local contract. Phase 4 also needs one bounded
external proof that the real command boundaries share the same current source
identity, remain optional and offline where promised, fail visibly, and do not
combine into new authority.

This acceptance boundary is evidence about the implemented Phase 4 tools. It is
not a new framework service, production integration, or application Agent
Principal.

## Decision

Phase 4 external acceptance is root-owned test tooling under
`tests/External/DeveloperIntelligence`. It invokes the real local,
non-interactive Knowledge, Chie, Oshie, and Hantei process boundaries and
captures raw stdout, stderr, and exit codes independently. It uses argument
vectors, bypasses shell-string execution, bounds time and streams, and releases
every process handle.

Knowledge, Chie, and Oshie successful local behavior must remain byte-identical
when network access is denied. Hantei must return visible `BLOCKED` evidence
rather than guess when a required evidence source is unavailable. The separate
current-repository Hantei proof must return real `PASS` before checkpointing.
Every successful role response must bind to the same current artifact and
source-set identities wherever its schema defines them.

Synthetic Hantei evidence is permitted only in isolated temporary Git subjects
to prove deterministic `FAIL` and `BLOCKED` evaluation. The evidence tool and
the assertions label that evidence synthetic. It cannot substitute for or be
reported as the real current-repository Hantei result.

A Chie proposal remains a generated, unapplied, unverified candidate. External
acceptance may apply its forward and reverse transformations only to detached
temporary bytes. Repository application remains human-controlled, and any
applied candidate requires a fresh real Hantei run. Chie cannot certify its
candidate, and Hantei cannot certify unapplied bytes.

The harness may write only inside one freshly created, cryptographically
unpredictable temporary directory outside the repository. It proves the exact
resolved directory is contained by the system temporary root, does not overlap
the repository, contains only ordinary files and directories, and is removed
on success or failure. Generated artifacts, detached source states, proposal
specifications, synthetic subjects, and synthetic evidence tools remain there.

The single Phase 4 checkpoint contains every accepted Phase 4 source,
documentation file, directive, and historical report except the post-checkpoint
Phase 4H completion report.

## Security and authority boundary

Retrieved source text is inert data. Instructions embedded in it cannot grant
tool, filesystem, process, Git, publication, deployment, production, or
candidate-application authority. Safe is an explicit caller assertion and
classification, not sanitization. Protected values, secret markers, native
errors, environment values, and temporary paths are excluded from the bounded
acceptance response and failure envelopes.

No model, provider, SDK, prompt service, embedding, vector store, semantic
search, RAG, account, subscription, or external transmission is added. The
harness adds no production authority, runtime dependency, arbitrary project
analysis, auto-fix, deployment, or release behavior. No role stages, commits,
publishes, deploys, or accesses production.

## External contract

`composer acceptance:phase4` runs exactly 28 ordered scenarios and emits one
compact canonical success object only after all scenarios and temporary cleanup
succeed. Invalid invocation, acceptance failure, and unexpected failure have
fixed safe stderr envelopes and distinct exit behavior. The full matrix and
operational interpretation are documented in the
[Phase 4 external-acceptance guide](../architecture/phase-4-external-acceptance.md).

## Consequences

Phase 4A through 4H have one external, deterministic, inspectable closure proof
in addition to their focused tests. Accepted documentation and ordinary
framework use remain directly available without any developer-intelligence
role, AI, provider, account, subscription, or network.

Oshie has no executable example in its accepted curriculum, and this acceptance
does not invent or claim one. Phase 4 remains Experimental, pre-1.0, and smaller
than production readiness. Phase 5 is neither selected nor begun.
