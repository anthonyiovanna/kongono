# ADR 0062: Deterministic Framework-Knowledge Artifact Package and Format

- Status: Accepted
- Date: 2026-08-22

## Context

ADR 0061 separates source authority, claim class, exact source state, and
bounded provenance. Phase 4C needs one concrete implementation slice that is
usable without AI and does not turn derived material into authority.

## Decision

Kongono owns the slice in the acyclic `kongono/knowledge` package. It depends
only on API Metadata and Diagnostics. Its package exception marker is Public;
generation, validation, publication, artifact, source, and authority values are
Experimental; implementation machinery is Internal. No existing package
depends on Knowledge.

The only source scope is the closed, ordinally ordered 31-source
`phase4-foundation` inventory. Sources carry one of governing authority,
implementation evidence, or current explanatory documentation. Historical
evidence, workspace observations, and derived or advisory material are modeled
but rejected from this artifact.

The format is compact UTF-8 canonical JSON with schema
`kongono.framework-knowledge`, version `1`, and exactly one final LF. It records
exact inventory, source-set, knowledge-contract, generator-contract, and
generator-implementation identities; embeds each exact source byte string; and
uses whole-source `kgn-source-v1` references. SHA-256 establishes integrity and
exact equality only. It is not authentication, authorization, or signing.

Every generation is complete and in memory. There is no cache or incremental
mode. Structural validation proves canonical form and internally derivable
identities. Current validation performs complete regeneration from an explicit
root and requires byte equality. Stale, partial, mixed-version, malformed, or
tampered artifacts fail visibly.

Optional publication uses the artifact SHA-256 in the filename and an atomic
no-clobber hard-link from a fully written, flushed, synchronized temporary file
in the destination directory. An identical existing target is accepted only
after byte equality and structural validation. There is no overwrite,
replacement, deletion, mutable pointer, `latest`, or stale fallback.

The Application Graph is not an input. No generated artifact is checked in.
The package has no model, provider, retrieval, search, workspace reader,
network, role, mutation, application-runtime, or production dependency.
Source text is inert and is never executed or obeyed.

## Limits and failure model

The package fixes bounds for inventory bytes, record and path counts, individual
and total source bytes, artifact bytes, JSON depth, and generator implementation
inputs. It rejects path escape, aliases, links or reparse points, alternate
stream syntax, non-regular files, changed-during-read input, invalid encoding,
and noncanonical JSON. Eleven `KGN-KNOWLEDGE-1NNN` diagnostics expose only one
closed Safe `violation` token; rejected values and native messages are not
rendered by the command.

## Rejected alternatives

We reject an equal-authority corpus, runtime globbing, ambient discovery,
generated prose as authority, timestamps or randomness in semantic output,
mutable `latest`, silent replacement, overwrite-on-stale, a database or vector
index, model-generated artifacts, and dependencies on the Application Graph or
a developer-intelligence role package. These choices would erase authority
distinctions, weaken reproducibility, or introduce broader runtime authority.

## Consequences

The artifact is inspectable derived evidence for one bounded foundation, not a
complete framework, repository, release, or application-workspace model.
Generation cost is linear in the fixed input bytes and deliberately repeats on
every run. Ordinary Kongono operation remains independent of artifacts, AI,
accounts, and networks.

Phase 4D may consume this capability read-only under a separately authenticated
directive. It must not add Chie, Hantei, Oshie, retrieval, workspace access,
patching, or production authority by inference.
