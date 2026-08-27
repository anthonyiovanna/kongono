# Kongono Security Enforcement

`kongono/security-enforcement` is the Experimental stateless boundary that
places one exact authorization evaluation immediately around one protected
capability call.

`ProtectedCapability` declares its exact application-owned `Action`, exact
`ResourceReference`, and one generic result-producing execution method. Merely
constructing or obtaining a capability grants nothing. The enforcer first
requires the identical operation `SecurityContext`, equal Action, and equal
Resource Reference. It then evaluates once and invokes once only for exact
`Permit`.

`Deny`, `Indeterminate`, or any binding mismatch stops before evaluation or
execution as applicable. Authorization engine failures remain authorization
failures, and capability failures after Permit propagate unchanged. No retry,
receipt replay, delayed handle, cache, logging, I/O, ambient decision, current
user, or retained call state exists.

The package depends only on PHP, API Metadata, Authorization, Diagnostics, and
Identity Access. It is usable outside HTTP and does not depend on Security
Runtime, Authentication, transports, Composition, Kernel, persistence, or
Console. Its diagnostics expose only fixed Safe package, category, invariant,
and remediation labels.

Phase 9F adds no policy language, policy currentness artifact, role or
permission semantics, application workflow, or Phase 9G behavior.
