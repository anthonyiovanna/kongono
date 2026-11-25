# <img src="site/assets/logo.svg" width="32" height="32" align="absmiddle" alt="Kongono"> Kongono Framework (v1.20.0)

> **Build what comes next.**

[![CI](https://github.com/anthonyiovanna/kongono/actions/workflows/ci.yml/badge.svg)](https://github.com/anthonyiovanna/kongono/actions/workflows/ci.yml)
[![Latest Stable Version](https://img.shields.io/packagist/v/kongono/framework.svg?label=Packagist)](https://packagist.org/packages/kongono/framework)
[![Discord](https://img.shields.io/badge/Discord-Join%20Community-5865F2?logo=discord&logoColor=white)](https://discord.gg/SXWjgBGwrZ)
![PHP 8.5.13+](https://img.shields.io/badge/PHP-8.5.13%2B-777BB4?logo=php&logoColor=white)
![Zero Symfony](https://img.shields.io/badge/Symfony-0%20runtime%20deps-16a34a)
![License MIT](https://img.shields.io/badge/License-MIT-0284c7)

Kongono is a modern, zero-legacy **PHP 8.5+** application framework built from the ground up — **zero Symfony packages, zero third-party runtime libraries, zero mutable static state, and zero magic global facades** — designed for persistent runtimes (`kongo serve`, FrankenPHP, RoadRunner, PHP-FPM), hardened against autonomous AI attacks, and featuring **three built-in AI companions**:

- <img src="site/assets/companions/oshie.svg" width="18" height="18" align="absmiddle" alt="Oshie"> **Oshie (`kongo oshie`)** — Interactive AI tutor that teaches Kongono grounded in your live app.
- <img src="site/assets/companions/chie.svg" width="18" height="18" align="absmiddle" alt="Chie"> **Chie (`kongo chie`)** — Project-aware AI builder & scaffolder that reads your `ApplicationGraph` and proposes clean PHP 8.5 code.
- <img src="site/assets/companions/hantei.svg" width="18" height="18" align="absmiddle" alt="Hantei"> **Hantei (`kongo hantei`)** — AI debugger, failure analyzer, and PHPUnit 13 test generator.

---

## Why Kongono?

- **Pure PHP 8.5.13+ & Zero Bloat**: 100% `declare(strict_types=1)`, `final readonly` classes, attributes, enums, and only the 7 PSR interface packages in `require` — zero Symfony or npm runtime dependencies.
- **Self-Verifying Application Stack (`kongo prove`, `kongo diagnose --fix`)**:
  - **One-Command Readiness Certificate (`kongo prove`)**: Executes 5 mathematical verification pillars in parallel — 17-Rule Static & Full-Stack Contract Inspection, Executable Architecture Invariants (`app/invariants.php`), Persistent-Worker Leak & N+1 Proof (`perf`), Security Graph Audit, and Multi-Tenant BOLA/IDOR Isolation (`prove:isolation`) — producing a single `A+`–`F` score (`--json` for CI).
  - **17-Rule Self-Inspector & Auto-Fixer (`kongo diagnose --fix [--dry-run]`)**: Catches broken controller methods, missing templates, middleware order bugs, CSRF/Live contract breaks, DTO mismatches, and broken `.kgn.html` links/forms/partials — and auto-repairs them with deterministic PHP/HTML patches.
  - **Plain-English 5W1H Dev X-Ray vs. Zero-Leak Production**: In development (`APP_DEBUG=true`), errors render an interactive **What / Where / Why / Who / How to Fix** studio with source snippets and correlated request traces. In production (`APP_DEBUG=false`), responses stay strictly vague (`KGN-*` Reference ID only, zero stack traces, zero file paths) while the full scrubbed 5W1H diagnostic logs server-side.
- **Zero-Dependency Editor Intelligence (`kongo lsp` & `editors/vscode-kongono`)**: Built-in LSP 3.17 server (`textDocument/didSave` live 17-rule diagnostics, quick-fix code actions, controller/template/component go-to-definition, and `.kgn.html` completion) paired with a zero-npm VS Code / Cursor extension.
- **Least-Privilege Route Capabilities**: Declare runtime boundaries directly on controllers with `#[ReadOnlyDb]`, `#[NoOutboundHttp]`, `#[MaxQueries(N)]`, and `#[MaxAiCost(cents)]` — enforced pre-execution at the `PdoConnection`, `SafeHttpClient`, and `CapabilityMiddleware` boundary.
- **Production Incident Time-Machine (`$app->recordIncidents()`)**: Captures secret-scrubbed production 5xx failures into `storage/incidents/*.json`, lets you replay them offline in-process (`kongo incident:replay`), and generates deterministic PHPUnit 13 regression tests (`kongo incident:test`).
- **Change-Safety & Self-Upgrading Engine**:
  - **Semantic Blast-Radius (`kongo snapshot` / `kongo impact`)**: Diffs two app snapshots to flag breaking route removals, tightened DTO fields, dropped auth guards, and widened capabilities before merge.
  - **Deterministic Behavioral Equivalence (`kongo shadow:record` / `kongo shadow:diff`)**: Proves refactors return identical status codes, headers, and JSON/HTML bodies across recorded traffic.
  - **Verifiable Codemods (`kongo upgrade --plan|--apply --verify`)**: Applies declarative AST codemods with automatic rollback if `diagnose` or `policy:check` regresses.
  - **Continuous Watch Loop (`kongo watch`)**: Pure-PHP file watcher that re-runs `diagnose`, `policy:check`, and PHPUnit on save with instant 5W1H feedback.
- **Built-In `/_kongono` Dev Studio & `/_ops` Operations**:
  - **`/_kongono` Dev Studio** (auto-disabled in production): **Proof Center** (5-pillar live dashboard), **Zero-Build Hot Reload** (CSS hot-swap + template/PHP reload without Node/Vite), **Built-In Dev Mailbox** (`DevMailboxTransport` with split-pane sandboxed HTML preview & secret scrubbing), **Read-Only Schema Explorer**, **Request/SQL Replay** (`/_kongono/api/replay/{id}`), 1-click PHPUnit 13 `TestExporter`, and Oshie/Chie/Hantei browser workbench.
  - **`/_ops` Operations Dashboard** (`OpsDashboardMiddleware`): Fail-closed `Gate` (`ops.view`/`ops.manage`), live queue depths, 1-click DLQ retry/flush, `JobBatch` progress bars, `HealthCheck`, `Metrics`, and recent traces.
- **Kongono Live 2.5 & Bridge (`Kongono\Http\Live`, `Kongono\Http\Bridge`)**:
  - **Live 2.5**: Zero-build, strict-CSP reactive hypermedia (`LiveMiddleware`, `@liveScript`, `Request::isLive()`, `Response::fragment()`) with keyed DOM morphing, zero-`eval()` cached-AST signals, optimistic UI, speculative hover/visible prefetch, out-of-band multi-morph (`kgn-oob`), `k-persist` + offline idempotent queue, drag-and-drop (`kgn-sortable`), View Transitions, and SSE streaming (`kgn-stream`).
  - **Bridge**: First-class React, Vue, and Svelte integration (`BridgeMiddleware`, `Response::page()`, `Response::adaptive()`, `@island` hybrid islands, `kongo bridge:init`, `kongo bridge:types`, and `kongo ui:add`).
- **Complete Zero-Vendor Auth & Security (`Kongono\Security`)**:
  - Session auth (`SessionAuth`, `SessionAuthMiddleware`, `#[Authenticated]`), `Gate` policies & `before()` hooks, revocable SHA-256 Personal Access Tokens (`Memory`/`Cache`/`PDO` with automatic `kgn_pat_` secret scrubbing), `PasswordResetBroker`, `EmailVerifier`, `LoginThrottler`, and theft-detecting split-token `RememberMeManager`.
  - Native WebAuthn/FIDO2 Passkeys (`PasskeyVerifier` ES256/Ed25519), single-use HMAC `MagicLinkAuth`, PKCE S256 `PkceOAuthClient`, RFC 6238 `Totp` MFA, Argon2id `PasswordHasher`, HKDF `Encryptor` v2 (XChaCha20-Poly1305 AEAD + key rotation), HTML5 `HtmlSanitizer`/`@safeHtml`, `WebhookSigner`, and SSRF-pinned `SafeHttpClient`.
- **Production Queues, Batches & Durable Workflows (`Kongono\Jobs`)**:
  - `PostgresQueueDriver` (`FOR UPDATE SKIP LOCKED` + fencing tokens), pure-PHP Lua-atomic `RedisQueueDriver`, and `MemoryQueueDriver` with multi-queue priority draining (`critical,high,default`), dead-letter inspection/replay (`kongo queue:failed`, `queue:retry`, `queue:flush`), atomic `uniqueKey` deduplication, composable `JobMiddlewareInterface`, parallel `JobBatch` + `BatchDispatcher`, `--daemon` workers, cron `Scheduler`, and durable `Workflow` (`$wf->step()`, multi-day `sleep()`, `waitForEvent()`/`signal()`).
- **Explicit Data Layer, Migrations & Hybrid Search (`Kongono\Database` + `Kongono\Ai`)**:
  - Parameterized `QueryBuilder` with soft deletes (`withoutTrashed`/`onlyTrashed`/`restore`), fail-closed `forTenant()` + PostgreSQL `RowLevelSecurity`, zero-N+1 `BatchLoader` (`hasMany`/`belongsTo`) + `QueryGuard`, and typed `Factory::for()`.
  - Full `Schema::table()` / `Schema::rename()` + `Blueprint` (`uuid`, `json`, `decimal`, `foreignId`, `softDeletes`), `NonTransactionalMigrationInterface`, `kongo migrate --pretend`, zero-downtime `kongo migrate:lint`, and production-guarded `kongo migrate:fresh`.
  - Native `PostgresFullTextSearch` (`tsvector`/`ts_rank_cd`, plain-text `[[`/`]]` excerpts) fused with `PgVectorStore` (HNSW/GIN) via `HybridSearch` (Reciprocal Rank Fusion) for high-precision RAG retrieval.
- **Streaming Storage & First-Class Testing (`Kongono\Storage` + `TestClient`)**:
  - Streaming `putStream`/`getStream` across `LocalStorage`, pure-PHP AWS SigV4 `S3Storage` (paginated `ListObjectsV2`, server-side `x-amz-copy-source` `copy`/`move`, presigned URLs), and `MemoryStorage`, plus magic-byte `UploadedFileStore`, `SignedLocalUrl`, `StorageDisks`, and `Response::download()`/`fromStorage()`.
  - Expressive testing without global state: `TestClient` (`actingAs`, `withStatefulCookies`, `postMultipart`, `followRedirects`, `withLive`, `withBridge`), `TestUpload` (`fakePng`/`fakeJpeg`/`fakePdf`/`fakeJson`), rich `TestResponse` assertions, `DatabaseTestHelper::inRollbackTransaction()` (with nested SQL `SAVEPOINT`s), SSRF-enforcing `SafeHttpClient::fake()`, and pure-DI `ClockInterface`/`FrozenClock`.
- **One Backend, Every Platform (`kongo sdk:generate`)**: Generate typed **TypeScript**, **Swift 6**, **Kotlin**, and **Dart** client SDKs + OpenAPI 3.1.0 + `/docs` straight from PHP 8.5 attribute routes and readonly DTOs (including `#[ArrayOf]`, `RuleInterface`, and `ValidatableDtoInterface`).
- **Persistent-Runtime Speed (`Async::all()` + AOT Router)**: Native PHP 8.5 Fiber concurrency (`Async::all`/`race`/`map`/`allSettled`), parallel `SafeHttpClient::requestMany()`, zero-reflection dispatch plan caching, PDO/Redis auto-reconnect + statement caching, `RedisEventBus`, `O(1)` static + chunked PCRE2 AOT `Router` (`kongo optimize`), and OPcache-compiled `.kgn.html` closures.
- **Hardened Against Autonomous AI Attacks + Built-in Eval Suite (`kongo eval`)**:
  - **`AiThreatShieldMiddleware`** — recon honeypot traps, hidden form field traps, 404 probe burst auto-banning, and cost-aware compute budget limiting.
  - **`PromptGuard` & `UntrustedInput`** — structural `<untrusted_data>` isolation, prompt-injection detection, and cryptographic Canary Token fail-closed blocking.
  - **`kongo eval --redteam`** — "PHPUnit for AI" asserting structured DTO accuracy, token/latency budgets, and a 12-vector adversarial prompt-injection red-team battery.
  - **`kongo audit --attack --strict`** — graph-driven security audit + automated in-process red-team simulations + parser fuzzing (`composer fuzz`).
- **Multi-Provider AI & Native MCP (`kongono/ai`)**: `AiClient` speaks **OpenAI**, **Anthropic Claude**, **Google Gemini**, **Groq**, and local **Ollama** (all with live streaming) over `ext-curl`, plus `structured()` DTO extraction, 5-line RAG `KnowledgeBase`, governed `AgentPrincipal`/`RequiresApproval` budgets, version-matched framework RAG (`KongonoKnowledgeIndex`), `kongo eval --agent-bench`, and a built-in **Model Context Protocol (MCP) Server** (`kongo mcp` / `POST /mcp`, 26 verification, UI, docs & graph tools, pre-wired in `.vscode/mcp.json`).

---

## Installation & Quick Start

```bash
# Create a brand-new Kongono app (includes /_kongono Dev Studio, Proof Center, Dev Mailbox, Hot Reload, app/invariants.php, .vscode/mcp.json & FrankenPHP Dockerfile)
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
# Run the full starter app locally (includes /_kongono Dev Studio, Proof Center, Dev Mailbox, Schema Explorer, Hot Reload, /docs, /health, /openapi.json, /metrics, /mcp)
php bin/kongo serve --app=examples/starter-app --port=8000

# Self-verifying proof suite, 17-rule auto-fixer, continuous watch, incident time-machine & LSP
php bin/kongo prove --app=examples/starter-app
php bin/kongo diagnose --fix --app=examples/starter-app
php bin/kongo watch --app=examples/starter-app
php bin/kongo policy:check --app=examples/starter-app
php bin/kongo perf --app=examples/starter-app
php bin/kongo prove:isolation --app=examples/starter-app
php bin/kongo incident:list --app=examples/starter-app
php bin/kongo snapshot --out=baseline.json --app=examples/starter-app
php bin/kongo impact --against=baseline.json --app=examples/starter-app
php bin/kongo shadow:record --out=golden.json --app=examples/starter-app
php bin/kongo shadow:diff --against=golden.json --app=examples/starter-app
php bin/kongo upgrade --plan --app=examples/starter-app
php bin/kongo lsp --app=examples/starter-app

# Security audit, AI red-team evals, migrations, queues & frontend tooling
php bin/kongo doctor --app=examples/starter-app
php bin/kongo audit --attack --strict --app=examples/starter-app
php bin/kongo eval --redteam --app=examples/starter-app
php bin/kongo migrate --pretend --app=examples/starter-app
php bin/kongo migrate:lint --app=examples/starter-app
php bin/kongo queue:failed --app=examples/starter-app
php bin/kongo work --daemon --app=examples/starter-app
php bin/kongo bridge:types --app=examples/starter-app
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
php bin/kongo skills:install --target=both --app=examples/starter-app

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
- `GROQ_API_KEY` (or `GROQ_BASE_URL` override)
- `OLLAMA_HOST=http://127.0.0.1:11434` (free, local, offline!)

---

## Complete 11-Package Architecture

| # | Package | Namespace | Purpose |
| --- | --- | --- | --- |
| 1 | `packages/core` | `Kongono\Core\` | Persistent `Kernel`, PSR-11 `Container`, pure-DI `ClockInterface`/`SystemClock`/`FrozenClock`, Fiber `Async`, `Config`, `EnvLoader`, PSR-3 `Logger`, `MemoryCache`/`FileCache`/pure-PHP `RedisClient`+`RedisCache`/`CacheFactory`, `FeatureManager`, `NotificationDispatcher`, `Retry`/`CircuitBreaker`, `Metrics`/`Tracer`, `HealthCheck`, `I18n\Translator`, `Diagnostics`/`SecretScrubber`, `IncidentRecord`/`FileIncidentStore`, `DevMailboxInterface`/`FileDevMailbox`, `EventDispatcher`, `ApplicationGraph` |
| 2 | `packages/http` | `Kongono\Http\` | PSR-7/15 `Request`/`Response`/`Cookie`/`UploadedFile`, **Kongono Live 2.5** (`LiveMiddleware`, keyed morphing, CSP-strict signals, `kgn-oob`, `k-persist`, `kgn-sortable`), **Kongono Bridge** (`BridgeMiddleware`, `Response::page()`, `Response::adaptive()`), `Session` + PRG flash (`old`/`errors`), `StaticFilesMiddleware`, `CompressionMiddleware`, `#[CacheControl]`, `#[Idempotent]`, `#[Feature]`, `SseEvent` + `EventBus`/`RedisEventBus`, `OpenApiGenerator`, `/docs`, `/_kongono` Dev Studio (Proof Center, Dev Mailbox, Schema Explorer, `DevHotReloadWatcher`, 5W1H Dev X-Ray vs. Zero-Leak Prod), `/_ops` `OpsDashboardMiddleware`, `TestClient`/`TestResponse`/`TestUpload`, zero-reflection `App` (`recordIncidents()`), `O(1)` static + chunked PCRE2 AOT `Router` |
| 3 | `packages/view` | `Kongono\View\` | Compiled `.kgn.html` OPcache closure templates (`warmCache()`, `autoReload: false`), `@liveScript`, `@bridgeScript`, `@island`, `@vite(...)`, `@safeHtml`, `@old(...)`/`@error(...)`, layouts, loops, `<x-component>` slots, `@t(...)` i18n, `@json`, automatic context-aware XSS escaping |
| 4 | `packages/validation` | `Kongono\Validation\` | PHP 8.5 attribute-driven typed DTO validation (nested DTOs, `BackedEnum`, `#[ArrayOf]`, `RuleInterface`, `ValidatableDtoInterface`, `#[Date]`, `#[Ip]`, `#[NotIn]`, `#[RequiredIf]`, `#[Uuid]`, `#[Between]`, `#[Confirmed]`, `#[Email]`, `#[Min]`/`#[Max]`, `#[In]`, `#[Regex]`) |
| 5 | `packages/database` | `Kongono\Database\` | Parameterized `QueryBuilder` (soft deletes, fail-closed `forTenant()` + PostgreSQL `RowLevelSecurity`, `JOIN`, `whereGroup()`, `upsert()`, `GROUP BY`, `HAVING`, `RETURNING`, `paginate()`, `getAs()`, `insertMany()`), `ConnectionManager` & `ReadWriteConnection` (named connections, read replicas, sticky-after-write), `BatchLoader` (`hasMany`/`belongsTo`), `QueryGuard`, `Factory`, `Schema`/`Blueprint` (`table`/`rename`/`softDeletes`/`foreignId`), `PdoConnection` (auto-reconnect, stmt cache, nested `SAVEPOINT` transactions, `QueryGuardableInterface`), `DatabaseTestHelper`, `Migrator` (`migrate --pretend`, `rollback`, `fresh`, `lint`), `PostgresFullTextSearch`/`MemorySearchEngine`, `Repository`, `SeederInterface` |
| 6 | `packages/security` | `Kongono\Security\` | `SessionAuth` + `#[Authenticated]`, least-privilege capabilities (`#[ReadOnlyDb]`, `#[NoOutboundHttp]`, `#[MaxQueries]`, `#[MaxAiCost]`, `CapabilityMiddleware`), revocable SHA-256 `PersonalAccessToken` (`Memory`/`Cache`/`Pdo` `PersonalAccessTokenStore`), `PasswordResetBroker`, `EmailVerifier`, `LoginThrottler`, `RememberMeManager`, WebAuthn `PasskeyVerifier`, `MagicLinkAuth`, `PkceOAuthClient`, `AiThreatShieldMiddleware`, `Principal`, `Gate` (policies + `before()`), `#[Can]`, `HtmlSanitizer`, RFC 6238 `Totp`, `WebhookSigner`/`WebhookDispatcher`/`#[VerifyWebhook]`, Argon2id `PasswordHasher`, HKDF `Encryptor` v2, `TokenSigner`/`SignedUrl`, CSRF/CORS/Bearer/RateLimit/SecurityHeaders middleware, SSRF-pinned `SafeHttpClient` (`EgressGuardableInterface`) + `SafeHttpClient::fake()` |
| 7 | `packages/storage` | `Kongono\Storage\` | Streaming (`putStream`/`getStream`), `copy`/`move`, contained atomic `LocalStorage`, pure PHP 8.5 AWS SigV4 `S3Storage` (S3/R2/MinIO, paginated `ListObjectsV2`, native server-side copy, presigned URLs), `MemoryStorage`, `StorageDisks`, `StorageFactory`, magic-byte `UploadedFileStore`, `SignedLocalUrl` |
| 8 | `packages/jobs` | `Kongono\Jobs\` | Background `Queue`, persistent/daemon `Worker` (multi-queue priority draining, `JobMiddlewareInterface`), dead-letter `listFailed`/`retryFailed`/`flushFailed`, atomic `uniqueKey` dedup, parallel `JobBatch` + `BatchDispatcher`, durable `Workflow`, `PostgresQueueDriver` (`FOR UPDATE SKIP LOCKED` + fencing tokens), Lua-atomic `RedisQueueDriver`, `MemoryQueueDriver`, cluster-safe cron `Scheduler` (`withoutOverlapping()`, `onOneServer()`) |
| 9 | `packages/mail` | `Kongono\Mail\` | Zero-dependency transactional email (`SmtpTransport` STARTTLS, `ResendTransport`, `DevMailboxTransport`, `LogTransport`, `MemoryTransport`, `MailNotificationChannel`, `.kgn.html` email views) |
| 10 | `packages/ai` | `Kongono\Ai\` | Multi-provider `AiClient` (OpenAI, Anthropic, Gemini, Ollama) with live streaming, `PromptGuard` + `UntrustedInput` (canary tokens + injection detection), `EvalRunner`, vector embeddings (`MemoryVectorStore` + `PgVectorStore` HNSW/GIN), `HybridSearch` (Reciprocal Rank Fusion), 5-line RAG `KnowledgeBase`, `structured()` DTO extraction, governed `AgentPrincipal`/`RequiresApproval`/`AgentRunner`, and native `McpServer` (JSON-RPC 2.0, 26 verification, UI, docs & graph tools) |
| 11 | `packages/cli` | `Kongono\Cli\` | Unified `kongo` CLI (`new [--starter=minimal|api|saas]`, `auth:init`, `serve`, `watch`, `prove`, `diagnose [--fix]`, `policy:check`, `perf`, `prove:isolation`, `incident:list|show|replay|test`, `snapshot`, `impact`, `shadow:record|diff`, `upgrade [--plan|--apply --verify]`, `lsp`, `tinker`, `doctor`, `audit [--attack]`, `eval [--redteam]`, `sdk:generate`, `bridge:init`, `bridge:types`, `ui:add`, `skills:install`, `deploy:init`, `env:check`, `optimize`, `cache:clear`, `key:generate`, `work [--daemon]`, `queue:failed`, `queue:retry`, `queue:flush`, `schedule:run`, `migrate [--pretend]`, `migrate:rollback`, `migrate:status`, `migrate:lint`, `migrate:fresh`, `db:seed`, `make:*`, `routes`, `graph`, `openapi`, `mcp`, `check`, `test`) + built-in **Oshie**, **Chie**, and **Hantei** + zero-npm VS Code/Cursor extension (`editors/vscode-kongono/`) |

*(Historical Phase 0–23 architecture archives remain preserved on the `main` and `archive/phase-23c-wip` branches.)*

---

## Quality Checks

```bash
composer dump-autoload
composer cs                 # PHP-CS-Fixer
composer analyse            # PHPStan level 9 + strict rules
composer test               # PHPUnit 13
composer fuzz               # Adversarial parser & security fuzzer
composer test:integration   # Live PostgreSQL 16 (pgvector) + Redis 7 integration suite
composer quality            # Run cs + analyse + test
```

## Documentation

- **[Official Documentation Site (GitHub Pages)](https://anthonyiovanna.github.io/kongono/)** · **[`llms.txt`](https://anthonyiovanna.github.io/kongono/llms.txt)** · **[`llms-full.txt`](https://anthonyiovanna.github.io/kongono/llms-full.txt)**
- **[Community Discussions](https://github.com/anthonyiovanna/kongono/discussions)**
- [Getting Started](docs/getting-started.md)
- [Architecture Overview](docs/architecture.md)
- [Built-In AI Companions & MCP Server](docs/ai-companions.md)
- [Production Deployment & Scaling Guide](docs/production-deployment.md)
- [Persistent-Worker Benchmark](examples/benchmark.php) (`php examples/benchmark.php`)
- [Changelog](CHANGELOG.md) · [Contributing](CONTRIBUTING.md) · [Security Policy](SECURITY.md)

## License

MIT — see [LICENSE](LICENSE).
