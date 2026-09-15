# Read-Only Kongono Hantei

Phase 19G's optional [asset-inspection evidence](asset-inspection-and-intelligence.md)
uses `hantei` only as the fixed `verify` audience label. Its independent
verifier is package-owned comparison code, not a Hantei invocation, quality
run, repair action, or certification.

Phase 12H externally proves Hantei's independent extension-view verification
and registers `acceptance:phase12` in the fixed root-script inventory. The
profile remains exactly eight checks; the script is not a ninth check and
Hantei gains no repair or extension authority. See
[Phase 12 external acceptance](phase-12-external-acceptance.md).

## Purpose and current status

Phase 4E implements the first read-only Hantei verifier. Hantei independently
judges Kongono's exact current framework state with deterministic evidence. It
consumes the Phase 4C knowledge foundation directly and has no dependency on
Chie. Its APIs are Experimental.

## Fixed profile

`framework-quality-v1` selects exactly eight ordered Composer scripts:
`validate:composer`, `validate:packages`, `audit:locked`, `platform`,
`knowledge:check`, `cs`, `analyse`, and `test`. The package-owned canonical
profile also records the exact required root script definitions. Callers cannot
supply a profile file, check subset, script, argument, exclusion, or command.

The current Phase 9H profile mirrors all forty-four production package
validations and all four copy-once skeleton validations, including Security
Policy Build, Security Application, and security-minimal. This inventory change
does not add a Hantei check or grant Hantei policy, repair, or acceptance
authority. It also records the exact root `acceptance:phase9` definition as
repository-quality evidence; the external profile remains test-owned and is not
an additional Hantei check.

## Subject and provenance

Hantei identifies every Git-visible tracked or unignored file except the
caller-managed `docs/dev_plan/**` and `docs/task_reports/**` trees. Paths are
ordinally sorted and each manifest record binds its path, decimal byte count,
and lowercase SHA-256. The subject is recaptured before checks, after each check,
and at completion. Any change blocks the run and stops later checks.

Every report also binds a freshly generated and currently validated framework-
knowledge artifact and its source set. Stale knowledge, weakened profiles,
skipped checks, irrelevant evidence, missing proof, and mismatched subjects
cannot pass.

## Verdict and evidence

`PASS` requires eight zero exits against one unchanged subject. A conclusive
nonzero selected check produces `FAIL`, which takes precedence over simultaneous
blocked evidence. Without a conclusive failure, missing or untrusted required
evidence produces `BLOCKED`.

Each check exposes only its fixed ID and Composer script, outcome, integer or
null exit code, stdout/stderr byte counts and hashes, and a closed Safe reason.
Reports expose the fixed schema/profile, exact identities, PHP and Git versions,
ordered checks, and unique uncertainties. They never expose raw streams,
environment values, rejected paths, command input, secrets, native messages,
stack traces, or Protected context.

## Process and mutation boundary

Hantei may execute only read-only Git inventory/version operations, local
PHP/Composer identity probes, and the eight fixed Composer scripts. It uses
argument vectors with shell bypass, 600-second check timeouts, and independent
8 MiB output limits. The locked audit may use its already accepted network
behavior. A cache warning alone is not transport unavailability, and policy or
security failures remain `FAIL`.

Ignored PHPStan, PHPUnit, PHP CS Fixer, and Composer cache effects are outside
the subject. Hantei does not restore or delete them. It cannot repair, patch,
stage, commit, publish, deploy, access production, or invoke caller-supplied
commands.

## Command contract

The only grammar is:

```text
kongono-hantei verify <framework-root>
```

Semantic reports go to stdout with exits `0` for `PASS`, `1` for `FAIL`, and `2`
for `BLOCKED`. Invalid usage writes one Safe canonical error to stderr and exits
`64`; an unexpected command-boundary failure exits `70`.

## Authority and next phases

A Hantei report is derived evidence, not accepted framework truth. A consumer
must match its profile and subject identities to the current subject. Hantei
cannot certify a not-yet-applied Chie candidate.

Phase 4H externally requires real current-repository `PASS` across all eight
checks with no uncertainty and the current artifact/source-set identities. Two
isolated temporary subjects use an explicitly test-owned synthetic evidence
tool to prove deterministic `FAIL` and advisory-unavailable `BLOCKED` behavior;
that evidence is not real framework quality. Subject bytes remain unchanged,
and Hantei still cannot repair or certify unapplied candidate bytes. Phase 4 is
closed; Phase 5 was not begun.

The current framework-quality profile mirrors 52 package validation commands
and five copy-once skeleton validation commands, including both Phase 10G
packages and `observability-minimal`. This updates deterministic verification
inventory only; Hantei still cannot build, repair, publish, execute
observability operations, or certify its own output.

Phase 10H adds `acceptance:phase10` to that same fixed profile. Hantei records
the exact root-owned external command as deterministic quality evidence; it
does not gain the ability to create temporary applications, export signals,
repair failures, stage or commit content, or certify its own report. The Phase
10 acceptance harness remains separately owned by root tests.

Phase 12G may address independently verifiable Safe extension aggregate
evidence to Hantei. The role token is not verification authority, and the
extension-inspection package does not depend on or invoke Hantei.
