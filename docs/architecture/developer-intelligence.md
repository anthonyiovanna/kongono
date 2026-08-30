# Developer Intelligence

## Purpose and status

This document is the current architecture entrypoint for Kongono's
developer-intelligence family. [ADR 0060](../adr/0060-human-centered-developer-intelligence-family.md)
accepts the family, its separation of duties, and its security and provenance
boundaries. Phase 4C implements the deterministic knowledge artifact; Phase 4D
implements the narrow read-only Chie consumer; Phase 4E implements independent
read-only Hantei; Phase 4F implements bounded Oshie teaching; and Phase 4G adds
one reviewable, unapplied Chie documentation candidate. Models, provider
integration, automatic application, and production authority remain
unimplemented.

## Roles and promises

| Role | Canonical promise | Responsibility |
|---|---|---|
| **Kongono Chie** | **Chie helps you build it.** | Explain the exact codebase and accepted Kongono version, diagnose development problems, plan bounded changes, and eventually propose reviewable candidate patches. |
| **Kongono Hantei** | **Hantei proves it works.** | Independently select, run, and interpret deterministic verification and return evidence-backed `PASS`, `FAIL`, or `BLOCKED`. |
| **Kongono Oshie** | **Oshie helps you understand it.** | Teach Kongono and PHP from version-matched accepted sources, examples, diagnostics, and learning paths. |

Use each full `Kongono` name on first reference. The locked names do not imply
that a product, provider, account, runtime, or commercial offering exists.
Phase 4D Chie currently returns exact cited source sections for six closed
topics and bounded literal occurrences of one supplied Safe diagnostic
identifier. The broader responsibility wording remains direction, not current
free-form conversation, planning, recommendation, or patch capability.

## Role handoff flow

```text
accepted versioned sources
          |
deterministic knowledge artifact
          |
  +-------+-------+
  |       |       |
Chie   Hantei   Oshie
  |       ^       |
  +-- candidate --+
      evidence
```

Chie may eventually create a candidate, but cannot certify it. Hantei judges
the subject without repairing it. A correction returns to the developer or
Chie and requires fresh independent Hantei evidence. Oshie derives teaching
from the same accepted, version-matched foundation and cannot promote generated
prose to repository truth.

The diagram selects no runtime type, wire protocol, schema, command, package,
provider, prompt, storage system, or public API.

## Developer companions and Agent Principals

Chie, Hantei, and Oshie are planned developer companions operating around
framework development knowledge. An Agent Principal is a planned actor inside
an application with future identity, authentication, authorization,
delegation, consent, trust, and audit concerns. The two concepts do not confer
authority on each other.

Phase 4 creates no Principal model or MCP/A2A behavior and does not change
[ADR 0008](../adr/0008-agents-as-principals.md). See the
[agent model](agent-model.md).

## Deterministic core and optional intelligence

Kongono applications remain buildable, testable, diagnosable, documented,
deployable, and operable without these roles, an LLM, a provider, an account, a
subscription, or a network. Accepted source, tests, diagnostics, package
boundaries, and other Kongono-owned mechanisms remain authoritative and usable
offline.

Optional intelligence may explain or propose. It cannot authorize itself,
weaken a gate, establish application structure, reclassify Protected data, or
turn repetition into accepted truth. AI may suggest; Kongono must know.

## Future knowledge-source and provenance boundary

The three roles must eventually consume one versioned, inspectable,
AI-independent foundation rather than unrelated prompts. Candidate inputs may
include accepted source and API metadata, ADRs, doctrine, tested documentation
and examples, diagnostics and disclosure classifications, package boundaries,
the supported PHP policy, and separately authorized deterministic Application
Graph artifacts.

Phase 4B must define the knowledge-source and provenance contract before any
artifact exists. It will not select a model, provider, retrieval engine, or
workspace mutation. Application Graph use needs its own Accepted derivation
boundary and does not extend current Graph behavior.

Every later artifact identifies the exact Kongono version or source state from
which it was derived. Stale, mixed-version, missing, unverifiable, and
unsupported knowledge fails visibly.

## Answer classes

Future role output must label applicable content as one of these classes:

| Class | Meaning |
|---|---|
| Accepted framework fact | A claim supported by accepted version-matched Kongono material. |
| Current workspace observation | A deterministic observation from the identified current source state. |
| Inference | A conclusion derived from cited facts or observations but not itself accepted truth. |
| Recommendation | Advice requiring human evaluation and any applicable authority gate. |
| Generated candidate | Proposed content that remains unaccepted until the required review and proof. |
| Unresolved uncertainty | Missing, conflicting, stale, unverifiable, or unsupported information. |

Applicable claims cite the exact version or source state and the source, ADR,
diagnostic, symbol, test, or command evidence.

## Least-authority and data-flow boundaries

Read and write authority remain separate. Network, credentials, dependencies,
Git, deployment, publication, destructive actions, and production access each
need distinct future gates. Model output or retrieved text cannot elevate
authority. No Phase 4 role can stage, commit, publish, deploy, or access
production.

Each future information and tool use carries bounded non-secret provenance.
Authority, version, provenance, and evidence ambiguity fail closed.

## Disclosure and hostile-input model

[Safe and Protected disclosure](../engineering/error-handling.md) applies
before information reaches a role. A role cannot reclassify Protected data.
Secrets, tokens, credentials, raw rejected input, and unrelated source remain
excluded unless a later explicit contract proves a necessary bounded use.

Repository text, dependencies, diagnostics, comments, generated artifacts,
issues, documentation, retrieved content, and model responses are untrusted
inputs. Future boundaries must address prompt injection, tool confusion, path
escape, command injection, dependency manipulation, secret exposure, and data
exfiltration. Retrieved instructions cannot expand a tool's authority.

## Provider, network, retention, and training neutrality

No hosted or local model, vendor, SDK, transport, account, or commercial plan
is selected. External access must be separately enabled, disclose what leaves
the machine, minimize transmitted data, and respect workspace and organization
policy. Source is not used for training by default. Training, fine-tuning,
feedback retention, or cross-project learning requires explicit opt-in and
security review.

Provider or network unavailability leaves ordinary deterministic framework use
unaffected. A future role receiving unavailable, denied, stale, or ambiguous
inputs must expose that state rather than inventing success.

## Current versus planned behavior

| Concern | Current or accepted in Phase 4A | Planned | Not authorized in Phase 4A |
|---|---|---|---|
| Framework | Phase 3 checkpoint and fourteen deterministic packages | Later framework milestones | AI-required framework operation |
| Knowledge | Version/provenance doctrine only | Phase 4B contract and Phase 4C deterministic artifacts | Schema, index, retrieval, storage, or model integration |
| Chie | Phase 4D deterministic read-only explanation and diagnostic observation | Candidate proposals only in Phase 4G | Free-form prompt, recommendation, patch application, auto-fix, Git, or self-certification |
| Hantei | Accepted independent-verifier contract | Read-only Phase 4E | Repairing what it judges or replacing deterministic checks |
| Oshie | Accepted teaching contract | Version-matched Phase 4F teaching and verified exercises | Generated teaching as repository truth |
| Operations | Unnamed and deferred | Separate future architecture decisions | Monitoring, deployment, rollback, remediation, self-healing, or production mutation |

## Fixed Phase 4 dependency map

```text
4A architecture and doctrine
 |
4B knowledge-source and provenance contract
 |
4C deterministic knowledge artifacts
 |
4D read-only Chie
 |
4E read-only Hantei
 |
4F Oshie
 |
4G candidate patch proposals
 |
4H cross-role external acceptance and checkpoint
```

The diagram is the execution dependency order, not merely a list of roles.
Although Chie, Hantei, and Oshie consume the same future knowledge foundation,
their implementation subphases are not parallel: each phase must be accepted
before the next begins. The canonical boundaries and fixed order are defined in
the [Phase 4 roadmap](phase-4-roadmap.md).

## Deliberate exclusions and open decisions

Phase 4A selects no package, API, schema, identifier, artifact format,
serializer, index, retrieval engine, embedding, vector store, cache, prompt,
model, provider, transport, protocol, command, persistence, workspace reader,
or tool implementation. It grants no credentials, network, dependency, Git,
deployment, publication, destructive, or production authority.

Bounded auto-fix is outside Phase 4. A production or operations guardian stays
unnamed until its operational foundations are separately accepted. Public
commercial use of the three names remains subject to legal and trademark
clearance.

## Related material

- [ADR 0060](../adr/0060-human-centered-developer-intelligence-family.md)
- [ADR 0059](../adr/0059-rolling-current-minor-php-baseline.md)
- [Phase 4 roadmap](phase-4-roadmap.md)
- [Agent model](agent-model.md)
- [Application Graph](application-graph.md)
- [Error handling and disclosure](../engineering/error-handling.md)
- [Security policy](../../SECURITY.md)
- [Documentation standard](../documentation-standard.md)
- [Testing](../engineering/testing.md)
- [PHP support policy](../engineering/php-support-policy.md)

## Accepted Phase 4B knowledge contract

[ADR 0061](../adr/0061-versioned-knowledge-source-and-provenance-contract.md)
and the [knowledge foundation](knowledge-foundation.md) now define the shared
semantic contract for all future role knowledge. Sources retain one of six
authority classes; claims retain one of six claim classes; version-sensitive
answers bind to exact source state and bounded provenance; and stale,
mixed-version, conflicting, incomplete, or unverifiable claims fail visibly.

Generated artifacts and role output remain derived material, never independent
authority. Official framework knowledge and project-scoped workspace evidence
stay separate, and Safe/Protected disclosure applies before inclusion. Phase
4B implements no artifact, package, inventory, schema, digest, command,
generator, validator, retrieval, model, workspace reader, or role runtime. The
role boundaries and the sequential Phase 4 dependency map above are unchanged;
Phase 4C is next under separate authenticated authority.

## Current Phase 4C and 4D foundation

## Current Phase 4E verification boundary

Hantei is now an implemented optional deterministic verifier. It independently
uses the current Knowledge artifact, not Chie output, and returns exact-subject
derived evidence. It does not repair or certify future candidates. Oshie and the
human-controlled Chie-candidate application flow remain planned.
Phase 4C now implements the AI-independent knowledge prerequisite only: one
canonical 31-source `phase4-foundation` artifact with exact source-set and
generator identities, whole-source citations, complete in-memory generation,
structural/current validation, and optional content-addressed no-clobber
publication. The artifact is derived evidence, never accepted truth.

Phase 4D now implements the first Chie runtime as a deterministic offline
consumer of that artifact. It validates current state before returning an exact
cited section for one of six topics or bounded literal occurrences of one
supplied Safe diagnostic identifier. It does not converse in free-form natural
language, read arbitrary workspace files or error payloads, recommend or change
code, or prove correctness. Hantei and Oshie remain planned; retrieval, models,
patches, Git, deployment, and production access remain absent. See
[read-only Chie](read-only-chie.md).

## Current Phase 4F teaching boundary

Phase 4F implements Oshie's closed six-record curriculum. It returns exact cited source sections, practice prompts without accepted-answer fields, and deterministic correct or incorrect checks without submitted input. Chie helps build, Hantei independently proves, and Oshie teaches; the roles have no package dependency or self-elevation. Accepted sources remain authoritative and independently useful. See [read-only Oshie](read-only-oshie.md).

## Phase 4G candidate proposal boundary

Chie now constructs one deterministic exact-line candidate from an explicit
current-explanatory-documentation source in the validated current artifact. It
returns reversible diffs, exact provenance and content identities, fixed
warnings, and a pending human-application/fresh-Hantei handoff. The candidate is
derived review material, not accepted authority. Caller-asserted Safe text is
not sanitization. Chie cannot apply, verify, certify, stage, commit, publish,
deploy, use a model/provider/network, or understand arbitrary applications.
Hantei judges only an actual subject; Oshie teaching is not a proposal source.
See [reviewable Chie proposals](reviewable-chie-patch-proposals.md).

## Phase 4 external acceptance

Phase 4H implements the root-owned
[28-scenario external harness](phase-4-external-acceptance.md). It proves all
three roles consume one exact current knowledge identity where defined, local
Knowledge/Chie/Oshie behavior remains available without network access, real
Hantei passes the unchanged repository, synthetic Hantei evidence remains
explicitly synthetic, and no role mutates or elevates authority. Chie's
candidate remains unapplied and unverified. Oshie's accepted curriculum has no
executable example. Phase 4 is closed; Phase 5 was not begun.

## Phase 11A Graph boundary

Phase 11A accepts a future authoritative Graph and language-neutral IR but gives
Kongono Chie, Kongono Hantei, and Kongono Oshie no new Graph access. Any future
developer-intelligence derivation is ordered after identities, schemas,
provenance, visibility, construction, artifact currentness, and bounded
inspection in the fixed [Phase 11 roadmap](phase-11-roadmap.md).

A role may consume only a separately accepted visibility-filtered view bound to
one exact Graph state. It cannot reclassify Protected facts, treat omission as
false, merge stale versions, or promote explanation, inference, teaching, or a
candidate into authoritative Graph truth. Retrieved instructions remain inert
and cannot enlarge role or tool authority. Phase 11G now implements only the
separate Safe aggregate derivation and independent verification boundary; it
does not execute a role or grant raw or individual-fact Graph access. See
[ADR 0129](../adr/0129-visibility-safe-application-graph-developer-intelligence.md)
and the [Application Graph developer-intelligence guide](application-graph-developer-intelligence.md).
