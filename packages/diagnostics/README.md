# Kongono Diagnostics

## Purpose and boundary

`kongono/diagnostics` provides presentation-neutral, bounded diagnostic schemas
and immutable values. Use it when a diagnostic owner needs a deterministic closed
structural snapshot. Do not use it as an arbitrary metadata bag, exception catch
root, renderer, transport, logger, serializer, redactor, or secret detector.

The package depends on `kongono/api-metadata` solely for `Api` and `ApiStatus`
metadata and has no Kernel dependency.
Application Graph, Kernel, and DI independently consume Diagnostics for bounded
values, but Diagnostics has no reverse dependency and owns no originating-package
identifier, schema, or throw-site meaning. Graph's 11, Kernel's six, and DI's 25
failures are integrated. Originating
packages own each diagnostic identifier's semantic schema and throw-site values.

[ADR 0034](../../docs/adr/0034-trusted-developer-diagnostic-projection.md)
assigns presentation to the separate leaf `kongono/diagnostic-presentation`,
and [ADR 0035](../../docs/adr/0035-trusted-developer-diagnostic-projection-implementation.md)
implements its pure trusted-developer projection core. The leaf consumes
Diagnostics, but Diagnostics has no reverse dependency and owns no developer
view model, projector, renderer, trusted-mode logic, HTML, JSON, CLI formatting,
or transport policy.

Phase 0S completed the atomic migration to `Kongono\ApiMetadata\Api` and
`ApiStatus`. The former Kernel FQCNs and metadata-only dependency are absent
without an alias or forwarder. Diagnostics behavior and its package-local
failure boundary are unchanged.

## Construction contract

`DiagnosticFieldSchema` accepts a lowercase ASCII snake-case key of at most 64
bytes, a non-empty valid UTF-8 label of at most 96 bytes, one exact
`DiagnosticValueKind`, one fixed `DiagnosticDisclosure`, and required/optional
status. `DiagnosticContextSchema` accepts non-empty valid UTF-8 operation and
invariant prose bounded to 160 and 256 bytes and a detached ordered list of at most
16 unique field schemas.

`DiagnosticContext` accepts only schema-approved string keys, requires every
required field, omits absent optional fields, rejects extra keys and wrong scalar
kinds, and returns fields in schema order. Schemas, caller maps, and returned arrays
are detached from stored snapshots.

```php
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

$schema = new DiagnosticContextSchema(
    'Validate entry',
    'Entry position must be available',
    [
        new DiagnosticFieldSchema(
            'entry_position',
            'Entry position',
            DiagnosticValueKind::Integer,
            DiagnosticDisclosure::Safe,
        ),
    ],
);

$context = new DiagnosticContext($schema, ['entry_position' => 2]);
```

Construction proves only structural compliance. It does not prove that this
example schema belongs to a Kongono diagnostic identifier.

## Fields and text representation

Kinds are exactly Text, Integer, and Boolean. Integer and Boolean values retain
their native value and have no text-truncation state. Text accepts arbitrary bytes
and stores only an ASCII representation: printable ASCII other than backslash is
unchanged; backslash, tab, line feed, and carriage return become `\\`, `\t`, `\n`,
and `\r`; all other bytes become uppercase `\xHH` tokens.

The representation is limited to 512 bytes. When the first complete token would
cross the limit, the longest complete-token prefix that leaves room is followed by
the exact 14-byte `...[truncated]` marker. This representation is deterministic and
binary-safe. It is not secret sanitization, redaction, HTML/JSON/shell escaping, or
transport encoding.

## Disclosure and security

Safe means eligible for a later explicit policy; it is not automatically public.
Protected values require a trusted development or protected operational boundary.
Passwords, tokens, credentials, private keys, complete bodies, environment dumps,
provider payloads, file contents, source excerpts, serialized objects, memory
dumps, and uncertain secret material must be excluded before construction. This
package does not recognize forbidden material.

Construction is eager, performs no I/O, consults no ambient state, and retains no
global or per-failure cache. These properties keep snapshots suitable for
persistent runtimes. Work is linear in schema count plus represented input bytes,
with hard bounds of 16 fields and 512 represented text bytes.

## Failures and API status

Invalid construction raises Experimental `InvalidDiagnosticValueException`
through the package's Public `ExceptionInterface`. The marker exposes only
diagnostic ID, title, and remediation. It is separate from Kernel, Graph, and DI
markers; failures do not carry recursive diagnostic context.

The two vocabularies, two schemas, and two values are Public at pre-alpha maturity.
The package marker is Public, the concrete failure is Experimental, and its catalog
and exception base are Internal. Public does not mean Stable.

## Explicit non-goals

Diagnostics owns no Kernel, Graph, or DI schemas, provides no context for its own
construction failures, and defines no universal exception accessor. It does not detect
or redact forbidden material, render an error page, enable trusted developer
mode, select production disclosure allowlists, serialize values, provide source
or documentation lookup, log, emit telemetry, or store incidents. Graph's 11,
Kernel's six, and DI's 25 schemas and values remain owned entirely by their
originating packages. Implemented projection ownership in the separate leaf
does not change this package's presentation-neutral role.
