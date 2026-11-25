# Getting Started with Kongono v1.20

Kongono is a zero-legacy **PHP 8.5+** application framework built from the ground up with **zero Symfony runtime dependencies**, **zero mutable static state**, self-verifying diagnostics & architecture proofs (`kongo prove`), least-privilege route capabilities (`#[ReadOnlyDb]`, `#[NoOutboundHttp]`), zero-build Hot Reload, built-in `/_kongono` Dev Studio (Proof Center, Dev Mailbox, Schema Explorer), `/_ops`, Live 2.5 & Bridge frontends, Hybrid RRF search, and three built-in AI companions (**Oshie**, **Chie**, and **Hantei**).

## Requirements

- PHP **8.5.13+** (`ext-curl`, `ext-dom`, `ext-json`, `ext-mbstring`, `ext-pdo`, `ext-sodium`)
- Optional recommended extensions: `ext-pdo_pgsql` (PostgreSQL/`pgvector`), `ext-pdo_sqlite` (local SQLite), `ext-zlib` (gzip/deflate compression), `ext-openssl` (STARTTLS SMTP & TLS Redis)
- Composer 2.x

## Create a New App (Recommended — 1 Command)

```bash
php bin/kongo new my-app --starter=minimal   # or --starter=api | --starter=saas
cd my-app
composer install
composer dev
```

Or via Composer directly:

```bash
composer create-project kongono/app my-app
cd my-app
composer dev
```

Every freshly scaffolded app ships Day-1 with:
- **`/_kongono` Dev Studio** (Proof Center, Dev Mailbox, Schema Explorer, Request/SQL Replay, & Zero-Build Hot Reload)
- **`app/invariants.php`** executable architecture & security rules
- **`#[ReadOnlyDb]`** least-privilege route capabilities & **`$app->recordIncidents()`** production incident capture
- **`.vscode/mcp.json`** pre-wired for the 26-tool Kongono MCP server

Or install `kongono/framework` directly into an existing PHP 8.5.13+ project:

```bash
composer require kongono/framework
php vendor/bin/kongo prove
php vendor/bin/kongo serve --port=8000
```

Then open in your browser:
- **App Home:** `http://127.0.0.1:8000/` (auto-reloads CSS & templates in dev with Zero-Build Hot Reload)
- **Kongono Dev Studio:** `http://127.0.0.1:8000/_kongono` (Proof Center, Dev Mailbox, Schema Explorer, Requests, Incidents, Routes, AI)
- **Operations Dashboard (when `OpsDashboardMiddleware` is mounted):** `http://127.0.0.1:8000/_ops`
- **Interactive API Explorer:** `http://127.0.0.1:8000/docs`
- **OpenAPI 3.1 Spec:** `http://127.0.0.1:8000/openapi.json`
- **Health Check:** `http://127.0.0.1:8000/health`

## Explore the Full Starter App (Monorepo)

This repository includes a runnable starter application in `examples/starter-app` exercising all 11 Kongono packages:

```bash
php bin/kongo prove --app=examples/starter-app
php bin/kongo serve --app=examples/starter-app --port=8000
```

### Verification, Self-Healing & Continuous Watch

```bash
php bin/kongo prove --app=examples/starter-app              # Unified 5-pillar readiness certificate (A+–F)
php bin/kongo diagnose --fix --app=examples/starter-app     # 17-rule static + full-stack contract check & auto-fix
php bin/kongo watch --app=examples/starter-app              # Continuous save loop (diagnose + policy + PHPUnit)
php bin/kongo policy:check --app=examples/starter-app       # Enforce app/invariants.php architecture policies
php bin/kongo perf --app=examples/starter-app               # Persistent-worker leak & N+1 prover
php bin/kongo prove:isolation --app=examples/starter-app    # Multi-tenant BOLA/IDOR isolation prover
php bin/kongo audit --attack --strict --app=examples/starter-app
php bin/kongo eval --redteam --app=examples/starter-app
```

### Production Incident Time-Machine, Snapshots & Self-Upgrades

```bash
php bin/kongo incident:list --app=examples/starter-app
php bin/kongo incident:replay <id> --app=examples/starter-app
php bin/kongo incident:test <id> --app=examples/starter-app
php bin/kongo snapshot --out=baseline.json --app=examples/starter-app
php bin/kongo impact --against=baseline.json --app=examples/starter-app
php bin/kongo shadow:record --out=golden.json --app=examples/starter-app
php bin/kongo shadow:diff --against=golden.json --app=examples/starter-app
php bin/kongo upgrade --plan --app=examples/starter-app
```

### Editor Support (Zero-Dependency LSP & VS Code / Cursor)

Start the built-in LSP 3.17 language server (`kongo lsp`) or load the zero-npm extension in `editors/vscode-kongono/` for live 17-rule squiggles, 1-click quick-fixes, go-to-definition across controllers/views/components, and `.kgn.html` completions.

### Migrations, Queues & Optimization

```bash
php bin/kongo routes --app=examples/starter-app
php bin/kongo graph --app=examples/starter-app
php bin/kongo openapi --app=examples/starter-app
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
