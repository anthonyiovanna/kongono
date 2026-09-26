# ADR 0208: Neutral Storage Contract and Bounded Evidence Vocabulary

- Status: Accepted
- Date: 2026-09-18
- Phase: 21B

## Context

Phase 21A accepted the architecture for an optional provider-neutral Object
Storage Platform. Storage may hold authoritative, irreplaceable
application-owned bytes, so the first callable boundary must preserve logical
identity, content evidence, mutation truth, provider location, authorization,
and effect authority as separate domains before any profile, adapter, runtime,
or application workflow exists.

The neutral vocabulary must also make every size finite, distinguish known
completion from uncertainty, and express explicit stream lifecycle ownership
without performing an operation or implying provider guarantees.

## Decision

Accept the optional Experimental `kongono/storage-contract` leaf. Its only
production dependencies are PHP 8.5, `kongono/api-metadata`, and
`kongono/diagnostics`. No existing production package depends on it.

### Identity, operations, and capabilities

`StorageNamespace`, `LogicalObjectIdentity`, and
`StorageOperationIdentity` use the canonical lowercase ASCII grammar
`\A[a-z][a-z0-9]*(?:[._-][a-z0-9]+)*\z` and are bounded to 64, 255, and 64
bytes respectively. `StorageObjectIdentity` composes a namespace and logical
identity without producing a path, URL, provider locator, or authority token.

The closed operation vocabulary is read, create, replace, delete, and list.
Declared support uses exactly read, create, replace, delete, list, metadata,
content digest, and conditional mutation. A `StorageCapabilitySet` is ordered,
deduplicated support evidence only; it grants no authorization, credential,
provider selection, or effect authority.

### Content and evidence bounds

Contract Version 1 fixes these maximums: namespace 64 bytes, logical identity
255 bytes, operation identity 64 bytes, object version 256 bytes, continuation
token 512 bytes, media type 127 bytes, metadata 32 entries, metadata name 64
bytes, metadata value 1,024 bytes, chunk 8,388,608 bytes, object
1,099,511,627,776 bytes, and list page 1,000 entries.

`StorageByteCount` covers zero through the object maximum. `StorageChunk` is
the only contract value that carries object bytes and is nonempty. Media types
are lowercase canonical type/subtype values without parameters or sniffing.
Content digests use exact lowercase hexadecimal SHA-256, SHA-384, or SHA-512
evidence. Metadata names are canonical; values are valid UTF-8 without control
characters; collections are unique, ordinally sorted, bounded, and detached.
Object versions and continuation tokens are opaque bounded base64url evidence.
None of these values claims origin, durability, ordering, authorization, or
provider currentness.

### Mutation and request relations

Mutation conditions are only `if_absent` and `if_version`. There is no
unconditional overwrite. A create request requires `if_absent`; replace and
delete require exact object-version evidence. Read carries an explicit maximum
byte count. Write carries declared length, required digest, required media
type, and bounded metadata. List carries a page size from 1 through 1,000 and
an optional opaque continuation token.

Every request carries Contract Version 1 and one caller-supplied operation
identity. Requests contain no adapter, provider, locator, path, URL,
credential, authorization result, stream, callback, retry policy, timeout, or
runtime object.

### Outcomes and detached results

Read distinguishes completed, absent, rejected, unsupported, and unavailable.
Mutation additionally distinguishes already existing, conflict, and uncertain.
List distinguishes completed, rejected, unsupported, and unavailable.

Completed reads require detached object evidence; every other read outcome
forbids it. Completed mutations require a resulting version and may carry a
digest; uncertain and every non-completed mutation forbid resulting-version or
digest claims. Completed lists require a bounded page; other outcomes forbid
one. Pages are unique and strictly ordered by namespace then logical identity.
A complete page has no continuation; an incomplete page requires one. These
relations prevent a failure or uncertain effect from being presented as known
success.

### Streaming lifecycle

`StorageByteSource` permits bounded reads of 1 through the maximum chunk size;
`null` is legal only at exhaustion. `StorageByteSink` accepts bounded chunks.
After close or abort, no read or write is legal. Close and abort are explicit,
idempotence is not promised, destructors are not lifecycle control, and an
implementation owns timely resource retirement. The interfaces grant no
storage or provider authority, and Phase 21B provides no I/O implementation or
I/O-performing fixture.

### Diagnostics and API status

Eight diagnostics cover invalid identity, capability, content evidence,
mutation condition, request, result, stream contract, and exceeded bounds.
Package-owned context is limited to closed Safe violation, operation, outcome,
capability, digest-algorithm, entry-count, entry-position, and limit fields.
Unsupported caller text becomes a fixed fallback. Messages, context, and
deterministic string presentation omit raw identities, metadata values,
tokens, content bytes, provider-like values, paths, URLs, credentials, rejected
input, and arbitrary provider text.

The package contains 40 declarations: 37 Experimental declarations and three
Internal implementation declarations (`StorageContractDiagnostic`, abstract
`StorageContractException`, and `ContractGuard`). No declaration is Public or
Stable.

## Consequences

Applications and later optional packages can exchange precise bounded storage
evidence without selecting a provider or performing an effect. The explicit
relations add construction work, but prevent silent overwrite, unbounded data,
provider vocabulary leakage, and false certainty. Lower packages and the root
framework remain usable without Storage.

## Alternatives considered

- A cloud SDK or provider-shaped contract was rejected because vendor types,
  locators, and options would redefine neutral meaning and authority.
- Filesystem paths, URLs, or bucket keys as logical identities were rejected
  because application selection and provider location are distinct domains.
- Unconditional overwrite, hidden retry, and treating uncertainty as failure
  were rejected because authoritative bytes require honest mutation truth.
- Unbounded strings, metadata, chunks, lists, or whole-object buffering were
  rejected because limits must exist before an effect.
- A facade, registry, default provider, ambient root, or credential discovery
  was rejected because possession of data is not operation authority.

## Excluded behavior

Phase 21B adds no profile schema, artifact, build, publication, adapter,
provider, locator mapping, filesystem operation, network operation, credential
source, authorization system, runtime, command, retry, application workflow,
example, inspection, external acceptance, durability guarantee,
provider-compatibility claim, Stable/1.0 status, Phase 21C, or later behavior.
