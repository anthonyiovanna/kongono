# Kongono Framework (v1.4.1)

> **Build what comes next.**

[![CI](https://github.com/anthonyiovanna/kongono/actions/workflows/ci.yml/badge.svg)](https://github.com/anthonyiovanna/kongono/actions/workflows/ci.yml)
[![Latest Stable Version](https://img.shields.io/packagist/v/kongono/framework.svg?label=Packagist)](https://packagist.org/packages/kongono/framework)
![PHP 8.5+](https://img.shields.io/badge/PHP-8.5%2B-777BB4?logo=php&logoColor=white)
![Zero Symfony](https://img.shields.io/badge/Symfony-0%20runtime%20deps-16a34a)
![License MIT](https://img.shields.io/badge/License-MIT-0284c7)

Kongono is a modern, zero-legacy **PHP 8.5+** application framework built from the ground up — **no Symfony dependency, no PHP 7/8.1 shims, no magic global facades** — designed for persistent runtimes, hardened against autonomous AI attacks, and featuring **three built-in AI companions**:

- **Oshie (`kongo oshie`)** — Interactive AI tutor that teaches Kongono grounded in your live app.
- **Chie (`kongo chie`)** — Project-aware AI builder & scaffolder that reads your `ApplicationGraph` and proposes clean PHP 8.5 code.
- **Hantei (`kongo hantei`)** — AI debugger, failure analyzer, and PHPUnit 13 test generator.

---

## Why Kongono?

- **Pure PHP 8.5+**: Strict types, `final readonly` classes, attributes, enums, zero legacy baggage.
- **Durable Workflows (`Kongono\Jobs\Workflow`)**: Built-in step memoization (`$wf->step()`), multi-day `sleep()`, and external event/human-approval signals (`waitForEvent()` + `signal()`) — Temporal/Inngest ergonomics with zero extra infrastructure.
- **Zero-Vendor Auth Suite (`Kongono\Security\Auth`)**: Native WebAuthn/FIDO2 Passkeys (`PasskeyVerifier` ES256/Ed25519), single-use HMAC `MagicLinkAuth`, and PKCE S256 `PkceOAuthClient` (GitHub, Google, OIDC over `SafeHttpClient`).
- **One Backend, Every Platform (`kongo sdk:generate`)**: Generate typed **TypeScript** (React/Next.js/React Native), **Swift 6** (iOS/macOS), **Kotlin** (Android), and **Dart** (Flutter) client SDKs straight from your PHP 8.5 attribute routes and readonly DTOs.
- **Time-Travel Dev Studio + N+1 Guard + Zero-Downtime `migrate:lint`**: Replay captured requests in-process (`/_kongono/api/replay/{id}`), export 1-click PHPUnit 13 regression tests (`TestExporter`), catch N+1 SQL storms (`QueryGuard` + `BatchLoader`), and lint migrations before deploy (`kongo migrate:lint`).
- **Persistent-Runtime Speed (`Async::all()` + AOT Router)**: Native PHP 8.5 Fiber concurrency (`Async::all`, `Async::race`, `Async::map`, `Async::allSettled`), parallel SSRF-pinned `SafeHttpClient::requestMany()` (`curl_multi`), $O(1)$ static route dispatch + pre-compiled regexes, and `kongo optimize` emitting OPcache-ready `var/cache/routes.compiled.php`.
- **Zero-Build Hypermedia & SSE Reactivity (`Kongono\Live`)**: ~3 KB strict-CSP-compliant runtime (`LiveMiddleware`, `@liveScript`, `Request::isLive()`, `Response::fragment()`) with declarative `kgn-get`/`kgn-post`/`kgn-target`/`kgn-swap`/`kgn-stream` live DOM updates and automatic CSRF forwarding.
- **Hardened Against Autonomous AI Attacks + Built-in AI Eval Suite (`kongo eval`)**:
  - **`AiThreatShieldMiddleware`** — recon honeypot traps (`/.env`, `/wp-login.php`, `/actuator/env`), hidden form field traps, 404 probe burst auto-banning, and cost-aware compute budget limiting.
  - **`PromptGuard` & `UntrustedInput`** — structural `<untrusted_data>` isolation, prompt-injection detection, and cryptographic Canary Token leakage fail-closed blocking.
  - **`kongo eval` (`EvalRunner`)** — "PHPUnit for AI" asserting structured DTO accuracy, token/latency budgets, and a built-in 12-vector adversarial prompt-injection red-team battery.
  - **`kongo audit --attack`** — graph-driven security audit + automated in-process red-team simulations (SSRF, PromptGuard canary, path traversal, security headers, error nondisclosure).
  - **Fail-Closed Row-Level Multi-Tenancy (`TenantContext` + `QueryBuilder::forTenant()`)**, **Human-in-the-Loop AI Approval & Token/Cost Budgets (`RequiresApproval`, `AgentPrincipal`)**, Argon2id, XChaCha20-Poly1305 AEAD `Encryptor`, `SafeHttpClient`, RFC 6238 `Totp`, and `WebhookSigner`.
- **Multi-Provider AI & MCP Native**: `kongono/ai` speaks **OpenAI**, **Anthropic Claude**, **Google Gemini**, and local **Ollama** (all with live streaming) over pure PHP `ext-curl`, plus a built-in **Model Context Protocol (MCP) Server** (`kongo mcp` / `POST /mcp`) exposing `kongono_graph`, `kongono_openapi`, `kongono_sdk_generate`, `kongono_audit`, `kongono_test_route`, and `kongono_deploy_preview` safely to AI coding agents.

---

## Installation & Quick Start

```bash
# Create a brand-new Kongono app (includes /_kongono Dev Studio, /docs, /health, /mcp & FrankenPHP Dockerfile)
composer create-project kongono/app my-app
cd my-app
composer dev
```

Or install `kongono/framework` directly into an existing project:

```bash
composer require kongono/framework
```

Or run directly from this repository:

```bash
# Run the full starter app locally (includes /_kongono Dev Studio, /docs API Explorer, /health, /openapi.json, /metrics, /mcp)
php bin/kongo serve --app=examples/starter-app --port=8000

# Verify environment, run red-team security audit, generate typed mobile/web SDKs & deploy blueprints
php bin/kongo doctor --app=examples/starter-app
php bin/kongo audit --attack --app=examples/starter-app
php bin/kongo eval --redteam --app=examples/starter-app
php bin/kongo migrate:lint --app=examples/starter-app
php bin/kongo sdk:generate --lang=ts --app=examples/starter-app --out=sdk/client.ts
php bin/kongo sdk:generate --lang=swift --app=examples/starter-app --out=sdk/Client.swift
php bin/kongo sdk:generate --lang=kotlin --app=examples/starter-app --out=sdk/Client.kt
php bin/kongo sdk:generate --lang=dart --app=examples/starter-app --out=sdk/client.dart
php bin/kongo deploy:init --app=examples/starter-app --domain=app.example.com
php bin/kongo optimize --app=examples/starter-app
php bin/kongo key:generate
php bin/kongo routes --app=examples/starter-app
php bin/kongo graph --app=examples/starter-app
php bin/kongo openapi --app=examples/starter-app
php bin/kongo mcp --app=examples/starter-app
php bin/kongo work --app=examples/starter-app

# Use the three built-in AI companions
php bin/kongo oshie --lessons
php bin/kongo oshie --interactive
php bin/kongo oshie "How do I protect an API route with BearerAuthMiddleware?"
php bin/kongo chie "Add a /health JSON endpoint"
php bin/kongo hantei check
```

To enable live AI responses for Oshie, Chie, and Hantei, set any one environment variable:
- `OPENAI_API_KEY` (or `OPENAI_BASE_URL` for OpenAI-compatible gateways)
- `ANTHROPIC_API_KEY`
- `GEMINI_API_KEY`
- `OLLAMA_HOST=http://127.0.0.1:11434` (free, local, offline!)

---

## Complete 11-Package Architecture

| # | Package | Namespace | Purpose |
| --- | --- | --- | --- |
| 1 | `packages/core` | `Kongono\Core\` | Persistent `Kernel`, PSR-11 `Container`, Fiber `Async` (`all`/`race`/`map`/`allSettled`), `Config`, `EnvLoader`, PSR-3 `Logger`, `MemoryCache`/`FileCache`/pure-PHP `RedisClient`+`RedisCache`, `FeatureManager`, `NotificationDispatcher`, `Retry`/`CircuitBreaker`, `Metrics`/`Tracer`, `HealthCheck`, `I18n\Translator`, `Diagnostics`/`SecretScrubber`, `EventDispatcher`, `ApplicationGraph` |
| 2 | `packages/http` | `Kongono\Http\` | PSR-7/15 `Request`/`Response`/`Cookie`, `Kongono\Live` (`LiveMiddleware`, `Request::isLive()`, `Response::fragment()`), `Session`, `StaticFilesMiddleware`, `CompressionMiddleware`, `#[CacheControl]`/`HttpCacheMiddleware`, `#[Idempotent]`/`IdempotencyMiddleware`, `#[Feature]`, `SseEvent` + `EventBus`, `OpenApiGenerator`, `/docs` `ApiDocsRenderer`, `/_kongono` `DevDashboardMiddleware`, `DevErrorRenderer`, fluent `TestClient`, $O(1)$ static + AOT compiled `Router`, persistent `App` |
| 3 | `packages/view` | `Kongono\View\` | Compiled `.kgn.html` closure templates (`warmCache()`, production `autoReload: false`), `@liveScript`, `@vite(...)`, layouts, loops, `<x-component>` slots, `@t(...)` i18n, `@json`, automatic XSS escaping |
| 4 | `packages/validation` | `Kongono\Validation\` | PHP 8.5 attribute-driven typed DTO validation (nested DTOs, `BackedEnum`, `#[Uuid]`, `#[Between]`, `#[Confirmed]`, `#[Email]`, `#[Min]`/`#[Max]`, `#[In]`, `#[Regex]`) |
| 5 | `packages/database` | `Kongono\Database\` | Parameterized `QueryBuilder` (fail-closed `forTenant()`/`TenantContext`, `JOIN`, `whereGroup()`, `upsert()`, `GROUP BY`, `HAVING`, `RETURNING`, `paginate()`, `getAs()`, `insertMany()`), `Schema`/`Blueprint`, `Repository`, `PdoConnection::onQuery()`, `Migrator` (`migrate` + `rollback`), `SeederInterface` |
| 6 | `packages/security` | `Kongono\Security\` | `AiThreatShieldMiddleware` (honeypots + recon burst banning + cost budgets), `Principal` (Human/Service/Agent), `Gate`, `#[Can]` + `AuthorizeMiddleware`, RFC 6238 `Totp` 2FA/MFA, `WebhookSigner` + `WebhookDispatcher` + `#[VerifyWebhook]`, Argon2id `PasswordHasher`, AEAD `Encryptor` (XChaCha20-Poly1305 + key rotation), HMAC `TokenSigner` & `SignedUrl`, CSRF/CORS/Bearer/RateLimit/SecurityHeaders middleware, SSRF-hardened `SafeHttpClient` (+ parallel `requestMany()`) |
| 7 | `packages/storage` | `Kongono\Storage\` | Contained atomic `LocalStorage`, pure PHP 8.5 AWS SigV4 `S3Storage` (S3/R2/MinIO + presigned URLs), and `MemoryStorage` |
| 8 | `packages/jobs` | `Kongono\Jobs\` | Background `Queue`, persistent `Worker`, `PostgresQueueDriver` (`FOR UPDATE SKIP LOCKED` + fencing tokens), pure-PHP `RedisQueueDriver`, `MemoryQueueDriver`, and cron `Scheduler` |
| 9 | `packages/mail` | `Kongono\Mail\` | Zero-dependency transactional email (`SmtpTransport` STARTTLS, `ResendTransport`, `LogTransport`, `MemoryTransport`, `MailNotificationChannel`, `.kgn.html` email views) |
| 10 | `packages/ai` | `Kongono\Ai\` | Multi-provider `AiClient` (OpenAI, Anthropic, Gemini, Ollama) with live streaming, `PromptGuard` + `UntrustedInput` (canary tokens + injection detection), vector embeddings (`MemoryVectorStore` + `PgVectorStore`), 5-line RAG `KnowledgeBase`, `structured()` DTO extraction, governed `AgentPrincipal`/`RequiresApproval`/`AgentRunner` (human approval + token/USD budgets), and native `McpServer` (JSON-RPC 2.0) |
| 11 | `packages/cli` | `Kongono\Cli\` | Unified `kongo` CLI (`new`, `serve`, `tinker`, `doctor`, `audit [--attack]`, `sdk:generate`, `deploy:init`, `env:check`, `optimize`, `cache:clear`, `key:generate`, `work`, `schedule:run`, `migrate`, `migrate:rollback`, `db:seed`, `make:*`, `routes`, `graph`, `openapi`, `mcp`, `check`, `test`) + built-in **Oshie**, **Chie**, and **Hantei** |

*(Historical Phase 0–23 architecture archives remain preserved on the `main` and `archive/phase-23c-wip` branches.)*

---

## Quality Checks

```bash
composer dump-autoload
composer cs        # PHP-CS-Fixer
composer analyse   # PHPStan level 9 + strict rules
composer test      # PHPUnit 13
```

## Documentation

- **[Official Documentation Site (GitHub Pages)](https://anthonyiovanna.github.io/kongono/)**
- **[Community Discussions](https://github.com/anthonyiovanna/kongono/discussions)**
- [Getting Started](docs/getting-started.md)
- [Architecture Overview](docs/architecture.md)
- [Built-In AI Companions & MCP Server](docs/ai-companions.md)
- [Production Deployment & Scaling Guide](docs/production-deployment.md)
- [Persistent-Worker Benchmark](examples/benchmark.php) (`php examples/benchmark.php`)
- [Changelog](CHANGELOG.md) · [Contributing](CONTRIBUTING.md) · [Security Policy](SECURITY.md)

## License

MIT — see [LICENSE](LICENSE).
