---
name: kongono-auth-and-security
description: >-
  Workflow for implementing authentication, authorization, revocable Personal
  Access Tokens, passwordless/MFA flows, multi-tenant isolation, and SSRF/XSS
  hardening in Kongono Framework (PHP 8.5+). Use when adding login, sessions,
  API tokens, roles/policies, passkeys, webhooks, outbound HTTP, or resolving
  security audit findings.
---

# Kongono Auth & Security Workflow

Every Kongono security primitive is **fail-closed by default** and free of global static state. Never introduce `Auth::user()`, `$_SESSION`, or raw `curl_exec()` calls.
To scaffold an editable zero-bloat auth starter kit immediately, run `php vendor/bin/kongo auth:init --mode=both` (or `web` / `api`), or start a new project with `kongo new my-app --starter=api` or `--starter=saas`.

## Step 1: Choose the Right Authentication Layer (`Kongono\Security`)

- **Browser Cookie Sessions:**
  - Wire `SessionMiddleware` (backed by `CacheSessionStore` with `RedisCache` in multi-node production) + `Kongono\Security\Auth\SessionAuth` + `Kongono\Security\Middleware\SessionAuthMiddleware`.
  - Protect routes with `#[Authenticated]` (`Kongono\Security\Attributes\Authenticated`) and inject `Kongono\Security\Principal` directly into controller actions.
  - Pair login forms with `LoginThrottler`, `PasswordHasher` (Argon2id), `CsrfMiddleware`, `PasswordResetBroker`, `EmailVerifier`, and split selector/validator `RememberMeManager` (automatically revokes the full series if a stolen cookie is replayed).
- **Revocable API Tokens (PATs):**
  - Use `Kongono\Security\Auth\PersonalAccessToken` with `PdoPersonalAccessTokenStore` (or `CachePersonalAccessTokenStore` / `MemoryPersonalAccessTokenStore` in tests) + `BearerAuthMiddleware`.
  - Tokens are SHA-256 hashed at rest; the `kgn_pat_` prefix is auto-scrubbed by `SecretScrubber`.
- **Passwordless & MFA:**
  - Native WebAuthn/FIDO2 Passkeys: `PasskeyVerifier` (ES256/Ed25519).
  - Magic links: `MagicLinkAuth`.
  - OAuth/OIDC: `PkceOAuthClient` (S256 over `SafeHttpClient`).
  - TOTP 2FA: `Kongono\Security\Totp` (RFC 6238 + recovery codes) — always wire an `AtomicCacheInterface` via `new Totp(replayCache: $cache)` or call `$totp->verifyOnce($secret, $code, $atomicCache)` so codes cannot be replayed within the drift window.

## Step 2: Enforce Fail-Closed Authorization (`Gate` & `#[Can]`)

1. Register abilities and policies on `Kongono\Security\Gate`:
   - `$gate->define('posts.write', fn(Principal $p, mixed $resource = null): bool => ...)`
   - `$gate->policy(Post::class, PostPolicy::class)`
   - `$gate->before(fn(Principal $p, string $ability): ?bool => ...)` (return `null` to defer to normal checks; `true`/`false` short-circuits).
2. Attach `AuthorizeMiddleware` and decorate routes with `#[Can('posts.write')]`, or check explicitly with `$gate->allows(...)` / `$gate->denies(...)`.

## Step 3: Least-Privilege Capabilities, Multi-Tenancy, Crypto & Outbound Egress

- **Least-Privilege Route Capabilities:** Mount `Kongono\Security\Middleware\CapabilityMiddleware` and annotate routes with `#[ReadOnlyDb]`, `#[NoOutboundHttp]`, `#[MaxQueries(N)]`, and `#[MaxAiCost(maxUsd: ...)]` (`Kongono\Security\Attributes\*`) so unauthorized DB writes, SSRF egress, or query explosions fail closed before execution.
- **Executable Policies (`app/invariants.php`):** Enforce route-level security invariants (e.g., `/api/admin/*` must carry `#[Authenticated]`, `/webhooks/*` must verify signatures) via `Kongono\Cli\Policy\Invariant` and `php vendor/bin/kongo policy:check`.
- **Multi-Tenant Isolation:** Scope every tenant query via `QueryBuilder::forTenant($tenant)` and enable PostgreSQL `Kongono\Database\RowLevelSecurity`. Prove zero BOLA/IDOR leaks with `php vendor/bin/kongo prove:isolation`.
- **Encryption at Rest:** Use `Kongono\Security\Encryptor` (HKDF v2 `XChaCha20-Poly1305` AEAD). Pass `previousKeys: [$oldKey]` for zero-downtime key rotation.
- **Outbound HTTP & Webhooks:** Always use `Kongono\Security\Egress\SafeHttpClient` (DNS IP pinning via `CURLOPT_RESOLVE`, blocks loopback/private/metadata IPs). Use `WebhookDispatcher` outbound and `#[VerifyWebhook]` inbound.
- **Bot & AI Recon Defense:** Mount `SecurityHeadersMiddleware` and `AiThreatShieldMiddleware` for public web/API deployments.

## Step 4: Verify Security & Nondisclosure

1. Write auth tests using `(new TestClient($app))->actingAs($principal)->withStatefulCookies()` and test both authorized (`200`/`201`) and unauthorized (`401`/`403`) paths.
2. Confirm secrets (`sk-*`, `sk-ant-*`, `AIza*`, `gsk_*`, `kgn_pat_*`, DSNs, JWTs) never appear in error payloads or logs, and that `APP_DEBUG=false` responses return only a vague message + `KGN-*` Reference ID.
3. Run the automated security, isolation, policy, and unified proof gates and confirm **100/100 PASS**:
   ```bash
   php vendor/bin/kongo prove
   php vendor/bin/kongo policy:check
   php vendor/bin/kongo prove:isolation
   php vendor/bin/kongo audit --attack --strict
   php vendor/bin/kongo test
   ```
