# Security Policy

## Project status

Kongono is pre-alpha and contains runnable Experimental Kernel lifecycle, Application Graph structural, and explicit dependency-resolution functionality. There are no Stable APIs or supported production releases.

## Reporting a vulnerability

Kongono does not yet have an authorized private vulnerability-reporting address or disclosure workflow. Do not publish suspected sensitive vulnerability details in a public issue. Request a private reporting channel from the project maintainers or architect before sharing reproduction details.

The reporting address, response targets, disclosure timeline, supported-version policy, and security advisory process remain open governance decisions.

## Architectural security expectations

Planned framework work must make authorization, trust, identity, delegation, and security metadata deterministic and inspectable. AI output may assist analysis but must never be authoritative for authorization or security policy.

Extensions must not automatically receive unrestricted access to databases, filesystems, secrets, networks, shells, or processes. The planned Extension Host will govern permissions, capabilities, lifecycle, storage, network access, secrets, events, UI contributions, agent capabilities, updates, removal, and health. Exact mechanisms remain open and must not be inferred from this policy.

Persistent runtimes require explicit lifecycle and operation scopes so that identity, authorization, tenant, request, and sensitive state cannot leak between operations. Runtime-specific security contracts have not yet been designed.

Phase 0F dependency definitions may invoke registered autoloaders, and resolution executes consumer constructors. Applications must treat supplied definitions and implementations as reviewed executable input. The resolver retains no constructed service instance, exposes no ambient current resolver, and provides no authorization boundary, secret injection, or special Kernel lifecycle behavior.

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

Phase 1A accepts a two-gate fail-closed architecture for trusted diagnostic
presentation. A future server-controlled composition boundary must deliberately
grant **trusted bootstrap permission** as an already validated immutable
decision for one adapter instance before it serves work. The future outer
adapter must then independently grant **adapter-controlled delivery
permission** for the current delivery boundary. Both permissions must be
affirmative before presentation access, projection, copying Safe or Protected
fields, rendering, or delivery. One permission never implies the other.

Absence is disabled. Invalid, ambiguous, or failed raw bootstrap configuration
must be rejected before serving rather than coerced or defaulted to allowed.
Raw truthy strings and application-environment labels are not permission, and
Diagnostic Presentation reads no raw configuration. Changing configuration
cannot mutate a live adapter's decision; a changed decision requires explicit
restart or construction of a new future adapter instance.

Delivery denial or uncertainty takes the future non-trusted path without
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
not authorization. Phase 1A implements no activation type, configuration
reader, adapter, HTTP behavior, served page, or enforcement. The permission
names are **trusted bootstrap permission** and **adapter-controlled delivery permission**;
their enforcement is unimplemented.

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
before presentation access, projection, rendering, or delivery. Their
enforcement remains unimplemented. The final HTML grammar boundary escapes each
dynamic text value with
`htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8',
true)`. Escaping prevents markup injection but does not authorize disclosure or
declassify Protected data. The trusted model remains prohibited for production,
which requires a separate deny-by-default model. The implemented renderer is a
pure string transformation only; no activation boundary, adapter, HTTP
response, or production page is implemented. Escaped Protected data remains
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
presentation requires both Accepted outer permissions, and no enforcement is
implemented. Graph, Kernel, and DI exception capture are implemented.
Forbidden-material detection, sanitizer behavior, and disclosure
surfaces do not exist.

## No security guarantees yet

This repository is not production-ready. Planned security architecture is not implemented security functionality.
