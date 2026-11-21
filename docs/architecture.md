# Kongono v1 Architecture & Deployment

## 11 Cohesive, Zero-Legacy Packages

Kongono keeps strict, clean package boundaries across 11 focused PHP 8.5+ packages with **zero Symfony runtime dependencies**:

1. **`kongono/core`** — Persistent `Kernel` + `OperationScope`, PSR-11 `Container` with `resetScope()`, `Config`, `EnvLoader`, PSR-3 secret-scrubbing `Logger`, `MemoryCache`/`FileCache`/pure-PHP `RedisClient` + `RedisCache`, `FeatureManager`, multi-channel `NotificationDispatcher`, `Retry`/`CircuitBreaker`, `Metrics`/`Tracer`, `HealthCheck`, `I18n\Translator`, nondisclosing `Diagnostics`, PSR-14 `EventDispatcher`, and live `ApplicationGraph`.
2. **`kongono/http`** — Native PSR-7/15 `Request`/`Response`/`Cookie`, `Session` + `SessionMiddleware`, `StaticFilesMiddleware`, `CompressionMiddleware`, `HttpCacheMiddleware`/`#[CacheControl]`, `IdempotencyMiddleware`/`#[Idempotent]`, `#[Feature]`, `#[Prefix]`, `#[Middleware]`, `EventBus` SSE pub/sub, `OpenApiGenerator`, zero-CDN `/docs` `ApiDocsRenderer`, `/_kongono` `DevDashboardMiddleware`, `DevErrorRenderer`, fluent `TestClient`, attribute + fluent `Router`, and persistent `App`.
3. **`kongono/view`** — Compiled `.kgn.html` HTML templates (`warmCache()`) with XSS auto-escaping, layouts, loops, `@t(...)` i18n, `@json`, and nested `<x-component>` slots.
4. **`kongono/validation`** — Attribute-driven PHP 8.5 typed DTO validation (`#[Required]`, `#[Min]`, `#[Max]`, `#[Between]`, `#[Email]`, `#[Url]`, `#[Uuid]`, `#[In]`, `#[Regex]`, `#[Confirmed]`), native `BackedEnum` coercion, and recursive nested DTO validation.
5. **`kongono/database`** — Parameterized `QueryBuilder` (`JOIN`, `WHERE`/`whereGroup()`, `upsert()`, `GROUP BY`, `HAVING`, `RETURNING`, `paginate()`, `getAs()`, `insertMany()`), `Schema`/`Blueprint` DDL builder, `Repository`, `PdoConnection::onQuery()`, `Migrator` (`migrate` + `rollback`), and `SeederInterface`.
6. **`kongono/security`** — `Principal` (`Human`/`Service`/`Agent`/`System`), fail-closed `Gate`, `#[Can]` + `AuthorizeMiddleware`, Argon2id `PasswordHasher`, AEAD `XChaCha20-Poly1305` `Encryptor` (`ext-sodium` + key rotation), RFC 6238 `Totp` 2FA/MFA, HMAC `TokenSigner` & `SignedUrl`, SSRF-hardened DNS-pinning `SafeHttpClient`, signed Webhooks (`WebhookSigner`, `WebhookDispatcher`, `#[VerifyWebhook]`, `WebhookNotificationChannel`), and CSRF/CORS/Bearer/RateLimit/SecurityHeaders middleware.
7. **`kongono/storage`** — Contained atomic SHA-256 versioned `LocalStorage`, pure-PHP AWS SigV4 `S3Storage` (S3/R2/MinIO + presigned URLs), and `MemoryStorage`.
8. **`kongono/jobs`** — Background `Queue`, persistent `Worker` (preserving `JobOutcome::Uncertain`), `PostgresQueueDriver` (`FOR UPDATE SKIP LOCKED` + fencing tokens), pure-PHP `RedisQueueDriver`, `MemoryQueueDriver`, and cron `Scheduler`.
9. **`kongono/mail`** — Zero-dependency transactional email (`SmtpTransport` STARTTLS, `ResendTransport`, `LogTransport`, `MemoryTransport`, `MailNotificationChannel`, and compiled `.kgn.html` email views).
10. **`kongono/ai`** — Multi-provider `AiClient` (OpenAI, Anthropic Claude, Google Gemini, local Ollama) with live streaming on all 4 providers, vector embeddings (`MemoryVectorStore` + `PgVectorStore`), 5-line RAG `KnowledgeBase`, `structured()` DTO extraction, capability-governed `AgentPrincipal`/`AgentRunner`, and native JSON-RPC 2.0 `McpServer`.
11. **`kongono/cli`** — Unified `kongo` CLI and built-in **Oshie**, **Chie**, and **Hantei** AI companions plus `KongonoMcpFactory`.

## Persistent-Runtime First Lifecycle

Kongono never stores per-request state in static properties, `$_SESSION`, or global facades. Every HTTP request and background job executes inside `Kernel::runOperation()` (running deferred cleanups in LIFO order) and resets scoped DI container instances (`Container::resetScope()`) on exit.

See [Production Deployment & Scaling Guide](production-deployment.md) for FrankenPHP workers, `kongo optimize`, PostgreSQL/`pgvector`, and Redis scaling.
