# ADR 0130: Phase 11 External Application-Graph Acceptance and Checkpoint

## Status

Accepted and implemented by Phase 11H.

## Context

Phases 11A through 11G accept and implement the authoritative Application
Graph contract, deterministic IR admission, Composition projection, canonical
artifact handling, bounded inspection, the supported read-only Console
consumer, and optional visibility-safe developer-intelligence evidence. Those
package proofs do not by themselves show that an external consumer can use the
accepted stack as one deterministic, isolated system.

The milestone also needs one exact local checkpoint that commits accepted
content without adopting implementation directives, task reports, temporary
artifacts, or caller-owned residue.

## Decision

### Root-owned external evidence

Phase 11 closes only after the root-owned
`tests/External/ApplicationGraph/run.php` process proves the accepted stack
through real public and Experimental APIs and the supported `kongo` executable.
The harness is repository acceptance evidence. It is not a production API, an
installed application feature, a package, or a runtime Graph consumer.

The process constructs one real Composition artifact in a unique
system-temporary workspace, projects and admits its language-neutral records,
builds and publishes one canonical authoritative Graph artifact, loads and
validates that artifact, consumes its bounded inspection through `kongo`, and
derives and verifies the optional role-addressed aggregate views. It also
exercises the unchanged read-only Chie and Hantei boundaries without adding a
production adapter or cross-package dependency.

### Determinism and currentness

The authoritative Application Graph remains generated, deterministic,
immutable, bounded, and non-executable. Equivalent admitted record order
produces the same canonical Graph. Artifact currentness means exact equality
with the caller-supplied current Graph at the validation boundary. It is not a
global freshness oracle for Composition source, generated wiring, a workspace,
deployment, or a running process.

Inspection and developer-intelligence views are read-only projections. They do
not expose a service locator, dynamic dependency resolution, runtime dispatch,
Graph mutation, or hidden authority. Identifiers, counts, summaries,
citations, diagnostics, and failure tokens retain their accepted disclosure
classifications and bounds.

### Developer-intelligence roles

Kongono Chie explains and Kongono Hantei proves. Neither role executes Graph
nodes or relations, mutates the Graph, infers secrets, grants authorization, or
bypasses structural validation and caller-relative currentness. The optional
`kongono/application-graph-intelligence` package remains
dependency-direction-safe and supplies only Safe aggregate evidence. Its role
token is purpose metadata, not execution authority.

### Process, isolation, and cleanup

The entry process accepts no arguments and emits one canonical LF-terminated
JSON object on success. Failure emits no stdout and only the fixed
`phase11_acceptance_failed` token on stderr. Output contains no temporary or
repository path, rejected input, artifact body, stack trace, environment value,
or secret.

All generated state stays beneath one unique system-temporary root whose leaf
contains a space. The harness removes that root in `finally`, including after a
forced mid-run failure. Repeated and concurrent executions are isolated and
byte-deterministic, retain no framework lifecycle operation, and leave no child
process or repository mutation.

### Tooling and checkpoint

The root script `acceptance:phase11` owns the public acceptance entry. The
fixed Hantei framework-quality profile records the same exact script so profile
and root script graphs cannot diverge.

After all required focused, canonical, historical-regression, filesystem,
dependency-graph, caller-record, staging, and semantic-tree proofs pass, Phase
11H creates exactly one local checkpoint commit containing the authenticated
180-path Phase 11 content set. Every path under `docs/dev_plan/` and
`docs/task_reports/` remains outside the commit. No tag, remote, push, release,
or history rewrite is part of the checkpoint.

## Security and authority consequences

- Graph facts and evidence remain structural and descriptive, never
  authentication, authorization, capability, process, filesystem, network,
  database, deployment, or production authority.
- Private fields and artifact bodies remain outside inspection and
  developer-intelligence output.
- Retrieved or rejected text is inert and cannot enlarge the harness or a
  role's authority.
- The harness adds no model, provider, training, telemetry, network access,
  autonomous modification, self-healing, dynamic container, registry, cache,
  watcher, or runtime Graph execution.
- Phase 12 is not started.

## Consequences

Phase 11 has one external whole-stack proof and one exact local checkpoint.
Direct use of the accepted lower-level packages remains available, and no
production package or dependency edge changes. Kongono remains Experimental,
pre-1.0, and not production-ready.

## Rejected alternatives

- Package tests alone were rejected because they do not prove the supported
  external process and cross-boundary behavior.
- A production application-graph acceptance package was rejected because the
  proof belongs to the repository test boundary.
- Raw Graph or artifact output was rejected because it would bypass bounded
  disclosure contracts.
- A Chie/Hantei production adapter was rejected because Phase 11G deliberately
  leaves role-specific adapters for separately accepted work.
- Newest-file discovery, global-currentness claims, runtime registries, dynamic
  service location, and Graph execution were rejected as unauthorized
  authority.
- Committing directives or reports was rejected because caller records are not
  checkpoint content.

## Related material

- [Phase 11 external acceptance](../architecture/phase-11-external-acceptance.md)
- [Phase 11 roadmap](../architecture/phase-11-roadmap.md)
- [Authoritative Application Graph](../architecture/authoritative-application-graph.md)
- [Application Graph inspection](../architecture/application-graph-inspection-and-consumption.md)
- [Application Graph developer intelligence](../architecture/application-graph-developer-intelligence.md)
- [ADR 0129](0129-visibility-safe-application-graph-developer-intelligence.md)
