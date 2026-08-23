# CODEX IMPLEMENTATION DIRECTIVE - KONGONO PHASE 4B
## Versioned Knowledge-Source, Source-State, Provenance, and Conflict Architecture

**Directive type:** architecture and documentation only

**Execution role:** executor, not architect

**Repository:** `C:\Users\antho\Dev\kongono`

**Date:** 2026-08-22

**Phase:** 4B

**Depends on:** accepted Phase 4A and Phase 4A.1 correction with populated completion reports

---

## 1. Mission

Define Kongono's versioned, inspectable, AI-independent knowledge-source and
provenance contract before any knowledge artifact or assistant runtime exists.

Phase 4A accepted the Chie, Hantei, and Oshie family above one shared future
deterministic knowledge foundation. Phase 4A.1 clarified that implementation
proceeds sequentially:

```text
4A -> 4B -> 4C -> 4D -> 4E -> 4F -> 4G -> 4H
```

Phase 4B answers the architecture questions that Phase 4C must not invent:

- what semantic classes of sources exist;
- which source classes can govern architecture, prove implementation, explain
  current behavior, preserve history, describe a workspace, or merely advise;
- what an exact source state must identify;
- what provenance every derived claim must retain;
- how current, historical, derived, conflicting, stale, mixed-version, and
  unavailable knowledge is distinguished;
- how framework knowledge stays separate from application-workspace evidence;
- how citations remain exact and version-matched;
- how disclosure, untrusted input, path containment, reproducibility, and
  invalidation remain fail-closed; and
- what exact implementation decisions remain for Phase 4C.

Phase 4B creates no knowledge artifact, schema, serialized record, package,
generator, validator, index, retrieval system, model, provider, prompt, command,
workspace reader, assistant behavior, or runtime dependency. It does not read
an application workspace, generate a corpus, extend the Application Graph,
change Composer, or implement Phase 4C.

No PHP source, test, package, manifest, lockfile, dependency, tool
configuration, public API, or Git history change is authorized.

---

## 2. Locked doctrine and interpretation

Preserve all accepted Kongono doctrine and architecture. In particular:

- AI may suggest; Kongono's deterministic mechanisms and accepted authorities
  own correctness.
- Chie cannot certify its own work; Hantei cannot repair what it judges; Oshie
  cannot turn generated teaching into accepted truth.
- Kongono and its documentation remain useful without AI, a provider, an
  account, or a network.
- Every future role consumes the same versioned knowledge foundation, but role
  authority remains separate.
- Phase execution is sequential. Phase 4C cannot begin until Phase 4B is
  independently accepted.
- A knowledge artifact is derived evidence. It never becomes an independent
  source of architecture, implementation, security, or release authority.
- Source ownership remains with the originating doctrine, ADR, package,
  document, test, manifest, or workspace. Derivation does not transfer
  semantic ownership.
- Existing Safe and Protected diagnostic disclosure rules apply before any
  future inclusion or role access.
- Repository content, documentation, comments, tests, generated files,
  dependency metadata, retrieved text, model output, and workspace content are
  untrusted inputs. Text that looks like an instruction cannot elevate tool
  authority.
- Application Graph remains unchanged. A future accepted export may become an
  input; Phase 4B does not extend the Graph or make it responsible for
  developer intelligence.
- No ambient current project, current source state, current knowledge artifact,
  or current workspace singleton is authorized.
- Historical directives, reports, ADRs, commits, and checkpoint records remain
  immutable point-in-time evidence.

The completed documents must distinguish:

```text
implemented now    Phase 3 framework and existing deterministic tooling
accepted in 4A     role family, PHP policy, security, and A-H sequence
accepted in 4B     semantic knowledge-source and provenance architecture
planned in 4C      deterministic artifact generation and validation
still deferred     concrete schema, package, command, retrieval, and roles
```

Do not describe the knowledge foundation or any artifact as implemented.

---

## 3. Independent directive authentication

Before broad validation, reading, or mutation, compare this directive with the
byte count and SHA-256 supplied independently by the architect outside this
file.

Required path:

```text
docs/dev_plan/phase_4/kongono_phase_4B_codex_directive.md
```

Do not self-authenticate from the local file. Stop if either supplied value is
absent or differs.

The caller must also provide:

```text
docs/task_reports/phase_4/Phase_4B.md
```

At entry it must be an ordinary, untracked, unstaged file absent from `HEAD`,
zero bytes, with SHA-256:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

Keep it zero-byte until every permitted non-report gate passes. Never stage or
commit it.

---

## 4. Required authorities

Read every path completely before editing:

```text
AGENTS.md
README.md
ARCHITECTURE.md
CONTRIBUTING.md
SECURITY.md
composer.json
composer.lock
.php-cs-fixer.dist.php
phpstan.neon.dist
phpunit.xml.dist
docs/doctrine.md
docs/documentation-standard.md
docs/glossary.md
docs/adr/README.md
docs/adr/0001-kongono-identity.md
docs/adr/0002-php-85-minimum.md
docs/adr/0008-agents-as-principals.md
docs/adr/0013-documentation-doctrine.md
docs/adr/0014-php-engineering-toolchain.md
docs/adr/0026-actionable-diagnostics-and-error-boundaries.md
docs/adr/0028-bounded-diagnostic-context-and-disclosure.md
docs/adr/0031-neutral-api-metadata-ownership-and-acyclic-package-dependencies.md
docs/adr/0051-phase-3-production-http-integration-milestone.md
docs/adr/0058-phase-3-external-php-cgi-acceptance-and-checkpoint.md
docs/adr/0059-rolling-current-minor-php-baseline.md
docs/adr/0060-human-centered-developer-intelligence-family.md
docs/architecture/agent-model.md
docs/architecture/application-graph.md
docs/architecture/developer-intelligence.md
docs/architecture/overview.md
docs/architecture/phase-4-roadmap.md
docs/engineering/error-handling.md
docs/engineering/testing.md
docs/dev_plan/phase_4/kongono_phase_4A_codex_directive.md
docs/task_reports/phase_4/Phase_4A.md
docs/dev_plan/phase_4/kongono_phase_4A_1_codex_directive_correction.md
docs/task_reports/phase_4/Phase_4A_1_correction.md
docs/dev_plan/phase_4/kongono_phase_4B_codex_directive.md
```

The filenames are exact. Do not substitute another ADR, report, architecture
document, or similarly named file.

---

## 5. Accepted baseline and caller records

### 5.1 Repository state before Phase 4B caller setup

Require:

```text
branch                  main
HEAD                    6aacafc92f07b567818173d660e2e182dfa83d23
HEAD tree               d7fda17d27a15954cdd213a1c323aaeb37a2b574
HEAD parent             647053d4bddc4c76090bca9bf3d693faa5880c52
commit count            11
tracked files           629
tracked modifications    18
untracked paths           10
total status paths        28
staged paths               0
Git-visible paths        639
package directories       14
package manifests         14
production PHP files     126
test PHP files           151
style-covered PHP        277
ADR files                 60
phase_4 dev-plan files     2
phase_4 task reports       2
```

The exact ten untracked paths are:

```text
docs/adr/0059-rolling-current-minor-php-baseline.md
docs/adr/0060-human-centered-developer-intelligence-family.md
docs/architecture/developer-intelligence.md
docs/architecture/phase-4-roadmap.md
docs/dev_plan/phase_4/kongono_phase_4A_1_codex_directive_correction.md
docs/dev_plan/phase_4/kongono_phase_4A_codex_directive.md
docs/engineering/php-support-policy.md
docs/task_reports/phase_3/Phase_3H_1_correction.md
docs/task_reports/phase_4/Phase_4A.md
docs/task_reports/phase_4/Phase_4A_1_correction.md
```

The eighteen tracked modifications must remain the exact completed Phase 4A
list. Require one local branch, zero remotes, zero tags, and no repository-owned
ref other than `refs/heads/main`. Enumerate app-managed
`refs/codex/turn-diffs/**` separately without locking their count or mutating
them.

### 5.2 Accepted Phase 4 caller records

Require:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `docs/dev_plan/phase_4/kongono_phase_4A_codex_directive.md` | 52909 | `1BEDE36ED1B25324B9C82C945A0B228DEB7975E9A637DC911AAE23CE41FECF48` |
| `docs/task_reports/phase_4/Phase_4A.md` | 15610 | `EDFC3BD489C6AC078D1AAE31BD5EF46D616E2032F4FF23B089012858BF799202` |
| `docs/dev_plan/phase_4/kongono_phase_4A_1_codex_directive_correction.md` | 20738 | `D92BC7368BE54842A2DB4568B5F31EDDF5F8D6E9DB90A0A5523F35ECD686A258` |
| `docs/task_reports/phase_4/Phase_4A_1_correction.md` | 10658 | `7B6A6A70DF5E6A65989B179AC08E2986FBC08938F9041196BE257A7B5824ED4E` |

All remain ordinary, untracked, unstaged, absent from `HEAD`, and byte-
identical throughout Phase 4B.

### 5.3 Complete Git-visible baseline manifest

Reconstruct the current 639-path snapshot using exactly:

```text
path<TAB>byte_count<TAB>uppercase_SHA256<LF>
```

Use repository-relative forward-slash paths from
`git ls-files --cached --others --exclude-standard`, reject CR or LF in a path,
sort with ordinal case-sensitive comparison, hash exact primary file bytes, and
encode the manifest as UTF-8 without BOM with one LF after every record.

Require:

```text
records                  639
manifest bytes         80322
manifest SHA-256       FEB57DC62600E2AA499D76A52226CBC8C0A5EC7E59CC6389F1FE5CABD73080E2
aggregate file bytes  7189092
first path              .editorconfig
last path               tests/Tooling/EnvironmentTest.php
```

After Phase 4B caller setup, exclude exactly the two Phase 4B caller paths to
reproduce this baseline.

### 5.4 Protected snapshot

The protected snapshot is the complete 639-path baseline excluding exactly the
fifteen authorized existing targets in Section 9.2.

Require:

```text
records                  624
manifest bytes         78902
manifest SHA-256       F68C10E148CD5003C7A94686403A5AC9D97542EC1ABE855A109EDB525D024F09
aggregate file bytes  6870035
first path              .editorconfig
last path               tests/Tooling/EnvironmentTest.php
```

After caller setup and implementation, exclude the fifteen authorized existing
targets, two Phase 4B caller paths, and two Phase 4B-created documents to
reproduce these 624 protected records exactly.

### 5.5 Historical caller-record archive

Before Phase 4B caller setup, the exact archive beneath `docs/dev_plan/phase_*`
and `docs/task_reports/phase_*` is:

```text
dev-plan files           111
task-report files        111
archive records          222
manifest bytes         27851
manifest SHA-256       98DDA202362D5D37E69DBD940EC2EB04B738422731B9949BED4A878815992771
aggregate file bytes  4567258
first path              docs/dev_plan/phase_0/kongono_phase_0A_codex_directive.md
last path               docs/task_reports/phase_4/Phase_4A_1_correction.md
```

Use the Section 5.3 record and ordering rules. After caller setup, exclude the
two Phase 4B caller paths to reproduce all 222 historical records byte-for-byte.

### 5.6 Locked dependency records

Require:

```text
composer.json bytes        4531
composer.json SHA-256      AADEE5ACBFBD41B32817D014A7ABEEBB48681A386CA60F53D244DA05A9A829C4
composer.lock bytes      197527
composer.lock SHA-256    823D849823E7403BD5F45C3DD85BAC17974D2FC66D004D454D9D06FE5AA1EC84
```

All root/package Composer constraints and the accepted transitional PHP state
remain unchanged.

### 5.7 Entry after Phase 4B caller setup

After the caller adds only the authenticated Phase 4B directive and zero-byte
report, require:

```text
tracked modifications   18
untracked paths          12
total status paths       30
staged paths              0
Git-visible paths       641
tracked files           629
phase_4 dev-plan files    3
phase_4 task reports      3
```

Stop on a real identity, path, content, manifest, archive, mode, stream,
inventory, history, index, or caller-record mismatch.

---

## 6. Knowledge-source authority classes

ADR 0061 must define six semantic source classes. These are architecture
concepts, not a PHP enum or serialized vocabulary in Phase 4B.

### 6.1 Governing authority

Accepted ADRs, locked doctrine, security policy, contributor/agent policy, and
other explicitly accepted decisions govern what Kongono authorizes or requires
within their stated scope.

- A pathname, directory, date, or filename number does not make a source
  governing by itself.
- A later source supersedes an earlier governing decision only when accepted
  authority explicitly records that relationship and scope.
- Governing authority describes permitted architecture and policy; it does not
  prove that current source bytes implement it.

### 6.2 Implementation evidence

Exact source, manifests, lockfiles, tests, fixtures, generated deterministic
metadata, quality configuration, and runtime probes can prove what a specified
source state contains or does.

- Implementation evidence does not authorize architecture merely because code
  exists or tests pass.
- A passing result belongs only to the exact source state, environment, command,
  and evidence scope that produced it.
- A stale completion report is not current proof after relevant bytes change.

### 6.3 Current explanatory documentation

Maintained guides, architecture documents, READMEs, glossary entries, and
examples explain accepted architecture and current behavior.

- They must cite or remain reconcilable with governing authority and
  implementation evidence.
- They do not silently override either class.
- A contradiction is an explicit documentation or implementation conflict, not
  permission to pick the most convenient sentence.

### 6.4 Historical evidence

Checkpoint commits, historical ADR context, directives, reports, release notes,
and archived proof describe a bounded earlier state.

- Historical evidence remains immutable and inspectable.
- It is not automatically current merely because it once passed.
- Historical constraints or behavior must not be blended into a current answer
  without their time/source-state label.

### 6.5 Workspace observation

Future application-specific source, configuration, diagnostics, and deterministic
tool observations belong to an explicitly scoped workspace overlay.

- Workspace observations never rewrite official Kongono framework truth.
- The overlay identifies its exact workspace source state and its matching
  Kongono framework source state separately.
- One project, tenant, user, or workspace cannot leak into another.
- Phase 4B does not read, index, retain, or transmit a workspace.

### 6.6 Derived or advisory material

Knowledge artifacts, model output, generated explanations, recommendations,
candidate patches, issue discussion, external pages, retrieved text, and other
advisory material are never independent authority.

- They must retain provenance to sources they summarize or transform.
- Repetition, model confidence, popularity, recency, or another derived source
  cannot elevate them.
- They cannot break a conflict tie or authorize tools, writes, security policy,
  release support, or architecture.

Every future included source must have one declared semantic role for each use.
The exact machine vocabulary and source inventory remain Phase 4C decisions.

---

## 7. Source-state identity contract

A knowledge claim is version-matched only when it is bound to an exact immutable
source state. `current`, `latest`, a branch name, a mutable tag, a filesystem
path, or a timestamp alone is insufficient.

The future machine contract must be capable of identifying, as applicable:

1. the Kongono project/repository origin;
2. an exact release identity when a release exists;
3. exact Git commit and tree identity when Git owns the source;
4. every included tracked modification and untracked input by exact content
   identity for a development/dirty state;
5. an explicit included-source inventory and explicit excluded scope;
6. relevant root/package manifest and lockfile identities;
7. the governing knowledge-contract/policy identity;
8. the deterministic derivation implementation identity;
9. environment and tool facts only where the claim depends on them;
10. the matching framework state and workspace state as separate identities;
    and
11. disclosure, redaction, conflict, validation, and incompleteness state.

Source-state equality requires exact equality of every semantically included
input and contract identity. Similar versions, compatible ranges, equal test
counts, matching timestamps, or a shared branch name do not establish equality.

The identity must be content-verifiable and reproducible. The exact digest
algorithm, canonical serialization, field names, schema, version syntax, and
dirty-state manifest representation remain for Phase 4C.

---

## 8. Provenance and claim contract

Every future derived claim must preserve enough information to distinguish:

1. accepted framework fact;
2. deterministic observation of an exact source state;
3. inference;
4. recommendation;
5. generated candidate content; and
6. unresolved uncertainty or conflict.

These are semantic claim classes, not a Phase 4B API or enum.

Provenance must be capable of recording, where applicable:

- claim class and scope;
- exact source-state identity;
- originating source and semantic authority class;
- exact content identity and resolvable source location;
- derivation steps and deterministic tool/contract identity;
- inclusion, exclusion, redaction, and disclosure decisions;
- validation evidence and its environment/command scope;
- supersession or conflict relationships;
- uncertainty, unsupported state, and incomplete evidence; and
- external origin and usage/licensing constraints if a later decision permits
  external material.

Provenance metadata cannot leak secrets or Protected values merely to prove
that redaction occurred. It records bounded evidence, not the forbidden value.

Generated text cannot cite another generated text as the sole authority for a
framework fact. Provenance chains must terminate in included source classes
appropriate to the claim.

---

## 9. Conflict, supersession, and precedence rules

Lock these rules:

1. Explicit accepted supersession governs only its declared decision and scope.
2. Date, filename order, ADR number, directory, commit recency, model
   confidence, majority vote, or search rank is not an implicit tie-breaker.
3. Governing authority answers what is authorized; exact implementation
   evidence answers what the identified bytes do. If they disagree, retain the
   conflict and fail affected combined/current-support claims.
4. Passing tests cannot authorize an unaccepted architecture. Conversely, an
   Accepted ADR cannot prove unimplemented behavior.
5. Current explanatory documentation cannot silently override governing or
   implementation evidence.
6. Historical evidence remains true for its historical source state and cannot
   be promoted to current evidence without fresh proof.
7. Workspace observations cannot alter framework authority, and framework
   generalizations cannot fabricate workspace facts.
8. Derived/advisory material cannot resolve a source conflict by itself.
9. Two unresolved sources in the same applicable authority class invalidate the
   affected claim unless an accepted deterministic rule resolves them.
10. A consumer may present the conflict with exact provenance, but must not
    collapse it into a confident answer.

Conflict handling is claim-scoped. An unrelated conflict need not invalidate an
entire future artifact, but the exact invalidation granularity and representation
remain Phase 4C decisions.

---

## 10. Citation and version-matching contract

Future citations must:

- identify the exact source state being discussed;
- resolve to the originating source bytes, symbol, decision, diagnostic, test,
  or command evidence with sufficient precision;
- distinguish current, historical, workspace, and derived material;
- remain usable without a model;
- avoid mutable `latest` aliases as the only reference;
- expose missing, stale, inaccessible, or mismatched sources visibly;
- not reveal excluded paths, secrets, or Protected values; and
- never imply that a citation itself grants authority.

Repository-owned source citations should be resolvable offline when their
source state is available. Exact anchor, line, symbol, URI, identifier, and
rendering syntax remain open for Phase 4C or later accepted boundaries.

An answer spanning multiple Kongono versions must identify each state and keep
their claims separated. Silent cross-version merging is prohibited.

---

## 11. Deterministic snapshot and derivation boundaries

Phase 4B accepts these requirements for the future Phase 4C implementation:

- input scope is explicit and closed, never ambient home-directory or machine
  discovery;
- source traversal is contained within authorized roots and treats symlinks,
  reparse points, alternate streams, path aliases, and escapes fail-closed;
- source counts, individual bytes, total bytes, recursion, and processing are
  bounded by later exact limits;
- ordering and duplicate handling are deterministic;
- text is data and is never executed merely because a source contains commands
  or agent instructions;
- deterministic generation performs no model call and requires no network;
- the same semantically included bytes, contract, and generator produce the
  same semantic artifact identity;
- clock time, host-specific paths, enumeration order, random values, process
  identity, and cache state cannot change semantic output;
- generation completes validation before an artifact can be marked usable;
- failed or partial generation does not replace a previously valid artifact;
- publication/replacement is atomic at the accepted artifact boundary;
- caches are derived, discardable, and never authority;
- any relevant source-state change makes the prior exact-match artifact stale;
- stale, mixed-version, conflicting, incomplete, tampered, or unverifiable
  artifacts fail visibly; and
- no production request or Kernel operation depends on artifact generation or
  an assistant being available.

Phase 4B does not choose storage, caching, publication, atomic-replacement, or
incremental-generation mechanisms.

---

## 12. Framework knowledge and workspace overlay separation

Define two future conceptual layers:

### Framework knowledge set

The versioned view derived from an exact Kongono framework source state. It may
eventually describe accepted architecture, current implementation, APIs,
packages, diagnostics, tests, documentation, PHP support, and history with the
authority and provenance rules above.

### Workspace evidence overlay

An explicitly authorized, project-scoped future observation layer for one
application workspace. It references, but cannot modify, one exact framework
knowledge state.

Lock these boundaries:

- official framework knowledge and private workspace evidence remain distinct;
- workspace evidence cannot be published as Kongono framework truth;
- one workspace cannot contaminate another workspace, user, organization,
  tenant, or source state;
- a dirty workspace requires exact included dirty-byte identity;
- closing or switching workspaces must not retain ambient project state in a
  persistent process;
- source retention, indexing, network transmission, training, and cross-project
  learning are disabled unless future explicit authority permits them; and
- Phase 4B implements neither layer and reads no application workspace.

The exact overlay API, storage, lifecycle, retention, identity, and permission
model remain future decisions.

---

## 13. Security and disclosure contract

ADR 0061 and current documentation must include:

1. allowlisted source roots and explicit containment before reading;
2. no ambient home, credential, environment, cache, vendor, VCS-internal, or
   unrelated project discovery;
3. rejection or explicit future policy for links, reparse points, alternate
   streams, device paths, and path escapes;
4. bounded parsing and no execution of source instructions;
5. dependency and repository content treated as untrusted;
6. prompt-injection text retained only as inert source data and never tool
   authority;
7. secrets, tokens, credentials, raw rejected values, and unrelated source
   excluded;
8. Safe/Protected disclosure applied before inclusion, not after model access;
9. Protected context excluded from a general knowledge artifact by default;
10. no external source, network retrieval, upload, or provider transmission in
    deterministic generation;
11. provenance that proves redaction without copying forbidden values;
12. exact workspace/project isolation in persistent processes;
13. no executable generated artifact or automatic code loading;
14. no source-state downgrade, mixed-version merge, or stale fallback hidden
    behind availability;
15. visible failure when authority, provenance, disclosure, or validation is
    incomplete; and
16. no production access or request-path dependency.

Phase 4B does not define a general secret scanner, sandbox, malware scanner,
content policy, cryptographic signature scheme, or production trust boundary.

---

## 14. Phase 4C handoff boundary

Phase 4C may implement only a deterministic, AI-independent generation and
validation slice after a separately authenticated directive chooses the
necessary concrete details.

Phase 4B deliberately leaves these decisions open for Phase 4C:

- package, root-tooling, or other ownership boundary;
- source allowlist and exact included path inventory;
- public/Internal/Experimental API status;
- schema, fields, vocabulary, identifiers, versioning, and canonical
  serialization;
- digest algorithm and source-state manifest representation;
- artifact location, extension, split/monolithic form, and commit policy;
- generator and validator language, entrypoint, command, and exit contract;
- exact count, byte, recursion, and memory/time limits;
- atomic publication and stale-artifact handling mechanism;
- cache policy and incremental versus complete regeneration;
- citation syntax and resolution mechanism;
- signing or authenticity mechanism, if any;
- exact Graph export input, if separately accepted;
- exact tests, fixtures, failure diagnostics, and external proof; and
- Composer/autoload/tooling changes, if explicitly justified and authorized.

Phase 4C must not add a model, provider, embedding, retrieval engine, Chie,
Hantei, Oshie, workspace mutation, patching, or network-dependent generation.

---

## 15. Exact authorized file boundary

### 15.1 Create exactly two documentation paths

```text
docs/adr/0061-versioned-knowledge-source-and-provenance-contract.md
docs/architecture/knowledge-foundation.md
```

### 15.2 Modify exactly fifteen existing paths

```text
AGENTS.md
ARCHITECTURE.md
CONTRIBUTING.md
README.md
SECURITY.md
docs/doctrine.md
docs/documentation-standard.md
docs/glossary.md
docs/adr/README.md
docs/architecture/developer-intelligence.md
docs/architecture/application-graph.md
docs/architecture/overview.md
docs/architecture/phase-4-roadmap.md
docs/engineering/error-handling.md
docs/engineering/testing.md
```

### 15.3 Exact accepted starting identities

| Path | Bytes | SHA-256 |
|---|---:|---|
| `AGENTS.md` | 8335 | `8B735BB51C713A407C3D1D20AE3265902E39B30000DB46EFB1A35473BDD9BB15` |
| `ARCHITECTURE.md` | 26672 | `F16C3C0DAE57F705535014E893D0757BE2CEE72CD3D12587AD919E04F6ECBE3C` |
| `CONTRIBUTING.md` | 4669 | `F69981B54A50024ECFD01870045B3D0BF7F0F0A9D3775879C6BF8A4ECAF26D19` |
| `README.md` | 19200 | `A05A9289EC9CF78ED8ED27DA4973C7553A10769FCDD69A3719C5D644AB8729F4` |
| `SECURITY.md` | 29406 | `321D3FC2E84E25A1C17180D063CD0F2624114EF814A3A4BC0335B30D10D69467` |
| `docs/doctrine.md` | 23800 | `33B32E4169105C84958A54168DA406D708C02617E3DBE6088A78E5AD603C6943` |
| `docs/documentation-standard.md` | 7836 | `93D98CBD10D5E6D0D802DB4D1403E8D49605284467D05A24D69E835F95161BA3` |
| `docs/glossary.md` | 41851 | `92986A379BE0D371DCEE1576900568F4FA61539768ED7DB8A3F1F32C16C61803` |
| `docs/adr/README.md` | 14441 | `64CAB4042C2A297B8274AD7930F83E3DB596328468AEED650D7DF13BDA8FC852` |
| `docs/architecture/developer-intelligence.md` | 9662 | `4DB9A117A202C2B679EC1D6A7FE4E787A8F1EC6631E06BDB063A3E901EE72928` |
| `docs/architecture/application-graph.md` | 9337 | `25F040BD7B97B3CBE604391C6680C19250EE85195BBC3E9373C37D0BEF89732D` |
| `docs/architecture/overview.md` | 21315 | `3BDE5380754B00465B640A841A1E9C0092404BBC6CFEEA34664184349BF4EC36` |
| `docs/architecture/phase-4-roadmap.md` | 13076 | `F39978678AFE0CB96F508055B438C3F57EA6890A657346099BF163B05EAF5658` |
| `docs/engineering/error-handling.md` | 83362 | `D694818EAC7548D0B4A993FF0102991C48BD401A60D771386FB33B3CA8261A3E` |
| `docs/engineering/testing.md` | 6095 | `8477DFE7C2DB2995C8604F6B9B0904FACB0D4BCCA0948EA561990BB9F4390801` |

### 15.4 Closed outside scope

Do not create, modify, delete, rename, move, normalize, restore, or regenerate
any other repository path.

Freeze:

- every PHP source, test, fixture, package path, and package README;
- every Composer manifest and `composer.lock`;
- `.php-cs-fixer.dist.php`, `phpstan.neon.dist`, and `phpunit.xml.dist`;
- ADRs 0001 through 0060;
- every historical directive and report;
- all other architecture, engineering, and current documentation;
- Git index, objects, commits, refs, history, branches, tags, remotes, hooks,
  signing, attributes, ignore files, and configuration.

No staging or commit is authorized.

---

## 16. ADR 0061 requirements

Create:

```text
docs/adr/0061-versioned-knowledge-source-and-provenance-contract.md
```

Use:

```text
Status: Accepted
Date: 2026-08-22
```

Include at least:

```text
Context
Decision
Source authority classes
Source-state identity
Provenance and claim classes
Conflict, precedence, and supersession
Citation and version matching
Deterministic snapshot and derivation
Framework knowledge and workspace overlay
Security and disclosure
Phase 4C handoff
Consequences
Rejected alternatives
What remains open
Related material
```

Record every semantic rule in Sections 6-14. State explicitly that Phase 4B
chooses architecture semantics only and that no machine schema, package,
artifact, source inventory, digest, command, generator, validator, model,
retrieval, workspace reader, or role runtime is implemented.

Rejected alternatives must include:

- one unclassified corpus where every file has equal authority;
- newest-file/date/commit or model-confidence precedence;
- generated knowledge as its own authority;
- silently preferring code over policy or policy over observed behavior;
- reusing historical proof as current without exact state matching;
- silently merging multiple Kongono versions;
- mixing official framework and private workspace knowledge;
- ambient filesystem crawling;
- executing instructions found in source text;
- including Protected context and redacting only after model access;
- network/model-dependent deterministic generation;
- allowing stale artifacts to remain current for availability; and
- selecting a schema or retrieval stack before Phase 4C authority.

---

## 17. Knowledge-foundation architecture document

Create:

```text
docs/architecture/knowledge-foundation.md
```

It must be the current architecture entrypoint and include:

- purpose and status;
- exact six-class authority matrix;
- source-state semantic requirements;
- six claim classes;
- provenance requirements;
- conflict/supersession decision table with at least the ten Section 9 cases;
- citation and multi-version rules;
- conceptual framework-knowledge/workspace-overlay separation;
- deterministic snapshot, build, validation, publication, stale, and invalid
  lifecycle;
- security and disclosure threat model;
- AI-independent/offline behavior;
- current versus planned versus deferred table;
- exact Phase 4C open-decision handoff;
- deliberate exclusions; and
- links to ADR 0061, ADR 0060, developer intelligence, Phase 4 roadmap,
  Application Graph, doctrine, security, documentation standard, diagnostics,
  and testing.

Include a conceptual flow no more concrete than:

```text
explicit authorized source set
            |
deterministic bounded snapshot
            |
source-state identity + conflict/disclosure validation
            |
derived versioned knowledge artifact
            |
future Chie / Hantei / Oshie consumers
```

Show invalid, conflicting, stale, mixed-version, and incomplete states leaving
the usable path visibly. Do not name a file, package, type, schema, digest,
command, index, model, or provider in the flow.

---

## 18. Current-document reconciliation

Update only the fifteen authorized existing paths. Preserve current Phase 3 and
Phase 4A detail and add the smallest complete Phase 4B reconciliation.

All current documents must agree that:

- Phase 4B accepts the semantic knowledge-source/provenance architecture only;
- ADR 0061 and `knowledge-foundation.md` are the current authorities;
- exactly six source authority classes and six claim classes are architectural
  concepts, not implemented enums or schema rows;
- exact source state, provenance, conflict visibility, disclosure, and version
  matching are required;
- generated artifacts and model output never become independent authority;
- framework knowledge and workspace observations stay separated;
- no knowledge artifact, generator, validator, package, source inventory,
  schema, digest, command, retrieval, model, workspace read, or assistant exists;
- Application Graph remains unchanged;
- Phase 4C is next and must select the concrete deterministic artifact slice;
  and
- the sequential Phase 4 dependency order remains unchanged.

Specific duties:

- `AGENTS.md`: add source-class, source-state, provenance, conflict, stale-state,
  and no-execution/no-authority-elevation rules without changing executor or
  report policy.
- `README.md`: mark Phase 4B architecture as accepted and link the knowledge
  foundation while stating no artifact or role exists.
- `ARCHITECTURE.md`: add ADR 0061 and the knowledge foundation as architecture
  only, preserve fourteen packages and zero new edges.
- `CONTRIBUTING.md`: require exact state/evidence labels and prohibit using
  generated or historical material as current proof.
- `SECURITY.md`: add source ingestion, path containment, prompt injection,
  disclosure-before-inclusion, workspace isolation, stale/mixed-version, and
  no-execution threats.
- `docs/doctrine.md`: lock exact-state, provenance, derived-non-authority,
  conflict-visible, offline, and workspace-separation doctrine.
- `docs/documentation-standard.md`: require source-state and authority labels
  where version or evidence matters; derived material must trace to exact
  sources.
- `docs/glossary.md`: define Knowledge Source, Source Authority Class, Source
  State, Provenance, Knowledge Claim, Knowledge Conflict, Knowledge Artifact,
  Framework Knowledge Set, and Workspace Evidence Overlay.
- `docs/adr/README.md`: add ADR 0061 in sequence and identify Phase 4B as the
  current accepted architecture boundary.
- `docs/architecture/developer-intelligence.md`: link and summarize the accepted
  Phase 4B contract without changing role boundaries or the sequential map.
- `docs/architecture/application-graph.md`: reiterate that only a separately
  accepted deterministic export could become a source and no current Graph
  behavior changes.
- `docs/architecture/overview.md`: link the new architecture and accurately
  label 4B current versus 4C planned.
- `docs/architecture/phase-4-roadmap.md`: mark Phase 4B accepted architecture-
  only and Phase 4C next while preserving the exact table and sequential graph.
- `docs/engineering/error-handling.md`: describe diagnostics as package-owned
  source material whose disclosure classification precedes inclusion; no
  Protected general corpus.
- `docs/engineering/testing.md`: state that test/quality evidence is bound to
  an exact source/environment/command scope and does not become architectural
  authority or reusable current proof after relevant state changes.

Do not rewrite historical ADRs or completion reports.

---

## 19. Documentation and file-quality rules

All seventeen created or modified documents must be:

- UTF-8 without BOM;
- LF-only;
- exactly one final LF;
- free of trailing whitespace and NUL;
- ordinary, non-reparse files with only the default data stream; and
- intended as Git mode `100644`.

Use repository-relative Markdown links and validate every local link in all
seventeen documents. Require `git diff --check` to pass.

Write plainly. Distinguish architecture semantics from machine schema and
implementation. Do not use `authoritative`, `current`, `version-matched`,
`valid`, `verified`, or `supported` without explaining the applicable source
state and evidence.

Do not make performance, completeness, security, compatibility, or correctness
claims without bounded evidence.

---

## 20. Validation sequence

### Gate A - authenticated preflight

Before mutation:

- authenticate this directive externally;
- verify the zero-byte active report;
- read every authority completely;
- verify exact Git/checkpoint/status/caller evidence;
- reconstruct the 639 complete, 624 protected, and 222 historical manifests;
- verify all fifteen target identities;
- verify root manifest, lockfile, package/PHP/ADR, phase-folder, mode, stream,
  and ordinary-file inventories;
- verify the two Phase 4B-created paths do not exist; and
- prove no stage or Git mutation exists.

Run literal pre-change:

```text
composer quality
```

Require:

```text
Composer validation      pass with only accepted warning categories
locked security audit    zero advisories
platform requirements    pass
PHP CS Fixer             0 of 277 files fixable
PHPStan                  zero errors
PHPUnit                  561 tests, 13004 assertions
```

If the audit fails solely because managed network/cache access is denied,
finish the failed command, prove repository and caller state unchanged, and run
the identical literal command once with approved access. No other retry, skip,
waiver, platform ignore, baseline, suppression, exclusion, memory change, or
count relaxation is authorized.

### Gate B - ADR and knowledge architecture

Create ADR 0061 and `knowledge-foundation.md`. Validate:

- exact six source authority classes;
- exact source-state requirements;
- exact six claim classes;
- provenance termination in appropriate source classes;
- all ten conflict rules;
- citation and cross-version behavior;
- framework/workspace separation;
- deterministic, offline, bounded, non-executing derivation requirements;
- all sixteen security requirements;
- exact Phase 4C open decisions;
- no concrete implementation selection; and
- all required sections and links.

Stop if safe writing would require a schema, type, package, digest, file format,
command, source inventory, tool, or other Phase 4C decision.

### Gate C - current-document reconciliation

Modify only Section 15.2 paths. Validate:

- current/accepted/planned/deferred language;
- source and claim class counts;
- exact-state, provenance, conflict, stale, mixed-version, disclosure, and
  offline rules;
- no role boundary or sequential phase order changed;
- no current package/API/Graph/runtime behavior changed;
- no knowledge artifact or concrete mechanism described as implemented;
- all local links and Section 19 file-quality checks; and
- `git diff --check`.

No formatter may rewrite Markdown. Do not run `composer cs:fix`.

### Gate D - canonical final quality

Run literal:

```text
composer quality
```

Require the same exact results as Gate A. The same single approved-access retry
rule applies only to a managed network/cache denial during this Gate D
invocation. Do not substitute pre-change evidence for final evidence.

### Gate E - final preservation and report

Before report population prove:

- Section 21 exact status and path inventories;
- exactly two created and fifteen modified documentation paths;
- every other path reproduces the 624-record protected manifest after excluding
  Phase 4B callers and created paths;
- all 222 prior caller records remain exact;
- all Phase 4A/4A.1 caller records remain exact;
- all PHP, package, manifest, lockfile, dependency, tool configuration, test,
  fixture, runtime, and non-authorized documentation remains unchanged;
- no stage, object, commit, ref, history, branch, tag, remote, hook, signing,
  ignore, attribute, or Git configuration mutation occurred;
- the active report remains zero-byte; and
- no Phase 4C behavior exists.

Only then populate the report.

---

## 21. Exact expected final state

Before and after report population require:

```text
tracked modifications   18
untracked paths          14
total status paths       32
staged paths              0
Git-visible paths       643
tracked files           629
package directories      14
package manifests        14
production PHP files    126
test PHP files          151
style-covered PHP       277
ADR files                61
phase_4 dev-plan files    3
phase_4 task reports      3
```

The eighteen tracked modifications remain the completed Phase 4A list. Phase
4B changes thirteen of those tracked paths and two existing untracked Phase 4A
architecture documents, so the tracked-status count remains eighteen.

The exact fourteen untracked paths are:

```text
docs/adr/0059-rolling-current-minor-php-baseline.md
docs/adr/0060-human-centered-developer-intelligence-family.md
docs/adr/0061-versioned-knowledge-source-and-provenance-contract.md
docs/architecture/developer-intelligence.md
docs/architecture/knowledge-foundation.md
docs/architecture/phase-4-roadmap.md
docs/dev_plan/phase_4/kongono_phase_4A_1_codex_directive_correction.md
docs/dev_plan/phase_4/kongono_phase_4A_codex_directive.md
docs/dev_plan/phase_4/kongono_phase_4B_codex_directive.md
docs/engineering/php-support-policy.md
docs/task_reports/phase_3/Phase_3H_1_correction.md
docs/task_reports/phase_4/Phase_4A.md
docs/task_reports/phase_4/Phase_4A_1_correction.md
docs/task_reports/phase_4/Phase_4B.md
```

No staging or commit is created.

---

## 22. Prohibited scope

Do not implement, select, or change:

- any PHP source, test, fixture, package, namespace, type, API, diagnostic,
  service definition, Graph node/relation, or runtime behavior;
- any root/package Composer field, PHP constraint, dependency, plugin,
  autoloading, script, repository, platform entry, or lockfile byte;
- a knowledge schema, machine vocabulary, field name, identifier, canonical
  serializer, digest algorithm, source manifest, artifact extension/location,
  generator, validator, cache, storage, publication mechanism, or command;
- an exact included-source path inventory or exact size/count/time limit;
- a Graph export or Graph knowledge integration;
- search, indexing, retrieval, embedding, vector storage, prompt, model,
  provider, SDK, network, remote source, or external service;
- Chie, Hantei, Oshie, workspace reader, workspace indexing, source retention,
  training, feedback, cross-project learning, patching, mutation, or auto-fix;
- credentials, Git operations, publication, deployment, rollback, production
  access, monitoring, autonomous remediation, or operations guardian;
- signing, attestation, release packaging, semantic versioning, 1.0, or PHP
  constraint migration;
- routing, DI/container, HTTP, ORM/database, migration, template, frontend,
  auth, session, cache, queue, event, mail, scheduler, realtime, extension, CLI,
  telemetry, or another later framework capability;
- Symfony, Laravel, or another framework component;
- staging, commit, amend, reset, restore, checkout, stash, clean, rebase, merge,
  branch/tag/remote mutation, push, hook bypass, signing override, Git
  configuration, history rewrite, or Phase 4C work.

---

## 23. Stop and bounded-correction rules

Stop and report exact expected and observed evidence if:

- directive/caller, Git, manifest, archive, target, mode, stream, or inventory
  evidence differs;
- a protected or out-of-scope path changes;
- the six source classes, six claim classes, conflict rules, or security rules
  cannot be documented without contradiction;
- a concrete Phase 4C implementation choice is required;
- any Composer or runtime byte changes;
- canonical quality has a genuine non-network failure;
- current documentation remains contradictory after authorized reconciliation;
- final counts or path scope differ; or
- a prohibited action would be required.

Use:

```text
STOP
REPORT EXPECTED AND OBSERVED VALUES
PRESERVE WORKTREE, INDEX, HISTORY, AND CALLER RECORDS
DO NOT GUESS OR EXPAND SCOPE
```

Within the exact seventeen-document boundary, ordinary link, Markdown,
spelling, terminology, or internal consistency defects discovered by the
required gates may be corrected and the focused documentation gates rerun.
This does not permit a new architecture decision, path, schema, mechanism, or
scope expansion.

A non-mutating checker/parser/quoting defect may be simplified and rerun only
after proving repository state unchanged. Report it as procedural evidence.

---

## 24. Phase 4B task completion report

After every non-report gate passes, populate only:

```text
docs/task_reports/phase_4/Phase_4B.md
```

Use exactly these headings:

```text
# PHASE 4B TASK COMPLETION REPORT
## 1. STATUS
## 2. SUMMARY
## 3. BASELINE VERIFIED
## 4. CALLER RECORDS
## 5. FILES CREATED
## 6. FILES MODIFIED
## 7. SOURCE AUTHORITY CLASSES
## 8. SOURCE-STATE IDENTITY
## 9. PROVENANCE AND CLAIM CLASSES
## 10. CONFLICT AND SUPERSESSION
## 11. CITATION AND VERSION MATCHING
## 12. FRAMEWORK AND WORKSPACE SEPARATION
## 13. PHASE 4C HANDOFF
## 14. ARCHITECTURAL DECISIONS
## 15. DEVIATIONS FROM DIRECTIVE
## 16. DEPENDENCIES ADDED/REMOVED
## 17. ENVIRONMENT
## 18. COMMANDS RUN
## 19. VALIDATION RESULTS
## 20. DOCUMENTATION REVIEW
## 21. SECURITY AND FILE-SAFETY REVIEW
## 22. WARNINGS / KNOWN ISSUES
## 23. QUESTIONS / BLOCKERS
## 24. FINAL GIT STATUS
## 25. ACCEPTANCE CRITERIA
## 26. NEXT STEP
```

Report:

- independent directive identity;
- Phase 4A/4A.1 caller identities;
- complete/protected/archive manifest proofs;
- exact two-created/fifteen-modified inventories;
- all six source authority and six claim classes;
- source-state and provenance semantics;
- conflict, supersession, citation, multi-version, disclosure, and workspace
  rules;
- exact Phase 4C open decisions;
- proof that no schema, package, artifact, digest, command, generator,
  validator, model, retrieval, workspace read, or role runtime was implemented;
- exact pre/post quality and file/link validation results;
- protected and historical preservation;
- exact final `18/14/32/0` state and no Git mutation;
- every acceptance criterion as PASS or FAIL; and
- final report byte count and SHA-256 returned independently outside the report
  body.

Its exact final sentence must be:

```text
Await architect review before Phase 4C.
```

---

## 25. Acceptance criteria

Phase 4B is Complete only when all are true:

1. directive identity matched independently supplied values;
2. active report began zero-byte and remained zero-byte through all non-report
   gates;
3. Phase 4A/4A.1 caller records and exact entry state matched;
4. complete, protected, and historical manifests matched;
5. all required authorities and fifteen target identities matched;
6. pre-change literal `composer quality` passed with exact results;
7. exactly two documentation paths were created;
8. exactly fifteen existing documentation paths were modified;
9. no other repository content changed except final report population;
10. ADR 0061 is Accepted, correctly dated, and contains all required sections;
11. exactly six source authority classes are defined semantically;
12. exact source-state identity requirements are complete;
13. exactly six claim classes and provenance termination rules are defined;
14. all ten conflict/supersession rules are present;
15. citation and silent-cross-version-merge prohibitions are explicit;
16. deterministic snapshot, validation, stale, invalid, and atomic-publication
    requirements are explicit without selecting mechanisms;
17. framework knowledge and workspace observation remain separate;
18. all sixteen security/disclosure requirements are present;
19. Phase 4C handoff lists every concrete open decision without implementing
    one;
20. Application Graph, role responsibilities, and sequential A-H order remain
    unchanged;
21. current documents accurately distinguish implemented, accepted, planned,
    and deferred behavior;
22. links, UTF-8, line endings, final LF, whitespace, NUL, ordinary-file,
    reparse, stream, mode, and `git diff --check` gates pass;
23. final literal `composer quality` passes with zero advisories, platform
    success, 0/277 style findings, zero PHPStan errors, and 561 tests/13004
    assertions;
24. the 624 protected paths and 222 prior caller records remain exact;
25. all PHP, packages, manifests, lockfile, dependencies, tool configuration,
    tests, fixtures, runtime, historical records, and non-authorized
    documentation remain unchanged;
26. no stage, object, commit, ref, history, branch, tag, remote, hook, signing,
    ignore, attribute, or Git configuration mutation occurred;
27. final state is exactly `18/14/32/0`, 643 visible, and 629 tracked;
28. no Phase 4C or later implementation occurred; and
29. the populated report satisfies the exact heading, final-sentence, file-
    quality, and independent-identity contract.

Any failed criterion means status cannot be Complete.

---

## 26. Required final outcome

Kongono has one Accepted, versioned, inspectable, AI-independent knowledge-
source and provenance architecture. Sources retain distinct authority roles;
claims bind to exact immutable source states; provenance terminates in
appropriate evidence; conflicts, stale data, mixed versions, incomplete proof,
and disclosure uncertainty fail visibly; framework truth remains separate from
workspace observation; and derived knowledge never becomes its own authority.

No knowledge artifact, schema, package, generator, validator, source inventory,
digest, command, index, retrieval engine, model, provider, workspace reader,
assistant, Composer change, API, or runtime behavior exists because of Phase
4B.

Then await architect review before Phase 4C.
