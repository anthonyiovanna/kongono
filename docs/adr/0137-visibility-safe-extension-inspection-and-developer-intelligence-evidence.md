# ADR 0137: Visibility-Safe Extension Inspection and Developer-Intelligence Evidence

- Status: Accepted
- Date: 2026-08-30
- Phase: 12G

## Context

Phase 12C produces canonical extension-declaration evidence and Phase 12E/F produces and hosts generated trusted/in-process wiring. Neither boundary provides a visibility-safe detached consumer. Inspection must not turn structural validity into currentness, trust, authorization, health, installation, host observation, or execution evidence.

## Decision

Create Experimental `kongono/extension-inspection`. A caller supplies exactly one declaration-artifact JSON file and one generated-wiring PHP file. The package applies bounded ordinary-file and no-link/reparse path rules, reads both as inert bytes, calls the existing declaration and wiring structural validators, and requires exact declaration-set identity equality. It never calls currentness validation and never loads, requires, reflects, tokenizes independently, executes, or retains generated PHP.

The resulting immutable inspection exposes only artifact and plan identities, bounded total aggregate counts across the complete category, lifecycle, capability-requirement, and contribution-visibility vocabularies, and the fixed limitation tokens `extension-platform-v1`, `safe-aggregate-only`, `not-established`, `evidence-only`, `not-observed`, `not-observed`, and `not-sandboxed`.

Chie, Hantei, and Oshie are role addresses only. They receive the same Safe aggregate evidence plus their role token, without package dependencies or authority. Independent verification compares exactly seven ordered evidence groups and emits only `<check-id>-failed` tokens in that order. It does not repair, normalize, or rederive supplied views.

The supported `kongo extension:inspect` command is read-only, noninteractive, observe-only, and accepts exactly `declaration-artifact-path` and `generated-wiring-path`. Success fields are Safe and presentation-neutral. Rejections use fixed non-disclosing diagnostics.

## Security and non-authority boundary

Structural validity does not establish currentness. The Extension Host is not observed, and execution is not observed. Trusted in-process PHP remains not sandboxed. Inspection grants no capability, authorization, Principal status, trust certification, approval, installation, health, marketplace, remote lookup, model or agent call, auto-fix, or mutation authority. An extension cannot authorize itself.

## Consequences

The production graph becomes exactly 63 packages and 226 acyclic local production edges. The generated file remains inert evidence throughout inspection. Phase 12H remains planned and unstarted. Kongono remains Experimental, pre-1.0, and not production-ready.

## Related material

- [Extension inspection and developer intelligence](../architecture/extension-inspection-and-developer-intelligence.md)
- [Extension platform](../architecture/extension-platform.md)
- [ADR 0136](0136-bounded-trusted-in-process-extension-host-lifecycle.md)
- [Phase 12 roadmap](../architecture/phase-12-roadmap.md)
