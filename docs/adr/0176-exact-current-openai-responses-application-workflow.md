# ADR 0176: Exact-current OpenAI Responses application profile and supported workflow

Status: Accepted

Date: 2026-09-10

Scope: Phase 17F only

## Context

ADRs 0171 through 0175 establish one optional OpenAI Responses adapter over the
accepted Phase 14 provider-neutral invocation, Phase 15 governed egress, and
Phase 16 exact-current external-service boundaries. The provider package can
map and execute an explicitly supplied adapter profile, but applications need
one supported composition path that proves the selected Phase 14, Phase 17,
and Phase 16 profiles belong together before any effect.

That path belongs in the outer provider-specific leaf. Changing any lower
package would duplicate accepted build, currentness, credential, transport,
cleanup, or uncertainty semantics. Provider configuration, generated wiring,
profile digests, results, usage, and action suggestions remain evidence and do
not grant credential, network, capability, tool, or application-effect
authority.

## Decision

Add one immutable Experimental `OpenAiResponsesApplicationProfile` containing
exactly one Phase 14 invocation-application profile, one Phase 17 provider
profile, one Phase 16 external-service-application profile, and one derived
identity. Its Internal canonical projection uses contract
`kongono-openai-responses-application-profile/1`, a versioned UTF-8
LF-terminated projection, and a terminal line feed. It contains only bounded identity and currentness
evidence: the accepted Phase 14 identities and digests, the Phase 17 adapter
identity, mapping version, storage preference and mode, the Phase 16
application and binding identities, and only the Version 2 definition name and
schema digest when applicable. It omits raw prompts, messages, schemas,
arguments, results, bodies, endpoints, credential references, credentials, and
arbitrary caller content.

The factory reconstructs both lower application profiles from the explicit
supplied values, requires exact identities, requires adapter identity
`openai.responses.v1`, and proves provider-profile equality with the neutral
Phase 14 request profile. It enforces the accepted mapping-version capability
combinations and requires the selected Phase 16 endpoint to use HTTPS with an
empty base path. It does not hard-code a hostname. The Phase 17 application
identity is derived only after every relation passes.

`OpenAiResponsesApplicationBuildWorkflow` exposes explicit `build()` and
write-free `check()` operations. Both delegate generated-wiring publication or
currentness verification to the accepted Phase 14 application workflow, then
validate and return the exact Phase 17 profile, lower current build, and one
Ready application bound to the lower loaded wiring. The Phase 17 workflow adds
no second publisher, generated-PHP loader, path policy, or repair behavior.

The application is single-use with Ready, Active, and Retired states. `run()`
rejects reentry, overlap, and reuse before another effect; revalidates profile
currentness; matches the explicitly supplied Phase 16 application factory;
constructs one operation-scoped invocation context and one fresh accepted
adapter; then delegates one terminal invocation to the bound Phase 14
application. All resolver, transport, policy-currentness, authority-currentness,
credential source, credential purpose, cancellation, clock, and receipt
collaborators remain explicit caller-owned inputs. The external-service
application remains lazy until the accepted adapter first pulls it.

The lower runtimes retain ownership of body/session closure, credential-lease
retirement, generated-wiring cleanup, failure precedence, and uncertain-effect
classification once they accept the operation. The Phase 17 application
retires exactly once and does not retry, reconnect, replay, fall back, flatten
cleanup failures, or create a second provider operation.

## Consequences

Applications have one supported deterministic build/check/run path for the
four accepted profiles: Version 1 text, Version 1 pull-based text streaming,
Version 2 structured result, and Version 2 inert action suggestion. A stale,
mismatched, unsupported, or malformed component fails before credential,
resolver, transport, application-factory, adapter, or network activity.

The package gains no registry, default provider or model, global client,
environment discovery, dynamic compatibility mode, automatic migration,
provider SDK, alternate networking stack, workflow loop, inspection surface,
command, Chie application, or tool execution. It makes no live OpenAI
compatibility, provider availability, pricing, cost-accuracy, certification,
production-readiness, Public, Stable, or 1.0 claim.

The graph remains 88 acyclic production packages and becomes 367 local
production edges. PHP inventories become 1,704 production, 888 package-test,
and 88 root-test files. New application declarations are Experimental except
the Internal canonical projection.

## Rejected alternatives

Adding composition to Phase 14 or Phase 16 was rejected because those packages
must remain provider-neutral. Reimplementing publication, loading, currentness,
credential, egress, cleanup, or uncertainty behavior was rejected because the
accepted lower workflows already own it. A registry, defaults, environment
lookup, SDK client, provider discovery, automatic compatibility fallback, or
multi-operation application was rejected because each adds ambient authority
or an unapproved lifecycle. Hard-coding `api.openai.com` was rejected because
the bound Phase 16 endpoint is explicit application-owned evidence.

## Current and next status

Phase 17A through Phase 17G are Accepted. Phase 17H remains Planned and
separately unauthorized; it alone may create the milestone checkpoint. Phase
18 does not begin automatically.

See [ADR 0175](0175-strict-structured-output-and-inert-openai-function-call-mapping.md),
the [OpenAI Responses provider-adapter guide](../architecture/openai-responses-provider-adapter.md),
the [Phase 17 roadmap](../architecture/phase-17-roadmap.md), the
[model-invocation application workflow](../architecture/model-invocation-application-workflow.md),
and the [external-service binding guide](../architecture/external-service-binding-and-secret-leases.md).
