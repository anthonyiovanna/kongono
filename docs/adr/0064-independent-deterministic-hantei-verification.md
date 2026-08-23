# ADR 0064: Independent Deterministic Hantei Verification

## Status

Accepted and implemented by Phase 4E.

## Context

Kongono needs an independent verifier for the same exact framework state used by
the deterministic knowledge foundation. Self-reported completion, selectable
subsets, and raw tool logs do not establish that every required quality check
judged one unchanged subject. Hantei must remain useful without AI, a provider,
an account, or a network except for the repository's existing locked advisory
audit.

## Decision

1. `kongono/hantei` is an optional read-only deterministic verifier and does not
   depend on Chie.
2. Hantei owns one closed `framework-quality-v1` profile with exactly eight
   ordered Composer checks: Composer schema, package schemas, locked advisories,
   platform, current knowledge, style, static analysis, and tests.
3. The package resource binds the exact required root Composer script
   definitions. Missing, reordered, renamed, weakened, unknown, or caller-chosen
   checks cannot pass.
4. The judged subject is the ordinally ordered Git-visible working-tree set,
   excluding only accepted ignore rules and the caller-managed directive/report
   trees. Each manifest record binds path, byte count, and SHA-256.
5. Subject identity is captured before checks, after every check, and at
   completion. A changed or unavailable subject stops further execution and
   produces blocked evidence.
6. Every report binds a freshly generated and currently validated Phase 4C
   framework-knowledge artifact and source-set identity.
7. Process authority is limited to fixed read-only Git inventory/version probes,
   local PHP/Composer identity, and the eight fixed Composer scripts. Commands
   use argument vectors, shell bypass, a 600-second timeout, and independent
   8 MiB stream bounds.
8. `FAIL` means at least one conclusive nonzero selected check. `FAIL` takes
   precedence over simultaneous `BLOCKED` evidence. `BLOCKED` means required
   evidence is unavailable or untrusted without a conclusive failure. `PASS`
   requires all eight checks to pass against one unchanged subject.
9. Missing, duplicate, unknown, out-of-order, stale, subject-mismatched,
   profile-mismatched, truncated, timed-out, unavailable, or irrelevant evidence
   cannot produce `PASS`.
10. Evidence exposes only fixed identities, exit status, byte counts, hashes,
    and a closed Safe reason. Raw streams, rejected values, environment values,
    secrets, and Protected context are not serialized.
11. Audit transport unavailability is recognized only through a closed signature
    list. Cache warnings alone and vulnerability, malware, abandoned-package,
    policy, schema, or dependency failures remain conclusive failures.
12. Ignored tool-cache effects are outside the judged subject. Hantei neither
    restores nor deletes those caches and never repairs source.
13. A Hantei verdict is derived evidence, never governing authority. Consumers
    must match profile and subject identities to their current state.
14. Hantei cannot repair, patch, stage, commit, publish, deploy, access
    production, or certify a future Chie candidate before the human-controlled
    Phase 4G application and fresh-verification boundary exists.
15. The API is Experimental except for the Public package exception marker and
    Internal command, diagnostic, exception-foundation, profile, subject,
    process, canonical-JSON, and verdict-evaluation mechanisms.

## Security and failure model

Source paths and process output are hostile input. Subject construction rejects
escape, alias, link/reparse, non-ordinary, duplicate, malformed, excessive,
unreadable, and unstable inputs. Process bytes remain in memory only long enough
to count, hash, and perform the narrow advisory-transport classification. Safe
diagnostics never capture raw native errors or rejected input.

## Rejected alternatives

- self-reported completion;
- selectable passing subsets;
- Hantei repair or automatic fixes;
- raw log disclosure;
- shell command strings derived from input;
- model-based judgment;
- network-required framework operation; and
- treating cache bytes or exact assertion totals as correctness contracts.

## Consequences

Verification is slower than running a selected check because the complete fixed
profile always runs unless subject or process safety is lost. The result is
portable, inspectable evidence whose limits and uncertainty remain explicit.
Phase 4F Oshie, Phase 4G candidate application, and Phase 4H external acceptance
remain unimplemented.
