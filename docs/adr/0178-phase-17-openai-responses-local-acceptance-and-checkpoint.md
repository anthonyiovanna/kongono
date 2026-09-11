# ADR 0178: Phase 17 OpenAI Responses Local Acceptance and Checkpoint

- Status: Accepted
- Date: 2026-09-11
- Phase: 17H

## Context

Phases 17B through 17G separately established the optional Experimental
OpenAI Responses offline mappings, one-shot adapter, pull-based SSE session,
strict structured result and inert function-call suggestion profiles,
exact-current application workflow, and visibility-safe inspection. Those
boundaries needed one root-owned external-process proof that the accepted
Phase 14-17 APIs compose without turning provider evidence into credential,
network, tool, application-effect, or compatibility authority.

The adapter requires an HTTPS `.invalid` origin. The local proof therefore
uses a child-owned ephemeral certificate and key, trusts only that certificate,
and binds its provider-protocol emulator only to IPv4 loopback. This is local
fixture transport, not public TLS, provider identity, or deployment evidence.

## Decision

Accept the root-test-only harness at
`tests/External/OpenAiResponses/run.php` and the explicit command
`composer acceptance:phase17`. The command is deliberately outside the eight
canonical quality and Hantei checks because it binds loopback sockets and
launches child processes.

Each fresh success child proves exactly four independent operations:

1. mapping version 1 non-streaming text with bounded usage;
2. mapping version 1 consumer-driven pull SSE with contiguous events, terminal
   text, bounded usage, and no reconnect or replay;
3. mapping version 2 strict structured output against one closed schema,
   without coercion or invented fields; and
4. mapping version 2 function-call output reduced to one inert neutral action
   suggestion, with no tool, function, proposal, capability, or effect.

Every operation uses one exact-current Phase 17F profile, the Phase 14
application/runtime composition, one Phase 16 binding and single-use credential
lease, and one governed Phase 15 attempt through the real first-party cURL
HTTP/1.1 transport. The binding uses an RFC-reserved `.invalid` hostname; a
test-owned resolver supplies only `127.0.0.1`, so ambient DNS is not used. The
policy explicitly permits the owned private address and the local HTTP test
posture, while the exact provider request remains HTTPS and trusts only the
ephemeral fixture certificate. Credential acquisition, borrowed use, and
retirement each occur exactly once per successful operation.

The emulator accepts exactly four bounded requests. It verifies method,
origin-form target, Host authority, media headers, exact request bodies, and
one inert bearer fixture, then emits bounded terminal JSON or SSE responses.
Processes and public streams are each bounded to 1,048,576 bytes; requests and
responses are bounded to 65,536 bytes; all readiness, accept, read, write,
child, and total waits are finite. Every disposable path lives under one
unique `kongono-phase17-*` directory directly beneath the system temporary
directory and is removed on success and failure.

The harness additionally proves fail-closed malformed, duplicate, unknown,
mismatched, incomplete, lifecycle, currentness, schema, and evidence cases.
It exercises Phase 17G inspection, Graph evidence, Observation and Security
Audit projections, Chie/Hantei/Oshie-addressed evidence views, and independent
verification without executing a role, recorder, exporter, provider observer,
repair, replay, or certification mechanism. Repeated sequential children, two
concurrent children, a generic failure followed by health, overflow, malformed
output, timeout, repository fingerprint, and residue checks prove isolation.

Public success is exactly one canonical LF-terminated JSON line. Public
failure is only `phase17_acceptance_failed` followed by LF. Dynamic ports,
addresses, paths, raw requests or responses, headers, bodies, provider values,
credential material, foreign failures, and stack traces are not disclosed.

## Checkpoint

Phase 17A through Phase 17H are Accepted and Phase 17 is closed by one ordinary
non-amending local commit with subject
`Complete Kongono Phase 17 OpenAI Responses milestone` and parent
`3690aab42e3759d3f42e711703d4f23e6588fa85`. The resulting commit and tree
identities are completion evidence recorded in the Phase 17H.2 task report,
not architectural input.

The topology remains 89 production packages, 375 local production edges, and
acyclic. PHP inventories are 1,726 production, 894 package-test, and 92
root-test files.

## Security and evidence limits

The harness contacts no public endpoint or non-loopback interface, uses no
real credential or account, and performs no ambient DNS, proxy discovery,
provider observation, function execution, application-directed effect,
recording, or export. Local acceptance does not establish live OpenAI
compatibility, certification, entitlement, current provider behavior,
retention, billing, pricing, compliance, deployment, security, performance,
production readiness, Stable/1.0 status, or release readiness.

## Consequences

Phase 17 has an explicit, deterministic, locally repeatable acceptance command
and one exact checkpoint. Phase 18 is not begun and remains separately
unauthorized.

## Related material

- [Phase 17 local acceptance](../architecture/phase-17-openai-responses-local-acceptance.md)
- [OpenAI Responses provider adapter](../architecture/openai-responses-provider-adapter.md)
- [OpenAI Responses inspection and evidence](../architecture/openai-responses-inspection-and-evidence.md)
- [Phase 17 roadmap](../architecture/phase-17-roadmap.md)
- [ADR 0171](0171-first-party-openai-responses-adapter-boundary.md)
- [ADR 0172](0172-deterministic-openai-responses-offline-protocol-mapping.md)
- [ADR 0173](0173-one-shot-openai-responses-invocation-adapter.md)
- [ADR 0174](0174-pull-based-openai-responses-sse-streaming.md)
- [ADR 0175](0175-strict-structured-output-and-inert-openai-function-call-mapping.md)
- [ADR 0176](0176-exact-current-openai-responses-application-workflow.md)
- [ADR 0177](0177-visibility-safe-openai-responses-inspection-and-bounded-evidence.md)
