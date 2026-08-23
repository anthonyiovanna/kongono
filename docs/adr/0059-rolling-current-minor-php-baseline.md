# ADR 0059: Rolling Current-Minor PHP Baseline

## Status

Accepted

## Date

2026-08-22

## Context

[ADR 0002](0002-php-85-minimum.md) selected PHP 8.5 as Kongono's minimum
language baseline and left the supported-minor policy open. A lower bound by
itself admits later minors and majors that Kongono has not tested. Conversely,
retaining every older minor would add compatibility work without evidence that
it benefits the actively developed line.

Kongono therefore needs an explicit support boundary that distinguishes
accepted policy from the current installable Composer metadata. This ADR
refines the open minor-version policy in ADR 0002 without rewriting that
historical decision.

## Decision

The actively developed Kongono line supports exactly one stable PHP minor at a
time: the newest minor that has completed Kongono's compatibility gate. The
current intended certified line is PHP 8.5. The intended bounded Composer form
for a later migration is exactly `>=8.5.0 <8.6.0` in the root and every package.

Phase 4A accepts this policy only. It does not change a Composer constraint,
the lockfile, CI, runtime behavior, the supported-host matrix, or a release
claim. Only a later separately authenticated migration may make the bounded
constraint repository behavior.

## Current transitional repository state

The current repository metadata remains mixed:

- the root and the API Metadata, Application Graph, Dependency Injection,
  Diagnostic Activation, Diagnostic Handler, Diagnostic Presentation,
  Diagnostics, and Kernel package manifests require `>=8.5`;
- HTTP Message, HTTP Server, PHP CGI, Routing, Web Application, and Web Runtime
  require `^8.5`; and
- no manifest requires `>=8.5.0 <8.6.0`.

Those existing expressions describe what Composer currently admits. They do
not certify PHP 8.6 or PHP 9, and they are not the final rolling-policy
expression.

## Support contract

1. One compatibility-certified stable PHP minor is active at a time.
2. Stable patch releases within that minor remain eligible without a manifest
   edit unless a documented security, correctness, or platform reason raises
   the minimum patch.
3. The newest available stable patch of the certified minor is the primary CI
   and development target.
4. A non-authoritative forward-compatibility lane may test the next minor as
   soon as practical. Alpha, beta, release-candidate, nightly, or other
   prerelease success does not establish support.
5. Publication of a new stable PHP minor does not silently add it to the
   supported range. It starts an explicit compatibility and migration
   boundary.
6. After an accepted migration and release, the preceding minor leaves active
   support. Previously published Kongono releases retain their immutable
   historical constraints.
7. A PHP major always requires an explicit architecture and migration
   decision. PHP 9 readiness cannot be inferred from a PHP 8 constraint,
   roadmap, or prerelease result.
8. The final rolling expression cannot be unbounded `>=8.5`, `*`, or another
   range that silently admits an untested future major.
9. Compatibility shims, polyfills, legacy branches, and abstractions are not
   added merely to preserve an obsolete minor. A specific time-bounded
   exception requires another Accepted ADR.

## Compatibility gate

Moving to another PHP minor requires at least all of the following:

- review of the official PHP migration guide and accepted RFCs;
- zero Kongono-owned deprecation notices on the candidate stable runtime;
- a passing locked dependency audit and platform-requirement check;
- strict optimized-autoload proof;
- zero static-analysis errors and zero style findings;
- complete unit, architecture, integration, black-box, and applicable external
  acceptance suites;
- persistent-runtime isolation and resource-cleanup probes;
- proof on Windows and every other officially supported host;
- review of removed or unbundled extensions, changed defaults, warnings,
  errors, reflection behavior, and resource-to-object migrations;
- documented performance and memory comparison without unsupported claims;
  and
- release notes that explicitly name the new range and the dropped range.

An experimental or partial lane is evidence only. It cannot replace this gate
or expand the support claim.

## Deprecation policy

PHP deprecations are migration signals. Kongono-owned deprecations are not
globally suppressed. Before a future baseline is accepted, affected code must
move through the intended modern replacement and the complete compatibility
gate must pass.

## Release and versioning consequences

Dropping a PHP minor changes the installation contract. This ADR does not
select its Semantic Versioning treatment, release cadence, maintenance window,
LTS promise, backport policy, Kongono 1.0 policy, or migration date. Those
remain separate architecture and release decisions.

Only a later authenticated migration may change root or package constraints,
lockfile metadata, CI lanes, runtime certification, or release claims.

## Security and persistent-runtime implications

The compatibility gate treats dependency advisories, extension changes,
changed runtime defaults, warnings, errors, resource ownership, and cleanup as
correctness and security evidence. A passing unit subset cannot certify a new
minor. Persistent-runtime isolation and resource retirement must be proved on
the candidate stable runtime so state or resource behavior is not assumed from
the preceding line.

## Consequences

The active support claim becomes narrow and inspectable. Adopting a new minor
requires explicit evidence, while old release metadata remains historical
truth. The project accepts regular migration work instead of carrying
indefinite compatibility layers.

Until the separately authorized Composer migration completes, contributors
must state both the accepted one-minor policy and the current mixed metadata
when the distinction matters.

## Rejected alternatives

- Unbounded `>=8.5` is rejected as the final policy because it admits untested
  later minors and majors.
- Long-term `^8.5` is rejected because it can admit PHP 8 minors that have not
  completed the gate.
- Automatic adoption when a release appears on php.net is rejected because
  publication is not Kongono compatibility evidence.
- Supporting multiple obsolete minors by default is rejected because it would
  create an unbounded compatibility obligation.
- Exact monthly patch pinning is rejected because eligible stable patches
  should not require routine manifest edits.
- Default conditional compatibility layers are rejected; an exception needs a
  separate Accepted ADR with a time bound.
- PHP 9 readiness based only on a roadmap or prerelease tests is rejected.

## What remains open

The migration date, exact migration directive, CI implementation, supported
host matrix, release treatment, maintenance policy, LTS, backports, and 1.0
policy remain open. No PHP 8.6 or PHP 9 support is accepted here.

## Related material

- [ADR 0002: PHP 8.5 Minimum](0002-php-85-minimum.md)
- [PHP support policy](../engineering/php-support-policy.md)
- [PHP engineering toolchain](../engineering/toolchain.md)
- [Testing](../engineering/testing.md)
- [Architectural doctrine](../doctrine.md)
- [Phase 4 roadmap](../architecture/phase-4-roadmap.md)
