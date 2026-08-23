# Phase 4 Roadmap

## Purpose and milestone boundary

Phase 4 establishes one versioned, inspectable, AI-independent developer-
knowledge foundation and proves three separated developer-intelligence roles:
Chie helps build, Hantei independently proves, and Oshie teaches.

Phase 4 is smaller than the full framework, Stable API, 1.0, auto-fix,
autonomous operations, or production AI. It does not create an application
Agent Principal or grant production authority.

## Starting state after Phase 3

Phase 3 is complete at checkpoint
`6aacafc92f07b567818173d660e2e182dfa83d23`. The repository has fourteen
acyclic production packages and one externally accepted synchronous PHP-CGI
FastCGI `RESPONDER` integration profile. That checkpoint is not a universal
runtime, production-hardening certification, Stable API, full framework, or
1.0 release.

Phase 4A accepts architecture, doctrine, security and provenance boundaries,
the rolling PHP policy, and this roadmap only. No role or knowledge runtime is
implemented.

## Fixed A-H roadmap

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

This dependency order is locked. Main phases do not automatically continue to
`Z`; Phase 4 ends at `4H` unless an architect-approved ADR changes it.

## Dependency graph

```text
Phase 3 checkpoint
       |
      4A
       |
      4B
       |
      4C
       |
      4D
       |
      4E
       |
      4F
       |
      4G
       |
      4H
```

This is a sequential execution dependency, not a parallel role fan-out. Each
subphase must be accepted before the next begins. No subphase may outrun the
decision, version identity, deterministic artifact, authority, disclosure,
provenance, or evidence boundary on which it depends.

## Deterministic-knowledge-first rationale

All roles need the same accepted, version-matched facts. Phase 4B therefore
defines their source and provenance contract before Phase 4C can implement
deterministic artifacts. Role behavior follows only after those artifacts can
be generated and validated without AI. This keeps accepted framework knowledge
inspectable and usable when models or networks are unavailable.

Phase 4A does not choose the contract's schema, identifier, format, package,
serializer, index, storage, retrieval, model, provider, prompt, or command.

## Role separation and handoffs

- Chie may eventually create a candidate but cannot certify it.
- Hantei selects and interprets accepted deterministic evidence without
  repairing the subject it judges.
- A failed or corrected subject returns to the developer or Chie and requires a
  fresh Hantei run.
- Oshie teaches from the same accepted version-matched foundation; generated
  teaching cannot become repository truth.
- A report, model confidence, passing subset, or another role's explanation
  cannot replace required deterministic evidence.

The initial Chie and Hantei boundaries are read-only. A Phase 4G patch remains
a candidate until explicit human-controlled application and fresh Hantei proof.

## Gates by subphase

| Phase | Deterministic and version gate | Authority and disclosure gate | Offline and evidence gate |
|---|---|---|---|
| `4A` | Accept policy and order only | Lock least authority, separation, provenance, and Safe/Protected precedence | Confirm no role or knowledge runtime exists |
| `4B` | Define exact source-state identity and provenance contract | Define input trust and fail-closed boundaries without selecting tools | Preserve AI-independent accepted sources |
| `4C` | Reproducibly generate and validate deterministic artifacts | Prevent unauthorized data inclusion or disclosure | Generate and validate without a model or network |
| `4D` | Require cited version-matched Chie answers | Read-only; no dependency, Git, patch, production, or network-by-default authority | Expose unavailable, stale, and unsupported states |
| `4E` | Select applicable checks and return evidence-backed `PASS`, `FAIL`, or `BLOCKED` | Read-only and independent; never repair the judged subject | Detect skipped, weakened, stale, irrelevant, or blocked proof |
| `4F` | Cite exact sources and deterministically prove executable examples | Teaching cannot elevate authority or truth status | Accepted documentation remains independently useful |
| `4G` | Preserve exact diff, provenance, reversal, and Hantei handoff | Explicit proposal authority only; no silent application, Git, auto-fix, or production | Candidate status remains visible until human action and fresh proof |
| `4H` | Reconcile cross-role source identity and deterministic evidence | Exercise denial, malicious input, disclosure, provenance, and production-access boundaries | Prove unavailable, stale, and evidence-failure cases fail visibly |

For every subphase, repository and retrieved content remains untrusted input.
Prompt injection, tool confusion, path escape, command injection, dependency
manipulation, secret exposure, and data exfiltration must not expand authority.

## Current versus planned behavior

| Concern | Implemented now | Accepted policy in Phase 4A | Planned Phase 4 | Deferred or prohibited here |
|---|---|---|---|---|
| Framework | Phase 3 checkpoint and deterministic package/runtime behavior | AI-independent operation | No change by intelligence work | Full framework, Stable API, 1.0, production AI |
| PHP | Mixed `>=8.5` and `^8.5` manifests | One compatibility-certified minor; intended later `>=8.5.0 <8.6.0` | Separate authenticated migration, not assigned a Phase 4 subphase | Automatic PHP 8.6/PHP 9 support claims |
| Knowledge | Accepted source documents and deterministic code/tests | Version, provenance, disclosure, and fail-closed doctrine | 4B contract and 4C artifacts | Schema, format, index, retrieval, provider, or model selected in 4A |
| Chie | Phase 4D exact cited explanation and bounded Safe diagnostic observation plus Phase 4G explicit review candidates | Name, promise, separation, authority limits | Human-controlled application only outside Chie | Free-form prompting, self-certification, silent patching, auto-fix, Git, deployment, production |
| Hantei | Phase 4E independent deterministic verifier | Independent evidence contract | Fresh verification of an externally applied subject | Repairing its subject, certifying an unapplied candidate, or replacing gates |
| Oshie | Phase 4F cited deterministic teaching | Version-matched teaching contract | Phase 4H external acceptance evidence | Generated prose or teaching as proposal authority |

## Phase 4H external acceptance checklist

Phase 4 cannot checkpoint until external acceptance proves at least:

1. one exact source-state/version identity drives all knowledge artifacts;
2. accepted source material remains independently usable without AI;
3. stale, mixed-version, missing, or unverifiable knowledge fails visibly;
4. deterministic artifact generation and validation are reproducible;
5. Chie cites exact accepted sources and distinguishes facts from inference;
6. Chie remains read-only before the separately accepted Phase 4G proposal
   boundary;
7. Chie cannot certify its own candidate;
8. Hantei selects applicable accepted checks and returns structured `PASS`,
   `FAIL`, or `BLOCKED` from inspectable evidence;
9. Hantei detects skipped, weakened, stale, or irrelevant proof;
10. Hantei never repairs the subject under judgment;
11. Oshie teaches from exact version-matched sources;
12. Oshie examples claimed executable have deterministic proof;
13. all roles disclose unsupported claims and uncertainty;
14. network/provider unavailability does not block ordinary framework use;
15. no external transmission occurs without explicit policy and disclosure;
16. secrets and Protected context remain excluded unless separately authorized;
17. prompt injection or retrieved instructions cannot elevate tool authority;
18. every tool/data use has bounded non-secret provenance;
19. a Phase 4G patch remains a candidate until explicit human-controlled
    application and fresh Hantei proof;
20. no role can stage, commit, publish, deploy, or access production under the
    Phase 4 contract; and
21. cross-role external acceptance exercises success, unavailable, denied,
    stale, malicious-input, and evidence-failure cases.

Phase 4A does not choose the concrete external acceptance harness.

## Correction and subphase policy

Use a bounded correction subphase when accepted evidence reveals a narrow
defect. A correction does not enlarge the phase boundary, skip a prerequisite,
or silently redefine an Accepted decision. Advancing to another subphase
requires the preceding boundary and its independent evidence to be complete.

## Deliberate exclusions

Phase 4 does not authorize a full framework, Stable API, 1.0, auto-fix,
production AI, monitoring, autonomous remediation, deployment, rollback,
self-healing, production mutation, or a production/operations guardian.

Phase 4A additionally selects no package, API, schema, knowledge artifact,
identifier, serializer, format, index, search or retrieval engine, embedding,
vector store, cache, prompt, model, provider, SDK, protocol, transport,
command, persistence, workspace reader, or tool authority.

## Relationship to Agent Principals and the Application Graph

Developer companions are distinct from application Agent Principals. Phase 4
creates no Principal identity, authentication, authorization, delegation,
consent, trust, MCP, or A2A behavior.

Future knowledge may use accepted Application Graph information only after a
separate derivation boundary is Accepted. That use does not extend the current
Experimental Graph or make it responsible for AI behavior.

## Relationship to rolling PHP support

[ADR 0059](../adr/0059-rolling-current-minor-php-baseline.md) is a prerequisite
policy decision, but the Composer migration is orthogonal to the A-H sequence.
Phase 4A changes none of the mixed current constraints and assigns no migration
subphase. No PHP 8.6 or PHP 9 support exists without a completed compatibility
gate and separately authorized migration.

## Auto-fix and operations work

Bounded auto-fix is deliberately outside Phase 4. A later milestone may
consider only low-risk deterministic corrections with explicit consent, exact
scope, reviewable changes, practical reversal, fresh Hantei verification, and
no silent commit, publication, deployment, or production access.

A production or operations guardian remains unnamed until telemetry,
operational state, credentials, deployment, incidents, and production
authorization have separately Accepted foundations.

## Relationship to the full-framework roadmap

Phase 4 adds a bounded developer-knowledge milestone above the current
deterministic framework. It does not complete routing, DI, HTTP, persistence,
templates, frontend, authentication, sessions, queues, events, mail,
scheduling, realtime, extensions, CLI, telemetry, or other future framework
capability. No Phase 4 result alone establishes production readiness or product
availability.

## Related material

- [ADR 0059: Rolling Current-Minor PHP Baseline](../adr/0059-rolling-current-minor-php-baseline.md)
- [ADR 0060: Human-Centered Developer-Intelligence Family](../adr/0060-human-centered-developer-intelligence-family.md)
- [Developer-intelligence architecture](developer-intelligence.md)
- [PHP support policy](../engineering/php-support-policy.md)
- [Security policy](../../SECURITY.md)
- [Architectural doctrine](../doctrine.md)
- [Documentation standard](../documentation-standard.md)
- [Testing](../engineering/testing.md)
- [Toolchain](../engineering/toolchain.md)
- [Error handling and diagnostics](../engineering/error-handling.md)
- [Phase 3 roadmap](phase-3-roadmap.md)
- [Agent model](agent-model.md)
- [Application Graph](application-graph.md)

## Phase 4B accepted status and Phase 4C handoff

Phase 4B is now Accepted architecture only through [ADR
0061](../adr/0061-versioned-knowledge-source-and-provenance-contract.md) and the
[knowledge foundation](knowledge-foundation.md). It defines semantic source
authority, exact source state, provenance, claim classes, conflict visibility,
citation, deterministic derivation, disclosure, and framework/workspace
separation without implementing a machine representation.

Phase 4C remains the next sequential phase. Under separate authentication it
must choose the concrete ownership, source inventory, schema, identity,
serialization, bounds, command, validation, publication, stale-state, citation,
and proof contracts for one deterministic AI-independent artifact slice. Phase
4B adds no artifact, package, digest, generator, validator, retrieval, model,
workspace read, Graph behavior, or role runtime. The A-H table and sequential
dependency graph above remain unchanged.

## Phase 4C implemented boundary

## Phase 4E implemented boundary

Phase 4E implements the independent read-only Hantei verifier with one closed
versioned profile, eight fixed checks, exact Git-visible subject identity, and
bounded non-disclosing evidence. Phase 4F implements Oshie's closed cited
curriculum, Phase 4G implements the unapplied Chie proposal, and Phase 4H
implements cross-role external acceptance. Candidate application remains
external and human-controlled.
Phase 4C is complete only for the deterministic framework-knowledge artifact
slice accepted by ADR 0062: one `kongono/knowledge` package, one exact 31-source
`phase4-foundation` inventory, canonical JSON and exact identities, whole-source
citations, complete generation and current validation, bounded diagnostics and
commands, and optional content-addressed atomic initial publication. No
generated artifact is checked in or authoritative.

The sequential dependency graph remains unchanged. Phase 4D now implements the
read-only Chie consumer accepted by ADR 0063: six exact cited topics, one
supplied Safe diagnostic identifier, visible response states, and no free-form
prompt, arbitrary workspace read, recommendation, patch, verification, model,
network, Git, deployment, or production authority. Phase 4E Hantei is next but
has not begun. Oshie, candidate proposals, and external acceptance remain
planned; later capabilities remain deferred or prohibited.

## Phase 4F implemented boundary

Phase 4F implements Oshie's exact cited six-record curriculum, deterministic practice, and exact-answer checks. It adds no executable example, free-form AI, learner persistence, mutation, verification, Git, deployment, or production authority.

Phase 4G implements one explicit deterministic Chie candidate for a current
explanatory documentation source. The reversible diff, provenance, identities,
warnings, and pending handoff do not apply or verify the candidate. Phase 4H
implements the root-owned exact 28-scenario external acceptance harness and
creates the single Phase 4 checkpoint after all pre- and post-checkpoint gates.

Phase 4A through 4H are implemented and externally accepted within the bounded
milestone. Chie candidates remain unapplied/unverified, synthetic Hantei
evidence remains distinct from real current-repository PASS, and no executable
Oshie example is claimed. Phase 4 is not Stable, 1.0, or production-ready. No
Phase 5 capability or roadmap decision is selected or implied.
