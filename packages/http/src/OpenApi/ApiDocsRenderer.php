<?php

declare(strict_types=1);

namespace Kongono\Http\OpenApi;

use Kongono\Http\App;

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
        $safeSpecUrl = htmlspecialchars($specUrl, ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{$title} — Kongono API Docs</title>
  <style>
    :root { font-family: ui-sans-serif, system-ui, -apple-system, sans-serif; color: #0f172a; background: #f8fafc; }
    body { max-width: 980px; margin: 0 auto; padding: 2rem 1.25rem 4rem; }
    header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 1rem; margin-bottom: 1.5rem; gap: 1rem; flex-wrap: wrap; }
    h1 { margin: 0; font-size: 1.5rem; }
    .meta { color: #64748b; font-size: 0.85rem; }
    .auth-bar { display: flex; gap: 0.5rem; align-items: center; }
    .auth-bar input { padding: 0.45rem 0.7rem; border: 1px solid #cbd5e1; border-radius: 6px; width: 260px; font-family: ui-monospace, monospace; font-size: 0.82rem; }
    .op { background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; margin-bottom: 0.85rem; overflow: hidden; }
    .op-head { display: flex; align-items: center; gap: 0.75rem; padding: 0.8rem 1rem; cursor: pointer; }
    .method { font-size: 0.75rem; font-weight: 700; padding: 0.22rem 0.55rem; border-radius: 6px; text-transform: uppercase; min-width: 58px; text-align: center; color: #fff; }
    .m-get { background: #0284c7; } .m-post { background: #16a34a; } .m-put { background: #d97706; } .m-patch { background: #7c3aed; } .m-delete { background: #dc2626; }
    .path { font-family: ui-monospace, monospace; font-weight: 600; font-size: 0.92rem; }
    .op-id { color: #64748b; font-size: 0.8rem; margin-left: auto; }
    .op-body { border-top: 1px solid #f1f5f9; padding: 1rem; display: none; background: #fcfcfd; }
    .op.open .op-body { display: block; }
    textarea { width: 100%; box-sizing: border-box; font-family: ui-monospace, monospace; font-size: 0.82rem; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; }
    button.try-btn { margin-top: 0.6rem; background: #0f172a; color: #fff; border: none; padding: 0.5rem 1rem; border-radius: 6px; font-weight: 600; cursor: pointer; }
    pre.out { margin-top: 0.75rem; background: #0f172a; color: #e2e8f0; padding: 0.8rem; border-radius: 8px; overflow-x: auto; font-size: 0.8rem; display: none; }
  </style>
</head>
<body>
  <header>
    <div>
      <h1>{$title}</h1>
      <div class="meta">OpenAPI 3.1.0 &middot; <a href="{$safeSpecUrl}">{$safeSpecUrl}</a> &middot; Powered by Kongono Framework</div>
    </div>
    <div class="auth-bar">
      <input id="bearerToken" type="password" placeholder="Optional Bearer token">
    </div>
  </header>
  <div id="endpoints"></div>
  <script>
    const spec = {$specJson};
    const root = document.getElementById('endpoints');
    const schemas = (spec.components && spec.components.schemas) || {};
    function sampleFor(schemaRef) {
      if (!schemaRef || !schemaRef.\$ref) return {};
      const name = schemaRef.\$ref.split('/').pop();
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
        const bodyHtml = reqSchema ? '<label class="meta">JSON Body</label><textarea rows="4">' + JSON.stringify(sampleFor(reqSchema), null, 2) + '</textarea>' : '';
        card.innerHTML =
          '<div class="op-head"><span class="method m-' + method + '">' + method + '</span><span class="path">' + path + '</span><span class="op-id">' + (op.operationId || '') + '</span></div>' +
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
HTML;
    }
}
