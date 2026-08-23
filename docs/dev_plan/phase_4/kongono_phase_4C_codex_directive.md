# CODEX IMPLEMENTATION DIRECTIVE — KONGONO PHASE 4C

## Deterministic Framework-Knowledge Artifact Generation and Validation

**Directive type:** authenticated bounded implementation  
**Repository:** `C:\Users\antho\Dev\kongono`  
**Phase:** 4C  
**Date:** 2026-08-22  
**Execution posture:** executor, not architect  

---

## 1. Mission

Implement the first concrete Kongono knowledge-foundation slice: one acyclic
`kongono/knowledge` package that deterministically builds, validates, and may
atomically publish a versioned, inspectable, AI-independent framework-
knowledge artifact from one closed 31-source Phase 4 foundation inventory.

The artifact is derived evidence, never authority. It embeds exact approved
source bytes with authority labels and bounded provenance. It can be generated
and validated without a model, provider, account, network, workspace reader,
retrieval system, or application runtime.

Phase 4C resolves the concrete choices left open by Phase 4B only for this
single bounded slice. It does not implement Chie, Hantei, Oshie, a general
knowledge corpus, application-workspace knowledge, search, retrieval,
embeddings, patching, mutation of framework source, or any later Phase 4 work.

---

## 2. Independent directive authentication

Before reading this file beyond the minimum needed to authenticate it, receive
from the architect outside this file:

```text
expected byte count
expected SHA-256
```

Calculate both independently and compare exactly. The local directive must not
authenticate itself.

If either value is absent or differs:

```text
STOP
REPORT EXPECTED AND OBSERVED VALUES
PRESERVE WORKTREE, INDEX, HISTORY, AND CALLER RECORDS
DO NOT GUESS OR EXPAND SCOPE
```

Do not run broad validation, edit, install, update, stage, commit, repair,
normalize, or populate the report before this gate passes.

---

## 3. Required authority reading

After authentication, read completely:

```text
AGENTS.md
ARCHITECTURE.md
CONTRIBUTING.md
README.md
SECURITY.md
composer.json
phpunit.xml.dist
phpstan.neon.dist
.php-cs-fixer.dist.php
docs/doctrine.md
docs/documentation-standard.md
docs/glossary.md
docs/adr/0059-rolling-current-minor-php-baseline.md
docs/adr/0060-human-centered-developer-intelligence-family.md
docs/adr/0061-versioned-knowledge-source-and-provenance-contract.md
docs/architecture/application-graph.md
docs/architecture/developer-intelligence.md
docs/architecture/knowledge-foundation.md
docs/architecture/overview.md
docs/architecture/phase-4-roadmap.md
docs/engineering/api-status.md
docs/engineering/dependency-security.md
docs/engineering/error-handling.md
docs/engineering/package-layout.md
docs/engineering/php-support-policy.md
docs/engineering/testing.md
docs/engineering/toolchain.md
docs/dev_plan/phase_4/kongono_phase_4A_codex_directive.md
docs/dev_plan/phase_4/kongono_phase_4A_1_codex_directive_correction.md
docs/dev_plan/phase_4/kongono_phase_4B_codex_directive.md
docs/dev_plan/phase_4/kongono_phase_4B_1_codex_directive_correction.md
docs/dev_plan/phase_4/kongono_phase_4B_2_codex_directive_correction.md
docs/task_reports/phase_4/Phase_4A.md
docs/task_reports/phase_4/Phase_4A_1_correction.md
docs/task_reports/phase_4/Phase_4B.md
docs/task_reports/phase_4/Phase_4B_1_correction.md
docs/task_reports/phase_4/Phase_4B_2_correction.md
```

Read the relevant existing package patterns for API metadata, diagnostic
catalogs and contexts, exception markers/bases, Composer manifests, package
tests, and the root architecture test before writing code.

If these authorities conflict with this directive outside an explicit Phase
4C choice, stop. Do not silently reinterpret Phase 4B.

---

## 4. Locked caller records

Require these exact Phase 4 records:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `docs/dev_plan/phase_4/kongono_phase_4A_codex_directive.md` | 52909 | `1BEDE36ED1B25324B9C82C945A0B228DEB7975E9A637DC911AAE23CE41FECF48` |
| `docs/dev_plan/phase_4/kongono_phase_4A_1_codex_directive_correction.md` | 20738 | `D92BC7368BE54842A2DB4568B5F31EDDF5F8D6E9DB90A0A5523F35ECD686A258` |
| `docs/dev_plan/phase_4/kongono_phase_4B_codex_directive.md` | 50718 | `92F11560F90EB17488C2CCB9FB2EF1C7E036AD2B0555BAC0D2368E16EA1C48A0` |
| `docs/dev_plan/phase_4/kongono_phase_4B_1_codex_directive_correction.md` | 23062 | `40C41B3923F8E51D96F65AE591FA47A18480C6C8E0FCDC2971EA0FB0BCED329E` |
| `docs/dev_plan/phase_4/kongono_phase_4B_2_codex_directive_correction.md` | 22574 | `45F88D3AACA442A63C78808DB706E8A608E56CE17B018606C7FBBE71BF279354` |
| `docs/task_reports/phase_4/Phase_4A.md` | 15610 | `EDFC3BD489C6AC078D1AAE31BD5EF46D616E2032F4FF23B089012858BF799202` |
| `docs/task_reports/phase_4/Phase_4A_1_correction.md` | 10658 | `7B6A6A70DF5E6A65989B179AC08E2986FBC08938F9041196BE257A7B5824ED4E` |
| `docs/task_reports/phase_4/Phase_4B.md` | 0 | `E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855` |
| `docs/task_reports/phase_4/Phase_4B_1_correction.md` | 0 | `E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855` |
| `docs/task_reports/phase_4/Phase_4B_2_correction.md` | 16606 | `02FCFF780726D73E93EBB62AC6988D2D6D571997EEB138EA0AB47AF605F4B787` |

The active report must begin as an ordinary, untracked, unstaged, zero-byte
file absent from `HEAD`:

```text
docs/task_reports/phase_4/Phase_4C.md
```

Its initial SHA-256 must be:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

Keep it zero-byte through every non-report gate.

---

## 5. Exact entry baseline

After the caller places this directive and creates the zero-byte report, but
before executor mutation, require:

```text
branch                  main
HEAD                    6aacafc92f07b567818173d660e2e182dfa83d23
HEAD tree               d7fda17d27a15954cdd213a1c323aaeb37a2b574
HEAD parent             647053d4bddc4c76090bca9bf3d693faa5880c52
commit count            11
tracked modifications   18
untracked paths          20
total status paths       38
staged paths              0
Git-visible paths       649
tracked files           629
package directories      14
package manifests        14
production PHP files    126
test PHP files          151
style-covered PHP       277
ADR files                61
phase_4 dev-plan files    6
phase_4 task reports      6
```

Also require one local branch named `main`, zero remotes, zero tags, one
repository-owned ref, no unexpected refs except app-managed
`refs/codex/turn-diffs/**`, a semantic index equal to `HEAD`, and no staged
path.

Require `composer.lock` to remain:

```text
bytes:         197527
SHA-256:       823D849823E7403BD5F45C3DD85BAC17974D2FC66D004D454D9D06FE5AA1EC84
content-hash:  cd2451460b76f0afd26d8936c011994f
packages:      18
packages-dev:  66
```

Require ADR 0062, `framework-knowledge-artifact.md`, and
`packages/knowledge` to be absent.

---

## 6. Canonical preservation manifests

### 6.1 Construction

Use Git-visible paths from:

```powershell
git -c safe.directory='C:/Users/antho/Dev/kongono' ls-files --cached --others --exclude-standard
```

Use typed `System.String[]`, `StringComparer.Ordinal`, records of:

```text
<path><TAB><physical-byte-count><TAB><uppercase-SHA-256>
```

Serialize with UTF-8 without BOM, one LF only between records, and no terminal
LF. Use an unambiguous equivalent of:

```powershell
[string[]] $paths = @($candidatePaths)
[Array]::Sort($paths, [StringComparer]::Ordinal)
$serialized = [string]::Join("`n", [string[]] $lines.ToArray())
$bytes = [Text.UTF8Encoding]::new($false).GetBytes($serialized)
```

Prove the last manifest byte is not `0A`.

### 6.2 Complete pre-Phase-4C content baseline

Exclude only the active Phase 4C directive and report. Require:

```text
records:              647
manifest bytes:       81332
SHA-256:              8FC44B73F9BD05C2E1FCBA3EDF90B64E381A8BDCB73E3C281958F5CB98AE1A8D
aggregate file bytes: 7348516
first path:           .editorconfig
last path:            tests/Tooling/EnvironmentTest.php
terminal LF:          absent
```

### 6.3 Protected baseline

Exclude the active caller pair and all fifty-five Phase 4C content paths: the
twenty-nine created paths in Section 20.1 and twenty-six modified paths in
Section 20.2. Require:

```text
records:              621
manifest bytes:       78825
SHA-256:              8C738F82D5345E91127F528C5EDF2A53A6F679DC17CB329778AE0A34BAF6CDC3
aggregate file bytes: 6659265
first path:           .editorconfig
last path:            tests/Tooling/EnvironmentTest.php
terminal LF:          absent
```

Reproduce this exact protected manifest after implementation and before report
population.

### 6.4 Historical archive

Exclude only the active Phase 4C caller pair from paths under numbered
dev-plan/task-report folders. Require:

```text
records:              228
manifest bytes:       28608
SHA-256:              83C8DAC6C9979871F77314922344C2D8346E8592DDD7C7A6A4B52D5854E8E638
aggregate file bytes: 4680218
first path:           docs/dev_plan/phase_0/kongono_phase_0A_codex_directive.md
last path:            docs/task_reports/phase_4/Phase_4B_2_correction.md
terminal LF:          absent
```

All 228 records are immutable.

---

## 7. Phase 4C architecture decision

Create and accept:

```text
ADR 0062: Deterministic Framework-Knowledge Artifact Package and Format
```

ADR 0062 must record:

1. package ownership by `kongono/knowledge`;
2. the dependency edge only to API Metadata and Diagnostics;
3. Experimental generation, validation, publication, artifact, source, and
   authority surfaces;
4. one Public package exception marker and Internal machinery;
5. one closed 31-source `phase4-foundation` inventory;
6. canonical JSON format `kongono.framework-knowledge` version `1`;
7. exact source-set and generator-implementation identity;
8. whole-source citation references;
9. compact UTF-8 JSON with exactly one final LF;
10. full regeneration on every run, no cache or incremental mode;
11. in-memory generation/validation plus optional content-addressed atomic
    initial publication;
12. no overwrite, mutable pointer, replacement, deletion, or stale fallback;
13. SHA-256 integrity only, not authenticity or signing;
14. no Application Graph input in this slice;
15. no checked-in generated artifact;
16. no model, retrieval, workspace, network, role, mutation, or production
    dependency;
17. explicit limits and failure diagnostics; and
18. the Phase 4D read-only handoff.

Status is `Accepted`; date is `2026-08-22`.

Rejected alternatives must include an equal-authority corpus, runtime globbing,
ambient discovery, generated prose as authority, timestamps/randomness in
semantic output, mutable `latest`, silent replacement, overwrite-on-stale,
database/vector index, model-generated artifacts, and a package dependency on
the Application Graph or a role package.

---

## 8. Package and dependency boundary

Create `packages/knowledge` with Composer identity:

```text
name:       kongono/knowledge
namespace:  Kongono\Knowledge\
type:       library
PHP:        >=8.5
requires:   ext-hash *, ext-json *, kongono/api-metadata *, kongono/diagnostics *
autoload:   Kongono\Knowledge\ => src/
autoload-dev: Kongono\Knowledge\Tests\ => tests/
bin:        bin/kongono-knowledge
```

The package is an acyclic low-level knowledge leaf. No existing package may
depend on it in Phase 4C. Only the root requires `kongono/knowledge: @dev`.
Future role packages may depend on it only under later directives.

The package must not depend on Kernel, Application Graph, DI, HTTP, Routing,
Web Application, Web Runtime, PHP CGI, Diagnostic Presentation, Diagnostic
Activation, Diagnostic Handler, PSR packages, Symfony, Laravel, a database,
network client, model SDK, serializer library, or any new third-party package.

No service registration, DI binding, Graph node, global singleton, environment
read, process-wide cache, static mutable state, or runtime auto-discovery is
created.

---

## 9. API status and exact type inventory

Create exactly eighteen production PHP files, each declaring exactly one type
and carrying exactly one `#[Api(...)]` attribute.

### Public

```text
Kongono\Knowledge\Exception\ExceptionInterface
```

It extends `Throwable` and exposes the same four presentation-neutral
diagnostic methods as other package markers.

### Experimental

```text
Kongono\Knowledge\Artifact\FrameworkKnowledgeArtifact
Kongono\Knowledge\Artifact\FrameworkKnowledgeArtifactGenerator
Kongono\Knowledge\Artifact\FrameworkKnowledgeArtifactPublisher
Kongono\Knowledge\Artifact\FrameworkKnowledgeArtifactValidator
Kongono\Knowledge\Exception\InvalidKnowledgeArtifactException
Kongono\Knowledge\Exception\InvalidKnowledgeSourceException
Kongono\Knowledge\Exception\KnowledgePublicationException
Kongono\Knowledge\Exception\StaleKnowledgeArtifactException
Kongono\Knowledge\Source\KnowledgeSource
Kongono\Knowledge\Source\KnowledgeSourceAuthority
```

### Internal

```text
Kongono\Knowledge\Command\KnowledgeCommand
Kongono\Knowledge\Diagnostic\KnowledgeDiagnostic
Kongono\Knowledge\Exception\KnowledgeException
Kongono\Knowledge\Internal\CanonicalJson
Kongono\Knowledge\Internal\PathGuard
Kongono\Knowledge\Source\KnowledgeSourceDefinition
Kongono\Knowledge\Source\KnowledgeSourceInventory
```

No alias, compatibility forwarder, trait, anonymous production type, dynamic
property, magic accessor, property hook, reflection-based mapper, or generated
class is authorized. Use explicit immutable values and readonly state where
applicable. Modern PHP is required, but novelty is not a substitute for a clear
contract.

---

## 10. Source authority and source values

`KnowledgeSourceAuthority` is a string-backed enum with exactly these ordered
cases and values:

```text
GoverningAuthority              governing-authority
ImplementationEvidence          implementation-evidence
CurrentExplanatoryDocumentation current-explanatory-documentation
HistoricalEvidence              historical-evidence
WorkspaceObservation            workspace-observation
DerivedOrAdvisoryMaterial        derived-or-advisory-material
```

The enum mirrors ADR 0061's semantic classes for this Experimental machine
format. It does not change their meaning.

The Phase 4C framework inventory may contain only the first three classes.
Reject Historical, Workspace, and Derived entries. Their enum cases exist so a
validator cannot silently reinterpret an unsupported token.

`KnowledgeSource` is an immutable detached value exposing only:

```text
reference
ordinal
path
authority
bytes
sha256
content
```

It contains no filesystem handle, root path, timestamp, model metadata,
mutable collection, lazy read, callback, or authority decision.

`KnowledgeSourceDefinition` and `KnowledgeSourceInventory` are Internal. The
inventory owns exact definitions, order, schema identity, canonical bytes, and
inventory SHA-256. Returned arrays are detached PHP lists.

---

## 11. Exact 31-source inventory

Create canonical resource:

```text
packages/knowledge/resources/framework-source-inventory.json
```

Its schema is:

```text
schema:   kongono.framework-knowledge-source-inventory
version:  1
scope:    phase4-foundation
sources:  exactly 31 ordered records
```

Each record has exactly `path` then `authority`. Encode compact canonical JSON
with `JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR`
and exactly one final LF. Records are ordered by raw path bytes.

The exact inventory is:

| Path | Authority |
|---|---|
| `.php-cs-fixer.dist.php` | `implementation-evidence` |
| `AGENTS.md` | `governing-authority` |
| `ARCHITECTURE.md` | `current-explanatory-documentation` |
| `CONTRIBUTING.md` | `governing-authority` |
| `README.md` | `current-explanatory-documentation` |
| `SECURITY.md` | `governing-authority` |
| `composer.json` | `implementation-evidence` |
| `composer.lock` | `implementation-evidence` |
| `docs/adr/0059-rolling-current-minor-php-baseline.md` | `governing-authority` |
| `docs/adr/0060-human-centered-developer-intelligence-family.md` | `governing-authority` |
| `docs/adr/0061-versioned-knowledge-source-and-provenance-contract.md` | `governing-authority` |
| `docs/adr/0062-deterministic-framework-knowledge-artifact.md` | `governing-authority` |
| `docs/architecture/application-graph.md` | `current-explanatory-documentation` |
| `docs/architecture/developer-intelligence.md` | `current-explanatory-documentation` |
| `docs/architecture/framework-knowledge-artifact.md` | `current-explanatory-documentation` |
| `docs/architecture/knowledge-foundation.md` | `current-explanatory-documentation` |
| `docs/architecture/overview.md` | `current-explanatory-documentation` |
| `docs/architecture/phase-4-roadmap.md` | `current-explanatory-documentation` |
| `docs/doctrine.md` | `governing-authority` |
| `docs/documentation-standard.md` | `governing-authority` |
| `docs/engineering/api-status.md` | `current-explanatory-documentation` |
| `docs/engineering/dependency-security.md` | `current-explanatory-documentation` |
| `docs/engineering/error-handling.md` | `current-explanatory-documentation` |
| `docs/engineering/package-layout.md` | `current-explanatory-documentation` |
| `docs/engineering/php-support-policy.md` | `current-explanatory-documentation` |
| `docs/engineering/testing.md` | `current-explanatory-documentation` |
| `docs/engineering/toolchain.md` | `current-explanatory-documentation` |
| `docs/glossary.md` | `current-explanatory-documentation` |
| `packages/knowledge/composer.json` | `implementation-evidence` |
| `phpstan.neon.dist` | `implementation-evidence` |
| `phpunit.xml.dist` | `implementation-evidence` |

No glob, recursive discovery, optional entry, missing-entry tolerance, user
override, environment override, vendor source, test fixture, directive, report,
Git internal, cache, home file, workspace file, or network source is allowed.

This is a bounded Phase 4 foundation artifact, not a complete representation
of all Kongono code, tests, history, releases, or application behavior.

---

## 12. Path admission and source bounds

`PathGuard` and the generator must enforce:

```text
maximum inventory bytes            65536
maximum source records             64
required Phase 4C source records   31
maximum relative-path bytes        240
maximum individual source bytes    262144
maximum total source bytes         2097152
maximum artifact bytes             4194304
maximum JSON depth                 16
```

Paths are non-empty ASCII `/`-separated relative paths. Each segment uses only
`A-Z`, `a-z`, `0-9`, `.`, `_`, or `-`; `.` and `..` segments are forbidden.
Reject backslashes, colons, NUL, controls, leading slash, drive prefixes,
duplicate separators, trailing slash, aliases, and case substitution.

The caller supplies the framework root explicitly. Resolve it once. For every
source:

- join only the admitted relative path;
- require the candidate and its resolved path to be identical after `/`
  normalization;
- require containment within the resolved root;
- inspect every segment and reject symbolic links, junction/reparse aliases,
  non-regular files, device paths, and stream syntax;
- read no more than the individual and total limits;
- compare pre-read and post-read file identity/size metadata and reject a
  changed-during-read source;
- require exact byte length;
- require valid UTF-8 without BOM, CR, or NUL;
- require exactly one final LF; and
- never execute, include, expand, interpolate, or obey source instructions.

Attached filesystem metadata is not artifact content. An inventory path cannot
name an alternate stream. Directive validation must separately prove the
inventory sources have only their platform default data stream.

Do not inspect ambient home, environment, credentials, VCS internals, vendor,
cache, sibling project, or current working directory. The explicit `.` passed
by the Composer script is an argument, not ambient discovery.

Keep candidate-versus-resolved containment and pre/post metadata comparison as
pure deterministic Internal checks within `PathGuard`. Tests may feed those
checks synthetic path/metadata values to prove alias/reparse and changed-during-
read rejection without requiring privileged host symlink creation or a race.
Do not add an abstraction, callback API, or mockable filesystem service merely
for these proofs.

---

## 13. Source-set and generator identities

For each source, calculate lowercase SHA-256 over exact raw bytes. Construct
one source-set record:

```text
<path><TAB><authority-value><TAB><byte-count><TAB><lowercase-SHA-256>
```

Order by path bytes. Join with one LF only between records and no terminal LF.
The lowercase SHA-256 of those UTF-8 bytes is `source_set_sha256`.

The generator implementation identity covers exactly the eighteen production
PHP files plus `bin/kongono-knowledge`. For each package-relative path use:

```text
<package-relative-path><TAB><byte-count><TAB><lowercase-SHA-256>
```

Order with ordinal byte order; join with inter-record LF and no terminal LF;
hash the UTF-8 bytes. This is `generator_implementation_sha256`.

The artifact separately carries:

- SHA-256 of the exact canonical inventory resource;
- SHA-256 of ADR 0061's included bytes as the governing knowledge contract;
- exact source count and total source bytes; and
- generator contract version `1`.

No branch, mutable tag, timestamp, hostname, absolute path, process ID,
randomness, cache state, or wall clock enters semantic output. The artifact
claims only its exact included source set, not the entire checkout, commit,
release, or application workspace.

---

## 14. Canonical artifact format

`FrameworkKnowledgeArtifactGenerator` returns one immutable
`FrameworkKnowledgeArtifact`. No repository artifact is checked in.

Encode one JSON object with exact keys in this order:

```text
schema
version
scope
framework
knowledge_contract
inventory
generator
source_state
sources
```

Exact fixed values:

```text
schema:     kongono.framework-knowledge
version:    1
scope:      phase4-foundation
framework:  kongono/framework
```

Nested key order is fixed:

```text
knowledge_contract: path, sha256
inventory: path, sha256
generator: package, contract_version, implementation_sha256
source_state: source_set_sha256, source_count, source_bytes
source record: reference, ordinal, path, authority, bytes, sha256, content
```

Use compact `json_encode` with only:

```text
JSON_UNESCAPED_SLASHES
JSON_UNESCAPED_UNICODE
JSON_THROW_ON_ERROR
```

Append exactly one LF. No BOM, CR, pretty printing, insignificant whitespace,
float, null, optional field, extension field, reordered key, or duplicate key
is permitted.

Source ordinals are integers `1..31`. Each whole-source citation is:

```text
kgn-source-v1:<source_set_sha256>:<four-digit-ordinal>
```

Example suffixes are `:0001` through `:0031`. A reference resolves only to one
whole source record in that exact source set. Line, symbol, fragment, and
cross-artifact citation are deliberately not implemented in Phase 4C.

The artifact SHA-256 is lowercase SHA-256 over the complete canonical artifact
bytes including the one final LF. It is returned by the value and command but
is not embedded recursively.

`FrameworkKnowledgeArtifact` exposes detached immutable values for canonical
bytes, artifact SHA-256, source-set SHA-256, generator implementation SHA-256,
source count, source bytes, sources, and exact lookup by reference. Unknown
references return `null`; no fuzzy, path, text, semantic, or ranked search is
implemented.

---

## 15. Validation contract

`FrameworkKnowledgeArtifactValidator` has two explicit operations:

```text
validateStructure(string $artifactBytes): FrameworkKnowledgeArtifact
validateCurrent(string $frameworkRoot, string $artifactBytes): FrameworkKnowledgeArtifact
```

Structural validation must reject over-limit bytes before JSON decoding, invalid
JSON, depth overflow, non-canonical bytes, incorrect final LF, unknown/missing/
reordered fields, wrong fixed values, invalid scalar kinds, counts, ranges,
authority tokens, path grammar, order, duplicates, references, content lengths,
content hashes, internally derivable source-set or contract-source mismatch,
malformed inventory/generator identity fields, and unsupported
schema/version/scope. It calculates and returns the artifact SHA-256; that hash
is not an embedded field that could validate itself.

Canonical byte comparison after strict decode/re-encode must reject duplicate
JSON keys and alternate encodings.

Current validation regenerates the exact current artifact from the explicit
framework root and requires byte equality. Any inventory, generator, contract,
source content, order, count, or semantic byte difference is stale and throws
`StaleKnowledgeArtifactException`. There is no compatible-range, partial,
mixed-version, newest, or availability fallback.

Only current validation can prove the inventory and generator identities
against external current bytes. Structural validation must not pretend those
unembedded bytes are self-authenticating.

Validation never executes content, fetches data, mutates source, publishes,
repairs, normalizes, or rewrites an artifact.

---

## 16. Publication, stale state, and cache policy

`FrameworkKnowledgeArtifactPublisher::publish()` accepts a validated/generated
artifact and an explicit existing output directory.

The content-addressed filename is:

```text
kongono-framework-knowledge-v1-<artifact_sha256>.json
```

Publication requirements:

- resolve and validate the explicit directory;
- reject links/reparse aliases and non-directories;
- create a bounded temporary regular file in that same directory;
- write all bytes with a complete-write loop;
- flush and `fsync` before visibility;
- establish the absent final name atomically with a no-clobber hard-link
  operation;
- remove the temporary name after the final name exists;
- never overwrite any path;
- if the exact target already exists, accept it only after exact byte equality
  and structural validation, otherwise fail visibly;
- on pre-publication failure, remove the temporary file and leave no final
  target; and
- report a post-publication cleanup failure explicitly without claiming the
  valid target was absent.

If the platform/filesystem cannot provide the required no-clobber hard-link
publication, fail with `KnowledgePublicationException`; do not fall back to a
non-atomic copy or overwrite.

Phase 4C provides no mutable pointer, `latest`, replacement, deletion,
garbage collection, lock service, cache, incremental build, background worker,
or automatic regeneration. Every generation is complete. A stale artifact
remains inspectable but `validateCurrent` rejects it.

SHA-256 here proves integrity and exact equality, not author identity,
authorization, signature, or trust. No signing mechanism is selected.

---

## 17. Diagnostics and exceptions

Create exactly eleven diagnostic cases:

| Case | Identifier |
|---|---|
| `InvalidInventory` | `KGN-KNOWLEDGE-1001` |
| `InvalidSourcePath` | `KGN-KNOWLEDGE-1002` |
| `SourceUnavailable` | `KGN-KNOWLEDGE-1003` |
| `UnsafeSource` | `KGN-KNOWLEDGE-1004` |
| `SourceLimitExceeded` | `KGN-KNOWLEDGE-1005` |
| `InvalidSourceEncoding` | `KGN-KNOWLEDGE-1006` |
| `InvalidArtifact` | `KGN-KNOWLEDGE-1007` |
| `StaleArtifact` | `KGN-KNOWLEDGE-1008` |
| `PublicationFailed` | `KGN-KNOWLEDGE-1009` |
| `InvalidCommand` | `KGN-KNOWLEDGE-1010` |
| `UnexpectedCommandFailure` | `KGN-KNOWLEDGE-1011` |

Exact diagnostic prose is:

| Case | Operation | Violated invariant | Title | Remediation |
|---|---|---|---|---|
| `InvalidInventory` | `Load framework knowledge source inventory` | `The inventory must be canonical, closed, ordered, unique, and within bounds` | `Invalid framework knowledge source inventory` | `Correct the canonical closed inventory before generating an artifact` |
| `InvalidSourcePath` | `Admit framework knowledge source path` | `Every source path must be relative, contained, exact, and free of aliases or stream syntax` | `Invalid framework knowledge source path` | `Use one exact allowlisted regular-file path beneath the explicit framework root` |
| `SourceUnavailable` | `Read framework knowledge source` | `Every required source must remain available and unchanged for the complete bounded read` | `Framework knowledge source is unavailable` | `Restore the exact required source state and rerun generation` |
| `UnsafeSource` | `Validate framework knowledge source type` | `Every admitted source must be an authorized ordinary regular file` | `Unsafe framework knowledge source` | `Remove the unsafe indirection or source and use the authorized ordinary file` |
| `SourceLimitExceeded` | `Enforce framework knowledge bounds` | `Inventory, path, source, implementation, and artifact work must remain within fixed limits` | `Framework knowledge limit exceeded` | `Reduce the authorized input within the fixed Phase 4C bounds` |
| `InvalidSourceEncoding` | `Validate framework knowledge source bytes` | `Every included source must be UTF-8 without BOM, CR, NUL, or invalid final-LF form` | `Invalid framework knowledge source encoding` | `Correct the authorized source encoding without changing its intended meaning` |
| `InvalidArtifact` | `Validate framework knowledge artifact` | `The artifact must match the exact canonical schema and all internally derivable identities` | `Invalid framework knowledge artifact` | `Regenerate the artifact from the exact authorized sources and generator` |
| `StaleArtifact` | `Match framework knowledge artifact to current sources` | `The artifact must equal complete regeneration from the exact current source, inventory, contract, and generator state` | `Stale framework knowledge artifact` | `Generate a new content-addressed artifact from the current exact source state` |
| `PublicationFailed` | `Publish framework knowledge artifact` | `A complete validated artifact must become visible atomically without overwrite or partial output` | `Framework knowledge artifact publication failed` | `Use an empty compatible output directory and retry explicit publication` |
| `InvalidCommand` | `Invoke framework knowledge command` | `The command must match one exact non-interactive operation and argument contract` | `Invalid framework knowledge command` | `Use the documented check, generate, or validate command form` |
| `UnexpectedCommandFailure` | `Run framework knowledge command` | `Unexpected native failure must remain bounded and must not disclose rejected values` | `Unexpected framework knowledge command failure` | `Inspect the diagnostic identifier and deterministic evidence before retrying` |

Each case has one required Safe text context field named `violation`. Exact
closed tokens are:

```text
InvalidInventory:
invalid_json, non_canonical_json, unsupported_schema, invalid_shape,
invalid_source_entry, duplicate_source_path, unordered_source_path,
unsupported_authority, inventory_limit

InvalidSourcePath:
invalid_path_grammar, path_escape, path_alias, link_or_reparse,
alternate_stream

SourceUnavailable:
invalid_root, source_missing, source_unreadable, source_read_failed,
source_changed_during_read

UnsafeSource:
non_regular_source, forbidden_source, forbidden_authority

SourceLimitExceeded:
inventory_bytes, source_count, path_bytes, source_bytes, total_source_bytes,
artifact_bytes, json_depth, implementation_count, implementation_bytes

InvalidSourceEncoding:
invalid_utf8, utf8_bom, cr_byte, nul_byte, missing_final_lf,
multiple_final_lf

InvalidArtifact:
invalid_json, non_canonical_json, unsupported_schema, invalid_shape,
invalid_scalar, invalid_order, duplicate_source, invalid_reference,
content_length, content_digest, source_set_digest, contract_source_digest,
malformed_inventory_digest, malformed_generator_digest

StaleArtifact:
source_state_mismatch, inventory_mismatch, generator_mismatch,
contract_mismatch

PublicationFailed:
output_directory, temporary_create, temporary_write, temporary_flush,
temporary_sync, target_conflict, atomic_link, temporary_cleanup,
existing_target_invalid

InvalidCommand:
argument_count, unknown_operation, invalid_root, invalid_artifact_path,
invalid_output_directory

UnexpectedCommandFailure:
unexpected_failure
```

Do not place raw source content, rejected JSON, absolute paths, secrets,
credentials, environment values, stack traces, or arbitrary exception messages
in a diagnostic message or Safe context. Source paths are already embedded only
after allowlist admission; command failures still report only the closed
violation token.

`KnowledgeException` extends `RuntimeException`, implements the package marker,
owns `KnowledgeDiagnostic` plus detached `DiagnosticContext`, and has a final
constructor:

```text
__construct(KnowledgeDiagnostic $diagnostic, string $violation, ?Throwable $previous = null)
```

Its message is the fixed diagnostic title. It exposes final diagnostic methods.
The four concrete exception classes are final and inherit this exact
constructor. Native programmer errors outside deliberate package failures are
not promised to implement the marker.

Exception stringification must not disclose raw rejected values. Preserve
native causes internally without rendering their messages in command output.

---

## 18. Command contract

Create Composer bin entry `packages/knowledge/bin/kongono-knowledge` and
Internal `KnowledgeCommand`.

Exact commands:

```text
kongono-knowledge check <framework-root>
kongono-knowledge generate <framework-root> <existing-output-directory>
kongono-knowledge validate <framework-root> <artifact-path>
```

No option aliases, optional arguments, environment configuration, interactive
prompt, config search, current-directory default, network access, model call,
workspace scan, source mutation, artifact repair, or delete operation exists.

Success writes one compact JSON object plus LF to stdout and nothing to stderr.
Key order:

```text
status, operation, artifact_sha256, source_set_sha256, source_count
```

`generate` adds final key `artifact_file` containing only the content-addressed
basename, never an absolute path. `status` is `ok`.

The success `operation` value is exactly `check`, `generate`, or `validate`.

Failure writes one compact JSON object plus LF to stderr and nothing to stdout.
Key order:

```text
status, operation, diagnostic_id, title, remediation, violation
```

No stack trace or native message is emitted. Exit codes:

```text
0   success
64  invalid command usage
65  invalid or stale artifact/inventory data
66  unavailable or unsafe source input
70  unexpected internal command failure
73  publication failure
```

On a recognized operation, the failure `operation` is that exact operation.
For missing/unknown operation or failure before safe operation admission, it is
the fixed value `command`; never echo rejected command text.

Before reading a validation artifact, require an explicit local ordinary-file
path with no link/reparse alias or stream syntax and reject a file larger than
the artifact bound using the applicable closed diagnostic token.

The root Composer script is exactly:

```text
knowledge:check = @php packages/knowledge/bin/kongono-knowledge check .
```

Insert `@knowledge:check` in `quality` after `@platform` and before `@cs`.
The check command generates and validates in memory and performs no write.

---

## 19. Composer, autoload, and tooling expansion

Modify root Composer metadata to:

- require `kongono/knowledge: @dev` in sorted order;
- add `Kongono\Knowledge\Tests\` => `packages/knowledge/tests/`;
- validate the knowledge package in `validate:packages` in sorted order;
- add the exact `knowledge:check` script; and
- add it to `quality` in the exact position above.

Modify PHPUnit to add package tests and source in package order. Modify PHPStan
to cover source, tests, and the bin file. Modify PHP CS Fixer to cover source,
tests, and explicitly append the extensionless bin path.

After creating the package and editing root `composer.json`, run exactly:

```text
composer update kongono/knowledge --with-dependencies --no-interaction
```

Composer must generate `composer.lock`. Do not edit it manually.

Authorized lock changes are limited to:

- root content hash;
- one new `kongono/knowledge` path package with its exact manifest data;
- root package dependency metadata; and
- mechanical `dist.reference` regeneration for existing local `kongono/*`
  path packages caused by this exact Composer operation.

No third-party package version, source, dist URL, requirement, advisory policy,
plugin policy, platform requirement, stability, dev package, or unrelated lock
metadata may change. If Composer proposes such a change, stop.

After the update require:

```text
packages:      19
packages-dev:  66
```

Run strict optimized autoload proof:

```text
composer dump-autoload --optimize --strict-psr --strict-ambiguous
```

No dependency, plugin, polyfill, Symfony component, serializer, CLI framework,
or model SDK is added.

---

## 20. Exact file boundary

### 20.1 Create exactly twenty-nine content paths

```text
docs/adr/0062-deterministic-framework-knowledge-artifact.md
docs/architecture/framework-knowledge-artifact.md
packages/knowledge/bin/kongono-knowledge
packages/knowledge/composer.json
packages/knowledge/resources/framework-source-inventory.json
packages/knowledge/src/Artifact/FrameworkKnowledgeArtifact.php
packages/knowledge/src/Artifact/FrameworkKnowledgeArtifactGenerator.php
packages/knowledge/src/Artifact/FrameworkKnowledgeArtifactPublisher.php
packages/knowledge/src/Artifact/FrameworkKnowledgeArtifactValidator.php
packages/knowledge/src/Command/KnowledgeCommand.php
packages/knowledge/src/Diagnostic/KnowledgeDiagnostic.php
packages/knowledge/src/Exception/ExceptionInterface.php
packages/knowledge/src/Exception/InvalidKnowledgeArtifactException.php
packages/knowledge/src/Exception/InvalidKnowledgeSourceException.php
packages/knowledge/src/Exception/KnowledgeException.php
packages/knowledge/src/Exception/KnowledgePublicationException.php
packages/knowledge/src/Exception/StaleKnowledgeArtifactException.php
packages/knowledge/src/Internal/CanonicalJson.php
packages/knowledge/src/Internal/PathGuard.php
packages/knowledge/src/Source/KnowledgeSource.php
packages/knowledge/src/Source/KnowledgeSourceAuthority.php
packages/knowledge/src/Source/KnowledgeSourceDefinition.php
packages/knowledge/src/Source/KnowledgeSourceInventory.php
packages/knowledge/tests/Unit/Artifact/FrameworkKnowledgeArtifactGeneratorTest.php
packages/knowledge/tests/Unit/Artifact/FrameworkKnowledgeArtifactPublisherTest.php
packages/knowledge/tests/Unit/Artifact/FrameworkKnowledgeArtifactValidatorTest.php
packages/knowledge/tests/Unit/Command/KnowledgeCommandTest.php
packages/knowledge/tests/Unit/Diagnostic/KnowledgeDiagnosticTest.php
packages/knowledge/tests/Unit/Source/KnowledgeSourceInventoryTest.php
```

### 20.2 Modify exactly twenty-six existing paths

```text
.php-cs-fixer.dist.php
AGENTS.md
ARCHITECTURE.md
CONTRIBUTING.md
README.md
SECURITY.md
composer.json
composer.lock
phpstan.neon.dist
phpunit.xml.dist
docs/adr/README.md
docs/architecture/application-graph.md
docs/architecture/developer-intelligence.md
docs/architecture/knowledge-foundation.md
docs/architecture/overview.md
docs/architecture/phase-4-roadmap.md
docs/doctrine.md
docs/documentation-standard.md
docs/engineering/api-status.md
docs/engineering/dependency-security.md
docs/engineering/error-handling.md
docs/engineering/package-layout.md
docs/engineering/testing.md
docs/engineering/toolchain.md
docs/glossary.md
tests/Architecture/ApiStatusConventionTest.php
```

### 20.3 Accepted starting identities

| Path | Bytes | SHA-256 |
|---|---:|---|
| `.php-cs-fixer.dist.php` | 2197 | `7BFE4A055AE92E0E85E0EE2EA51005231723A562CB2258F667072B115EF44267` |
| `AGENTS.md` | 9473 | `2C16B753959C9DDE705E610D09E1D53AB3608892D7DC24A0538AD75CCA227100` |
| `ARCHITECTURE.md` | 27551 | `941C3844C86CD860E2881EB39A15ABC53962394744F458BC38E6BA48B20AE457` |
| `CONTRIBUTING.md` | 5445 | `E240CE4979F7E49C35704CBB308E0B21D9B73AC963D5CA0CE2EF1479DAB7F1EB` |
| `README.md` | 20016 | `AB97E1EDAC9CB116E5490C57E808063754FA34AAD0A0216B3162FB3EF1FC3980` |
| `SECURITY.md` | 30915 | `1DCCE4E8769C1B8A578E9EF14B143361DA4212B08316C9FC24AF2196D0DCD00F` |
| `composer.json` | 4531 | `AADEE5ACBFBD41B32817D014A7ABEEBB48681A386CA60F53D244DA05A9A829C4` |
| `composer.lock` | 197527 | `823D849823E7403BD5F45C3DD85BAC17974D2FC66D004D454D9D06FE5AA1EC84` |
| `phpstan.neon.dist` | 1336 | `60052B4AE7C7A221075AD0040B4D19097A1CE2503571DEE03850B1E7908155B3` |
| `phpunit.xml.dist` | 2560 | `C5F5AC8943E1AAA3090233BAE096DFFB914541844948A38657A2DDF954992472` |
| `docs/adr/README.md` | 15018 | `1BF2CB8FB9534AA5F3D93B92C8E3A705DBB9BD58864A953BA341285F11224ADC` |
| `docs/architecture/application-graph.md` | 9953 | `6615AD61614CD855EC83AFF8189941F3FF16403E93913004C1EF2D244B5C4FBE` |
| `docs/architecture/developer-intelligence.md` | 10681 | `CAFD03A9DE4D8AF8582ED367A7B3D226A17918C96B97527E24143C29FCFE835A` |
| `docs/architecture/knowledge-foundation.md` | 14320 | `ADFFDC199E69BA082F7C959C2D0A121FB200BDD1D2F15AD487F07ECD6EEE9AD3` |
| `docs/architecture/overview.md` | 22168 | `E6C542E321C8E42BE751A4D82C077F38D5165381B5F21187C731D874604E0ED8` |
| `docs/architecture/phase-4-roadmap.md` | 14055 | `16E8EB28C833836DF35A4CDB20AD2D516CF123BA529EA9DCE88EB2A562CEB7DC` |
| `docs/doctrine.md` | 24757 | `667704A3A889CCF95F85B4EDF84D24F25FDBAA5282160442F7422024D9B9BB74` |
| `docs/documentation-standard.md` | 8862 | `94B98B8B64D59FFC44760CFAE0E6F3AF6A28615FD19C07A767912A7FA35306FB` |
| `docs/engineering/api-status.md` | 7091 | `5CC847E180C2BEB763CE8CBF225590FD5B01465BBD61B76E4667545D23B04492` |
| `docs/engineering/dependency-security.md` | 2393 | `85C8A6A6B8470C4FB23AEEEFAB6007C07A0327849C6EE1A5E374AE3BB6F42FFF` |
| `docs/engineering/error-handling.md` | 84273 | `0B74847AA048FA5CF6F533213997131B767612F9E146C8242C19836A80D0CCD8` |
| `docs/engineering/package-layout.md` | 22974 | `CC1535E5CE27B42F7B4229885F4C0AFA259CDC0DEAC43C30A86511D6F90F3AB3` |
| `docs/engineering/testing.md` | 6962 | `33937DF28C3B4D830C9972641B78651F40A4D5A70459AAB13DC42ADBC79A748B` |
| `docs/engineering/toolchain.md` | 8776 | `DABDDD482CB8AD2B6B38BA1D5E97E4F6EB33E5B8C70332CA2CBF9A0A8EAD747D` |
| `docs/glossary.md` | 44140 | `BBFE6120210AC96A1DED52949EB18946CCED65A857113ECF79F1F044D62057FD` |
| `tests/Architecture/ApiStatusConventionTest.php` | 91277 | `06DD386843B2B4DCDB0F9404DBC32B0F9B19E4CA6A0B82602D3A200742D74828` |

### 20.4 Closed outside scope

Do not create, modify, delete, rename, move, normalize, restore, or regenerate
any other repository content path. Temporary test/publication directories must
be outside the repository and fully removed after proof.

---

## 21. Test contract

Each of the six new test files declares exactly six `test*` methods, uses no
data provider, and contributes exactly thirty-six new PHPUnit tests. The final
canonical suite must contain exactly 597 tests. Report the observed assertion
count; do not invent or lock it in advance.

Required coverage includes:

### Inventory

- exact canonical 31-entry resource and order;
- exact authority mapping and rejection of unsupported authority classes;
- malformed, non-canonical, duplicate, unordered, missing, and over-limit
  inventory failure;
- detached definitions;
- path grammar and source-count bounds; and
- no ambient discovery or optional entry behavior.

### Generation

- same exact inputs produce byte-identical artifact and digest;
- one changed source changes source-set and artifact identity;
- exact schema/key order/final LF/reference grammar;
- exact content bytes, counts, hashes, inventory, contract, and generator
  identity;
- source limit, encoding, path escape, alias/link, missing, and changed-during-
  read failure; and
- no time, random, host path, environment, network, model, cache, or Git data in
  semantic output.

### Validation

- valid structural and current validation;
- malformed, over-limit, non-canonical, duplicate-key, extra/missing/reordered
  field rejection;
- structural rejection of every internally derivable content/count/hash/
  reference/source-set/contract-source tamper;
- current-validation rejection of inventory, generator, contract, and current-
  source tamper;
- stale current-source rejection;
- mixed-version and partial artifact rejection; and
- no repair, rewrite, source mutation, or stale fallback.

### Publication

- exact content-addressed name and byte equality;
- atomic no-clobber initial publication;
- identical existing target idempotence only after full validation;
- conflicting existing target rejection;
- missing/unsafe output and reachable write/link failures leave explicit
  evidence and no partial final artifact before publication;
- the diagnostic catalog independently proves the closed write, flush, sync,
  link, and cleanup violation tokens without adding fault-injection machinery;
- no overwrite, mutable pointer, cache, or repository write.

### Diagnostics

- exact eleven cases, IDs, titles, remediation, schemas, and closed tokens;
- global diagnostic uniqueness and `KGN-KNOWLEDGE-1NNN` grammar;
- marker/base/concrete reflection shapes and cause identity;
- same-object detached contexts;
- messages/context/stringification exclude raw rejected values; and
- failure-priority behavior.

### Command

- exact three command shapes and exit codes;
- exact stdout/stderr JSON key order and final LF;
- real repository `check` succeeds with 31 sources and no write;
- generate then validate succeeds in an external temporary directory;
- usage/data/source/publication/unexpected failures are structured and bounded;
  and
- no stack trace, native message, absolute path, source content, environment,
  network, model, prompt, interaction, or source mutation.

Tests must clean all temporary files in `finally`. Do not skip based on host
capabilities. Where a hard-link capability is deliberately unavailable, prove
the required fail-closed publication result rather than skipping.

Update the root architecture test for exactly fifteen packages, 144 production
types/files, 143 globally unique diagnostic IDs with eleven Knowledge IDs, the
new marker separation, exact API statuses, package dependency direction,
root/package manifests, source inventory, and absence of forbidden package
edges or role/runtime behavior.

Do not weaken existing tests, counts, assertions, failure settings, static
analysis, style, security policy, or architecture checks.

---

## 22. Documentation reconciliation

Create `docs/architecture/framework-knowledge-artifact.md` as the current
implementation guide covering purpose, scope, exact 31-source inventory,
authority classes, schema, identities, canonical JSON, citations, generation,
validation, publication, diagnostics, commands, limits, security, lifecycle,
performance bounds, API status, examples, and deliberate exclusions.

Reconcile all authorized documents so they state:

- Phase 4B architecture is Accepted;
- Phase 4C implements one bounded `phase4-foundation` artifact capability;
- no generated artifact is checked in or authoritative;
- current generation is complete/in-memory and current publication is optional
  content-addressed initial publication only;
- current citations resolve whole source records only;
- exact source inventory is 31 and excludes workspace, historical caller
  records, tests, arbitrary application code, vendor, caches, and external data;
- package count is fifteen and dependency direction remains acyclic;
- diagnostics total and testing/tooling counts are current;
- Phase 4D remains planned and Chie is not implemented;
- Hantei, Oshie, retrieval, search, models, workspace reads, mutation, and
  production integration remain deferred/prohibited; and
- ordinary Kongono use remains independent of artifacts, AI, accounts, and
  networks.

Do not relabel the Phase 3 checkpoint, full framework, Stable API, 1.0,
production readiness, rolling PHP migration, or any later capability as
complete.

---

## 23. Required validation sequence

### Gate A — authentication and baseline

Authenticate the directive/report, verify Sections 4–6 and all target
identities, read authorities, prove created paths absent, and prove all visible
files ordinary/non-reparse/default-stream-only with intended `100644` modes.

### Gate B — pre-change quality

Run literal:

```text
composer quality
```

Require:

```text
security advisories     0
platform                pass on PHP 8.5.9
style                   0 of 277 files fixable
PHPStan                 zero errors
PHPUnit                 561 tests, 13004 assertions
```

If only managed Composer network/cache access fails, prove repository and
caller preservation and retry the identical command once with approved access.
No other retry, substitution, or skipped stage is authorized.

### Gate C — package and Composer expansion

Create package files, edit root/tool configuration, run the exact Composer
update, and stop if the lock delta exceeds Section 19.

Run syntax checks on all twenty-five new PHP/bin/test files, package Composer
validation, strict optimized autoload proof, focused PHPStan, focused style,
and focused package PHPUnit. Correct only defects within authorized paths.

### Gate D — deterministic external proof

Run:

```text
composer knowledge:check
```

Then use two fresh external empty directories to generate independently. Prove
identical content-addressed basenames, bytes, artifact SHA-256, source-set
SHA-256, generator identity, source count, and source bytes. Validate both
artifacts against the current root.

Use an external copied source set to prove one admitted byte change causes a
stale failure without mutating the repository. Prove malformed/tampered input,
existing-conflicting target, path escape, and unsupported publication fail
with exact diagnostic/exit contracts and no raw-value disclosure.

Remove every temporary path and prove removal.

### Gate E — documentation and file quality

Validate all local links and exact current/planned/deferred wording. For every
created or modified text file require UTF-8 without BOM, LF only, exactly one
final LF, no NUL, no trailing whitespace, ordinary file, non-reparse,
default-stream-only, and intended `100644` mode.

Run:

```text
git -c safe.directory='C:/Users/antho/Dev/kongono' diff --check
```

### Gate F — final canonical quality

Run literal:

```text
composer quality
```

Require:

```text
root plus 15 package validation       pass with only accepted warnings
locked audit                          0 advisories
platform                              pass
knowledge:check                       success, 31 sources, no write
PHP CS Fixer                          0 of 302 files fixable
PHPStan                               zero errors
PHPUnit                               exactly 597 tests
```

Report the observed assertion count. The same single managed network/cache
retry rule applies only to this invocation.

### Gate G — final preservation and report

Before report population prove:

- exactly twenty-nine created and twenty-six modified content paths;
- the 621-record protected manifest reproduces exactly;
- all 228 historical records reproduce exactly;
- no path outside the caller/report/authorized content boundary changed;
- package/source/test/style/ADR/diagnostic/lock/source-inventory counts match;
- no generated artifact, temporary file, or cache remains Git-visible;
- no source, test, artifact, or command used a network/model/workspace;
- semantic index equals `HEAD` and nothing is staged;
- no object, commit, ref, history, branch, tag, remote, hook, signing, ignore,
  attribute, or Git-configuration mutation occurred;
- final state in Section 25 is exact; and
- the active report remains zero-byte.

Only then populate the report.

---

## 24. Prohibited scope

Do not implement or introduce:

- Chie, Hantei, Oshie, or any other assistant/agent runtime;
- a model, provider, prompt, embedding, vector store, retrieval, ranking,
  semantic search, RAG, external source, browser, network client, or SDK;
- application-workspace reading, indexing, retention, learning, mutation, or
  cross-project state;
- generated claims, inference, recommendations, lessons, exercises, patches,
  auto-fix, Git actions, dependency edits by an assistant, deployment, or
  production access;
- Application Graph export/input or Graph schema change;
- database, ORM, persistence service, queue, cache, index, daemon, scheduler,
  telemetry, CLI framework, frontend, template engine, or HTTP integration;
- a checked-in generated knowledge artifact;
- a mutable latest pointer, overwrite, replacement, garbage collection,
  artifact deletion, incremental generation, or silent stale fallback;
- cryptographic signing/authenticity claims;
- source globbing, ambient discovery, vendor/tests/directives/reports/workspace
  inclusion, or a general secret/malware scanner;
- public Stable API, 1.0 claim, PHP constraint migration, or new dependency;
- suppression, ignore, baseline, weakened gate, skipped test, or policy
  exception; or
- staging, commit, tag, branch, remote, or history mutation.

Phase 4D must not begin.

---

## 25. Exact expected final state

Before and after report population require:

```text
tracked modifications   26
untracked paths          49
total status paths       75
staged paths              0
Git-visible paths       678
tracked files           629
package directories      15
package manifests        15
production PHP files    144
test PHP files          157
style-covered PHP       302
production types        144
global diagnostic IDs   143
Knowledge diagnostic IDs 11
ADR files                62
phase_4 dev-plan files    6
phase_4 task reports      6
knowledge inventory      31 sources
knowledge implementation 19 files
lock packages            19
lock packages-dev        66
```

The exact forty-nine untracked paths are:

```text
docs/adr/0059-rolling-current-minor-php-baseline.md
docs/adr/0060-human-centered-developer-intelligence-family.md
docs/adr/0061-versioned-knowledge-source-and-provenance-contract.md
docs/adr/0062-deterministic-framework-knowledge-artifact.md
docs/architecture/developer-intelligence.md
docs/architecture/framework-knowledge-artifact.md
docs/architecture/knowledge-foundation.md
docs/architecture/phase-4-roadmap.md
docs/dev_plan/phase_4/kongono_phase_4A_1_codex_directive_correction.md
docs/dev_plan/phase_4/kongono_phase_4A_codex_directive.md
docs/dev_plan/phase_4/kongono_phase_4B_1_codex_directive_correction.md
docs/dev_plan/phase_4/kongono_phase_4B_2_codex_directive_correction.md
docs/dev_plan/phase_4/kongono_phase_4B_codex_directive.md
docs/dev_plan/phase_4/kongono_phase_4C_codex_directive.md
docs/engineering/php-support-policy.md
docs/task_reports/phase_3/Phase_3H_1_correction.md
docs/task_reports/phase_4/Phase_4A.md
docs/task_reports/phase_4/Phase_4A_1_correction.md
docs/task_reports/phase_4/Phase_4B.md
docs/task_reports/phase_4/Phase_4B_1_correction.md
docs/task_reports/phase_4/Phase_4B_2_correction.md
docs/task_reports/phase_4/Phase_4C.md
packages/knowledge/bin/kongono-knowledge
packages/knowledge/composer.json
packages/knowledge/resources/framework-source-inventory.json
packages/knowledge/src/Artifact/FrameworkKnowledgeArtifact.php
packages/knowledge/src/Artifact/FrameworkKnowledgeArtifactGenerator.php
packages/knowledge/src/Artifact/FrameworkKnowledgeArtifactPublisher.php
packages/knowledge/src/Artifact/FrameworkKnowledgeArtifactValidator.php
packages/knowledge/src/Command/KnowledgeCommand.php
packages/knowledge/src/Diagnostic/KnowledgeDiagnostic.php
packages/knowledge/src/Exception/ExceptionInterface.php
packages/knowledge/src/Exception/InvalidKnowledgeArtifactException.php
packages/knowledge/src/Exception/InvalidKnowledgeSourceException.php
packages/knowledge/src/Exception/KnowledgeException.php
packages/knowledge/src/Exception/KnowledgePublicationException.php
packages/knowledge/src/Exception/StaleKnowledgeArtifactException.php
packages/knowledge/src/Internal/CanonicalJson.php
packages/knowledge/src/Internal/PathGuard.php
packages/knowledge/src/Source/KnowledgeSource.php
packages/knowledge/src/Source/KnowledgeSourceAuthority.php
packages/knowledge/src/Source/KnowledgeSourceDefinition.php
packages/knowledge/src/Source/KnowledgeSourceInventory.php
packages/knowledge/tests/Unit/Artifact/FrameworkKnowledgeArtifactGeneratorTest.php
packages/knowledge/tests/Unit/Artifact/FrameworkKnowledgeArtifactPublisherTest.php
packages/knowledge/tests/Unit/Artifact/FrameworkKnowledgeArtifactValidatorTest.php
packages/knowledge/tests/Unit/Command/KnowledgeCommandTest.php
packages/knowledge/tests/Unit/Diagnostic/KnowledgeDiagnosticTest.php
packages/knowledge/tests/Unit/Source/KnowledgeSourceInventoryTest.php
```

No staging or commit is authorized.

---

## 26. Mandatory stop rules

Stop immediately on:

- directive, caller, baseline, manifest, identity, inventory, count, stream,
  mode, or ordinary-file mismatch;
- any pre-change or final quality failure except the one exact managed
  network/cache retry rule;
- a Composer lock delta outside Section 19;
- a need for another file, type, dependency, schema field, source, authority
  class, command, exit code, diagnostic, public API, or architecture choice;
- inability to provide atomic no-clobber publication without fallback;
- a source/admission/disclosure ambiguity;
- a test that exposes a production defect outside the authorized boundary;
- any raw rejected content or path disclosure;
- a nondeterministic artifact byte;
- any generated artifact or temporary path left in the repository;
- any protected/historical preservation mismatch; or
- any staging, object, commit, ref, history, branch, tag, remote, hook, signing,
  ignore, attribute, or Git-configuration mutation.

Use:

```text
STOP
REPORT EXPECTED AND OBSERVED VALUES
PRESERVE WORKTREE, INDEX, HISTORY, AND CALLER RECORDS
DO NOT GUESS OR EXPAND SCOPE
```

Within the fifty-five content paths, ordinary implementation, test,
documentation, link, formatting, static-analysis, and consistency defects may
be corrected and the relevant focused gates rerun. This permission does not
authorize a new decision or wider boundary.

A non-mutating checker/parser/quoting defect may be simplified and rerun only
after proving repository state unchanged. Report it honestly.

---

## 27. Task completion report

After every non-report gate passes, populate only:

```text
docs/task_reports/phase_4/Phase_4C.md
```

Use exactly:

```text
# PHASE 4C TASK COMPLETION REPORT
## 1. STATUS
## 2. SUMMARY
## 3. BASELINE VERIFIED
## 4. CALLER RECORDS
## 5. FILES CREATED
## 6. FILES MODIFIED
## 7. PACKAGE AND DEPENDENCY BOUNDARY
## 8. SOURCE INVENTORY AND AUTHORITY
## 9. ARTIFACT SCHEMA AND IDENTITIES
## 10. GENERATION AND VALIDATION
## 11. PUBLICATION AND STALE STATE
## 12. DIAGNOSTICS AND COMMAND CONTRACT
## 13. ARCHITECTURAL DECISIONS
## 14. DEVIATIONS FROM DIRECTIVE
## 15. DEPENDENCIES ADDED/REMOVED
## 16. ENVIRONMENT
## 17. COMMANDS RUN
## 18. VALIDATION RESULTS
## 19. DOCUMENTATION REVIEW
## 20. SECURITY AND FILE-SAFETY REVIEW
## 21. WARNINGS / KNOWN ISSUES
## 22. QUESTIONS / BLOCKERS
## 23. FINAL GIT STATUS
## 24. ACCEPTANCE CRITERIA
## 25. NEXT STEP
```

Include exact directive/caller/manifest evidence; file inventories; API and
diagnostic counts; source inventory identity; schema and format; generated
artifact/source-set/generator identities from external proof; command outputs
and exit codes; lock delta; focused and canonical results; temporary cleanup;
protected/historical preservation; exact final state; every Section 28
criterion marked PASS or FAIL; and final report byte count/SHA-256 returned
outside its body.

Its exact final sentence is:

```text
Await architect review before Phase 4D.
```

---

## 28. Acceptance criteria

Phase 4C is Complete only when all are true:

1. directive identity matched independently supplied values;
2. active report began zero-byte and remained so through non-report gates;
3. Phase 4 caller records and exact entry state matched;
4. complete, protected, and historical manifests matched with typed ordinal
   no-terminal-LF serialization;
5. all required authorities and 26 starting identities matched;
6. pre-change literal quality passed at 561 tests/13004 assertions;
7. exactly 29 content paths were created and 26 modified;
8. no other content changed except final report population;
9. ADR 0062 is Accepted, correctly dated, and complete;
10. `kongono/knowledge` has the exact acyclic dependency boundary;
11. exactly eighteen production types have exact API statuses and shapes;
12. exact eleven diagnostics and package exception separation pass;
13. canonical inventory has exactly 31 ordered authorized sources;
14. all path, byte, encoding, containment, alias, stream-syntax, and changed-
    during-read checks pass;
15. source-set, inventory, contract, generator, citation, and artifact identity
    algorithms match exactly;
16. canonical JSON schema/key order/encoding/final-LF contract passes;
17. structural and current validation reject every required invalid/stale case;
18. complete regeneration is deterministic and performs no network/model/cache;
19. content-addressed initial publication is atomic and no-clobber;
20. stale state is visible and no overwrite/replacement/latest fallback exists;
21. command grammar, JSON output, diagnostics, disclosure, and exit codes match;
22. root Composer/tooling integration and exact lock delta pass;
23. strict optimized autoload passes;
24. six test files contribute exactly 36 tests and all required cases pass;
25. external double-generation bytes and identities are exact;
26. external stale/tamper/path/publication failures are fail-closed and bounded;
27. every temporary path is removed and no generated artifact is Git-visible;
28. documentation accurately separates current, accepted, planned, deferred,
    and prohibited behavior;
29. all local-link, UTF-8, LF, final-LF, whitespace, NUL, ordinary-file,
    reparse, stream, mode, and diff checks pass;
30. final literal quality passes with 15 packages, `knowledge:check`, 0/302
    style findings, zero PHPStan errors, and exactly 597 tests;
31. observed final assertion count is reported without weakening tests;
32. the 621 protected paths and 228 historical records remain exact;
33. final inventory is exactly `26/49/75/0`, 678 visible, 629 tracked;
34. package/source/test/style/type/diagnostic/ADR/lock counts match Section 25;
35. no dependency, plugin, policy exception, suppression, baseline, or ignored
    failure was introduced;
36. no model, retrieval, workspace, Graph input, role, patch, production, or
    later-phase behavior exists;
37. no stage, object, commit, ref, history, branch, tag, remote, hook, signing,
    ignore, attribute, or Git-configuration mutation occurred; and
38. the populated report satisfies its exact structure, evidence, ending, file
    quality, and independently returned identity.

Any failed criterion means status cannot be Complete.

---

## 29. Required final outcome

Kongono owns one Experimental, deterministic, inspectable, AI-independent
framework-knowledge artifact capability. It generates one exact bounded Phase
4 foundation source set into canonical JSON, validates structure and current
source equality, exposes whole-source version-matched citations, reports
actionable bounded diagnostics, and may atomically publish a content-addressed
artifact without overwrite.

The artifact remains derived evidence rather than authority. It is not checked
into the repository, does not represent the whole framework or a workspace,
and is never required for ordinary framework or production behavior.

No Chie, Hantei, Oshie, model, provider, retrieval, search, embedding,
workspace access, patch, auto-fix, Graph integration, network generation, or
production authority is implemented.

Then await architect review before Phase 4D.
