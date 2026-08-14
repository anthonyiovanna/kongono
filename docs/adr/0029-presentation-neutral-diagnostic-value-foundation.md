# ADR 0029: Presentation-Neutral Diagnostic Value Foundation

## Status

Accepted

## Date

2026-08-13

## Context

ADR 0028 accepts bounded, schema-owned diagnostic context but leaves its PHP
construction, package dependency, structural validation, and text representation
open. Kongono needs those presentation-neutral values before the existing Kernel,
Application Graph, and Dependency Injection failures can safely capture context.

## Decision

Create `kongono/diagnostics` as the fourth monorepo package. At Phase 0P it
depends on `kongono/kernel` only for the Public `Api` and `ApiStatus` compatibility metadata;
Kernel, Application Graph, and Dependency Injection do not depend on Diagnostics
in this phase.

The package exposes six Public pre-alpha value concepts:

- `DiagnosticContext` and `DiagnosticContextSchema`;
- `DiagnosticField` and `DiagnosticFieldSchema`;
- `DiagnosticDisclosure`; and
- `DiagnosticValueKind`.

Schemas are closed structural plans, not arbitrary context bags. A field schema
has a unique lowercase ASCII snake-case key of at most 64 bytes, a non-empty valid
UTF-8 label of at most 96 bytes, one exact scalar kind, one fixed disclosure, and
required or optional status. A context schema has non-empty valid UTF-8 operation
prose of at most 160 bytes, invariant prose of at most 256 bytes, and at most 16
detached field schemas in supplied order. Kinds are exactly text, integer, and
boolean. Context values are detached, validated against exact schema keys, omit
absent optional fields, reject missing required fields and wrong native kinds,
and produce fields only in schema order.

Structural validity does not authorize semantic use. The originating package
continues to own which schema belongs to a diagnostic identifier and which values
a throw site may supply. Consumer-created schemas acquire no Kongono-owned
diagnostic meaning merely by satisfying these structural rules.

Text accepts arbitrary bytes and stores only a deterministic ASCII representation.
Printable ASCII except backslash is retained; backslash, tab, line feed, and
carriage return become `\\`, `\t`, `\n`, and `\r`; every other byte becomes
`\xHH` with uppercase hexadecimal. Complete encoded-byte tokens are accumulated
only until the first token would exceed 512 represented bytes. A truncated value
retains the longest complete-token prefix that leaves room for the exact 14-byte
`...[truncated]` marker. No token is split and the original string is not retained.

Disclosure has exactly Safe and Protected. Safe means eligible for a later
explicit policy, not automatically disclosed. Protected remains confined to a
trusted development or protected operational boundary. Credentials, secrets,
unbounded payloads, and uncertain material remain excluded before value
construction; representation does not detect, redact, or sanitize them.

The package owns a fourth Public exception marker, an Internal exception base and
11 Internal catalog identifiers from `KGN-DIAGNOSTICS-1001` through
`KGN-DIAGNOSTICS-1011`. Its construction failures expose identifier, title, and
remediation but no diagnostic context, avoiding recursive context construction.
The marker remains unrelated to the Kernel, Graph, and DI markers.

Phase 0S implementation note: [ADR 0031](0031-neutral-api-metadata-ownership-and-acyclic-package-dependencies.md)
is implemented. Diagnostics now depends only on `kongono/api-metadata` for
`Kongono\ApiMetadata\Api` and `ApiStatus`; it has no Kernel dependency. The
namespace and manifest migration changes no Diagnostics value, failure,
disclosure, context, security, or runtime behavior.

Values are eager immutable snapshots. Construction performs no I/O and consults
no ambient, global, runtime, environment, clock, random, logging, telemetry, or AI
state. No per-failure global state or mutable cache is retained. Work is linear in
the schema count plus the represented input bytes, bounded to 16 schemas or fields
and 512 represented text bytes.

The six value types are Public under Kongono's pre-alpha release maturity. The
package marker is Public, the concrete invalid-value failure is Experimental, and
the catalog and exception base are Internal. This phase does not change the 42
existing failures, their constructors, markers, catalogs, messages, or throw sites.

## Consequences

Consumers can construct deterministic bounded structural values without selecting
a renderer or transport. The new Public surface creates a compatibility obligation
under current pre-alpha policy, while semantic diagnostic schemas remain with their
originating packages. Persistent workers cannot accumulate per-failure context
through this package.

## Rejected alternatives

- Arbitrary bags and nested values are rejected because they evade closed schema,
  ordering, and bounds.
- Lazy values and ambient capture are rejected because later state could cross
  operation boundaries.
- Raw unbounded text is rejected because it permits uncontrolled retention.
- Locale-dependent or character-normalizing representation is rejected because
  arbitrary bytes require deterministic treatment.
- A universal exception marker is rejected because recovery ownership remains
  package local.
- Rendering in this package is rejected because presentation and disclosure
  policy remain separate decisions.

## Open decisions

All 42 original Kernel, Graph, and DI diagnostics now integrate independently
with this foundation. The following remain open: representation of simultaneous
primary and context-capture failure; renderer and
transport APIs; production allowlists; serialization and cross-process
representation; localization; source providers; documentation lookup; logging
and telemetry; and incident storage.
