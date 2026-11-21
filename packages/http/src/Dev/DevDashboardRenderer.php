<?php

declare(strict_types=1);

namespace Kongono\Http\Dev;

use Kongono\Http\App;

/**
 * Self-contained, zero-CDN HTML5 Kongono Dev Studio (`/_kongono`).
 *
 * Combines live request/SQL tracing, ApplicationGraph inspection, route explorer,
 * health/metrics monitor, and an interactive AI/MCP workbench.
 */
final class DevDashboardRenderer
{
    public function render(App $app, DevInspector $inspector, string $basePath = '/_kongono'): string
    {
        $graph = $app->graph()->toArray();
        $appName = htmlspecialchars(is_string($graph['app'] ?? null) ? $graph['app'] : 'Kongono App', ENT_QUOTES, 'UTF-8');
        $graphJson = json_encode($graph, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
        $tracesJson = json_encode($inspector->traces(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
        $safeBase = htmlspecialchars(rtrim($basePath, '/'), ENT_QUOTES, 'UTF-8');
        $phpVersion = htmlspecialchars(PHP_VERSION, ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{$appName} — Kongono Dev Studio</title>
  <style>
    :root { font-family: ui-sans-serif, system-ui, -apple-system, sans-serif; color: #e2e8f0; background: #0b1120; }
    * { box-sizing: border-box; }
    body { margin: 0; max-width: 1120px; margin-inline: auto; padding: 1.5rem 1.25rem 4rem; }
    header { display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap; border-bottom: 1px solid #1e293b; padding-bottom: 1rem; margin-bottom: 1.25rem; }
    h1 { margin: 0; font-size: 1.4rem; color: #f8fafc; }
    .sub { color: #94a3b8; font-size: 0.85rem; margin-top: 0.2rem; }
    .links { display: flex; gap: 0.6rem; flex-wrap: wrap; }
    .pill { background: #1e293b; color: #e2e8f0; text-decoration: none; padding: 0.4rem 0.75rem; border-radius: 999px; font-size: 0.8rem; border: 1px solid #334155; }
    .pill:hover { border-color: #38bdf8; color: #38bdf8; }
    nav.tabs { display: flex; gap: 0.5rem; margin-bottom: 1.25rem; flex-wrap: wrap; }
    nav.tabs button { background: #111827; color: #94a3b8; border: 1px solid #1e293b; padding: 0.55rem 0.95rem; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 0.85rem; }
    nav.tabs button.active { background: #0ea5e9; color: #082f49; border-color: #38bdf8; }
    .panel { display: none; }
    .panel.active { display: block; }
    .card { background: #111827; border: 1px solid #1e293b; border-radius: 10px; padding: 1rem 1.15rem; margin-bottom: 1rem; }
    table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
    th, td { text-align: left; padding: 0.55rem 0.6rem; border-bottom: 1px solid #1e293b; }
    th { color: #94a3b8; font-weight: 600; }
    code, pre { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
    pre { background: #020617; border: 1px solid #1e293b; border-radius: 8px; padding: 0.85rem; overflow-x: auto; font-size: 0.8rem; color: #cbd5e1; }
    .badge { display: inline-block; padding: 0.15rem 0.45rem; border-radius: 5px; font-size: 0.72rem; font-weight: 700; }
    .b-ok { background: #064e3b; color: #6ee7b7; }
    .b-warn { background: #78350f; color: #fcd34d; }
    .b-err { background: #7f1d1d; color: #fca5a5; }
    .b-method { background: #1e3a8a; color: #93c5fd; }
    .toolbar { display: flex; gap: 0.5rem; align-items: center; margin-bottom: 0.85rem; flex-wrap: wrap; }
    .btn { background: #1e293b; color: #f8fafc; border: 1px solid #334155; padding: 0.45rem 0.85rem; border-radius: 6px; cursor: pointer; font-size: 0.82rem; font-weight: 600; }
    .btn:hover { background: #334155; }
    .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 0.75rem; margin-bottom: 1rem; }
    .stat { background: #111827; border: 1px solid #1e293b; border-radius: 10px; padding: 0.85rem 1rem; }
    .stat .k { color: #94a3b8; font-size: 0.76rem; text-transform: uppercase; letter-spacing: 0.04em; }
    .stat .v { font-size: 1.45rem; font-weight: 700; color: #f8fafc; margin-top: 0.25rem; }
  </style>
</head>
<body>
  <header>
    <div>
      <h1>Kongono Dev Studio &middot; {$appName}</h1>
      <div class="sub">PHP {$phpVersion} &middot; Debug Mode Only (auto-disabled in production)</div>
    </div>
    <div class="links">
      <a class="pill" href="/">App Home</a>
      <a class="pill" href="/docs">API Explorer (/docs)</a>
      <a class="pill" href="/openapi.json">OpenAPI 3.1</a>
      <a class="pill" href="/health">Health</a>
      <a class="pill" href="/metrics">Metrics</a>
    </div>
  </header>

  <div class="grid" id="stats"></div>

  <nav class="tabs">
    <button class="active" data-tab="requests">Live Requests</button>
    <button data-tab="routes">Routes</button>
    <button data-tab="graph">Services &amp; Graph</button>
    <button data-tab="ai">AI Companions &amp; MCP</button>
  </nav>

  <section id="tab-requests" class="panel active">
    <div class="card">
      <div class="toolbar">
        <button class="btn" id="refreshBtn">Refresh Traces</button>
        <button class="btn" id="clearBtn">Clear Traces</button>
      </div>
      <table>
        <thead><tr><th>#</th><th>Method</th><th>Path</th><th>Status</th><th>Duration</th><th>Queries</th></tr></thead>
        <tbody id="traceRows"></tbody>
      </table>
    </div>
  </section>

  <section id="tab-routes" class="panel">
    <div class="card">
      <table>
        <thead><tr><th>Methods</th><th>Path</th><th>Name</th><th>Handler</th></tr></thead>
        <tbody id="routeRows"></tbody>
      </table>
    </div>
  </section>

  <section id="tab-graph" class="panel">
    <div class="card">
      <h3>Middleware Pipeline</h3>
      <pre id="mwList"></pre>
      <h3>Container Services</h3>
      <pre id="svcList"></pre>
      <h3>Compiled Views</h3>
      <pre id="viewList"></pre>
    </div>
  </section>

  <section id="tab-ai" class="panel">
    <div class="card">
      <h3>Built-in AI Companions (Oshie &middot; Chie &middot; Hantei)</h3>
      <pre>php bin/kongo oshie --interactive
php bin/kongo chie "Add a /api/stats endpoint"
php bin/kongo hantei check
php bin/kongo mcp</pre>
      <h3>Live MCP Workbench (POST /mcp)</h3>
      <div class="toolbar">
        <button class="btn" data-mcp="kongono_routes">Call kongono_routes</button>
        <button class="btn" data-mcp="kongono_oshie_lesson">Call kongono_oshie_lesson</button>
        <button class="btn" data-mcp="kongono_graph">Call kongono_graph</button>
      </div>
      <pre id="mcpOut">Click a button above to invoke your live Kongono MCP server.</pre>
    </div>
  </section>

  <script>
    const BASE = "{$safeBase}";
    const GRAPH = {$graphJson};
    let traces = {$tracesJson};

    function esc(s) {
      return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    document.getElementById('stats').innerHTML = [
      ['Routes', (GRAPH.routes || []).length],
      ['Services', (GRAPH.services || []).length],
      ['Middleware', (GRAPH.middleware || []).length],
      ['Views', (GRAPH.views || []).length],
    ].map(([k, v]) => '<div class="stat"><div class="k">' + k + '</div><div class="v">' + v + '</div></div>').join('');

    function renderTraces() {
      const tb = document.getElementById('traceRows');
      if (!traces.length) {
        tb.innerHTML = '<tr><td colspan="6" style="color:#64748b">No requests captured yet. Visit / or /api/notes then click Refresh.</td></tr>';
        return;
      }
      tb.innerHTML = traces.map(t => {
        const cls = t.status >= 500 ? 'b-err' : (t.status >= 400 ? 'b-warn' : 'b-ok');
        return '<tr><td>' + t.id + '</td><td><span class="badge b-method">' + esc(t.method) + '</span></td><td><code>' + esc(t.path) + '</code></td><td><span class="badge ' + cls + '">' + t.status + '</span></td><td>' + t.duration_ms + ' ms</td><td>' + (t.queries || []).length + '</td></tr>';
      }).join('');
    }
    renderTraces();

    document.getElementById('routeRows').innerHTML = (GRAPH.routes || []).map(r =>
      '<tr><td><span class="badge b-method">' + esc((r.methods || []).join('|')) + '</span></td><td><code>' + esc(r.path) + '</code></td><td>' + esc(r.name || '-') + '</td><td><code>' + esc(r.handler) + '</code></td></tr>'
    ).join('');

    document.getElementById('mwList').textContent = (GRAPH.middleware || []).join('\n') || '(none)';
    document.getElementById('svcList').textContent = (GRAPH.services || []).map(s => '[' + s.lifetime + '] ' + s.id).join('\n') || '(none)';
    document.getElementById('viewList').textContent = (GRAPH.views || []).join('\n') || '(none)';

    document.querySelectorAll('nav.tabs button').forEach(btn => {
      btn.addEventListener('click', () => {
        document.querySelectorAll('nav.tabs button').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
      });
    });

    document.getElementById('refreshBtn').addEventListener('click', async () => {
      const res = await fetch(BASE + '/api/traces');
      traces = await res.json();
      renderTraces();
    });
    document.getElementById('clearBtn').addEventListener('click', async () => {
      await fetch(BASE + '/api/clear', { method: 'POST' });
      traces = [];
      renderTraces();
    });
    document.querySelectorAll('[data-mcp]').forEach(btn => {
      btn.addEventListener('click', async () => {
        const out = document.getElementById('mcpOut');
        out.textContent = 'Calling ' + btn.dataset.mcp + '...';
        const res = await fetch('/mcp', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ jsonrpc: '2.0', id: 1, method: 'tools/call', params: { name: btn.dataset.mcp, arguments: {} } }),
        });
        const json = await res.json();
        out.textContent = (json.result && json.result.content && json.result.content[0] && json.result.content[0].text) || JSON.stringify(json, null, 2);
      });
    });
  </script>
</body>
</html>
HTML;
    }
}
