# ADR 0012: Structured CLI Contract

## Status

Accepted

## Date

2026-08-12

## Context

The CLI will be consumed by humans, automation, software, and AI tooling. Human-formatted terminal output alone is not a reliable machine contract.

## Decision

The official future executable is `kongo`. Meaningful commands should support structured machine-readable output and safe automation behavior where applicable, especially `--json`, `--dry-run`, and `--no-interaction`.

## Rationale

Structured contracts make commands deterministic, inspectable, composable, and safer for non-human consumers without removing a strong human experience.

## Consequences

Future commands such as `kongo new`, `kongo dev`, `kongo build`, `kongo check`, `kongo test`, and `kongo graph` must consider both human and machine consumers. Examples record direction, not an implemented command inventory.

## Rejected alternatives

Human-only output, hidden interactivity, and an unapproved `kgo` alias are rejected for the current direction.

## What remains open

CLI architecture, executable packaging, command APIs, JSON schemas, exit codes, error contracts, interactive behavior, compatibility, and alias policy remain open. No executable is created in Phase 0A.
