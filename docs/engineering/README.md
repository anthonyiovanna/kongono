# Kongono Engineering Foundation

- [Read-only Hantei](../architecture/read-only-hantei.md) documents the fixed
  Phase 4E verifier, subject identity, evidence, process, and mutation boundary.
## Purpose and current scope

Kongono's engineering foundation makes the repository's PHP quality expectations executable across the root and all nineteen production packages. It gives contributors one reproducible development environment and one non-mutating local gate without implying that Experimental behavior is Stable or that planned capabilities exist.

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
- [Generated wiring](generated-wiring.md)
- [Developer-intelligence architecture](../architecture/developer-intelligence.md)
- [Phase 4 roadmap](../architecture/phase-4-roadmap.md)
- [Phase 4 external acceptance](../architecture/phase-4-external-acceptance.md)
- [Application composition and build](../architecture/application-composition-and-build.md)
- [Phase 5 roadmap](../architecture/phase-5-roadmap.md)

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
Kongono remains pre-1.0.

## Phase 5A composition engineering

Phase 5A adds documentation architecture only. The [generated wiring guide](generated-wiring.md)
records future contributor rules for explicit canonical constructor
dependencies, deterministic readable PHP output, source/output identity,
lifetime and cleanup ownership, no runtime discovery or property-hook
resolution, reproducible-build proof, and benchmark-before-performance claims.

The [composition guide](../architecture/application-composition-and-build.md)
and [Phase 5 roadmap](../architecture/phase-5-roadmap.md) distinguish current
caller-owned composition from Accepted and planned behavior. No package,
source, test, command, dependency, Composer constraint, generated artifact, or
runtime behavior changes in Phase 5A. Phase 5B now applies
`>=8.5.0 <8.6.0` to the root and all eighteen packages. PHP 8.5 is the one
active compatibility-certified minor, with PHP 8.5.9 on Windows proved by this
execution; stable eligible 8.5 patches remain in range. PHP 8.6, PHP 9,
prerelease, nightly, and automatic future-minor support are not claimed. The
migration changes installability metadata only and adds no runtime semantics,
package, dependency, public API, polyfill, shim, release/LTS promise, or
composition/build capability. Phase 5B is accepted and the bounded PHP
constraint is current.

## Phase 5C declaration engineering

Phase 5C accepts the contract for the singular inert
`<application-root>/kongono.composition.json` source only. The
[composition declaration guide](composition-declarations.md) records exact v1
grammar, canonical symbols, three argument forms, three lifetimes, two root
kinds, reachability, cleanup, bounds, safe reading, deterministic failures,
non-disclosure, provenance, tests, and future-version migration review.

Phase 5D adds the Experimental `kongono/composition` parser, validator,
canonical artifact builder, current-state validator, package tests, and
content-addressed publisher. Phase 5E adds canonical typed wiring and isolated
lifecycle support in the same package, using application-owned typed aggregates
instead of collection injection. Phase 5F adds one exact-current loader, a
one-use startup boundary, and the exact single-root web integration without a
schema file, command, container, service locator, or new transport. Composition
remains caller-owned; DI and Graph remain unintegrated, while existing Web
Application, Web Runtime, and PHP-CGI ownership is preserved.
See [ADR 0070](../adr/0070-versioned-composition-source-and-provenance.md),
[ADR 0071](../adr/0071-roots-bindings-lifetimes-and-cleanup.md),
[ADR 0072](../adr/0072-deterministic-composition-artifact-and-publication.md),
[ADR 0073](../adr/0073-generated-typed-wiring-and-lifecycle-boundary.md),
[ADR 0074](../adr/0074-generated-assembly-web-runtime-integration.md),
the [composition source guide](../architecture/composition-source.md), and the
[composition build guide](composition-build.md).
[generated runtime integration guide](../architecture/generated-runtime-integration.md).
[application workflow guide](application-workflow.md).

## Phase 5H external acceptance

The [Phase 5 external acceptance guide](../architecture/phase-5-external-acceptance.md)
documents the root-owned `composer acceptance:phase5` harness. It proves the
fixed 28-scenario external application matrix with mirrored ordinary package
copies, plugins and network disabled, exact raw-stream assertions, unchanged
repository state, and owned temporary cleanup. It is test evidence only, not a
runtime package, framework CLI, installer, deployment mechanism, Stable API,
production-readiness claim, or Phase 6 capability.
