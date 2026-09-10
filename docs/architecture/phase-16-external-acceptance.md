# Phase 16 External-Service Acceptance

## Purpose and context

Phase 16H closes the accepted Phase 16 external-service milestone with one
root-owned local external-process harness. It proves that the separately
accepted Phase 16B-G production APIs compose over the accepted Phase 15 egress
platform while preserving the separation between secret-free binding evidence,
selected-address resolution evidence, operation-scoped credential borrowing,
credential permission, and network permission.

Run the proof explicitly with:

```text
composer acceptance:phase16
```

The command is not part of `composer quality` or the eight canonical Hantei
checks because it deliberately opens a bounded local loopback listener and
launches child processes.

## What the harness proves

Each success child constructs one secret-free service binding from an explicit
typed source, builds and validates canonical artifact bytes, generates and
validates ordinary PHP bytes, and independently publishes both content-
addressed representations into one child-owned temporary workspace. Repeated
identical publication is idempotent and conflicting or unsafe paths fail
closed. Generated Phase 16 PHP remains inert: the harness never loads or
executes it.

The binding uses an RFC-reserved `.invalid` hostname and a base path. The
nested policy allows exactly that canonical HTTP origin, explicitly permits a
private selected address and plain HTTP for this local proof, denies redirects,
proxies, and cookies, requires operation-scoped credentials, and requires
HTTP/1.1. A test-owned endpoint resolver returns one validated selected
`127.0.0.1` address. The Phase 16F application then delegates one terminal
operation to the accepted Phase 15 runtime and real first-party cURL adapter.
Selected-address binding makes the `.invalid` origin reach only the owned
loopback listener without ambient DNS.

The responder accepts exactly one connection, reads one bounded origin-form
request, verifies its method, target, Host authority, and single inert
credential header, and writes one fixed bounded HTTP/1.1 response. The harness
checks response status and body, transfer evidence, negotiated HTTP/1.1,
single-attempt lifecycle, and deterministic cleanup. The dynamic port, raw
request, headers, body, response bytes, and temporary path never enter public
output.

Separately, the child calls the real `SystemEndpointResolver` once with numeric
literal `127.0.0.1` and an explicit bounded IPv4-first profile. It verifies
deterministic candidate and selected-member evidence. The numeric lookup does
not perform hostname DNS, open a connection, grant authority, or provide the
answer used by the `.invalid` exchange.

One test-owned inert source supplies one operation-scoped lease. Production
credential runtime and application APIs prove one acquisition, one borrowed
material-use callback, and one retirement attempt. The request factory may use
the borrowed value only synchronously to construct the test request and does
not retain it. Source, consumer, lease-protocol, lifetime, and retirement
failures are exercised as typed terminal evidence and followed by a healthy
fresh success. Retirement and cleanup evidence do not claim physical memory
erasure or reliable zeroization of PHP strings.

The resulting exact profile and detached completed result pass offline Phase
16G inspection. Chie, Hantei, and Oshie views are evidence-addressing values;
the harness does not execute those roles. Independent verification passes,
Graph evidence retains the external-service-to-Graph relationship as not
established, and Observation and Security Audit projection only construct
values without recording or exporting them. All accepted egress outcomes,
including possible-effect uncertainty, remain exact and are not retried,
replayed, repaired, or promoted to external truth.

## Process, isolation, and output contract

The public parent fingerprints Git-visible repository status before and after
execution. It runs repeated sequential success children, a generic failure
child followed by a healthy child, and two concurrent success children. Every
child owns fresh source, binding, policy, profile, listener, port, resolver,
cURL handle, lease, runtime, inspection, and workspace state.

Captured stdout and stderr are bounded independently to 1,048,576 bytes.
Unexpected arguments, malformed output, timeouts, nonzero success exits,
unexpected child streams, residue, or repository drift fail closed. Every
disposable file is created beneath one unique `kongono-phase16-*` directory
directly under the system temporary directory. Traversal, links, junctions or
other reparse points, special files, and paths outside the workspace are
rejected. Processes, sockets, streams, sessions, listeners, leases, and the
complete workspace are closed or removed on success and failure.

Success emits exactly one canonical LF-terminated line shaped as:

```text
{"status":"ok","profile":"kongono-phase16-external-service","case_count":N,"cleanup":"complete"}
```

`N` is the command's positive observed deterministic case count. Failure emits only
`phase16_acceptance_failed` followed by LF. Public streams omit origins,
hostnames, ports, selected addresses, request and response data, credential
source/reference/material, identities, paths, generated bytes, exception
arguments, stack traces, environment values, and foreign messages.

## Use and non-use

Use this command to reproduce the exact local Phase 16 conformance evidence on
a supported checkout with `ext-curl`, `ext-sockets`, child-process support,
Git, and local IPv4 loopback bind/connect available.

Do not use it as a provider compatibility test, public endpoint probe,
credential validation, DNS validation, TLS deployment test, security
certification, compliance result, load test, release gate, production monitor,
or proof that a remote system acted. It contacts no public Internet endpoint,
production service, ambient hostname resolver, proxy, certificate service,
provider account, or real credential.

## Accepted closure

Phase 16A through Phase 16H are Accepted. Phase 16 is closed by the local
checkpoint whose subject is
`Complete Kongono Phase 16 external service milestone` and whose parent is
`b4cb57ffa01eea130a693daee0be605ae7f0dda5`. The completion report records the
resulting commit and tree identities.

The topology remains 87 production packages, 355 local production edges, and
acyclic. PHP inventories are 1,647 production, 851 package-test, and 88
root-test files. No real credential, production credential source, provider
adapter or SDK, public endpoint, ambient production DNS, Stable/1.0 promotion,
release, deployment, compatibility, or production-readiness claim is added.

Phase 17 has not begun and requires separate authenticated architect authority.

## Related material

- [ADR 0170](../adr/0170-phase-16-external-service-acceptance-and-checkpoint.md)
- [Phase 16 roadmap](phase-16-roadmap.md)
- [External-service bindings and secret leases](external-service-binding-and-secret-leases.md)
- [External-service inspection and evidence](external-service-inspection-and-evidence.md)
- [Outbound HTTP and governed network egress](outbound-http-and-network-egress.md)
- [Phase 15 external acceptance](phase-15-external-acceptance.md)
