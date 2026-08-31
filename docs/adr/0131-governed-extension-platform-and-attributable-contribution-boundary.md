# ADR 0131: Governed Extension Platform and Attributable Contribution Boundary

## Status

Accepted

## Date

2026-08-30

## Context

Kongono has long accepted the direction of a secure Extension Platform, but the
earlier direction did not define a complete boundary between extension claims,
application approval, Application Graph facts, capability authority, and
execution. Phase 11 now provides an authoritative, deterministic,
visibility-aware Application Graph substrate. That substrate must not turn an
installed package, manifest, contribution, or Graph fact into authority.

Kongono also needs honest execution semantics. Trusted PHP running in the
application process has the technical power of that process. Framework
capability ports can bound what Kongono intentionally supplies, but they cannot
sandbox arbitrary PHP language behavior. Strong isolation requires a separately
selected and proven sandboxed or external boundary.

Phase 12 therefore requires one security-first architectural boundary before
any extension contract, artifact, selection mechanism, capability plan, host,
or inspection surface is implemented.

## Decision

Phase 12A accepts the governed Extension Platform architecture, terminology,
security boundary, and fixed Phase 12A-H dependency order. This phase is
documentation only. It creates no extension package, runtime, public API,
manifest, schema, loader, host, registry, marketplace, Composer plugin,
sandbox, executable extension, capability adapter, signing system, update
system, or Graph contribution API.

An **extension** is an explicitly identified, versioned, attributable
contribution unit selected by an application. A package is not an extension
merely because it is installed, and an extension is never enabled through
ambient scanning or inference.

### Extension categories

Kongono retains four architectural categories:

1. **Core** is first-party framework capability governed and released with
   Kongono. Core is not dynamically installed or loaded by an Extension Host.
2. **Trusted/in-process** is application-approved PHP code executed in the
   application process. It has the full technical power of that process;
   Kongono capability grants do not sandbox arbitrary PHP behavior.
3. **Sandboxed** is future isolated execution under a separately selected and
   proven sandbox profile. Phase 12 selects no sandbox technology.
4. **External** is future out-of-process integration under a separately
   selected authenticated protocol and process boundary. Phase 12A selects no
   protocol or host profile.

Category is descriptive. It is not trust evidence, installation authority,
authorization, a capability grant, or a security decision, and it cannot
elevate itself.

### Explicit application selection

Future extension use begins with explicit application-owned selection and
exact-current inputs. Runtime directory scanning, Reflection discovery,
mutable global registries, Composer plugin activation, package-name heuristics,
environment-driven auto-enable, network discovery, and newest-file selection
are prohibited.

Generated ordinary PHP constructor wiring remains the preferred future
in-process composition mechanism. The Extension Platform must not become a
service locator, dynamic dependency-injection container, hidden composition
root, or ambient registry.

### Declaration, admission, grant, and execution

The platform keeps four facts separate:

- **Declaration** is inert extension-owned evidence about identity,
  requirements, requested capabilities, lifecycle, and contributions.
- **Admission** is deterministic framework/application validation of those
  claims for one exact state.
- **Grant** is explicit application-owned approval of one exact bounded
  capability.
- **Execution** is a separately governed host invoking behavior that has
  already been admitted and granted under an explicit lifecycle.

A manifest, installed package, valid signature, Graph fact, generated artifact,
Chie answer, Hantei evidence, or extension self-claim grants no authority.

### Capabilities and least privilege

Future capabilities are typed, versioned, purpose-bounded, attributable,
explicitly granted by the application, and denied by default. A request and a
grant are different facts. No capability implies filesystem, database, secret,
shell, process, network, deployment, identity, authorization, telemetry, agent,
or other authority that was not explicitly granted.

Framework capability ports do not pretend to sandbox trusted in-process PHP.
Strong isolation requires a later sandboxed or external execution boundary.

### Contributions and the authoritative Application Graph

Future extension contributions enter through versioned language-neutral IR
claims carrying extension identity, exact source state, provenance, visibility,
and schema. They are inputs, not automatically authoritative Graph facts. The
existing deterministic admission, conflict, currentness, publication, and
disclosure rules remain in force.

The Graph describes accepted structure. It does not load code, execute an
extension, grant a capability, prove package integrity, approve a publisher,
authorize a Principal, or monitor health. Omitted or invisible facts remain
omitted and cannot be inferred as false.

### Independent authority boundaries

An extension is not a Principal merely because it is an extension. If a later
design needs a Principal, the existing identity, authentication, and
authorization boundaries apply independently. An Agent Principal is distinct
from an extension and from Kongono Chie, Hantei, and Oshie. Phase 12A
implements no Agent Principal behavior.

Extension operational evidence uses separately accepted explicit Observability
boundaries. Telemetry grants no authority, and health evidence is neither
authorization nor correctness.

Chie may later explain accepted extension evidence, Hantei may independently
verify a bounded subject, and Oshie may teach accepted material. None may
install, admit, grant, load, execute, update, remove, or trust an extension.

### Lifecycle

Future hosted work must use an explicit bounded lifecycle suitable for
persistent runtimes: validate before acquire; exact start, operation, and retire
ownership; cleanup on every path; preservation of primary and cleanup failures;
idempotent terminal cleanup where promised; no per-operation state retained
after completion; and fail-closed retirement when state becomes uncertain.

Phase 12A selects no host API or runtime implementation.

### Supply chain and distribution

Package format, Composer integration, signing, publisher identity,
transparency, provenance attestation, distribution, marketplace, download,
install, update, rollback, removal, revocation, vulnerability response, and
compatibility policy remain separate future decisions. A future signature
would prove only its precisely defined signed statement; it would not prove
safety, correctness, permission, or trustworthiness.

### Stability and current reality

Phase 11 is complete at checkpoint
`081ae7f50ad4081bb3890a63a652a0bf782a6da1`. Phase 12A accepts architecture and
doctrine, not a Stable API. The production graph remains exactly 57 packages
and 201 acyclic local production dependency edges. No extension package or
runtime exists. Phase 12B is the next planned row and has not started.

Kongono remains pre-1.0 and not production-ready. Future extension production
types are Experimental unless a later authenticated phase explicitly states
otherwise.

## Fixed Phase 12 sequence

| Phase | Boundary |
| --- | --- |
| `12A` | Architecture, doctrine, terminology, security boundary, and fixed roadmap; documentation only. |
| `12B` | Neutral versioned extension identity, declaration, capability-request, contribution, provenance, visibility, and lifecycle contracts; no loading or execution. |
| `12C` | Deterministic extension declaration/artifact build, exact-current validation, and no-clobber publication; no host. |
| `12D` | Deterministic application-owned selection, admission, conflict handling, and extension-to-Application-Graph IR contribution projection; no execution authority. |
| `12E` | Explicit application-owned capability grant plan and generated Composition wiring for the selected trusted/in-process profile; no dynamic discovery or service location. |
| `12F` | Bounded trusted/in-process Extension Host lifecycle with honest non-sandboxed semantics, cleanup, isolation, and failure preservation. |
| `12G` | Bounded read-only inspection, supported CLI consumption, and visibility-safe Chie/Hantei/Oshie evidence; no extension mutation or self-authorization. |
| `12H` | Root-owned external acceptance and exact Phase 12 checkpoint. |

The order is dependency order, not parallel authorization. Completion of one
row never begins the next automatically.

Sandboxed and external execution profiles, marketplace and distribution,
package installation/update/removal, signing and publisher trust, remote
networking, Agent Principal runtime, frontend/UI extension contracts,
production-readiness certification, and Stable/1.0 API remain outside Phase 12
unless a later architect directive changes that boundary.

## Consequences

- Application ownership is explicit from selection through grant.
- Declaration evidence remains inert until deterministic admission.
- Graph evidence can describe an admitted contribution without becoming a
  loader, capability, authorization decision, or health system.
- Least-privilege capability design remains honest about the limits of
  in-process PHP.
- Strong isolation, supply-chain trust, and distribution require later,
  separately accepted decisions.
- The fixed sequence makes contracts and deterministic evidence precede
  selection, grants, hosting, inspection, and external acceptance.
- Documentation must distinguish current Phase 12A architecture from planned
  Phase 12B-H behavior.

## Rejected alternatives

- Ambient discovery, package-name inference, environment-driven activation,
  and mutable global extension registries are rejected.
- Treating installation, a manifest, a signature, Graph presence, or generated
  evidence as admission, trust, authorization, or a capability grant is
  rejected.
- A dynamic container, service locator, or hidden composition root as the
  Extension Platform is rejected.
- Claiming that framework capability ports sandbox arbitrary trusted PHP is
  rejected.
- Giving the Application Graph loading, execution, publisher-trust,
  authorization, or health-remediation authority is rejected.
- Letting extensions become Principals, telemetry become authorization, or
  developer-intelligence evidence become extension authority by implication is
  rejected.
- Selecting a sandbox, process protocol, package format, signature scheme,
  marketplace, or vendor during Phase 12A is rejected.

## Open work

The exact Phase 12B-H contracts and implementations remain open within their
fixed rows. No package, namespace, manifest syntax, schema, capability type,
host API, lifecycle API, artifact format, composition shape, command, package
format, sandbox, external protocol, signing scheme, publisher model,
marketplace, distribution service, or update mechanism is selected by this
decision.

## Related material

- [Extension Platform guide](../architecture/extension-platform.md)
- [Phase 12 roadmap](../architecture/phase-12-roadmap.md)
- [Authoritative Application Graph](../architecture/authoritative-application-graph.md)
- [Identity and Access Security](../architecture/identity-and-access-security.md)
- [Observability and Operational Evidence](../architecture/observability-and-operational-evidence.md)
- [Agent model](../architecture/agent-model.md)
- [ADR 0009](0009-secure-extension-platform.md)
- [ADR 0123](0123-authoritative-application-graph-and-language-neutral-ir-boundary.md)
