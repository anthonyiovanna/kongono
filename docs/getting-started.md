# Getting Started with Kongono v1

Kongono is a zero-legacy **PHP 8.5+** application framework built from the ground up with **zero Symfony runtime dependencies** and three built-in AI companions (**Oshie**, **Chie**, and **Hantei**).

## Requirements

- PHP **8.5+** (`ext-curl`, `ext-json`, `ext-mbstring`, `ext-pdo`, `ext-sodium`)
- Optional recommended extensions: `ext-pdo_pgsql` (PostgreSQL/`pgvector`), `ext-zlib` (gzip/deflate compression), `ext-openssl` (STARTTLS SMTP & TLS Redis)
- Composer 2.x

## Install via Composer

```bash
composer require kongono/framework
php vendor/bin/kongo new my-app --name="My App"
cd my-app
composer install
php vendor/bin/kongo doctor
php vendor/bin/kongo serve --port=8000
```

Then open in your browser:
- **App Home:** `http://127.0.0.1:8000/`
- **Kongono Dev Studio:** `http://127.0.0.1:8000/_kongono`
- **Interactive API Explorer:** `http://127.0.0.1:8000/docs`
- **OpenAPI 3.1 Spec:** `http://127.0.0.1:8000/openapi.json`
- **Health Check:** `http://127.0.0.1:8000/health`

## Explore the Full Starter App (Monorepo)

This repository includes a full runnable starter application in `examples/starter-app` exercising all 11 Kongono packages:

```bash
php bin/kongo doctor --app=examples/starter-app
php bin/kongo serve --app=examples/starter-app --port=8000
```

Inspect your live app from the CLI:

```bash
php bin/kongo routes --app=examples/starter-app
php bin/kongo routes --json --app=examples/starter-app
php bin/kongo graph --app=examples/starter-app
php bin/kongo openapi --app=examples/starter-app
php bin/kongo work --app=examples/starter-app
php bin/kongo optimize --app=examples/starter-app
```

## Instant Deterministic Generators

```bash
php vendor/bin/kongo make:controller Post
php vendor/bin/kongo make:dto CreatePostInput
php vendor/bin/kongo make:migration create_posts
php vendor/bin/kongo make:seeder Users
php vendor/bin/kongo make:test Post
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
php bin/kongo check   # Runs CS + PHPStan Level 9 Strict + PHPUnit 13
```
