# Contributing to Kongono Framework

Thank you for your interest in contributing to **Kongono**!

## Core Engineering Principles

Every contribution must honor Kongono's five engineering rules:

1. **Zero Legacy, Pure PHP 8.5+:** Use `declare(strict_types=1)`, `final readonly` classes, native enums, attributes, and strict return/parameter types. **No Symfony runtime packages, no global magic facades, no PHP 7/8.1 shims.**
2. **Persistent-Runtime First:** Never store per-request mutable state in static properties or globals (`$_SESSION`, etc.). Every operation must clean up deterministically so apps behave identically under PHP-FPM, `kongo serve`, FrankenPHP, and RoadRunner.
3. **Joyful Day-1 DX:** Keep strict safety underneath and clean, concise, expressive public APIs on top.
4. **Nondisclosing Diagnostics:** Always scrub secrets with `SecretScrubber` before logging, rendering dev errors, or sending payloads to AI/MCP clients.
5. **Real-World Proof:** Every feature must include PHPUnit 13 tests, pass PHPStan Level 9 + Strict Rules with 0 errors, pass PHP-CS-Fixer with 0 diffs, and (when user-facing) be exercised in `examples/starter-app`.

## Local Development Setup

```bash
git clone https://github.com/anthonyiovanna/kongono.git kongono
cd kongono
composer install
php bin/kongo doctor --app=examples/starter-app
```

## Running the Quality Suite

Before opening a pull request, run:

```bash
php bin/kongo check
```

Or run each gate individually:

```bash
composer cs        # PHP-CS-Fixer (auto-fix with `composer cs:fix`)
composer analyse   # PHPStan Level 9 + Strict Rules
composer test      # PHPUnit 13
```

To run live PostgreSQL (`pgvector`) + Redis integration tests locally:

```bash
KONGONO_TEST_POSTGRES_URL="pgsql://kongono:secret@127.0.0.1:5432/kongono_test" \
KONGONO_TEST_REDIS_URL="redis://127.0.0.1:6379/0" \
composer test:integration
```

## Pull Request Checklist

- [ ] Targets `main` with a focused, atomic change.
- [ ] All 11 package manifests pass `composer validate --strict`.
- [ ] `composer cs`, `composer analyse`, and `composer test` pass with 0 warnings and 0 deprecations.
- [ ] Documentation (`README.md` / `docs/`) updated for any new public API or CLI command.
