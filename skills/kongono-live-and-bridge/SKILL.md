---
name: kongono-live-and-bridge
description: >-
  Guide for building reactive server-rendered interfaces with Kongono Live 2.5
  (.kgn.html, keyed DOM morphing, strict-CSP zero-eval signals) or React/Vue/Svelte
  pages and hybrid islands with Kongono Bridge. Use when the user builds UI pages,
  forms, live search, drag-and-drop, SSE updates, or React/Vue/Svelte components
  in a Kongono app.
---

# Kongono Live 2.5 & Bridge UI Workflow

Choose the lightest rendering mode that fits the page — never add an npm build chain when Live 2.5 handles it natively.

## Step 1: Choose Live 2.5 vs. Bridge vs. Hybrid Island

| Need | Use |
| --- | --- |
| Forms, search/filter, tabs, modals, CRUD tables, drag-and-drop, SSE live feeds, offline drafts | **Kongono Live 2.5** (zero npm, ~3 KB, strict CSP) |
| Mostly server-rendered `.kgn.html` page with one rich JS chart/editor widget | **Hybrid `@island`** (`@island('ChartWidget', $props)`) |
| Full client-side SPA screen in React, Vue, or Svelte | **Kongono Bridge** (`Response::page('Orders/Show', $props)`) |

## Step 2: Author Safe `.kgn.html` Templates (`Kongono\View`)

- Escaping: `{{ $value }}` auto-escapes contextually against XSS.
- Rich user HTML: always pass through `@safeHtml($html)` (`Kongono\View\HtmlSanitizer`) — never emit raw unescaped user HTML.
- Forms with PRG flash: pair session flash with `@old('field')` and `@error('field') ... @enderror` (sensitive fields like passwords/tokens are stripped automatically before flashing).
- Layouts & components: `@extends('layouts/app')`, `@section('content')...@endsection`, `@yield('content')`, `<x-card :title="$title">...</x-card>`, `@json($data)`, `@t('key')`.
- Production workers: wire `$app->views(autoReload: $debug)` so FrankenPHP caches compiled closures in memory.

## Step 3: Wire Kongono Live 2.5 (`Kongono\Http\Live`)

1. Mount `Kongono\Http\Live\LiveMiddleware` in `app/bootstrap.php` and include `@liveScript` in your layout.
2. Use declarative attributes on HTML elements (never inline `onclick` JS or `eval()`):
   - Requests & morphing: `kgn-get`, `kgn-post`, `kgn-target="#list"`, `kgn-swap="morph"`.
   - Multi-region updates: include elements with `kgn-oob="true"` (matching `id`) in fragment responses.
   - Offline & drafts: `k-persist="draft-key"` preserves form state and queues idempotent mutations offline.
   - Drag-and-drop ordering: `kgn-sortable`.
   - Live SSE streams: `kgn-stream="/events/channel"` backed by `EventBus` / `RedisEventBus` + `Response::sse(...)`.
   - Strict-CSP signals: use safe cached-AST arithmetic/ternary/pipe expressions (zero `unsafe-eval`).
3. Detect Live requests in controllers with `$request->isLive()` and return `Response::fragment($html)`, or return all formats from one handler via `Response::adaptive($request, html: ..., fragment: ..., bridge: ..., json: ...)`.

## Step 4: Wire Kongono Bridge (`Kongono\Http\Bridge`) When Using React/Vue/Svelte

1. Scaffold once: `php vendor/bin/kongo bridge:init` (supports React 19, Vue 3, Svelte 5) and mount `BridgeMiddleware` + `@bridgeScript` / `@vite(...)`.
2. Return pages from controllers with `Response::page('Tasks/Index', ['tasks' => $tasks])`.
3. Regenerate strict TypeScript route & DTO types whenever DTOs/routes change:
   ```bash
   php vendor/bin/kongo bridge:types
   ```
4. For reusable strict-CSP Live UI primitives without npm, run `php vendor/bin/kongo ui:add`.

## Verification Checklist

1. Test both full-page HTML and Live/Bridge requests using `TestClient`:
   - `(new TestClient($app))->withLive()->postForm(...)`
   - `(new TestClient($app))->withBridge()->get('/tasks')->assertOk()`
2. Verify strict CSP and XSS safety:
   ```bash
   php vendor/bin/kongo test
   php vendor/bin/kongo audit --attack --strict
   ```
