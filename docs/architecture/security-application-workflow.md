# Security Application Workflow

## Purpose

Phase 9G provides one supported, explicit route from application-owned policy
identity to protected capability execution. It combines existing boundaries
without moving authority into transport, generated wiring, commands, or
artifacts.

## Build and currentness

An application owns `kongono.security-policy.json`. Version 1 contains exactly
the policy name, version, and provenance; it is deliberately rule-free. The
Security Policy Build package validates the bounded ordinary source and derives
one canonical artifact whose identity covers source bytes, detached model, and
the exact builder implementation.

`inspect(application-root, output-directory)` computes identities and reports
whether publication is required without writing. `build(...)` publishes only
the content-addressed artifact into an already-existing contained directory,
then validates exact currentness. Identical publication is idempotent;
conflicting bytes are never overwritten. `check(...)` performs no writes and
requires the exact current artifact to exist. The running application receives
one immutable `CurrentSecurityPolicy`; it does not watch or reload source.

## Request lifecycle

For each explicit server request, instant, environmental evidence object, and
protected capability, `BrowserSessionProtectedApplication`:

1. begins one browser-session security operation;
2. uses the exact Security Context owned by that operation;
3. creates one Authorization Request from that same context, the capability's
   exact Action and Resource, and current policy evidence;
4. invokes `ProtectedCapabilityEnforcer` exactly once;
5. returns the capability result only after exact Permit; and
6. closes the operation exactly once on success or failure, passing the primary
   failure so established cleanup composition remains authoritative.

Authentication success grants no permission. Policy identity evidence grants
no permission. Deny, Indeterminate, evidence mismatch, unsupported policy,
evaluation failure, and binding mismatch all prevent capability execution.

## Copy-once example

`skeletons/security-minimal` is a copy-once application example. Its Composition
version-2 source has one operation-lifetime Security Context input and one
operation root. The public example evaluator permits only its exact anonymous
example request. Its explicit `NoSessionVerifier` rejects all presented session
evidence and exists only so the no-cookie example has a closed registration.
Neither component is a production provider or framework policy.

After copying, replace those application-owned examples with reviewed
application code. Generated output and `vendor/` remain derived. Kongono does
not merge, patch, watch, clean, or overwrite the copied application.

## Supported commands and disclosure

The official one-shot `kongo` process exposes three commands:

- `application:build`, retaining its established explicit plan/apply behavior;
- `security:inspect`, a read-only policy identity plan; and
- `security:check`, a read-only exact-current proof.

Both security commands require ordinary `application-root` and
`output-directory` inputs, never prompt, and never accept plan/apply authority.
Human and machine output derives from one neutral result and discloses only the
artifact basename, artifact SHA-256, source SHA-256, and the applicable bounded
publication/currentness fact. Raw paths, source, policy values, credentials,
exception messages, and debug details are not results.

## Stability and exclusions

The two packages and their operational APIs are Experimental. The decision and
separation rules are Accepted. The graph contains exactly forty-four acyclic
production packages. No broad identity product, second provider/profile,
autonomous policy, policy language, agent-delegation runtime, or deployment
manager exists. Phase 9H externally proves this workflow from fresh temporary
application state without adding production behavior or authority.

## Related material

- [ADR 0112](../adr/0112-deterministic-security-policy-evidence-and-supported-application-workflow.md)
- [ADR 0113](../adr/0113-phase-9-external-security-acceptance-and-checkpoint.md)
- [Identity and Access Security](identity-and-access-security.md)
- [Operation-scoped Security Context](operation-scoped-security-context.md)
- [Browser-session security and capability enforcement](browser-session-security-and-capability-enforcement.md)
- [Structured CLI](structured-cli.md)
- [Phase 9 external security acceptance](phase-9-external-acceptance.md)
