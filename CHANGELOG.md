# Changelog

All notable changes to the **Kongono Framework** are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-11-21

### Added

- **Zero-Legacy PHP 8.5+ Core (`kongono/core`):**
  - Persistent-runtime-first `Kernel` + `OperationScope` LIFO cleanup, PSR-11 `Container` with `resetScope()`, `Config`, `EnvLoader`, PSR-3 secret-scrubbing `Logger`, PSR-14 `EventDispatcher`, nondisclosing `Diagnostics` (`SecretScrubber`), and live `ApplicationGraph`.
  - `MemoryCache`, `FileCache`, and zero-dependency pure-PHP `RedisClient` (RESP2/RESP3 TCP/TLS) + `RedisCache`.
  - `FeatureManager` (resolvers, deterministic percentage rollouts, cache-backed overrides), multi-channel `NotificationDispatcher`, `Retry` & `CircuitBreaker` resilience primitives, `HealthCheck`, `I18n\Translator`, and Prometheus `Metrics` + `Server-Timing` `Tracer`.
- **Native PSR-7/15 HTTP Layer & Dev Studio (`kongono/http`):**
  - `Request`, `Response` (`html`, `json`, `text`, `redirect`, `stream`, `sse`), `Cookie`, `UploadedFile`, `Session` + flash + rotation, `StaticFilesMiddleware` (`ETag`/`304`), `CompressionMiddleware` (`gzip`/`deflate`), `HttpCacheMiddleware` & `#[CacheControl]`, `IdempotencyMiddleware` & `#[Idempotent]`, `#[Feature]`, `#[Prefix]`, `#[Middleware]`, and fluent + attribute `Router`.
  - Real-time `EventBus` with `Last-Event-ID` SSE replay, automatic OpenAPI 3.1.0 generation (`OpenApiGenerator`), zero-CDN interactive `/docs` API Explorer (`ApiDocsRenderer`), rich code-frame `DevErrorRenderer`, fluent `TestClient`, and zero-CDN `/_kongono` **Kongono Dev Studio** (live request/SQL traces, graph inspector, and interactive Oshie/Hantei/MCP workbench; auto-disabled in production).
- **Compiled `.kgn.html` Views (`kongono/view`):**
  - Context-aware XSS auto-escaping, `@extends`/`@section`/`@yield`, `@include`, `@if`/`@unless`/`@isset`/`@foreach`/`@forelse`, `@t(...)` i18n, `@json`, nested `<x-component>` slots, and `warmCache()` pre-compilation.
- **Typed PHP 8.5 Validation (`kongono/validation`):**
  - Attribute-driven readonly DTO validation (`#[Required]`, `#[Min]`, `#[Max]`, `#[Between]`, `#[Email]`, `#[Url]`, `#[Uuid]`, `#[In]`, `#[Regex]`, `#[Confirmed]`), native `BackedEnum` coercion, and recursive nested DTO validation with dotted error keys.
- **Parameterized SQL Database Layer (`kongono/database`):**
  - `QueryBuilder` (`JOIN`/`LEFT JOIN`, `WHERE`/`OR`/`IN`/`NULL`/`LIKE`/`BETWEEN`, parenthesized `whereGroup()`, multi-dialect `upsert()`, `GROUP BY`, `HAVING`, `ORDER BY`, `LIMIT`/`OFFSET`, `RETURNING`, `paginate()`, `getAs()` DTO hydration, `insertMany()`), `Schema`/`Blueprint`, `Repository`, `PdoConnection::onQuery()`, `Migrator` (`migrate` + `rollback`), and `SeederInterface`.
- **Hardened Security (`kongono/security`):**
  - Capability-based `Principal` (`Human`/`Service`/`Agent`/`System`), fail-closed `Gate`, declarative `#[Can]` + `AuthorizeMiddleware`, Argon2id `PasswordHasher`, AEAD `XChaCha20-Poly1305` `Encryptor` (`ext-sodium`) with key rotation, RFC 6238 `Totp` 2FA/MFA + recovery codes, HMAC `TokenSigner` & expiring `SignedUrl`, SSRF-hardened DNS-pinning `SafeHttpClient`, signed Webhooks (`WebhookSigner`, `WebhookDispatcher`, `#[VerifyWebhook]`, `WebhookNotificationChannel`), and CSRF/CORS/Bearer/RateLimit/SecurityHeaders middleware.
- **Storage, Jobs & Transactional Mail (`kongono/storage`, `kongono/jobs`, `kongono/mail`):**
  - Contained atomic `LocalStorage`, pure-PHP AWS SigV4 `S3Storage` (S3/R2/MinIO + presigned URLs), and `MemoryStorage`.
  - Background `Queue`, persistent `Worker` (preserving `JobOutcome::Uncertain`), `PostgresQueueDriver` (`FOR UPDATE SKIP LOCKED` + fencing tokens), pure-PHP `RedisQueueDriver`, `MemoryQueueDriver`, and cron `Scheduler`.
  - Zero-dependency `Mailer` (`SmtpTransport` STARTTLS, `ResendTransport`, `LogTransport`, `MemoryTransport`, `MailNotificationChannel`, and `.kgn.html` email views).
- **Multi-Provider AI, RAG & Native MCP (`kongono/ai`):**
  - Unified `AiClient` across **OpenAI**, **Anthropic Claude**, **Google Gemini**, and local **Ollama** (all 4 with live streaming), `structured()` validated DTO extraction, vector embeddings (`MemoryVectorStore` + `PgVectorStore`) & 5-line RAG `KnowledgeBase`, governed `AgentPrincipal`/`AgentRunner`, and native JSON-RPC 2.0 `McpServer`.
- **Unified `kongo` CLI & Three Built-in AI Companions (`kongono/cli`):**
  - **Oshie** (`kongo oshie`), **Chie** (`kongo chie`), and **Hantei** (`kongo hantei`), plus `new`, `serve`, `doctor`, `env:check`, `optimize`, `cache:clear`, `key:generate`, `routes`, `graph`, `openapi`, `mcp`, `work`, `schedule:run`, `migrate`, `migrate:rollback`, `db:seed`, `make:*`, `check`, and `test`.
