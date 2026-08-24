# PHP Support Policy

## Purpose and status

This contributor policy translates [ADR 0059](../adr/0059-rolling-current-minor-php-baseline.md)
into operational review criteria. The rolling one-minor policy is Accepted.
Phase 5B applies ADR 0059's accepted bounded current-minor expression without
changing runtime behavior or making a release claim.

## Current manifest state

The root and all eighteen package manifests require `>=8.5.0 <8.6.0`. PHP 8.5
is the one active compatibility-certified minor. Phase 5B proves PHP 8.5.9 on
Windows; it does not invent a broader host matrix. Eligible stable 8.5 patches
remain within range, while a later minor requires the complete separately
accepted compatibility gate. PHP 8.6, PHP 9, prerelease, nightly, and automatic
future-minor support are not claimed.

## Accepted rolling one-minor policy

The actively developed line supports exactly one stable PHP minor at a time:
the newest minor that has completed Kongono's compatibility gate. PHP 8.5 is
the current certified line. The bounded expression is repository behavior from
Phase 5B onward.

Eligible stable patches within the certified minor do not require a manifest
edit unless a documented security, correctness, or platform reason raises the
minimum. The newest stable patch is the primary development and CI target.

A non-authoritative forward-compatibility lane may test the next minor. Its
result is preliminary evidence only; development, alpha, beta, RC, and nightly
success do not establish support. A new stable minor starts an explicit review
and migration boundary rather than entering the range automatically.

## Compatibility gate checklist

Before another minor can be accepted, reviewers must obtain all of this
evidence:

- [ ] Review the official PHP migration guide and accepted RFCs.
- [ ] Observe zero Kongono-owned deprecation notices on the candidate stable
  runtime.
- [ ] Pass the locked dependency audit and platform requirements.
- [ ] Prove strict optimized autoloading.
- [ ] Report zero static-analysis errors and zero style findings.
- [ ] Pass the complete unit, architecture, integration, black-box, and
  applicable external acceptance suites.
- [ ] Pass persistent-runtime isolation and resource-cleanup probes.
- [ ] Prove Windows and every other officially supported host.
- [ ] Review removed or unbundled extensions, changed defaults, warnings,
  errors, reflection behavior, and resource-to-object migrations.
- [ ] Document performance and memory comparisons without unsupported claims.
- [ ] Prepare release notes naming the new supported range and dropped range.

Skipped, stale, partial, prerelease-only, or environment-blocked evidence does
not pass the gate.

## Deprecations and runtime changes

Do not globally suppress Kongono-owned PHP deprecations. Treat each as a
migration signal and use the intended modern replacement before accepting the
candidate baseline.

Review extension availability and bundling, default configuration, warning and
error behavior, reflection changes, and resource-to-object migrations. Repeat
persistent-runtime isolation and cleanup probes; prior behavior cannot be
assumed on a new minor.

## Dependencies and engineering tools

The locked dependency audit, installed-platform check, optimized autoload
proof, style check, static analysis, and complete tests are all required. The
literal repository commands and their order remain defined by the
[toolchain](toolchain.md). No dependency, policy exception, baseline,
suppression, platform ignore, or test relaxation is implied by this policy.

## Host, performance, and release evidence

Windows and every other officially supported host require current proof.
Performance and memory comparisons must name the runtime and workload and must
not become general speed, overhead, or capacity claims.

After an accepted migration and release, the preceding minor leaves active
support. Historical releases keep their original constraints. Release notes
must name both the new and dropped ranges. Semantic Versioning treatment,
cadence, maintenance, LTS, backports, and Kongono 1.0 policy remain open.

## Unsupported interpretations

This policy does not mean:

- the bounded constraint certifies any minor beyond PHP 8.5;
- PHP 8.6 is supported because it is published or a forward lane passes;
- PHP 9 is supported because PHP 8 code or a prerelease test succeeds;
- older minors receive default shims, polyfills, legacy branches, or indefinite
  maintenance; or
- Phase 5B changes runtime semantics, dependencies, API, or release status.

A PHP major always requires a separate architecture and migration decision. A
time-bounded compatibility exception requires another Accepted ADR.

## Phase 5B migration boundary

Phase 5B changes installability metadata only. It adds no runtime semantics,
package, dependency, public API, polyfill, shim, composition/build capability,
release, or LTS promise. Phase 5A remains accepted; Phase 5C is next and has not
begun. Any later minor still requires a separately authenticated directive and
the complete gate above.

## Related material

- [ADR 0059](../adr/0059-rolling-current-minor-php-baseline.md)
- [ADR 0002](../adr/0002-php-85-minimum.md)
- [Toolchain](toolchain.md)
- [Testing](testing.md)
- [Contributing](../../CONTRIBUTING.md)
- [Architectural doctrine](../doctrine.md)
- [Phase 4 roadmap](../architecture/phase-4-roadmap.md)
