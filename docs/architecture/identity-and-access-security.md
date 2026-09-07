# Identity and Access Security

## Phase 14B model-invocation relationship

The implemented [neutral contract foundation](model-invocation-contract-foundation.md)
under the Accepted [provider-neutral model-invocation architecture](provider-neutral-model-invocation.md)
does not change this authority boundary. Provider capabilities, profiles,
prompts, responses, stream events, normalized results, tool suggestions,
usage/cost evidence, and receipts do not authenticate a Principal or authorize
an effect. Applications retain credential policy and supply, model policy,
adapter and transport authority, result eligibility, and exact Phase 13
admission and authorization. Phase 14B adds no credential mechanism, provider
SDK, network, adapter, Agent bridge, runtime, currentness, or enforcement behavior.

## Phase 13G Agent Principal relationship

Phase 13B adds an Agent-only wrapper around the existing neutral
`PrincipalReference` without changing any Phase 9 package or runtime behavior.
The wrapper validates only `PrincipalKind::Agent`; it does not authenticate the
Principal or construct a Security Context. Agent
kind, identity, authenticated facts, delegation, consent, a model proposal,
Graph visibility, extension state, and previous success grant no permission.
Only an exact deterministic authorization decision over current explicit
evidence may reach a separately protected capability.

Phase 13C declaration artifacts reconstruct one Agent-kind reference but do not
authenticate it, create a Security Context, evaluate policy, validate external
evidence currentness, or authorize a capability. Exact-current artifact bytes
are not current authority evidence. Phase 9 Security behavior is unchanged.

Phase 13D validates one exact current declaration artifact against explicit
completed application-owned authentication and authorization evidence. It does
not invoke an authenticator or policy evaluator, validate the external
currentness of a receipt, create a Security Context, grant a capability, enforce
a constraint, or execute work. Identity mismatch, missing evidence, Deny,
Indeterminate, undeclared requests, and unmet required capability deny admission
by default. See [Agent Admission and Authority Plan](agent-admission-and-authority-plan.md).

Phase 13E consumes only an exact matching admitted plan and asks one explicit
caller-owned currentness port to affirm the plan and operation immediately
before proposal and capability effects. It neither evaluates policy nor turns
the plan, currentness response, proposal, or receipt into a grant. Only one
explicit matching capability port may perform work. No ambient Security
Context, tool registry, provider adapter, autonomous loop, or sandbox is added.
See [Agent Principal Runtime](agent-principal-runtime.md).

Phase 13F binds one exact declaration artifact and matching admitted plan into
one generated and explicitly loaded application profile. Profile and generated
identities remain evidence, not authentication, authorization, currentness, or
a Security Context. Every operation still supplies the Phase 13E currentness
port explicitly before possible effects. See
[Agent Application Workflow](agent-application-workflow.md).

Phase 13G revalidates only the exact explicit profile/wiring relationship and
may summarize a completed result. Its inspection identity, aggregate counts,
runtime evidence, Graph context, observations, audit evidence, role tokens, and
verification PASS are inert evidence. They do not authenticate a principal,
reevaluate or currentize authorization, grant a capability, create a Security
Context, prove revocation/consent/delegation, establish an agent-to-Graph
relationship, observe a live application/provider, or prove an external effect
or application security. See
[Agent Principal Inspection and Evidence](agent-principal-inspection-and-evidence.md).

Phase 14G follows the same evidence separation for model invocation. Exact
supplied profile/wiring proof, optional completed detached runtime evidence,
inspection identity, Graph context, projections, role tokens, and verifier PASS
do not establish credentials, authentication, authorization, live provider or
application state, current external authority, or proof of effect. Raw model,
provider, application, credential, path, and arbitrary caller content is
omitted. See [Model Invocation Inspection and Evidence](model-invocation-inspection-and-evidence.md).

Agent Principal capability requests and grants remain distinct.
Capabilities are explicit, typed, versioned, purpose-bounded, attributable,
constrained, and denied by default. No ambient Security Context, current agent,
policy, credential, tool registry, or authorization state is introduced. See
[ADR 0139](../adr/0139-governed-agent-principals-and-deterministic-authority-boundary.md)
and the [Agent Principal Contract Foundation](agent-principal-contract-foundation.md).

## Purpose and current scope

Phase 9 establishes Kongono's Identity and Access Security direction without
pretending that later runtime behavior exists. Phase 9A is documentation only. It accepts
the separation, lifecycle, enforcement, provenance, and disclosure rules in
[ADR 0105](../adr/0105-explicit-identity-authentication-and-authorization-boundary.md)
and [ADR 0106](../adr/0106-fail-closed-security-context-and-policy-separation.md),
plus the fixed [Phase 9 roadmap](phase-9-roadmap.md). Phase 9B accepts
[ADR 0107](../adr/0107-neutral-identity-and-access-contracts-and-package-boundary.md)
and implements the neutral Experimental `kongono/identity-access` contract
package. Phase 9C accepts
[ADR 0108](../adr/0108-deterministic-explicit-authentication-orchestration.md)
and implements the separate Experimental `kongono/authentication` package.
Phase 9D accepts
[ADR 0109](../adr/0109-deterministic-deny-by-default-authorization-evaluation.md)
and implements the separate Experimental `kongono/authorization` package.
Phase 9E accepts
[ADR 0110](../adr/0110-operation-scoped-security-context-and-generated-composition-inputs.md)
and implements explicit Composition operation inputs plus the Experimental
`kongono/security-runtime` package. Phase 9F adds the selected browser-session
transport and protected-capability enforcement packages. Phase 9G accepts
[ADR 0112](../adr/0112-deterministic-security-policy-evidence-and-supported-application-workflow.md)
and adds rule-free exact-current policy evidence plus the supported protected
application workflow.

Use this guide to distinguish those current contracts from later Phase 9
proposals. Authorization evaluation is implemented only within the bounded
Phase 9D package, and operation-scoped context construction/wiring is implemented
only within the bounded Phase 9E package. Do not use either as evidence that
session handling, policy compilation/currentness artifacts, or
protected-capability enforcement is implemented beyond those exact Phase 9F/9G
boundaries.

## Current Phase 9B contracts

`kongono/identity-access` is the thirty-seventh acyclic production package and
depends only on API Metadata, Diagnostics, and PHP. No existing production
package depends on it. It currently owns:

- exact Human, Service, Organization, System, and Agent `PrincipalKind` values;
- opaque exact `PrincipalId` and `ResourceId` values and immutable
  `PrincipalReference` and `ResourceReference` pairs;
- eight `AuthenticationOutcome` categories, with a principal present only for
  Authenticated;
- the inert `AuthorizationDecision` cases Permit, Deny, and Indeterminate;
- exact canonical application-owned `Action` and `ResourceType` names; and
- an explicit `SecurityContext` that is Anonymous without a principal or
  Authenticated with exactly one principal reference.

The opaque identifiers accept 1-512 bytes of valid UTF-8 without C0/C1
controls and preserve bytes exactly. Action and ResourceType accept 1-128 ASCII
bytes matching `^[a-z][a-z0-9]*(?:[._:-][a-z0-9]+)*$`. Invalid input is rejected
through package-owned bounded non-disclosing diagnostics and exceptions.
Identifier accessors do not make their bytes safe for diagnostics or display.

These contracts perform no verification, policy evaluation, authorization,
domain loading, enforcement, I/O, global read, discovery, Reflection,
serialization, or lifecycle integration. An Authenticated outcome or context
grants no permission, and Permit is inert vocabulary until later separately
authorized behavior consumes it.

## Current Phase 9C authentication orchestration

`kongono/authentication` is the thirty-eighth acyclic production package. It
depends only on API Metadata, Diagnostics, Identity Access, and PHP; no existing
production package depends on it. It owns exact canonical mechanism names,
explicit non-negative Unix-second instants, unnamed strength levels from 1
through 255, maximum-age requirements from zero through 31,536,000 seconds,
validated facts, typed verification outcomes, and neutral evidence/verifier/
operation SPIs.

Evidence is already detached and caller-owned. Its neutral interface exposes
only `mechanism()`. A request detaches and reindexes zero through eight ordered
evidence objects plus explicit `now` and requirement values. The package does
not inspect, copy, normalize, deduplicate, parse, serialize, log, or retain an
evidence payload.

An orchestrator is constructed with one through thirty-two explicit verifiers
whose exact mechanism names are unique. Zero evidence returns Absent; multiple
evidence returns Ambiguous; an unmatched sole mechanism returns Unsupported.
Those outcomes invoke no verifier. A match calls `begin()` once, `verify()`
once, and `retire()` once. There is no discovery, priority, fallback,
`supports()` probe, downgrade, retry, grace period, or best effort.

A begin failure propagates unchanged because no operation exists. Verification
failure still retires the operation and is rethrown unchanged if cleanup
succeeds. A cleanup-only failure retains its exact cause. If verification and
cleanup both fail, the combined exception retains both exact objects and uses
the verification failure as native previous. Safe diagnostic presentation
contains neither cause details nor evidence, mechanisms supplied by callers,
principal identifiers, timestamps, provider data, or rejected values.

Authenticated facts carry exactly principal, mechanism, strength,
`authenticatedAt`, and `validUntil`. They grant no permission and create no
Security Context. The mechanism must match the selected evidence, the
authentication instant may not be after explicit `now`, validity must not have
expired, age must not exceed the requested maximum, and strength must meet the
requested minimum. Equality at the validity, age, and strength bounds remains
valid.

The orchestrator retains only immutable verifier registration. Request,
operation, result, principal, facts, and failure state remain local to one call,
so sequential and reentrant calls inherit no current authentication state.
Verifier and evidence objects remain caller-owned; explicit retirement, not a
destructor or process termination, owns attempt cleanup.

## Current Phase 9D authorization evaluation

`kongono/authorization` is the thirty-ninth acyclic production package. It
depends only on API Metadata, Diagnostics, Identity Access, and PHP; no existing
production package depends on it. Policy name, version, and provenance form one
exact expected identity but define no policy syntax, signature, trust, loading,
ordering, or build currentness. Opaque policy evidence exposes only its own
reference, and opaque environmental evidence exposes only one exact profile.

Each immutable request preserves the exact supplied Security Context, Action,
Resource Reference, expected policy, policy evidence, and environment. The
engine reads the evidence reference once. A mismatch returns Indeterminate/
PolicyMismatch and invokes no evaluator. Matching evidence without an exact
registered name/version/provenance evaluator returns Indeterminate/
UnsupportedPolicy. Otherwise the sole evaluator receives the identical request
once and its exact Permit, Deny, or Indeterminate result is preserved. A thrown
failure is safely wrapped and never permits; there is no retry or fallback.

The bounded receipt contains only decision, reason, expected policy, Action,
Resource Type, Security Context state, and environment profile. It omits
principal/resource IDs and opaque payloads, cannot replay or enforce a grant,
and is never transferable authority. The engine retains only immutable
evaluator registration, so sequential and reentrant calls share no call state.

Phase 9D implements no policy language/build/loader, roles/permissions,
Security Context construction/lifecycle, Composition wiring, ambient guard,
protected-capability enforcement, HTTP/session transport, database enforcement,
audit, telemetry, AI authority, or application workflow.

## Current Phase 9E operation-scoped context integration

`kongono/security-runtime` is the fortieth acyclic production package. It
depends only on API Metadata, Authentication, Composition, Diagnostics,
Identity Access, and PHP; no existing inward package depends on it.

`SecurityContextFactory` has separate anonymous and authenticated paths. The
authenticated path accepts only an exact successful `AuthenticationVerification`
with present facts and the same exact principal reference in outcome and facts.
Every other outcome or inconsistent success fails closed without downgrading to
anonymous. The context carries no credential, evidence, mechanism, strength,
timestamp, policy, action, resource, or decision.

Composition version 2 declares `SecurityContext` with operation lifetime.
Generated startup retains only application inputs. Before startup,
`GeneratedSecurityApplication` validates one exact context input, no application
root, and one typed operation root. Each operation receives one fresh context
through explicit constructor wiring; no request-time reflection, container,
resolver, service locator, or ambient current-user state exists. Closing the
operation clears wrapper access before generated reverse cleanup.

Authentication success still grants no authorization. This phase implements no
protected-capability enforcement, HTTP credential/session transport, login,
cookie profile, policy build/currentness, workflow/skeleton, or Phase 9F
behavior.

## Separate security concerns

Identity, authentication, authorization, credential transport, enforcement,
disclosure, audit, and telemetry must remain distinct:

- identity names or refers to a party in an application-owned domain;
- authentication evaluates evidence and establishes bounded facts about an
  identity claim;
- authorization decides whether an exact action on an exact resource may
  proceed under exact policy and context;
- credential and session transports carry evidence or continuity across a
  boundary;
- enforcement prevents a protected capability from reading or acting without
  an exact permit;
- disclosure controls what an observer may learn from outcomes;
- audit records bounded security events after or around decisions; and
- telemetry observes operation behavior but never makes a security decision.

Authentication success is not authorization. A principal reference, principal
kind, role, session, route, middleware position, Dependency Injection binding,
Application Graph node, repository method, command definition, or diagnostic
mode grants no permission by itself.

## Principals and application identity

A Principal is an immutable reference suitable for exact comparison and
explicit carriage. It is not the application's user record, an external
provider object, a credential, a display name, an email address, a role, a
tenant, an actor, a subject, or a permission scalar. Domain identity data and
its lifecycle remain application-owned.

Human, Service, Organization, System, and Agent are eligible conceptual
principal kinds. A kind describes what is referenced; it grants nothing. The
application must keep principal, tenant, actor, and subject semantics distinct.
A future delegated operation may need both an actor and a subject plus exact
delegation evidence, but Phase 9A creates no delegation runtime or agent
authorization mechanism.

Anonymous access is also explicit. It is not a `null` principal accidentally
falling through a check. Whether anonymous access is permitted remains an exact
authorization decision for an application-owned action and resource.

## Explicit authentication evidence

Authentication receives detached caller-supplied evidence through one
explicitly selected typed verifier adapter. Neutral orchestration must not read
headers, cookies, environment variables, process globals, ambient request
attributes, static registries, clocks, networks, or provider state on its own.
An outer transport adapter may obtain candidate evidence, but obtaining it does
not validate it.

A successful authentication outcome contains only validated authentication
facts. Absence, invalid evidence, expiry, insufficient strength or freshness,
replay, unsupported mechanisms, ambiguity, and infrastructure failure remain
typed non-success outcomes. They must not collapse into `true`/`false`, `null`,
an untyped array, a raw provider result, or normal exception control flow. A
selected mechanism may not silently fall back to a weaker verifier when
verification fails.

Provider-originated claims are untrusted until the selected adapter validates
the exact properties its mechanism requires. Depending on the later selected
profile, those properties can include subject, issuer, audience,
cryptographic validity, time constraints, replay resistance, key identity, and
key currentness. Phase 9A selects no provider or claim format and does not make
these checks current behavior.

## Credentials and cryptographic boundaries

Credentials, keys, authenticators, proof material, session identifiers, and
recovery material are opaque sensitive values. They must not be stringified,
logged, placed in diagnostics, emitted through human or machine CLI output,
retained after their explicit lifecycle, or copied into ordinary application
state. A Safe diagnostic label does not make credential bytes safe.

Kongono will not invent cryptographic primitives. A later implementation must
select reviewed current PHP facilities, applicable standards, or explicitly
authorized libraries behind typed adapters. Password verification and any
rehash decision must use the selected platform primitive and constant-time
behavior where the threat model requires it. Future passkeys and federation are
eligible directions, not implemented support or compliance claims. Evidence
ownership, zeroization limits, retention, rotation, and adapter cleanup require
explicit later contracts.

## Deny-by-default authorization

Authorization evaluates all of the following as explicit immutable inputs:

- one operation-scoped Security Context;
- one exact application-owned Action;
- one exact application-owned Resource;
- exact current Policy Evidence; and
- explicit environmental facts needed by that policy.

The bounded decision vocabulary is `Permit`, `Deny`, and `Indeterminate`. Only
an exact `Permit` authorizes progress. `Deny` and `Indeterminate` stop the
operation. Missing, stale, partial, mixed-version, ambiguous, unsupported,
conflicting, unverifiable, or failed policy evidence cannot become a permit.
Exceptions do not represent ordinary authorization outcomes.

Applications own the meaning and granularity of actions, resources, tenancy,
ownership, relationships, and attributes. Later phases may select exact typed
capability, relationship, or attribute mechanics, but Phase 9A selects none.
Object access and field disclosure are separate decisions; authority to observe
one representation does not imply authority to reveal every field.

## Enforcement at protected capabilities

Middleware may adapt transport evidence, construct explicit inputs, and reject
invalid requests. It cannot be the sole authorization boundary. Enforcement
must occur at the typed protected capability before the protected read or side
effect, including database access, an external service call, secret disclosure,
filesystem publication, or a mutation-capable command apply.

Routing, callability, Dependency Injection availability, Application Graph
presence, repository discovery, and Structured CLI definition discovery are
reachability or construction facts, not authorization. A capability must
receive an exact decision or an explicit enforcing collaborator rather than
querying ambient current-user state.

An authorization decision is bounded to the exact inputs evaluated. It is not
a transferable bearer capability and must not be reused for a different
action, resource, policy state, tenant, actor, subject, or operation. Later
implementation must address time-of-check/time-of-use behavior wherever state
can change between evaluation and the protected operation.

## Operation-scoped Security Context

A Security Context is an immutable detached set of validated security facts for
one operation. It is constructed at an outer boundary from validated
authentication and application evidence, then passed through generated typed
wiring or explicit operation collaborators. It is never a static current user,
ambient `Auth` facade, thread-local, process-global, service-located object,
request singleton with hidden authority, or Kernel-owned state.

Operation retirement releases every reference to credentials, authentication
facts, authorization inputs, and decision evidence that the operation owns.
Cleanup remains explicit and reverse-ordered where applicable. Sequential and
overlapping operations in one persistent process must not observe one another's
security state, including after primary or cleanup failure. Kernel remains
lifecycle-neutral.

## Sessions and transport profiles

A session provides continuity; it is neither identity proof by itself nor
authorization. Credential transports and session profiles remain outer
adapters. A future supported browser-session profile must explicitly address
identifier rotation, fixation, absolute and idle expiry, invalidation, secure
cookie and TLS boundaries, logout, concurrent use, store failure, and CSRF
where applicable. CSRF protection is distinct from authentication and
authorization, and CORS grants no authorization.

A framework-reserved request attribute may later carry validated detached
evidence, but arbitrary request attributes remain untrusted and cannot create a
Security Context merely by using a familiar key. Phase 9A selects no attribute
name, cookie name, header, token format, route, login form, logout behavior,
status code, store, provider, or transport profile.

## Deterministic policy evidence

Policy truth must be explicit, deterministic, versioned, bounded, reviewable,
and provenance-bound. A later build step may validate application-owned policy
evidence and produce an exact-current artifact before runtime. Missing, stale,
partial, mixed, ambiguous, conflicting, unsupported, or unverifiable evidence
fails closed.

Runtime filesystem scanning, Reflection-based policy discovery, mutable
registries, dynamic proxy generation, `eval`, newest-file selection, remote
code, remote policy loading, and network compilation are rejected. Build-time
Reflection may be considered only through a later explicit decision and may not
leak into request-time behavior. Phase 9A selects no policy source, schema,
language, compiler, digest, package, artifact format, generated class, or
publication/loading mechanism.

## Diagnostics and non-disclosure

Existing Kongono diagnostic classification and disclosure rules continue to
apply. Public outcomes may deliberately collapse distinctions that could reveal
whether a principal, credential, tenant, policy, resource, role, relationship,
key, provider account, or recovery mechanism exists. They must not expose raw
credentials, tokens, proofs, keys, provider messages, policy source, resource
identifiers, arbitrary claims, exception text, stack arguments, paths, or
environment data.

Trusted internal evidence may preserve exact bounded diagnostic identity for an
authorized diagnostic consumer. Trusted developer mode and diagnostic access
still grant no application authorization. Authentication outcomes,
authorization decisions, diagnostics, exceptions, audit events, and telemetry
records remain separate types of evidence.

## Decision receipts, audit, and telemetry

Phase 9D implements one exact bounded Decision Receipt containing decision,
reason, expected policy reference, Action, Resource Type, Security Context
state, and environmental profile. It cannot replay a grant, become an ambient
permission, or disclose policy/environment payloads or domain identifiers.

Audit storage is not policy authority, and an audit write must not retroactively
change a decision. OpenTelemetry may observe bounded security operations but is
not an authentication or authorization engine. Receipt storage, audit
destinations, retention, privacy, access control, backpressure, failure policy,
and telemetry fields remain later decisions.

## Package and dependency direction

Future neutral security contracts must remain independent of HTTP transports,
provider adapters, sessions, application workflows, and deployment. Specific
adapters and integration packages may depend inward on neutral contracts;
neutral and lower capability packages must not depend upward on them.
Composition remains the owner of explicit construction and generated typed
wiring. Containers, resolvers, facades, and ambient registries do not become
security dependencies.

Any new Phase 9 implementation contract is Experimental unless a later accepted
decision explicitly promotes it. Implementation detail remains Internal by
default. A Public security contract is created only deliberately because its
compatibility and security obligations outlive one phase.

## Determinism, performance, and developer experience

Prefer readonly typed records, explicit inputs, prevalidated evidence, and
ordinary generated PHP over runtime magic. A constant-time claim requires an
identified threat and proof through the selected primitive; Phase 9A makes no
such implementation claim. A future cache is safe only with complete explicit
keys, bounded lifetime, currentness checks, invalidation, operation isolation,
and no authority widening. Phase 9A authorizes no security cache.

Developer APIs should make the safe path understandable: exact actions and
resources, immutable context, typed outcomes, bounded diagnostics, explicit
cleanup, and reviewable policy evidence. Tests should be deterministic and use
explicit adapters and evidence rather than networks, ambient clocks, globals,
or real secrets. Hantei may later inspect deterministic evidence but cannot
grant authority or repair policy. AI may suggest reviewable material; it never
authenticates a principal, authorizes an action, accepts policy truth, or
silently changes secrets or policy.

## Stability and truthful non-claims

The architecture in Phase 9A and the Phase 9B/9C/9D/9E/9F/9G boundaries are
Accepted. Their concrete values, orchestrators, engine, Composition version-2
surface, Security Runtime, Security HTTP, and Security Enforcement packages are
Experimental except deliberate Public exception markers and Internal
diagnostic/base types. Kongono remains pre-1.0 with exactly forty-four acyclic
production packages.

Phase 9F implements exactly one same-origin HTTPS `browser.session.v1` profile
with `__Host-kongono_session` and session-bound `X-Kongono-CSRF` evidence, plus
one stateless enforcer that invokes a protected capability only for exact
Permit. Session syntax/emission is not session truth, cryptography, storage,
login, or authorization. Authentication success, routing, middleware, and
receipts grant no authority; no ambient current-user/session/decision state
exists. Phase 9G adds only rule-free policy identity/currentness evidence, one
supported request-to-operation-to-enforcement workflow, a copy-once example,
and read-only inspect/check commands. The example evaluator and no-session
verifier are not framework authority or production providers. No second
profile/provider, role or permission semantics, database enforcement, audit,
telemetry, account product, autonomous policy, deployment manager, security
certification or production-readiness claim exists. Phase 9H adds root-owned
external proof of the selected exact boundaries and checkpoint only. Phase 9A
through 9H are complete at that checkpoint; Kongono remains pre-1.0 and Phase
10 is unimplemented.

## Related concepts

- [Phase 9 roadmap](phase-9-roadmap.md)
- [ADR 0107](../adr/0107-neutral-identity-and-access-contracts-and-package-boundary.md)
- [ADR 0108](../adr/0108-deterministic-explicit-authentication-orchestration.md)
- [ADR 0109](../adr/0109-deterministic-deny-by-default-authorization-evaluation.md)
- [ADR 0110](../adr/0110-operation-scoped-security-context-and-generated-composition-inputs.md)
- [ADR 0111](../adr/0111-secure-browser-session-transport-and-capability-enforcement.md)
- [ADR 0112](../adr/0112-deterministic-security-policy-evidence-and-supported-application-workflow.md)
- [ADR 0113](../adr/0113-phase-9-external-security-acceptance-and-checkpoint.md)
- [Operation-scoped Security Context](operation-scoped-security-context.md)
- [Browser-session security and capability enforcement](browser-session-security-and-capability-enforcement.md)
- [Security application workflow](security-application-workflow.md)
- [Phase 9 external security acceptance](phase-9-external-acceptance.md)
- [Application Composition and Build](application-composition-and-build.md)
- [Runtime model](runtime-model.md)
- [Web Runtime model](web-runtime-model.md)
- [Structured CLI](structured-cli.md)
- [Error handling and actionable diagnostics](../engineering/error-handling.md)
- [Package layout](../engineering/package-layout.md)

## Phase 13 external closure

Phase 13H supplies completed application-owned authentication and authorization
evidence to the deterministic admission boundary and proves denial for missing,
stale, mismatched, undeclared, or unpermitted cases. The harness does not
authenticate a principal, evaluate credentials, grant a capability, or turn an
admission plan, inspection, Graph view, Observation, Security Audit value, or
verifier PASS into authority. See
[Phase 13 external acceptance](phase-13-external-acceptance.md).

Phase 14H confirms that provider/model labels, plans, results, receipts,
proposals, inspection, and verifier PASS do not authenticate, admit, authorize,
grant, invoke, or prove an effect. See [Phase 14 external
acceptance](phase-14-external-acceptance.md).
