# Deterministic Storage-Profile Build

Phase 21C accepts the optional Experimental `kongono/storage-build` leaf. It
sits above `kongono/storage-contract` and has exactly three local production
dependencies: API Metadata, Diagnostics, and the neutral Storage Contract. The
package is optional; applications that do not need storage-profile evidence
install none of it.

## Profile authoring

Callers supply one complete `StorageProfileSet` containing 1 through 256
declarations. Each declaration binds:

- an ordinal ASCII lowercase `StorageProfileName`;
- Contract Version 1;
- one unique `StorageNamespace`;
- one non-empty ordered `StorageCapabilitySet`;
- a digest algorithm exactly when content-digest capability is declared; and
- positive chunk, object, and list-page limits within Storage Contract bounds.

Profile names and namespaces are unique. Chunk size cannot exceed object size.
Capabilities use their closed declaration order, and profiles use ascending
profile-name byte order. Caller order, locale, time, randomness, environment,
working directory, installed provider, and host state cannot affect the model.

No profile contains a provider, endpoint, bucket, filesystem path, credential,
principal, authorization result, timeout, retry policy, public URL, adapter,
runtime object, callback, or executable behavior.

## Identities and artifact shape

Source, build, and artifact identities are separate lowercase SHA-256 domains:

- source identity hashes the canonical model under
  `kongono/storage-profile-source/1`;
- build identity hashes the fixed schema and source identity under
  `kongono/storage-profile-build/1`; and
- artifact identity hashes the exact artifact bytes, including the terminal
  LF.

The only schema identifier is `kongono/storage-profile-artifact/1`. Top-level
members are ordered exactly as follows:

```text
schema
source_identity
build_identity
profile_count
profiles
```

Each profile contains `name`, `contract_version`, `namespace`, `capabilities`,
`digest_algorithm`, and `limits` in that order. Limits contain
`max_chunk_bytes`, `max_object_bytes`, and `max_list_page_size` in that order.
The nullable digest member is always present.

Artifacts are compact canonical UTF-8 JSON without a BOM or CR, with exactly
one terminal LF, a 1,048,576-byte maximum including that LF, and maximum JSON
depth 16. Unknown, missing, duplicate, reordered, malformed, unsupported, or
noncanonical evidence fails closed.

## Structural and exact-current validation

Structural validation decodes duplicate-safely, enforces every schema, key,
type, order, bound, relation, and canonicalization rule, rebuilds the complete
typed profile set, recomputes all three identities, and requires exact rebuilt
bytes.

Exact-current validation first completes structural validation. It then builds
again from one explicitly supplied complete current set and succeeds only when
the supplied artifact bytes are exactly equal. A valid artifact for any other
set is stale. Validation never scans files, selects a newest artifact, repairs
input, contacts a provider, discovers credentials, or performs an object
operation.

## Contained publication

Publication requires one already validated artifact and one exact explicit
absolute root. The final relative path is:

`objects/sha256/<first-two-digest-bytes>/<artifact-digest>/storage-profile.json`

Only the required contained directories may be created. The publisher uses a
unique exclusively created temporary sibling, writes every byte, flushes and
closes it, and renames within the same directory. It rejects relative,
malformed, stream-wrapper, device, traversal, wildcard, NUL-containing,
link/reparse, and escaping roots or path components.

The publisher never overwrites, truncates, repairs, replaces, or deletes an
existing final artifact. Exact ordinary existing bytes are idempotent evidence;
different bytes and unexpected path types fail closed. Cleanup failure remains
a publication failure. There is no mutable current pointer, latest alias,
manifest index, provider location, public URL, or runtime registration.

## Security and disclosure boundary

Storage profiles and artifacts are evidence, not authority. They grant no
filesystem, provider, network, credential, authorization, adapter, runtime, or
storage-operation capability. Diagnostics and exceptions are fixed, bounded,
and nondisclosing: raw names, namespaces, artifact JSON, roots, paths, supplied
digests, provider material, credentials, and arbitrary native messages do not
appear.

The package has 24 production declarations: 17 Experimental, six Internal,
one Public exception-family interface, and none Stable. Its 15 package-test
files cover deterministic construction, strict currentness, adversarial
publication containment, complete cleanup, and nondisclosure.

## Later composition

Phase 21D accepts the separately optional neutral adapter protocol and
contained local-filesystem implementation described by
[ADR 0210](../adr/0210-neutral-storage-adapter-and-contained-local-filesystem-implementation.md)
and the [contained local adapter guide](contained-local-storage-adapter.md).
Phases 21E-G are accepted; Phase 21H implements root-owned local acceptance with
separate validation, audit and checkpoint gates. The exact-current
runtime consumes only explicit current evidence and a directly supplied
adapter; it does not scan, rebuild, discover, reconcile, or fall back. Phase
21F adds `storage:plan`, `storage:build`, and `storage:check` through the
separate application workflow. This build package itself exposes no command
and connects to no runnable storage provider.

See [ADR 0209](../adr/0209-deterministic-storage-profile-artifact-and-contained-publication.md),
the [Storage Platform guide](storage-platform.md), the
[storage-authoring guide](../engineering/storage-authoring.md), and the fixed
[Phase 21 roadmap](phase-21-roadmap.md).
