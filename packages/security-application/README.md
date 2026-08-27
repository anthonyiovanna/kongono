# Kongono Security Application

`kongono/security-application` provides the Experimental supported browser-session protected-capability workflow.

`BrowserSessionProtectedApplication` begins one `BrowserSessionSecurityOperation`, uses its exact operation-scoped `SecurityContext`, constructs one `AuthorizationRequest` from the capability's exact Action and Resource plus one immutable checked policy artifact, invokes the existing enforcer once, and closes the operation once on every outcome. Only an exact application-owned evaluator `Permit` at the enforcer can execute the capability.

The workflow preserves lower-boundary authentication, authorization, capability, and cleanup failures rather than wrapping or stringifying them. It retains only immutable collaborators and current policy between calls, so sequential and reentrant calls share no request, context, decision, result, or failure state.

This package supplies no policy semantics, provider, session store, credential, route, middleware grant, retry, ambient current user, audit, telemetry, or deployment behavior.

Phase 9H externally proves this unchanged public workflow with synthetic
application-owned fixtures through `composer acceptance:phase9`. The harness is
root test evidence, not package behavior or security certification.
