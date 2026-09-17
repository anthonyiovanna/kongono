# Kongono Cache Inspection

`kongono/cache-inspection` is the optional Experimental Phase 20G upper
evidence leaf. It accepts exactly one supplied `CurrentCacheApplicationBuild`
and optional caller-supplied detached aggregate runtime evidence. It
independently reconstructs the profile set and artifact in memory and emits
only deterministic Safe aggregates and Protected SHA-256 bindings.

It reads no files, keys, values, backend state, runtime state, clocks, or
environment. It performs no cache operation, mutation, role execution,
recording, export, repair, or production observation. Supplied structural
evidence does not establish live backend currentness, cache correctness,
Graph ownership, certification, or production readiness.

- [ADR 0204](../../docs/adr/0204-visibility-safe-cache-inspection-and-developer-intelligence.md)
- [Cache inspection guide](../../docs/architecture/cache-inspection-and-intelligence.md)
- [Phase 20 roadmap](../../docs/architecture/phase-20-roadmap.md)
