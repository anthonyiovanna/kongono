<?php

declare(strict_types=1);

namespace Kongono\Http\OpenApi;

use Kongono\Http\App;
use Kongono\Http\Brand\KongonoBrand;

/**
 * Self-contained, zero-CDN HTML5 Interactive API Explorer (`/docs`)
 * powered directly by Kongono's live OpenAPI 3.1.0 generator.
 */
final class ApiDocsRenderer
{
    public function render(App $app, string $specUrl = '/openapi.json', string $version = '1.0.0'): string
    {
        $spec = (new OpenApiGenerator())->generate($app, $version);
        $info = is_array($spec['info'] ?? null) ? $spec['info'] : [];
        $title = htmlspecialchars(is_string($info['title'] ?? null) ? $info['title'] : 'Kongono API', ENT_QUOTES, 'UTF-8');
        $specJson = json_encode($spec, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
        $trimmedSpec = trim($specUrl);
        $isSafeSpecUrl = '' !== $trimmedSpec
            && 1 !== preg_match('/[\x00-\x1f\x7f]/', $trimmedSpec)
            && (
                (str_starts_with($trimmedSpec, '/') && !str_starts_with($trimmedSpec, '//') && !str_starts_with($trimmedSpec, '/\\'))
                || str_starts_with(strtolower($trimmedSpec), 'https://')
                || str_starts_with(strtolower($trimmedSpec), 'http://')
            );
        if (!$isSafeSpecUrl) {
            throw new \InvalidArgumentException('Unsafe OpenAPI specUrl (must be a safe relative path or http(s):// URL).');
        }
        $safeSpecUrl = htmlspecialchars($trimmedSpec, ENT_QUOTES, 'UTF-8');
        $brandSvg = KongonoBrand::markSvg(24, 'kgn-api-gm');

        $head = <<<HTML
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{$title} — Kongono API Docs</title>
  <style>
    :root {
      color-scheme: dark light;
      --bg: #0c0c0d;
      --surface: #131315;
      --surface-2: #19191c;
      --border: rgba(255, 255, 255, 0.08);
      --border-strong: rgba(255, 255, 255, 0.16);
      --text: #f4f4f5;
      --text-2: #a1a1aa;
      --muted: #63636b;
      --ok: #4ade80;
      --err: #e5534b;
      font-family: ui-sans-serif, "Inter", system-ui, -apple-system, sans-serif;
      color: var(--text);
      background: var(--bg);
    }
    @media (prefers-color-scheme: light) {
      :root {
        --bg: #fcfcfc;
        --surface: #f5f5f6;
        --surface-2: #ebebee;
        --border: rgba(0, 0, 0, 0.08);
        --border-strong: rgba(0, 0, 0, 0.16);
        --text: #09090b;
        --text-2: #52525b;
        --muted: #8a8a94;
        --ok: #15803d;
        --err: #d12e26;
      }
    }
    * { box-sizing: border-box; }
    body { max-width: 980px; margin: 0 auto; padding: 2rem 1.5rem 4rem; background: var(--bg); color: var(--text); -webkit-font-smoothing: antialiased; }
    a { color: var(--text); text-decoration: underline; text-underline-offset: 2px; }
    header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 1.15rem; margin-bottom: 1.65rem; gap: 1rem; flex-wrap: wrap; }
    h1 { margin: 0; font-size: 1.45rem; font-weight: 560; letter-spacing: -0.025em; display: inline-flex; align-items: center; gap: 0.55rem; }
    .meta { color: var(--muted); font-size: 0.82rem; font-family: ui-monospace, Menlo, monospace; margin-top: 0.25rem; }
    .auth-bar { display: flex; gap: 0.5rem; align-items: center; }
    .auth-bar input { padding: 0.44rem 0.72rem; border: 1px solid var(--border-strong); background: var(--surface); color: var(--text); border-radius: 6px; width: 260px; font-family: ui-monospace, monospace; font-size: 0.8rem; }
    .op { background: var(--surface); border: 1px solid var(--border); border-radius: 9px; margin-bottom: 0.75rem; overflow: hidden; }
    .op-head { display: flex; align-items: center; gap: 0.75rem; padding: 0.78rem 1rem; cursor: pointer; }
    .op-head:hover { background: var(--surface-2); }
    .method { font-family: ui-monospace, Menlo, monospace; font-size: 0.72rem; font-weight: 600; padding: 0.2rem 0.52rem; border-radius: 5px; text-transform: uppercase; min-width: 58px; text-align: center; border: 1px solid var(--border-strong); background: var(--bg); color: var(--text); }
    .m-post { border-color: rgba(74, 222, 128, 0.35); color: var(--ok); }
    .m-delete { border-color: rgba(229, 83, 75, 0.35); color: var(--err); }
    .path { font-family: ui-monospace, "JetBrains Mono", monospace; font-weight: 500; font-size: 0.88rem; }
    .op-id { color: var(--muted); font-family: ui-monospace, monospace; font-size: 0.76rem; margin-left: auto; }
    .op-body { border-top: 1px solid var(--border); padding: 1rem; display: none; background: var(--bg); }
    .op.open .op-body { display: block; }
    textarea { width: 100%; font-family: ui-monospace, monospace; font-size: 0.8rem; padding: 0.65rem; border: 1px solid var(--border-strong); background: var(--surface); color: var(--text); border-radius: 6px; }
    button.try-btn { margin-top: 0.65rem; background: var(--text); color: var(--bg); border: 1px solid var(--text); padding: 0.48rem 0.95rem; border-radius: 6px; font-weight: 500; font-size: 0.82rem; cursor: pointer; }
    pre.out { margin-top: 0.75rem; background: var(--surface); border: 1px solid var(--border); color: var(--text-2); padding: 0.85rem; border-radius: 7px; overflow-x: auto; font-size: 0.79rem; display: none; }
  </style>
</head>
<body>
  <header>
    <div>
      <h1>{$brandSvg}<span>{$title}</span></h1>
      <div class="meta">OpenAPI 3.1.0 &middot; <a href="{$safeSpecUrl}">{$safeSpecUrl}</a> &middot; Powered by Kongono Framework</div>
    </div>
    <div class="auth-bar">
      <input id="bearerToken" type="password" placeholder="Optional Bearer token">
    </div>
  </header>
  <div id="endpoints"></div>
  <script>
    const spec = {$specJson};

HTML;

        $script = <<<'JS'
    const root = document.getElementById('endpoints');
    const schemas = (spec.components && spec.components.schemas) || {};
    function esc(s) {
      return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    function sampleFor(schemaRef) {
      if (!schemaRef || !schemaRef.$ref) return {};
      const name = schemaRef.$ref.split('/').pop();
      const s = schemas[name];
      if (!s || !s.properties) return {};
      const out = {};
      for (const [k, p] of Object.entries(s.properties)) {
        out[k] = p.type === 'integer' ? 1 : p.type === 'boolean' ? true : p.format === 'email' ? 'user@example.com' : '';
      }
      return out;
    }
    for (const [path, methods] of Object.entries(spec.paths || {})) {
      for (const [method, op] of Object.entries(methods)) {
        const card = document.createElement('section');
        card.className = 'op';
        const reqSchema = op.requestBody && op.requestBody.content && op.requestBody.content['application/json'] && op.requestBody.content['application/json'].schema;
        const bodyHtml = reqSchema ? '<label class="meta">JSON Body</label><textarea rows="4">' + esc(JSON.stringify(sampleFor(reqSchema), null, 2)) + '</textarea>' : '';
        const safeMethod = esc(method);
        card.innerHTML =
          '<div class="op-head"><span class="method m-' + safeMethod + '">' + safeMethod + '</span><span class="path">' + esc(path) + '</span><span class="op-id">' + esc(op.operationId || '') + '</span></div>' +
          '<div class="op-body">' + bodyHtml + '<button class="try-btn">Send Request</button><pre class="out"></pre></div>';
        card.querySelector('.op-head').addEventListener('click', () => card.classList.toggle('open'));
        card.querySelector('.try-btn').addEventListener('click', async () => {
          const out = card.querySelector('.out');
          const ta = card.querySelector('textarea');
          const headers = { 'Accept': 'application/json' };
          const tok = document.getElementById('bearerToken').value.trim();
          if (tok) headers['Authorization'] = 'Bearer ' + tok;
          const opts = { method: method.toUpperCase(), headers };
          if (ta) { headers['Content-Type'] = 'application/json'; opts.body = ta.value; }
          const res = await fetch(path.replace(/\{[^}]+\}/g, '1'), opts);
          const text = await res.text();
          out.style.display = 'block';
          out.textContent = 'HTTP ' + res.status + '\n' + text;
        });
        root.appendChild(card);
      }
    }
  </script>
</body>
</html>
JS;

        return $head . $script;
    }
}
