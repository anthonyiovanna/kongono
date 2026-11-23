# Getting Started with Kongono v1.17

Kongono is a zero-legacy **PHP 8.5+** application framework built from the ground up with **zero Symfony runtime dependencies**, **zero mutable static state**, Live 2.5 & Bridge frontends, built-in `/_ops`, Hybrid RRF search, and three built-in AI companions (**Oshie**, **Chie**, and **Hantei**).

## Requirements

- PHP **8.5.13+** (`ext-curl`, `ext-dom`, `ext-json`, `ext-mbstring`, `ext-pdo`, `ext-sodium`)
- Optional recommended extensions: `ext-pdo_pgsql` (PostgreSQL/`pgvector`), `ext-pdo_sqlite` (local SQLite), `ext-zlib` (gzip/deflate compression), `ext-openssl` (STARTTLS SMTP & TLS Redis)
- Composer 2.x

## Create a New App (Recommended — 1 Command)

```bash
composer create-project kongono/app my-app
cd my-app
composer dev
```

Or install `kongono/framework` directly into an existing PHP 8.5.13+ project:

```bash
composer require kongono/framework
php vendor/bin/kongo doctor
php vendor/bin/kongo serve --port=8000
```

Then open in your browser:
- **App Home:** `http://127.0.0.1:8000/`
- **Kongono Dev Studio:** `http://127.0.0.1:8000/_kongono`
- **Operations Dashboard (when `OpsDashboardMiddleware` is mounted):** `http://127.0.0.1:8000/_ops`
- **Interactive API Explorer:** `http://127.0.0.1:8000/docs`
- **OpenAPI 3.1 Spec:** `http://127.0.0.1:8000/openapi.json`
- **Health Check:** `http://127.0.0.1:8000/health`

## Explore the Full Starter App (Monorepo)

This repository includes a runnable starter application in `examples/starter-app` exercising all 11 Kongono packages:

```bash
php bin/kongo doctor --app=examples/starter-app
php bin/kongo serve --app=examples/starter-app --port=8000
```

Inspect, audit, and run workflows from the CLI:

```bash
php bin/kongo routes --app=examples/starter-app
php bin/kongo graph --app=examples/starter-app
php bin/kongo openapi --app=examples/starter-app
php bin/kongo audit --attack --strict --app=examples/starter-app
php bin/kongo eval --redteam --app=examples/starter-app
php bin/kongo migrate --pretend --app=examples/starter-app
php bin/kongo migrate:lint --app=examples/starter-app
php bin/kongo queue:failed --app=examples/starter-app
php bin/kongo work --daemon --app=examples/starter-app
php bin/kongo optimize --app=examples/starter-app
```

## Instant Deterministic Generators & SDKs

```bash
php vendor/bin/kongo make:controller Post
php vendor/bin/kongo make:dto CreatePostInput
php vendor/bin/kongo make:migration create_posts
php vendor/bin/kongo make:seeder Users
php vendor/bin/kongo make:test Post
php vendor/bin/kongo bridge:init
php vendor/bin/kongo bridge:types
php vendor/bin/kongo sdk:generate --lang=ts --out=sdk/client.ts
php vendor/bin/kongo deploy:init --domain=app.example.com
php vendor/bin/kongo key:generate
```

## Learn, Build & Debug with Built-In AI

```bash
php vendor/bin/kongo oshie --interactive
php vendor/bin/kongo chie "Add a /api/stats JSON endpoint"
php vendor/bin/kongo hantei check
php vendor/bin/kongo mcp
```

## Quality Toolchain

```bash
composer quality            # PHP-CS-Fixer + PHPStan Level 9 Strict + PHPUnit 13
composer fuzz               # Adversarial parser & security fuzzer
composer test:integration   # Live PostgreSQL 16 (pgvector) + Redis 7 suite
```
