# Phase 12 Roadmap

> **Current boundary:** Phases 12A-12H are implemented to their accepted
> boundaries. Phase 12H adds only root-owned external acceptance evidence and
> the exact local Phase 12 checkpoint. The production graph remains 63 packages
> / 226 acyclic local edges.

## Purpose and current status

Phase 11 is complete at checkpoint
`081ae7f50ad4081bb3890a63a652a0bf782a6da1`. Its authoritative Application
Graph substrate remains descriptive and grants no extension authority.

Phase 12A accepts [ADR 0131](../adr/0131-governed-extension-platform-and-attributable-contribution-boundary.md)
and reconciles the primary [Extension Platform guide](extension-platform.md).
Phase 12B accepts [ADR 0132](../adr/0132-neutral-versioned-extension-contract-foundation.md)
and implements `kongono/extension-contract`, depending only on API Metadata and
Diagnostics. Phase 12C accepts [ADR 0133](../adr/0133-deterministic-extension-declaration-artifact.md)
and implements `kongono/extension-build`, depending only on API Metadata,
Diagnostics, Extension Contract, and PHP. The production graph is exactly 59
packages and 206 acyclic local production dependency edges. No public loader,
host, registry, capability grant, Application Graph integration, or extension
runtime exists at that historical checkpoint. Phases 12D-12G add their
separately accepted boundaries, and Phase 12H adds only external repository
evidence under [ADR 0138](../adr/0138-phase-12-external-extension-platform-acceptance-and-checkpoint.md).

## Fixed dependency order

| Phase | Boundary |
| --- | --- |
| `12A` | **Historical/complete.** Architecture, doctrine, terminology, security boundary, and fixed roadmap; documentation only. |
| `12B` | **Historical/current foundation.** Neutral versioned extension identity, declaration, capability-request, contribution, provenance, visibility, and lifecycle contracts; no admission, loading, or execution. |
| `12C` | **Implemented/current.** Deterministic extension declaration artifact build, caller-relative exact-current validation, and atomic no-clobber publication; no public loader or host. |
| `12D` | **Implemented/current.** Deterministic application-owned selection, admission, conflict handling, and extension-to-Application-Graph IR contribution projection; no execution authority. |
| `12E` | **Implemented/current.** Explicit application-owned capability grant plan and generated ordinary PHP wiring for the selected trusted/in-process profile; no dynamic discovery, service location, loading, or execution. |
| `12F` | **Implemented/current.** Bounded trusted/in-process Extension Host lifecycle with exact one-file loading, honest non-sandboxed semantics, cleanup, isolation, leases, retirement, and failure preservation. |
| `12G` | **Implemented/current.** Bounded read-only inspection, supported CLI consumption, and visibility-safe Chie/Hantei/Oshie evidence; no extension mutation or self-authorization. |
| `12H` | **Implemented/current closure.** Root-owned external acceptance and exact local Phase 12 checkpoint; no production change or new authority. |

The table is dependency order, not parallel authorization. Completion of one
row does not begin the next. Each later row requires a separately authenticated
architect directive.

## Current versus planned behavior

Phases 12A-H are implemented to their stated boundaries. Phase 12C provides one
canonical declaration artifact, exact identities, strict reconstruction,
caller-relative currentness, and atomic no-clobber publication. It provides no
public loader, selection, admission, conflict resolution, grant, Graph fact,
Composition wiring, host, executable extension, or runtime behavior.

Phase 12D is implemented to its stated admission boundary. Phase 12E adds
explicit grants and deterministic generated wiring evidence. Phase 12F now adds
the bounded trusted/in-process host lifecycle for one exact generated file.
Phase 12G provides detached aggregate inspection and the supported read-only
command edge. Phase 12H provides only the root-owned external acceptance
harness described in [Phase 12 external acceptance](phase-12-external-acceptance.md).
That harness is test evidence, not production behavior.

## Outside the Phase 12 boundary

Sandboxed and external execution profiles, marketplace and distribution,
package installation/update/removal, signing and publisher trust, remote
networking, Agent Principal runtime, frontend/UI extension contracts,
production-readiness certification, and Stable/1.0 API remain outside Phase 12
unless a later architect directive changes that boundary.

## Preserved authority boundaries

- An installed package, declaration, signature, artifact, Graph fact, or
  extension self-claim grants no authority.
- The Application Graph describes admitted structure; it does not load,
  execute, grant, authorize, approve a publisher, or monitor health.
- Trusted/in-process PHP is not sandboxed by framework capability ports.
- Principal, Security, Observability, Graph, Chie, Hantei, and Oshie boundaries
  remain independent.
- Kongono remains pre-1.0 and not production-ready.

## Related material

- [ADR 0131](../adr/0131-governed-extension-platform-and-attributable-contribution-boundary.md)
- [ADR 0132](../adr/0132-neutral-versioned-extension-contract-foundation.md)
- [ADR 0133](../adr/0133-deterministic-extension-declaration-artifact.md)
- [ADR 0135](../adr/0135-explicit-extension-capability-grants-and-generated-wiring.md)
- [ADR 0136](../adr/0136-bounded-trusted-in-process-extension-host-lifecycle.md)
- [ADR 0137](../adr/0137-visibility-safe-extension-inspection-and-developer-intelligence-evidence.md)
- [ADR 0138](../adr/0138-phase-12-external-extension-platform-acceptance-and-checkpoint.md)
- [Phase 12 external acceptance](phase-12-external-acceptance.md)
- [Trusted In-Process Extension Host](trusted-in-process-extension-host.md)
- [Extension Platform](extension-platform.md)
- [Extension Declaration Artifact](extension-declaration-artifact.md)
- [Phase 11 roadmap](phase-11-roadmap.md)
- [Authoritative Application Graph](authoritative-application-graph.md)
- [Agent model](agent-model.md)

Phase 12B construction proves only local grammar, bounds, types, uniqueness,
and canonical ordering. It does not prove admission, currentness, or trust.
Requests are not grants, contributions are not Graph facts, visibility is not
authorization, lifecycle is not hosting, and category is not a security
decision. Phase 12C artifacts remain declaration evidence rather than
admission, trust, grants, Graph facts, loaded code, or execution authority.
Phase 12D is implemented in `kongono/extension-admission`; its output remains
IR awaiting separately owned Graph construction. That topology was 60 packages
/ 212 local edges. Phase 12E is implemented as explicit grant planning and
inert generated extension wiring in a separate build-only package. Phase 12F is
implemented in `kongono/extension-host` with exact loading and isolated explicit
lifecycle; trusted PHP is not sandboxed. The current topology is 62 packages /
220 local edges. Phase 12G is implemented in `kongono/extension-inspection`
with detached two-file structural validation, Safe aggregate role views,
independent verification, and `extension:inspect`. The current topology is 63
packages / 226 local edges. Phase 12H adds only root-owned external process
evidence and the exact local checkpoint. Kongono remains Experimental and
pre-1.0.
