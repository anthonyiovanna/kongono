# ADR 0138: Phase 12 External Extension Platform Acceptance and Checkpoint

- Status: Accepted
- Date: 2026-08-30
- Scope: Phase 12H repository acceptance evidence and checkpoint closure

## Context

Phases 12B-12G implement separate Experimental boundaries for declarations,
deterministic artifacts, selection and admission, attributable Application
Graph IR, explicit grants, generated ordinary PHP wiring, the bounded trusted
in-process host, and visibility-safe inspection. Package-local tests do not by
themselves prove that these accepted parts operate coherently through the real
supported command edge without widening any component's authority.

## Decision

Phase 12 closes only after a root-owned external process harness proves the
accepted extension stack from one isolated system-temporary workspace and the
authenticated Phase 12 content is recorded in one exact local checkpoint.
The harness is repository acceptance evidence. It is not a production API,
installed application feature, extension runtime, or new authority source.

The harness uses real package APIs and the real `vendor/bin/kongo
extension:inspect` edge. It proves deterministic evidence, caller-relative
currentness, explicit selection and conflict handling, attributed neutral Graph
IR, explicit capability decisions, generated typed wiring, exact one-file host
loading, bounded lifecycle and cleanup, detached aggregate inspection, and
Chie/Hantei/Oshie evidence views. It also proves canonical success and failure
output, disclosure bounds, repeatability, concurrency, repository no-write,
temporary cleanup, and process cleanup.

Declaration evidence is not admission. A capability request is not a grant.
Graph IR is not an authoritative Graph or execution authority. Visibility is
not authorization. Generated wiring remains inert ordinary PHP until the
explicit host path loads exactly one validated file. Trusted in-process PHP is
not sandboxed.

Inspection validates detached structure and identity binding only. It does not
establish caller-relative currentness, host state, execution, health, trust,
installation, authorization, or production readiness. Chie, Hantei, and Oshie
receive bounded evidence; they do not execute extensions or authorize their own
claims.

Every directive and task report remains caller-owned evidence outside the
checkpoint. The checkpoint is one local commit only. Phase 12H authorizes no
push, tag, release, second commit, or Phase 13 work.

## Consequences

The root gains one deterministic `composer acceptance:phase12` command and the
fixed eight-check Hantei profile records that script in its closed root-script
inventory without adding a ninth check. Production packages, package
manifests, dependencies, the lockfile, and package topology remain unchanged.

Sandboxed or external extension execution, installation/update/removal,
marketplace or distribution behavior, publisher trust or signing, remote
networking, Agent Principal runtime, frontend extension APIs, Stable/1.0, and
production-readiness certification remain outside Phase 12.

## Related material

- [Phase 12 external acceptance](../architecture/phase-12-external-acceptance.md)
- [Phase 12 roadmap](../architecture/phase-12-roadmap.md)
- [Extension Platform](../architecture/extension-platform.md)
- [ADR 0137](0137-visibility-safe-extension-inspection-and-developer-intelligence-evidence.md)
