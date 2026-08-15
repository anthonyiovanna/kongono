# ADR 0028: Bounded Diagnostic Context and Disclosure

## Status

Accepted

## Date

2026-08-13

## Context

Phase 0M implements package-local runtime diagnostic identifiers, titles, and
remediation for the 42 deliberate failures currently raised by the Kernel,
Application Graph, and Dependency Injection packages. Existing exception
messages remain protected prose, and the three package markers remain separate
catch boundaries. No structured diagnostic context or renderer exists.

A later developer experience needs precise facts about a failed operation and
violated invariant. Capturing those facts without a closed schema, disclosure
classification, and hard bounds would create an arbitrary data channel that
could retain secrets, caller-controlled payloads, or unbounded process state.

## Decision

A future presentation-neutral package named `kongono/diagnostics` will own the
Public value concepts
`Kongono\Diagnostics\Context\DiagnosticContext`,
`Kongono\Diagnostics\Context\DiagnosticField`, and
`Kongono\Diagnostics\Context\DiagnosticDisclosure`. This phase reserves the
concepts only; it creates no package, dependency, PHP type, or accessor.

Originating packages continue to own diagnostic meaning: identifier, title,
remediation, fixed operation, fixed violated invariant, allowed field schema,
and throw-site values. The shared package does not own Kernel, Graph, or DI
semantics and does not define a universal exception interface. Their Public
markers remain pairwise unrelated recovery boundaries.

`DiagnosticDisclosure` has exactly two semantic cases:

- `Safe`: catalog-approved information eligible for intentional disclosure
  only when a future presentation or transport policy allows it.
- `Protected`: diagnostic information available only within a trusted
  development or protected operational boundary.

Forbidden material is not a third disclosure case. It is never captured.

A future `DiagnosticContext` is an eager, immutable, ordered, bounded snapshot
containing one catalog-controlled operation, one catalog-controlled violated
invariant or expectation, and zero or more schema-approved fields. A future
`DiagnosticField` contains a canonical key, fixed catalog label, bounded scalar
value, fixed disclosure, and a text-truncation flag. Exact PHP construction and
access APIs remain unselected.

Context is schema-controlled rather than an arbitrary map. For each identifier,
the owning Internal catalog will define fixed operation and invariant prose plus
the exact allowed keys, labels, scalar kinds, disclosures, order, and
required/optional status. Throw sites may supply values only; they may not
invent schema. Optional unavailable facts are omitted rather than represented
by `null`.

Keys are unique lowercase ASCII snake case matching
`^[a-z][a-z0-9]*(?:_[a-z0-9]+)*$`, contain at most 64 bytes, keep one semantic
meaning wherever reused, and never encode a value, index, locale, version, or
environment. Fixed English labels contain at most 96 UTF-8 bytes; labels are
not stable machine identity and later localization requires a separate decision.

Allowed value kinds are exactly `text`, `integer`, and `boolean`. Floats,
`null`, arrays, maps, lists, objects, enums, resources, callables, closures,
`Throwable` values, lazy suppliers, serialized data, raw JSON, stringified
objects, and nested context are prohibited. Multiple facts require multiple
approved fields.

One context has at most 16 fields. A text value has at most 512 bytes in its
future bounded representation and exactly one truncation flag; integer and
boolean fields have no truncation flag. Truncation will be deterministic and
visibly marked, but its exact UTF-8, binary escaping, and truncation algorithm
remains open. Integers fit the supported 64-bit PHP native range, and schema
counts are non-negative.

Field order is catalog schema order, never caller insertion, hash, reflection,
locale, or implicit alphabetical order. Context and fields are detached
immutable snapshots. Repeated access to one exception will be semantically
identical and will not depend on later state.

The complete normative 42-identifier schema, including exact operations,
invariants, fields, kinds, disclosures, order, optionality, and controlled Safe
tokens, is recorded in the [error-handling engineering
guide](../engineering/error-handling.md#accepted-future-context-schemas). Each
identifier retains its current meaning; both DI invariant-defense throw sites
continue to share `KGN-DI-1025` and one schema.

Future capture is synchronous and eager at the deliberate failure site. It may
read only explicit arguments and already-owned immutable framework state. It
performs no I/O and consults no global request, superglobal, container,
current-user, thread-local, fiber-local, environment, clock, random source,
filesystem, network, database, logger, tracer, or AI system. Per-failure values
are never stored in a static registry or global cache and live no longer than
their owning exception. Work and memory are bounded by the 16-field maximum.
Capture failure must never silently replace the primary deliberate failure;
their simultaneous representation requires a later decision.

A future trusted developer presentation may use Safe fields and, only under
server-controlled trusted disclosure, Protected fields. A production surface
requires its own explicit allowlist. Safe means eligible, not automatically
public. Protected values, native messages, traces, paths, source, headers,
bodies, and environment values are excluded from untrusted production output by
default. Client input and AI output cannot enable trusted mode, classify fields,
override disclosure, recover forbidden data, or become necessary to interpret a
known diagnostic.

Passwords, password hashes, passphrases, PINs, API keys, access or refresh
tokens, bearer tokens, cookies, session identifiers, CSRF values, authorization
headers, credentials, private/signing/encryption keys, recovery codes,
credential-bearing connection strings, complete environment/configuration
dumps, complete request or response bodies, arbitrary headers, SQL with bound
values, full provider payloads, file contents, source excerpts, serialized
objects, memory dumps, and uncertain secret material are forbidden from context.
A future boundary may record that such data was omitted, never the data itself.

Phase 0O selects no serialization, renderer, HTML, CSS, template, JSON,
Markdown, transport mapping, localization, documentation lookup, source
provider, logging, telemetry, retry, cleanup, or incident mechanism.

## Rationale

Package-owned closed schemas preserve semantic authority and make context
deterministic, testable, and reviewable. Hard scalar and size limits make capture
safe for persistent workers. Separating eligibility from actual disclosure
prevents developer usefulness from becoming an accidental production data leak.
A presentation-neutral value package can later serve multiple adapters without
erasing package-specific recovery ownership.

## Consequences

Later implementation must add exact schema metadata and throw-site capture for
all current identifiers without changing diagnostic identity. It must validate
keys, kinds, bounds, order, disclosure, immutability, forbidden capture, and
persistent-runtime behavior deterministically. A future accessor may be added to
each existing package marker, but its name and PHP signature are not selected.

Schema authors cannot attach arbitrary metadata, and values that do not fit a
bounded scalar schema must be modeled as separate approved facts or omitted.
Future renderers and transports must apply policies in addition to the stored
classification. The 42-schema target is Accepted architecture, not current
runtime behavior.

Compatibility impact is deferred: current marker methods and exception behavior
are unchanged, no Public PHP signature changes, and no new package exists.

Phase 0T implementation note: the value package exists, and Kernel is now the
second semantic consumer after Graph. All six Kernel schemas use only required
Safe fields, Kernel throw sites capture exact closed values before mutation, and
the Public Kernel marker exposes `diagnosticContext()`. This pre-alpha signature
addition preserves package-local recovery and does not authorize disclosure.

Phase 0U implementation note: DI is now the third independent semantic consumer.
Its 25 schemas and 26 exact value maps use bounded counts, positions and closed
Safe tokens plus already-available Protected contract and implementation names.
The Public DI marker exposes `diagnosticContext()` without authorizing disclosure
or creating a universal recovery boundary.

Phase 0W amendment: [ADR 0034](0034-trusted-developer-diagnostic-projection.md)
resolves future presentation ownership in favor of the separate planned leaf
`kongono/diagnostic-presentation`. Its first planned model is a pure trusted-
developer projection over exactly one Kernel, Graph, or DI contextual marker.
It preserves canonical field order and exact Safe/Protected classification,
includes Protected fields only after a future server-controlled outer
integration authorizes trusted use, and is prohibited as a production model.
Diagnostics remains presentation-neutral with no reverse dependency. Concrete
renderers, formats, transport integration, and production allowlists remain
open, and Phase 0W implements no package or runtime behavior.

Phase 0X implementation note: [ADR 0035](0035-trusted-developer-diagnostic-projection-implementation.md)
creates the leaf and implements the pure detached trusted-developer projection.
It preserves both disclosure classifications exactly and adds no trusted-mode
selector, renderer, production model, transport, or reverse Diagnostics edge.

Phase 0Y architecture note: [ADR 0036](0036-accessible-trusted-developer-html-diagnostic-page.md)
selects a planned pure HTML page that visibly preserves every Safe and Protected
classification. The page neither authorizes nor reclassifies a field. Exact
HTML escaping prevents markup injection but does not declassify Protected data;
production remains a separate deny-by-default model. Phase 0Z implements only
that pure renderer and exact escaping; it does not authorize disclosure.

## Security considerations

Disclosure is fixed by the owning schema, not selected by a throw site,
renderer, request, environment value, or AI system. Opaque consumer-provided
text defaults to Protected and cannot be promoted because it resembles a class,
path, key, route, or identifier. Forbidden data is excluded at capture rather
than redacted into context. Trusted developer mode is controlled only by trusted
server configuration.

## Performance implications

Future capture performs bounded work proportional to at most 16 fields, creates
an eager detached snapshot, performs no I/O, and retains no global per-failure
state. Exact representation and allocation details remain an implementation
decision.

## Rejected alternatives

- Arbitrary arrays, maps, metadata bags, and nested values are rejected because
  they evade package schema, bounds, and review.
- A `Secret` disclosure class is rejected because secret-bearing data must not
  be captured at all.
- A universal Kongono exception or diagnostic marker is rejected because
  recovery ownership remains package-specific.
- Raw message parsing is rejected because message prose is protected and not a
  machine contract.
- Automatic production disclosure of Safe fields is rejected because transport
  policy remains explicit.
- Lazy or ambient capture is rejected because later state and cross-operation
  data would undermine immutability and persistent-runtime isolation.
- Implementing a renderer in Phase 0O, before the context foundation and
  presentation ownership existed, was rejected as premature coupling.

## What remains open

- Representation of simultaneous primary and context-capture failure.
- Version-matched documentation lookup.
- Other renderer formats.
- Source excerpt and stack-frame providers.
- HTTP and CLI mappings, production allowlists, and localization.
- Incident storage and references.
- Logging and telemetry.
- Retry, cancellation, cleanup failure representation, and recovery.
- Serialization and cross-process or cross-language form.
