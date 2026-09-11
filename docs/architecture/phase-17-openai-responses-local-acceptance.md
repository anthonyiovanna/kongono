# Phase 17 OpenAI Responses Local Acceptance

## Purpose and command

Phase 17H closes the accepted Phase 17 milestone with one root-owned local
external-process harness. Run it explicitly with:

```text
composer acceptance:phase17
```

The command is not part of `composer quality` or the eight canonical Hantei
checks because it deliberately launches child processes and binds an owned
IPv4 loopback listener.

## What it proves

Every fresh success child performs four independent operations through the
accepted production composition: version 1 non-streaming text, version 1
pull-based text SSE, version 2 strict structured output, and version 2 inert
function-call suggestion. Each operation starts from an exact-current Phase
17F profile, uses Phase 14 neutral invocation, borrows one Phase 16 credential
lease for one request construction, and delegates one attempt to Phase 15's
real first-party cURL HTTP/1.1 transport.

The text cases prove exact normalized text and bounded usage. The SSE case
proves ordered consumer-driven framing, contiguous neutral events, terminal
text and usage, and deterministic close without a background reader,
reconnect, or replay. The structured case validates one explicit closed JSON
schema without coercion. The function-call case produces only one inert
`ActionSuggestion`; it invokes no function, tool, proposal, capability, or
application effect and creates no second provider operation.

The child also proves Phase 17F build, write-free check, exact-current wiring
validation, one trusted contained load, single-use lifecycle, stale artifact
rejection, and later-operation isolation. Phase 17G inspection revalidates the
matching detached evidence and constructs only Safe aggregates, closed tokens,
and Protected digests. Chie, Hantei, and Oshie values address evidence purposes
only; no role, recorder, exporter, telemetry sink, repair, replay, provider
observer, or certification process runs.

## Local fixture trust model

The provider-protocol emulator is test-owned and binds only `127.0.0.1` on an
operating-system-assigned port. The service binding uses an RFC-reserved
`.invalid` hostname. A test-owned selected-address result supplies loopback
directly, so the exchange performs no ambient hostname lookup and cannot route
to a public address.

The production adapter's exact profile requires HTTPS. Each child therefore
creates an ephemeral certificate and key inside its private workspace, and the
cURL transport trusts only that fixture certificate. The Phase 15 policy uses
the explicit private-address and local HTTP-allowed test postures; the actual
provider-profile request remains HTTPS. Neither posture authorizes production
plain HTTP, public TLS, another destination, or another interface.

The emulator accepts exactly four connections, each with finite timeouts. It
reads at most 65,536 bytes, verifies the exact request shape privately, and
writes at most 65,536 bytes. The inert bearer value exists only in child memory
during borrowed material use. Dynamic ports, selected addresses, credentials,
headers, bodies, payloads, paths, and foreign failures are omitted from public
output.

## Isolation, output, and cleanup

The parent runs repeated sequential success, a failure followed by fresh
health, and two concurrent success children. It rejects unexpected arguments,
malformed output, nonzero success exits, stdout or stderr exceeding 1,048,576
bytes, timeouts, repository status drift, and temporary residue.

Success emits exactly one compact LF-terminated line shaped as:

```text
{"status":"ok","profile":"kongono-phase17-openai-responses","case_count":N,"cleanup":"complete"}
```

`N` is the positive observed deterministic case count. Failure emits only
`phase17_acceptance_failed` followed by LF. Sequential and concurrent success
outputs must be byte-identical.

Every child-owned file is contained beneath one unique
`kongono-phase17-*` directory directly under the system temporary directory.
Traversal, links, junctions or other reparse points, special files, and paths
outside that workspace fail closed. Processes, listeners, sockets, streams,
cURL sessions, application/runtime sessions, credential leases, and workspace
content are closed or removed on success and failure.

## What it does not prove

The command uses a synthetic local emulator, not OpenAI. It does not validate a
real account or credential and does not establish live compatibility,
certification, entitlement, current remote schemas or behavior, public DNS or
TLS deployment, rate limits, retention, billing, pricing, compliance,
performance, production readiness, Stable/1.0 status, release readiness, or a
remote effect.

## Accepted closure

[ADR 0178](../adr/0178-phase-17-openai-responses-local-acceptance-and-checkpoint.md)
accepts this proof. Phase 17A-H are Accepted and Phase 17 is closed by the
checkpoint subject `Complete Kongono Phase 17 OpenAI Responses milestone` with
parent `3690aab42e3759d3f42e711703d4f23e6588fa85`; the completion report records
the resulting commit and tree identities. Topology remains 89 packages / 375
local production edges / acyclic, with PHP inventories 1,726 production / 894
package-test / 92 root-test. Phase 18 is not begun and remains separately
unauthorized.

## Related material

- [OpenAI Responses provider adapter](openai-responses-provider-adapter.md)
- [OpenAI Responses inspection and evidence](openai-responses-inspection-and-evidence.md)
- [Phase 17 roadmap](phase-17-roadmap.md)
- [Phase 16 external acceptance](phase-16-external-acceptance.md)
