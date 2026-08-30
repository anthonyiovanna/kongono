# Phase 11 External Application-Graph Acceptance

> **Current boundary:** Phase 11H adds root-owned external acceptance and the
> exact local Phase 11 checkpoint. It adds no production Graph behavior,
> package, dependency, runtime integration, or Phase 12 capability.

## Purpose and context

The Phase 11 package suites prove their individual contracts. The external
harness proves that the real accepted boundaries also compose as one bounded
developer-facing system from a child-process edge. It covers Composition,
language-neutral IR, authoritative Graph construction, artifact publication
and loading, bounded inspection, the supported `kongo` command, optional
developer-intelligence evidence, and unchanged Chie and Hantei read-only
behavior.

The harness is test evidence owned by the repository root. Applications do not
install it, production packages do not depend on it, and its helper classes are
not public framework APIs.

## Entry and output contract

Run:

```text
composer acceptance:phase11
```

The script invokes `tests/External/ApplicationGraph/run.php` through PHP. The
entry accepts no arguments. Success writes exactly one canonical JSON object,
followed by one LF, with this fixed schema:

```json
{"status":"ok","profile":"kongono-phase11-application-graph","case_count":1,"cleanup":"complete"}
```

`case_count` is the positive deterministic number of completed matrix cases;
the example value only illustrates the type. No timing, process identifier,
random token, absolute path, or machine-specific value enters the document.

Failure exits nonzero, writes no stdout, and writes only:

```text
phase11_acceptance_failed
```

with one final LF to stderr.

## Workspace and process safety

Each run creates one unique system-temporary root whose leaf contains a space.
The root cannot overlap the repository. All fixture sources, Composition input,
artifacts, malformed candidates, and aliases remain inside that temporary
boundary. Publication must return a contained content-addressed ordinary file.

Cleanup is unconditional in `finally`. The harness proves ordinary success,
forced mid-run failure, repeated execution, and concurrent execution. It waits
for every child, closes every stream, retains no active framework lifecycle
operation, and verifies that no temporary root remains.

The harness fingerprints Git status, caller records, index, configuration,
refs, history, remotes, tags, and the lockfile before and after execution.
Fingerprinting disables optional Git index refresh. The harness never stages,
commits, writes generated state into the repository, or modifies a caller
record.

## Acceptance matrix

The matrix uses the real accepted APIs and executable to prove:

1. valid Composition v2 input produces the authoritative Graph through the
   accepted projection boundary;
2. reversed language-neutral record order preserves canonical Graph bytes and
   identity, while raw Composition provenance remains exact;
3. node and relation ordering is canonical and duplicates, missing endpoints,
   and invalid identities fail visibly;
4. Graph artifact bytes, artifact identity, Graph identity, and bounded
   inspection are deterministic;
5. publication is contained, atomic, content-addressed, idempotent, and leaves
   no temporary publication residue;
6. explicit loading accepts valid state and rejects missing, malformed,
   truncated, oversized, tampered, and link/reparse-mediated state;
7. `loadCurrent` accepts the matching caller-supplied Graph and rejects a
   different Graph without claiming global freshness;
8. inspection exposes only the accepted identities, version, totals, and full
   closed-vocabulary counts;
9. the real `kongo application-graph:inspect` machine and human paths have
   deterministic output, exact stdout/stderr separation, fixed exit behavior,
   and no mutation;
10. role-addressed Chie, Hantei, and Oshie views contain identical shared Graph
    evidence and only the role token differs;
11. the independent verifier accepts exact evidence and rejects identity,
    role, and semantic-token mismatches using bounded failure tokens;
12. Chie returns a current bounded cited explanation without gaining Graph
    execution or mutation authority;
13. Hantei remains a bounded independent verifier and does not disclose an
    unavailable hostile subject;
14. the Graph and intelligence public surfaces contain no service location,
    dynamic dependency resolution, role execution, runtime dispatch, mutation,
    network access, or authorization mechanism;
15. private values, temporary and repository roots, rejected input, artifact
    bodies, exception details, environment values, host/user values, traces,
    and terminal escapes do not enter accepted output;
16. the harness's own failure, repeatability, concurrency, repository
    preservation, cleanup, child-process, and lifecycle contracts are exact.

Package unit and architecture suites retain the detailed minimum, maximum,
empty, boundary-adjacent, malformed, duplicate, unknown, tampered, stale, and
unsupported-value proofs for the accepted APIs. The external harness composes
those actual implementations; it does not reimplement their algorithms.

## Currentness and disclosure meaning

Artifact currentness is equality with the Graph explicitly supplied to
validation. It does not inspect or attest to current Composition source, PHP
source, generated wiring, a workspace, a deployment, or a running process.

Artifact contents remain application-sensitive. Inspection and optional
developer-intelligence output contain only Safe bounded aggregates. Omitted
facts are unavailable or out of scope, never false. Identities, counts,
citations, diagnostics, role tokens, and verification results grant no
authorization or operational authority.

## Chie, Hantei, and optional intelligence

`kongono/application-graph-intelligence` remains optional and depends only on
its accepted inward contracts. It does not depend on Chie, Hantei, or Oshie.
The external harness may coordinate their separate read-only behavior as proof,
but it creates no production adapter or response-schema change.

Kongono Chie explains. Kongono Hantei proves. Neither executes Graph roles,
loads services, dispatches runtime work, infers secrets, repairs evidence,
mutates state, or bypasses validation/currentness. A verifier PASS is bounded
comparison evidence only.

## Checkpoint boundary

The Phase 11 checkpoint contains exactly the authenticated 180 content paths:
the accepted 174-path Phase 11A-11G set plus the six Phase 11H content paths.
All implementation directives and task reports remain untracked and outside the
commit. The checkpoint creates no release, tag, remote, push, branch, signing
change, hook change, or history rewrite.

## Deliberate exclusions

Phase 11H adds no production API, package, dependency, Graph domain, query,
traversal, index, diff, visualization, registry, cache, watcher, daemon,
container, runtime execution, telemetry, model/provider call, training,
autonomous modification, self-healing, deployment, or production behavior.
Phase 12 is not started.

## Related material

- [ADR 0130](../adr/0130-phase-11-external-application-graph-acceptance-and-checkpoint.md)
- [Phase 11 roadmap](phase-11-roadmap.md)
- [Authoritative Application Graph](authoritative-application-graph.md)
- [Artifact and currentness](application-graph-artifact-and-currentness.md)
- [Inspection and consumption](application-graph-inspection-and-consumption.md)
- [Developer-intelligence evidence](application-graph-developer-intelligence.md)
