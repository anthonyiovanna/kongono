# Kongono Framework (v1.1.0)

> **Build what comes next.**

[![CI](https://github.com/anthonyiovanna/kongono/actions/workflows/ci.yml/badge.svg)](https://github.com/anthonyiovanna/kongono/actions/workflows/ci.yml)
[![Latest Stable Version](https://img.shields.io/packagist/v/kongono/framework.svg?label=Packagist)](https://packagist.org/packages/kongono/framework)
![PHP 8.5+](https://img.shields.io/badge/PHP-8.5%2B-777BB4?logo=php&logoColor=white)
![Zero Symfony](https://img.shields.io/badge/Symfony-0%20runtime%20deps-16a34a)
![License MIT](https://img.shields.io/badge/License-MIT-0284c7)

Kongono is a modern, zero-legacy **PHP 8.5+** application framework built from the ground up — **no Symfony dependency, no PHP 7/8.1 shims, no magic global facades** — designed for persistent runtimes and featuring **three built-in AI companions**:

- **Oshie (`kongo oshie`)** — Interactive AI tutor that teaches Kongono grounded in your live app.
- **Chie (`kongo chie`)** — Project-aware AI builder & scaffolder that reads your `ApplicationGraph` and proposes clean PHP 8.5 code.
- **Hantei (`kongo hantei`)** — AI debugger, failure analyzer, and PHPUnit 13 test generator.

---

## Why Kongono?

- **Pure PHP 8.5+**: Strict types, `final readonly` classes, attributes, enums, zero legacy baggage.
- **Joyful, Zero-Magic DX**: Fluent + Attribute routing (`#[Get]`, `#[Post]`), automatic typed DTO validation (`#[Required]`, `#[Min]`, `#[Email]`), compiled `.kgn.html` views with `<x-component>` tags and automatic XSS escaping, full parameterized SQL QueryBuilder (`JOIN`, `GROUP BY`, `HAVING`, `RETURNING`).
- **Persistent-Runtime First**: Explicit `Kernel` operation scopes and automatic scoped DI container reset — works identically on PHP-FPM, `kongo serve`, FrankenPHP, and RoadRunner.
- **Hardened by Default**: Capability-based `Principal` (Human/Service/Agent/System), Argon2id password hashing, AEAD XChaCha20-Poly1305 `Encryptor` with key rotation (`ext-sodium`), constant-time HMAC `TokenSigner` & expiring `SignedUrl`, CSRF/CORS/SecurityHeaders/BearerAuth/RateLimit middleware, **SSRF-hardened `SafeHttpClient`** (DNS IP pinning + private IP blocking), **contained atomic `LocalStorage`** + AWS SigV4 `S3Storage`, and **fencing-token `PostgresQueueDriver`** + persistent `Worker` preserving `Uncertain` outcomes.
- **Multi-Provider AI & MCP Native**: `kongono/ai` speaks **OpenAI**, **Anthropic Claude**, **Google Gemini**, and local **Ollama** (all with live streaming) over pure PHP `ext-curl`, plus a built-in **Model Context Protocol (MCP) Server** (`kongo mcp` / `POST /mcp`) so Antigravity, Cursor, Claude Code, and Codex can inspect your live `ApplicationGraph` and OpenAPI 3.1 spec safely.

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

# Verify environment & live app health, generate APP_KEY, inspect routes/graph/OpenAPI/MCP
php bin/kongo doctor --app=examples/starter-app
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
| 1 | `packages/core` | `Kongono\Core\` | Persistent `Kernel`, PSR-11 `Container`, `Config`, `EnvLoader`, PSR-3 `Logger`, `MemoryCache`/`FileCache`/pure-PHP `RedisClient`+`RedisCache`, `FeatureManager`, `NotificationDispatcher`, `Retry`/`CircuitBreaker`, `Metrics`/`Tracer`, `HealthCheck`, `I18n\Translator`, `Diagnostics`, `EventDispatcher`, `ApplicationGraph` |
| 2 | `packages/http` | `Kongono\Http\` | PSR-7/15 `Request`/`Response`/`Cookie`, `Session`, `StaticFilesMiddleware`, `CompressionMiddleware`, `#[CacheControl]`/`HttpCacheMiddleware`, `#[Idempotent]`/`IdempotencyMiddleware`, `#[Feature]`, `SseEvent` + `EventBus`, `OpenApiGenerator`, `/docs` `ApiDocsRenderer`, `/_kongono` `DevDashboardMiddleware` (with live Oshie/Hantei Studio), `DevErrorRenderer`, fluent `TestClient`, `#[Prefix]`/`#[Middleware]`/`#[Get]` `Router`, persistent `App` |
| 3 | `packages/view` | `Kongono\View\` | Compiled `.kgn.html` templates (`warmCache()`), layouts, loops, `<x-component>` slots, `@t(...)` i18n, `@json`, automatic XSS escaping |
| 4 | `packages/validation` | `Kongono\Validation\` | PHP 8.5 attribute-driven typed DTO validation (nested DTOs, `BackedEnum`, `#[Uuid]`, `#[Between]`, `#[Confirmed]`, `#[Email]`, `#[Min]`/`#[Max]`, `#[In]`, `#[Regex]`) |
| 5 | `packages/database` | `Kongono\Database\` | Parameterized `QueryBuilder` (`JOIN`, `whereGroup()`, `upsert()`, `GROUP BY`, `HAVING`, `RETURNING`, `paginate()`, `getAs()`, `insertMany()`), `Schema`/`Blueprint`, `Repository`, `PdoConnection::onQuery()`, `Migrator` (`migrate` + `rollback`), `SeederInterface` |
| 6 | `packages/security` | `Kongono\Security\` | `Principal` (Human/Service/Agent), `Gate`, `#[Can]` + `AuthorizeMiddleware`, RFC 6238 `Totp` 2FA/MFA, `WebhookSigner` + `WebhookDispatcher` + `#[VerifyWebhook]`, Argon2id `PasswordHasher`, AEAD `Encryptor` (XChaCha20-Poly1305 + key rotation), HMAC `TokenSigner` & `SignedUrl`, CSRF/CORS/Bearer/RateLimit/SecurityHeaders middleware, SSRF-hardened `SafeHttpClient` |
| 7 | `packages/storage` | `Kongono\Storage\` | Contained atomic `LocalStorage`, pure PHP 8.5 AWS SigV4 `S3Storage` (S3/R2/MinIO + presigned URLs), and `MemoryStorage` |
| 8 | `packages/jobs` | `Kongono\Jobs\` | Background `Queue`, persistent `Worker`, `PostgresQueueDriver` (`FOR UPDATE SKIP LOCKED` + fencing tokens), pure-PHP `RedisQueueDriver`, `MemoryQueueDriver`, and cron `Scheduler` |
| 9 | `packages/mail` | `Kongono\Mail\` | Zero-dependency transactional email (`SmtpTransport` STARTTLS, `ResendTransport`, `LogTransport`, `MemoryTransport`, `MailNotificationChannel`, `.kgn.html` email views) |
| 10 | `packages/ai` | `Kongono\Ai\` | Multi-provider `AiClient` (OpenAI, Anthropic, Gemini, Ollama) with live streaming, vector embeddings (`MemoryVectorStore` + `PgVectorStore`), 5-line RAG `KnowledgeBase`, `structured()` DTO extraction, governed `AgentPrincipal`/`AgentRunner`, and native `McpServer` (JSON-RPC 2.0) |
| 11 | `packages/cli` | `Kongono\Cli\` | Unified `kongo` CLI (`new`, `serve`, `doctor`, `env:check`, `optimize`, `cache:clear`, `key:generate`, `work`, `schedule:run`, `migrate`, `migrate:rollback`, `db:seed`, `make:*`, `routes`, `graph`, `openapi`, `mcp`, `check`, `test`) + built-in **Oshie**, **Chie**, and **Hantei** |

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
- [Getting Started](docs/getting-started.md)
- [Architecture Overview](docs/architecture.md)
- [Built-In AI Companions & MCP Server](docs/ai-companions.md)
- [Production Deployment & Scaling Guide](docs/production-deployment.md)
- [Changelog](CHANGELOG.md) · [Contributing](CONTRIBUTING.md) · [Security Policy](SECURITY.md)

## License

MIT — see [LICENSE](LICENSE).
