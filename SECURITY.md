# Security Policy

## Phase 22A-E Application Events security boundary

Phase 22A accepts only [documentation architecture](docs/architecture/application-events.md)
under [ADR 0215](docs/adr/0215-optional-application-events-and-explicit-synchronous-dispatch.md)
and the [Phase 22 roadmap](docs/architecture/phase-22-roadmap.md). Phase 22B
implements the inert optional contract under
[ADR 0216](docs/adr/0216-neutral-application-event-contract-and-bounded-registration.md):
registration checks one already-loaded canonical concrete event class, and
the set validates at most 64 ordered entries, without autoload, event
construction or handler invocation. Its four fixed diagnostics expose only a
Safe violation token. Native PHP errors and programmatic traces remain
outside that presentation guarantee. The separately optional Experimental
dispatcher under
[ADR 0217](docs/adr/0217-optional-synchronous-application-event-dispatcher-and-instance-lifecycle.md)
receives an explicitly typed application event and complete
caller-supplied registrations; malformed or duplicate bindings fail before
handler effects. It preserves the original first handler Throwable and
truthful earlier partial effects, rejects same-instance reentrancy before
matching with fixed Safe diagnostic `KGN-EVENT-RUNTIME-2221`, and releases its
guard in `finally`. Each effectful handler owns business authorization,
privacy, credentials and safe failure presentation. Framework diagnostics
must omit raw payloads, handler results and arbitrary throwable messages.
Events are separate from telemetry, audit, Graph facts, commands and queues;
no hidden network, persistence, retry, worker or automatic publication is
accepted. Phases 22A-C are architect-accepted within their recorded limits.
The [event-minimal example](skeletons/event-minimal/README.md) checks current
application authorization before each receipt and ledger effect; its fixed
sample policy is not production authorization. Native errors, handler
Throwables and traces require application-owned safe presentation. Phase 22D
is architect-accepted within its recorded local limits. Phase 22E found that
a missing demo autoloader emitted a native warning before the fixed failure
token; a temporary E_WARNING handler now converts that trusted bootstrap
warning to a fixed exception and is restored in `finally`. Local recovery and
assembled-path validation are architect-accepted within their recorded limits.
This does not sanitize arbitrary native warnings or handler traces. Phase 22F's
offline separate-process and own-autoload consumer proof passed locally; broad
milestone validation, advisory audit, architect milestone acceptance and the
exact checkpoint remain pending. See
[event authoring](docs/engineering/event-authoring.md).

## Phase 21A-H Object Storage security boundary

Phase 21A accepts documentation architecture; Phase 21B accepts only the inert
Experimental `kongono/storage-contract` vocabulary and creates no storage
runtime or authority. Phase 21C accepts only the Experimental
`kongono/storage-build` deterministic evidence leaf. Its profiles, identities,
canonical artifacts, validation results, and contained publication results are
not provider, credential, authorization, runtime, or object-operation
authority. Declared capabilities are support evidence, not authorization.
Phase 21D accepts only a neutral adapter protocol and one explicitly rooted,
contained local-filesystem implementation. Its descriptor, guarantees,
limitations, path mapping, record evidence, versions, outcomes, and sources do
not grant application authorization, provider credentials, exact-current
runtime authority, or public delivery.
Phase 21E accepts the separately optional Experimental `kongono/storage-runtime`
leaf. It binds only one exact current profile and one directly supplied
compatible neutral adapter, validates requests before effect and adapter
evidence afterward, rejects same-instance reentrancy, preserves uncertain
mutation without retry, and retires operation-owned resources deterministically.
It grants no application authorization, credential, provider, network, public
delivery, or local-filesystem authority and does not require the local adapter.
Phase 21F adds explicit guarded composition through an application-supplied
authorizer. Each operation is checked once before lower-runtime delegation;
denial and collaborator failure are distinct and no decision is cached. The
profile commands never perform object operations. The example authorizer is
not authentication or a production policy.
Phase 21G accepts optional offline `kongono/storage-inspection` evidence.
It independently checks supplied profile
and artifact coherence and accepts only detached bounded outcome counts. It
does not inspect an object, contact a provider, validate credentials, execute
authorization, observe a runtime, or certify storage security. Inspection
digests are Protected; Safe projections omit raw identities and contents.
See [ADR 0213](docs/adr/0213-visibility-safe-storage-inspection-and-developer-intelligence.md)
and the [storage inspection guide](docs/architecture/storage-inspection-and-intelligence.md).
Phase 21H adds [bounded root-owned local acceptance](docs/architecture/phase-21-external-storage-acceptance.md)
without granting provider, credential, network, or application authority.
The time-bound audit, architect acceptance, and exact checkpoint completed as
separate Phase 21 gates; local proof retains its recorded limits.

Contract requests contain no provider locator, path, URL, credential,
authorization result, retry policy, or runtime object. Runtime storage access
must be capability-scoped and explicitly composed. Logical object identifiers, namespaces, provider
locators, roots, buckets, endpoints, content digests, metadata,
version/concurrency tokens, credentials, authorization decisions, and
operation evidence are distinct. Possessing any string or identifier grants no
read, create, replace, delete, list, or inspect permission.

The accepted local adapter operates beneath one exact caller-supplied root and
proves containment on read, mutation, listing, and cleanup paths. Traversal, absolute
escape, ambiguous separators, device paths, reserved names, alternate data
streams, link/reparse escape, case-folding ambiguity, and mutation of path
components are threats, not edge conveniences. Current working directory,
home, and temporary directories are never implicit authority.

Provider credentials and tenant mapping remain application-owned and separate
from object identifiers and network-egress permission. Object contents, raw
identifiers, private paths, provider locators, URLs, credentials, signing
material, customer metadata, tenant identifiers, and provider response bodies
must not enter Safe diagnostics, logs, artifacts, inspection, or developer
intelligence merely because an adapter can observe them.

Silent overwrite and hidden retry are prohibited. Create, conditional replace,
version-matched mutation, delete, conflict, known completion, known failure,
and uncertain outcome remain distinct. An uncertain mutation is an integrity
event that may require application-owned reconciliation; it must not be
reported as success or retried implicitly. Checksums, lengths, versions,
tokens, and acknowledgements prove no stronger durability, atomicity,
consistency, origin, or authorization than their provenance supports.

Streaming is bounded and lifecycle-owned. Cancellation, close, abort, cleanup,
temporary state, handles, sockets, buffers, credentials, and metadata must be
retired deterministically on every path without leaking into a later
persistent-worker operation. Phase 21B implements no operational behavior. See
the [Storage Platform guide](docs/architecture/storage-platform.md),
[deterministic storage-profile build guide](docs/architecture/deterministic-storage-profile-build.md),
[storage-authoring guide](docs/engineering/storage-authoring.md),
[ADR 0208](docs/adr/0208-neutral-storage-contract-and-bounded-evidence-vocabulary.md),
[ADR 0209](docs/adr/0209-deterministic-storage-profile-artifact-and-contained-publication.md),
[ADR 0210](docs/adr/0210-neutral-storage-adapter-and-contained-local-filesystem-implementation.md),
[ADR 0211](docs/adr/0211-exact-current-storage-runtime-and-operation-scoped-lifecycle.md),
[ADR 0212](docs/adr/0212-supported-storage-application-workflow-and-explicit-authorization.md),
[contained local adapter guide](docs/architecture/contained-local-storage-adapter.md),
[exact-current storage runtime guide](docs/architecture/exact-current-storage-runtime.md), and
[storage application workflow guide](docs/architecture/storage-application-workflow.md), and
[Phase 21 roadmap](docs/architecture/phase-21-roadmap.md).

## Phase 20A-H Derived-Data Cache security boundary

Phase 20A accepts documentation architecture. Phase 20B accepts only the
effect-free Experimental `kongono/cache-contract` values and diagnostics.
Phase 20C accepts the Experimental `kongono/cache-build` build-time evidence
leaf. Profiles and artifacts grant no cache, filesystem, credential, network,
provider, runtime, or application-effect authority; publication is limited to
one explicit caller-selected root and exact content-addressed artifact. Phase
20D accepts the neutral adapter protocol and one bounded memory implementation.
Its detached results, exact-version conditional mutations, fixed capacity, and
atomic candidate-state swap grant no authority or production guarantee.
Cache entries and outcomes
are derived, disposable evidence and grant no identity, authentication,
authorization, capability, credential, policy, storage, network, process,
Graph, or application-effect authority. A cache hit is not source currentness,
permission, or proof that an external effect occurred.

Credentials, secrets, bearer tokens, capability grants, authorization
decisions, sessions, CSRF tokens, unredacted sensitive data, migration state,
durable events, audit records, receipts, locks, leases, queues, rate-limit
state, counters, and authoritative records must not be cached. Applications
remain secure and correct after miss, expiry, eviction, rejection, backend
loss, or complete flush.

Future namespaces, keys, values, metadata, lifetimes, operation counts,
provider results, durations, and diagnostics require finite bounds before an
effect. Diagnostics may include bounded Safe counts, closed reasons, and
Protected digests, but must not expose raw keys, cached or encoded values,
credentials, tokens, backend addresses, connection data, application paths,
arbitrary provider output, or unbounded payloads.

Adapters receive no ambient credentials, environment, filesystem,
network, DNS, process, discovery, or configuration authority. Explicit time,
typed outcomes, atomic visibility, version-bound conditional mutation,
uncertain-effect preservation, detached results, and deterministic cleanup are
security requirements. A facade, global registry, magic serialization, hidden
clock, stale fallback, implicit retry, read-through callback, or cache-based
coordination primitive is prohibited.

Phases 20A-H are Accepted and Phase 20 is closed. Phase 20E performs only exact-current bounded operations through one directly supplied adapter. Phase 20F reads one explicit contained profile source and composes one runtime only after exact profile, artifact, codec, and adapter validation. Phase 20G inspects only supplied typed evidence in memory and emits bounded Safe aggregates and Protected digests; it never accesses keys, values, a backend, runtime state, a clock, or a cache operation. Phase 20H adds only local root-test acceptance and the exact checkpoint. No provider compatibility, production availability, or security certification exists. See
[ADR 0197](docs/adr/0197-explicit-derived-data-cache-and-non-authority-boundary.md),
[ADR 0199](docs/adr/0199-neutral-cache-contract-and-bounded-evidence-vocabulary.md),
[ADR 0198](docs/adr/0198-deterministic-cache-freshness-atomicity-and-lifecycle.md),
[ADR 0201](docs/adr/0201-neutral-cache-adapter-and-bounded-memory-implementation.md),
[ADR 0204](docs/adr/0204-visibility-safe-cache-inspection-and-developer-intelligence.md),
 [ADR 0205](docs/adr/0205-phase-20-external-cache-acceptance-and-checkpoint.md),
and the [Cache Platform guide](docs/architecture/cache-platform.md).

## Phase 19A-H Asset Platform security boundary

Phase 19A accepts architecture, Phase 19B accepts the neutral asset contract,
and Phase 19C accepts only the optional deterministic native asset-build leaf.
Phase 19D accepts only the optional neutral adapter boundary and separately
optional fixed-profile Vite adapter. Phase 19E accepts only the optional
stateless resolution leaf: complete build evidence is revalidated, expected
artifact identity must match before lookup, and public URL/cache/integrity/
preload policy is explicit and application-owned. Phase 19F accepts only the
optional application workflow over explicit canonical paths and stable reads,
the closed command edge, and application-owned View value construction. It
adds no ambient manifest, service locator, hidden build, tag emission, HTTP
delivery, or View-package dependency. Phase 19G accepts only an offline
inspection leaf over one supplied current result. Its Safe/Protected evidence
omits logical identities, paths, URLs, bytes, individual content and integrity
values, process data, environment, messages, and traces; it performs no role,
Graph, observation, audit, recorder, exporter, repair, or certification effect.
Phase 19H accepts only the root-owned local external proof and exact checkpoint
under [ADR 0196](docs/adr/0196-phase-19-external-asset-acceptance-and-checkpoint.md).
The harness uses explicit local inputs and bounded children, performs no public
network or ambient DNS, and treats its Vite protocol emulator as test evidence,
not real-tool certification. Phase 19A-H are Accepted and Phase 19 is closed.
Phase 19C discovers no
source, parses or transforms no content, invokes no subprocess or network,
selects no URL or HTTP policy, and adds no Vite or frontend behavior.

Future source declarations, source bytes, package contents, build executables,
plugins, tool manifests, generated output, content-type claims, URLs, source
maps, and integrity claims are hostile at their input boundaries. Validation
must reject traversal, collisions, ambiguity, outside-root state, symlinks,
junctions, reparse points, alternate streams, remote inputs, stale or mixed
evidence, unsupported values, tampering, and exceeded bounds before publication
or resolution.

Build tools are untrusted producers until complete output is validated against
the neutral contract. Browser-executable JavaScript, CSS, WebAssembly, markup,
and related output is trusted application code or content, not a Kongono
sandbox or security certification. Content addressing and Subresource Integrity
metadata do not grant authorization and do not replace CSP, origin policy,
transport security, content-type enforcement, or supply-chain review.

Application and deployment layers own origin and public base URL, CDN and
storage selection, cache policy, CSP and nonce creation, security headers,
authentication, authorization, cookies, sessions, credentials, network access,
and byte delivery. Diagnostics must omit source and output bytes, private paths,
raw tool manifests, environment values, secrets, credentials, nonces, headers,
cookies, and provider payloads. See [ADR 0188](docs/adr/0188-optional-asset-platform-and-deterministic-build-boundary.md),
[ADR 0189](docs/adr/0189-exact-current-asset-resolution-and-browser-delivery-policy.md),
the [Asset Platform guide](docs/architecture/asset-platform.md), and the
[exact-current resolution guide](docs/architecture/exact-current-asset-resolution.md),
the [asset-application workflow](docs/architecture/asset-application-workflow.md),
and the [external acceptance](docs/architecture/phase-19-external-asset-acceptance.md).

Phase 19E returns metadata only. It never returns source/output bytes or
private paths, emits no response/header/markup, creates no CSP, nonce, or
crossorigin decision, and performs no filesystem, network, storage,
authentication, authorization, credential, or deployment effect. Diagnostics
are bounded and nondisclosing; retained previous throwables are not claimed to
have sanitized traces.

Phase 19C canonical artifacts omit source/output bytes, protected source paths,
absolute paths, URLs, ambient policy, credentials, environment, and time. The
publisher writes only one validated contained operation-owned temporary tree,
accepts existing output only when its complete ordinary inventory and bytes
are exact, never clobbers or repairs, and preserves primary plus cleanup truth.
See [ADR 0191](docs/adr/0191-deterministic-native-asset-graph-and-build-artifact.md).

## Phase 18A-H presentation and output-safety boundary

Phase 18A accepts the security doctrine in [ADR 0179](docs/adr/0179-server-rendered-presentation-and-compiled-view-boundary.md),
[ADR 0180](docs/adr/0180-context-aware-output-safety-and-presentation-lifecycle.md),
and the [presentation guide](docs/architecture/server-rendered-presentation.md)
as documentation. Phase 18B accepts [ADR 0181](docs/adr/0181-neutral-view-contract-and-closed-presentation-values.md)
and adds closed bounded input/schema and render-evidence values with
nondisclosing diagnostics. Phase 18C accepts [ADR 0182](docs/adr/0182-deterministic-structured-view-source-and-compiled-artifact.md)
and the closed duplicate-aware structured source compiler. It resolves all
references against required schema fields, encodes static HTML text and quoted
attributes, and rejects unsupported contexts before emitting inert ordinary PHP.
Phase 18D accepts [ADR 0183](docs/adr/0183-exact-current-compiled-view-runtime-and-bounded-rendering.md)
and the [compiled-view runtime](docs/architecture/compiled-view-runtime.md). It
authenticates one explicit fixed two-file exact-current artifact and renders
synchronously with operation-local bounded output and contextual escaping. No
trusted-markup type or browser-security policy exists. Phase 18E accepts
[ADR 0184](docs/adr/0184-explicit-completed-view-to-psr-7-response-adaptation.md)
and the explicit [compiled-view HTTP adapter](docs/architecture/compiled-view-http.md),
which derives only content length and leaves status, reason, content type,
caller headers, cookies, caching, redirects, and browser-security policy to the application.
Phase 18F accepts [ADR 0185](docs/adr/0185-supported-compiled-view-application-workflow.md)
and the [compiled-view application workflow](docs/architecture/compiled-view-application-workflow.md).
It authenticates one explicit contained source and exact-current artifact,
returns lower render failure unchanged, and leaves route, 404, browser-security,
and failure-presentation policy application-owned.
Phase 18G accepts [ADR 0186](docs/adr/0186-visibility-safe-compiled-view-inspection-and-developer-intelligence.md)
and the [compiled-view inspection guide](docs/architecture/compiled-view-inspection-and-intelligence.md).
It structurally validates supplied artifact bytes in memory without executing
them and returns only closed Safe/Protected evidence. Paths, templates,
presentation values, artifact bytes, rendered bytes, HTTP material, foreign
failures, and traces remain undisclosed. The evidence establishes no live
filesystem, deployment, Graph, authorization, security, or production truth.
Phase 18H accepts the root-owned local proof under
[ADR 0187](docs/adr/0187-phase-18-server-rendered-presentation-acceptance-and-checkpoint.md).
It adds no public network, ambient DNS, live server, browser, provider,
database, credential, deployment, certification, or production authority.

Plain strings are data, never markup. The Phase 18C compiler knows output
context at build time; unsupported or ambiguous context fails closed. The
initial direction is HTML text and quoted HTML attributes. URL, JavaScript,
CSS, raw markup, and event-handler contexts remain denied until separately
specified and independently tested. Runtime guessing, regex sanitization,
post-render cleaning, raw flags, and one generic `escape()` promise are not
accepted security mechanisms.

Templates receive closed bounded presentation values, not services,
repositories, requests, sessions, security contexts, credentials, capability
ports, or arbitrary objects. Template names, source locations, values,
fragments, rendered bytes, private paths, stacks, secrets, session/CSRF
material, and provider payloads are Protected or denied by default. Existing
trusted diagnostic activation may disclose only bounded authorized context;
production presentation stays generic.

Rendering does not create identity, authentication, authorization, capability,
policy, CSP, nonce, asset-integrity, URL-policy, session, CSRF, header, or
application-effect authority. Generated PHP remains trusted in-process code,
not a sandbox; the deployment filesystem is also trusted. No source is parsed
or compiled during rendering. Phase 18D makes no browser-security, accessibility, compliance,
production-readiness, or certification claim.

Phase 14B implements only the inert neutral values in
[ADR 0148](docs/adr/0148-neutral-versioned-model-invocation-contract-foundation.md)
and the [contract guide](docs/architecture/model-invocation-contract-foundation.md).
Provider capabilities, profiles, prompts, responses,
streaming events, tool suggestions, normalized results, usage/cost evidence,
and receipts are untrusted evidence, never authentication, authorization,
capability grants, or executable authority. Applications retain exact provider
choice, adapter and transport supply, credential policy, budgets, result
eligibility, and all Phase 13 authority decisions. See
[ADR 0147](docs/adr/0147-provider-neutral-model-invocation-and-adapter-boundary.md).
The package contains no credential, provider SDK, endpoint, network, adapter,
invocation plan, runtime enforcement, serialization, Agent bridge, or recording.

## Project status

Kongono is pre-alpha and contains runnable Experimental Kernel lifecycle, Application Graph structural, and explicit dependency-resolution functionality. There are no Stable APIs or supported production releases.

Phase 2A through 2H are complete at checkpoint
`647053d4bddc4c76090bca9bf3d693faa5880c52`. Exactly fourteen production
packages and a root-owned, test-owned fixture prove the bounded framework path.
The fixture is not a production application, SAPI adapter, server, listener,
emitter, deployment surface, trust mechanism, or security-hardening claim.
Phase 3B selects PHP `cgi-fcgi` in the FastCGI `RESPONDER` role under CGI/1.1
as the first production host profile. Phase 3C and ADR 0053 implement bounded
`kongono/php-cgi` inbound conversion from explicit caller values. Phase 3D and
ADR 0054 implement bounded full-body staging and exact response emission. Phase
3E and ADR 0055 implement a literal typed operator profile, fail-closed fixed
process validation, and independent current-delivery permission evaluation.
Public and production selection maps bootstrap permission to `Denied`; loopback
development still requires fresh direct, non-forwarded delivery evidence.
Closed diagnostics retain no rejected raw value. Streaming is unsupported.
Post-commit failure is uncertain, throws, and is never rolled back or retried.
The concrete delivery, thin entry, front-controller lifecycle, and deployment
constraints are implemented. Phase 3H adds root-owned external acceptance
through an operator-supplied loopback listener without adding a production
listener, client, process manager, or deployment artifact.

## Reporting a vulnerability

Kongono does not yet have an authorized private vulnerability-reporting address or disclosure workflow. Do not publish suspected sensitive vulnerability details in a public issue. Request a private reporting channel from the project maintainers or architect before sharing reproduction details.

The reporting address, response targets, disclosure timeline, supported-version policy, and security advisory process remain open governance decisions.

## Architectural security expectations

Planned framework work must make authorization, trust, identity, delegation, and security metadata deterministic and inspectable. AI output may assist analysis but must never be authoritative for authorization or security policy.

Extensions must not automatically receive unrestricted access to databases, filesystems, secrets, networks, shells, or processes. The implemented Extension Host governs only its accepted bounded in-process lifecycle; it is not a sandbox and grants no ambient resource authority. Future permissions, storage, network, secret, UI, update, removal, or health mechanisms require separate authority and must not be inferred from this policy.

Persistent runtimes require explicit lifecycle and operation scopes so that identity, authorization, tenant, request, credential, prompt, provider-response, and sensitive state cannot leak between operations. Phase 14A selects no runtime, but fixes explicit bounded lifecycle, cancellation, timeout, attempt, streaming, and cleanup direction for later separately authorized work.

Phase 0F dependency definitions may invoke registered autoloaders, and resolution executes consumer constructors. Applications must treat supplied definitions and implementations as reviewed executable input. The resolver retains no constructed service instance, exposes no ambient current resolver, and provides no authorization boundary, secret injection, or special Kernel lifecycle behavior.

## Implemented Phase 2 web security boundary

These requirements govern Phase 2 work. Phase 2C implements strict header, URI,
stream, upload, server-request, and bounded diagnostic behavior in the
first-party HTTP message package. Phase 2D implements a separate routing package
that validates bounded method/path inputs and matches exact encoded bytes without
decoding or normalization. Captured path parameters remain untrusted; a match is
not authentication, authorization, or permission. Routing diagnostics contain
only bounded Safe structure, never raw route or request values. Phase 2E adds an
explicit bounded PSR-15 middleware pipeline and terminal routing handler.
Selected matches are copied to the reserved `RouteMatch::class` request
attribute, but remain untrusted and non-authorizing; unmatched output is
delegated rather than hard-coded. Phase 2F adds the transport-free Web
Application boundary, which validates composition before Kernel boot, owns one
exact local Kernel scope per application call, retires after uncertain lifecycle
failure, and retains no request-specific state. Its two fixed diagnostics have
empty schemas and expose no failure or request data. Phase 2G adds the Web
Runtime delivery port, generic failure surface, trusted-diagnostic delivery, and
fail-closed outer adapter. Phase 2H's root-owned fixture proves that path
externally. Phase 3F now supplies the selected PHP CGI delivery and emitter
composition. Kongono still has no supplied error middleware, first-party
server/listener, supported deployment profile, or production application.

The immutable matcher retains no request, URI, method, path, parameters, match,
principal, tenant, or permission after a call. It reads no headers, body, cookies,
query parameters, server parameters, uploaded files, attributes, request target,
SAPI globals, environment, filesystem, or network. Phase 2G adds a separate
request-independent delivery-evidence port and fail-closed outer adapter; the
first runnable security proof remains Phase 2H black-box acceptance.

Future header storage must reject carriage return, line feed, NUL, invalid
field-name characters, and ambiguous line folding rather than repair or
silently normalize them. Case-insensitive lookup must preserve the originally
supplied field-name case and repeated-value order. URI syntax and
canonicalization follow PSR-7 and RFC 3986, but syntactic validity establishes
no trust and performs no DNS, filesystem, network, or base-URI guessing.

PSR-7 messages use copy-on-change semantics, not deep immutability. A body
stream remains stateful, and message copies may share it; cursor movement,
resource mutation, detach, and close behavior can therefore be observable
through more than one message value. Message, stream, upload, server-parameter,
cookie, query, parsed-body, and attribute data must remain explicit
operation-lifetime values rather than ambient or previous-operation state.

The HTTP message package stores already-supplied representations. It must not
read SAPI globals, parse bodies or uploads, select limits, trust proxies,
authenticate, authorize, emit headers, or deliver responses. PHP CGI now owns
the selected bounded native capture, conversion, and emission outside that
package; listener/process integration, deployment hardening, and external
acceptance remain unimplemented. Diagnostic context must exclude
raw headers, cookies, query strings, bodies, credentials, tokens, uploaded
content, local file contents, and unbounded URI text.

Every inbound request component is untrusted until validated for its specific
use. Future parsing must explicitly define normalization, size limits,
duplicate handling, invalid syntax, and rejection behavior. Header and body
handling must prevent injection, response splitting, unbounded buffering,
confused canonicalization, and accidental secret disclosure. Syntactic
validity alone must never make request data eligible for a diagnostic Safe
field.

Web Runtime now owns each neutral delivery explicitly through
`WebDeliveryInterface`: it obtains one request and delivery permission, selects
one fixed generic or eligible trusted response, attempts one send, and attempts
one close. Web Application alone creates and closes one Kernel operation scope
around the accepted application call. The implemented PHP CGI
`CgiWebDelivery` captures the closed native projection, calls the existing
converter and emitter, and owns explicit one-delivery cleanup. Request,
response, route match, middleware state, permissions,
exceptions, principals, tenants, and application state must not be retained in
global, static, singleton, container-current, thread-local, fiber-local, or
previous-operation state. The Phase 2 fixture and Phase 3F local delivery tests
prove sequential object/call-frame isolation. Phase 3H additionally proves that
isolation through the external selected host while reusing one listener process.

## Completed Phase 3 production integration security boundary

[ADR 0051](docs/adr/0051-phase-3-production-http-integration-milestone.md)
accepts Phase 3 as the path to one supported synchronous production HTTP
integration profile. [ADR 0052](docs/adr/0052-php-cgi-fastcgi-responder-profile.md)
and the [PHP CGI profile](docs/architecture/php-cgi-fastcgi-profile.md) select
the exact host, closed input sources, byte/count limits, invalid-input outcome,
response preflight and commitment boundary, cleanup ownership, and external
acceptance surface.

Phase 3C accepts only a caller-supplied closed server projection and caller-owned
readable input resource. It never reads `$_SERVER`, `$_ENV`, `$_REQUEST`,
PHP-parsed query/cookie/form/upload globals, `getenv()`, `php://input`, forwarded
trust, or whole global dumps.
Malformed, ambiguous, oversized, unsupported, or unreadable input fails before
Web Application and maps through the Phase 3F delivery and Web Runtime to its
fixed generic 500 when `request()` performs conversion; no semantic
4xx/413/415/431 mapping exists. Phase 3C implements bounded inbound conversion.
Phase 3D implements response emission after complete bounded body staging.
Phase 3E implements exact literal operator-profile syntax, fixed native
prerequisite validation, and independent current-delivery permission evaluation.

Trusted bootstrap and current-delivery permissions remain independent,
non-client-controlled inputs. `HTTP_FORWARDED`, `HTTP_X_FORWARDED`, or any
`HTTP_X_FORWARDED_*` key forces delivery denial in the first profile. Only an explicitly enabled development
bootstrap plus provable direct canonical loopback `REMOTE_ADDR` (`127.0.0.1` or
`::1`) in a non-proxied loopback-only deployment may allow the current
delivery; all missing, invalid, ambiguous, proxied, failed, non-loopback, or
uncertain evidence is denied. Production guidance keeps bootstrap denied.
Denial occurs before protected source access and uses the fixed generic path
without trusted fallback. Production integration preserves Web Runtime, Web
Application, and Kernel ownership, performs exact-once cleanup, retires
uncertain state, and proves repeated-operation isolation where `php-cgi`
process reuse is available.

Phase 3 does not implement or guarantee proxy/TLS termination, host validation,
authentication, authorization, sessions, CSRF, rate limiting, incident response,
logging, telemetry, recovery, general operations, support for every runtime, or
production-hardening certification.

Client-controlled query, path, header, cookie, form, body, session, token, or
other input cannot enable trusted diagnostics or upgrade either permission.
The concrete delivery establishes current-delivery evidence;
Web Runtime evaluates the resulting permission before diagnostic source access.
Trusted output requires both independent affirmative typed
permissions; denial occurs before source access and never falls back to trusted
output. Failure in authorization, evidence, activation, projection, rendering,
transport, or cleanup must not recursively enable trusted output. Normal
application failure follows a separately modeled generic non-trusted path by
default. Exact request, response, status, header, body, evidence, production
failure, cleanup, and simultaneous-failure mechanisms remain open.

## Diagnostic disclosure

Phase 0M exposes fixed package-catalog identifiers, titles, and remediation
through the three package exception markers. Those strings contain no caller
input. Existing exception messages may contain caller-supplied Graph keys, kind
labels, service contracts, implementation names, or counts and remain protected
detail. Runtime access to catalog metadata does not authorize disclosure of an
exception message, cause, stack, file, or source to an untrusted production
surface.

Future development diagnostics may include precise causes, bounded source or
value context, and exception chains only after escaping, truncation, and
redaction. Credentials, tokens, cookies, authorization headers, session values,
encryption material, private keys, full environment dumps, arbitrary request
bodies, and unclassified provider detail must never be emitted. Graph keys,
service names, routes, SQL values, file paths, and provider messages are not
presumed safe for an untrusted audience.

Future production output must remain intentionally generic and may expose only
an opaque incident reference when protected recording exists. It must not expose
exception classes or messages, stack traces, source excerpts, absolute paths,
environment or configuration values, internal topology, or unapproved
diagnostic detail. Stable diagnostic identifiers and opaque incident references
are distinct.

Phase 1A accepted a two-gate fail-closed architecture for trusted diagnostic
presentation. At that phase, a future server-controlled composition boundary
had to grant **trusted bootstrap permission** as an already validated immutable
decision for one adapter instance before it served work, and the future outer
adapter had to grant **adapter-controlled delivery permission** independently
for the current delivery boundary. Both permissions must be
affirmative before presentation access, projection, copying Safe or Protected
fields, rendering, or delivery. One permission never implies the other.

Phase 1C implements only the first typed input as the Experimental unbacked
`Kongono\DiagnosticActivation\TrustedDeveloperBootstrapPermission` enum.
`Denied` reports that bootstrap permission does not allow the trusted path;
`Allowed` reports only that the already-validated bootstrap decision permits it
to be considered. Directly selecting `Allowed` in PHP does not prove trust or
delivery authorization. The enum reads no raw configuration and performs no
presentation access or I/O.

Phase 1D implements the second neutral typed input as the Experimental unbacked
`Kongono\DiagnosticActivation\AdapterControlledDeliveryPermission` enum.
`Denied` prohibits the trusted path; `Allowed` records only that an outer
adapter has already made an affirmative decision for one current delivery.
The adapter remains solely responsible for evidence, evaluation, and authority.
Unknown, unavailable, failed, uncertain, ambiguous, expired, or unverified
evidence must never become `Allowed`, and an affirmative decision must not be
reused across deliveries. Directly selecting the PHP case proves no delivery
identity, freshness, authentication, listener trust, or authorization.

Absence is disabled. Invalid, ambiguous, or failed raw bootstrap configuration
must be rejected before serving rather than coerced or defaulted to allowed.
Raw truthy strings and application-environment labels are not permission, and
Diagnostic Presentation reads no raw configuration. Changing configuration
cannot mutate a live adapter's decision; a changed decision requires explicit
restart or construction of a new adapter instance.

Delivery denial or uncertainty now takes Web Runtime's fixed generic non-trusted path without
accessing presentation data, projecting, rendering, or optimistically producing
and discarding trusted output. Query parameters, routes, paths, headers,
cookies, forms, bodies, sessions, user-supplied tokens, and every other client-
controlled value cannot enable delivery or upgrade denied bootstrap permission.
Database or provider values, application callbacks, extensions, plugins, agents,
prior requests, operations, tenants, users, fibers, and worker state cannot
self-authorize or leak permission to another delivery.

The implemented projector and renderer remain low-level pure Experimental APIs
that cannot determine whether their caller is trusted. Direct PHP callability,
API status, a package-local token, output escaping, or successful rendering is
not authorization. Phases 1C and 1D implement the two decision types, and Phase
1H implements only their neutral eligibility-first presentation sequencing.
Phase 2G implements Web Runtime's fail-closed adapter, response selection, send,
and close sequence from explicitly supplied decisions. Phase 3E implements PHP
CGI's literal operator selection and exact delivery-evidence evaluation, and
Phase 3F binds them to concrete capture, conversion, emission, and cleanup. A
general configuration system, production listener/process integration, deployed
hardening, and a served deployment remain unimplemented. Phase 3H external
acceptance is test evidence, not a deployed security boundary.
The permission names are **trusted bootstrap permission** and
**adapter-controlled delivery permission**; PHP CGI establishes their selected
operator and host provenance without client input while keeping them
independent.

Phase 1E implements the pure `TrustedDeveloperPresentationEligibility`
conjunction: only `Allowed` plus `Allowed` returns true. The value is scoped to
one current delivery, validates no external evidence, and is neither an
authorization proof nor enforcement. Its result must not be retained, cached,
serialized, transmitted, logged, persisted, or reused as a capability.

Phase 1H implements the transport-neutral Diagnostic Handler boundary. Its
Experimental `renderIfEligible(...)` operation evaluates the supplied
eligibility object first. False returns `null` immediately without any source
access; true alone permits the handler to invoke the existing projector and
renderer and return their exact HTML result unchanged. The handler catches
nothing and every `Throwable` propagates unchanged. It does not establish either
permission, validate evidence or provenance, authenticate a caller, select a
transport, or define the non-trusted path. Direct construction of allowed values,
direct use of the handler, and direct use of the low-level projector or renderer
therefore remain possible but prove no authorization. Diagnostic Handler owns
none of those outer concerns. Web Runtime now owns per-delivery composition,
the non-trusted path, response selection, send, and close from supplied
decisions. The PHP CGI delivery now owns bounded native capture, fresh evidence
evaluation, permission construction, and concrete emission; the converter and
Diagnostic Handler own none of those outer concerns. Diagnostic Handler is the only
current package depending on both Diagnostic Activation and Diagnostic
Presentation; neither lower layer depends on Handler.

Trust may never be enabled, widened, or inferred from exception messages, class
names, context fields, hostnames, filesystem presence, network reachability,
heuristics, AI prompts, responses, tools, models, or recommendations; or global,
superglobal, container, service-locator, thread-local, fiber-local, static,
mutable process-wide, cached ambient, or previous-operation state. Development
presentation never authorizes automatic environment, request, secret, SQL, or
provider-payload dumping.

The implemented trusted model includes both Safe and Protected fields because it is
explicitly developer-only, but it preserves each classification exactly and
cannot promote, reclassify, merge, omit, redact, sanitize, or infer it. A
production adapter must not reuse this model and redact afterward; production
requires a separate deny-by-default model and explicit allowlists. Native
messages, causes, traces, classes, source, stacks, requests, headers, bodies,
environment or configuration values, SQL, provider payloads, log or telemetry
data, incidents, documentation, localization, arbitrary metadata, and transport
data are excluded from the implemented projection.

Phase 0Z implements the trusted-developer HTML renderer and its final HTML
escaping. Phase 1A accepts two independent outer permissions: trusted bootstrap
permission and adapter-controlled delivery permission must both be affirmative
before presentation access, projection, rendering, or delivery. Phases 1C and
1D implement the two typed inputs, Phase 1E implements their conjunction, and
Phase 1H implements denial-before-access sequencing. Phase 2G implements the
neutral two-gate adapter composition and delivery sequence. Phase 3E implements
literal operator selection and exact host-evidence evaluation, and Phase 3F
implements the concrete PHP CGI delivery and entry. Phase 3H externally proves
that selected path; broader transport authorization and deployment hardening
remain unimplemented.
The final HTML grammar boundary escapes each
dynamic text value with
`htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8',
true)`. Escaping prevents markup injection but does not authorize disclosure or
declassify Protected data. The trusted model remains prohibited for production,
which requires a separate deny-by-default model. The implemented renderer is a
pure string transformation only and itself owns no activation boundary,
adapter, HTTP response, or production page. Web Runtime's separate implemented
boundary does not change that package-local limitation. Escaped Protected data remains
Protected.

These are architectural disclosure requirements. Phase 0Q integrates exact
bounded context for all 11 Graph failures. Phase 0T integrates all six Kernel
lifecycle failures using only closed Safe state names, bounded counts, and the
conservative `inactive_or_foreign` token. Numeric first-mismatch keys from a
non-list array are captured only as canonical base-10 Protected text; if the
first mismatch is a string key, it is omitted entirely and later keys are not
inspected. Graph keys and open kind labels are schema-approved structural values
but remain Protected. This conservative omission is not sanitization, redaction,
or proof that retained values are publishable.

Dependency Injection's 25 failures now expose exact bounded context across 26
throw sites, while Diagnostics construction failures expose no recursive
context. Kernel, Graph, and DI contexts remain independently package-owned.
Kongono implements only the isolated trusted-developer HTML renderer; it
implements no disclosure policy, served error page, incident store, or
production transport mapping.

The closed structural context-schema model fixes every field's key, fixed label,
scalar kind, order, optionality, and disclosure. The Graph, Kernel, and DI
catalogs now own the semantically authorized schemas for their respective 11,
six, and 25 identifiers, and their throw sites supply only schema-approved
bounded values. A structurally valid consumer schema is not a Kongono-owned
diagnostic schema, and arbitrary context bags are prohibited.
`Safe` means eligible
for a future explicit presentation or transport policy, never automatically
public. `Protected` values are limited to trusted developer or protected
operational boundaries and remain excluded from untrusted production output by
default.

Passwords and hashes, passphrases, PINs, API and bearer tokens, cookies,
sessions, CSRF values, authorization headers, credentials, private/signing/
encryption keys, recovery codes, credential-bearing connection strings,
complete environment or configuration dumps, complete request or response
bodies, arbitrary headers, SQL with bound values, provider payloads, file
contents, source excerpts, serialized objects, memory dumps, and uncertain
secret material are forbidden from context rather than classified as a third
disclosure type. A boundary may record only that forbidden material was omitted.

Direct structural construction is eager, immutable, ordered, bounded to 16 scalar
fields, and free of ambient state and I/O. Text representation is deterministic,
binary-safe, and bounded to 512 bytes with a visible marker, but it is not secret
detection, redaction, or transport escaping. Disclosure is fixed by schema, not a
throw site, renderer, request, environment value, or AI system. Trusted
presentation requires both Accepted outer permissions. Web Runtime enforces the
supplied decisions for response selection; PHP CGI implements the selected
literal operator and exact host-evidence evaluation without adding general
configuration or broader transport authorization. Graph, Kernel, and DI
exception capture are implemented.
Forbidden-material detection, sanitizer behavior, and disclosure
surfaces do not exist.

## Accepted developer-intelligence security boundary

Phase 3 is complete at checkpoint
`6aacafc92f07b567818173d660e2e182dfa83d23`. Phase 4A accepts security policy
for planned Kongono Chie, Kongono Hantei, and Kongono Oshie only; it implements
no assistant, knowledge artifact, provider, tool, patch, or production access.
The deterministic framework remains usable offline and without AI. Developer
companions are distinct from application Agent Principals.

Future role access must enforce least authority, separate read/write authority,
and distinct gates for network, credentials, dependencies, Git, deployment,
publication, destructive actions, and production access. No model output,
retrieved instruction, or generated artifact may elevate authority, weaken a
gate, or certify itself. Chie cannot certify its own candidate, Hantei cannot
repair what it judges, and Oshie cannot promote generated teaching to accepted
truth.

Safe and Protected disclosure applies before any role receives information.
Roles cannot reclassify Protected data. Secrets, credentials, tokens, raw
rejected input, and unrelated source remain excluded unless a later explicit
contract proves a necessary bounded use. Repository text, dependencies,
diagnostics, comments, issues, retrieved content, and model responses are
untrusted inputs. Future boundaries must fail closed on absent or ambiguous
authority, version, provenance, or evidence and address prompt injection, tool
confusion, path escape, command injection, dependency manipulation, secret
exposure, and data exfiltration.

No provider or external transmission is selected. Any later external access
must be explicitly enabled, disclose and minimize what leaves the machine, and
respect workspace policy. Training, fine-tuning, feedback retention, or cross-
project learning requires explicit opt-in and security review; source is not
used for training by default. No Phase 4 role may stage, commit, publish,
deploy, or access production. See the [developer-intelligence architecture](docs/architecture/developer-intelligence.md)
and [ADR 0060](docs/adr/0060-human-centered-developer-intelligence-family.md).

## No security guarantees yet

This repository is not production-ready. Planned security architecture is not implemented security functionality.

The completed Phase 2 fixture does not change that status. It proves fixed
generic output and trusted HTML only when both typed permissions and a supported
source are present. Bootstrap denial skips delivery-evidence evaluation; request,
route, header, cookie, query, body, session, token, and client flags cannot enable
trusted output. The fixture's delivery permission is operator-owned test setup,
not a production network authorization mechanism.

Black-box tests prove one request/send/close cycle, closed request and response
bodies, and zero active Kernel operations after sequential deliveries. No
listener, SAPI policy, proxy trust, TLS/operator identity, authentication,
authorization, incident handling, logging, telemetry, or production hardening is
implemented. See the [Phase 2 fixture model](docs/architecture/phase-2-runnable-fixture.md).

Phase 3F adds a production PHP CGI delivery boundary, not production-hardening
certification. Capture retains only the selected CGI server allowlist, form and
upload presence booleans, and a lazy binary input opener. `php://input` is opened
only after the independent delivery-permission decision; rejected conversion is
mapped by Web Runtime to its fixed generic response. The delivery releases
request, response, and native-input resources explicitly and retains no
request-specific state after close. `Production` still denies trusted diagnostic
presentation, while `LoopbackDevelopment` still requires exact direct loopback
and absence of forwarded evidence. Phase 3G now supplies one entry attempt plus
one caller-supplied application-shutdown attempt and fixed diagnostic 1019 when
both fail. Throwable accessors are not Safe disclosure fields. Uncertain
emission or shutdown requires external process retirement; Kongono adds no
listener, server configuration, deployment artifact, or automatic recovery.
Application composition and partial-boot cleanup remain caller responsibilities.
Phase 3H's test-owned acceptance proves direct-loopback trust denial, disclosure,
reuse, and retirement without changing production authorization. Production
trusted diagnostics remain denied; Phase 3 is closed. Phase 4A adds only the
separately Accepted planning boundary described above.

The intrinsic `QUERY_STRING` ceiling remains 8,192 bytes. Because a matching
nonempty query must also appear after `/?` in a complete target capped at 8,192
bytes, the effective externally representable maximum is 8,190 bytes. This is
composition evidence, not truncation or a weakened input limit.

## Knowledge-source ingestion boundary

Phase 4B accepts architecture semantics only. Future knowledge ingestion must
allowlist and contain source roots before reading; reject or separately govern
links, reparse points, alternate streams, device paths, aliases, and escapes;
and never crawl ambient home, credential, environment, cache, vendor,
VCS-internal, or unrelated project state. Repository, dependency,
documentation, issue, retrieved, and generated text is untrusted inert data.
Embedded commands and prompt injection cannot execute or expand tool authority.

Secrets, credentials, raw rejected values, and unrelated source are excluded.
Safe and Protected classification applies before inclusion and before model
access; Protected diagnostic context is excluded from a general artifact by
default. Deterministic generation performs no external retrieval, upload,
provider transmission, or model call. Provenance records exclusion without
copying forbidden values.

Framework and workspace states remain separately identified and isolated in
persistent processes. Stale, mixed-version, conflicting, incomplete, tampered,
or unverifiable states fail visibly rather than silently downgrading for
availability. Generated artifacts are not executable and are not loaded
automatically. Phase 4B grants no production access or request-path dependency
and implements no ingestion, artifact, workspace reader, or runtime. See [ADR
0061](docs/adr/0061-versioned-knowledge-source-and-provenance-contract.md).

## Implemented framework-knowledge security boundary

Phase 4C implements one closed 31-source framework artifact. The generator
admits only bounded allowlisted regular files beneath an explicit root and
rejects escapes, aliases, links or reparse points, stream syntax, invalid
encoding, changed-during-read input, and size excess. Source text is inert.
Structural and current validation fail closed on malformed, tampered, stale,
partial, or mixed-version bytes. Diagnostics disclose only fixed metadata and
one closed Safe violation token, never rejected content, absolute paths,
credentials, environment values, stack traces, or native messages. Publication
is optional, content-addressed, atomic, and no-clobber. SHA-256 proves exact
integrity, not authenticity or authorization. No artifact is loaded
automatically or used on a production request path.

## Phase 4D read-only Chie boundary

## Phase 4E read-only Hantei boundary

Hantei admits only fixed local tool identities, read-only Git operations, and
the eight package-owned Composer checks. Raw child output and environment values
never enter reports or diagnostics; only bounded counts, hashes, exits, and Safe
reason tokens do. Hostile paths, links/reparse points, unstable reads, excessive
streams, timeouts, and weakened evidence fail closed. The existing locked audit
is the only check with accepted network behavior.
Chie validates the complete artifact against an explicit framework root before
resolving any topic or diagnostic. Its topic catalog is closed; diagnostic
matching is literal, case-sensitive, token-bounded, and limited to artifact
lines. The artifact reader rejects aliases, links or reparse points, alternate
streams, special files, changed reads, invalid content-addressed names, and
size excess. Retrieved instructions remain inert.

Responses omit roots, artifact paths, native messages, rejected input, secrets,
Protected context, and stack traces. Chie performs no write, model, provider,
network, dependency, Git, process launch, retention, deployment, production
access, or arbitrary workspace discovery. `blocked` is a visible artifact
state, not a Hantei verification verdict. See [ADR 0063](docs/adr/0063-read-only-chie-explanation-and-diagnosis.md).

## Phase 4F read-only Oshie boundary

Oshie reads only one explicit ordinary content-addressed artifact and its accepted fixed sources. Source text is inert. Canonical output excludes absolute paths, rejected identifiers, submitted answers, native messages, traces, secrets, and Protected context. The package retains no learner or artifact state and performs no write, network, process, Git, model, provider, verification, deployment, or production operation.

## Phase 4G reviewable Chie proposal boundary

Chie accepts one explicit bounded canonical spec and reads candidate source bytes
only from a validated current artifact. The target must be current explanatory
documentation and the asserted uppercase base SHA must match exactly. Safe text
is caller-classified and is not sanitized. The candidate has reversible diffs,
exact provenance, content identities, fixed warnings, and a pending human/fresh-
Hantei handoff. Chie has no arbitrary path discovery, second target read, write,
process, Git, Hantei, model, provider, network, publication, or deployment
authority. Fixed failure presentation excludes rejected inputs, native messages,
and paths; it does not claim that native PHP trace APIs are sanitized.

## Phase 4 external-acceptance security boundary

The Phase 4H harness treats retrieved instructions as inert bytes and proves
they cannot grant tool, filesystem, process, Git, publication, deployment,
production, or candidate-application authority. Network-denied local
Knowledge/Chie/Oshie results remain byte-identical. Hantei blocks rather than
guessing when required evidence is unavailable, while checkpointing separately
requires a real current-repository PASS. Protected, secret, environment,
temporary, rejected, and native values are excluded from bounded outputs.
Temporary writes are contained outside the repository and cleaned before
success. No model, provider, account, subscription, telemetry, or external
transmission is introduced.

## Accepted Phase 5A composition-build security boundary

Phase 4 is complete at checkpoint
`358e412929d6bb7316195d3a1748c346a2de0e1b`. Phase 5A accepts security
requirements for future application composition and generated wiring but
implements no reader, declaration format, artifact, generator, package,
command, or runtime behavior.

Composition inputs are executable trust-boundary material. Later phases must
define exact source authority, path containment, supported symbol forms,
bounds, encoding, symlink/reparse/alternate-stream handling, duplicate and
conflict selection, diagnostics, disclosure, content identity, and
changed-during-read behavior before admitting them. Repository text remains
inert and cannot enlarge tool or runtime authority.

Generated source must exclude secrets, credentials, environment values,
request data, raw rejected input, and Protected diagnostic values. It cannot
grant authorization, capability, Git, network, provider, deployment, or
production authority. Production startup must fail closed before accepting
work when the build artifact is missing, stale, partial, mixed-version,
ambiguous, tampered, unsupported, or unverifiable.

Shared generated wiring must never retain request, response, principal, tenant,
diagnostic failure, route match, scope token, or operation data. Overlapping,
reentrant, and sequential operations require explicit isolation. Partial boot,
constructor failure, cleanup failure, shutdown failure, and combined failure
must preserve deterministic ownership; uncertainty requires external runtime-
profile retirement rather than silent reuse.

Ambient containers, runtime service location, resolver/container injection,
property-hook dependency resolution, request-time reflection/scanning,
runtime `eval`, and remote generated code are prohibited for the supported
path. Phase 5A changes neither the current resolver nor the Application Graph.

Phase 5B applies `>=8.5.0 <8.6.0` to the root and all eighteen packages as
installability metadata only. PHP 8.5 is the sole compatibility-certified
minor, with PHP 8.5.9 on Windows proved by this execution. Stable eligible 8.5
patches remain admitted; PHP 8.6, PHP 9, prerelease, nightly, and automatic
future-minor support are not claimed. No runtime semantics, security control,
package, dependency, public API, polyfill, shim, release/LTS promise, or
composition/build capability is added. Phase 5B is accepted and this bounded
PHP constraint is current.

## Accepted Phase 5C source and lifecycle security contract

The only Accepted version-1 source is inert UTF-8 JSON at
`<application-root>/kongono.composition.json`. The Phase 5D build boundary receives
the root explicitly, prove containment and stable bytes, and reject wrong-case,
outside-root, non-ordinary, linked/reparse, alternate-stream, device, UNC-
ambiguous, case-colliding, duplicate-member, unknown, malformed, or over-bound
source. It never executes source text, merges authority, or publishes after a
failure.

Runtime values and secrets enter only as explicit typed inputs; neither source
nor provenance contains their values. Diagnostics exclude raw source/value
fragments, absolute paths, credentials, environment values, native parser text,
stack arguments, and Protected context. Application objects cannot retain
operation-specific state, and cleanup uncertainty remains visible for external
retirement. These controls are implemented inside the Experimental build
package. Raw source and canonical semantic hashes remain distinct, and exact
currentness fails closed. Phase 5E generated bytes bind exact Composition,
model, and generator identities and exclude runtime values. Typed aggregate
services replace collection injection; automatic multibinding, array/iterable/
variadic/PHPDoc collection widening, containers, and service location remain
prohibited. Leases prevent shutdown with active operations, and failed cleanup
permanently closes or retires its boundary. Composition and
DI/Graph/Kernel/runtime behavior are unchanged; no production loader exists and
Phase 5F has not begun. See [ADR 0070](docs/adr/0070-versioned-composition-source-and-provenance.md),
[ADR 0071](docs/adr/0071-roots-bindings-lifetimes-and-cleanup.md),
[ADR 0072](docs/adr/0072-deterministic-composition-artifact-and-publication.md), and the
[ADR 0073](docs/adr/0073-generated-typed-wiring-and-lifecycle-boundary.md), and the
[composition source guide](docs/architecture/composition-source.md).

## Phase 8G native CLI and build-publication security

Phase 8G implements the one-shot `kongo` edge with exactly
`application:build`. Only the extensionless package binary reads process
arguments or native streams and terminates the process; inner production code
receives explicit bounded tokens and ports. Human and canonical machine forms
derive from one neutral result and exit map, and the selected stream receives
one completed document in one write without retry after an output failure.

`--plan` is a non-mutating dry run: it performs validation, generation, path
containment, and current-output assessment in memory without creating,
deleting, renaming, touching, or rewriting a path. `--apply` is the sole
mutation authority and publishes exactly the predicted Composition artifact
and generated wiring through their content-addressed, atomic, contained,
initial-only, no-clobber publishers before rechecking their identities. Linked,
partial, tampered, outside-root, or changing targets fail closed without
residue.

No prompt exists and `--no-interaction` is automation-safe. Diagnostics contain
only bounded Safe fields and never raw values, paths, source text, exception
messages, credentials, environment data, ANSI control sequences, banners, or
debug output. Phase 8F snapshot handlers remain programmatic because the
application supplies no fabricated snapshots. No alias, second command,
daemon, watcher, shell, installer, or deployment manager is introduced. Phase
8H externally proves those boundaries through fresh argv-array processes and
an owned temporary application root. A conflicting target is a nondisclosing
Precondition at exit 3 and remains byte-for-byte unchanged; exit 6 remains the
Infrastructure category. The harness removes all owned paths before success and
requires byte-identical repository status. See [ADR 0104](docs/adr/0104-phase-8-external-cli-acceptance-and-checkpoint.md).

## Observability configuration and disclosure

Phase 10G admits only the closed application-owned
`kongono.observability.json` source. It contains bounded resource and batch
values but no endpoint, URL, header, credential, token, provider, transport,
sampler, or environment lookup. Source and artifact admission reject escapes,
links, junctions, reparse points, alternate streams, case aliases, and
non-ordinary substitution. Inspect/check output is limited to canonical hashes,
the artifact basename, fixed profile, and currentness/publication booleans.

Observability does not declassify values or grant authority. Signal data,
resource/configuration values, paths, payloads, trace/correlation identifiers,
exception text, endpoints, credentials, and provider details remain absent from
diagnostics and command output. Transport is an explicit caller-owned
capability; the repository skeleton's capturing transport performs no network
access and is not a production exporter.

## Governed outbound HTTP and network egress

Phase 15A accepts documentation architecture for optional outbound HTTP.
Phase 15B accepts the optional Experimental neutral contract in
[ADR 0156](docs/adr/0156-neutral-versioned-network-egress-contract-foundation.md);
it implements no network behavior. A URI, policy artifact, authority reference,
capability claim, DNS answer, address,
TLS certificate, redirect, response, receipt, Graph fact, model result, or
developer-tool output is evidence rather than permission. Applications retain
current authority, policy, credentials, endpoint and transport selection, and
external-service semantics.

The accepted direction binds resolution to the selected connection address,
fails closed for SSRF and DNS-rebinding hazards, keeps TLS verification enabled,
denies ambient proxies, credentials, cookies, redirects, and retries, and
requires finite budgets, streaming backpressure, cancellation, uncertainty,
and deterministic cleanup. Diagnostics omit URLs, queries, credentials,
headers, cookies, bodies, private addresses, proxy secrets, certificates,
paths, and foreign exception text. Contract construction retains PSR-7 body
streams by reference without reading, copying, or claiming deep immutability.
See [ADR 0155](docs/adr/0155-explicit-outbound-http-and-governed-network-egress-boundary.md),
[ADR 0156](docs/adr/0156-neutral-versioned-network-egress-contract-foundation.md),
and the [egress guide](docs/architecture/outbound-http-and-network-egress.md).

Phase 15C accepts [ADR 0157](docs/adr/0157-deterministic-network-egress-policy-artifact.md)
and the deterministic [policy-artifact boundary](docs/architecture/network-egress-policy-artifact.md).
Exact origins and policy configuration in canonical artifacts are Protected,
not diagnostic text or authority. Structural validation and caller-relative
currentness use only supplied evidence. Publication is content-addressed,
atomic, and no-clobber within one explicit checked directory; diagnostics omit
origins, identities, digests, paths, credentials, and throwable messages. The
package supplies no resolver, DNS, socket, TLS implementation, credential or
proxy discovery, cookie jar, transport, runtime authorization, loader, or
network effect. Phase 15D accepts [ADR 0158](docs/adr/0158-bounded-provider-neutral-network-egress-runtime.md)
and the [bounded runtime](docs/architecture/network-egress-runtime.md). It
validates exact-current policy, rejects mixed or forbidden resolution results,
binds the exact selected address to one caller-owned transport attempt,
rechecks authority before open, enforces finite byte/time/evidence limits, and
closes every opened session once. It never consumes a body stream or discovers
DNS, credentials, proxies, cookies, transports, or providers. Phase 15E adds
only the explicit cURL HTTP/1.1 transport profile. Phase 15F adds only an
exact-current single-profile application workflow over explicit caller-owned
authority-currentness, resolver, transport, cancellation, and clock
collaborators. Phase 15G adds only offline, read-only, visibility-safe
inspection of one exact supplied profile/wiring value and optional exactly
paired detached operation/result evidence. It performs no live probe,
credential access, generated-code loading, authority evaluation, replay,
recording, repair, or role execution. Phase 15H adds only bounded local
external acceptance, and Phase 15 is closed at one exact local checkpoint.

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

The current topology is 81 packages / 317 local edges / acyclic, with PHP
inventories 1,514 / 768 / 84. Phase 15A-H are Accepted and Phase 15 is closed.
No first-party resolver, credential provider, provider adapter, live
inspection probe, inspection command, public compatibility certification,
Stable/1.0 claim, or production-readiness claim exists.

## Phase 16A-C external-service binding and secret-lease security boundary

Phase 16A accepts [ADR 0163](docs/adr/0163-explicit-external-service-binding-and-secret-lease-boundary.md),
the [security-facing architecture guide](docs/architecture/external-service-binding-and-secret-leases.md),
and the [Phase 16 roadmap](docs/architecture/phase-16-roadmap.md). Phase 16B
accepts [ADR 0164](docs/adr/0164-neutral-external-service-binding-and-credential-lease-contracts.md)
and the optional Experimental `kongono/external-service-contract` package.
Its 27 production declarations and 15 tests reuse Phase 15 origin, capability,
resolver, and resolution evidence without granting authority.

Binding evidence carries an opaque reference, never secret material. A
credential lease is operation-scoped and distinct from endpoint selection,
resolution evidence, the selected address, transport, provider semantics, and
current network authority. Credential access and network access are separate
permissions. PHP retirement minimizes lifetime and copies but cannot prove
reliable memory zeroization or prevent caller copies. The borrowed consumer is
the sole secret-bearing signature. The package provides no lease, source,
resolver, provider, artifact, cache, environment, runtime, command, or network
implementation. Its diagnostic and exception surfaces admit only bounded Safe
context. Phase 16C accepts [ADR 0165](docs/adr/0165-deterministic-external-service-binding-artifact-and-generated-php.md)
and adds only secret-free deterministic artifact and ordinary-PHP evidence.
Generated PHP is never loaded; structural or explicit-source-current validation
grants nothing. Path-safe content-addressed atomic publication never overwrites
a conflict. No material, material-derived hash or length, provider value,
environment value, query string, or foreign exception text is admitted.
Credential and network permissions remain independent and absent. Phase 16D
accepts [ADR 0166](docs/adr/0166-explicit-bounded-system-resolver-adapter.md)
and an optional Experimental system-resolver leaf. It performs one bounded
`ext-sockets` lookup with `SOL_TCP` and omitted `ai_family`, validates every
entry, preserves every conservative Phase 15 address category, and returns
selected-address evidence without connecting or granting authority. Native
messages, hostnames, addresses, ports, resolver configuration, credentials,
and provider data never enter diagnostics. There is no cache, retry, fallback,
TTL, DNSSEC, provenance, or portable deadline claim. Phase 16E accepts [ADR
0167](docs/adr/0167-operation-scoped-credential-lease-runtime.md) and one
explicit operation-scoped lease coordinator. It delegates borrowed material
directly from the lease to the consumer, retires every acquired lease exactly
once, keeps package diagnostics nondisclosing, and preserves foreign failures
only for deliberate programmatic handling. It supplies no credential source or
network permission and cannot promise PHP memory zeroization. Phase 16F accepts
[ADR 0168](docs/adr/0168-exact-current-external-service-binding-application.md)
and one optional one-use application coordinator. Request construction occurs
only inside borrowed material access; the request factory, PSR-7 value, caller,
and transport may still copy material. Package diagnostics omit secrets,
requests, responses, URIs, hosts, addresses, paths, and foreign details, while
exact throwable identity remains available only programmatically. Phase 16G
accepts [ADR
0169](docs/adr/0169-visibility-safe-external-service-inspection-and-bounded-evidence.md)
and a visibility-safe inspection leaf. It compares endpoint and credential
references without rendering them, never reads credential material or message
bodies, and reports only Protected digests, closed tokens, and Safe aggregates.
Retirement and cleanup do not prove physical erasure, and possible effects
remain uncertain. Phase 16H accepts the root-owned local external-service
acceptance proof and closes Phase 16 at one exact local checkpoint. The proof
uses only an inert test credential, numeric loopback resolution, and an
explicit `.invalid` selected-address exchange; it grants no credential or
network authority and does not establish physical erasure, public DNS, TLS,
provider, remote-effect, deployment, or production-readiness truth. Stable/1.0
remains unauthorized; Phase 17A is separately accepted below as documentation
only. Phase 17B-G add the optional mapping/adapter application and inspection
leaves. Current topology is 89 packages / 375 local production edges / acyclic;
PHP inventories are 1,726 / 894 / 92. See [ADR
0170](docs/adr/0170-phase-16-external-service-acceptance-and-checkpoint.md).

## Phase 17A-H provider-adapter security boundary

Phase 17A accepts [ADR 0171](docs/adr/0171-first-party-openai-responses-adapter-boundary.md)
as documentation architecture for an optional OpenAI Responses adapter. Every
future attempt still requires separate current application authorization,
Phase 14 plan currentness, Phase 16 binding and credential-lease currentness,
and Phase 15 network authority. Possession of a key, model name, response ID,
provider acknowledgement, receipt, or Agent proposal grants none of those.

Secrets may be borrowed only through the Phase 16 lease while constructing one
terminal authorization field and must not enter deterministic evidence,
generated PHP, URLs, model material, diagnostics, logs, traces, metrics,
receipts, Graph facts, inspection, tests, or reports. Provider bodies and SSE
events remain bounded untrusted input. There is no ambient discovery,
automatic retry/replay, tool execution, live-provider call, or reliable memory-
zeroization claim in Phase 17A. Phase 17B accepts [ADR 0172](docs/adr/0172-deterministic-openai-responses-offline-protocol-mapping.md).
Its offline mapping validates duplicate-free bounded JSON and rejects
unsupported nested shapes. Diagnostics exclude raw bodies, messages,
identifiers, model names, endpoints, headers, native messages, and secret-like
values. Storage is only a provider request preference; decoded claims grant no
credential, network, tool, or application-effect authority. See the [adapter guide](docs/architecture/openai-responses-provider-adapter.md)
and [roadmap](docs/architecture/phase-17-roadmap.md).

Phase 17C and Phase 17D retain one-attempt governed transport, bounded body or
SSE parsing, preserved uncertainty, and deterministic cleanup without retry or
replay. Phase 17E accepts [ADR
0175](docs/adr/0175-strict-structured-output-and-inert-openai-function-call-mapping.md).
Its Version 2 schemas are explicit, bounded, duplicate-safe, closed provider-
profile inputs. Structured values are locally validated without coercion but
remain untrusted evidence. Function-call names and arguments become one inert
neutral suggestion only: no callable, tool, proposal, capability, approval,
policy, queue, or application effect is discovered or invoked, and no second
provider operation is created. Version 2 streaming fails before external-
service application creation. Version 1 text and text streaming remain
accepted. Phase 17F binds the exact-current application profile and explicit
single-use workflow. Phase 17G's optional inspection leaf revalidates supplied
profile evidence and optional matching detached completed runtime evidence
without accessing provider or transport bodies, credential material, endpoints,
networking, provider state, or authority. Its digests, tokens, aggregates,
Graph projections, observations, audits, and role-addressed views remain
bounded evidence rather than truth, authorization, certification, or proof of
delivery. Phase 17H accepts the root-owned loopback proof in [ADR
0178](docs/adr/0178-phase-17-openai-responses-local-acceptance-and-checkpoint.md).
It uses no public endpoint or real credential and adds no compatibility,
certification, deployment, production-readiness, or Stable/1.0 claim. Phase
17A-H are Accepted and Phase 17 is closed. Phase 18A-H are Accepted and Phase
18 is closed.
