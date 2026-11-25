# Kongono Editor Extension (Zero-Logic LSP Bridge)

Nothing outside Kongono knows Kongono better than Kongono itself. This zero-dependency extension contains **zero hardcoded rules** — it simply connects VS Code, Cursor, or Windsurf directly to your project's own `php vendor/bin/kongo lsp` server over stdio.

## Features
- **Live 17-Rule Squiggles:** Full-Stack Contracts (`<a href>`, `<form action>`, `<form>` inputs ↔ PHP 8.5 Input DTOs), Live 2.5 AST & `k-show` scope, CSRF `_csrf` vs `_token`, compiled `.kgn.html` PHP 8.5 syntax, DI container checks, and persistent-worker static leaks.
- **Plain-English 5W1H Hover Cards:** Hover any diagnostic line for `What`, `Why`, `Who`, `Did you mean`, and `How to fix`.
- **One-Click Quick Fixes:** Instant CodeActions powered directly by Kongono's `AppAutoFixer`.
- **`.kgn.html` Syntax Highlighting:** Directives (`@extends`, `@state`, `@liveScript`), `<x-ui.*>` Zokeibi components, and Live 2.5 `k-on:*` / `k-show` / `k-model` attributes.

## Quick Setup
- **VS Code / Cursor / Windsurf:** Open this folder via `code --extensionDevelopmentPath=editors/vscode-kongono` or package with `npx @vscode/vsce package` (zero `npm install` required!).
- **Neovim (`nvim-lspconfig`):** Start `php vendor/bin/kongo lsp` as a stdio LSP server for `php` and `html` buffers.
- **Zed:** Register `php vendor/bin/kongo lsp` in your project `.zed/settings.json` LSP configuration.
