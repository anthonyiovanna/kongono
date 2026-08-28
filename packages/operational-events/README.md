# Kongono Operational Events

## Status

`kongono/operational-events` is Experimental. It is a provider-neutral boundary
for recording bounded structured operational events as neutral Observability
records. It depends only on API Metadata, Diagnostics, and Observability.

## Contract

An `OperationalEventSchema` predeclares zero through thirty fields. Each field
has an exact key, scalar kind, disclosure classification, required flag, and
one through sixteen allowed values. Schemas reject reserved `event.*` and
`audit.*` keys and fields suggesting credentials, cookies, CSRF data, request
or database bodies, raw headers, passwords, policy bodies, query strings,
secrets, sessions, stack data, tokens, or URLs. Validation rejects missing,
unknown, wrongly typed, and out-of-domain fields before recording.

An event carries an explicit Observability context, definition, status, and
attribute set. Its disclosure is Protected only when an admitted supplied field
is declared Protected. Recording synchronously emits exactly one neutral
Operational Event observation with reserved severity and disclosure attributes.

## Failure, lifecycle, and extension boundary

Recorder failure is final for that call, preserves the exact previous throwable
for trusted handling, and is exposed only through a fixed non-disclosing package
exception. The runtime retains only the explicit recorder capability, so calls
share no event or schema state and recorder reentry is deterministic.

The package provides no logging facade, formatter, channel, clock, storage,
buffer, batching, sampling, routing, exporter, provider, application wiring,
retry, flush, or durability claim. A successful call means only that the
supplied in-process recorder returned successfully.

See [ADR 0119](../../docs/adr/0119-structured-operational-events-and-bounded-security-audit-evidence.md)
and the [Observability and Operational Evidence guide](../../docs/architecture/observability-and-operational-evidence.md).
