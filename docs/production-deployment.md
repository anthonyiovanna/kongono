# Production Deployment & Scaling Guide

Kongono v1.20 is engineered **persistent-runtime first**, with deterministic per-request lifecycle cleanup (`OperationScope` + `Container::resetScope()`), zero mutable static state, and zero-reflection handler dispatch caching so your application runs identically across PHP-FPM, FrankenPHP persistent workers, and RoadRunner.

---

## 1. Zero-Cold-Start Build Step (`kongo optimize`)

Run during your Docker image build or CI release step before starting workers:

```bash
composer install --no-dev --optimize-autoloader
php bin/kongo optimize
```

`kongo optimize` precompiles every `.kgn.html` view into OPcache-ready static PHP closures, emits the chunked PCRE2 AOT route cache (`var/cache/routes.compiled.php`), and caches `var/cache/graph.json` and `var/cache/openapi.json`.

---

## 2. Hardened FrankenPHP Persistent Worker Deployment

Generate hardened non-root, read-only container blueprints with `php bin/kongo deploy:init --domain=app.example.com`, or use the `public/worker.php`, `Caddyfile`, and `Dockerfile` scaffolded by `kongo new`:

```php
<?php

declare(strict_types=1);

use Kongono\Http\App;

require dirname(__DIR__) . '/vendor/autoload.php';

/** @var App $app */
$app = require dirname(__DIR__) . '/app/bootstrap.php';

$app->runWorker(static fn(Closure $handler): bool => frankenphp_handle_request($handler));
```

Build and run:

```bash
docker build -t my-kongono-app .
docker run -p 8080:8080 --env-file .env.production my-kongono-app
```

---

## 3. Production Hardening Checklist

1. **Enforce PHP `>= 8.5.13` & disable debug mode:** Set `APP_DEBUG=false` (disables `DevErrorRenderer` stack traces, strips Hot Reload / Floating Dev HUD `devHud: true` injection, and automatically fail-closes `/_kongono` Dev Studio to `404`), and configure `->views(autoReload: false)` so persistent workers cache compiled `.kgn.html` closures in memory with zero per-request filesystem checks. Call `$views->warmCache()` at worker boot.
2. **Gate `/_ops` Operations Dashboard:** If mounting `OpsDashboardMiddleware`, register strict `Gate` rules for `ops.view` (read-only dashboards) and `ops.manage` (queue dead-letter retry/flush) and pass an explicit queue allowlist — `/_ops` fails closed by default.
3. **Generate & rotate `APP_KEY`:** Run `php bin/kongo key:generate`. Rotate seamlessly using `new Encryptor($currentKey, previousKeys: [$oldKey])` (HKDF v2 AEAD with upgrade-safe v1 decryption).
4. **Database, RLS & Zero-Downtime Migrations:**
   - Use `PdoConnection::fromUrl(Config::env('DATABASE_URL'))` (includes auto-reconnect and prepared statement caching) with PostgreSQL `RowLevelSecurity` and `QueryBuilder::forTenant()`.
   - Run `php bin/kongo migrate:lint` and `php bin/kongo migrate --pretend` in CI before `php bin/kongo migrate`.
   - Use `NonTransactionalMigrationInterface` for `CREATE INDEX CONCURRENTLY`.
   - Run `PostgresQueueDriver` (`FOR UPDATE SKIP LOCKED` + fencing tokens) or Lua-atomic `RedisQueueDriver` with `php bin/kongo work --daemon`, priority queues (`critical,high,default`), and `kongo queue:failed` / `queue:retry` / `queue:flush`.
5. **Horizontal Scaling with Redis / Valkey:**
   ```php
   $redis = RedisClient::fromUrl(Config::env('REDIS_URL') ?? 'redis://127.0.0.1:6379');
   $cache = new RedisCache($redis);
   ```
   Pass `$cache` to `CacheSessionStore`, `CachePersonalAccessTokenStore`, `CacheBatchStore`, `RateLimitMiddleware`, `IdempotencyMiddleware`, and `FeatureManager`, and use `RedisEventBus` for multi-node SSE fan-out.
6. **Pre-Deploy Proof, Env, Security & AI Gates in CI:**
   - Run `php bin/kongo prove`, `php bin/kongo env:sync --check`, `php bin/kongo env:check` (against deployment secrets), `php bin/kongo audit --attack --strict`, `php bin/kongo eval --redteam`, `composer quality`, and `composer fuzz`.
   - Enable `$app->recordIncidents()` so production 5xx errors return vague `KGN-*` references to users while saving secret-scrubbed replay bundles to `storage/incidents/` for offline `kongo incident:replay` & `kongo incident:test`.
   - Decorate read-only routes with `#[ReadOnlyDb]` / `#[NoOutboundHttp]` / `#[MaxQueries(N)]`, mutating APIs with `#[Idempotent(ttlSeconds: 86400)]`, sanitize rich HTML with `HtmlSanitizer`/`@safeHtml`, and protect outbound calls with DNS-pinned `SafeHttpClient` + signed `WebhookDispatcher`.
7. **Observability:**
   - Point Kubernetes/load-balancer readiness probes at `GET /health` (`200` when healthy, `503` when degraded).
   - Scrape Prometheus metrics from `GET /metrics` and monitor `Server-Timing` response headers via `Tracer`.
   - Hook query timing & N+1 detection with `$db->onQuery(...)` and `QueryGuard`.
8. **AI & Hybrid RAG in Production:**
   - Govern every tool-using agent with `AgentPrincipal` (explicit `allowedTools`, `maxSteps`, `maxToolCalls`, token/USD budgets, and `RequiresApproval` for sensitive actions) and wrap untrusted text in `PromptGuard` / `UntrustedInput`.
   - Combine `PostgresFullTextSearch` and `PgVectorStore` (HNSW/GIN) via `HybridSearch` (RRF) with automatic `SecretScrubber` redaction.
   - Keep `KongonoMcpFactory` read-only and protect HTTP `/mcp` behind authentication if exposed outside local development.
