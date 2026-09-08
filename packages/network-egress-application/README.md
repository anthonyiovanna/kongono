# Kongono Network Egress Application

This optional Experimental package binds one explicit application-owned
`EgressPolicySource` to its exact-current Phase 15C artifact, emits deterministic
inert PHP wiring, publishes and loads that wiring from one explicit contained
path, and starts one application with one directly supplied `EgressResolver` and
one directly supplied `EgressTransport`.

Use it when an application needs a repeatable single-profile composition boundary
around the accepted Phase 15D runtime. Do not use it as a general HTTP client, a
resolver, a transport selector, a credential provider, a service registry, or a
provider integration. The package does not depend on the cURL adapter; an
application may pass any explicitly constructed implementation of the accepted
transport port.

## Workflow

`NetworkEgressApplicationBuildWorkflow::build()` accepts an existing
application-owned root, a relative existing output directory, one complete policy
source, and the exact artifact bytes. It validates currentness before publication,
derives one bounded profile identity, generates and validates canonical PHP,
publishes without clobbering different bytes, loads the exact published path, and
returns the current profile, generated evidence, published path, and a one-use
loaded handle.

Calling `LoadedNetworkEgressWiring::start()` with explicit resolver and transport
ports revalidates the exact profile and checks required transport capabilities
before resolution or transfer. The returned application accepts one
`NetworkEgressApplicationRunInput`, delegates one terminal synchronous operation
to a fresh `NetworkEgressRuntime`, and retires after success or failure. Phase 15D
alone owns every opened transport session and its exactly-once close attempt.

Generated PHP contains bounded identity and digest evidence only. It grants no
authority and has no effect when loaded beyond declaring one fixed class. Loading
trusted generated and in-process PHP is not sandboxing; callers must control the
application root, output directory, policy source, artifact bytes, resolver, and
transport.

See the [architecture guide](../../docs/architecture/network-egress-application-workflow.md),
[ADR 0160](../../docs/adr/0160-exact-current-single-profile-network-egress-application-workflow.md),
and the [Phase 15 roadmap](../../docs/architecture/phase-15-roadmap.md).
