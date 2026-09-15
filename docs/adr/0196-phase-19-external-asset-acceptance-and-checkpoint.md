# ADR 0196: Phase 19 External Asset Acceptance and Checkpoint

- Status: Accepted
- Date: 2026-09-15
- Phase: 19H

## Context

Phases 19B through 19G separately established the optional Experimental
neutral asset contract, deterministic native builder, replaceable adapter
contract and bounded Vite adapter, exact-current resolver, supported
application workflow, explicit Phase 18 value mapping, and visibility-safe
inspection leaf. Package tests prove those individual boundaries, but closing
the milestone requires fresh-process evidence that the real accepted stack
composes without ambient authority, retained process state, stale fallback,
uncontained output, or disclosure.

## Decision

Accept the root-test-only harness at `tests/External/Asset/run.php` and the
explicit `composer acceptance:phase19` command. The harness owns uniquely
named temporary application workspaces and uses the real Phase 19 production
packages. It supplies every source, dependency, executable, launch artifact,
working directory, environment map, expected digest, deadline, and stream cap
explicitly. It may launch only bounded local child processes and may create or
remove only its owned `kongono-phase19-*` temporary trees.

The native scenario exercises all accepted dependency kinds and proves
canonical planning and building, exact source and artifact currentness,
content and SHA-384 integrity, deterministic output, contained
content-addressed no-clobber publication, write-free checking, resolution under
explicit URL/cache/integrity/preload policy, and application-owned mapping into
Phase 18 presentation values. Mutated, stale, missing, additional, linked,
outside-root, and conflicting evidence fails closed.

The adapter scenario uses the real neutral adapter and Vite-adapter production
stack with a test-owned PHP protocol emulator supplied as the explicit
executable. It proves the fixed request, bounded shell-free process launch,
strict manifest and complete ordinary-file output translation, deterministic
neutral artifact validation, timeout and stdout/stderr caps, one cleanup
attempt, and healthy recovery after failure. Malformed, duplicate, partial,
extra, outside-root, linked, changed-launch, start-failure, nonzero-exit,
overflow, and timeout cases fail. This is protocol-emulated Vite-adapter
evidence; it is not real Vite or Node version compatibility certification.

The harness copies `asset-minimal` into an owned workspace, installs only local
locked packages with Composer networking disabled, and proves direct and
Composer parity for `asset:build`, `asset:check`, and `asset:resolve`. Phase
19G inspection reconstructs one explicit current result in memory and is
independently verified for Chie, Hantei, and Oshie evidence purposes. Role
labels execute no role or effect, Graph relationships remain
`not-established`, and projections remain `constructed-not-recorded`.

Repeated sequential children, concurrent children, malformed output,
failure-then-health, stream overflow, timeout, repository-fingerprint, and
temporary-residue checks prove isolation, bounded failure behavior, process
retirement, and cleanup. Adversarial literals, exception text, traces, paths,
commands, source/output values, URLs, private identities or digests,
environment data, headers, and bodies are excluded from public output and
harness-created repository content.

Public success is exactly one compact LF-terminated JSON line:

```text
{"status":"ok","profile":"kongono-phase19-asset-platform","case_count":N,"cleanup":"complete"}
```

`N` is the observed positive deterministic case count. Successful stderr is
empty. Any failure exits nonzero and emits only:

```text
Kongono Phase 19 external acceptance failed.
```

The harness performs no public network access, ambient DNS, non-loopback
listening, provider or browser use, database or credential access, package
download, Node/npm installation, deployment, CDN operation, or external
service access. Trusted fixtures and local executables are explicit inputs;
they do not make the acceptance a sandbox or certification.

## Checkpoint

Phase 19A through Phase 19H are Accepted and Phase 19 is closed by one ordinary
non-amending local commit with subject
`Complete Kongono Phase 19 asset platform milestone` and parent
`a04b1ae491cb9c52eb5bd32c37f8524fcd683c74`. Resulting commit and tree
identities belong to the post-commit report, not architecture.

Topology remains 102 production packages, 435 local production edges, and
acyclic. PHP inventories are 2,026 production, 1,055 package-test, and 100
root-test files.

## Security and evidence limits

Passing establishes deterministic local external evidence for the exact
accepted Phase 19 Asset Platform. It does not establish public deployment,
real Vite/Node version compatibility, browser compatibility, accessibility,
performance, security certification, CDN behavior, third-party adapter
compatibility, production readiness, Stable API, 1.0 compatibility, release
readiness, mobile integration, or authority to begin Phase 20.

## Consequences

Phase 19 now has one explicit repeatable acceptance command and one exact local
checkpoint. API-only, CLI, worker, agent, headless, mobile, and no-asset
applications remain first-class and need not install the Asset Platform.

## Related material

- [Phase 19 external asset acceptance](../architecture/phase-19-external-asset-acceptance.md)
- [Asset Platform](../architecture/asset-platform.md)
- [Deterministic asset build](../architecture/deterministic-asset-build.md)
- [Replaceable asset-build adapters](../architecture/replaceable-asset-build-adapters.md)
- [Exact-current asset resolution](../architecture/exact-current-asset-resolution.md)
- [Asset application workflow](../architecture/asset-application-workflow.md)
- [Asset inspection and intelligence](../architecture/asset-inspection-and-intelligence.md)
- [Phase 19 roadmap](../architecture/phase-19-roadmap.md)
- [ADR 0188](0188-optional-asset-platform-and-deterministic-build-boundary.md)
- [ADR 0189](0189-exact-current-asset-resolution-and-browser-delivery-policy.md)
- [ADR 0190](0190-neutral-asset-contract-and-bounded-declaration-vocabulary.md)
- [ADR 0191](0191-deterministic-native-asset-graph-and-build-artifact.md)
- [ADR 0192](0192-replaceable-asset-build-adapter-and-bounded-vite-integration.md)
- [ADR 0193](0193-exact-current-asset-resolution-and-explicit-browser-http-policy.md)
- [ADR 0194](0194-supported-asset-application-workflow-and-explicit-view-integration.md)
- [ADR 0195](0195-visibility-safe-asset-inspection-and-developer-intelligence.md)
