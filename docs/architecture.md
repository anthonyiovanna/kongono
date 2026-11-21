# Kongono v1 Architecture & Deployment

## 10 Cohesive Packages

Kongono keeps strict acyclic boundaries across 10 focused packages:

1. `kongono/core` — Persistent `Kernel`, PSR-11 `Container` with `resetScope()`, `Config`, `EnvLoader`, PSR-3 secret-scrubbing `Logger`, `MemoryCache`/`FileCache`, nondisclosing `Diagnostics`, PSR-14 `EventDispatcher`, and live `ApplicationGraph`.
2. `kongono/http` — Native PSR-7/15 `Request`/`Response`/`Cookie`, `Session` + `SessionMiddleware`, `StaticFilesMiddleware`, streaming responses, multipart uploads, attribute + fluent `Router`, and `App`.
3. `kongono/view` — Compiled `.kgn.html` HTML templates with XSS auto-escaping, layouts, loops, and `<x-component>` slots.
4. `kongono/validation` — Attribute-driven PHP 8.5 typed DTO validation (`#[Required]`, `#[Min]`, `#[Max]`, `#[Email]`, `#[Url]`, `#[In]`, `#[Regex]`).
5. `kongono/database` — Parameterized `QueryBuilder` (`JOIN`, `WHERE`, `GROUP BY`, `HAVING`, `RETURNING`, `paginate()`, `getAs()`), `Schema`/`Blueprint` DDL builder, strict `PdoConnection`, and `Migrator`.
6. `kongono/security` — Human/Service/Agent/System `Principal`, fail-closed `Gate`, Argon2id `PasswordHasher`, HMAC `TokenSigner`, SecurityHeaders/CORS/CSRF/BearerAuth/RateLimit middleware, and SSRF-hardened DNS-pinning `SafeHttpClient`.
7. `kongono/storage` — Root-contained, atomic, SHA-256 versioned `LocalStorage` and `MemoryStorage` with optimistic concurrency.
8. `kongono/jobs` — Background `Queue` and persistent-runtime-safe `Worker` over `PostgresQueueDriver` (`FOR UPDATE SKIP LOCKED` + fencing tokens) or `MemoryQueueDriver`, explicitly preserving `JobOutcome::Uncertain`.
9. `kongono/ai` — Multi-provider `AiClient` (OpenAI, Anthropic, Gemini, Ollama) with `structured()` DTO extraction and capability-governed `AgentPrincipal` / `AgentRunner`.
10. `kongono/cli` — Unified `kongo` CLI and built-in **Oshie**, **Chie**, and **Hantei** AI companions.

## Persistent-Runtime First Deployment

Kongono never stores per-request state in static properties, `$_SESSION`, or global facades. Every request runs inside `Kernel::runOperation()` and clears scoped container instances on exit.

### Standard PHP-FPM / `kongo serve` (`public/index.php`)
```php
<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

$app = require dirname(__DIR__) . '/app/bootstrap.php';
$app->run();
```

### FrankenPHP Worker Mode (`public/worker.php`)
```php
<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

$app = require dirname(__DIR__) . '/app/bootstrap.php';
$app->runWorker(static fn(Closure $handle): bool => frankenphp_handle_request($handle), maxRequests: 1000);
```
