# Framework-Knowledge Artifact

## Purpose and status

Phase 4C implements one Experimental, deterministic, AI-independent artifact
for the exact `phase4-foundation` source state. It lets tools inspect and cite
version-matched framework material without making generated output authoritative.
The implementation belongs to the acyclic `kongono/knowledge` package.

Use it when a caller needs exact, whole-source framework evidence from the
closed Phase 4 foundation. Do not use it as the whole repository, an application
workspace index, a search system, a release signature, a cache, or runtime
authorization.

Phase 4D now consumes this artifact through the separate `kongono/chie`
package. Chie requires structural and exact current validation before returning
six configured cited sections or bounded diagnostic-identifier occurrences; it
does not change Knowledge generation or publication authority.

## Exact inventory and authority

The canonical resource contains exactly 31 raw-byte-ordered paths:

```text
.php-cs-fixer.dist.php
AGENTS.md
ARCHITECTURE.md
CONTRIBUTING.md
README.md
SECURITY.md
composer.json
composer.lock
docs/adr/0059-rolling-current-minor-php-baseline.md
docs/adr/0060-human-centered-developer-intelligence-family.md
docs/adr/0061-versioned-knowledge-source-and-provenance-contract.md
docs/adr/0062-deterministic-framework-knowledge-artifact.md
docs/architecture/application-graph.md
docs/architecture/developer-intelligence.md
docs/architecture/framework-knowledge-artifact.md
docs/architecture/knowledge-foundation.md
docs/architecture/overview.md
docs/architecture/phase-4-roadmap.md
docs/doctrine.md
docs/documentation-standard.md
docs/engineering/api-status.md
docs/engineering/dependency-security.md
docs/engineering/error-handling.md
docs/engineering/package-layout.md
docs/engineering/php-support-policy.md
docs/engineering/testing.md
docs/engineering/toolchain.md
docs/glossary.md
packages/knowledge/composer.json
phpstan.neon.dist
phpunit.xml.dist
```

Each entry is labeled governing authority, implementation evidence, or current
explanatory documentation. The enum also names historical evidence, workspace
observation, and derived or advisory material so unsupported tokens cannot be
silently reinterpreted; those three classes are rejected in this inventory.
Directives, reports, tests, arbitrary source, vendor, caches, VCS internals,
home files, credentials, workspace data, and external data are excluded.

## Schema and identities

The top-level canonical key order is `schema`, `version`, `scope`, `framework`,
`knowledge_contract`, `inventory`, `generator`, `source_state`, `sources`.
The schema is `kongono.framework-knowledge`, version is `1`, scope is
`phase4-foundation`, and framework is `kongono/framework`.

The source-set identity hashes raw-byte-ordered records containing path,
authority, byte count, and lowercase source SHA-256. The inventory identity
hashes the exact canonical inventory resource. The knowledge-contract identity
hashes the included ADR 0061 bytes. The generator implementation identity
hashes exact byte records for all 18 production PHP files and the bin entry.
The artifact identity hashes the complete canonical artifact including its one
final LF; it is returned rather than recursively embedded.

Every source record has exact keys `reference`, `ordinal`, `path`, `authority`,
`bytes`, `sha256`, and `content`. References use
`kgn-source-v1:<source-set-sha256>:<four-digit-ordinal>` and resolve only to the
whole source in that exact source set. Line, symbol, fragment, fuzzy, ranked,
semantic, and cross-artifact lookup do not exist.

## Generation and validation

`FrameworkKnowledgeArtifactGenerator::generate()` receives the framework root
explicitly, reads the closed inventory completely, and returns an immutable
artifact. It performs no ambient discovery, network access, model call, cache,
Git query, source mutation, or write.

Paths are bounded ASCII relative paths. Candidate and resolved paths must be
identical and contained; every segment must be ordinary and free of aliases,
links, reparse points, device or alternate-stream syntax. Reads compare metadata
before and after, enforce byte limits, and require valid UTF-8 without BOM, CR,
NUL, or invalid final-LF form. Source text remains inert.

Structural validation rejects size and depth excess, invalid or noncanonical
JSON, wrong key order or scalar kinds, duplicate or unordered sources, bad
references, unsupported authorities, and inconsistent counts or digests.
Current validation regenerates from the explicit root and requires byte equality.
There is no compatibility range, partial acceptance, repair, rewrite, newest
selection, or stale fallback.

## Publication and lifecycle

Publication is optional and requires an explicit existing ordinary directory.
The basename is
`kongono-framework-knowledge-v1-<artifact-sha256>.json`. Bytes are completely
written, flushed, and synchronized in a same-directory temporary file before an
atomic no-clobber hard link establishes the final name. Identical existing
content is idempotent only after validation. Conflicting content fails. A host
without the required primitive fails closed; it never copies or overwrites.

Published artifacts are immutable snapshots. Stale artifacts remain inspectable
but fail current validation. The package provides no pointer, replacement,
deletion, garbage collection, scheduler, daemon, or persistence service.

## Commands

```text
kongono-knowledge check <framework-root>
kongono-knowledge generate <framework-root> <existing-output-directory>
kongono-knowledge validate <framework-root> <artifact-path>
```

Success and failure are one compact JSON object plus LF on the appropriate
stream. Success includes artifact and source-set SHA-256 and source count;
generation adds only the content-addressed basename. Failure includes the fixed
diagnostic identifier, title, remediation, and one closed violation token.
Exit codes are 0 success, 64 usage, 65 invalid or stale data, 66 unavailable or
unsafe source, 70 unexpected internal failure, and 73 publication failure.
`composer knowledge:check` performs in-memory generation and current validation
with no write.

## Bounds, security, and performance

Inventory is limited to 65,536 bytes and 64 records; this slice requires 31.
Paths are limited to 240 bytes, each source to 262,144 bytes, all sources to
2,097,152 bytes, the artifact to 4,194,304 bytes, and JSON depth to 16.
Generation and validation are linear in bounded input size. Full regeneration
is intentional and retains no process-wide state.

SHA-256 provides integrity and equality, not authenticity, identity, trust, or
authorization. Diagnostics never include rejected JSON, raw content, absolute
paths, secrets, environment values, stack traces, or native messages. No source,
artifact, or command grants tool authority. Safe and Protected classification
still applies before inclusion in any later consumer.

## API and exclusions

The package marker is Public. Artifact, generator, validator, publisher, source,
authority, and four concrete exceptions are Experimental. Canonical JSON, path
guard, inventory definitions, inventory loader, command, diagnostic catalog,
and exception base are Internal. Nothing is Stable.

No generated artifact is checked in. The Knowledge package itself implements
no role, Application Graph input, retrieval, search, embedding, model,
provider, prompt, workspace reader, patch, auto-fix, deployment, or production
integration. Phase 4D's separate read-only Chie consumer is described in
[ADR 0063](../adr/0063-read-only-chie-explanation-and-diagnosis.md) and the
[Chie guide](read-only-chie.md); Hantei and later role capabilities remain
planned.

Phase 4F's separate read-only Oshie consumer uses one explicit current artifact to bind a closed teaching curriculum to exact source sections. Oshie teaching is derived evidence, not authority, and cannot mutate, publish, verify, or replace the artifact or its accepted sources. See [read-only Oshie](read-only-oshie.md).
## Phase 4G proposal consumption

Chie may consume one exact current-explanatory-documentation source embedded in
the validated current artifact to construct a deterministic candidate. This
does not make the artifact or proposal an authority, does not permit a second
workspace target read, and does not change artifact generation or validation.
The proposal binds the artifact and source-set identities and remains pending
human application plus fresh Hantei verification.

## External acceptance

Phase 4H generates the current artifact twice through the real Knowledge CLI,
requires byte-identical artifact and source-set identities, validates one
artifact as current, and binds Chie, Oshie, and real Hantei evidence to it.
Network-denied Knowledge generation remains local and deterministic. Generated
artifacts are written only to the verified temporary acceptance tree and are
removed before success. This proof does not change artifact authority,
publication, package behavior, or production integration. Phase 5 was not
begun.
