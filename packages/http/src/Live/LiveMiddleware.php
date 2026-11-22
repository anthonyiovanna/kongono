<?php

declare(strict_types=1);

namespace Kongono\Http\Live;

use Kongono\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Serves Kongono\Live — a ~3 KB zero-build-step, strict-CSP-compliant hypermedia & SSE runtime.
 *
 * Supports declarative HTML attributes:
 *   - kgn-get / kgn-post / kgn-put / kgn-patch / kgn-delete
 *   - kgn-target="#selector"
 *   - kgn-swap="innerHTML|outerHTML|beforeend|afterbegin|delete|none"
 *   - kgn-confirm="Confirm action?"
 *   - kgn-stream="/events/stream" + kgn-event="item.updated" (native EventSource DOM swaps)
 *
 * Automatically forwards `X-Kongono-Live: true` and `X-CSRF-Token`.
 */
final readonly class LiveMiddleware implements MiddlewareInterface
{
    public const string DEFAULT_SCRIPT_PATH = '/_kongono/live.js';

    public function __construct(
        private string $scriptPath = self::DEFAULT_SCRIPT_PATH,
        private int $maxAgeSeconds = 86400,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $method = strtoupper($request->getMethod());
        if (('GET' === $method || 'HEAD' === $method) && $request->getUri()->getPath() === $this->scriptPath) {
            $script = self::scriptSource();
            $etag = 'W/"' . hash('sha256', $script) . '"';

            if (trim($request->getHeaderLine('If-None-Match')) === $etag) {
                return new Response(304, [
                    'ETag' => $etag,
                    'Cache-Control' => sprintf('public, max-age=%d', max(0, $this->maxAgeSeconds)),
                ]);
            }

            return new Response(200, [
                'Content-Type' => 'application/javascript; charset=utf-8',
                'Cache-Control' => sprintf('public, max-age=%d', max(0, $this->maxAgeSeconds)),
                'ETag' => $etag,
                'X-Content-Type-Options' => 'nosniff',
            ], 'HEAD' === $method ? null : \Kongono\Http\Stream::fromString($script));
        }

        return $handler->handle($request);
    }

    public static function scriptSource(): string
    {
        return <<<'JS'
(() => {
  "use strict";
  if (window.KongonoLive) return;

  const METHODS = ["get", "post", "put", "patch", "delete"];
  const streams = new WeakMap();

  function getCsrf(el) {
    const form = el && el.closest ? el.closest("form") : null;
    const input = form ? form.querySelector('input[name="_csrf"]') : document.querySelector('input[name="_csrf"]');
    if (input && input.value) return input.value;
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? (meta.getAttribute("content") || "") : "";
  }

  function applySwap(target, html, mode) {
    if (!target || mode === "none") return;
    if (mode === "delete") {
      target.remove();
      return;
    }
    if (mode === "outerHTML") {
      target.outerHTML = html;
    } else if (mode === "beforeend" || mode === "afterbegin") {
      target.insertAdjacentHTML(mode, html);
    } else {
      target.innerHTML = html;
    }
    connectStreams(document);
  }

  function findLiveAttr(el) {
    for (const m of METHODS) {
      const attr = "kgn-" + m;
      if (el.hasAttribute(attr)) {
        return { method: m.toUpperCase(), url: el.getAttribute(attr) || "" };
      }
    }
    return null;
  }

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

  async function dispatchLive(el, form) {
    const live = findLiveAttr(el);
    if (!live || !live.url) return;

    const targetUrl = isSameOriginUrl(live.url);
    if (!targetUrl) return;

    const confirmMsg = el.getAttribute("kgn-confirm");
    if (confirmMsg && !window.confirm(confirmMsg)) return;

    const targetSel = el.getAttribute("kgn-target");
    const target = targetSel ? document.querySelector(targetSel) : el;
    const swap = el.getAttribute("kgn-swap") || "innerHTML";

    const headers = {
      "X-Kongono-Live": "true",
      "Accept": "text/html"
    };
    const csrf = getCsrf(form || el);
    if (csrf) headers["X-CSRF-Token"] = csrf;

    const init = { method: live.method, headers, credentials: "same-origin" };
    if (form && live.method !== "GET") {
      init.body = new FormData(form);
    }

    el.setAttribute("aria-busy", "true");
    try {
      const res = await fetch(targetUrl.href, init);
      const finalUrl = isSameOriginUrl(res.url || targetUrl.href);
      if (!finalUrl) return;
      const redirect = res.headers.get("X-Kongono-Redirect");
      if (redirect && !/[\u0000-\u001F\u007F]/.test(redirect)) {
        try {
          const parsed = new URL(redirect, window.location.origin);
          if (parsed.protocol === "http:" || parsed.protocol === "https:") {
            window.location.assign(parsed.href);
            return;
          }
        } catch (_e) {}
      }
      const pushUrl = res.headers.get("X-Kongono-Push-Url");
      if (pushUrl && pushUrl.startsWith("/") && !pushUrl.startsWith("//") && !pushUrl.startsWith("/\\") && window.history && window.history.pushState) {
        try {
          window.history.pushState({}, "", pushUrl);
        } catch (_e) {}
      }
      const text = await res.text();
      const ct = (res.headers.get("Content-Type") || "").toLowerCase();
      if (res.ok || text.length > 0) {
        if (ct.startsWith("text/html")) {
          applySwap(target, text, swap);
        } else if (target && swap !== "none" && swap !== "delete") {
          target.textContent = text;
        }
      }
      el.dispatchEvent(new CustomEvent("kongono:swapped", { bubbles: true, detail: { status: res.status, target } }));
    } finally {
      el.removeAttribute("aria-busy");
    }
  }

  function connectStreams(root) {
    const nodes = (root || document).querySelectorAll("[kgn-stream]");
    nodes.forEach((node) => {
      if (streams.has(node)) return;
      const url = node.getAttribute("kgn-stream");
      const streamUrl = isSameOriginUrl(url || "");
      if (!streamUrl || typeof EventSource === "undefined") return;
      const eventName = node.getAttribute("kgn-event") || "message";
      const swap = node.getAttribute("kgn-swap") || "innerHTML";
      const es = new EventSource(streamUrl.href);
      streams.set(node, es);
      const handler = (ev) => {
        if (!document.contains(node)) {
          es.close();
          streams.delete(node);
          return;
        }
        applySwap(node, ev.data || "", swap);
      };
      if (eventName === "message") {
        es.onmessage = handler;
      } else {
        es.addEventListener(eventName, handler);
      }
    });
  }

  document.addEventListener("click", (ev) => {
    const el = ev.target && ev.target.closest ? ev.target.closest("[kgn-get],[kgn-post],[kgn-put],[kgn-patch],[kgn-delete]") : null;
    if (!el || el.tagName === "FORM") return;
    ev.preventDefault();
    dispatchLive(el, null);
  });

  document.addEventListener("submit", (ev) => {
    const form = ev.target;
    if (!form || form.tagName !== "FORM") return;
    if (!findLiveAttr(form)) return;
    ev.preventDefault();
    dispatchLive(form, form);
  });

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", () => connectStreams(document));
  } else {
    connectStreams(document);
  }

  window.KongonoLive = { version: "1.3.0", connectStreams };
})();
JS;
    }
}
