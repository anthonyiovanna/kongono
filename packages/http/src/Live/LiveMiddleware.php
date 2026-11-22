<?php

declare(strict_types=1);

namespace Kongono\Http\Live;

use Kongono\Http\Response;
use Kongono\Http\Stream;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Serves Kongono\Live 2.0 — a zero-build-step, strict-CSP-compliant (zero eval / zero new Function)
 * reactive hypermedia, keyed DOM morphing, client signals, optimistic UI, and SSE runtime.
 *
 * Server-driven Hypermedia & Morphing:
 *   - kgn-get / kgn-post / kgn-put / kgn-patch / kgn-delete
 *   - kgn-target="#selector"
 *   - kgn-swap="morph|morphInner|innerHTML|outerHTML|beforeend|afterbegin|delete|none"
 *   - kgn-trigger="click|submit|input delay:250ms|change|keyup"
 *   - kgn-transition="true" (native View Transitions API)
 *   - kgn-optimistic-text="Saved!" / kgn-optimistic-class="is-active" / kgn-disable
 *   - kgn-confirm="Confirm action?"
 *   - kgn-stream="/events/stream" + kgn-event="item.updated"
 *
 * Zero-eval CSP-strict Client Signals:
 *   - k-state='{"open":false,"count":0,"tab":"overview","query":""}' (or `@state([...])`)
 *   - k-text="expr" / k-show="expr" / k-hide="expr" / k-model="path"
 *   - k-class:active="expr" / k-attr:aria-expanded="expr" / k-filter="query"
 *   - k-on:click="toggle(open)" / "inc(count)" / "dec(count)" / "set(tab, 'billing')" / "reset()" / "dispatch(ev)" / "call(fn)"
 *   - Event modifiers: .prevent, .stop, .self, .enter, .escape
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
            ], 'HEAD' === $method ? null : Stream::fromString($script));
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
  const BLOCKED_KEYS = new Set(["__proto__", "prototype", "constructor"]);
  const URL_ATTRS = new Set(["href", "src", "action", "formaction"]);
  const streams = new WeakMap();
  const islands = new WeakMap();
  const timers = new WeakMap();
  const customActions = Object.create(null);

  function cloneSafe(val) {
    if (Array.isArray(val)) return val.map(cloneSafe);
    if (val && typeof val === "object") {
      const out = Object.create(null);
      for (const k of Object.keys(val)) {
        if (!BLOCKED_KEYS.has(k)) out[k] = cloneSafe(val[k]);
      }
      return out;
    }
    return val;
  }

  function isSafeAttrUrl(raw) {
    const v = String(raw == null ? "" : raw).trim();
    if (!v || /[\u0000-\u001F\u007F\\]/.test(v)) return false;
    if (v.startsWith("//")) return false;
    if (v.startsWith("/") || v.startsWith("#")) return true;
    const m = /^([a-zA-Z][a-zA-Z0-9+.-]*):/.exec(v);
    if (!m) return !v.includes(":");
    const scheme = m[1].toLowerCase();
    return scheme === "https" || scheme === "http" || scheme === "mailto";
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

  function getPath(state, path) {
    const parts = String(path || "").trim().split(".");
    let cur = state;
    for (const p of parts) {
      if (!p || BLOCKED_KEYS.has(p) || cur == null) return undefined;
      if (p === "length" && (Array.isArray(cur) || typeof cur === "string")) return cur.length;
      if (typeof cur !== "object") return undefined;
      cur = cur[p];
    }
    return cur;
  }

  function setPath(state, path, value) {
    const parts = String(path || "").trim().split(".");
    if (parts.length === 0) return;
    let cur = state;
    for (let i = 0; i < parts.length - 1; i++) {
      const p = parts[i];
      if (!p || BLOCKED_KEYS.has(p)) return;
      if (!cur[p] || typeof cur[p] !== "object") cur[p] = Object.create(null);
      cur = cur[p];
    }
    const leaf = parts[parts.length - 1];
    if (!leaf || BLOCKED_KEYS.has(leaf)) return;
    cur[leaf] = value;
  }

  function parseLiteralOrPath(token, state) {
    const t = token.trim();
    if (t === "true") return true;
    if (t === "false") return false;
    if (t === "null") return null;
    if ((t.startsWith("'") && t.endsWith("'")) || (t.startsWith('"') && t.endsWith('"'))) {
      return t.slice(1, -1);
    }
    if (/^-?\d+(?:\.\d+)?$/.test(t)) return Number(t);
    const val = getPath(state, t);
    return val !== undefined ? val : t;
  }

  function evalAtom(expr, state) {
    const s = expr.trim();
    if (!s) return false;
    if (s.startsWith("!")) return !evalAtom(s.slice(1), state);
    const cmp = /^(.+?)\s*(===|!==|==|!=|>=|<=|>|<)\s*(.+)$/.exec(s);
    if (cmp) {
      const left = evalAtom(cmp[1], state);
      const right = parseLiteralOrPath(cmp[3], state);
      switch (cmp[2]) {
        case "===": case "==": return String(left) === String(right) || left === right;
        case "!==": case "!=": return !(String(left) === String(right) || left === right);
        case ">=": return Number(left) >= Number(right);
        case "<=": return Number(left) <= Number(right);
        case ">": return Number(left) > Number(right);
        case "<": return Number(left) < Number(right);
      }
    }
    if (s === "true") return true;
    if (s === "false") return false;
    if (s === "null") return null;
    if ((s.startsWith("'") && s.endsWith("'")) || (s.startsWith('"') && s.endsWith('"'))) return s.slice(1, -1);
    if (/^-?\d+(?:\.\d+)?$/.test(s)) return Number(s);
    return getPath(state, s);
  }

  // Zero-eval expression evaluator supporting ??, ||, &&, !, comparisons, and dotted paths
  function evalExpr(rawExpr, state) {
    const s = String(rawExpr || "").trim();
    if (!s) return undefined;
    if (s.includes("??")) {
      const parts = s.split("??");
      for (const p of parts) {
        const v = evalExpr(p, state);
        if (v !== null && v !== undefined && v !== "") return v;
      }
      return "";
    }
    if (s.includes("||")) {
      const parts = s.split("||");
      let last = false;
      for (const p of parts) {
        last = evalExpr(p, state);
        if (last) return last;
      }
      return last;
    }
    if (s.includes("&&")) {
      const parts = s.split("&&");
      let last = true;
      for (const p of parts) {
        last = evalExpr(p, state);
        if (!last) return last;
      }
      return last;
    }
    return evalAtom(s, state);
  }

  function findIsland(el) {
    const host = el && el.closest ? el.closest("[k-state]") : null;
    return host ? islands.get(host) || initIsland(host) : null;
  }

  function renderIsland(island) {
    const { root, state } = island;
    const walk = (node) => {
      if (!node || node.nodeType !== 1) return;
      if (node !== root && node.hasAttribute("k-state")) return;

      if (node.hasAttribute("k-text")) {
        const val = evalExpr(node.getAttribute("k-text"), state);
        const text = val == null ? "" : String(val);
        if (node.textContent !== text) node.textContent = text;
      }
      if (node.hasAttribute("k-show")) {
        const show = Boolean(evalExpr(node.getAttribute("k-show"), state));
        node.hidden = !show;
        node.style.display = show ? "" : "none";
      }
      if (node.hasAttribute("k-hide")) {
        const hide = Boolean(evalExpr(node.getAttribute("k-hide"), state));
        node.hidden = hide;
        node.style.display = hide ? "none" : "";
      }
      if (node.hasAttribute("k-model")) {
        const path = node.getAttribute("k-model") || "";
        const val = getPath(state, path);
        if (node.type === "checkbox") {
          node.checked = Boolean(val);
        } else if (node.type === "radio") {
          node.checked = String(val) === node.value;
        } else if (document.activeElement !== node) {
          const str = val == null ? "" : String(val);
          if (node.value !== str) node.value = str;
        }
      }
      if (node.hasAttribute("k-filter")) {
        const q = String(evalExpr(node.getAttribute("k-filter"), state) || "").trim().toLowerCase();
        const hay = String(node.getAttribute("data-filter-text") || node.textContent || "").toLowerCase();
        const match = !q || hay.includes(q);
        node.hidden = !match;
        node.style.display = match ? "" : "none";
      }
      for (const attr of Array.from(node.attributes)) {
        if (attr.name.startsWith("k-class:")) {
          const cls = attr.name.slice(8);
          if (cls) node.classList.toggle(cls, Boolean(evalExpr(attr.value, state)));
        } else if (attr.name.startsWith("k-attr:")) {
          const targetAttr = attr.name.slice(7).toLowerCase();
          if (!targetAttr || targetAttr.startsWith("on") || targetAttr === "srcdoc" || targetAttr === "style") continue;
          const v = evalExpr(attr.value, state);
          if (v === false || v === null || v === undefined) {
            node.removeAttribute(targetAttr);
          } else {
            const strVal = v === true ? "true" : String(v);
            if (URL_ATTRS.has(targetAttr) && !isSafeAttrUrl(strVal)) continue;
            node.setAttribute(targetAttr, strVal);
          }
        }
      }
      for (const child of node.children) walk(child);
    };
    walk(root);
  }

  function initIsland(root) {
    if (!root || islands.has(root)) return islands.get(root);
    let initial = Object.create(null);
    try {
      const parsed = JSON.parse(root.getAttribute("k-state") || "{}");
      if (parsed && typeof parsed === "object" && !Array.isArray(parsed)) {
        initial = cloneSafe(parsed);
      }
    } catch (_e) {}
    const island = { root, state: cloneSafe(initial), initial: cloneSafe(initial) };
    islands.set(root, island);
    renderIsland(island);
    return island;
  }

  function runActionList(rawActions, island, el, ev) {
    const statements = String(rawActions || "").split(";").map((s) => s.trim()).filter(Boolean);
    let dirty = false;
    for (const stmt of statements) {
      const m = /^([a-zA-Z_][a-zA-Z0-9_]*)\s*\((.*)\)$/.exec(stmt);
      if (!m) continue;
      const fn = m[1];
      const args = m[2].trim() ? m[2].split(",").map((a) => a.trim()) : [];
      if (fn === "toggle" && args[0]) {
        setPath(island.state, args[0], !Boolean(getPath(island.state, args[0])));
        dirty = true;
      } else if (fn === "set" && args[0] && args.length >= 2) {
        setPath(island.state, args[0], parseLiteralOrPath(args.slice(1).join(","), island.state));
        dirty = true;
      } else if (fn === "inc" && args[0]) {
        const step = args[1] ? Number(parseLiteralOrPath(args[1], island.state)) : 1;
        setPath(island.state, args[0], Number(getPath(island.state, args[0]) || 0) + (Number.isFinite(step) ? step : 1));
        dirty = true;
      } else if (fn === "dec" && args[0]) {
        const step = args[1] ? Number(parseLiteralOrPath(args[1], island.state)) : 1;
        setPath(island.state, args[0], Number(getPath(island.state, args[0]) || 0) - (Number.isFinite(step) ? step : 1));
        dirty = true;
      } else if (fn === "reset") {
        if (args[0]) {
          setPath(island.state, args[0], cloneSafe(getPath(island.initial, args[0])));
        } else {
          island.state = cloneSafe(island.initial);
        }
        dirty = true;
      } else if (fn === "dispatch" && args[0]) {
        const name = String(parseLiteralOrPath(args[0], island.state));
        el.dispatchEvent(new CustomEvent(name, { bubbles: true, detail: { state: cloneSafe(island.state) } }));
      } else if (fn === "call" && args[0]) {
        const name = String(parseLiteralOrPath(args[0], island.state));
        if (typeof customActions[name] === "function") {
          customActions[name](island.state, { el, ev, root: island.root });
          dirty = true;
        }
      }
    }
    if (dirty) renderIsland(island);
  }

  function handleSignalEvent(eventType, ev) {
    let cur = ev.target && ev.target.nodeType === 1 ? ev.target : null;
    while (cur) {
      for (const attr of Array.from(cur.attributes || [])) {
        if (!attr.name.startsWith("k-on:")) continue;
        const parts = attr.name.slice(5).split(".");
        if (parts[0] !== eventType) continue;
        const mods = new Set(parts.slice(1));
        if (mods.has("self") && ev.target !== cur) continue;
        if (mods.has("enter") && ev.key !== "Enter") continue;
        if (mods.has("escape") && ev.key !== "Escape") continue;
        if (mods.has("prevent")) ev.preventDefault();
        if (mods.has("stop")) ev.stopPropagation();
        const island = findIsland(cur);
        if (island) runActionList(attr.value, island, cur, ev);
      }
      cur = cur.parentElement;
    }
  }

  // Keyed DOM Morphing preserving active input focus, cursor selection, and k-ignore subtrees
  function nodeKey(node) {
    if (!node || node.nodeType !== 1) return null;
    return node.getAttribute("k-key") || node.id || null;
  }

  function syncAttributes(fromEl, toEl) {
    const isFormField = fromEl.tagName === "INPUT" || fromEl.tagName === "TEXTAREA" || fromEl.tagName === "SELECT";
    const preserveInput = isFormField && !toEl.hasAttribute("k-sync") && (
      document.activeElement === fromEl ||
      ((fromEl.type === "checkbox" || fromEl.type === "radio")
        ? fromEl.checked !== fromEl.defaultChecked
        : fromEl.value !== fromEl.defaultValue)
    );

    for (const attr of Array.from(fromEl.attributes)) {
      if (preserveInput && (attr.name === "value" || attr.name === "checked")) continue;
      if (!toEl.hasAttribute(attr.name)) fromEl.removeAttribute(attr.name);
    }
    for (const attr of Array.from(toEl.attributes)) {
      if (preserveInput && (attr.name === "value" || attr.name === "checked")) continue;
      if (fromEl.getAttribute(attr.name) !== attr.value) {
        fromEl.setAttribute(attr.name, attr.value);
      }
    }
    if (isFormField && !preserveInput) {
      if (fromEl.type === "checkbox" || fromEl.type === "radio") {
        fromEl.checked = toEl.hasAttribute("checked");
      } else if ("value" in toEl && fromEl.value !== toEl.value) {
        fromEl.value = toEl.value;
      }
    }
  }

  function morphNode(fromNode, toNode) {
    if (fromNode.nodeType === 1 && fromNode.hasAttribute("k-ignore")) return;
    if (fromNode.nodeType !== toNode.nodeType || fromNode.nodeName !== toNode.nodeName) {
      fromNode.replaceWith(toNode.cloneNode(true));
      return;
    }
    if (fromNode.nodeType === 3 || fromNode.nodeType === 8) {
      if (fromNode.nodeValue !== toNode.nodeValue) fromNode.nodeValue = toNode.nodeValue;
      return;
    }
    if (fromNode.nodeType === 1) {
      syncAttributes(fromNode, toNode);
      morphChildren(fromNode, toNode);
    }
  }

  function morphChildren(fromParent, toParent) {
    const keyedOld = new Map();
    for (const child of Array.from(fromParent.childNodes)) {
      const k = nodeKey(child);
      if (k) keyedOld.set(k, child);
    }

    const toNodes = Array.from(toParent.childNodes);
    let cursor = fromParent.firstChild;

    for (const toChild of toNodes) {
      // Drop dead whitespace-only text nodes ahead of a matched element so we never relocate/blur a focused node
      while (cursor && cursor.nodeType === 3 && toChild.nodeType === 1 && cursor.nodeValue.trim() === "") {
        const next = cursor.nextSibling;
        fromParent.removeChild(cursor);
        cursor = next;
      }

      const k = nodeKey(toChild);
      let match = null;
      if (k && keyedOld.has(k)) {
        match = keyedOld.get(k);
        keyedOld.delete(k);
        if (match !== cursor) fromParent.insertBefore(match, cursor);
      } else if (cursor && !nodeKey(cursor) && cursor.nodeType === toChild.nodeType && cursor.nodeName === toChild.nodeName) {
        match = cursor;
      }

      if (match) {
        const next = match.nextSibling;
        morphNode(match, toChild);
        cursor = next;
      } else {
        const clone = toChild.cloneNode(true);
        fromParent.insertBefore(clone, cursor);
      }
    }

    while (cursor) {
      const next = cursor.nextSibling;
      fromParent.removeChild(cursor);
      cursor = next;
    }
  }

  function getCsrf(el) {
    const form = el && el.closest ? el.closest("form") : null;
    const input = form ? form.querySelector('input[name="_csrf"]') : document.querySelector('input[name="_csrf"]');
    if (input && input.value) return input.value;
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? (meta.getAttribute("content") || "") : "";
  }

  function hydrate(root) {
    const scope = root || document;
    if (scope.nodeType === 1 && scope.hasAttribute("k-state")) initIsland(scope);
    scope.querySelectorAll("[k-state]").forEach(initIsland);
    connectStreams(scope);
  }

  function performSwap(target, html, mode) {
    if (!target || mode === "none") return;
    if (mode === "delete") {
      target.remove();
      return;
    }
    if (mode === "morph" || mode === "morphInner") {
      const tpl = document.createElement("template");
      tpl.innerHTML = html;
      if (mode === "morph" && tpl.content.childElementCount === 1 && target.parentNode) {
        morphNode(target, tpl.content.firstElementChild);
      } else {
        morphChildren(target, tpl.content);
      }
      hydrate(target.parentNode || document);
      return;
    }
    if (mode === "outerHTML") {
      const parent = target.parentNode;
      target.outerHTML = html;
      hydrate(parent || document);
    } else if (mode === "beforeend" || mode === "afterbegin") {
      target.insertAdjacentHTML(mode, html);
      hydrate(target);
    } else {
      target.innerHTML = html;
      hydrate(target);
    }
  }

  function applySwap(target, html, mode, useTransition) {
    if (useTransition && typeof document.startViewTransition === "function") {
      document.startViewTransition(() => performSwap(target, html, mode));
    } else {
      performSwap(target, html, mode);
    }
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

  async function dispatchLive(el, form) {
    const live = findLiveAttr(el);
    if (!live || !live.url) return;

    const targetUrl = isSameOriginUrl(live.url);
    if (!targetUrl) return;

    const confirmMsg = el.getAttribute("kgn-confirm");
    if (confirmMsg && !window.confirm(confirmMsg)) return;

    const targetSel = el.getAttribute("kgn-target");
    const target = targetSel ? document.querySelector(targetSel) : el;
    const defaultSwap = el.getAttribute("kgn-swap") || "innerHTML";
    const useTransition = el.getAttribute("kgn-transition") === "true";

    const headers = { "X-Kongono-Live": "true", "Accept": "text/html" };
    const csrf = getCsrf(form || el);
    if (csrf) headers["X-CSRF-Token"] = csrf;

    const init = { method: live.method, headers, credentials: "same-origin" };
    let fetchUrl = targetUrl.href;
    if (live.method === "GET" && (el.tagName === "INPUT" || el.tagName === "SELECT" || el.tagName === "TEXTAREA") && el.name) {
      const u = new URL(targetUrl.href);
      u.searchParams.set(el.name, el.value || "");
      fetchUrl = u.href;
    } else if (form && live.method !== "GET") {
      init.body = new FormData(form);
    }

    // Optimistic UI with automatic rollback on failure
    const optText = el.getAttribute("kgn-optimistic-text");
    const optClass = el.getAttribute("kgn-optimistic-class");
    const prevText = optText !== null ? el.textContent : null;
    const hadOptClass = optClass ? el.classList.contains(optClass) : false;
    if (optText !== null) el.textContent = optText;
    if (optClass) el.classList.toggle(optClass, true);
    const shouldDisable = el.hasAttribute("kgn-disable") && "disabled" in el;
    if (shouldDisable) el.disabled = true;

    el.setAttribute("aria-busy", "true");
    let succeeded = false;
    try {
      const res = await fetch(fetchUrl, init);
      const finalUrl = isSameOriginUrl(res.url || fetchUrl);
      if (!finalUrl) return;
      succeeded = res.ok;

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
        try { window.history.pushState({}, "", pushUrl); } catch (_e) {}
      }
      const swap = res.headers.get("X-Kongono-Swap") || defaultSwap;
      const text = await res.text();
      const ct = (res.headers.get("Content-Type") || "").toLowerCase();
      if (res.ok || text.length > 0) {
        if (ct.startsWith("text/html")) {
          applySwap(target, text, swap, useTransition);
        } else if (target && swap !== "none" && swap !== "delete") {
          target.textContent = text;
        }
      }
      el.dispatchEvent(new CustomEvent("kongono:swapped", { bubbles: true, detail: { status: res.status, target } }));
    } finally {
      el.removeAttribute("aria-busy");
      if (shouldDisable) el.disabled = false;
      if (!succeeded) {
        if (prevText !== null) el.textContent = prevText;
        if (optClass) el.classList.toggle(optClass, hadOptClass);
      }
    }
  }

  function scheduleTriggered(el, triggerType) {
    const spec = (el.getAttribute("kgn-trigger") || "").trim();
    if (!spec) return;
    const parts = spec.split(/\s+/);
    if (parts[0] !== triggerType) return;
    let delay = 0;
    for (const p of parts.slice(1)) {
      const m = /^delay:(\d+)ms$/.exec(p);
      if (m) delay = Math.min(5000, Number(m[1]));
    }
    if (timers.has(el)) clearTimeout(timers.get(el));
    if (delay > 0) {
      timers.set(el, setTimeout(() => dispatchLive(el, el.closest("form")), delay));
    } else {
      dispatchLive(el, el.closest("form"));
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
      const swap = node.getAttribute("kgn-swap") || "morphInner";
      const es = new EventSource(streamUrl.href);
      streams.set(node, es);
      const handler = (ev) => {
        if (!document.contains(node)) {
          es.close();
          streams.delete(node);
          return;
        }
        applySwap(node, ev.data || "", swap, false);
      };
      if (eventName === "message") {
        es.onmessage = handler;
      } else {
        es.addEventListener(eventName, handler);
      }
    });
  }

  document.addEventListener("click", (ev) => {
    handleSignalEvent("click", ev);
    const el = ev.target && ev.target.closest ? ev.target.closest("[kgn-get],[kgn-post],[kgn-put],[kgn-patch],[kgn-delete]") : null;
    if (!el || el.tagName === "FORM" || el.hasAttribute("kgn-trigger")) return;
    ev.preventDefault();
    dispatchLive(el, null);
  });

  document.addEventListener("submit", (ev) => {
    handleSignalEvent("submit", ev);
    const form = ev.target;
    if (!form || form.tagName !== "FORM" || !findLiveAttr(form)) return;
    ev.preventDefault();
    dispatchLive(form, form);
  });

  document.addEventListener("input", (ev) => {
    const el = ev.target;
    if (el && el.nodeType === 1) {
      if (el.hasAttribute("k-model")) {
        const island = findIsland(el);
        if (island) {
          const val = el.type === "checkbox" ? el.checked : (el.type === "number" ? Number(el.value) : el.value);
          setPath(island.state, el.getAttribute("k-model"), val);
          renderIsland(island);
        }
      }
      if (el.hasAttribute("kgn-trigger")) scheduleTriggered(el, "input");
    }
    handleSignalEvent("input", ev);
  });

  document.addEventListener("change", (ev) => {
    const el = ev.target;
    if (el && el.nodeType === 1) {
      if (el.hasAttribute("k-model")) {
        const island = findIsland(el);
        if (island) {
          const val = el.type === "checkbox" ? el.checked : el.value;
          setPath(island.state, el.getAttribute("k-model"), val);
          renderIsland(island);
        }
      }
      if (el.hasAttribute("kgn-trigger")) scheduleTriggered(el, "change");
    }
    handleSignalEvent("change", ev);
  });

  document.addEventListener("keydown", (ev) => handleSignalEvent("keydown", ev));
  document.addEventListener("keyup", (ev) => {
    const el = ev.target;
    if (el && el.nodeType === 1 && el.hasAttribute("kgn-trigger")) scheduleTriggered(el, "keyup");
    handleSignalEvent("keyup", ev);
  });

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", () => hydrate(document));
  } else {
    hydrate(document);
  }

  window.KongonoLive = {
    version: "2.0.0",
    connectStreams,
    hydrate,
    morph: (target, html, outer = false) => performSwap(target, html, outer ? "morph" : "morphInner"),
    action: (name, fn) => { if (name && !BLOCKED_KEYS.has(name) && typeof fn === "function") customActions[name] = fn; },
    getState: (el) => { const i = findIsland(el); return i ? cloneSafe(i.state) : null; },
    setState: (el, key, value) => { const i = findIsland(el); if (i) { setPath(i.state, key, value); renderIsland(i); } }
  };
})();
JS;
    }
}
