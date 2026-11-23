# Changelog

All notable changes to the **Kongono Framework** are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.19.0] - 2026-11-22

### Added

- **Next-Gen AI Companion Triplets — Oshie, Chie & Hantei (`kongono/ai`, `kongono/cli`, `kongono/http`):**
  - **Self-Verifying Multi-Gate Build Loop (`kongo chie "..." --apply --verify`):** Validates PHP syntax (`php -l`), applies surgical search-and-replace patches (`action: "patch"` with strict unique-match validation) or new files, runs PHPStan + PHPUnit + `MigrationLinter` + `SecurityAuditor`, feeds secret-scrubbed failures back to Chie for bounded auto-repair (`--max-repairs`), and atomically rolls back snapshots on exhaustion (`--rollback-on-failure`).
  - **Per-File Interactive Diff Approval & Iterative Sessions (`--interactive`, `--continue`, `--progress`, `history`):** Approve or skip each unified diff interactively (`[y]es / [n]o / [a]ll / [q]uit`), persist secret-scrubbed gitignored local sessions in `var/kongono/companions/` (`CompanionSessionStore`), and auto-load team conventions from `AGENTS.md` / `.kongono/companions.md` inside `PromptGuard` boundaries.
  - **Shared Project & Live Schema Intelligence Tools (`CompanionTools`):** Equips both Chie and Hantei with governed read-only `list_files`, `read_file` (with line ranges), `search_code`, `inspect_graph`, and metadata-only `inspect_schema` (SQLite, PostgreSQL, MySQL — zero row data).
  - **Live Token Streaming (CLI & `/_kongono` Dev Studio SSE):** Added `--stream` to `kongo oshie` and `kongo hantei debug`, streaming multi-turn `kongo oshie --interactive`, and `POST /_kongono/api/{oshie,hantei}/stream` (`Response::sse()` + `SseEvent::json()`), plus OpenAI/Groq streaming `usage` extraction.
  - **Live Traces, Dead-Letter Jobs, `/_ops` Diagnose & Local Watcher:** Passed live `DevInspector` slow-request / N+1 traces into Oshie, added 1-click `POST /_kongono/api/hantei/trace/{id}` (root-cause diagnosis + PHPUnit 13 `TestExporter` regression test), `kongo hantei queue-failed`, `Gate`-protected (`ops.view`) `POST /_ops/queues/diagnose` (`DevStudioCompanions::opsJobDiagnoser()`), and `kongo hantei watch [--log=file] [--queue=default]`.
  - **Closed-Loop `hantei fix`, CI-Ready `hantei review` (SARIF 2.1.0), Verifiable Oshie Exercises & 15-Tool MCP:** Added `kongo hantei fix`, `kongo hantei review [--base=main] [--format=markdown|sarif]` + ready-to-copy [docs/ci-hantei-review.yml](docs/ci-hantei-review.yml), proof-based `kongo oshie --exercises` / `--exercise <id>` grading, and 3 new read-only MCP tools (`kongono_inspect_schema`, `kongono_hantei_diagnose`, `kongono_chie_preview`).
  - **Resilient Multi-Provider Failover, Per-Companion Routing, Cost Caps & Companion Evals:** Added `FallbackProvider` (transient 429/5xx retries + ordered failover), `AiClient::fromEnvForCompanion()` (`KONGONO_{OSHIE,CHIE,HANTEI}_{PROVIDER,MODEL}`), `CompanionTelemetry` with fail-closed `KONGONO_AI_MAX_COST_USD` / `--max-cost` caps, and `kongo eval --companions` (`CompanionEvalSuite`).

## [1.18.0] - 2026-11-22

### Added

- **AI-Native DX, Native Groq & Bundled Workflow Skills (`kongono/ai`, `kongono/core`, `kongono/cli`, `skills/`, `site/`):**
  - Added native `GroqProvider` (`llama-3.3-70b-versatile` on `https://api.groq.com/openai/v1`, streaming SSE + chat), `GROQ_API_KEY` / `GROQ_BASE_URL` / `KONGONO_AI_PROVIDER=groq` auto-detection in `AiClient::fromEnv()`, custom `$providerName` on `OpenAiProvider`, and automatic `gsk_*` key redaction in `SecretScrubber`.
  - Bundled 5 progressive-disclosure cross-agent `SKILL.md` workflow recipes (`kongono-feature-builder`, `kongono-live-and-bridge`, `kongono-auth-and-security`, `kongono-ai-and-rag`, `kongono-queues-and-ops`) and `kongo skills:install [--target=agents|claude|both] [--force]` (`SkillsInstaller`).
  - `kongo new` now automatically scaffolds `AGENTS.md`, `CLAUDE.md`, and `.mcp.json` (`php vendor/bin/kongo mcp`) and excludes local agent files in `.dockerignore`.
  - Published `llms.txt` and `llms-full.txt` on GitHub Pages (`https://anthonyiovanna.github.io/kongono/llms.txt`).
- **Complete Scaffolding Suite & Starter Presets (`kongono/cli`):**
  - `kongo auth:init [--mode=web|api|both] [--force]` (`AuthScaffolder`) — zero-bloat editable auth starter kit (`users` + `personal_access_tokens` migration, DTOs, `MemoryUserStore` + `PdoUserStore`, `WebAuthController` + CSRF + `LoginThrottler` + `.kgn.html` views, `ApiAuthController` revocable PATs, and `AuthFlowTest`).
  - `kongo make:resource <Name>` — full 7-file CRUD vertical slice (migration with soft deletes, `Create`/`Update`/`View` DTOs, `QueryBuilder` repository, REST controller, and `TestClient` test).
  - Added `kongo make:job`, `make:policy`, `make:middleware`, `make:factory`, `make:rule`, and `make:mail` (builder + `.kgn.html` view).
  - Added `--starter=minimal|api|saas` presets on `kongo new` (`api` wires PAT auth + rate limits + fail-closed `/_ops`; `saas` wires Live 2.5 + `CacheSessionStore` + `SessionAuth` + PATs + CSRF + `/_ops` + `.agents/skills`).
- **Cluster-Safe Cron Locking & Read/Write Database Routing (`kongono/jobs`, `kongono/database`):**
  - Added `ScheduledTask::withoutOverlapping(ttlSeconds: 300)` and `ScheduledTask::onOneServer(ttlSeconds: 60)` backed by `AtomicCacheInterface` (verified against live Redis 7) with owner-token release in `finally` and fail-closed `JobException` when no lock store is provided.
  - Added `ConnectionManager` (named connections, lazy factory closures, persistent-worker `resetSticky()`) and `ReadWriteConnection` (round-robin `SELECT`/read-only `WITH` replica routing, primary routing for DML/`FOR UPDATE`/transactions, and persistent-runtime-safe sticky-after-write).

## [1.17.0] - 2026-11-22

### Added

- **Explicit Data Ergonomics (`kongono/database`):**
  - Soft deletes via `Blueprint::softDeletes()` / `dropSoftDeletes()` and `QueryBuilder::withoutTrashed()`, `onlyTrashed()`, `softDelete()`, and `restore()`.
  - Zero-N+1 relation eager loading via `BatchLoader::hasMany()` and `BatchLoader::belongsTo()`.
  - Immutable typed `Factory::for()` with `state()`, `sequence()`, `count()`, `make()`, and `create()`.
- **Full-Text & Hybrid RRF Search (`kongono/database`, `kongono/ai`):**
  - `SearchEngineInterface`, `MemorySearchEngine`, and `PostgresFullTextSearch` (`tsvector`/`websearch_to_tsquery`/`ts_rank_cd` with XSS-safe plain-text `[[`/`]]` excerpts).
  - `HybridSearch` fusing keyword full-text search and `PgVectorStore` semantic embeddings via Reciprocal Rank Fusion (RRF).
- **Capability-Gated Live 2.5 Operations Dashboard (`kongono/http`, `kongono/core`):**
  - `OpsDashboardMiddleware` & `OpsDashboardRenderer` (`/_ops`) — zero-npm, strict-CSP Live 2.5 dashboard fail-closed on `Gate` (`ops.view` / `ops.manage`) displaying live queue depths, 1-click DLQ retry/flush, active `JobBatch` progress, `HealthCheck`, `Metrics::snapshot()`, and recent traces.

## [1.16.0] - 2026-11-22

### Added

- **Complete Zero-Vendor Auth Suite (`kongono/security`, `kongono/core`):**
  - `SessionAuth`, `SessionAuthMiddleware`, `#[Authenticated]`, and `Gate::before()` / `Gate::policy()` / `Gate::denies()`.
  - Revocable SHA-256 `PersonalAccessToken` across `MemoryPersonalAccessTokenStore`, `CachePersonalAccessTokenStore`, and `PdoPersonalAccessTokenStore`, with automatic `kgn_pat_` redaction in `SecretScrubber`.
  - `PasswordResetBroker`, `EmailVerifier`, `LoginThrottler`, and theft-detecting split selector/validator `RememberMeManager`.

## [1.15.0] - 2026-11-22

### Added

- **Global-Free Testing Suite (`kongono/http`, `kongono/database`, `kongono/security`, `kongono/core`):**
  - Expanded `TestClient` (`actingAs`, `withStatefulCookies`, `postMultipart`, `request`, `followRedirects`, `withLive`, `withBridge`), `TestUpload` (`fakePng`, `fakeJpeg`, `fakePdf`, `fakeText`, `fakeJson`), and rich `TestResponse` assertions.
  - `DatabaseTestHelper::inRollbackTransaction()` backed by nested SQL `SAVEPOINT` support in `PdoConnection::transaction()`.
  - SSRF-enforcing `SafeHttpClient::fake()` + `SafeHttpFake` and pure-DI `ClockInterface` / `SystemClock` / `FrozenClock`.

## [1.14.0] - 2026-11-22

### Added

- **Production Queue Power Features (`kongono/jobs`, `kongono/cli`):**
  - Dead-letter inspection & replay (`listFailed`, `retryFailed`, `flushFailed`) across Memory, PostgreSQL, and Lua-atomic Redis drivers + `kongo queue:failed`, `queue:retry`, and `queue:flush`.
  - Multi-queue priority draining (`Worker::run('critical,high,default')`), atomic `uniqueKey` deduplication, composable `JobMiddlewareInterface`, and parallel `JobBatch` + `BatchDispatcher` (`MemoryBatchStore`, `CacheBatchStore`).

## [1.13.0] - 2026-11-22

### Added

- **Streaming & Multi-Disk Storage (`kongono/storage`, `kongono/http`):**
  - `putStream()` / `getStream()` and `copy()` / `move()` (with native S3 `x-amz-copy-source`) across `LocalStorage`, `S3Storage`, and `MemoryStorage`, plus paginated S3 `ListObjectsV2`.
  - Magic-byte `UploadedFileStore`, `SignedLocalUrl`, `StorageDisks`, and `Response::download()` / `Response::fromStorage()`.

## [1.12.0] - 2026-11-22

### Added

- **Validation & HTML Form Ergonomics (`kongono/validation`, `kongono/http`, `kongono/view`):**
  - Custom `RuleInterface`, cross-field `ValidatableDtoInterface`, `#[ArrayOf]` (integrated across Validator, OpenAPI 3.1, and Bridge TypeScript generation), `#[Date]`, `#[Ip]`, `#[NotIn]`, and `#[RequiredIf]`.
  - Secret-scrubbed HTML PRG flash errors & input helpers (`@old()`, `@error()`).

## [1.11.0] - 2026-11-22

### Added

- **Schema Evolution & Safe Migrations (`kongono/database`, `kongono/cli`):**
  - `Schema::table()`, `Schema::rename()`, and `Blueprint` helpers (`uuid`, `json`, `decimal`, `foreignKey`, `foreignId`).
  - `NonTransactionalMigrationInterface`, `PretendConnection` + `kongo migrate --pretend`, and production-guarded `Migrator::fresh()` + `kongo migrate:fresh`.

## [1.10.1] - 2026-11-22

### Fixed

- Cache-busted `@liveScript` and `@bridgeScript` URLs with version query strings and `must-revalidate` headers on unversioned runtime script responses.

## [1.10.0] - 2026-11-22

### Added

- **Kongono Live 2.5 & Hybrid Bridge (`kongono/http`, `kongono/view`, `kongono/cli`):**
  - Safe cached arithmetic/ternary/pipe AST for zero-`eval()` strict-CSP signals, speculative hover/visible prefetch, out-of-band multi-morph (`kgn-oob`), `k-persist` + offline idempotent queue, drag-and-drop `kgn-sortable`, `@island` hybrid Bridge islands, `Response::adaptive()`, and `kongo ui:add`.

## [1.9.0] - 2026-11-22

### Added

- **Kongono Live 2.0 & Bridge (`kongono/http`, `kongono/view`, `kongono/cli`):**
  - Keyed DOM morphing, zero-`eval()` CSP-strict signals, optimistic UI, and View Transitions in `LiveMiddleware`.
  - First-class React, Vue, and Svelte **Bridge** (`Response::page()`, `BridgeMiddleware`, `kongo bridge:init`, `kongo bridge:types`).

## [1.8.1] - 2026-11-22

### Security

- Enforced PHP `>= 8.5.13` runtime CVE checks, hardened `php.ini` defaults in Dockerfiles/CI, and guarded FPM `413` `php://input` drain bounds.

## [1.8.0] - 2026-11-22

### Performance

- Eliminated 6 structural scaling bottlenecks: multi-node `RedisEventBus`, PDO/Redis auto-reconnect + prepared statement caching, zero-reflection handler dispatch caching, `--daemon` worker + `O(1)` Redis stats, chunked PCRE2 AOT `Router`, `pgvector` HNSW/GIN indexes, and `CacheFactory` / `StorageFactory`.

## [1.7.0] - 2026-11-22

### Security

- Added HTML5 `HtmlSanitizer` / `@safeHtml`, PostgreSQL `RowLevelSecurity`, hardened non-root read-only deployment blueprints, adversarial parser fuzzing (`composer fuzz`), `SECURITY.md`, SBOM, and SLSA provenance.

## [1.6.1] - 2026-11-22

### Security

- Upgraded `Encryptor` to versioned `v2.` HKDF AEAD keys with upgrade-safe legacy `v1.` decryption.

## [1.5.0] - [1.6.0] - 2026-11-22

### Security

- Completed Round-3 and Round-4 adversarial security hardening across all 11 packages, `examples/starter-app`, and CI.

## [1.4.0] - [1.4.4] - 2026-11-21

### Added & Security

- Added durable `Workflow`s (`step`, `sleep`, `waitForEvent`/`signal`), WebAuthn Passkeys (`PasskeyVerifier`), `MagicLinkAuth`, `PkceOAuthClient`, Time-Travel Replay & `TestExporter`, `QueryGuard`/`BatchLoader`, `kongo migrate:lint`, and `kongo eval` (`EvalRunner`).
- Completed Round-1 & Round-2 security hardening across all packages (including `AgentRunner` USD cost budgets, `ApiDocsRenderer` DOM sink escaping, Redis RESP DoS/TLS, SSE CR smuggling, S3/Curl lockdown, and `HttpCacheMiddleware` CDN key safety).

## [1.3.0] - 2026-11-21

### Added

- Added `kongo sdk:generate` (TypeScript, Swift 6, Kotlin, Dart), `AiThreatShieldMiddleware`, `PromptGuard`/`UntrustedInput`, `kongo audit --attack`, Fiber `Async`, AOT compiled `Router`, and initial `Kongono\Http\Live`.

## [1.2.0] - 2026-11-21

### Added

- Added `kongo audit`, `kongo deploy:init`, expanded 12-tool MCP server, fail-closed multi-tenant `TenantContext`, `RequiresApproval` / token & cost budgets on `AgentRunner`, and `SecretScrubber` v2.

## [1.1.3] - 2026-11-21

### Security

- **Expanded `SecretScrubber` Redaction Coverage (`kongono/core`):**
  - Automatically scrubs credentials embedded in URI/DSN connection strings (`postgres://`, `postgresql://`, `pgsql://`, `mysql://`, `mariadb://`, `redis://`, `rediss://`, `amqp://`, `amqps://`, `mongodb://`, `mongodb+srv://`, and `http(s)://user:pass@host`) while preserving scheme and host for diagnostics (`postgres://[REDACTED]@host:5432/db`).
  - Added redaction patterns for webhook signing secrets (`whsec_*`), GitHub fine-grained PATs (`github_pat_*`), and AWS access key IDs (`AKIA*`).

## [1.1.2] - 2026-11-21

### Fixed & Improved

- **Browser JavaScript Fix in `/docs` (`ApiDocsRenderer`) & `/_kongono` (`DevDashboardRenderer`):**
  - Switched inline `<script>` templates from double-quoted PHP heredoc (`<<<HTML`) to nowdoc (`<<<'JS'`) so JS `\n` string escapes are emitted verbatim instead of literal newlines (fixes `SyntaxError` in live browsers on both `/docs` and `/_kongono`).
  - Added expandable per-request SQL query details inside `/_kongono` Dev Studio trace rows.
- **`TestClient` & `TestResponse` DX Improvements (`kongono/http`):**
  - `TestResponse` now reads `Response::bodyString()` for streaming and `Response::sse()` responses.
  - Added `TestClient::postForm()` and `TestClient::patchJson()`.

## [1.1.1] - 2026-11-21

### Performance & Added

- **Persistent-Worker In-Memory View Caching (`kongono/view`, `kongono/http`):**
  - Compiled `.kgn.html` templates now emit OPcache-friendly pure static PHP closures (format `v2`, zero collision with prior caches) and invoke `opcache_invalidate()` when recompiled in dev mode.
  - Added opt-in `autoReload: false` mode on `ViewEngine::__construct()`, `ViewEngine::withAutoReload(false)`, `ViewEngine::isAutoReload()`, and `App::views(..., autoReload: false)` for production persistent workers (FrankenPHP, RoadRunner, `kongo serve`).
  - When `autoReload` is disabled, compiled template closures are cached per `ViewEngine` instance (and preloaded by `warmCache()`), eliminating per-request `realpath()`/`filemtime()`/`require` overhead (~116x faster `.kgn.html` rendering in `examples/benchmark.php`, jumping from ~508 req/s to ~59,000 req/s on PHP 8.5 CLI).
  - `App::instance(ViewEngine::class, ...)` now keeps `$app->view(...)` synchronized when replacing `ViewEngine` (e.g. via `withTranslator()`, `withVite()`, or `withAutoReload()`).
  - Updated `kongo new` scaffolding (`AppScaffolder`) so newly generated apps automatically wire `->views(autoReload: $debug)`.

## [1.1.0] - 2026-11-21

### Added

- **First-Class Vite Integration (`kongono/view`):**
  - `ViteManifest` supporting both hot-reload dev servers (`public/hot` -> `@vite/client`) and Vite 5/6/7 production manifests (`public/build/manifest.json` or `.vite/manifest.json`) with hashed CSS, `modulepreload` chunks, and `<script type="module">` tags.
  - `@vite('resources/js/app.js')` / `@vite([...])` template directive + `ViewEngine::withVite()`.
- **Complete Browser AI Trio inside `/_kongono` Dev Studio (`kongono/http`):**
  - Added debug-only `POST /_kongono/api/chie` preview endpoint and interactive **Chie — Project-Aware AI Builder (Safe Diff Preview)** panel right alongside Oshie, Hantei, and MCP (strictly read/diff-preview only in browser; writes remain terminal-confirmed via `--apply`).
- **Database Status & Interactive App REPL (`kongono/database`, `kongono/cli`):**
  - `Migrator::status()` and `kongo migrate:status` showing `[APPLIED]` vs `[PENDING]` migrations.
  - `kongo tinker [--app=path] [--eval="expr"]` — interactive PHP 8.5 REPL (and one-liner `--eval` script runner) pre-booted with `$app`, `$container`, `$router`, and `$graph`.

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
