# Phase 13 external Agent Principal acceptance

## Purpose

Phase 13H closes the governed Agent Principal milestone by exercising the
accepted Phase 13B-G stack through production APIs in a fresh external PHP
process. The harness is root-owned test evidence. It is not a production host,
authority source, provider integration, command, monitor, or certification.

Run the exact entry point directly or through Composer:

```text
php tests/External/AgentPrincipal/run.php
composer acceptance:phase13
```

Success is one canonical LF-terminated JSON line with the explicit case count
and cleanup state. Intentional child failure emits empty stdout and one fixed
LF-terminated failure token on stderr.

## Evidence flow

The harness constructs one explicit version-1 declaration with an Agent-kind
principal reference, visibility, canonical capabilities, all nine constraints,
external evidence references, and provenance. Those values are inert: identity
is not authentication, a request is not a grant, and provenance is not truth.

The declaration builder produces deterministic canonical bytes and a
content-addressed artifact. Structural validation and caller-relative
currentness remain distinct. Publication is idempotent for identical bytes and
fails closed on conflicts. No discovery occurs.

The admission builder consumes the exact current artifact plus completed
application-owned authentication and authorization evidence. Missing,
undeclared, denied, indeterminate, stale, and identity-mismatched cases deny or
fail closed. The resulting decision and authority plan remain evidence; they do
not evaluate credentials, grant a capability, resolve a port, or perform work.

The provider-neutral runtime receives proposal, capability, authority-
currentness, cancellation, clock, intent, and budget collaborators explicitly.
It rechecks current authority immediately before each possible effect, invokes
at most one proposal and one exact capability, checks cancellation and time at
the fixed checkpoints, enforces the complete nine-budget contract, closes
capability ports in reverse order and the proposal port last, and retires the
operation. No provider SDK, ambient registry, retry loop, delegation engine,
retained session, or background work exists.

The application profile binds the exact declaration artifact to the matching
admitted plan. Generated ordinary PHP is deterministic and content-addressed.
One explicit contained generated file is snapshot-validated, reverified, and
loaded once. There is no glob, directory scan, ambient discovery, container,
service locator, provider lookup, or hidden configuration. Cleanup ownership
transfers exactly once when the runtime accepts the request. Generated and
in-process PHP is trusted and not sandboxed.

Inspection revalidates the exact supplied profile/wiring pair without loading
or executing it. An optional completed runtime result contributes only closed
aggregates. Raw principal, capability, constraint, evidence, proposal, result,
receipt, generated-code, path, provider/model, credential, secret, token, and
arbitrary fixture values remain omitted from inspection and projected evidence.

Optional matching-role Application Graph Intelligence contributes only Safe
aggregate context. It establishes no relationship, currentness, authority, or
live fact. The Observation and Security Audit projections are inert neutral
values with no recorder, delivery, durability, application-security, or
certification claim. Chie, Hantei, and Oshie views share underlying facts but
are separately addressed; Agent Inspection does not depend on or execute the
roles. Independent verification compares eight fixed evidence groups and
repairs nothing.

## Process and workspace safety

Every artifact workspace is freshly named outside the repository. Paths reject
NUL, traversal, absolute inputs, backslash escape, symlink, junction/reparse,
and repository overlap. Identity and cleanup walks accept only contained
ordinary files and directories. All process pipes are closed, stdout and stderr
are independently limited to 1 MiB, and cleanup occurs in `finally` paths.

The harness runs two sequential and two concurrent success children and
requires byte-identical output. It exercises a fixed intentional failure token,
then proves a later operation remains healthy. Repository fingerprints cover
porcelain status, refs, HEAD, the empty staged delta, the index, local Git
configuration, and `composer.lock` before and after execution.

## Disclosure boundary

Success and failure streams contain only the closed profile, status, case-count,
cleanup, or failure-token vocabulary. They contain no repository or temporary
absolute path, principal ID, capability identity or purpose, constraint or
evidence detail, provenance, prompt, proposal, result, receipt, generated PHP,
provider/model value, credential, secret, token, or arbitrary private fixture
literal. Native throwable traces are not presented as safe evidence.

## Stability and absent behavior

Phase 13 is closed at one local checkpoint. Kongono remains Experimental and
pre-1.0. No provider, network account, remote agent service, profile registry,
command, retained memory/session, autonomous loop, scheduler, background
worker, deployment manager, marketplace, live monitor, repair engine, release,
tag, push, second checkpoint commit, or Phase 14 behavior is introduced.
Directives and reports remain outside the checkpoint.

See [ADR 0146](../adr/0146-phase-13-external-agent-principal-acceptance-and-checkpoint.md),
the [Phase 13 roadmap](phase-13-roadmap.md), and the
[Agent Principal Platform](agent-principal-platform.md).
