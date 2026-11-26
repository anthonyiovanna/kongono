# Kongono Framework — Editor Extension

Official zero-dependency editor extension for [**Kongono Framework**](https://kongono.com) (PHP 8.5+) and [**Zokeibi UI**](https://zokeibi.com).

Nothing outside Kongono knows Kongono better than Kongono itself. This extension contains **zero hardcoded rules and zero npm runtime dependencies** — it streams live LSP 3.17 frames directly between your editor (VS Code, Cursor, Windsurf, Antigravity, VSCodium) and your project's own `kongo lsp` server over stdio.

## Features

- **Live 17-Rule Full-Stack Contract Diagnostics:** Red/yellow squiggles for broken routes (`<a href>`, `<form action>`), `<form>` input ↔ PHP 8.5 Input DTO mismatches, Live 2.5 AST & `k-show` scope bugs, CSRF `_csrf` vs `_token`, compiled `.kgn.html` PHP 8.5 syntax, DI container issues, and persistent-worker static leaks.
- **Plain-English 5W1H Hover Cards:** Hover any diagnostic line for `What`, `Why`, `Who`, `Did you mean`, and `How to fix`.
- **One-Click Quick Fixes:** Instant CodeActions backed directly by Kongono's `AppAutoFixer`.
- **`.kgn.html` Syntax Highlighting:** Kongono directives (`@extends`, `@state`, `@csrf`, `@liveScript`, `@safeHtml`), `<x-ui.*>` Zokeibi components, and Live 2.5 `k-*` / `kgn-*` attributes.
- **Command Palette Actions:** Run `kongo prove`, `kongo diagnose`, `kongo diagnose --fix`, or restart `kongo lsp` directly from your editor.
- **Workspace Trust First:** Refuses to spawn local PHP binaries in untrusted workspaces and marks hover Markdown untrusted by default.

## Requirements

- PHP 8.5+ on your `PATH` (or configured via `kongono.phpPath`).
- A Kongono project containing `vendor/bin/kongo` (or `bin/kongo` in framework development).

## Settings

| Setting | Default | Description |
| --- | --- | --- |
| `kongono.phpPath` | `php` | Path to your PHP 8.5+ binary |
| `kongono.enableLsp` | `true` | Enable live diagnostics, 5W1H hovers, and Quick Fixes via `kongo lsp` |
| `kongono.appPath` | *(workspace root)* | Optional `--app=` root override for monorepos |

## Links

- **Docs:** https://kongono.com
- **GitHub:** https://github.com/kongono/framework
- **Zokeibi UI:** https://zokeibi.com
- **Discord Community:** https://discord.gg/SXWjgBGwrZ
