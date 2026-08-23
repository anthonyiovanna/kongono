# Kongono Engineering Foundation

- [Read-only Hantei](../architecture/read-only-hantei.md) documents the fixed
  Phase 4E verifier, subject identity, evidence, process, and mutation boundary.
## Purpose and current scope

Kongono's engineering foundation makes the repository's PHP quality expectations executable across the root and all fourteen production packages. It gives contributors one reproducible development environment and one non-mutating local gate without implying that Experimental behavior is Stable or that planned capabilities exist.

Phase 0X expands the automatically discovered inventory with eight Diagnostic
Presentation declarations and six focused package tests. Existing quality rules
and policy remain unchanged.

The root command is:

```bash
composer quality
```

It validates the root and all six package manifests in lexicographic order, audits the locked dependency graph, checks the actual runtime platform, checks formatting, performs maximum-level static analysis with strict rules, and runs tests. A passing gate means those configured checks passed for the current checkout and environment; it is not a claim of framework completeness, production readiness, or security certification.

## Guides

- [Toolchain and contributor commands](toolchain.md)
- [PHP style](php-style.md)
- [Static analysis](static-analysis.md)
- [Testing](testing.md)
- [Dependency security](dependency-security.md)
- [Package layout](package-layout.md)
- [API status](api-status.md)
- [Application Graph structure](application-graph-structure.md)
- [Explicit typed dependency resolution](dependency-injection.md)
- [Error handling and actionable diagnostics](error-handling.md)
- [PHP support policy](php-support-policy.md)
- [Developer-intelligence architecture](../architecture/developer-intelligence.md)
- [Phase 4 roadmap](../architecture/phase-4-roadmap.md)
- [Phase 4 external acceptance](../architecture/phase-4-external-acceptance.md)

## Authority and exceptions

These controls are repository architecture, not optional developer preferences. Do not bypass a failure, weaken policy, authorize a Composer plugin, add a dependency, create a PHPStan baseline, or add a suppression without architect approval. When an approved tool appears to conflict with an approved requirement, stop and report the conflict rather than making the gate artificially green.

**Stability:** Internal engineering policy for the pre-alpha repository. It does not create a public Kongono framework API.

Phase 4F adds the read-only Oshie package to canonical package, style, static-analysis, and PHPUnit gates. Its curriculum resource and real CLI proof are deterministic evidence, not an alternate source of engineering policy.
## Phase 4G proposal engineering

The [reviewable Chie proposal guide](../architecture/reviewable-chie-patch-proposals.md)
defines the only implemented proposal rule: one exact complete-line replacement
in current explanatory documentation, with canonical identities, reversible
diffs, provenance, and a pending human/fresh-Hantei handoff. It grants no apply,
write, Git, verification, model, network, or deployment authority.

## Phase 4 external acceptance engineering

The root-owned `composer acceptance:phase4` harness proves 28 exact scenarios
through real local process boundaries, bounded streams and timeouts, temporary-
only writes, cleanup, shared current identities, and explicit real versus
synthetic Hantei evidence. It adds no package API, dependency, production
authority, network integration, or executable Oshie example. Phase 4 is closed;
Kongono remains pre-1.0, and Phase 5 was not begun.
