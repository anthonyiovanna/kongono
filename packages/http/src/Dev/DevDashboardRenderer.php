<?php

declare(strict_types=1);

namespace Kongono\Http\Dev;

use Kongono\Http\App;
use Kongono\Http\Brand\KongonoBrand;

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
        $graphJson = json_encode($graph, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE);
        $tracesJson = json_encode($inspector->traces(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE);
        $safeBase = htmlspecialchars(rtrim($basePath, '/'), ENT_QUOTES, 'UTF-8');
        $baseJson = json_encode(rtrim($basePath, '/'), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE);
        $phpVersion = htmlspecialchars(PHP_VERSION, ENT_QUOTES, 'UTF-8');
        $brandSvg = KongonoBrand::markSvg(24, 'kgn-dev-gm');
        $oshieSvg = KongonoBrand::oshieSvg(22, 'kgn-dev-oshie');
        $chieSvg = KongonoBrand::chieSvg(22, 'kgn-dev-chie');
        $hanteiSvg = KongonoBrand::hanteiSvg(22, 'kgn-dev-hantei');

        $head = <<<HTML
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{$appName} — Kongono Dev Studio</title>
  <link rel="icon" type="image/svg+xml" href="{$safeBase}/assets/favicon.svg">
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
      --warn: #fbbf24;
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
        --warn: #b45309;
        --err: #d12e26;
      }
    }
    * { box-sizing: border-box; }
    body { margin: 0; max-width: 1140px; margin-inline: auto; padding: 1.75rem 1.5rem 4rem; background: var(--bg); color: var(--text); -webkit-font-smoothing: antialiased; }
    header { display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap; border-bottom: 1px solid var(--border); padding-bottom: 1.15rem; margin-bottom: 1.4rem; }
    h1 { margin: 0; font-size: 1.35rem; font-weight: 560; letter-spacing: -0.025em; color: var(--text); display: inline-flex; align-items: center; gap: 0.55rem; }
    .sub { color: var(--muted); font-size: 0.82rem; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; margin-top: 0.25rem; }
    .links { display: flex; gap: 0.45rem; flex-wrap: wrap; }
    .pill { background: transparent; color: var(--text-2); text-decoration: none; padding: 0.36rem 0.72rem; border-radius: 6px; font-size: 0.78rem; font-weight: 500; border: 1px solid var(--border); transition: all 120ms ease; }
    .pill:hover { border-color: var(--border-strong); background: var(--surface); color: var(--text); }
    nav.tabs { display: flex; gap: 0.35rem; margin-bottom: 1.35rem; flex-wrap: wrap; border-bottom: 1px solid var(--border); padding-bottom: 0.75rem; }
    nav.tabs button { background: transparent; color: var(--text-2); border: 1px solid transparent; padding: 0.44rem 0.82rem; border-radius: 6px; cursor: pointer; font-weight: 500; font-size: 0.82rem; font-family: inherit; transition: all 120ms ease; }
    nav.tabs button:hover { color: var(--text); background: var(--surface); }
    nav.tabs button.active { background: var(--text); color: var(--bg); border-color: var(--text); }
    .panel { display: none; }
    .panel.active { display: block; }
    .card { background: var(--surface); border: 1px solid var(--border); border-radius: 9px; padding: 1.1rem 1.2rem; margin-bottom: 1rem; }
    .ai-head { display: inline-flex; align-items: center; gap: 0.55rem; margin: 0 0 0.75rem; font-size: 1rem; font-weight: 560; }
    .ai-input { flex: 1; min-width: 240px; padding: 0.45rem 0.7rem; border-radius: 6px; border: 1px solid var(--border-strong); background: var(--bg); color: var(--text); font: inherit; font-size: 0.84rem; }
    table { width: 100%; border-collapse: collapse; font-size: 0.83rem; }
    th, td { text-align: left; padding: 0.58rem 0.65rem; border-bottom: 1px solid var(--border); }
    th { color: var(--muted); font-weight: 500; font-size: 0.74rem; text-transform: uppercase; letter-spacing: 0.05em; }
    code, pre { font-family: ui-monospace, "JetBrains Mono", SFMono-Regular, Menlo, monospace; }
    pre { background: var(--bg); border: 1px solid var(--border); border-radius: 7px; padding: 0.9rem; overflow-x: auto; font-size: 0.79rem; color: var(--text-2); }
    .badge { display: inline-block; padding: 0.14rem 0.45rem; border-radius: 5px; font-size: 0.71rem; font-weight: 600; font-family: ui-monospace, Menlo, monospace; border: 1px solid var(--border); }
    .b-ok { background: rgba(74, 222, 128, 0.10); color: var(--ok); border-color: rgba(74, 222, 128, 0.25); }
    .b-warn { background: rgba(251, 191, 36, 0.10); color: var(--warn); border-color: rgba(251, 191, 36, 0.25); }
    .b-err { background: rgba(229, 83, 75, 0.12); color: var(--err); border-color: rgba(229, 83, 75, 0.28); }
    .b-method { background: var(--surface-2); color: var(--text); border-color: var(--border-strong); }
    .toolbar { display: flex; gap: 0.45rem; align-items: center; margin-bottom: 0.9rem; flex-wrap: wrap; }
    .btn { background: transparent; color: var(--text-2); border: 1px solid var(--border); padding: 0.4rem 0.8rem; border-radius: 6px; cursor: pointer; font-size: 0.8rem; font-weight: 500; font-family: inherit; }
    .btn:hover { background: var(--surface-2); border-color: var(--border-strong); color: var(--text); }
    .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 0.75rem; margin-bottom: 1.25rem; }
    .stat { background: var(--surface); border: 1px solid var(--border); border-radius: 9px; padding: 0.9rem 1.05rem; }
    .stat .k { color: var(--muted); font-size: 0.72rem; font-family: ui-monospace, Menlo, monospace; text-transform: uppercase; letter-spacing: 0.06em; }
    .stat .v { font-size: 1.45rem; font-weight: 600; letter-spacing: -0.02em; color: var(--text); margin-top: 0.25rem; }
    .sql-list { margin: 0.35rem 0 0; padding: 0.55rem 0.75rem; background: var(--bg); border-radius: 6px; border: 1px solid var(--border); font-size: 0.75rem; color: var(--text-2); }
    .sql-list div { margin-bottom: 0.25rem; }
    .proof-banner { display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap; padding: 1.15rem 1.25rem; border-radius: 9px; background: var(--surface); border: 1px solid var(--border-strong); margin-bottom: 1rem; }
    .grade-badge { display: inline-flex; align-items: center; justify-content: center; width: 48px; height: 48px; border-radius: 10px; font-size: 1.25rem; font-weight: 700; font-family: ui-monospace, Menlo, monospace; border: 1px solid rgba(74, 222, 128, 0.3); background: rgba(74, 222, 128, 0.12); color: var(--ok); }
    .grade-badge.fail { border-color: rgba(229, 83, 75, 0.35); background: rgba(229, 83, 75, 0.12); color: var(--err); }
    .pillar-grid { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 0.7rem; margin-bottom: 1rem; }
    @media (max-width: 960px) { .pillar-grid { grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); } }
    .pillar-card { background: var(--surface); border: 1px solid var(--border); border-radius: 9px; padding: 0.95rem 1.05rem; display: flex; flex-direction: column; gap: 0.4rem; }
    .pillar-top { display: flex; justify-content: space-between; align-items: center; gap: 0.5rem; }
    .pillar-name { font-size: 0.82rem; font-weight: 600; color: var(--text); }
    .pillar-score { font-size: 1.35rem; font-weight: 600; font-family: ui-monospace, Menlo, monospace; }
    .pillar-meta { font-size: 0.75rem; color: var(--text-2); line-height: 1.45; }
    .cap-tags { display: flex; gap: 0.3rem; flex-wrap: wrap; }
    .btn-primary { background: var(--text); color: var(--bg); border-color: var(--text); }
    .btn-primary:hover { opacity: 0.9; background: var(--text); color: var(--bg); }
    .mailbox-split { display: grid; grid-template-columns: 310px minmax(0, 1fr); gap: 1rem; align-items: start; }
    @media (max-width: 820px) { .mailbox-split { grid-template-columns: 1fr; } }
    .mail-item { width: 100%; text-align: left; background: var(--bg); border: 1px solid var(--border); border-radius: 7px; padding: 0.7rem 0.85rem; margin-bottom: 0.5rem; cursor: pointer; color: var(--text); font: inherit; transition: border-color 120ms ease; }
    .mail-item:hover, .mail-item.active { border-color: var(--border-strong); background: var(--surface-2); }
    .mail-subj { font-weight: 600; font-size: 0.84rem; margin-bottom: 0.2rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .mail-meta { font-size: 0.74rem; color: var(--text-2); font-family: ui-monospace, Menlo, monospace; }
    .mail-frame { width: 100%; min-height: 360px; border: 1px solid var(--border); border-radius: 7px; background: #ffffff; }
    .schema-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 0.85rem; }
  </style>
</head>
<body>
  <header>
    <div>
      <h1>{$brandSvg}<span>Kongono Dev Studio &middot; {$appName}</span></h1>
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
    <button data-tab="proofs">Proof Center</button>
    <button data-tab="mailbox">Dev Mailbox</button>
    <button data-tab="schema">Schema</button>
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
        <thead><tr><th>#</th><th>Method</th><th>Path</th><th>Status</th><th>Duration</th><th>Queries</th><th>Actions</th></tr></thead>
        <tbody id="traceRows"></tbody>
      </table>
    </div>
  </section>

  <section id="tab-proofs" class="panel">
    <div class="proof-banner">
      <div style="display:flex;align-items:center;gap:0.9rem;">
        <div class="grade-badge" id="proofGrade">&mdash;</div>
        <div>
          <div style="font-weight:600;font-size:0.98rem;" id="proofHeadline">Kongono Readiness Certificate</div>
          <div class="sub" id="proofSub">Run all 5 deterministic proof engines (17-Rule Contracts, Policy, Isolation, Perf/N+1, Upgrade)</div>
        </div>
      </div>
      <div class="toolbar" style="margin-bottom:0;">
        <button class="btn btn-primary" id="runProofsBtn">Run All 5 Proofs</button>
        <button class="btn" id="copyProofJsonBtn">Copy Proof JSON</button>
      </div>
    </div>

    <div class="pillar-grid" id="pillarGrid"></div>

    <div class="card" id="proofFindingsCard" style="display:none;">
      <h3 style="margin-top:0;">5W1H Proof Findings</h3>
      <pre id="proofFindingsOut"></pre>
    </div>

    <div class="card">
      <h3 style="margin-top:0;">Route Capability &amp; Contract Matrix</h3>
      <table>
        <thead><tr><th>Route</th><th>Handler</th><th>Security &amp; Capabilities</th><th>Input DTO</th></tr></thead>
        <tbody id="capMatrixRows"><tr><td colspan="4" style="color:var(--muted)">Click "Run All 5 Proofs" to load the live capability &amp; DTO matrix.</td></tr></tbody>
      </table>
    </div>

    <div class="card">
      <div style="display:flex;justify-content:space-between;align-items:center;gap:0.75rem;flex-wrap:wrap;margin-bottom:0.75rem;">
        <h3 style="margin:0;">Production Incident Time-Machine (KGN-*)</h3>
        <button class="btn" id="refreshIncidentsBtn">Refresh Incidents</button>
      </div>
      <table>
        <thead><tr><th>Reference</th><th>Status</th><th>Endpoint</th><th>Code</th><th>Actions</th></tr></thead>
        <tbody id="incidentRows"><tr><td colspan="5" style="color:var(--muted)">No recorded incidents yet (enable with <code>\$app-&gt;recordIncidents()</code>).</td></tr></tbody>
      </table>
      <pre id="incidentOut" style="display:none;margin-top:0.85rem;"></pre>
    </div>
  </section>

  <section id="tab-mailbox" class="panel">
    <div class="card">
      <div class="toolbar" style="justify-content:space-between;">
        <div>
          <strong style="font-size:0.95rem;">Built-In Dev Mailbox</strong>
          <span class="sub" style="margin-left:0.5rem;">Captures outgoing <code>kongono/mail</code> emails locally (zero SMTP / Mailpit needed)</span>
        </div>
        <div style="display:flex;gap:0.45rem;">
          <button class="btn" id="refreshMailboxBtn">Refresh Mailbox</button>
          <button class="btn" id="clearMailboxBtn">Clear Mailbox</button>
        </div>
      </div>
      <div class="mailbox-split">
        <div id="mailboxList" style="color:var(--muted);font-size:0.82rem;">Click Refresh Mailbox or send an email via <code>DevMailboxTransport</code>.</div>
        <div id="mailboxPreview">
          <div style="color:var(--muted);font-size:0.84rem;padding:1.5rem;border:1px dashed var(--border);border-radius:7px;text-align:center;">Select a captured email to preview its rendered HTML, plain-text fallback, and headers.</div>
        </div>
      </div>
    </div>
  </section>

  <section id="tab-schema" class="panel">
    <div class="card">
      <div class="toolbar" style="justify-content:space-between;">
        <div>
          <strong style="font-size:0.95rem;">Live Database Schema Explorer (Read-Only)</strong>
          <span class="sub" id="schemaMeta" style="margin-left:0.5rem;">Inspects tables, columns, types, and indexes without reading row data</span>
        </div>
        <button class="btn" id="refreshSchemaBtn">Refresh Schema</button>
      </div>
      <div class="schema-grid" id="schemaGrid" style="color:var(--muted);font-size:0.84rem;">Open this tab to inspect live database schema metadata.</div>
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
      <h3 class="ai-head">{$oshieSvg}<span>Oshie — Interactive Kongono Tutor</span></h3>
      <div class="toolbar">
        <select id="oshieLesson" class="btn">
          <option value="">Ask a question below...</option>
          <option value="routing">Lesson: Routing &amp; Controllers</option>
          <option value="views">Lesson: Compiled .kgn.html Views</option>
          <option value="live">Lesson: Kongono Live 2.5</option>
          <option value="bridge">Lesson: React / Vue / Svelte Bridge</option>
          <option value="validation">Lesson: Typed DTO Validation</option>
          <option value="database">Lesson: QueryBuilder, Replicas &amp; Migrations</option>
          <option value="security">Lesson: Security, Auth, PATs &amp; SSRF</option>
          <option value="storage">Lesson: Streaming Storage &amp; S3</option>
          <option value="jobs">Lesson: Queues, DLQ, Batches &amp; Scheduler</option>
          <option value="testing">Lesson: Global-Free Testing</option>
          <option value="ai">Lesson: Multi-Provider AI, Hybrid RAG &amp; Evals</option>
          <option value="ops-deploy">Lesson: /_ops, FrankenPHP &amp; Scaffolding</option>
        </select>
        <input id="oshieInput" class="ai-input" type="text" placeholder="Ask Oshie anything about your Kongono app...">
        <button class="btn" id="oshieSend">Ask Oshie</button>
      </div>
      <pre id="oshieOut">Pick a lesson or ask a question to study Kongono right inside Dev Studio.</pre>
    </div>
    <div class="card">
      <h3 class="ai-head">{$chieSvg}<span>Chie — Project-Aware AI Builder (Safe Diff Preview)</span></h3>
      <div class="toolbar">
        <input id="chieInput" class="ai-input" type="text" placeholder="Describe what to build (e.g. Add a /api/stats endpoint)...">
        <button class="btn" id="chieSend">Preview Diff with Chie</button>
      </div>
      <pre id="chieOut">Chie previews unified diffs against your live ApplicationGraph without modifying disk from the browser. Run `kongo chie "..." --apply` in your terminal to write approved changes.</pre>
    </div>
    <div class="card">
      <h3 class="ai-head">{$hanteiSvg}<span>Hantei — Instant Error &amp; Diagnostic Debugger</span></h3>
      <div class="toolbar">
        <input id="hanteiInput" class="ai-input" type="text" placeholder="Paste an error message or KGN-* diagnostic code...">
        <button class="btn" id="hanteiSend">Diagnose with Hantei</button>
      </div>
      <pre id="hanteiOut">Hantei automatically scrubs secrets before diagnosing errors against your ApplicationGraph.</pre>
    </div>
    <div class="card">
      <h3>Live MCP Workbench (POST /mcp)</h3>
      <div class="toolbar">
        <button class="btn" data-mcp="kongono_prove_all">Call kongono_prove_all</button>
        <button class="btn" data-mcp="kongono_diagnose">Call kongono_diagnose</button>
        <button class="btn" data-mcp="kongono_routes">Call kongono_routes</button>
        <button class="btn" data-mcp="kongono_oshie_lesson">Call kongono_oshie_lesson</button>
        <button class="btn" data-mcp="kongono_graph">Call kongono_graph</button>
      </div>
      <pre id="mcpOut">Click a button above to invoke your live Kongono MCP server.</pre>
    </div>
  </section>

  <script>
    const BASE = {$baseJson};
    const GRAPH = {$graphJson};
    let traces = {$tracesJson};

HTML;

        $script = <<<'JS'
    function esc(s) {
      return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
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
        tb.innerHTML = '<tr><td colspan="7" style="color:#64748b">No requests captured yet. Visit / or an API endpoint then click Refresh.</td></tr>';
        return;
      }
      tb.innerHTML = traces.map(t => {
        const cls = t.status >= 500 ? 'b-err' : (t.status >= 400 ? 'b-warn' : 'b-ok');
        const queries = t.queries || [];
        const qHtml = queries.length
          ? '<details><summary>' + queries.length + ' SQL</summary><div class="sql-list">' +
            queries.map(q => '<div><code>' + esc(q.sql) + '</code> (' + q.ms + ' ms)</div>').join('') +
            '</div></details>'
          : '0';
        return '<tr><td>' + t.id + '</td><td><span class="badge b-method">' + esc(t.method) + '</span></td><td><code>' + esc(t.path) + '</code></td><td><span class="badge ' + cls + '">' + t.status + '</span></td><td>' + t.duration_ms + ' ms</td><td>' + qHtml + '</td><td><button class="btn" data-diagnose-trace="' + t.id + '">Diagnose</button></td></tr>';
      }).join('');
      tb.querySelectorAll('[data-diagnose-trace]').forEach(btn => {
        btn.addEventListener('click', () => diagnoseTrace(btn.dataset.diagnoseTrace));
      });
    }
    renderTraces();

    function activateTab(tabName) {
      document.querySelectorAll('nav.tabs button').forEach(b => b.classList.toggle('active', b.dataset.tab === tabName));
      document.querySelectorAll('.panel').forEach(p => p.classList.toggle('active', p.id === 'tab-' + tabName));
    }

    async function streamSsePost(url, payload, onDelta) {
      const res = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'text/event-stream', 'X-Kongono-Dev': '1' },
        body: JSON.stringify(payload),
      });
      if (res.status === 501 || !res.body) {
        return null;
      }
      const reader = res.body.getReader();
      const decoder = new TextDecoder();
      let buf = '';
      let donePayload = null;
      while (true) {
        const { value, done } = await reader.read();
        if (done) break;
        buf += decoder.decode(value, { stream: true });
        const frames = buf.split('\n\n');
        buf = frames.pop() || '';
        for (const frame of frames) {
          let ev = 'message';
          const dataLines = [];
          for (const line of frame.split('\n')) {
            if (line.startsWith('event:')) ev = line.slice(6).trim();
            else if (line.startsWith('data:')) dataLines.push(line.slice(5).trim());
          }
          if (!dataLines.length) continue;
          const parsed = JSON.parse(dataLines.join('\n'));
          if (ev === 'delta' && parsed.text) onDelta(parsed.text);
          else if (ev === 'done') donePayload = parsed;
          else if (ev === 'error') throw new Error(parsed.error || 'Stream error');
        }
      }
      return donePayload;
    }

    async function diagnoseTrace(traceId) {
      activateTab('ai');
      const out = document.getElementById('hanteiOut');
      out.textContent = 'Running Hantei diagnosis on trace #' + traceId + '...';
      const res = await fetch(BASE + '/api/hantei/trace/' + encodeURIComponent(traceId), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Kongono-Dev': '1' },
        body: '{}',
      });
      const json = await res.json();
      if (json.error) {
        out.textContent = json.error;
        return;
      }
      const parts = [json.diagnosis || ''];
      if (json.telemetry && json.telemetry.footer) parts.push('\n' + json.telemetry.footer);
      if (json.regression_test) parts.push('\n--- Generated PHPUnit 13 Regression Test ---\n' + json.regression_test);
      out.textContent = parts.join('\n').trim();
    }

    document.getElementById('routeRows').innerHTML = (GRAPH.routes || []).map(r =>
      '<tr><td><span class="badge b-method">' + esc((r.methods || []).join('|')) + '</span></td><td><code>' + esc(r.path) + '</code></td><td>' + esc(r.name || '-') + '</td><td><code>' + esc(r.handler) + '</code></td></tr>'
    ).join('');

    document.getElementById('mwList').textContent = (GRAPH.middleware || []).join('\n') || '(none)';
    document.getElementById('svcList').textContent = (GRAPH.services || []).map(s => '[' + s.lifetime + '] ' + s.id).join('\n') || '(none)';
    document.getElementById('viewList').textContent = (GRAPH.views || []).join('\n') || '(none)';

    let lastProofReport = null;
    let proofsLoaded = false;

    function renderIncidentsList(incidents) {
      const tb = document.getElementById('incidentRows');
      if (!incidents || !incidents.length) {
        tb.innerHTML = '<tr><td colspan="5" style="color:var(--muted)">No recorded incidents yet. Trigger an error with <code>$app-&gt;recordIncidents()</code> enabled to replay it here.</td></tr>';
        return;
      }
      tb.innerHTML = incidents.map(inc => {
        const cls = inc.status_code >= 500 ? 'b-err' : 'b-warn';
        const code = (inc.explanation && inc.explanation.code) ? inc.explanation.code : 'KGN-ERR';
        return '<tr><td><code>' + esc(inc.reference) + '</code></td><td><span class="badge ' + cls + '">' + inc.status_code + '</span></td><td><span class="badge b-method">' + esc(inc.method) + '</span> <code>' + esc(inc.path) + '</code></td><td><code>' + esc(code) + '</code></td><td><button class="btn" data-inc-replay="' + esc(inc.reference) + '">Replay</button> <button class="btn" data-inc-test="' + esc(inc.reference) + '">PHPUnit Test</button></td></tr>';
      }).join('');
      tb.querySelectorAll('[data-inc-replay]').forEach(b => {
        b.addEventListener('click', async () => {
          const out = document.getElementById('incidentOut');
          out.style.display = 'block';
          out.textContent = 'Replaying incident ' + b.dataset.incReplay + ' in-process...';
          const r = await fetch(BASE + '/api/incidents/' + encodeURIComponent(b.dataset.incReplay) + '/replay', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Kongono-Dev': '1' },
            body: '{}',
          });
          const j = await r.json();
          out.textContent = j.error ? j.error : ('Replay ' + j.reference + ': HTTP ' + j.original_status + ' -> HTTP ' + j.replayed_status + ' (' + (j.resolved ? 'RESOLVED' : 'STILL FAILING') + ', ' + j.elapsed_ms + 'ms)\n\n' + (j.response_preview || ''));
        });
      });
      tb.querySelectorAll('[data-inc-test]').forEach(b => {
        b.addEventListener('click', async () => {
          const out = document.getElementById('incidentOut');
          out.style.display = 'block';
          out.textContent = 'Generating PHPUnit 13 regression test...';
          const r = await fetch(BASE + '/api/incidents/' + encodeURIComponent(b.dataset.incTest) + '/test');
          const j = await r.json();
          out.textContent = j.error ? j.error : ('// ' + (j.relative_path || '') + '\n\n' + (j.content || ''));
        });
      });
    }

    async function runAllProofs() {
      const btn = document.getElementById('runProofsBtn');
      const sub = document.getElementById('proofSub');
      btn.disabled = true;
      sub.textContent = 'Running all 5 deterministic proof engines...';
      try {
        const res = await fetch(BASE + '/api/proofs', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-Kongono-Dev': '1' },
          body: '{}',
        });
        const report = await res.json();
        if (report.error) {
          sub.textContent = report.error;
          return;
        }
        lastProofReport = report;
        proofsLoaded = true;
        const gradeEl = document.getElementById('proofGrade');
        gradeEl.textContent = report.grade || '—';
        gradeEl.classList.toggle('fail', !report.passed);
        document.getElementById('proofHeadline').textContent = 'Kongono Readiness Certificate — ' + report.overall_score + '/100 (' + (report.passed ? 'PASS' : 'FAILED') + ')';
        sub.textContent = 'Completed in ' + report.elapsed_ms + 'ms · ' + report.total_errors + ' error(s), ' + report.total_warnings + ' warning(s), ' + report.fixable_count + ' auto-fixable';

        const pOrder = ['diagnose', 'policy', 'isolation', 'perf', 'upgrade'];
        document.getElementById('pillarGrid').innerHTML = pOrder.map(key => {
          const p = report.pillars[key];
          if (!p) return '';
          const bCls = p.passed ? 'b-ok' : 'b-err';
          return '<div class="pillar-card"><div class="pillar-top"><span class="pillar-name">' + esc(p.label) + '</span><span class="badge ' + bCls + '">' + (p.passed ? 'PASS' : 'FAIL') + '</span></div><div class="pillar-score">' + p.score + '/100</div><div class="pillar-meta">' + esc(p.summary) + '</div></div>';
        }).join('');

        const allFindings = [];
        for (const k of ['diagnose', 'policy', 'isolation', 'perf']) {
          const list = (report.pillars[k] && report.pillars[k].findings) || [];
          for (const f of list) {
            allFindings.push('[' + String(f.severity || 'error').toUpperCase() + '] ' + f.code + ' (' + f.where + ')\n  What: ' + f.what + '\n  Why:  ' + f.why + (f.how_to_fix && f.how_to_fix[0] ? '\n  Fix:  ' + f.how_to_fix[0] : ''));
          }
        }
        const fCard = document.getElementById('proofFindingsCard');
        if (allFindings.length) {
          fCard.style.display = 'block';
          document.getElementById('proofFindingsOut').textContent = allFindings.join('\n\n');
        } else {
          fCard.style.display = 'none';
        }

        const caps = report.route_capabilities || [];
        document.getElementById('capMatrixRows').innerHTML = caps.length
          ? caps.map(c => {
              const tags = (c.attributes || []).length
                ? '<div class="cap-tags">' + c.attributes.map(a => '<span class="badge b-ok">#[' + esc(a) + ']</span>').join('') + '</div>'
                : '<span style="color:var(--muted)">public</span>';
              const dto = c.dto ? '<span class="badge b-method">' + esc(c.dto) + '</span>' : '<span style="color:var(--muted)">—</span>';
              return '<tr><td><code>' + esc(c.signature) + '</code></td><td><code>' + esc(c.handler) + '</code></td><td>' + tags + '</td><td>' + dto + '</td></tr>';
            }).join('')
          : '<tr><td colspan="4" style="color:var(--muted)">No routes registered.</td></tr>';

        renderIncidentsList(report.incidents || []);
      } finally {
        btn.disabled = false;
      }
    }

    document.getElementById('runProofsBtn').addEventListener('click', runAllProofs);
    document.getElementById('copyProofJsonBtn').addEventListener('click', async () => {
      if (!lastProofReport) return;
      try {
        await navigator.clipboard.writeText(JSON.stringify(lastProofReport, null, 2));
        const btn = document.getElementById('copyProofJsonBtn');
        btn.textContent = 'Copied!';
        setTimeout(() => { btn.textContent = 'Copy Proof JSON'; }, 1200);
      } catch (e) {}
    });
    document.getElementById('refreshIncidentsBtn').addEventListener('click', async () => {
      const r = await fetch(BASE + '/api/incidents');
      const j = await r.json();
      renderIncidentsList(j.incidents || []);
    });

    let mailboxLoaded = false;
    let mailboxMessages = [];
    let schemaLoaded = false;

    function renderMailPreview(msg) {
      const box = document.getElementById('mailboxPreview');
      if (!msg) {
        box.innerHTML = '<div style="color:var(--muted);font-size:0.84rem;padding:1.5rem;border:1px dashed var(--border);border-radius:7px;text-align:center;">No email selected.</div>';
        return;
      }
      const atts = Array.isArray(msg.attachments) ? msg.attachments : [];
      const attHtml = atts.length
        ? '<div class="mail-meta" style="margin-top:0.35rem;display:flex;flex-wrap:wrap;gap:0.35rem;align-items:center;">Attachments: '
          + atts.map(a => '<span class="badge b-method">' + esc(a.filename || 'file') + ' (' + esc(String(a.bytes ?? 0)) + ' B)</span>').join('')
          + '</div>'
        : '';
      const meta = '<div class="card" style="margin-bottom:0.75rem;padding:0.85rem 1rem;">'
        + '<div style="font-weight:600;font-size:0.95rem;margin-bottom:0.35rem;">' + esc(msg.subject || '(no subject)') + '</div>'
        + '<div class="mail-meta">From: ' + esc(msg.from || '—') + ' &middot; To: ' + esc((msg.to || []).join(', ') || '—') + '</div>'
        + '<div class="mail-meta">Sent: ' + esc(msg.sent_at || '') + ' &middot; ID: ' + esc(msg.id || '') + '</div>'
        + attHtml
        + '</div>';
      if (msg.html) {
        box.innerHTML = meta + '<iframe class="mail-frame" sandbox="" id="mailHtmlFrame"></iframe>'
          + (msg.text ? '<details style="margin-top:0.65rem;"><summary style="cursor:pointer;color:var(--text-2);font-size:0.8rem;">Plain-Text Body</summary><pre>' + esc(msg.text) + '</pre></details>' : '');
        const frame = document.getElementById('mailHtmlFrame');
        if (frame) frame.srcdoc = msg.html;
      } else {
        box.innerHTML = meta + '<pre>' + esc(msg.text || '(empty body)') + '</pre>';
      }
    }

    async function loadMailbox() {
      const listEl = document.getElementById('mailboxList');
      listEl.textContent = 'Loading captured emails...';
      const r = await fetch(BASE + '/api/mailbox', { cache: 'no-store' });
      const j = await r.json();
      mailboxMessages = j.messages || [];
      mailboxLoaded = true;
      if (!mailboxMessages.length) {
        listEl.innerHTML = '<div style="color:var(--muted);padding:0.75rem 0;">No emails captured yet. Send mail using <code>DevMailboxTransport</code> to inspect it here.</div>';
        renderMailPreview(null);
        return;
      }
      listEl.innerHTML = mailboxMessages.map((m, idx) =>
        '<button class="mail-item' + (idx === 0 ? ' active' : '') + '" data-mail-idx="' + idx + '">'
        + '<div class="mail-subj">' + esc(m.subject || '(no subject)') + '</div>'
        + '<div class="mail-meta">To: ' + esc((m.to || []).join(', ')) + '</div>'
        + '<div class="mail-meta">' + esc(m.sent_at || '') + '</div>'
        + '</button>'
      ).join('');
      listEl.querySelectorAll('[data-mail-idx]').forEach(btn => {
        btn.addEventListener('click', () => {
          listEl.querySelectorAll('.mail-item').forEach(el => el.classList.remove('active'));
          btn.classList.add('active');
          renderMailPreview(mailboxMessages[Number(btn.dataset.mailIdx)]);
        });
      });
      renderMailPreview(mailboxMessages[0]);
    }

    async function loadSchema() {
      const grid = document.getElementById('schemaGrid');
      const meta = document.getElementById('schemaMeta');
      grid.textContent = 'Inspecting database schema...';
      const r = await fetch(BASE + '/api/schema', { cache: 'no-store' });
      const j = await r.json();
      schemaLoaded = true;
      const connected = Boolean(j.connected || j.dialect);
      const rawTables = j.tables || [];
      const tables = Array.isArray(rawTables)
        ? rawTables
        : Object.entries(rawTables).map(([name, info]) => ({
            name,
            columns: (info && info.columns) || [],
            indexes: (info && info.indexes) || [],
          }));
      if (!connected || !tables.length) {
        meta.textContent = connected ? ('Connected (' + (j.dialect || 'sql') + ') · 0 tables') : 'No ConnectionInterface registered in container';
        grid.innerHTML = '<div style="color:var(--muted);">' + (connected ? 'No database tables found yet — run <code>kongo migrate</code>.' : 'Bind a <code>ConnectionInterface</code> in <code>app/bootstrap.php</code> to inspect your live schema here.') + '</div>';
        return;
      }
      meta.textContent = 'Dialect: ' + (j.dialect || 'sql') + ' · ' + tables.length + ' table(s)';
      grid.innerHTML = tables.map(t => {
        const cols = (t.columns || []).map(c =>
          '<tr><td><code>' + esc(c.name) + '</code>' + (c.primary ? ' <span class="badge b-method">PK</span>' : '') + '</td><td><code>' + esc(c.type || '') + '</code></td><td>' + (c.nullable ? 'NULL' : 'NOT NULL') + '</td></tr>'
        ).join('');
        const idxHtml = (t.indexes && t.indexes.length)
          ? '<div class="mail-meta" style="margin-top:0.5rem;">Indexes: ' + t.indexes.map(i => '<code>' + esc(i) + '</code>').join(', ') + '</div>'
          : '';
        return '<div class="card" style="margin-bottom:0;"><div style="font-weight:600;margin-bottom:0.5rem;"><code>' + esc(t.name) + '</code></div><table><thead><tr><th>Column</th><th>Type</th><th>Null</th></tr></thead><tbody>' + cols + '</tbody></table>' + idxHtml + '</div>';
      }).join('');
    }

    document.getElementById('refreshMailboxBtn').addEventListener('click', loadMailbox);
    document.getElementById('clearMailboxBtn').addEventListener('click', async () => {
      await fetch(BASE + '/api/mailbox/clear', { method: 'POST', headers: { 'X-Kongono-Dev': '1' } });
      mailboxMessages = [];
      loadMailbox();
    });
    document.getElementById('refreshSchemaBtn').addEventListener('click', loadSchema);

    document.querySelectorAll('nav.tabs button').forEach(btn => {
      btn.addEventListener('click', () => {
        document.querySelectorAll('nav.tabs button').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
        if (btn.dataset.tab === 'proofs' && !proofsLoaded) {
          runAllProofs();
        } else if (btn.dataset.tab === 'mailbox' && !mailboxLoaded) {
          loadMailbox();
        } else if (btn.dataset.tab === 'schema' && !schemaLoaded) {
          loadSchema();
        }
      });
    });

    document.getElementById('refreshBtn').addEventListener('click', async () => {
      const res = await fetch(BASE + '/api/traces');
      traces = await res.json();
      renderTraces();
    });
    document.getElementById('clearBtn').addEventListener('click', async () => {
      await fetch(BASE + '/api/clear', { method: 'POST', headers: { 'X-Kongono-Dev': '1' } });
      traces = [];
      renderTraces();
    });
    document.getElementById('oshieSend').addEventListener('click', async () => {
      const out = document.getElementById('oshieOut');
      const lesson = document.getElementById('oshieLesson').value;
      const question = document.getElementById('oshieInput').value.trim();
      out.textContent = 'Asking Oshie...';
      try {
        let streamed = '';
        const done = await streamSsePost(BASE + '/api/oshie/stream', { question, lesson }, (chunk) => {
          streamed += chunk;
          out.textContent = streamed;
        });
        if (done !== null) {
          const text = streamed || done.reply || '';
          const footer = done.telemetry && done.telemetry.footer ? '\n\n' + done.telemetry.footer : '';
          out.textContent = text + footer;
          return;
        }
      } catch (err) {
        out.textContent = 'Oshie error: ' + err.message;
        return;
      }
      const res = await fetch(BASE + '/api/oshie', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Kongono-Dev': '1' },
        body: JSON.stringify({ question, lesson }),
      });
      const json = await res.json();
      out.textContent = json.reply || JSON.stringify(json, null, 2);
    });
    document.getElementById('hanteiSend').addEventListener('click', async () => {
      const out = document.getElementById('hanteiOut');
      const diagnostic = document.getElementById('hanteiInput').value.trim();
      if (!diagnostic) return;
      out.textContent = 'Running Hantei diagnosis...';
      try {
        let streamed = '';
        const done = await streamSsePost(BASE + '/api/hantei/stream', { diagnostic }, (chunk) => {
          streamed += chunk;
          out.textContent = streamed;
        });
        if (done !== null) {
          const text = streamed || done.diagnosis || '';
          const footer = done.telemetry && done.telemetry.footer ? '\n\n' + done.telemetry.footer : '';
          out.textContent = text + footer;
          return;
        }
      } catch (err) {
        out.textContent = 'Hantei error: ' + err.message;
        return;
      }
      const res = await fetch(BASE + '/api/hantei', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Kongono-Dev': '1' },
        body: JSON.stringify({ diagnostic }),
      });
      const json = await res.json();
      out.textContent = json.diagnosis || JSON.stringify(json, null, 2);
    });
    document.getElementById('chieSend').addEventListener('click', async () => {
      const out = document.getElementById('chieOut');
      const instruction = document.getElementById('chieInput').value.trim();
      if (!instruction) return;
      out.textContent = 'Asking Chie for a safe diff preview...';
      const res = await fetch(BASE + '/api/chie', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Kongono-Dev': '1' },
        body: JSON.stringify({ instruction }),
      });
      const json = await res.json();
      const blocks = [json.summary || ''];
      for (const d of (json.diffs || [])) {
        blocks.push('\n[' + (d.action || 'change') + '] ' + d.path + ' — ' + (d.explanation || '') + '\n' + (d.diff || ''));
      }
      if (json.telemetry && json.telemetry.footer) blocks.push('\n' + json.telemetry.footer);
      if (json.apply_command) blocks.push('\nApply in terminal:\n  ' + json.apply_command);
      out.textContent = blocks.join('\n').trim() || JSON.stringify(json, null, 2);
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
JS;

        return $head . $script;
    }
}
