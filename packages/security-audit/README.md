# Kongono Security Audit

## Status

`kongono/security-audit` is Experimental. It records bounded structured
security-audit evidence as neutral Observability records and depends only on API
Metadata, Diagnostics, and Observability.

## Contract

A `SecurityAuditSchema` predeclares one through twenty-eight exact bounded
fields. Each field declares a key, scalar kind, disclosure classification,
required flag, and one through sixteen allowed values. Reserved namespaces and
keys suggesting credentials, cookies, CSRF data, request or database bodies,
raw headers, passwords, policy bodies, query strings, secrets, sessions, stack
data, tokens, or URLs fail closed. Evidence construction rejects missing,
unknown, wrongly typed, and out-of-domain fields before recording.

Evidence carries an explicit context, definition, outcome, attribute set, and
derived disclosure. Recording synchronously emits one neutral Security Audit
observation. Succeeded maps to Ok; Denied and Failed map to Error. Reserved
attributes state the outcome, retention intent, disclosure, and the exact
durability truth `not_guaranteed`.

## Authority, retention, and failure

Evidence is not identity, authentication, authorization, exact Permit, a
capability, policy, credential, session, or replayable grant. Retention intents
are labels only and define no duration, storage class, durability, deletion,
legal hold, privacy, compliance, tamper evidence, or incident-response policy.

Recorder failure is final for that call and retains the exact previous
throwable behind a fixed non-disclosing package exception. The runtime retains
only its recorder, shares no record state, and permits deterministic reentry.
A successful call means only that the supplied in-process recorder returned;
the package provides no store, export, backend, provider, queue, retry, flush,
application integration, or durability claim.

See [ADR 0119](../../docs/adr/0119-structured-operational-events-and-bounded-security-audit-evidence.md)
and the [Observability and Operational Evidence guide](../../docs/architecture/observability-and-operational-evidence.md).
