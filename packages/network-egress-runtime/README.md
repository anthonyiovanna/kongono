# Kongono Network Egress Runtime

`kongono/network-egress-runtime` is an optional Experimental boundary for one
bounded, synchronous outbound HTTP attempt. It coordinates only explicit
caller-owned authority, resolver, transport, cancellation, and monotonic-clock
collaborators against one exact-current Phase 15C policy artifact.

## Use when

Use this package when an application already owns the external implementations
and needs Kongono to enforce the accepted origin, address posture, transport
capabilities, finite budgets, cancellation checkpoints, transfer evidence, and
deterministic cleanup for one operation.

The runtime resolves once, rejects mixed or forbidden address results, retains
the exact selected address for the transport, rechecks authority immediately
before opening, opens at most one session, and requires a terminal emission
followed by one trailing `null`. Redirects, retries, races, hedges, fallback,
transport selection, and background work are outside this package.

## Do not use when

This package supplies no DNS implementation, socket or TLS stack, PSR-18
client, credential provider, proxy discovery, cookie jar, transport registry,
provider SDK, command, application workflow, or deployment behavior. Values,
policies, receipts, failures, and results are evidence rather than authority or
external truth.

## Calling contract

Create one immutable `EgressRuntimeRequest` with the exact operation, policy
source and canonical artifact bytes, receipt and attempt identities, and all
five caller-owned collaborators. The transport must connect to the exact
selected address while preserving the operation origin for HTTP authority and
TLS identity. Collaborators must return the declared typed evidence; unexpected
foreign throws are preserved behind bounded package exceptions.

Request and response body streams are never read, rewound, sought, stringified,
hashed, copied, or buffered by the coordinator. Header limits are measured from
PSR-7 metadata only. Diagnostics omit origins, addresses, identities, digests,
headers, credentials, bodies, paths, and foreign exception presentation.

## Lifecycle and failure

Each successfully opened session receives exactly one close attempt. Expected
denial, resolution, transfer, cancellation, timeout, budget, and protocol
outcomes return typed terminal results. Cleanup-only and combined failures keep
their components separately inspectable; PHP engine `Error` remains unwrapped.
The runtime rejects reentrancy, clears active state in `finally`, and retains no
completed operation state, allowing later sequential reuse.

## Stability

The marker interface is Public. Collaborator interfaces, values, results,
runtime, and concrete exceptions are Experimental. Diagnostics and internal
guards, meters, classifiers, receipt construction, and lifecycle composition
are Internal. Nothing in this package is Stable.
