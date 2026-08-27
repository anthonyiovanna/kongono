# Kongono Security Policy Build

`kongono/security-policy-build` is the Experimental deterministic build boundary for one application-owned, rule-free security-policy identity source.

Use it when an application needs canonical, content-addressed evidence identifying the exact policy name, version, provenance, source bytes, and builder implementation used at startup. Do not use it to express roles, permissions, rules, claims, tenancy, ownership, or evaluator semantics. The artifact implements `AuthorizationPolicyEvidence`, but it never evaluates or grants access.

`SecurityPolicyBuildWorkflow::inspect()` and `check()` are write-free. `build()` publishes only one exact artifact atomically into an existing contained output directory, is idempotent for identical bytes, and never overwrites conflicting content. A checked `CurrentSecurityPolicy` remains immutable for the running application; source changes require a new build, check, and start.

The package reads no network, provider, credential, principal, request, environment, registry, or ambient application state. Diagnostics expose fixed safe labels only.

Phase 9H externally proves exact-current, stale, tampered, wrong-identity, and
read-only behavior through `composer acceptance:phase9`. That root-owned
harness adds no policy semantics or package runtime authority.
