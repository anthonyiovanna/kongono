---
name: kongono-queues-and-ops
description: >-
  Workflow for configuring background queues, priority draining, dead-letter
  replay, parallel JobBatch fan-out, durable Workflows, streaming S3/local
  storage, capability-gated /_ops Operations Dashboard, and FrankenPHP production
  deployment in Kongono Framework (PHP 8.5+). Use when building async jobs,
  scheduled tasks, file uploads, /_ops monitoring, or Docker deployment.
---

# Kongono Queues, Storage, `/_ops` & Production Workflow

## Step 1: Dispatch & Process Background Jobs (`Kongono\Jobs`)

1. Choose a durable production driver:
   - `PostgresQueueDriver` (`FOR UPDATE SKIP LOCKED` + fencing tokens) — great when already on PostgreSQL.
   - `RedisQueueDriver` (pure-PHP Lua-atomic `RedisClient`, zero `ext-redis` needed) — great for high-throughput Redis/Valkey.
   - `MemoryQueueDriver` for fast unit tests.
2. Dispatch jobs through `Queue`:
   - Prevent duplicates atomically with `uniqueKey`: `$queue->dispatch('sync.user', $payload, queue: 'high', uniqueKey: "sync:{$userId}")`.
   - Attach cross-cutting concerns (logging, tenant context, rate limits, timeouts) via `JobMiddlewareInterface`.
3. Run persistent workers with priority queue ordering:
   ```bash
   php vendor/bin/kongo work --daemon --queue=critical,high,default
   ```
4. Inspect and recover dead-letter jobs cleanly:
   ```bash
   php vendor/bin/kongo queue:failed
   php vendor/bin/kongo queue:retry <job-id>   # or --all
   php vendor/bin/kongo queue:flush
   ```

## Step 2: Parallel Batches & Durable Multi-Day Workflows

- **Fan-out / Fan-in Batches:** Use `Kongono\Jobs\Batch\BatchDispatcher` + `JobBatch` (`CacheBatchStore` in production, `MemoryBatchStore` in tests) with `then`, `catch`, and `finally` callbacks.
- **Durable Workflows:** Use `Kongono\Jobs\Workflow\WorkflowEngine` + `WorkflowContext` (`CacheWorkflowStore` / `MemoryWorkflowStore`) for step memoization (`$wf->step()`), multi-day `sleep()`, and external approval signals (`waitForEvent()` + `signal()`).
- **Cron Scheduling:** Register recurring tasks with `Kongono\Jobs\Scheduler\Scheduler` and run `php vendor/bin/kongo schedule:run` every minute.

## Step 3: Streaming Storage & Safe Uploads (`Kongono\Storage`)

- Stream large payloads with `putStream()` / `getStream()` and move/copy via `copy()` / `move()` (native `x-amz-copy-source` on `S3Storage`) across `LocalStorage`, pure-PHP AWS SigV4 `S3Storage`, and `MemoryStorage`.
- Validate uploads by magic bytes with `UploadedFileStore`, serve downloads via `Response::download()` / `Response::fromStorage()` / `SignedLocalUrl`, and manage multiple disks with `StorageDisks` / `StorageFactory`.

## Step 4: Mount the Fail-Closed `/_ops` Dashboard (`Kongono\Http\Ops`)

1. Define strict `Gate` rules for `ops.view` (read-only dashboards) and `ops.manage` (dead-letter retry/flush).
2. Mount `OpsDashboardMiddleware` with an explicit queue allowlist, `QueueDriverInterface`, `HealthCheck`, `Metrics`, and optional `BatchStoreInterface` / `DevInspector`.
3. Never weaken `Gate` defaults — `/_ops` must return `401`/`403` to unauthenticated or non-operator principals.

## Step 5: Harden & Optimize for FrankenPHP Production

1. Generate non-root read-only deployment files: `php vendor/bin/kongo deploy:init --domain=app.example.com`.
2. During Docker build / release:
   ```bash
   composer install --no-dev --optimize-autoloader
   php vendor/bin/kongo optimize
   php vendor/bin/kongo migrate:lint
   php vendor/bin/kongo migrate --pretend
   ```
3. Ensure `APP_DEBUG=false`, `views(autoReload: false)`, `RedisCache` + `RedisEventBus` for multi-node state, and readiness probes targeting `GET /health` + Prometheus scraping `GET /metrics`.

## Verification Checklist

```bash
php vendor/bin/kongo doctor
php vendor/bin/kongo env:check
php vendor/bin/kongo audit --attack --strict
php vendor/bin/kongo test
```
