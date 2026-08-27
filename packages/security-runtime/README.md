# Kongono Security Runtime

This Experimental outer integration package creates one fresh anonymous or
authenticated `SecurityContext` for one generated Composition operation. It
validates the exact version-2 generated security profile before startup, passes
the context as the sole operation input, and releases wrapper access before
delegated generated cleanup begins.

## Calling contract

Use `SecurityContextFactory::anonymous()` for explicit anonymous intent.
`authenticated()` accepts only an exact successful `AuthenticationVerification`
with present facts and the same exact principal-reference object in both facts
and outcome. Every non-success or inconsistent result throws; no result silently
downgrades to anonymous.

`GeneratedSecurityApplication::start()` accepts one exact-current loaded wiring
profile with no application root, exactly one `SecurityContext` operation input,
and exactly one typed `SecurityOperationRoot`. Profile reflection completes
before generated startup. Each operation entry creates a fresh context and
returns a `GeneratedSecurityOperation` exposing the exact context and root.

## Lifecycle and failure

Close each operation before application shutdown. Close clears root, context,
and boundary access before delegating generated reverse cleanup and is
idempotent after the first attempt. The generated boundary preserves primary
and cleanup failure identity and refuses shutdown while an operation is active.
Package diagnostics expose fixed Safe classification only.

Authentication success supplies identity evidence only. This package performs
no authorization decision, protected-capability enforcement, credential or
session transport, HTTP adaptation, policy loading, audit storage, telemetry,
provider integration, or ambient current-user registration. Policy
build/currentness, workflows/skeletons, and Phase 9F behavior remain
unimplemented; Phase 9F is the only next architect-review handoff.
