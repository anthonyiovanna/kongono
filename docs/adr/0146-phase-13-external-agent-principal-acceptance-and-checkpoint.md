# ADR 0146: Phase 13 external Agent Principal acceptance and checkpoint

- Status: Accepted
- Date: 2026-08-31
- Scope: Phase 13H external acceptance and local checkpoint only

## Context

Phases 13B through 13G implement the Experimental neutral contract,
declaration artifact, admission evidence, provider-neutral runtime,
single-profile application workflow, and visibility-safe inspection boundaries.
Their package tests prove each boundary locally, but Phase 13 is not closed
until one root-owned external process proves that the accepted stack composes
without widening any package's authority.

The acceptance proof must remain evidence rather than production behavior. It
must also preserve the Phase 12 checkpoint and the complete caller-managed
Phase 13 delta until the exact Phase 13 checkpoint is created.

## Decision

Accept `tests/External/AgentPrincipal/run.php` as the sole Phase 13 external
acceptance entry point. The root harness uses production autoloading and
production Phase 13 APIs with root-owned fixture collaborators. Filesystem
artifacts exist only in a fresh, unique temporary workspace outside the
repository. The harness is deterministic, offline, bounded to 1 MiB per
process stream, cleanup-complete, disclosure-safe, and repository-preserving.

The harness is test evidence only. It grants no identity, authentication,
authorization, delegation, consent, capability, visibility, provider, model,
tool, or external-effect authority. Declaration, profile, and currentness
inputs remain explicit caller-owned evidence. Admission decisions and authority
plans remain inert until the runtime rechecks current authority and invokes an
explicit supplied port.

The runtime remains one synchronous operation with nine explicit budgets,
cancellation and monotonic-time checkpoints, at most one proposal and one
exact capability invocation, reverse cleanup, and deterministic receipts.
Generated PHP and in-process collaborators remain trusted and are not
sandboxed. Application wiring is explicit, exact-current, content-addressed,
and loaded from one supplied contained file; it is not service location,
ambient discovery, a container, or a registry.

Inspection remains read-only Safe aggregate evidence. It is not live
monitoring, repair, certification, recording, delivery, durability, or a grant.
Optional Graph context is separately supplied bounded evidence and establishes
no agent-to-Graph relationship, currentness, authority, or live fact.
Observation and Security Audit projections are inert and unrecorded. Chie,
Hantei, and Oshie receive separately addressed evidence with the same
underlying facts; Agent Inspection neither depends on nor executes those roles.

No provider SDK, network, account, command, profile registry, retained memory
or session, autonomous loop, background work, deployment manager, marketplace,
remote service, Stable/1.0 claim, or Phase 14 behavior is added. Kongono remains
Experimental, pre-1.0, and usable without AI, a provider, an account, or a
network.

Phase 13 closes with one normal local checkpoint commit containing only the
authenticated Phase 13 content set. Directives and reports remain excluded.
The checkpoint authorizes no push, tag, release, publication, deployment,
second commit, or Phase 14 work.

## Consequences

- `composer acceptance:phase13` executes the external proof.
- The fixed Hantei profile mirrors that script without changing its eight
  checks.
- The production graph remains 69 packages, 260 local production edges, and
  acyclic.
- Production PHP, package manifests, dependencies, and `composer.lock` remain
  unchanged.
- The harness owns only root test fixtures and temporary external-process
  state; cleanup failure is a failed acceptance result.
- A passing result applies only to the exact committed source, environment,
  command, and evidence. It is not security certification or external truth.

## Alternatives rejected

- Package-local acceptance was rejected because it could reuse package test
  support and would not prove the public production composition boundary.
- A provider-backed or networked proof was rejected because Phase 13 selects
  no provider and Kongono must remain usable offline.
- A long-running agent host was rejected because the accepted runtime is one
  synchronous operation with no retained session or background authority.
- Committing directives or reports was rejected because they are caller
  records, not checkpoint content.

## Related material

- [Phase 13 external acceptance](../architecture/phase-13-external-acceptance.md)
- [Phase 13 roadmap](../architecture/phase-13-roadmap.md)
- [Agent Principal Platform](../architecture/agent-principal-platform.md)
- [ADR 0139](0139-governed-agent-principals-and-deterministic-authority-boundary.md)
- [ADR 0145](0145-visibility-safe-agent-principal-inspection-and-bounded-evidence.md)
