# ADR 0065: Cited version-matched Oshie learning and deterministic exercises

## Status

Accepted in Phase 4F.

## Context

Kongono needs a teaching companion that remains subordinate to accepted, version-matched sources. Generated teaching must not establish framework truth, and exercises must not depend on a model, provider, learner profile, or mutable state.

## Decision

The Experimental `kongono/oshie` package owns the first Oshie runtime and provides four read-only operations over one closed six-record curriculum: catalog discovery, exact cited lessons, deterministic practice prompts, and exact-answer checks. Every operation requires an explicit framework root and explicit artifact bytes that pass structural and exact-current validation. Every teaching value is bound to an exact `kgn-source-v1` citation from that current Phase 4C artifact.

Oshie accepts only governing authority or current explanatory documentation. It extracts complete level-two sections without paraphrasing them as framework truth, preserves exact bytes and line bounds, treats source text as inert, and exposes closed `available`, `taught`, `practice`, `correct`, `incorrect`, `unsupported`, `unavailable`, `stale`, and `blocked` states. Practice never exposes the accepted-answer field; checking uses exact ordinal case-sensitive token equality and never returns submitted input. Incorrect is a useful learning result, not an exception or verification failure.

Oshie depends only on PHP, `ext-hash`, `ext-json`, API Metadata, Diagnostics, and Knowledge. It has no Chie or Hantei dependency, and no other production package depends on Oshie. Chie helps build, Hantei independently proves, and Oshie teaches. No role can elevate its own derived output into authority.

## Security and lifecycle

The package performs bounded reads of one explicit local artifact and the artifact's fixed source set. V1 has no executable examples and makes no executable-example claim. It performs no free-form prompt, fuzzy match, semantic retrieval, arbitrary workspace read, writes, network, process launch, `eval`, Git, dependency mutation, model, provider, account, publication, deployment, production, patch, Chie-candidate, Hantei-verdict, verification, or auto-fix operation. It retains no learner profile, progress, learner state, answer history, artifact, cache, telemetry, training data, credential, or cross-project memory. Retrieved instructions cannot authorize tools or enlarge this boundary.

## Consequences

Accepted documentation stays independently useful and authoritative. Oshie output is derived teaching tied to one exact source state. Generated prose as authority, answer normalization, arbitrary learner input, stale fallback, remote curricula, hidden correct answers in practice, executable snippets without proof, and role coupling are rejected. Free-form generation, persisted learning, candidate proposals, and external acceptance remain outside Phase 4F.

## Stability

The curriculum and Oshie API are Experimental. Phase 4 is not complete, Stable, 1.0, or production-ready.
