# ADR 0060: Human-Centered Developer-Intelligence Family

## Status

Accepted

## Date

2026-08-22

## Context

Kongono needs developer assistance that can explain, verify, and teach the
exact framework without making model output authoritative. Combining creation,
certification, and teaching in one unconstrained assistant would obscure
responsibility and allow a generated claim to validate itself.

The developer-facing problem is distinct from an Agent Principal acting inside
an application under [ADR 0008](0008-agents-as-principals.md). Developer
companions do not create Principal identity, authorization, delegation,
consent, trust, MCP, A2A, or application-agent runtime behavior.

## Decision

Kongono accepts exactly three complementary developer-intelligence roles above
one future versioned, inspectable, AI-independent knowledge foundation:

| Role | Canonical promise | Responsibility |
|---|---|---|
| **Kongono Chie** | **Chie helps you build it.** | Explain the exact codebase and accepted Kongono version, diagnose development problems, plan bounded changes, and eventually propose reviewable candidate patches. |
| **Kongono Hantei** | **Hantei proves it works.** | Independently select, run, and interpret deterministic verification and return evidence-backed `PASS`, `FAIL`, or `BLOCKED`. |
| **Kongono Oshie** | **Oshie helps you understand it.** | Teach Kongono and PHP from version-matched accepted sources, examples, diagnostics, and learning paths. |

The first reference uses `Kongono Chie`, `Kongono Hantei`, or `Kongono Oshie`;
later references may use the short name. These are locked internal product and
architecture names. Public commercial use remains subject to final legal and
trademark clearance.

Phase 4A accepts the names, responsibilities, separation, and future security
boundary only. It implements no role, model, package, command, knowledge
artifact, retrieval system, workspace reader, verifier, teacher, patch engine,
or auto-fix behavior.

## Chie boundary

Chie helps create and understand work. It may eventually explain cited
version-matched facts, diagnose bounded workspace problems, plan changes, and
propose a reviewable candidate patch. Chie cannot certify its own work. Its
initial Phase 4D boundary is read-only; Phase 4G may permit proposals only
after separate authority, diff, provenance, reversal, and handoff contracts are
accepted. A proposal remains a candidate until explicit human-controlled
application and fresh Hantei proof.

## Hantei boundary

Hantei independently selects applicable accepted deterministic checks, runs
and interprets them, detects skipped, weakened, stale, or irrelevant proof, and
returns structured `PASS`, `FAIL`, or `BLOCKED` with inspectable evidence.
Hantei never repairs the subject it judges. A failure or correction returns to
the developer or Chie and requires a fresh independent verification run.

A completion report, model confidence, passing subset, or another role's
explanation cannot substitute for required evidence. Hantei's initial Phase 4E
boundary is read-only.

## Oshie boundary

Oshie teaches Kongono and PHP from accepted, version-matched sources,
diagnostics, examples, and learning paths. An example claimed executable needs
deterministic proof. Generated teaching is derived material and cannot become
accepted repository truth merely through repetition.

## Shared knowledge foundation

All three roles must eventually consume one versioned, inspectable foundation,
not separate prompt collections. Candidate authoritative inputs may include
accepted source and API metadata, ADRs, doctrine, tested documentation and
examples, diagnostics and disclosure classifications, package boundaries, the
supported PHP policy, and separately authorized deterministic Application
Graph artifacts.

Phase 4A selects no package, schema, stable identifier, serializer, file format,
index, embedding, vector store, retrieval engine, model, prompt, cache,
transport, provider, protocol, command, or persistence mechanism. Application
Graph derivation requires another Accepted boundary and does not extend the
current Experimental Graph.

Every future knowledge artifact must identify the exact Kongono version or
source state from which it was derived. Stale, mixed-version, absent,
unverifiable, or unsupported knowledge fails visibly.

## AI-independent framework rule

Kongono applications remain buildable, testable, diagnosable, documented,
deployable, and operable without Chie, Hantei, Oshie, an LLM, a provider, an
account, a subscription, or a network connection. Accepted sources and
deterministic framework mechanisms remain independently usable and
authoritative: AI may suggest; Kongono must know.

## Provider and deployment neutrality

No model vendor, hosted service, local model, transport, account, commercial
plan, or provider SDK is selected. External access, if later accepted, must be
separately enabled, identify what leaves the machine, minimize transmitted
data, and respect workspace and organization policy.

Kongono or application source is not used for training by default. Training,
fine-tuning, feedback retention, or cross-project learning requires explicit
opt-in and security review. Network or provider unavailability cannot block
ordinary framework use.

No role receives production access under the Phase 4 contract. Staging,
committing, publication, deployment, rollback, destructive actions, and
production mutation require distinct future decisions and are not implied by
developer assistance.

## Security and disclosure contract

Future boundaries must enforce:

1. least authority for each operation;
2. separate read and write authority;
3. distinct gates for network, credentials, dependencies, Git, deployment,
   publication, destructive actions, and production access;
4. no self-elevation from model output or retrieved text;
5. existing Safe and Protected disclosure enforcement before role access;
6. no role reclassification of Protected data;
7. exclusion of secrets, tokens, credentials, raw rejected input, and unrelated
   source unless a later explicit contract proves a necessary bounded use;
8. bounded non-secret provenance for every information and tool use;
9. no production access before a distinct production-trust decision;
10. fail-closed behavior when authority, version, provenance, or evidence is
    absent or ambiguous;
11. treatment of repository text, dependencies, diagnostics, comments,
    generated artifacts, issues, documentation, retrieved content, and model
    responses as untrusted inputs; and
12. explicit defenses for prompt injection, tool confusion, path escape,
    command injection, dependency manipulation, secret exposure, and data
    exfiltration.

Agent output cannot authorize itself, weaken a gate, reclassify data, or become
accepted repository truth merely because it is repeated.

## Provenance and answer contract

Future answers must distinguish:

- accepted framework fact;
- deterministic current-workspace observation;
- inference;
- recommendation;
- generated candidate content; and
- unresolved uncertainty.

Applicable claims cite the exact Kongono version or source state and the source,
ADR, diagnostic, symbol, test, or command evidence. Unsupported claims and
uncertainty remain visible.

## Agent creation gate

No additional named assistant may be added unless a proposal proves a durable
unowned developer problem, distinct authority boundary, distinct evidence and
output contract, distinct failure mode, material value above cognitive cost,
and a security model preserving separation of duties.

A production or operations guardian remains unnamed and deferred until
telemetry, operational state, credentials, deployment, incidents, and
production authorization have separately Accepted foundations. Phase 4A does
not authorize monitoring, autonomous remediation, deployment, rollback,
self-healing, or production mutation.

## Required implementation sequence

The dependency order is fixed:

| Phase | Boundary |
|---|---|
| `4A` | Accept ADR 0059, ADR 0060, the rolling PHP support doctrine, the Chie/Hantei/Oshie family, security/provenance boundaries, and this fixed A-H roadmap. Documentation only; no assistant or knowledge runtime. |
| `4B` | Architecture-only definition of the versioned, inspectable, AI-independent knowledge-source and provenance contract. Select no model, provider, retrieval engine, or workspace mutation. |
| `4C` | Implement only the accepted deterministic generation and validation of versioned knowledge artifacts, usable without AI. No model integration or assistant behavior. |
| `4D` | Implement and prove read-only Chie for cited version-matched explanation and bounded workspace diagnosis. No patch application, dependency, Git, network-by-default, or self-certification authority. |
| `4E` | Implement and prove read-only Hantei as an independent deterministic verifier with structured `PASS`, `FAIL`, or `BLOCKED` verdicts. Hantei may not repair what it judges. |
| `4F` | Implement and prove Oshie's cited version-matched learning boundary and deterministically verified exercises. Accepted documentation remains useful and authoritative without AI. |
| `4G` | Permit Chie to produce reviewable candidate patch proposals only after explicit authority, diff, provenance, reversal, and Hantei-handoff contracts are accepted. No silent application or auto-fix. |
| `4H` | Run cross-role external acceptance, offline/unavailable/fail-closed proof, security/provenance reconciliation, documentation reconciliation, and create the Phase 4 checkpoint. |

No subphase may outrun a required decision or deterministic foundation.
Bounded auto-fix is outside Phase 4. The rolling-PHP Composer migration is
orthogonal and has no assigned Phase 4 subphase.

## Consequences

Creation, independent proof, and teaching have named owners and incompatible
self-certification paths. All roles depend on version identity, deterministic
evidence, disclosure controls, and explicit authority. This increases the work
needed before model integration but prevents convenience from silently
becoming framework authority.

## Rejected alternatives

- One omnipotent assistant is rejected because it collapses authority and
  evidence boundaries.
- Chie self-certification and Hantei self-repair are rejected because creator
  and verifier duties must remain separate.
- AI as framework truth and AI-required framework operation are rejected.
- A required hosted provider is rejected; provider and offline neutrality are
  mandatory.
- Default source transmission, retention, or training is rejected.
- Default mutation, Git, deployment, publication, destructive, or production
  authority is rejected.
- Mascot-only roles without evidence contracts are rejected.
- A premature named operations guardian is rejected.

## Explicitly deferred decisions

Packages, APIs, schemas, identifiers, artifact formats, retrieval, models,
providers, prompts, commands, persistence, protocols, network behavior,
workspace reads, mutation, candidate application, auto-fix, production access,
commercial terms, and the external acceptance harness remain deferred.

## Related material

- [Developer-intelligence architecture](../architecture/developer-intelligence.md)
- [Phase 4 roadmap](../architecture/phase-4-roadmap.md)
- [Agent model](../architecture/agent-model.md)
- [Application Graph](../architecture/application-graph.md)
- [ADR 0008: Agents as Principals](0008-agents-as-principals.md)
- [ADR 0059: Rolling Current-Minor PHP Baseline](0059-rolling-current-minor-php-baseline.md)
- [Security policy](../../SECURITY.md)
- [Documentation standard](../documentation-standard.md)
- [Testing](../engineering/testing.md)
