# Kongono v1 Engineering Policy

Kongono is a modern, zero-legacy PHP 8.5+ application framework built from the ground up with no Symfony dependency and three built-in AI companions:

- **Oshie** (`kongo oshie`) — Interactive AI tutor for learning Kongono.
- **Chie** (`kongo chie`) — Project-aware AI builder and scaffolder.
- **Hantei** (`kongo hantei`) — AI debugger, failure analyzer, and test generator.

## Core Principles

1. **Zero Legacy, Pure PHP 8.5+**: Use strict types, `readonly` classes, enums, attributes, and native PHP 8.5 features. No Symfony packages, no global magic facades, no PHP 7/8.1 shims.
2. **Persistent-Runtime First**: Never store per-request mutable state in static properties or globals. Every operation scope has deterministic lifecycle cleanup (works identically on PHP-FPM, `kongo serve`, FrankenPHP, and RoadRunner).
3. **Joyful Developer Experience**: Strict safety lives underneath; public APIs must be concise, expressive, type-safe, and effortless to use on Day 1.
4. **Nondisclosing Diagnostics**: Errors carry structured Safe vs Protected/Forbidden context so secrets are scrubbed before rendering to users or sending to external AI providers.
5. **Real-World Proof**: Every package must pass `composer test` (PHPUnit), `composer analyse` (PHPStan level 9 + strict rules), and `composer cs` (PHP-CS-Fixer), and be exercised in a real runnable app (`examples/starter-app`).
