# ADR 0021: Machine-Checkable API Status Metadata

## Status

Accepted

## Date

2026-08-12

## Context

The first production types create consumer expectations before Kongono has a stable release. Documentation-only or implicit stability labels can drift, and namespace or file location cannot reliably express supported external use. The repository needs deterministic metadata and enforcement without introducing a third-party dependency or deciding a complete versioning system.

## Decision

Define the Public bootstrap metadata types `Api` and `ApiStatus` in `kongono/kernel`. `ApiStatus` is a string-backed enum with exactly `Internal` (`internal`), `Experimental` (`experimental`), and `Public` (`public`). `Api` is a final immutable, non-repeatable PHP attribute targeting class-like declarations and accepting exactly one `ApiStatus`.

Every production class, interface, trait, and enum beneath `packages/*/src` must carry exactly one explicit classification. The two metadata types are Public by definition and are the only documented bootstrap exception to self-classification. Status applies to a type's public and protected members in Phase 0C; per-member attributes are not authorized.

Internal supports Kongono internals only and gives no external compatibility promise. Experimental permits explicitly instability-aware evaluation without a backward-compatibility promise before promotion. Public supports consumer use under Kongono's versioning policy and release maturity but does not mean Stable or unchangeable forever.

A deterministic repository architecture test discovers production declarations, verifies autoloadability and the exact authorized inventory, rejects missing or incorrect status, and checks the attribute declaration. API status is compatibility and exposure metadata only, not authorization, security, package visibility, or Application Graph data.

## Rationale

Language-level metadata is explicit beside the type, inspectable through standard reflection, and mechanically enforceable. Requiring a value prevents accidental default exposure. A minimal in-repository enum avoids importing another compatibility vocabulary before Kongono has selected its broader versioning and promotion rules.

## Consequences

Every newly authorized production type requires an intentional classification and test-inventory update. Public and protected members inherit the enclosing type's status for this phase. Consumers can distinguish supported, evaluation-only, and internal surfaces, but must still consult the specific contract and release maturity.

The bootstrap exception must remain exactly two types. Stable and Deprecated remain documentation vocabulary rather than executable statuses in Phase 0C.

## Rejected alternatives

- Opaque or undocumented API stability is rejected because consumers and repository checks need explicit expectations.
- Namespace-only or file-path-only conventions are rejected because they silently default status and couple compatibility to layout.
- Comment-only or PHPDoc-only classification is rejected because it is easier to drift and lacks a constrained value type.
- A third-party API-status package is rejected because the three-value contract needs no external dependency or vocabulary.
- Repeatable status attributes are rejected because conflicting classifications would be ambiguous.
- Per-member status and additional Stable or Deprecated cases are rejected for this phase because their compatibility semantics are unresolved.

## What remains open

Promotion and deprecation processes, semantic versioning details, pre-1.0 compatibility procedure, per-member status, package-level visibility enforcement, cross-language representation, Application Graph integration, generated documentation, and release tooling remain open.
