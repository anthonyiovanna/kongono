# Network-Egress Policy Artifact

> **Current boundary:** Phase 15C and [ADR 0157](../adr/0157-deterministic-network-egress-policy-artifact.md)
> are Accepted. Phase 15D and ADR 0158 add the separate bounded runtime. The
> production graph is 81 packages / 317 local edges / acyclic; PHP inventories
> are 1,514 / 768 / 84. Phases 15F through 15H are Accepted; Phase 15 is closed.

## Purpose and context

The optional Experimental `kongono/network-egress-policy-build` package turns
one explicit application-owned policy into deterministic, inert, offline
evidence. Use it when the application already knows the exact origins,
postures, finite budget, and transport capabilities it intends to require and
needs reproducible bytes before a separately governed runtime exists.

Do not use the artifact as authorization, a resolver result, a connection
permit, a credential, a live endpoint claim, a transport compatibility result,
or proof that any request occurred.

## Constructing a source

Create an `EgressPolicyIdentity` containing one to 128 lowercase ASCII bytes.
The first and last bytes are alphanumeric; interior bytes may also contain
period, underscore, or hyphen. A colon is not valid.

Construct `EgressPolicySource` with:

1. the identity;
2. one to sixty-four `CanonicalOrigin` values, already unique and sorted by
   scheme value, host, and zero-padded five-digit port;
3. one `AddressScope`: `public_only` or `explicit_private_allowed`;
4. one `TransportSecurity`: `https_only` or `explicit_http_allowed`;
5. one `RedirectMode`: `deny` or `same_origin`;
6. one `ProxyMode`: `deny` or `explicit_only`;
7. one `CookieMode`: `deny` or `explicit_only`;
8. one `CredentialMode`: `none` or `operation_scoped`;
9. one finite Phase 15B `EgressBudget`; and
10. one exact Phase 15B `TransportCapabilitySet`.

The constructor detaches the origin list and rejects noncanonical ordering,
duplicates, out-of-bound counts, and incompatible postures. HTTPS-only cannot
contain an HTTP origin; every HTTPS origin requires `tls`; denied redirects
require a zero redirect budget; same-origin redirects require at least one;
and explicit proxy use requires `proxy`. These are source-coherence checks,
not live security or authority decisions.

## Building and artifact shape

Pass the source to `EgressPolicyArtifactBuilder::build()`. The builder first
encodes the exact contract version and policy projection and hashes those bytes
as `source_sha256`. It then encodes the fixed artifact schema, artifact and
contract versions, builder identity, the policy projection, and the source
digest. All member order is fixed by the version-1 contract.

Canonical bytes are compact UTF-8 JSON, contain no BOM, CR, NUL, or float, end
with exactly one LF, and do not exceed 65,536 bytes. `artifactSha256()` is the
lowercase hash of those complete bytes. `sourceSha256()` is the lowercase hash
of the separate source projection. `policyReference()` preserves the Phase 15B
reference contract by returning the identity and uppercase source digest.

## Validating and comparing

`EgressPolicyArtifactValidator::validateStructure($bytes)` validates framing,
JSON shape and order, schema and versions, all typed values and bounds,
canonical origin and capability order, policy compatibility, source digest,
and byte-for-byte canonical rebuilding. It returns detached verified artifact
evidence.

`validateCurrent($bytes, $currentSource)` first performs structural validation,
then rebuilds from only the explicit current source and requires identical
canonical bytes, artifact digest, source digest, and policy identity. A stale
result means only that the supplied source differs. No file, registry,
environment, or external authority is consulted.

## Publishing

Call `EgressPolicyArtifactPublisher::publish($artifact, $directory)` only with
one explicit existing absolute canonical ordinary writable directory. The path
and its ancestors must not cross a symlink, junction, or reparse transition.
The returned target is:

`kongono-network-egress-policy-v1-<artifact-sha256>.json`

The publisher revalidates before I/O, creates an exclusive temporary file in
the same directory, performs complete checked writes and sync, and uses atomic
no-overwrite publication. An existing byte-identical valid target is accepted;
any conflicting or unsafe target fails. Temporary state is removed on every
handled path.

## Security, disclosure, and lifecycle

Canonical artifact bytes are Protected because exact origin names and policy
configuration may reveal application topology. Store and transmit them under
application-controlled protections. Do not present them as diagnostics.
Failure diagnostics use only closed Safe violation and field labels plus
bounded counts, positions, limits, and byte sizes. Raw paths, origins,
identities, digests, credential material, and throwable messages are excluded.

Source and artifact values are immutable and return detached arrays. Builders
and validators retain no source across calls; publishers retain no registry or
selected artifact. Concurrent publications converge only when bytes are
identical and otherwise fail without overwrite.

## Non-capabilities and stability

The package is Experimental. It performs no resolver or DNS work, address
classification, connection, socket, TLS negotiation, credential lookup, proxy
discovery, cookie persistence, transport, redirect following, retry, runtime
authorization, request execution, network effect, command, loader, mutable
registry, provider integration, or compatibility certification. Phase 15D
consumes exact-current artifact evidence only through its separate explicit
runtime boundary. Phase 15F consumes the same exact-current evidence through
its separate application workflow. Phase 15G adds a separate offline bounded
inspection leaf; Phase 15H adds the separately authorized bounded local
external harness and exact checkpoint, closing Phase 15.

See the [package README](../../packages/network-egress-policy-build/README.md),
the [neutral contract foundation](network-egress-contract-foundation.md), the
[bounded runtime](network-egress-runtime.md), and the [Phase 15 roadmap](phase-15-roadmap.md).

## Phase 15E cURL transport and Phase 15F application workflow status

Phase 15E accepts ADR 0159 and implements the optional Experimental
`kongono/network-egress-curl` HTTP/1.1 adapter. It binds the canonical origin
to the single Phase 15D selected address, uses explicit in-memory CA-bundle
bytes with strict TLS verification, and performs one bounded synchronous
transfer without proxy, redirect, cookie, netrc, automatic authentication,
retry, pooling, or PSR-18 control-plane behavior.

Phase 15F accepts ADR 0160 and implements the optional Experimental
`kongono/network-egress-application` exact-current single-profile workflow.
It derives canonical profile and generated-wiring evidence from one supplied
policy source, validates and publishes the wiring without overwrite, loads one
exact contained generated class, checks required transport capabilities before
resolution, and delegates one terminal synchronous operation to a fresh Phase
15D runtime. Authority currentness, resolver, transport, cancellation, and
clock collaborators remain explicit and caller-owned.

Phase 15G accepts ADR 0161 and implements only the optional Experimental
`kongono/network-egress-inspection` upper evidence leaf. It revalidates exact
supplied profile/wiring evidence and can summarize one exactly paired detached
operation/result without loading generated PHP, networking, authority
evaluation, recording, replay, repair, certification, or role execution.

The current topology is 81 packages / 317 local edges / acyclic, with PHP
inventories 1,514 / 768 / 84. Phase 15 is closed. No first-party
resolver, credential provider, provider adapter, live inspection probe,
inspection command, public compatibility certification, Stable/1.0 claim,
production-readiness claim exists.
