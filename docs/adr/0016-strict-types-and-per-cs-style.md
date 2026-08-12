# ADR 0016: Strict Types and PER-CS Style

## Status

Accepted

## Date

2026-08-12

## Context

First-party PHP needs consistent coercion and formatting rules before framework APIs are designed. A large custom style dialect would create maintenance without architectural value.

## Decision

Every syntactically applicable first-party PHP source, test, and PHP-based repository configuration file uses `declare(strict_types=1);`. Use the current stable `@PER-CS` PHP CS Fixer ruleset plus strict-types declarations, unused-import removal, alphabetical import ordering, and supported multiline trailing commas.

Prefer native PHP types over PHPDoc-only contracts. Use PHPDoc for information PHP cannot express clearly, and treat `mixed` as an explicit boundary type rather than a modeling shortcut.

## Rationale

Strict types expose coercion assumptions at calling boundaries, while a maintained community standard keeps formatting deterministic without inventing project-specific syntax preferences.

## Consequences

Style checking is non-mutating in the quality gate. Contributors may run the separate formatter command and must review its output. PHP CS Fixer is allowed to apply the authorized risky strict-types rule.

## Rejected alternatives

Deprecated version-specific PER-CS aliases, header or license rules, speculative formatting rules, dynamic-property compatibility patterns, and PHPDoc duplication of obvious native types are rejected.

## What remains open

Style scope will expand only as first-party implementation locations are authorized. Public API documentation and compatibility policy remain separate decisions.
