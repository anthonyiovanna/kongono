<?php

declare(strict_types=1);

namespace Kongono\Http\Bridge;

use Closure;
use Kongono\Http\Request;
use Kongono\Http\Response;
use Kongono\Http\Stream;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * First-class React / Vue / Svelte SPA Bridge middleware (Inertia-class protocol, ~3 KB zero-dep JS).
 *
 * - Serves `/_kongono/bridge.js` (`window.KongonoBridge`) with ETag caching and strict CSP safety
 * - Injects shared props (auth, flash, CSRF) into every `Response::page(...)`
 * - Enforces asset version synchronization (`409 Conflict` + `X-Kongono-Bridge-Location` on stale bundles)
 * - Supports lazy partial reloads (`X-Kongono-Bridge-Partial-Component` + `X-Kongono-Bridge-Partial-Data`)
 * - Converts `302` redirects after `PUT`/`PATCH`/`DELETE` into `303 See Other` per HTTP semantics
 */
final readonly class BridgeMiddleware implements MiddlewareInterface
{
    public const string DEFAULT_SCRIPT_PATH = '/_kongono/bridge.js';
    public const string RUNTIME_VERSION = '1.10.1';

    /**
     * @param string|Closure(): string                                                     $version        Asset bundle version string or lazy resolver (e.g. manifest hash)
     * @param array<string, mixed>|Closure(ServerRequestInterface): array<string, mixed>   $sharedProps    Shared props merged into every page (e.g. auth user, flash, csrfToken)
     * @param ?Closure(array{component: string, props: array<string, mixed>, url: string, version: string, errors: array<string, list<string>>, csrfToken: ?string}, string): string $rootTemplate Custom initial HTML shell renderer receiving ($pageArray, $escapedDataPageJson)
     */
    public function __construct(
        private string|Closure $version = '1',
        private array|Closure $sharedProps = [],
        private ?Closure $rootTemplate = null,
        private string $scriptPath = self::DEFAULT_SCRIPT_PATH,
        private int $maxAgeSeconds = 86400,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $method = strtoupper($request->getMethod());
        $path = $request->getUri()->getPath();

        if (('GET' === $method || 'HEAD' === $method) && $path === $this->scriptPath) {
            $script = self::scriptSource();
            $etag = 'W/"' . hash('sha256', $script) . '"';
            $hasVersion = str_contains($request->getUri()->getQuery(), 'v=');
            $cacheControl = $hasVersion
                ? sprintf('public, max-age=%d, immutable', max(0, $this->maxAgeSeconds))
                : 'public, max-age=0, must-revalidate';
            if (trim($request->getHeaderLine('If-None-Match')) === $etag) {
                return new Response(304, [
                    'ETag' => $etag,
                    'Cache-Control' => $cacheControl,
                ]);
            }

            return new Response(200, [
                'Content-Type' => 'application/javascript; charset=utf-8',
                'Cache-Control' => $cacheControl,
                'ETag' => $etag,
                'X-Content-Type-Options' => 'nosniff',
            ], 'HEAD' === $method ? null : Stream::fromString($script));
        }

        $isBridge = 'true' === strtolower(trim($request->getHeaderLine('X-Kongono-Bridge')));
        $currentVersion = $this->version instanceof Closure ? ($this->version)() : $this->version;

        // Asset version mismatch on GET Bridge visits -> 409 Conflict instructing client to hard reload
        if ($isBridge && 'GET' === $method) {
            $clientVersion = trim($request->getHeaderLine('X-Kongono-Bridge-Version'));
            if ('' !== $clientVersion && $clientVersion !== $currentVersion) {
                $target = $path . ('' !== $request->getUri()->getQuery() ? '?' . $request->getUri()->getQuery() : '');
                if (
                    !str_starts_with($target, '/')
                    || str_starts_with($target, '//')
                    || str_starts_with($target, '/\\')
                    || 1 === preg_match('/[\x00-\x1f\x7f\\\\]/', $target)
                ) {
                    $target = '/';
                }

                return new Response(409, [
                    'X-Kongono-Bridge-Location' => $target,
                    'Vary' => 'X-Kongono-Bridge, X-Kongono-Bridge-Version',
                ]);
            }
        }

        $response = $handler->handle($request);

        // Normalize 302 redirects on PUT/PATCH/DELETE to 303 See Other for SPA visits
        if ($isBridge && 302 === $response->getStatusCode() && in_array($method, ['PUT', 'PATCH', 'DELETE'], true)) {
            return $response->withStatus(303);
        }

        if (!$response instanceof Response) {
            return $response;
        }

        $payload = $response->bridgePayload();
        if (null === $payload) {
            return $response;
        }

        $shared = $this->sharedProps instanceof Closure ? ($this->sharedProps)($request) : $this->sharedProps;
        $kgnReq = $request instanceof Request ? $request : null;
        $resolved = $payload->resolve($kgnReq, $shared, $currentVersion);
        $json = json_encode(
            $resolved,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT,
        );

        $varyHeader = self::mergeVary(
            $response->getHeaderLine('Vary'),
            ['X-Kongono-Bridge', 'X-Kongono-Bridge-Version', 'X-Kongono-Bridge-Partial-Component', 'X-Kongono-Bridge-Partial-Data'],
        );
        $hasCsrf = (null !== $resolved['csrfToken'] && '' !== $resolved['csrfToken'])
            || (is_string($resolved['props']['csrfToken'] ?? null) && '' !== $resolved['props']['csrfToken'])
            || (is_string($resolved['props']['csrf'] ?? null) && '' !== $resolved['props']['csrf'])
            || (is_string($resolved['props']['_csrf'] ?? null) && '' !== $resolved['props']['_csrf']);

        if ($isBridge) {
            $out = $response
                ->withHeader('Content-Type', 'application/json; charset=utf-8')
                ->withHeader('X-Kongono-Bridge', 'true')
                ->withHeader('Vary', $varyHeader)
                ->withBody(Stream::fromString($json));

            return $hasCsrf ? $out->withHeader('Cache-Control', 'private, no-store') : $out;
        }

        $escapedJson = htmlspecialchars($json, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        if (null !== $this->rootTemplate) {
            $html = ($this->rootTemplate)($resolved, $escapedJson);
        } else {
            $escapedTitle = htmlspecialchars($resolved['component'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $html = sprintf(
                "<!doctype html>\n<html lang=\"en\">\n<head><meta charset=\"utf-8\"><meta name=\"viewport\" content=\"width=device-width,initial-scale=1\"><title>%s</title></head>\n<body><div id=\"app\" data-page=\"%s\"></div><script src=\"%s\" defer></script></body>\n</html>",
                $escapedTitle,
                $escapedJson,
                htmlspecialchars($this->scriptPath, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            );
        }

        $out = $response
            ->withHeader('Content-Type', 'text/html; charset=utf-8')
            ->withHeader('Vary', $varyHeader)
            ->withBody(Stream::fromString($html));

        return $hasCsrf ? $out->withHeader('Cache-Control', 'private, no-store') : $out;
    }

    /** @param list<string> $tokens */
    private static function mergeVary(string $existing, array $tokens): string
    {
        $parts = '' !== trim($existing)
            ? array_values(array_filter(array_map('trim', explode(',', $existing)), static fn(string $v): bool => '' !== $v))
            : [];
        $lower = array_map('strtolower', $parts);
        foreach ($tokens as $token) {
            if (!in_array(strtolower($token), $lower, true) && !in_array('*', $lower, true)) {
                $parts[] = $token;
                $lower[] = strtolower($token);
            }
        }

        return implode(', ', $parts);
    }

    public static function scriptSource(): string
    {
        $script = <<<'JS'
(() => {
  "use strict";
  if (window.KongonoBridge) return;

  let currentPage = null;
  let mountHandler = null;
  let resolveHandler = null;
  let rootEl = null;

  function isSameOriginUrl(rawUrl) {
    if (!rawUrl || /[\u0000-\u001F\u007F]/.test(rawUrl)) return null;
    try {
      const parsed = new URL(rawUrl, window.location.origin);
      if (parsed.origin !== window.location.origin) return null;
      return parsed;
    } catch (_e) {
      return null;
    }
  }

  function getCsrfToken() {
    if (currentPage && currentPage.csrfToken) return String(currentPage.csrfToken);
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (meta && meta.getAttribute("content")) return meta.getAttribute("content");
    const input = document.querySelector('input[name="_csrf"]');
    return input ? (input.value || "") : "";
  }

  async function renderPage(page, options = {}) {
    currentPage = page;
    if (!resolveHandler || !mountHandler || !rootEl) return page;
    const mod = await resolveHandler(page.component);
    mountHandler({ el: rootEl, Component: mod && mod.default ? mod.default : mod, props: page.props || {}, page });
    if (!options.preserveScroll) window.scrollTo(0, 0);
    window.dispatchEvent(new CustomEvent("kongono:bridge:navigated", { detail: { page } }));
    return page;
  }

  async function visit(rawUrl, options = {}) {
    const parsed = isSameOriginUrl(rawUrl);
    if (!parsed) return null;

    const method = String(options.method || "GET").toUpperCase();
    const headers = {
      "X-Kongono-Bridge": "true",
      "Accept": "application/json"
    };
    if (currentPage && currentPage.version) {
      headers["X-Kongono-Bridge-Version"] = String(currentPage.version);
    }
    const csrf = getCsrfToken();
    if (csrf && method !== "GET" && method !== "HEAD") {
      headers["X-CSRF-Token"] = csrf;
    }
    if (Array.isArray(options.only) && options.only.length > 0 && currentPage && currentPage.component) {
      headers["X-Kongono-Bridge-Partial-Component"] = currentPage.component;
      headers["X-Kongono-Bridge-Partial-Data"] = options.only.join(",");
    }

    const init = { method, headers, credentials: "same-origin" };
    if (options.data !== undefined && method !== "GET" && method !== "HEAD") {
      if (options.data instanceof FormData) {
        init.body = options.data;
      } else {
        headers["Content-Type"] = "application/json";
        init.body = JSON.stringify(options.data);
      }
    }

    const res = await fetch(parsed.href, init);

    if (res.status === 409) {
      const hardLoc = res.headers.get("X-Kongono-Bridge-Location");
      const safeLoc = isSameOriginUrl(hardLoc || parsed.href);
      if (safeLoc) window.location.assign(safeLoc.href);
      return null;
    }

    const finalUrl = isSameOriginUrl(res.url || parsed.href);
    if (!finalUrl) return null;

    const ct = (res.headers.get("Content-Type") || "").toLowerCase();
    if (res.status === 422 && ct.includes("application/json")) {
      const errBody = await res.json();
      const rawErrors = errBody && (errBody.errors || errBody.fields);
      const errors = (rawErrors && typeof rawErrors === "object") ? rawErrors : {};
      if (typeof options.onError === "function") options.onError(errors);
      return { ok: false, status: 422, errors };
    }

    if (!ct.includes("application/json")) {
      window.location.assign(finalUrl.href);
      return null;
    }

    const nextPage = await res.json();
    if (Array.isArray(options.only) && options.only.length > 0 && currentPage && nextPage.component === currentPage.component) {
      const merged = Object.create(null);
      const safeMerge = (src) => {
        if (!src || typeof src !== "object") return;
        for (const k of Object.keys(src)) {
          if (k !== "__proto__" && k !== "constructor" && k !== "prototype") merged[k] = src[k];
        }
      };
      safeMerge(currentPage.props);
      safeMerge(nextPage.props);
      nextPage.props = merged;
    }

    const historyPage = { ...nextPage, csrfToken: null };
    const targetPath = finalUrl.pathname + finalUrl.search + finalUrl.hash;
    if (window.history) {
      if (options.replace) {
        window.history.replaceState({ kongonoBridge: true, page: historyPage }, "", targetPath);
      } else {
        window.history.pushState({ kongonoBridge: true, page: historyPage }, "", targetPath);
      }
    }

    await renderPage(nextPage, options);
    if (typeof options.onSuccess === "function") options.onSuccess(nextPage);
    return { ok: res.ok, status: res.status, page: nextPage };
  }

  function reload(options = {}) {
    return visit(window.location.href, Object.assign({ replace: true, preserveScroll: true }, options));
  }

  function createForm(initialValues = {}) {
    const state = {
      data: Object.assign({}, initialValues),
      errors: {},
      processing: false,
      recentlySuccessful: false,
      reset() {
        state.data = Object.assign({}, initialValues);
        state.errors = {};
      },
      async submit(method, url, opts = {}) {
        state.processing = true;
        state.errors = {};
        state.recentlySuccessful = false;
        try {
          const result = await visit(url, Object.assign({}, opts, {
            method,
            data: state.data,
            onError: (errs) => {
              state.errors = errs || {};
              if (typeof opts.onError === "function") opts.onError(state.errors);
            },
            onSuccess: (page) => {
              state.recentlySuccessful = true;
              if (typeof opts.onSuccess === "function") opts.onSuccess(page);
            }
          }));
          return result;
        } finally {
          state.processing = false;
        }
      },
      post: (url, opts) => state.submit("POST", url, opts),
      put: (url, opts) => state.submit("PUT", url, opts),
      patch: (url, opts) => state.submit("PATCH", url, opts),
      delete: (url, opts) => state.submit("DELETE", url, opts)
    };
    return state;
  }

  function boot({ el = "#app", resolve, mount }) {
    rootEl = typeof el === "string" ? document.querySelector(el) : el;
    resolveHandler = resolve;
    mountHandler = mount;
    if (!rootEl) return null;

    const raw = rootEl.getAttribute("data-page");
    if (!raw) return null;
    const initial = JSON.parse(raw);
    if (window.history) {
      window.history.replaceState({ kongonoBridge: true, page: initial }, "", window.location.href);
    }
    return renderPage(initial, { preserveScroll: true });
  }

  document.addEventListener("click", (ev) => {
    if (ev.defaultPrevented || ev.button !== 0 || ev.metaKey || ev.ctrlKey || ev.shiftKey || ev.altKey) return;
    const link = ev.target && ev.target.closest ? ev.target.closest("a[k-link], a[data-kgn-link]") : null;
    if (!link) return;
    const href = link.getAttribute("href");
    if (!href || !isSameOriginUrl(href)) return;
    ev.preventDefault();
    visit(href, {
      method: link.getAttribute("data-method") || "GET",
      preserveScroll: link.hasAttribute("data-preserve-scroll")
    });
  });

  window.addEventListener("popstate", (ev) => {
    if (ev.state && ev.state.kongonoBridge && ev.state.page) {
      renderPage(ev.state.page, { preserveScroll: true });
    }
  });

  // Astro-style Hybrid Bridge Islands inside Kongono Live 2.5 pages (`@island('Chart', $props, 'visible')`)
  const mountedIslands = new WeakMap();

  function mountIslands({ resolve, mount, root = document }) {
    if (typeof resolve !== "function" || typeof mount !== "function") return;
    const hydrateOne = async (el) => {
      if (mountedIslands.has(el)) return;
      mountedIslands.set(el, true);
      const name = el.getAttribute("data-k-island") || "";
      if (!name) return;
      let props = {};
      try { props = JSON.parse(el.getAttribute("data-k-props") || "{}"); } catch (_e) {}
      const mod = await resolve(name);
      const Component = (mod && mod.default) ? mod.default : mod;
      const runMount = (nextProps) => mount({ el, Component, props: nextProps, component: name });
      runMount(props);
      el.addEventListener("kongono:island-props", (ev) => {
        if (ev.detail && ev.detail.props) runMount(ev.detail.props);
      });
    };

    const scan = (scope) => {
      const nodes = (scope || document).querySelectorAll("[data-k-island]");
      nodes.forEach((el) => {
        if (mountedIslands.has(el)) return;
        const strategy = (el.getAttribute("data-k-hydrate") || "load").toLowerCase();
        if (strategy === "visible" && typeof IntersectionObserver !== "undefined") {
          const io = new IntersectionObserver((entries) => {
            for (const entry of entries) {
              if (entry.isIntersecting) {
                io.disconnect();
                hydrateOne(el);
              }
            }
          }, { rootMargin: "150px" });
          io.observe(el);
        } else if (strategy === "idle" && typeof window.requestIdleCallback === "function") {
          window.requestIdleCallback(() => hydrateOne(el));
        } else {
          hydrateOne(el);
        }
      });
    };

    scan(root);
    document.addEventListener("kongono:swapped", (ev) => scan((ev.detail && ev.detail.target) || document));
  }

  window.KongonoBridge = {
    version: "__RUNTIME_VERSION__",
    boot,
    mountIslands,
    visit,
    reload,
    createForm,
    page: () => currentPage
  };
})();
JS;

        return str_replace('__RUNTIME_VERSION__', self::RUNTIME_VERSION, $script);
    }
}
