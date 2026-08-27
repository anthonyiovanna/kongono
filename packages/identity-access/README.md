# Kongono Identity Access

`kongono/identity-access` provides Kongono's neutral Phase 9B identity and
access vocabulary. The package is inert: it validates and carries immutable
values but performs no authentication, authorization, lookup, or I/O.

## Dependency direction

The package depends only on `kongono/api-metadata`, `kongono/diagnostics`, and
PHP `>=8.5.0 <8.6.0`. No existing production package depends on it.

```text
api-metadata <- diagnostics
       ^            ^
       + identity-access
```

## Contract roles

- `PrincipalReference` pairs one exact opaque `PrincipalId` with one of Human,
  Service, Organization, System, or Agent. It is not a user record, role,
  tenant, credential, or permission.
- `AuthenticationOutcome` reports one of eight typed authentication outcome
  categories. Only Authenticated contains a principal; even that outcome
  grants no authorization.
- `AuthorizationDecision` is the inert Permit, Deny, or Indeterminate
  vocabulary. It evaluates no policy and does not enforce a capability.
- `Action` is an exact canonical application-owned operation name.
- `ResourceReference` pairs an exact canonical `ResourceType` with one opaque
  `ResourceId`; it does not load domain state.
- `SecurityContext` is explicitly Anonymous or Authenticated. It carries no
  ambient current user, credentials, roles, permissions, policy, session, or
  decision.

## Examples

```php
use Kongono\IdentityAccess\Action;
use Kongono\IdentityAccess\PrincipalId;
use Kongono\IdentityAccess\PrincipalKind;
use Kongono\IdentityAccess\PrincipalReference;
use Kongono\IdentityAccess\ResourceId;
use Kongono\IdentityAccess\ResourceReference;
use Kongono\IdentityAccess\ResourceType;
use Kongono\IdentityAccess\SecurityContext;

$principal = new PrincipalReference(
    PrincipalKind::Human,
    new PrincipalId('application-principal-42'),
);
$context = SecurityContext::authenticated($principal);
$action = new Action('invoice.read');
$resource = new ResourceReference(
    new ResourceType('invoice'),
    new ResourceId('application-invoice-7'),
);
```

These values only establish valid shape. The example performs no verification,
policy evaluation, permission check, domain loading, or enforcement.

## Bounds and nondisclosure

`PrincipalId` and `ResourceId` accept 1-512 bytes of valid UTF-8 without C0 or
C1 controls. They preserve bytes exactly: no trimming, normalization,
case-folding, hashing, parsing, lookup, or autoloading occurs. `Action` and
`ResourceType` accept 1-128 ASCII bytes matching
`^[a-z][a-z0-9]*(?:[._:-][a-z0-9]+)*$` exactly.

Invalid values produce package-owned non-disclosing exceptions. Rejected
bytes never enter messages, diagnostic contexts, or safe exception string
forms. Explicit identifier accessors do not make identifier bytes safe for
logs, diagnostics, display, or serialization.

All contract values are Experimental. The exception marker is Public; the
diagnostic catalog and exception base are Internal.

## Absent capabilities

This package provides no authenticator, verifier adapter, credential, password,
token, key, cryptography, strength or freshness fact, policy, evaluator,
decision receipt, session, cookie, CSRF behavior, middleware, HTTP adapter,
Composition wiring, protected-capability enforcement, provider, command,
schema, build artifact, cache, audit, telemetry, login flow, recovery,
delegation, or application integration. Phase 9C remains unimplemented.
