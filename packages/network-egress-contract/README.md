# Kongono Network-Egress Contract

This optional Experimental package defines inert, provider-neutral values for
describing one governed outbound HTTP operation. It does not authorize or
perform network work.

## Purpose and placement

Applications need a vocabulary that keeps a request, its canonical origin,
application-owned authority evidence, an exact policy reference, finite
budgets, transfer evidence, outcomes, failures, and receipts distinct. This
package supplies that vocabulary below later policy and runtime layers.

Use it when application code must pass one explicitly constructed operation or
terminal result across a typed boundary. Do not use it as a client, resolver,
transport, policy engine, credential source, retry controller, or proof that a
remote party processed a request.

## Contract

- ContractVersion::CURRENT is 1.
- Identities are bounded lowercase ASCII tokens supplied by the caller.
- CanonicalOrigin accepts exact lowercase DNS, IPv4, or bracketless canonical
  IPv6 host evidence and one effective port.
- EgressAuthorityReference and EgressPolicyReference remain inert references.
- EgressBudget declares finite limits but does not meter them.
- Cancellation, lifecycle, capability, protocol, transfer, outcome, failure,
  result, and receipt types carry bounded evidence only.
- EgressOperation retains the exact PSR-7 request reference. Construction and
  access never read, rewind, seek, write, stringify, serialize, hash, buffer,
  or copy its body stream.
- A successful result carries a PSR-7 response and no failure. Every other
  result carries one bounded failure and no response.

## Security and disclosure

Origin and PSR-7 message data are Protected application evidence. Exceptions
expose only closed Safe violation tokens, enum values, counts, bounds, and
positions. Raw URI, host, address, header, cookie, credential, body,
certificate, filesystem path, and foreign exception text are never copied into
Kongono diagnostic text.

Receipts and responses are claims, not external truth, capability grants, or
retry permission. Applications remain responsible for current authorization,
policy, credential, endpoint, transport, and effect decisions.

## Lifecycle, concurrency, and side effects

Values have no hidden mutable state, clock, randomness, environment lookup,
registry, service locator, background work, destructor, shutdown hook, or
autoload side effect. The package performs no DNS, socket, TLS, HTTP, provider,
database, process, filesystem, or network operation. Caller-supplied PSR-7
streams are retained by reference and are not claimed deeply immutable.

The package is Experimental. It makes no Stable, 1.0, provider, transport,
performance, security-certification, PHP 8.6, or PHP 9 promise.

Phase 15D's separate optional runtime consumes these values and narrowly allows
`FailedBeforeEffect` and `CancelledBeforeEffect` receipts to retain a supplied
attempt identity after an opened connection that transferred no request bytes.
`RejectedBeforeAttempt` remains strictly attempt-free, and success and
possible-effect relations remain unchanged. Runtime evidence does not enlarge
this contract into authority or external truth.

See [ADR 0156](../../docs/adr/0156-neutral-versioned-network-egress-contract-foundation.md)
the [architecture guide](../../docs/architecture/network-egress-contract-foundation.md),
and the [Phase 15D runtime](../../docs/architecture/network-egress-runtime.md).
