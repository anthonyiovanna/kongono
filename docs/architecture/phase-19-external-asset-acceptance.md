# Phase 19 External Asset Acceptance

## Purpose and command

Phase 19H closes the accepted Phase 19 milestone with one root-owned local
fresh-process harness. Run it explicitly with:

```text
composer acceptance:phase19
```

The command is outside `composer quality` and the eight canonical Hantei
checks because it launches bounded local children and creates copied test
applications.

## Trust model

Each success child creates one uniquely named `kongono-phase19-*` workspace
directly beneath the system temporary directory. The harness writes and
removes only its owned tree. Every executable, launch artifact, input,
expected digest, working directory, environment map, deadline, and stream cap
is explicit. Source documents, copied skeleton files, localized locked
packages, build artifacts, and local executables are trusted fixture inputs;
they grant no identity, authentication, authorization, capability, policy,
Graph, credential, network, or deployment authority and are not sandboxed.

Composer in the copied application has networking disabled and receives no
proxy variables. The harness uses no public network, ambient DNS,
non-loopback listener, provider, browser, database, credential source, package
download, Node/npm installation, CDN, deployment system, or external service.

## Native scenario

The real contract, build, resolution, and application packages consume one
explicit complete bounded source set containing every accepted dependency
kind. Repeated plan/build operations produce identical identities and
canonical bytes. Checks are write-free. Exact source-set, graph, content,
build, path, artifact, and SHA-384 integrity evidence stays distinct.
Publication is contained, content-addressed, and no-clobber.

Missing, changed, stale, additional, outside-root, linked, or conflicting
evidence fails closed. Resolution succeeds only for one explicit expected
artifact identity and caller-owned public-base-URL, cache, integrity, and
preload policy. The application explicitly maps resolved metadata into closed
Phase 18 presentation values; neither routing nor View owns asset resolution
or browser policy.

## Adapter scenario and emulator limit

The adapter proof uses the real neutral adapter contract, Vite adapter,
workspace, process runner, manifest translation, output-tree validation, and
neutral artifact validation. A test-owned PHP protocol emulator is supplied as
the explicit executable. It exercises success plus malformed, duplicate,
partial, extra, outside-root, linked, nonzero-exit, stdout/stderr overflow,
timeout, changed-launch, and start-failure behavior. Every process has a finite
deadline and 1,048,576-byte stream caps, and cleanup is attempted exactly once
before a healthy later run is required.

This proves the accepted fixed protocol and lifecycle without installing Node,
npm, Vite, plugins, or packages. It is not a claim of compatibility with a
real Vite or Node release.

## Application and inspection scenarios

The harness copies `asset-minimal` into its owned workspace, installs only
local locked packages offline, and compares direct and Composer forms of
`asset:build`, `asset:check`, and `asset:resolve`. Their accepted results are
equivalent and no runtime build, discovery, ambient manifest, stale fallback,
or service location is introduced.

One Phase 19G inspection reconstructs exact supplied current evidence entirely
in memory. Chie-, Hantei-, and Oshie-addressed views verify independently while
role execution remains `not-performed`, Graph relationships remain
`not-established`, and projections remain `constructed-not-recorded`.
Inspection performs no build, resolution, loading, networking, repair,
recording, export, or certification.

## Output, failure, limits, and cleanup

Success emits exactly one compact LF-terminated line:

```text
{"status":"ok","profile":"kongono-phase19-asset-platform","case_count":N,"cleanup":"complete"}
```

`N` is a positive observed case count and is not fixed in documentation.
Successful stderr is empty. Any failure exits nonzero and emits only:

```text
Kongono Phase 19 external acceptance failed.
```

Adversarial literals, exception messages, traces, repository or temporary
paths, command lines, source/output values, URLs, nonpublic identities or
digests, environment data, headers, and bodies never enter public output,
documentation, or harness-created repository content. Source structures,
files, outputs, processes, durations, streams, and evidence are bounded by the
accepted APIs and harness.

The parent requires complete retirement after ordinary failure, malformed
output, stream overflow, and timeout, followed by a healthy run. It compares
repository fingerprints and temporary residue before and after repeated
sequential and concurrent children. A cleanup failure is terminal and visible
only through the fixed public failure contract. No copied application, output,
workspace, vendor tree, cache, log, marker, socket, child, or temporary
artifact may remain or become Git-visible.

## Regression and checkpoint meaning

This command is the root-owned composition regression above package unit and
integration tests. [ADR 0196](../adr/0196-phase-19-external-asset-acceptance-and-checkpoint.md)
accepts it. Phase 19A-H are Accepted and Phase 19 is closed by the checkpoint
subject `Complete Kongono Phase 19 asset platform milestone` with parent
`a04b1ae491cb9c52eb5bd32c37f8524fcd683c74`. The post-commit report records
the resulting commit and tree identities.

Topology remains 102 packages / 435 local production edges / acyclic, with PHP
inventories 2,026 production / 1,055 package-test / 100 root-test.

## Non-claims

Passing does not establish public deployment, real Vite/Node version
compatibility, browser compatibility, accessibility, performance, security
certification, CDN behavior, third-party adapter compatibility, production
readiness, Stable API, 1.0 compatibility, release readiness, mobile
integration, or authority to begin Phase 20.

## Related material

- [Asset Platform](asset-platform.md)
- [Deterministic asset build](deterministic-asset-build.md)
- [Replaceable asset-build adapters](replaceable-asset-build-adapters.md)
- [Exact-current asset resolution](exact-current-asset-resolution.md)
- [Asset application workflow](asset-application-workflow.md)
- [Asset inspection and intelligence](asset-inspection-and-intelligence.md)
- [Phase 19 roadmap](phase-19-roadmap.md)
