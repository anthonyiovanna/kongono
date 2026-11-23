---
name: kongono-feature-builder
description: >-
  End-to-end procedure for building a new HTTP endpoint, CRUD resource, database
  migration, DTO, and test in a Kongono Framework (PHP 8.5+) application. Use when
  the user asks to add a feature, endpoint, controller, schema change, or test in
  Kongono so code follows zero-facade, persistent-runtime, and zero-N+1 patterns.
---

# Kongono Feature Builder

Follow this workflow to ship a complete, idiomatic Kongono PHP 8.5 feature without introducing Laravel facades, ActiveRecord magic, or persistent-runtime state leaks.

## Step 1: Inspect the Live App Before Coding

- If the `kongono` MCP server (`.mcp.json` / `kongo mcp`) is connected, call `kongono_graph`, `kongono_routes`, and `kongono_openapi` first.
- Otherwise run `php vendor/bin/kongo routes --json` and inspect `app/bootstrap.php` to see registered services, middleware, and controllers.
- Tip: Scaffold full CRUD slices in one command with `php vendor/bin/kongo make:resource <Name>`, or single files with `make:controller|dto|migration|seeder|test|job|policy|middleware|factory|rule|mail`.

## Step 2: Evolve the Schema Safely (When Persistence Changes)

1. Scaffold or edit a migration using `Kongono\Database\Schema\Schema` and `Kongono\Database\Schema\Blueprint`:
   - `Schema::create('table', fn(Blueprint $t) => ...)` or `Schema::table('table', fn(Blueprint $t) => ...)` or `Schema::rename(...)`.
   - Use native helpers: `$t->id()`, `$t->uuid(...)`, `$t->foreignId(...)`, `$t->json(...)`, `$t->decimal(...)`, `$t->timestamps()`, `$t->softDeletes()`.
   - For PostgreSQL `CREATE INDEX CONCURRENTLY`, implement `Kongono\Database\Migration\NonTransactionalMigrationInterface`.
2. Dry-run and lint before migrating:
   ```bash
   php vendor/bin/kongo migrate --pretend
   php vendor/bin/kongo migrate:lint
   ```

## Step 3: Write the Input DTO (`Kongono\Validation`)

Create a `final readonly class` with `declare(strict_types=1);` using PHP 8.5 validation attributes:
- Built-in attributes: `#[Required]`, `#[Min]`, `#[Max]`, `#[Between]`, `#[Email]`, `#[Url]`, `#[Uuid]`, `#[Date]`, `#[Ip]`, `#[In]`, `#[NotIn]`, `#[Regex]`, `#[Confirmed]`, `#[RequiredIf]`, `#[ArrayOf]`.
- Backed enums coerce automatically; nested DTOs validate recursively with dotted keys.
- Implement `Kongono\Validation\ValidatableDtoInterface` (`validateSelf(ValidationErrors $errors)`) for cross-field rules or `Kongono\Validation\RuleInterface` for custom attributes.

## Step 4: Write Explicit Data Access (`Kongono\Database`)

- Inject `Kongono\Database\PdoConnection` (never use a static `DB::` facade).
- Use `QueryBuilder`: `->forTenant($tenant)` in multi-tenant apps, `->withoutTrashed()` / `->onlyTrashed()` / `->softDelete()` / `->restore()` for soft deletes, `->paginate()`, `->getAs(Dto::class)`.
- Prevent N+1 queries with `Kongono\Database\BatchLoader::hasMany()` and `BatchLoader::belongsTo()` instead of querying inside loops.
- Unscoped `UPDATE`/`DELETE` fails closed by design — always attach a `where(...)` clause.

## Step 5: Write & Register the Controller (`Kongono\Http`)

- Use `final readonly class` with constructor DI and route attributes (`#[Prefix]`, `#[Get]`, `#[Post]`, `#[Put]`, `#[Patch]`, `#[Delete]`, `#[Middleware]`, `#[Authenticated]`, `#[Can]`, `#[CacheControl]`, `#[Idempotent]`, `#[Feature]`).
- Type-hint `Request`, `Session`, `Principal`, route params (`int $id`), and the input DTO directly on the handler method.
- Register `$app->controller(MyController::class)` in `app/bootstrap.php`.
- Never store per-request state in static properties or read `$_GET`/`$_POST`/`$_COOKIE`/`$_FILES`/`$_SESSION`.

## Step 6: Write Global-Free Tests

- Use `Kongono\Http\Testing\TestClient` (`actingAs()`, `withStatefulCookies()`, `postMultipart()`, `followRedirects()`), `TestUpload::fakePng()`/`fakePdf()`, and `TestResponse` assertions (`assertCreated()`, `assertJsonPath()`, `assertValidationError()`).
- Isolate database tests with `Kongono\Database\Testing\DatabaseTestHelper::inRollbackTransaction($db, ...)` (nested `$db->transaction()` calls use SQL `SAVEPOINT`s) and `Kongono\Database\Testing\Factory::for(...)`.
- Control time with `Kongono\Core\Clock\FrozenClock` bound to `ClockInterface` and mock outbound HTTP with `Kongono\Security\Egress\SafeHttpClient::fake(...)` (still enforces SSRF).

## Verification Checklist

Run and confirm zero errors before finishing:
```bash
php vendor/bin/kongo routes
php vendor/bin/kongo test
php vendor/bin/kongo check
php vendor/bin/kongo audit --attack --strict
```
