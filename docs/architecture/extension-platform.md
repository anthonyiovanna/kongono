# Extension Platform

Phase 12 is closed by the root-owned evidence in
[ADR 0138](../adr/0138-phase-12-external-extension-platform-acceptance-and-checkpoint.md)
and [Phase 12 external acceptance](phase-12-external-acceptance.md). That
evidence changes no production package or authority boundary; trusted
in-process PHP remains non-sandboxed and inspection remains detached evidence.

> **Current boundary:** Phases 12A-12H are implemented to their accepted
> boundaries. Phase 12H adds external acceptance evidence only; the production
> graph remains 63 packages / 226 acyclic local edges.

## Purpose and context

Kongono's Extension Platform is the future application-owned governance
boundary for explicitly selected, versioned, attributable contribution units.
It supports extension without treating installation, discovery, a manifest, a
signature, or Graph presence as permission.

Phase 11 is complete at checkpoint
`081ae7f50ad4081bb3890a63a652a0bf782a6da1` and supplies the authoritative
Application Graph substrate. Phase 12A accepts [ADR 0131](../adr/0131-governed-extension-platform-and-attributable-contribution-boundary.md)
and the [fixed Phase 12 roadmap](phase-12-roadmap.md). It implemented no
extension package or runtime. Phase 12B now implements the neutral
[Extension Contract foundation](extension-contract-foundation.md) under
[ADR 0132](../adr/0132-neutral-versioned-extension-contract-foundation.md).
The package depends only on API Metadata and Diagnostics. Phase 12C adds the
separate [Extension Declaration Artifact](extension-declaration-artifact.md)
package above it. Neither phase adds selection, admission, a capability grant,
host, public loader, registry, executable
extension, or runtime.

## Extension identity and categories

An **extension** is an explicitly identified, versioned, attributable
contribution unit selected by an application. An installed package is not an
extension by implication, and ambient discovery never enables one.

Kongono retains four architectural categories:

1. **Core** is first-party framework capability governed and released with
   Kongono, not code dynamically installed or loaded by an Extension Host.
2. **Trusted/in-process** is application-approved PHP executed in the
   application process. It has the full technical power of that PHP process;
   framework capability ports cannot sandbox arbitrary language behavior.
3. **Sandboxed** is future isolated execution under a separately selected and
   proven sandbox profile. Phase 12 selects no sandbox technology.
4. **External** is future out-of-process integration under a separately
   selected authenticated protocol and process boundary. Phase 12A selects no
   protocol or host profile.

Category is not trust evidence, installation authority, authorization, a
capability grant, or a security decision. It cannot elevate itself.

## Explicit application selection

Future use begins with explicit application-owned selection and exact-current
inputs. Runtime directory scanning, Reflection discovery, mutable global
registries, Composer plugin activation, package-name heuristics,
environment-driven auto-enable, network discovery, and newest-file selection
are prohibited.

Generated ordinary PHP constructor wiring remains the preferred future
in-process composition mechanism. The Extension Platform is not a service
locator, dynamic dependency-injection container, or hidden composition root.

## Declaration, admission, grant, and execution

- **Declaration** is inert extension-owned evidence about identity,
  requirements, requested capabilities, lifecycle, and contributions.
- **Admission** is deterministic framework/application validation of those
  claims for one exact state.
- **Grant** is explicit application-owned approval of one exact bounded
  capability.
- **Execution** is a separately governed host invoking already admitted and
  granted behavior under an explicit lifecycle.

These facts never collapse into one another. A manifest, installed package,
valid signature, Graph fact, generated artifact, Chie answer, Hantei evidence,
or extension self-claim grants no authority.

## Capabilities and least privilege

Future capabilities are typed, versioned, purpose-bounded, attributable,
explicitly granted by the application, and denied by default. Requesting a
capability is not receiving one. No grant implies filesystem, database, secret,
shell, process, network, deployment, identity, authorization, telemetry, agent,
or other authority that was not explicitly approved.

Capability ports govern Kongono-supplied collaboration. They do not pretend to
sandbox trusted in-process PHP. Strong isolation requires a later sandboxed or
external execution boundary.

## Attributable contributions and the Graph

Future contributions enter through versioned language-neutral IR claims with
extension identity, exact source state, provenance, visibility, and schema.
They are inputs, not automatic authoritative Graph facts. Existing
deterministic admission, conflict, currentness, publication, and disclosure
rules remain in force.

The Graph describes accepted structure. It does not load code, execute an
extension, grant a capability, prove package integrity, approve a publisher,
authorize a Principal, or monitor health. Omitted and invisible facts remain
omitted; consumers cannot infer them as false.

## Security, identity, observability, and developer intelligence

An extension is not a Principal merely because it is an extension. If a later
design needs a Principal, existing identity, authentication, and authorization
boundaries apply independently. An Agent Principal is distinct from an
extension and from Kongono Chie, Hantei, and Oshie.

Operational evidence uses separately accepted explicit Observability
boundaries. Telemetry grants no authority; health evidence is not authorization
or correctness.

Chie may later explain accepted evidence, Hantei may independently verify a
bounded subject, and Oshie may teach accepted material. None may install,
admit, grant, load, execute, update, remove, or trust an extension.

## Lifecycle

Future hosted work must validate before acquire, own exact start/operation/
retire lifecycles, clean up on every path, preserve primary and cleanup
failures, provide idempotent terminal cleanup where promised, retain no
per-operation state after completion, and fail closed when retirement leaves
state uncertain. Phase 12A selects no host API or runtime implementation.

## Supply chain and distribution

Package format, Composer integration, signing, publisher identity,
transparency, provenance attestation, distribution, marketplace, download,
install, update, rollback, removal, revocation, vulnerability response, and
compatibility policy remain separate future decisions. A signature, if later
selected, proves only its defined signed statement; it does not prove safety,
correctness, permission, or trustworthiness.

Sandboxed and external execution profiles, distribution, installation and
updates, signing and publisher trust, remote networking, Agent Principal
runtime, frontend/UI extension contracts, production-readiness certification,
and Stable/1.0 API remain outside Phase 12 unless later authority changes the
boundary.

## Stability

The secure governed Extension Platform direction and Phase 12A authority
separation are **LOCKED**. Phase 12A accepts architecture, not a Stable API.
Future production extension types are **EXPERIMENTAL** unless later authority
states otherwise. Kongono remains pre-1.0 and not production-ready.

## Related concepts

- [ADR 0131](../adr/0131-governed-extension-platform-and-attributable-contribution-boundary.md)
- [Phase 12 roadmap](phase-12-roadmap.md)
- [Microkernel](microkernel.md)
- [Authoritative Application Graph](authoritative-application-graph.md)
- [Agent model](agent-model.md)
- [ADR 0009](../adr/0009-secure-extension-platform.md)

## Implemented neutral contract foundation

Phase 12B values are inert evidence. Local construction proves only exact
grammar, bounds, types, uniqueness, and canonical ordering; it proves no
admission, currentness, trust, package integrity, or publisher identity. A
capability request is not a grant, a contribution is not an authoritative
Application Graph fact, visibility is not authorization or declassification,
lifecycle is not hosting or execution, and category is not a security decision.
No Application Graph dependency or integration exists.

Phase 12C now preserves those exact fields in deterministic canonical evidence,
reconstructs them during strict validation, compares exact currentness only
with caller-supplied declarations, and publishes content-addressed bytes
atomically without clobber. The artifact is not admission, trust, a grant, a
Graph fact, loaded code, hosting, or execution authority, and no public loader
exists. Phase 12D now implements exact application-owned selection, explicit
contribution-conflict winners, attributable `kongono.extension/1` IR, and
complete structural Graph admission without returning Graph truth or granting
authority. That checkpoint topology was 60 packages / 212 local edges. Phase
12E adds explicit application-owned grants and inert generated extension wiring
without loading or execution. Phase 12F adds exact one-file loading plus bounded
application and operation lifecycle in `kongono/extension-host`; it does not
sandbox trusted PHP or add discovery, external execution, marketplace, Graph,
Security, or CLI authority. The current topology is 62 packages / 220 local
edges. Phase 12G now provides detached Safe aggregate inspection, role-addressed
Chie/Hantei/Oshie evidence, and read-only CLI consumption. It neither loads
generated PHP nor establishes currentness, host state, execution, trust, or
authority. The current topology is 63 packages / 226 local edges; Phase 12H
adds only root-owned external evidence and the exact local checkpoint. Kongono
remains Experimental and pre-1.0.
