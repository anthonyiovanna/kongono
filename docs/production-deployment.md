# Production Deployment & Scaling Guide

Kongono v1 is engineered **persistent-runtime first**, with deterministic per-request lifecycle cleanup (`OperationScope` + `Container::resetScope()`) so your application runs identically across PHP-FPM, FrankenPHP persistent workers, and RoadRunner.

---

## 1. Zero-Cold-Start Build Step (`kongo optimize`)

Run during your Docker image build or CI release step before starting workers:

```bash
composer install --no-dev --optimize-autoloader
php bin/kongo optimize
```

`kongo optimize` precompiles every `.kgn.html` view into opcache-ready PHP files and caches `var/cache/graph.json` and `var/cache/openapi.json`.

---

## 2. FrankenPHP Persistent Worker Deployment

Every app scaffolded with `kongo new` includes `public/worker.php`, `Caddyfile`, and `Dockerfile`:

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

1. **Disable debug mode:** Set `APP_DEBUG=false` (disables `DevErrorRenderer` stack traces and automatically fail-closes `/_kongono` Dev Studio to `404`).
2. **Generate a strong `APP_KEY`:** Run `php bin/kongo key:generate`. Rotate safely using `new Encryptor($currentKey, previousKeys: [$oldKey])`.
3. **Database & Migrations:** Use `PdoConnection::fromUrl(Config::env('DATABASE_URL'))`, run `php bin/kongo migrate`, and use `PostgresQueueDriver` (`FOR UPDATE SKIP LOCKED` + fencing tokens) for durable background jobs.
4. **Horizontal Scaling with Redis / Valkey:**
   ```php
   $redis = RedisClient::fromUrl(Config::env('REDIS_URL') ?? 'redis://127.0.0.1:6379');
   $cache = new RedisCache($redis);
   ```
   Pass `$cache` to `CacheSessionStore`, `RateLimitMiddleware`, `IdempotencyMiddleware`, and `FeatureManager` for shared state across nodes.
5. **Idempotent APIs & Signed Webhooks:**
   - Decorate mutating API endpoints with `#[Idempotent(ttlSeconds: 86400)]` to make client retries safe.
   - Use `WebhookDispatcher` (built on SSRF-hardened `SafeHttpClient`) for outbound webhooks and `#[VerifyWebhook]` for inbound webhook endpoints.
6. **Observability:**
   - Point Kubernetes/load-balancer readiness probes at `GET /health` (`200` when healthy, `503` when degraded).
   - Scrape Prometheus metrics from `GET /metrics` and monitor `Server-Timing` response headers.
   - Hook query timing with `$db->onQuery(...)`.
7. **AI & RAG in Production:**
   - Govern every tool-using agent with `AgentPrincipal` (explicit `allowedTools`, `maxSteps`, `maxToolCalls`).
   - Use `PgVectorStore` + `KnowledgeBase` for PostgreSQL `pgvector` semantic retrieval with automatic `SecretScrubber` redaction.
   - Never expose write-capable MCP tools publicly; keep `KongonoMcpFactory` read-only and protect HTTP `/mcp` behind authentication if enabled in staging/production.
