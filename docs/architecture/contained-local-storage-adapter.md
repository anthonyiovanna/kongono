# Contained Local Storage Adapter

Phase 21D accepts two separately optional Experimental packages:
`kongono/storage-adapter-contract` and `kongono/storage-adapter-local`. The
first defines a provider-neutral bounded protocol. The second is one explicitly
rooted local-filesystem implementation of that protocol.

## Protocol and profile binding

An adapter descriptor binds Adapter Contract Version 1, one adapter identity,
one already validated `StorageProfileArtifact`, one exact profile name, and
closed guarantee and limitation sets. It preserves the artifact, source, and
build identities without rebuilding, scanning, selecting a newest artifact,
discovering an implementation, or validating exact currentness. The accepted
Phase 21E runtime owns exact-current composition through one directly supplied
compatible neutral adapter and does not require this local implementation.

The protocol exposes read, write, delete, and list through the accepted Phase
21B request/result vocabulary. Create requires `if_absent`; replace and delete
require exact `if_version` evidence. Unsupported capability, rejection,
absence, conflict, unavailability, and uncertain mutation remain distinct.
There is no generic options array, provider locator, credential, callback,
retry policy, registry, facade, service locator, or discovery API.

## Explicit root and contained layout

`ContainedLocalStorageAdapter` is constructed only from an explicit
`LocalStorageRoot`, a validated artifact, and an exact profile name. The root
must be an existing absolute ordinary directory. Relative, missing, URI,
stream-wrapper, wildcard, NUL, device, reserved-name, alternate-stream,
link/reparse, non-directory, and unresolvable inputs are rejected. Current
working directory, home, and operating-system temporary directories are never
implicit production roots.

All owned paths stay beneath `.kongono-storage-local-v1`. Namespace and object
components are lowercase SHA-256 identities over fixed domains and canonical
raw identities. Raw namespace and logical-object text never becomes a path
component. The adapter clears relevant stat/realpath caches and proves the root
and every existing component's ordinary type and exact canonical physical
resolution, including namespace, object and lock ancestors. Only separator and
drive-letter normalization is permitted; broad case folding grants no access.
Missing final components require a proved parent. Directories are created one
component at a time and rechecked. The proof is repeated after coordination,
immediately before opens, mutation, listing and cleanup, and against acquired
file handles where PHP exposes corresponding identity evidence.
Unexpected types, links/reparse points, separator ambiguity, traversal,
absolute escape, device paths, alternate streams, and unprovable case
ambiguity fail closed.

## Records, writes, and mutation truth

One object is stored in one ordinary framed record. Its bounded canonical JSON
header records the exact identity relation, byte count, digest algorithm and
digest, optional media type, ordered metadata, adapter-scoped version, and
selected profile/artifact evidence. Raw object bytes follow the header. There
is no PHP serialization, executable record, sidecar truth, mutable index,
current pointer, newest-file rule, or configuration scan.

Create and replace consume the caller-owned source once, forward-only, in
profile-bounded chunks into an exclusively created contained temporary sibling.
The adapter does not close, retain, rewind, or expose that source. Empty,
overlarge, early-exhausted, extra, size-mismatched, or digest-mismatched input
is rejected before publication. Create never overwrites. Replace and delete
require the exact accepted version. Known pre-effect failure remains rejected
or unavailable; an effect whose completion cannot be proved is uncertain and
is never success, failure, or permission to retry.

Each new create or replace generates `bin2hex(random_bytes(32))` under object
coordination before publication: a fresh opaque 256-bit generation token,
independent of content, media, metadata and operation identity. Entropy failure,
or a replacement candidate equal to the stored token, returns Unavailable
before mutation with no entropy retry or deterministic fallback. Collision
resistance across deletion/recreation is probabilistic, not guaranteed reuse
prevention. The v1 framing, schema and version grammar are unchanged. Valid
legacy records remain readable/listable without rewriting; their stored token
remains current for conditional mutation until replacement/deletion. Replacement
writes a random token. This cannot distinguish historical generations already
sharing a legacy token; all cooperating writers must use the correction.

The deterministic temporary pathname grants no ownership. Existing entries
are preserved and yield Unavailable; only successful exclusive open establishes
operation ownership. The adapter owns the handle and cleanup path until rename
transfers the temporary to the record. It attempts handle, contained owned-file
and lock retirement before choosing a result, while leaving the caller's write
source open. Invalid input with no record effect and successful cleanup is
Rejected. Required cleanup failure before record mutation is Unavailable.
After attempted/removed/published record effect, a later failure preventing
success proof is Uncertain, without success evidence. If ownership or physical
containment cannot be reproved, cleanup preserves the residue instead of
deleting through an unproved parent. Neutral results do not retain multiple
throwable causes.

## Reads, listing, and ownership

A completed read validates containment, framing, canonical header evidence,
identity/profile relations, length, digest, and complete content before
returning detached `StorageObjectEvidence` and one open `LocalReadSource`. The
caller owns that source and must close it explicitly. Reads are nonempty,
forward-only, and bounded by both the caller and profile chunk limits. Closing
releases file and coordination handles without changing the object.

Listing enumerates only the selected namespace's contained record directory.
Every candidate is revalidated; unexpected types, malformed records, mixed
profile evidence, duplicates, and containment failures reject the operation.
Entries are ordered by canonical logical identity and limited by the smaller
of the request and profile page bounds. Continuation evidence is opaque,
checksum-protected, deterministic, and bound to the exact adapter/artifact/
profile/namespace relation. Listing is not a snapshot or transactional view.

## Guarantees, limitations, and nondisclosure

The implementation declares create-if-absent, version-matched replace/delete,
read after completed mutation, ordinal bounded listing, metadata round-trip,
content-digest verification, and operation cleanup. It also declares local-
filesystem-only, single-host-only, filesystem-dependent atomicity and
durability, no snapshot listing, no public URL, no replication, no encryption
guarantee, no cross-process transaction, and no out-of-band-writer protection.

These portable pathname checks reject the demonstrated pre-existing redirected
ancestors. They do not provide native handle-relative race-proof I/O, prevent a
hostile process replacing ancestors between check and use, or detect every
conceivable reparse technology. There is no native helper, FFI or extension
dependency, and no universal filesystem containment claim.

Messages, diagnostics, exception strings, and representations omit object and
chunk bytes, raw identities, metadata, roots, paths, filenames, handles,
versions, continuation tokens, supplied digests, credentials, provider
locators, native messages, and tenant data. Hashing forbidden raw data does not
make it Safe.

Mutable state is operation-local. Stable lock files coordinate cooperating
instances; operation-owned handles and temporaries have explicit retirement
attempts on success, expected outcomes, failures and uncertainty. Failed or
unproved retirement is failure truth, never a successful cleanup claim.
Destructors and shutdown hooks
are not lifecycle control. Independent roots and instances remain isolated
across sequential, repeated, failure-then-success, and overlapping operations.

## Non-claims and later composition

Phase 21D adds no exact-current runtime, command, application workflow,
authorization mechanism, credential source, cloud/provider adapter, public
delivery, inspection, external acceptance, checkpoint, replication,
encryption/KMS, backup, multipart transfer, watcher, queue, or background
worker. It makes no Stable/1.0, universal filesystem, benchmark, durability,
production-readiness, PHP 8.6, or PHP 9 claim.

See [ADR 0210](../adr/0210-neutral-storage-adapter-and-contained-local-filesystem-implementation.md),
the [Storage Platform guide](storage-platform.md), the
[storage-authoring guide](../engineering/storage-authoring.md), and the fixed
[Phase 21 roadmap](phase-21-roadmap.md).
