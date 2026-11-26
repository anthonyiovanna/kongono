<?php

declare(strict_types=1);

namespace Kongono\Http\Dev;

use Kongono\Http\App;
use Kongono\Http\Brand\KongonoBrand;
use Kongono\Http\OpenApi\OpenApiGenerator;
use Throwable;

/**
 * Self-contained, zero-CDN, strict-CSP HTML5 Kongono Dev Studio (`/_kongono`).
 *
 * Calm, sidebar-driven workspace combining live request/SQL/memory tracing with inline Replay,
 * PHPUnit 13 export & cURL copy, 5-pillar Proof Center with structured 5W1H cards, Mailtrap-style
 * Dev Mailbox with mobile preview & OTP/link extraction, read-only Schema Explorer, inline Try Route
 * tester, nondisclosing Runtime & Env health, and a focused AI/MCP workbench.
 */
final class DevDashboardRenderer
{
    public function render(App $app, DevInspector $inspector, string $basePath = '/_kongono'): string
    {
        $graph = $app->graph()->toArray();
        $appName = htmlspecialchars(is_string($graph['app'] ?? null) ? $graph['app'] : 'Kongono App', ENT_QUOTES, 'UTF-8');
        $cleanBase = rtrim($basePath, '/');
        $safeBase = htmlspecialchars($cleanBase, ENT_QUOTES, 'UTF-8');
        $bootstrapJson = json_encode(
            [
                'base' => $cleanBase,
                'graph' => $graph,
                'traces' => $inspector->traces(),
                'route_templates' => $this->buildRouteTemplates($app),
            ],
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE,
        );
        $phpVersion = htmlspecialchars(PHP_VERSION, ENT_QUOTES, 'UTF-8');
        $brandSvg = KongonoBrand::markSvg(22, 'kgn-dev-gm');
        $oshieSvg = KongonoBrand::oshieSvg(18, 'kgn-dev-oshie');
        $chieSvg = KongonoBrand::chieSvg(18, 'kgn-dev-chie');
        $hanteiSvg = KongonoBrand::hanteiSvg(18, 'kgn-dev-hantei');

        return <<<HTML
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{$appName} — Kongono Dev Studio</title>
  <link rel="icon" type="image/svg+xml" href="{$safeBase}/assets/favicon.svg">
  <link rel="stylesheet" href="{$safeBase}/assets/studio.css">
</head>
<body>
  <div class="studio-shell">
    <aside class="studio-sidebar" aria-label="Kongono Dev Studio navigation">
      <div class="sidebar-brand">
        <div class="brand-row">
          {$brandSvg}
          <div class="brand-text">
            <div class="brand-name">Kongono Dev Studio</div>
            <div class="brand-app">{$appName}</div>
          </div>
        </div>
        <div class="brand-meta">PHP {$phpVersion} &middot; Debug Only</div>
      </div>

      <nav class="tabs" role="tablist">
        <div class="nav-group">
          <div class="nav-group-label">Observe</div>
          <button type="button" class="nav-btn active" data-tab="requests">
            <span class="nav-label">Live Requests</span>
            <span class="nav-right"><span class="tab-count" id="countRequests">0</span></span>
          </button>
          <button type="button" class="nav-btn" data-tab="mailbox">
            <span class="nav-label">Dev Mailbox</span>
            <span class="nav-right"><span class="tab-count is-hidden" id="countMailbox"></span></span>
          </button>
          <button type="button" class="nav-btn" data-tab="runtime">
            <span class="nav-label">Runtime &amp; Env</span>
            <span class="nav-right"><span class="nav-kbd">3</span></span>
          </button>
        </div>

        <div class="nav-group">
          <div class="nav-group-label">Verify</div>
          <button type="button" class="nav-btn" data-tab="proofs">
            <span class="nav-label">Proof Center</span>
            <span class="nav-right"><span class="tab-count is-hidden" id="countProofs"></span></span>
          </button>
          <button type="button" class="nav-btn" data-tab="schema">
            <span class="nav-label">Schema</span>
            <span class="nav-right"><span class="nav-kbd">5</span></span>
          </button>
        </div>

        <div class="nav-group">
          <div class="nav-group-label">Explore</div>
          <button type="button" class="nav-btn" data-tab="routes">
            <span class="nav-label">Routes</span>
            <span class="nav-right"><span class="tab-count" id="countRoutes">0</span></span>
          </button>
          <button type="button" class="nav-btn" data-tab="graph">
            <span class="nav-label">App Graph</span>
            <span class="nav-right"><span class="nav-kbd">7</span></span>
          </button>
          <button type="button" class="nav-btn" data-tab="ai">
            <span class="nav-label">AI &amp; MCP</span>
            <span class="nav-right"><span class="nav-kbd">8</span></span>
          </button>
        </div>
      </nav>

      <div class="sidebar-footer">
        <div class="nav-group-label">App Endpoints</div>
        <div class="sidebar-links">
          <a href="/">App Home</a>
          <a href="/docs">API Explorer</a>
          <a href="/openapi.json">OpenAPI 3.1</a>
          <a href="/health">Health</a>
          <a href="/metrics">Metrics</a>
        </div>
      </div>
    </aside>

    <main class="studio-main">
      <header class="workspace-bar">
        <div class="workspace-heading">
          <h1 id="workspaceTitle">Live Requests</h1>
          <span class="workspace-sub" id="workspaceSub">Captured HTTP traffic, SQL timing, replay &amp; PHPUnit 13 export</span>
        </div>
        <div class="workspace-actions">
          <div class="inline-telemetry" id="stats"></div>
          <button type="button" class="pill pill-btn" id="themeToggleBtn" title="Toggle light/dark theme (t)">Light</button>
          <button type="button" class="pill pill-btn" id="shortcutsBtn" title="Keyboard shortcuts (?)">?</button>
        </div>
      </header>

      <div class="workspace-body">
        <section id="tab-requests" class="panel active">
          <div class="card">
            <div class="toolbar toolbar-between">
              <div class="toolbar-group">
                <button type="button" class="btn" id="liveTailBtn" aria-pressed="false"><span class="live-dot" id="liveTailDot"></span>Live Tail: Off</button>
                <button type="button" class="btn" id="refreshBtn">Refresh</button>
                <button type="button" class="btn" id="clearBtn">Clear</button>
              </div>
              <div class="toolbar-group">
                <div class="filter-pills" id="traceFilters">
                  <button type="button" class="chip active" data-trace-filter="all">All</button>
                  <button type="button" class="chip" data-trace-filter="errors">Errors</button>
                  <button type="button" class="chip" data-trace-filter="n1">N+1</button>
                  <button type="button" class="chip" data-trace-filter="slow">Slow</button>
                </div>
                <input id="traceSearch" class="search-input" type="search" placeholder="Filter traces... (/)" aria-label="Filter traces">
              </div>
            </div>
            <div class="table-wrap trace-table-wrap">
              <table>
                <thead><tr><th>#</th><th>Method</th><th>Path</th><th>Status</th><th>Time</th><th>Mem</th><th>SQL</th><th class="col-right">Quick</th></tr></thead>
                <tbody id="traceRows"></tbody>
              </table>
            </div>
          </div>

          <div class="card is-hidden" id="traceDrawer">
            <div class="toolbar toolbar-between drawer-head">
              <div class="drawer-title-wrap">
                <strong id="drawerTitle">Trace</strong>
                <span class="sub" id="drawerSubtitle"></span>
              </div>
              <div class="toolbar-group">
                <button type="button" class="btn" id="drawerReplayBtn">Replay</button>
                <button type="button" class="btn" id="drawerCopyCurlBtn">Copy cURL</button>
                <button type="button" class="btn" id="drawerDiagnoseBtn">Diagnose</button>
                <button type="button" class="btn" id="drawerCloseBtn">Close</button>
              </div>
            </div>
            <div class="subtabs" id="drawerTabs">
              <button type="button" class="subtab active" data-drawer-tab="overview">Overview &amp; Headers</button>
              <button type="button" class="subtab" data-drawer-tab="sql">SQL &amp; N+1 <span class="tab-count" id="drawerSqlCount">0</span></button>
              <button type="button" class="subtab" data-drawer-tab="replay">Replay Result</button>
              <button type="button" class="subtab" data-drawer-tab="test">PHPUnit 13 Test</button>
            </div>
            <div id="drawerPanel-overview" class="drawer-panel active"></div>
            <div id="drawerPanel-sql" class="drawer-panel"></div>
            <div id="drawerPanel-replay" class="drawer-panel">
              <div class="empty-box">Click <strong>Replay</strong> to re-execute this trace in-process and compare status, latency, and output.</div>
            </div>
            <div id="drawerPanel-test" class="drawer-panel">
              <div class="toolbar toolbar-between">
                <span class="sub">Generated deterministic PHPUnit 13 <code>TestClient</code> regression test</span>
                <button type="button" class="btn" id="copyExportedTestBtn">Copy Test</button>
              </div>
              <pre id="exportedTestOut">Select a trace to generate a PHPUnit 13 test.</pre>
            </div>
          </div>
        </section>

        <section id="tab-proofs" class="panel">
          <div class="proof-banner">
            <div class="proof-banner-left">
              <div class="grade-badge" id="proofGrade">&mdash;</div>
              <div>
                <div class="proof-title" id="proofHeadline">Kongono Readiness Certificate</div>
                <div class="sub" id="proofSub">Run all 5 deterministic proof engines (17-Rule Contracts, Policy, Isolation, Perf/N+1, Upgrade)</div>
              </div>
            </div>
            <div class="toolbar mb-0">
              <button type="button" class="btn btn-primary" id="runProofsBtn">Run All 5 Proofs</button>
              <button type="button" class="btn" id="copyProofJsonBtn">Copy JSON</button>
            </div>
          </div>

          <div class="pillar-grid" id="pillarGrid"></div>

          <div class="card is-hidden" id="proofFindingsCard">
            <div class="toolbar toolbar-between">
              <h2 class="card-title">5W1H Proof Findings</h2>
              <button type="button" class="btn btn-sm" id="toggleRawFindingsBtn">Toggle Raw Text</button>
            </div>
            <div id="proofFindingsList" class="findings-list"></div>
            <pre id="proofFindingsOut" class="is-hidden"></pre>
          </div>

          <div class="card">
            <div class="toolbar toolbar-between">
              <h2 class="card-title">Route Capability &amp; Contract Matrix</h2>
              <input id="capSearch" class="search-input" type="search" placeholder="Filter routes or capabilities... (/)" aria-label="Filter capability matrix">
            </div>
            <div class="table-wrap">
              <table>
                <thead><tr><th>Route</th><th>Handler</th><th>Security &amp; Capabilities</th><th>Input DTO</th></tr></thead>
                <tbody id="capMatrixRows"><tr><td colspan="4" class="muted">Click "Run All 5 Proofs" to load the live capability &amp; DTO matrix.</td></tr></tbody>
              </table>
            </div>
          </div>

          <div class="card">
            <div class="toolbar toolbar-between">
              <h2 class="card-title">Production Incident Time-Machine (KGN-*)</h2>
              <div class="toolbar-group">
                <button type="button" class="btn btn-sm is-hidden" id="copyIncidentOutBtn">Copy Output</button>
                <button type="button" class="btn btn-sm" id="refreshIncidentsBtn">Refresh</button>
              </div>
            </div>
            <div class="table-wrap">
              <table>
                <thead><tr><th>Reference</th><th>Status</th><th>Endpoint</th><th>Code</th><th>Actions</th></tr></thead>
                <tbody id="incidentRows"><tr><td colspan="5" class="muted">No recorded incidents yet (enable with <code>\$app-&gt;recordIncidents()</code>).</td></tr></tbody>
              </table>
            </div>
            <pre id="incidentOut" class="is-hidden mt-md"></pre>
          </div>
        </section>

        <section id="tab-mailbox" class="panel">
          <div class="card">
            <div class="toolbar toolbar-between">
              <input id="mailboxSearch" class="search-input search-wide" type="search" placeholder="Search subject, sender, or recipient... (/)" aria-label="Search mailbox">
              <div class="toolbar-group">
                <button type="button" class="btn" id="refreshMailboxBtn">Refresh Mailbox</button>
                <button type="button" class="btn" id="clearMailboxBtn">Clear Mailbox</button>
              </div>
            </div>
            <div class="mailbox-split">
              <div id="mailboxList" class="muted text-sm">Send an email via <code>DevMailboxTransport</code> to inspect it here.</div>
              <div id="mailboxPreview">
                <div class="empty-box">Select a captured email to preview Desktop/Mobile HTML, plain-text fallback, headers, and extracted verification links/codes.</div>
              </div>
            </div>
          </div>
        </section>

        <section id="tab-schema" class="panel">
          <div class="card">
            <div class="toolbar toolbar-between">
              <span class="sub" id="schemaMeta">Read-only schema metadata (tables, columns, types &amp; indexes)</span>
              <div class="toolbar-group">
                <input id="schemaSearch" class="search-input" type="search" placeholder="Filter tables or columns... (/)" aria-label="Filter schema">
                <button type="button" class="btn" id="refreshSchemaBtn">Refresh Schema</button>
              </div>
            </div>
            <div class="schema-grid muted text-sm" id="schemaGrid">Open this tab to inspect live database schema metadata.</div>
          </div>
        </section>

        <section id="tab-routes" class="panel">
          <div class="card">
            <div class="toolbar toolbar-between">
              <span class="sub">Click <strong>Try</strong> on any route to compose and run a live in-process request</span>
              <input id="routeSearch" class="search-input" type="search" placeholder="Filter routes... (/)" aria-label="Filter routes">
            </div>
            <div class="table-wrap">
              <table>
                <thead><tr><th>Methods</th><th>Path</th><th>Name</th><th>Handler</th><th class="col-right">Action</th></tr></thead>
                <tbody id="routeRows"></tbody>
              </table>
            </div>
          </div>

          <div class="card is-hidden" id="tryRouteDrawer">
            <div class="toolbar toolbar-between drawer-head">
              <div class="drawer-title-wrap">
                <strong id="tryRouteTitle">Try Route</strong>
                <span class="sub" id="tryRouteSubtitle">In-process route tester</span>
              </div>
              <div class="toolbar-group">
                <button type="button" class="btn btn-sm" id="tryRouteCurlBtn">Copy cURL</button>
                <button type="button" class="btn btn-sm" id="tryRouteCloseBtn">Close</button>
              </div>
            </div>

            <div class="try-form-row">
              <select id="tryMethod" class="btn try-method" aria-label="HTTP method">
                <option value="GET">GET</option>
                <option value="POST">POST</option>
                <option value="PUT">PUT</option>
                <option value="PATCH">PATCH</option>
                <option value="DELETE">DELETE</option>
                <option value="HEAD">HEAD</option>
                <option value="OPTIONS">OPTIONS</option>
              </select>
              <input id="tryPath" class="ai-input" type="text" placeholder="/api/notes" aria-label="Request path">
              <input id="tryQuery" class="search-input" type="text" placeholder="query=value (optional)" aria-label="Query string">
              <button type="button" class="btn btn-primary" id="trySendBtn">Send Request</button>
            </div>

            <div id="tryParamsRow" class="try-params is-hidden"></div>

            <div class="drawer-grid">
              <div class="drawer-box">
                <div class="drawer-box-title">Headers (One <code>Key: Value</code> per line)</div>
                <textarea id="tryHeaders" class="code-textarea" rows="4" placeholder="Accept: application/json&#10;Authorization: Bearer ..."></textarea>
              </div>
              <div class="drawer-box">
                <div class="drawer-box-title">Request Body (JSON or raw)</div>
                <textarea id="tryBody" class="code-textarea" rows="4" placeholder='{"title": "Hello"}'></textarea>
              </div>
            </div>

            <div id="tryResultBox" class="is-hidden mt-md">
              <div class="toolbar toolbar-between">
                <div class="toolbar-group" id="tryResultMeta"></div>
                <button type="button" class="btn btn-sm is-hidden" id="tryInspectTraceBtn">Inspect Captured Trace</button>
              </div>
              <div class="drawer-grid">
                <div class="drawer-box">
                  <div class="drawer-box-title">Response Headers (Scrubbed)</div>
                  <div id="tryResultHeaders"></div>
                </div>
                <div class="drawer-box">
                  <div class="drawer-box-title">Response Body (Scrubbed)</div>
                  <pre id="tryResultBody"></pre>
                </div>
              </div>
            </div>
          </div>
        </section>

        <section id="tab-graph" class="panel">
          <div class="card">
            <div class="toolbar toolbar-between">
              <span class="sub">Execution pipeline, container registrations, and compiled <code>.kgn.html</code> templates</span>
              <input id="graphSearch" class="search-input" type="search" placeholder="Filter middleware, services, views... (/)" aria-label="Filter graph">
            </div>
            <div class="graph-grid">
              <div class="drawer-box">
                <div class="drawer-box-title">Middleware Pipeline</div>
                <pre id="mwList"></pre>
              </div>
              <div class="drawer-box">
                <div class="drawer-box-title">Container Services</div>
                <pre id="svcList"></pre>
              </div>
              <div class="drawer-box">
                <div class="drawer-box-title">Compiled Views</div>
                <pre id="viewList"></pre>
              </div>
            </div>
          </div>
        </section>

        <section id="tab-ai" class="panel">
          <div class="card">
            <div class="toolbar toolbar-between ai-switcher-bar">
              <div class="filter-pills" id="aiModeTabs" role="tablist">
                <button type="button" class="chip active" data-ai-pane="oshie">{$oshieSvg}<span>Oshie</span></button>
                <button type="button" class="chip" data-ai-pane="chie">{$chieSvg}<span>Chie</span></button>
                <button type="button" class="chip" data-ai-pane="hantei">{$hanteiSvg}<span>Hantei</span></button>
                <button type="button" class="chip" data-ai-pane="mcp"><span>MCP Server</span></button>
              </div>
              <span class="sub" id="aiPaneHint">Interactive Kongono tutor grounded in your live app</span>
            </div>

            <div class="ai-pane active" id="aiPane-oshie">
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
                <button type="button" class="btn btn-primary" id="oshieSend">Ask Oshie</button>
              </div>
              <pre id="oshieOut">Pick a lesson or ask a question to study Kongono right inside Dev Studio.</pre>
            </div>

            <div class="ai-pane" id="aiPane-chie">
              <div class="toolbar">
                <input id="chieInput" class="ai-input" type="text" placeholder="Describe what to build (e.g. Add a /api/stats endpoint)...">
                <button type="button" class="btn btn-primary" id="chieSend">Preview Diff with Chie</button>
              </div>
              <pre id="chieOut">Chie previews unified diffs against your live ApplicationGraph without modifying disk from the browser. Run `kongo chie "..." --apply` in your terminal to write approved changes.</pre>
            </div>

            <div class="ai-pane" id="aiPane-hantei">
              <div class="toolbar">
                <input id="hanteiInput" class="ai-input" type="text" placeholder="Paste an error message or KGN-* diagnostic code...">
                <button type="button" class="btn btn-primary" id="hanteiSend">Diagnose with Hantei</button>
              </div>
              <pre id="hanteiOut">Hantei automatically scrubs secrets before diagnosing errors against your ApplicationGraph.</pre>
            </div>

            <div class="ai-pane" id="aiPane-mcp">
              <div class="toolbar">
                <button type="button" class="btn" data-mcp="kongono_prove_all">kongono_prove_all</button>
                <button type="button" class="btn" data-mcp="kongono_diagnose">kongono_diagnose</button>
                <button type="button" class="btn" data-mcp="kongono_routes">kongono_routes</button>
                <button type="button" class="btn" data-mcp="kongono_oshie_lesson">kongono_oshie_lesson</button>
                <button type="button" class="btn" data-mcp="kongono_graph">kongono_graph</button>
              </div>
              <pre id="mcpOut">Click any tool above to invoke your live Kongono MCP server (POST /mcp).</pre>
            </div>
          </div>
        </section>

        <section id="tab-runtime" class="panel">
          <div class="card">
            <div class="toolbar toolbar-between">
              <span class="sub">PHP 8.5 security baseline, loaded extensions, wired subsystems &amp; presence-only secret checks</span>
              <div class="toolbar-group">
                <button type="button" class="btn" id="copyRuntimeBtn">Copy Runtime Report</button>
                <button type="button" class="btn" id="refreshRuntimeBtn">Refresh</button>
              </div>
            </div>
            <div class="runtime-grid" id="runtimeStats"></div>
            <h2 class="card-title mt-md mb-sm">PHP Extensions</h2>
            <div class="ext-grid" id="runtimeExts"></div>
          </div>

          <div class="drawer-grid">
            <div class="card">
              <h2 class="card-title mb-sm">Wired Subsystems &amp; Studio Features</h2>
              <div class="table-wrap">
                <table>
                  <thead><tr><th>Subsystem</th><th>State</th><th>Details</th></tr></thead>
                  <tbody id="runtimeSubsystems"></tbody>
                </table>
              </div>
            </div>
            <div class="card">
              <h2 class="card-title mb-sm">Environment &amp; AI Providers</h2>
              <div class="table-wrap">
                <table>
                  <thead><tr><th>Variable</th><th>Status / Safe Value</th></tr></thead>
                  <tbody id="runtimeEnv"></tbody>
                </table>
              </div>
            </div>
          </div>
        </section>
      </div>
    </main>
  </div>

  <div class="modal-backdrop is-hidden" id="shortcutsModal" role="dialog" aria-modal="true" aria-label="Keyboard shortcuts">
    <div class="modal-card">
      <div class="toolbar toolbar-between mb-sm">
        <strong class="section-title">Keyboard Shortcuts</strong>
        <button type="button" class="btn btn-sm" id="closeShortcutsBtn">Close</button>
      </div>
      <div class="shortcut-list">
        <div><kbd>1</kbd>–<kbd>8</kbd><span>Switch workspaces in sidebar order</span></div>
        <div><kbd>/</kbd><span>Focus search in current workspace</span></div>
        <div><kbd>r</kbd><span>Refresh active workspace</span></div>
        <div><kbd>l</kbd><span>Toggle Live Tail auto-refresh</span></div>
        <div><kbd>p</kbd><span>Open Proof Center &amp; run all 5 proofs</span></div>
        <div><kbd>t</kbd><span>Toggle Light / Dark theme</span></div>
        <div><kbd>?</kbd><span>Show or hide this cheat sheet</span></div>
        <div><kbd>Esc</kbd><span>Close modal or open drawer</span></div>
      </div>
    </div>
  </div>

  <script type="application/json" id="kgn-studio-data">{$bootstrapJson}</script>
  <script src="{$safeBase}/assets/studio.js" defer></script>
</body>
</html>
HTML;
    }

    public function studioStylesheet(): string
    {
        return <<<'CSS'
:root {
  color-scheme: dark light;
  --bg: #0b0b0d;
  --sidebar: #101013;
  --surface: #131316;
  --surface-2: #1a1a1f;
  --border: rgba(255, 255, 255, 0.08);
  --border-strong: rgba(255, 255, 255, 0.16);
  --text: #f4f4f5;
  --text-2: #a1a1aa;
  --muted: #686873;
  --ok: #4ade80;
  --warn: #fbbf24;
  --err: #e5534b;
  font-family: ui-sans-serif, "Inter", system-ui, -apple-system, sans-serif;
  color: var(--text);
  background: var(--bg);
}
@media (prefers-color-scheme: light) {
  :root:not([data-theme="dark"]) {
    --bg: #fafafa;
    --sidebar: #f3f3f5;
    --surface: #ffffff;
    --surface-2: #ededf0;
    --border: rgba(0, 0, 0, 0.08);
    --border-strong: rgba(0, 0, 0, 0.16);
    --text: #09090b;
    --text-2: #52525b;
    --muted: #7e7e8a;
    --ok: #15803d;
    --warn: #b45309;
    --err: #d12e26;
  }
}
:root[data-theme="light"] {
  color-scheme: light;
  --bg: #fafafa;
  --sidebar: #f3f3f5;
  --surface: #ffffff;
  --surface-2: #ededf0;
  --border: rgba(0, 0, 0, 0.08);
  --border-strong: rgba(0, 0, 0, 0.16);
  --text: #09090b;
  --text-2: #52525b;
  --muted: #7e7e8a;
  --ok: #15803d;
  --warn: #b45309;
  --err: #d12e26;
}
:root[data-theme="dark"] {
  color-scheme: dark;
}
* { box-sizing: border-box; }
html, body { height: 100%; margin: 0; background: var(--bg); color: var(--text); -webkit-font-smoothing: antialiased; }
.is-hidden { display: none !important; }
.mt-0 { margin-top: 0; }
.mb-0 { margin-bottom: 0; }
.mb-sm { margin-bottom: 0.65rem; }
.mt-md { margin-top: 0.9rem; }
.muted { color: var(--muted); }
.text-sm { font-size: 0.82rem; }
.col-right { text-align: right; }
.section-title { font-size: 0.94rem; font-weight: 600; color: var(--text); }
.card-title { margin: 0; font-size: 0.92rem; font-weight: 600; color: var(--text); letter-spacing: -0.01em; }
.sub { color: var(--muted); font-size: 0.78rem; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }

/* Workspace Shell */
.studio-shell { display: grid; grid-template-columns: 232px minmax(0, 1fr); min-height: 100vh; }
.studio-sidebar {
  position: sticky;
  top: 0;
  height: 100vh;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  background: var(--sidebar);
  border-right: 1px solid var(--border);
  padding: 1.05rem 0.8rem 1rem;
}
.sidebar-brand { padding: 0.15rem 0.45rem 0.95rem; border-bottom: 1px solid var(--border); margin-bottom: 0.85rem; }
.brand-row { display: flex; align-items: center; gap: 0.55rem; }
.brand-text { min-width: 0; }
.brand-name { font-size: 0.88rem; font-weight: 620; letter-spacing: -0.02em; color: var(--text); line-height: 1.2; }
.brand-app { font-size: 0.74rem; color: var(--text-2); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 0.1rem; }
.brand-meta { margin-top: 0.55rem; font-size: 0.71rem; font-family: ui-monospace, Menlo, monospace; color: var(--muted); }

nav.tabs { display: flex; flex-direction: column; gap: 0.95rem; flex: 1; }
.nav-group { display: flex; flex-direction: column; gap: 0.15rem; }
.nav-group-label { font-size: 0.67rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; color: var(--muted); padding: 0 0.5rem 0.28rem; }
nav.tabs .nav-btn {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  background: transparent;
  color: var(--text-2);
  border: 1px solid transparent;
  padding: 0.44rem 0.55rem;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 500;
  font-size: 0.82rem;
  font-family: inherit;
  text-align: left;
  transition: background 110ms ease, color 110ms ease;
}
nav.tabs .nav-btn:hover { background: var(--surface-2); color: var(--text); }
nav.tabs .nav-btn.active { background: var(--text); color: var(--bg); font-weight: 560; }
.nav-label { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.nav-right { display: inline-flex; align-items: center; gap: 0.3rem; flex-shrink: 0; }
.nav-kbd { font-size: 0.67rem; font-family: ui-monospace, Menlo, monospace; color: var(--muted); opacity: 0.65; }
nav.tabs .nav-btn.active .nav-kbd { color: var(--bg); opacity: 0.65; }
.tab-count { display: inline-flex; align-items: center; justify-content: center; min-width: 1.25rem; padding: 0.05rem 0.36rem; border-radius: 999px; font-size: 0.68rem; font-family: ui-monospace, Menlo, monospace; font-weight: 600; background: var(--surface-2); color: var(--text-2); border: 1px solid var(--border); }
nav.tabs .nav-btn.active .tab-count { background: rgba(128, 128, 128, 0.25); color: var(--bg); border-color: transparent; }

.sidebar-footer { border-top: 1px solid var(--border); padding-top: 0.8rem; margin-top: 0.9rem; }
.sidebar-links { display: flex; flex-wrap: wrap; gap: 0.3rem 0.55rem; padding: 0 0.5rem; }
.sidebar-links a { font-size: 0.74rem; color: var(--muted); text-decoration: none; transition: color 100ms ease; }
.sidebar-links a:hover { color: var(--text); text-decoration: underline; text-underline-offset: 2px; }

/* Main Workspace */
.studio-main { min-width: 0; display: flex; flex-direction: column; }
.workspace-bar {
  position: sticky;
  top: 0;
  z-index: 30;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  flex-wrap: nowrap;
  min-height: 58px;
  padding: 0.62rem 1.5rem;
  background: color-mix(in srgb, var(--bg) 90%, transparent);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  border-bottom: 1px solid var(--border);
}
.workspace-heading { display: flex; flex-direction: column; gap: 0.1rem; min-width: 0; flex: 1 1 auto; }
.workspace-heading h1 { margin: 0; font-size: 0.98rem; font-weight: 620; letter-spacing: -0.02em; color: var(--text); line-height: 1.25; }
.workspace-sub { font-size: 0.74rem; color: var(--muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; line-height: 1.3; }
.workspace-actions { display: flex; align-items: center; gap: 0.45rem; flex-shrink: 0; }
.inline-telemetry { display: inline-flex; align-items: center; gap: 0.55rem; padding: 0.26rem 0.65rem; border-radius: 6px; background: var(--surface); border: 1px solid var(--border); font-family: ui-monospace, Menlo, monospace; font-size: 0.74rem; color: var(--text-2); white-space: nowrap; }
.tel-item strong { color: var(--text); font-weight: 600; }
.tel-item.tel-err strong { color: var(--err); }
.tel-item.tel-ok strong { color: var(--ok); }
.tel-sep { color: var(--border-strong); }

.workspace-body { width: 100%; max-width: 1180px; padding: 1.2rem 1.5rem 3.5rem; }
.pill { background: transparent; color: var(--text-2); text-decoration: none; padding: 0.3rem 0.62rem; border-radius: 6px; font-size: 0.76rem; font-weight: 500; border: 1px solid var(--border); transition: all 120ms ease; cursor: pointer; font-family: inherit; }
.pill:hover { border-color: var(--border-strong); background: var(--surface); color: var(--text); }
.pill-btn { line-height: 1.25; }

@media (max-width: 880px) {
  .studio-shell { grid-template-columns: 1fr; }
  .studio-sidebar { position: static; height: auto; border-right: 0; border-bottom: 1px solid var(--border); }
  nav.tabs { flex-direction: row; flex-wrap: wrap; gap: 0.35rem; }
  .nav-group { flex-direction: row; flex-wrap: wrap; gap: 0.25rem; }
  .nav-group-label, .nav-kbd { display: none; }
  nav.tabs .nav-btn { width: auto; padding: 0.36rem 0.62rem; }
  .workspace-body { padding: 1rem 1rem 3rem; }
  .workspace-bar { padding: 0.7rem 1rem; flex-wrap: wrap; }
}

.panel { display: none; }
.panel.active { display: block; }
.card { background: var(--surface); border: 1px solid var(--border); border-radius: 9px; padding: 1.05rem 1.15rem; margin-bottom: 1rem; }
.ai-input { flex: 1; min-width: 240px; padding: 0.44rem 0.7rem; border-radius: 6px; border: 1px solid var(--border-strong); background: var(--bg); color: var(--text); font: inherit; font-size: 0.83rem; }
.search-input { min-width: 200px; padding: 0.36rem 0.66rem; border-radius: 6px; border: 1px solid var(--border-strong); background: var(--bg); color: var(--text); font: inherit; font-size: 0.79rem; }
.search-wide { flex: 1; max-width: 380px; }
.table-wrap { overflow-x: auto; }
.trace-table-wrap { max-height: 295px; overflow-y: auto; }
.trace-table-wrap thead th { position: sticky; top: 0; background: var(--surface); z-index: 2; }
table { width: 100%; border-collapse: collapse; font-size: 0.81rem; }
th, td { text-align: left; padding: 0.5rem 0.56rem; border-bottom: 1px solid var(--border); vertical-align: middle; }
td code { overflow-wrap: anywhere; word-break: break-word; font-size: 0.76rem; }
th { color: var(--muted); font-weight: 500; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.06em; white-space: nowrap; }
tbody tr.trace-row { cursor: pointer; transition: background 100ms ease; }
tbody tr.trace-row:hover { background: var(--surface-2); }
tbody tr.trace-row.selected { background: var(--surface-2); }
code, pre { font-family: ui-monospace, "JetBrains Mono", SFMono-Regular, Menlo, monospace; }
pre { background: var(--bg); border: 1px solid var(--border); border-radius: 7px; padding: 0.85rem; overflow-x: auto; font-size: 0.78rem; color: var(--text-2); margin: 0.35rem 0 0; white-space: pre-wrap; word-break: break-word; }
.badge { display: inline-block; padding: 0.13rem 0.44rem; border-radius: 5px; font-size: 0.7rem; font-weight: 600; font-family: ui-monospace, Menlo, monospace; border: 1px solid var(--border); white-space: nowrap; }
.b-ok { background: rgba(74, 222, 128, 0.10); color: var(--ok); border-color: rgba(74, 222, 128, 0.25); }
.b-warn { background: rgba(251, 191, 36, 0.10); color: var(--warn); border-color: rgba(251, 191, 36, 0.25); }
.b-err { background: rgba(229, 83, 75, 0.12); color: var(--err); border-color: rgba(229, 83, 75, 0.28); }
.b-method { background: var(--surface-2); color: var(--text); border-color: var(--border-strong); }
.toolbar { display: flex; gap: 0.45rem; align-items: center; margin-bottom: 0.85rem; flex-wrap: wrap; }
.toolbar-between { justify-content: space-between; }
.toolbar-group { display: flex; gap: 0.38rem; align-items: center; flex-wrap: wrap; }
.btn { display: inline-flex; align-items: center; gap: 0.35rem; background: transparent; color: var(--text-2); border: 1px solid var(--border); padding: 0.36rem 0.7rem; border-radius: 6px; cursor: pointer; font-size: 0.78rem; font-weight: 500; font-family: inherit; transition: all 120ms ease; }
.btn:hover { background: var(--surface-2); border-color: var(--border-strong); color: var(--text); }
.btn:disabled { opacity: 0.5; cursor: not-allowed; }
.btn-sm { padding: 0.22rem 0.52rem; font-size: 0.72rem; }
.btn-primary { background: var(--text); color: var(--bg); border-color: var(--text); }
.btn-primary:hover { opacity: 0.9; background: var(--text); color: var(--bg); }
.btn-live-on { border-color: rgba(74, 222, 128, 0.4); color: var(--ok); background: rgba(74, 222, 128, 0.08); }
.live-dot { width: 7px; height: 7px; border-radius: 50%; background: var(--muted); display: inline-block; }
.btn-live-on .live-dot { background: var(--ok); box-shadow: 0 0 8px rgba(74, 222, 128, 0.75); }
.filter-pills { display: inline-flex; gap: 0.2rem; padding: 0.18rem; border-radius: 7px; background: var(--bg); border: 1px solid var(--border); }
.chip { display: inline-flex; align-items: center; gap: 0.35rem; background: transparent; color: var(--text-2); border: 1px solid transparent; padding: 0.24rem 0.56rem; border-radius: 5px; cursor: pointer; font-size: 0.75rem; font-weight: 500; font-family: inherit; }
.chip:hover { color: var(--text); }
.chip.active { background: var(--surface-2); color: var(--text); border-color: var(--border-strong); }
.actions-cell { display: inline-flex; gap: 0.35rem; align-items: center; justify-content: flex-end; }
.row-chevron { color: var(--muted); font-size: 0.85rem; padding-left: 0.2rem; }
.trace-path-meta { display: flex; align-items: center; gap: 0.35rem; flex-wrap: wrap; }
.trace-err-snippet { color: var(--err); font-size: 0.73rem; margin-top: 0.18rem; font-family: ui-monospace, Menlo, monospace; }

/* Symmetric Runtime 3x2 Stat Grid */
.runtime-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0.7rem; }
@media (max-width: 720px) { .runtime-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
.stat { background: var(--bg); border: 1px solid var(--border); border-radius: 8px; padding: 0.8rem 0.95rem; }
.stat .k { color: var(--muted); font-size: 0.69rem; font-family: ui-monospace, Menlo, monospace; text-transform: uppercase; letter-spacing: 0.06em; }
.stat .v { font-size: 1.22rem; font-weight: 600; letter-spacing: -0.02em; color: var(--text); margin-top: 0.2rem; }
.stat .v.v-err { color: var(--err); }
.stat .v.v-ok { color: var(--ok); }

/* Inspector & Route Drawers */
.drawer-head { border-bottom: 1px solid var(--border); padding-bottom: 0.75rem; }
.drawer-title-wrap { display: flex; flex-direction: column; gap: 0.15rem; }
.subtabs { display: flex; gap: 0.35rem; margin-bottom: 0.9rem; flex-wrap: wrap; }
.subtab { display: inline-flex; align-items: center; gap: 0.35rem; background: var(--bg); color: var(--text-2); border: 1px solid var(--border); padding: 0.32rem 0.66rem; border-radius: 6px; cursor: pointer; font-size: 0.77rem; font-weight: 500; font-family: inherit; }
.subtab.active { background: var(--surface-2); color: var(--text); border-color: var(--border-strong); }
.drawer-panel { display: none; }
.drawer-panel.active { display: block; }
.drawer-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 0.85rem; }
.graph-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 0.85rem; }
.drawer-box { background: var(--bg); border: 1px solid var(--border); border-radius: 7px; padding: 0.8rem 0.9rem; min-width: 0; }
.drawer-box-title { font-size: 0.71rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: var(--muted); margin-bottom: 0.45rem; }
.kv-table td { padding: 0.28rem 0.42rem; font-size: 0.75rem; }
.kv-key { color: var(--text-2); font-family: ui-monospace, Menlo, monospace; white-space: nowrap; }
.sql-row { display: flex; justify-content: space-between; align-items: baseline; gap: 0.75rem; padding: 0.46rem 0.55rem; border-bottom: 1px solid var(--border); font-size: 0.77rem; }
.sql-row:last-child { border-bottom: 0; }
.sql-ms { font-family: ui-monospace, Menlo, monospace; color: var(--muted); white-space: nowrap; }
.alert-banner { padding: 0.68rem 0.85rem; border-radius: 7px; border: 1px solid rgba(229, 83, 75, 0.32); background: rgba(229, 83, 75, 0.10); color: var(--err); font-size: 0.8rem; margin-bottom: 0.75rem; }
.warn-banner { padding: 0.68rem 0.85rem; border-radius: 7px; border: 1px solid rgba(251, 191, 36, 0.32); background: rgba(251, 191, 36, 0.10); color: var(--warn); font-size: 0.8rem; margin-bottom: 0.75rem; }

/* Proof Center */
.proof-banner { display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap; padding: 1.05rem 1.2rem; border-radius: 9px; background: var(--surface); border: 1px solid var(--border-strong); margin-bottom: 1rem; }
.proof-banner-left { display: flex; align-items: center; gap: 0.85rem; }
.proof-title { font-weight: 600; font-size: 0.96rem; }
.grade-badge { display: inline-flex; align-items: center; justify-content: center; width: 46px; height: 46px; border-radius: 10px; font-size: 1.2rem; font-weight: 700; font-family: ui-monospace, Menlo, monospace; border: 1px solid rgba(74, 222, 128, 0.3); background: rgba(74, 222, 128, 0.12); color: var(--ok); }
.grade-badge.fail { border-color: rgba(229, 83, 75, 0.35); background: rgba(229, 83, 75, 0.12); color: var(--err); }
.pillar-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 0.7rem; margin-bottom: 1rem; }
.pillar-card { background: var(--surface); border: 1px solid var(--border); border-radius: 9px; padding: 0.9rem 1rem; display: flex; flex-direction: column; gap: 0.38rem; }
.pillar-top { display: flex; justify-content: space-between; align-items: center; gap: 0.4rem; }
.pillar-name { font-size: 0.8rem; font-weight: 600; color: var(--text); }
.pillar-score { font-size: 1.25rem; font-weight: 600; font-family: ui-monospace, Menlo, monospace; }
.pillar-meta { font-size: 0.74rem; color: var(--text-2); line-height: 1.45; }
.findings-list { display: flex; flex-direction: column; gap: 0.65rem; }
.finding-card { background: var(--bg); border: 1px solid var(--border); border-radius: 8px; padding: 0.85rem 1rem; }
.finding-head { display: flex; justify-content: space-between; align-items: center; gap: 0.6rem; flex-wrap: wrap; margin-bottom: 0.5rem; }
.finding-meta { display: flex; align-items: center; gap: 0.4rem; flex-wrap: wrap; }
.finding-row { font-size: 0.8rem; margin: 0.25rem 0; line-height: 1.45; color: var(--text-2); }
.finding-row strong { color: var(--text); }
.cap-tags { display: flex; gap: 0.28rem; flex-wrap: wrap; }

/* Mailbox */
.mailbox-split { display: grid; grid-template-columns: 295px minmax(0, 1fr); gap: 1rem; align-items: start; }
@media (max-width: 820px) { .mailbox-split { grid-template-columns: 1fr; } }
.mail-item { width: 100%; text-align: left; background: var(--bg); border: 1px solid var(--border); border-radius: 7px; padding: 0.68rem 0.82rem; margin-bottom: 0.48rem; cursor: pointer; color: var(--text); font: inherit; transition: border-color 120ms ease; }
.mail-item:hover, .mail-item.active { border-color: var(--border-strong); background: var(--surface-2); }
.mail-subj { font-weight: 600; font-size: 0.83rem; margin-bottom: 0.2rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.mail-meta { font-size: 0.73rem; color: var(--text-2); font-family: ui-monospace, Menlo, monospace; margin-top: 0.16rem; }
.mail-meta-card { margin-bottom: 0.75rem; padding: 0.82rem 0.95rem; background: var(--bg); }
.mail-title { font-weight: 600; font-size: 0.93rem; margin-bottom: 0.32rem; }
.mail-atts { display: flex; flex-wrap: wrap; gap: 0.32rem; align-items: center; margin-top: 0.38rem; }
.token-strip { display: flex; flex-wrap: wrap; gap: 0.38rem; align-items: center; margin-bottom: 0.7rem; padding: 0.58rem 0.72rem; border-radius: 7px; background: var(--bg); border: 1px solid var(--border); }
.token-label { font-size: 0.7rem; font-family: ui-monospace, Menlo, monospace; color: var(--muted); text-transform: uppercase; letter-spacing: 0.05em; margin-right: 0.2rem; }
.mail-frame-wrap { display: flex; justify-content: center; background: var(--bg); border: 1px solid var(--border); border-radius: 7px; padding: 0.5rem; }
.mail-frame { width: 100%; min-height: 380px; border: 0; border-radius: 5px; background: #ffffff; transition: width 160ms ease; }
.mail-frame.mail-frame-mobile { width: 390px; max-width: 100%; border: 1px solid var(--border-strong); }
.empty-box { color: var(--muted); font-size: 0.82rem; padding: 1.4rem; border: 1px dashed var(--border); border-radius: 7px; text-align: center; }

/* Schema, AI, Try Route & Shortcuts */
.schema-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(290px, 1fr)); gap: 0.85rem; }
.schema-card { margin-bottom: 0; background: var(--bg); }
.schema-table-name { font-weight: 600; margin-bottom: 0.45rem; }
.ai-switcher-bar { border-bottom: 1px solid var(--border); padding-bottom: 0.75rem; }
.ai-pane { display: none; }
.ai-pane.active { display: block; }
.try-form-row { display: flex; gap: 0.45rem; align-items: center; flex-wrap: wrap; margin-bottom: 0.75rem; }
.try-method { font-family: ui-monospace, Menlo, monospace; font-weight: 600; }
.try-params { display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 0.75rem; padding: 0.58rem 0.72rem; border-radius: 7px; background: var(--bg); border: 1px solid var(--border); align-items: center; }
.try-param-field { display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.77rem; color: var(--text-2); }
.try-param-field input { width: 130px; padding: 0.26rem 0.48rem; border-radius: 5px; border: 1px solid var(--border-strong); background: var(--surface); color: var(--text); font-family: ui-monospace, Menlo, monospace; font-size: 0.77rem; }
.code-textarea { width: 100%; padding: 0.58rem 0.68rem; border-radius: 6px; border: 1px solid var(--border-strong); background: var(--surface); color: var(--text); font-family: ui-monospace, "JetBrains Mono", Menlo, monospace; font-size: 0.77rem; resize: vertical; line-height: 1.45; }
.ext-grid { display: flex; flex-wrap: wrap; gap: 0.42rem; }
.ext-chip { display: inline-flex; align-items: center; gap: 0.36rem; padding: 0.28rem 0.58rem; border-radius: 6px; background: var(--bg); border: 1px solid var(--border); font-family: ui-monospace, Menlo, monospace; font-size: 0.75rem; color: var(--text); }
.modal-backdrop { position: fixed; inset: 0; background: rgba(0, 0, 0, 0.62); display: flex; align-items: center; justify-content: center; padding: 1.25rem; z-index: 90; }
.modal-card { width: 100%; max-width: 450px; background: var(--surface); border: 1px solid var(--border-strong); border-radius: 10px; padding: 1.1rem 1.25rem; }
.shortcut-list { display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.82rem; color: var(--text-2); }
.shortcut-list > div { display: flex; align-items: center; gap: 0.55rem; }
kbd { display: inline-block; padding: 0.14rem 0.44rem; border-radius: 5px; font-family: ui-monospace, Menlo, monospace; font-size: 0.73rem; background: var(--bg); border: 1px solid var(--border-strong); color: var(--text); min-width: 1.55rem; text-align: center; }
CSS;
    }

    public function studioScript(): string
    {
        return <<<'JS'
(() => {
  const dataEl = document.getElementById('kgn-studio-data');
  const bootstrap = dataEl ? JSON.parse(dataEl.textContent || '{}') : {};
  const BASE = typeof bootstrap.base === 'string' ? bootstrap.base : '/_kongono';
  const GRAPH = bootstrap.graph || {};
  const ROUTE_TEMPLATES = bootstrap.route_templates || {};
  let traces = Array.isArray(bootstrap.traces) ? bootstrap.traces : [];

  const WORKSPACE_META = {
    requests: ['Live Requests', 'Captured HTTP traffic, SQL timing, replay & PHPUnit 13 export'],
    mailbox: ['Dev Mailbox', 'Local email capture with Desktop/Mobile preview & OTP/link detection'],
    runtime: ['Runtime & Env', 'PHP 8.5 readiness, extensions, wired subsystems & safe env checks'],
    proofs: ['Proof Center', '5 deterministic verification engines & Production Incident Time-Machine'],
    schema: ['Schema Explorer', 'Read-only live database tables, columns, types & indexes'],
    routes: ['Routes', 'Searchable endpoint map & inline Try Route request composer'],
    graph: ['App Graph', 'Middleware execution order, container services & compiled views'],
    ai: ['AI & MCP', 'Oshie tutor, Chie diff preview, Hantei debugger & live MCP tools'],
  };

  const AI_HINTS = {
    oshie: 'Interactive Kongono tutor grounded in your live app',
    chie: 'Safe unified diff previews without modifying files from the browser',
    hantei: 'Automatic secret-scrubbed error & trace diagnosis',
    mcp: 'Invoke live read-only JSON-RPC 2.0 tools on POST /mcp',
  };

  const root = document.documentElement;
  const themeBtn = document.getElementById('themeToggleBtn');
  const THEME_KEY = 'kongono-dev-theme';

  function applyTheme(theme) {
    if (theme === 'light' || theme === 'dark') {
      root.dataset.theme = theme;
    }
    const prefersLight = window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches;
    const active = root.dataset.theme || (prefersLight ? 'light' : 'dark');
    if (themeBtn) {
      themeBtn.textContent = active === 'dark' ? 'Light' : 'Dark';
    }
  }

  try {
    applyTheme(localStorage.getItem(THEME_KEY));
  } catch {
    applyTheme(null);
  }

  function toggleTheme() {
    const prefersLight = window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches;
    const current = root.dataset.theme || (prefersLight ? 'light' : 'dark');
    const next = current === 'dark' ? 'light' : 'dark';
    applyTheme(next);
    try { localStorage.setItem(THEME_KEY, next); } catch {}
  }
  if (themeBtn) themeBtn.addEventListener('click', toggleTheme);

  function esc(s) {
    return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
  }

  async function copyToClipboard(text, btn, resetLabel) {
    if (!text) return;
    try {
      await navigator.clipboard.writeText(text);
    } catch {
      const ta = document.createElement('textarea');
      ta.value = text;
      ta.className = 'is-hidden';
      document.body.appendChild(ta);
      ta.select();
      document.execCommand('copy');
      ta.remove();
    }
    if (btn) {
      const orig = resetLabel || btn.textContent;
      btn.textContent = 'Copied!';
      setTimeout(() => { btn.textContent = orig; }, 1300);
    }
  }

  let activeTab = 'requests';
  let activeTraceFilter = 'all';
  let selectedTraceId = null;
  let activeDrawerTab = 'overview';
  let liveTailTimer = null;
  let lastExportedTest = '';
  let lastProofReport = null;
  let proofsLoaded = false;
  let mailboxLoaded = false;
  let mailboxMessages = [];
  let selectedMailIndex = 0;
  let mailViewMode = 'html-desktop';
  let schemaLoaded = false;
  let schemaTables = [];
  let runtimeLoaded = false;
  let lastRuntimeData = null;
  let tryBasePathTemplate = '';
  let lastTryTraceId = null;

  function renderStats() {
    const alertCount = traces.reduce((acc, t) => acc + ((t.status >= 400 || t.error) ? 1 : 0) + ((t.n_plus_one && t.n_plus_one.length) ? 1 : 0), 0);
    const routeCount = (GRAPH.routes || []).length;
    const alertCls = alertCount > 0 ? 'tel-err' : 'tel-ok';
    document.getElementById('stats').innerHTML =
      '<span class="tel-item"><strong>' + routeCount + '</strong> routes</span>'
      + '<span class="tel-sep">&middot;</span>'
      + '<span class="tel-item"><strong>' + traces.length + '</strong> traces</span>'
      + '<span class="tel-sep">&middot;</span>'
      + '<span class="tel-item ' + alertCls + '"><strong>' + alertCount + '</strong> alerts</span>';

    const reqBadge = document.getElementById('countRequests');
    if (reqBadge) reqBadge.textContent = String(traces.length);
    const routeBadge = document.getElementById('countRoutes');
    if (routeBadge) routeBadge.textContent = String(routeCount);
  }

  function buildCurl(trace) {
    const origin = window.location.origin || 'http://127.0.0.1:8000';
    const url = origin + (trace.path || '/') + (trace.query ? '?' + trace.query : '');
    const parts = ['curl -i -X ' + String(trace.method || 'GET').toUpperCase(), "'" + url.replace(/'/g, "'\\''") + "'"];
    const hdrs = trace.headers || {};
    for (const [k, v] of Object.entries(hdrs)) {
      if (v === '[REDACTED]' || ['host', 'content-length'].includes(k.toLowerCase())) continue;
      parts.push("-H '" + (k + ': ' + v).replace(/'/g, "'\\''") + "'");
    }
    if (trace.body && trace.body !== '[MULTIPART BODY REDACTED]') {
      parts.push("--data-raw '" + String(trace.body).replace(/'/g, "'\\''") + "'");
    }
    return parts.join(' \\\n  ');
  }

  function filteredTraces() {
    const q = (document.getElementById('traceSearch')?.value || '').trim().toLowerCase();
    return traces.filter(t => {
      if (activeTraceFilter === 'errors' && !(t.status >= 400 || t.error)) return false;
      if (activeTraceFilter === 'n1' && !(t.n_plus_one && t.n_plus_one.length)) return false;
      if (activeTraceFilter === 'slow' && !(Number(t.duration_ms) > 100)) return false;
      if (!q) return true;
      const hay = [t.method, t.path, t.query, t.status, t.error || '', ...(t.queries || []).map(x => x.sql)].join(' ').toLowerCase();
      return hay.includes(q);
    });
  }

  function formatMemoryDelta(kb) {
    const n = Number(kb);
    if (!Number.isFinite(n)) return '—';
    const sign = n > 0 ? '+' : '';
    return sign + n.toFixed(1) + ' KB';
  }

  function renderTraces() {
    renderStats();
    const tb = document.getElementById('traceRows');
    const list = filteredTraces();
    if (!list.length) {
      tb.innerHTML = '<tr><td colspan="8" class="muted">' + (traces.length ? 'No traces match the current filter.' : 'No requests captured yet. Visit your app then click Refresh (or enable Live Tail).') + '</td></tr>';
      return;
    }
    tb.innerHTML = list.map(t => {
      const cls = t.status >= 500 ? 'b-err' : (t.status >= 400 ? 'b-warn' : 'b-ok');
      const durCls = Number(t.duration_ms) > 100 ? 'b-warn' : '';
      const queries = t.queries || [];
      const n1 = t.n_plus_one || [];
      const n1Badge = n1.length ? ' <span class="badge b-err">N+1 (' + n1.length + ')</span>' : '';
      const errSnippet = t.error ? '<div class="trace-err-snippet">' + esc(t.error) + '</div>' : '';
      const qCell = queries.length
        ? '<span class="badge ' + (n1.length ? 'b-err' : 'b-method') + '">' + queries.length + '</span>'
        : '<span class="muted">0</span>';
      const selectedCls = selectedTraceId === t.id ? ' selected' : '';
      return '<tr class="trace-row' + selectedCls + '" data-trace-row="' + t.id + '">'
        + '<td class="muted">' + t.id + '</td>'
        + '<td><span class="badge b-method">' + esc(t.method) + '</span></td>'
        + '<td><div class="trace-path-meta"><code>' + esc(t.path + (t.query ? '?' + t.query : '')) + '</code>' + n1Badge + '</div>' + errSnippet + '</td>'
        + '<td><span class="badge ' + cls + '">' + t.status + '</span></td>'
        + '<td>' + (durCls ? '<span class="badge ' + durCls + '">' + t.duration_ms + ' ms</span>' : (t.duration_ms + ' ms')) + '</td>'
        + '<td class="muted"><code>' + esc(formatMemoryDelta(t.memory_delta_kb)) + '</code></td>'
        + '<td>' + qCell + '</td>'
        + '<td class="col-right"><div class="actions-cell">'
        + '<button type="button" class="btn btn-sm" data-replay-trace="' + t.id + '">Replay</button>'
        + '<span class="row-chevron" aria-hidden="true">&#8250;</span>'
        + '</div></td></tr>';
    }).join('');

    tb.querySelectorAll('[data-trace-row]').forEach(row => {
      row.addEventListener('click', (e) => {
        if (e.target.closest('button')) return;
        openTraceDrawer(Number(row.dataset.traceRow), 'overview');
      });
    });
    tb.querySelectorAll('[data-replay-trace]').forEach(btn => {
      btn.addEventListener('click', () => {
        const id = Number(btn.dataset.replayTrace);
        openTraceDrawer(id, 'replay');
        runTraceReplay(id);
      });
    });
  }

  function setDrawerTab(tabName) {
    activeDrawerTab = tabName;
    document.querySelectorAll('#drawerTabs .subtab').forEach(b => b.classList.toggle('active', b.dataset.drawerTab === tabName));
    document.querySelectorAll('#traceDrawer .drawer-panel').forEach(p => p.classList.toggle('active', p.id === 'drawerPanel-' + tabName));
  }

  function renderKvTable(obj) {
    const entries = Object.entries(obj || {});
    if (!entries.length) return '<div class="muted text-sm">(none)</div>';
    return '<table class="kv-table"><tbody>' + entries.map(([k, v]) =>
      '<tr><td class="kv-key">' + esc(k) + '</td><td><code>' + esc(v) + '</code></td></tr>'
    ).join('') + '</tbody></table>';
  }

  function openTraceDrawer(traceId, targetSubtab) {
    const trace = traces.find(x => x.id === traceId);
    if (!trace) return;
    selectedTraceId = traceId;
    renderTraces();

    const drawer = document.getElementById('traceDrawer');
    drawer.classList.remove('is-hidden');
    document.getElementById('drawerTitle').textContent = 'Trace #' + trace.id + ' · ' + trace.method + ' ' + trace.path + (trace.query ? '?' + trace.query : '');
    document.getElementById('drawerSubtitle').textContent = 'HTTP ' + trace.status + ' · ' + trace.duration_ms + ' ms · ' + formatMemoryDelta(trace.memory_delta_kb) + ' · ' + (trace.timestamp || '');
    document.getElementById('drawerSqlCount').textContent = String((trace.queries || []).length);

    const errBanner = trace.error ? '<div class="alert-banner"><strong>Captured Exception:</strong> ' + esc(trace.error) + '</div>' : '';
    const overviewHtml = errBanner
      + '<div class="drawer-grid">'
      + '<div class="drawer-box"><div class="drawer-box-title">Request Headers (Scrubbed)</div>' + renderKvTable(trace.headers) + '</div>'
      + '<div class="drawer-box"><div class="drawer-box-title">Response Headers (Scrubbed)</div>' + renderKvTable(trace.response_headers) + '</div>'
      + '<div class="drawer-box"><div class="drawer-box-title">Request Payload &amp; Query</div>'
      + (trace.query ? '<div class="mail-meta mb-sm">Query: <code>' + esc(trace.query) + '</code></div>' : '')
      + '<pre>' + esc(trace.body || '(empty body)') + '</pre></div>'
      + '<div class="drawer-box"><div class="drawer-box-title">Response Preview (Scrubbed)</div><pre>' + esc(trace.response_body || '(not captured or binary response)') + '</pre></div>'
      + '</div>';
    document.getElementById('drawerPanel-overview').innerHTML = overviewHtml;

    const queries = trace.queries || [];
    const n1 = trace.n_plus_one || [];
    const n1Html = n1.length
      ? '<div class="warn-banner"><strong>N+1 Query Pattern Detected:</strong><br>' + n1.map(a => esc(a.count + 'x  ' + a.pattern)).join('<br>') + '</div>'
      : '';
    const totalSqlMs = queries.reduce((sum, q) => sum + Number(q.ms || 0), 0).toFixed(2);
    const sqlHtml = n1Html + (queries.length
      ? '<div class="sub mb-sm">' + queries.length + ' SQL quer' + (queries.length === 1 ? 'y' : 'ies') + ' · Total ' + totalSqlMs + ' ms</div><div class="drawer-box">'
        + queries.map((q, idx) => '<div class="sql-row"><code>#' + (idx + 1) + ' ' + esc(q.sql) + '</code><span class="sql-ms">' + esc(q.ms) + ' ms</span></div>').join('')
        + '</div>'
      : '<div class="empty-box">No SQL queries executed during this request.</div>');
    document.getElementById('drawerPanel-sql').innerHTML = sqlHtml;

    setDrawerTab(targetSubtab || activeDrawerTab || 'overview');
  }

  async function runTraceReplay(traceId) {
    const panel = document.getElementById('drawerPanel-replay');
    panel.innerHTML = '<div class="empty-box">Replaying trace #' + traceId + ' in-process...</div>';
    try {
      const res = await fetch(BASE + '/api/replay/' + encodeURIComponent(traceId), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Kongono-Dev': '1' },
        body: '{}',
      });
      const j = await res.json();
      if (j.error) {
        panel.innerHTML = '<div class="alert-banner">' + esc(j.error) + '</div>';
        return;
      }
      const matchCls = j.original_status === j.replay_status ? 'b-ok' : 'b-warn';
      panel.innerHTML = '<div class="toolbar toolbar-between">'
        + '<div class="toolbar-group"><span class="badge b-method">' + esc(j.method) + '</span> <code>' + esc(j.uri) + '</code>'
        + '<span class="badge ' + matchCls + '">HTTP ' + j.original_status + ' &rarr; ' + j.replay_status + '</span>'
        + '<span class="sub">' + j.duration_ms + ' ms</span></div></div>'
        + '<pre>' + esc(j.body || '(empty response body)') + '</pre>';
    } catch (err) {
      panel.innerHTML = '<div class="alert-banner">Replay failed: ' + esc(err.message) + '</div>';
    }
  }

  async function loadExportedTest(traceId) {
    const out = document.getElementById('exportedTestOut');
    out.textContent = 'Generating PHPUnit 13 regression test...';
    try {
      const res = await fetch(BASE + '/api/export-test/' + encodeURIComponent(traceId), {
        headers: { 'Accept': 'application/json' },
      });
      const j = await res.json();
      lastExportedTest = j.code || j.error || '';
      out.textContent = lastExportedTest;
    } catch (err) {
      out.textContent = 'Failed to export test: ' + err.message;
    }
  }

  document.querySelectorAll('#drawerTabs .subtab').forEach(btn => {
    btn.addEventListener('click', () => {
      const tab = btn.dataset.drawerTab;
      setDrawerTab(tab);
      if (tab === 'test' && selectedTraceId !== null) loadExportedTest(selectedTraceId);
    });
  });
  document.getElementById('drawerReplayBtn').addEventListener('click', () => {
    if (selectedTraceId !== null) {
      setDrawerTab('replay');
      runTraceReplay(selectedTraceId);
    }
  });
  document.getElementById('drawerCopyCurlBtn').addEventListener('click', () => {
    const trace = traces.find(x => x.id === selectedTraceId);
    if (trace) copyToClipboard(buildCurl(trace), document.getElementById('drawerCopyCurlBtn'), 'Copy cURL');
  });
  document.getElementById('drawerDiagnoseBtn').addEventListener('click', () => {
    if (selectedTraceId !== null) diagnoseTrace(selectedTraceId);
  });
  document.getElementById('drawerCloseBtn').addEventListener('click', () => {
    selectedTraceId = null;
    document.getElementById('traceDrawer').classList.add('is-hidden');
    renderTraces();
  });
  document.getElementById('copyExportedTestBtn').addEventListener('click', () => {
    copyToClipboard(lastExportedTest, document.getElementById('copyExportedTestBtn'), 'Copy Test');
  });

  document.querySelectorAll('#traceFilters .chip').forEach(btn => {
    btn.addEventListener('click', () => {
      activeTraceFilter = btn.dataset.traceFilter || 'all';
      document.querySelectorAll('#traceFilters .chip').forEach(c => c.classList.toggle('active', c === btn));
      renderTraces();
    });
  });
  document.getElementById('traceSearch').addEventListener('input', renderTraces);

  async function refreshTraces() {
    try {
      const res = await fetch(BASE + '/api/traces', { cache: 'no-store' });
      traces = await res.json();
      renderTraces();
    } catch {}
  }

  function toggleLiveTail() {
    const btn = document.getElementById('liveTailBtn');
    if (liveTailTimer !== null) {
      clearInterval(liveTailTimer);
      liveTailTimer = null;
      btn.classList.remove('btn-live-on');
      btn.setAttribute('aria-pressed', 'false');
      btn.innerHTML = '<span class="live-dot"></span>Live Tail: Off';
      return;
    }
    btn.classList.add('btn-live-on');
    btn.setAttribute('aria-pressed', 'true');
    btn.innerHTML = '<span class="live-dot"></span>Live Tail: On';
    refreshTraces();
    liveTailTimer = setInterval(() => {
      if (document.visibilityState === 'visible') refreshTraces();
    }, 2500);
  }
  document.getElementById('liveTailBtn').addEventListener('click', toggleLiveTail);

  function activateAiPane(paneName) {
    document.querySelectorAll('#aiModeTabs .chip').forEach(c => c.classList.toggle('active', c.dataset.aiPane === paneName));
    document.querySelectorAll('#tab-ai .ai-pane').forEach(p => p.classList.toggle('active', p.id === 'aiPane-' + paneName));
    const hint = document.getElementById('aiPaneHint');
    if (hint && AI_HINTS[paneName]) hint.textContent = AI_HINTS[paneName];
  }
  document.querySelectorAll('#aiModeTabs .chip').forEach(btn => {
    btn.addEventListener('click', () => activateAiPane(btn.dataset.aiPane));
  });

  function activateTab(tabName) {
    activeTab = tabName;
    document.querySelectorAll('nav.tabs button').forEach(b => b.classList.toggle('active', b.dataset.tab === tabName));
    document.querySelectorAll('.panel').forEach(p => p.classList.toggle('active', p.id === 'tab-' + tabName));
    const meta = WORKSPACE_META[tabName];
    if (meta) {
      const tEl = document.getElementById('workspaceTitle');
      const sEl = document.getElementById('workspaceSub');
      if (tEl) tEl.textContent = meta[0];
      if (sEl) sEl.textContent = meta[1];
    }
    if (tabName === 'proofs' && !proofsLoaded) {
      runAllProofs();
    } else if (tabName === 'mailbox' && !mailboxLoaded) {
      loadMailbox();
    } else if (tabName === 'schema' && !schemaLoaded) {
      loadSchema();
    } else if (tabName === 'runtime' && !runtimeLoaded) {
      loadRuntime();
    }
  }

  async function streamSsePost(url, payload, onDelta) {
    const res = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'text/event-stream', 'X-Kongono-Dev': '1' },
      body: JSON.stringify(payload),
    });
    if (res.status === 501 || !res.body) return null;
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
    activateAiPane('hantei');
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

  function renderRoutes() {
    const q = (document.getElementById('routeSearch')?.value || '').trim().toLowerCase();
    const allRoutes = GRAPH.routes || [];
    const rows = allRoutes
      .map((r, idx) => ({ route: r, idx }))
      .filter(({ route: r }) => {
        if (!q) return true;
        return [(r.methods || []).join('|'), r.path, r.name || '', r.handler || ''].join(' ').toLowerCase().includes(q);
      });
    const tb = document.getElementById('routeRows');
    tb.innerHTML = rows.length
      ? rows.map(({ route: r, idx }) => '<tr><td><span class="badge b-method">' + esc((r.methods || []).join('|')) + '</span></td><td><code>' + esc(r.path) + '</code></td><td>' + esc(r.name || '-') + '</td><td><code>' + esc(r.handler) + '</code></td><td class="col-right"><button type="button" class="btn btn-sm" data-try-route-idx="' + idx + '">Try</button></td></tr>').join('')
      : '<tr><td colspan="5" class="muted">No matching routes.</td></tr>';
    tb.querySelectorAll('[data-try-route-idx]').forEach(btn => {
      btn.addEventListener('click', () => {
        const route = allRoutes[Number(btn.dataset.tryRouteIdx)];
        if (route) openTryRouteDrawer(route);
      });
    });
  }
  document.getElementById('routeSearch').addEventListener('input', renderRoutes);

  function parseHeaderLines(raw) {
    const out = {};
    const trimmed = (raw || '').trim();
    if (!trimmed) return out;
    if (trimmed.startsWith('{')) {
      try {
        const parsed = JSON.parse(trimmed);
        if (parsed && typeof parsed === 'object') {
          for (const [k, v] of Object.entries(parsed)) {
            if (typeof k === 'string' && typeof v === 'string') out[k] = v;
          }
          return out;
        }
      } catch {}
    }
    for (const line of trimmed.split('\n')) {
      const idx = line.indexOf(':');
      if (idx <= 0) continue;
      const k = line.slice(0, idx).trim();
      const v = line.slice(idx + 1).trim();
      if (k) out[k] = v;
    }
    return out;
  }

  function updatePathFromParams() {
    if (!tryBasePathTemplate) return;
    let resolved = tryBasePathTemplate;
    document.querySelectorAll('#tryParamsRow [data-param-name]').forEach(inp => {
      const name = inp.dataset.paramName;
      const val = inp.value.trim() || '1';
      resolved = resolved.replace(new RegExp('\\{' + name + '(?::[^}]+)?\\}', 'g'), encodeURIComponent(val));
    });
    document.getElementById('tryPath').value = resolved;
  }

  function openTryRouteDrawer(route) {
    const methods = (route.methods || ['GET']).filter(m => m !== 'HEAD');
    const primaryMethod = methods[0] || 'GET';
    const rawPath = route.path || '/';
    const normalizedOpenApiPath = rawPath.replace(/\{([a-zA-Z_][a-zA-Z0-9_]*)(?::[^}]+)?\}/g, '{$1}');
    const tpl = ROUTE_TEMPLATES[primaryMethod + ' ' + normalizedOpenApiPath] || ROUTE_TEMPLATES[primaryMethod + ' ' + rawPath] || {};

    tryBasePathTemplate = rawPath;
    document.getElementById('tryMethod').value = primaryMethod;
    document.getElementById('tryRouteTitle').textContent = 'Try Route · ' + primaryMethod + ' ' + rawPath;
    document.getElementById('tryRouteSubtitle').textContent = route.handler ? ('Handler: ' + route.handler) : 'In-process route tester';
    document.getElementById('tryQuery').value = '';
    document.getElementById('tryHeaders').value = 'Accept: application/json';
    document.getElementById('tryBody').value = tpl.body ? JSON.stringify(tpl.body, null, 2) : '';
    document.getElementById('tryResultBox').classList.add('is-hidden');
    lastTryTraceId = null;

    const params = [];
    const paramRe = /\{([a-zA-Z_][a-zA-Z0-9_]*)(?::([^}]+))?\}/g;
    let pm;
    while ((pm = paramRe.exec(rawPath)) !== null) {
      params.push({ name: pm[1], isInt: pm[2] === '\\d+' });
    }
    const paramsRow = document.getElementById('tryParamsRow');
    if (params.length) {
      paramsRow.classList.remove('is-hidden');
      paramsRow.innerHTML = '<span class="token-label">Path Params</span>' + params.map(p =>
        '<label class="try-param-field"><code>{' + esc(p.name) + '}</code><input type="text" data-param-name="' + esc(p.name) + '" value="' + (p.isInt ? '1' : 'demo') + '"></label>'
      ).join('');
      paramsRow.querySelectorAll('[data-param-name]').forEach(inp => inp.addEventListener('input', updatePathFromParams));
      updatePathFromParams();
    } else {
      paramsRow.classList.add('is-hidden');
      paramsRow.innerHTML = '';
      document.getElementById('tryPath').value = rawPath;
    }

    const drawer = document.getElementById('tryRouteDrawer');
    drawer.classList.remove('is-hidden');
    drawer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  async function sendTryRoute() {
    const btn = document.getElementById('trySendBtn');
    const method = document.getElementById('tryMethod').value;
    const path = document.getElementById('tryPath').value.trim();
    const query = document.getElementById('tryQuery').value.trim();
    const headers = parseHeaderLines(document.getElementById('tryHeaders').value);
    const body = document.getElementById('tryBody').value;
    btn.disabled = true;
    btn.textContent = 'Sending...';
    try {
      const res = await fetch(BASE + '/api/try-route', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Kongono-Dev': '1' },
        body: JSON.stringify({ method, path, query, headers, body }),
      });
      const j = await res.json();
      const box = document.getElementById('tryResultBox');
      box.classList.remove('is-hidden');
      const status = Number(j.status || res.status);
      const stCls = status >= 500 ? 'b-err' : (status >= 400 ? 'b-warn' : 'b-ok');
      document.getElementById('tryResultMeta').innerHTML =
        '<span class="badge b-method">' + esc(j.method || method) + '</span>'
        + '<code>' + esc(j.uri || path) + '</code>'
        + '<span class="badge ' + stCls + '">HTTP ' + status + '</span>'
        + (j.duration_ms !== undefined ? '<span class="sub">' + esc(j.duration_ms) + ' ms</span>' : '')
        + (j.memory_delta_kb !== undefined ? '<span class="sub">' + esc(formatMemoryDelta(j.memory_delta_kb)) + '</span>' : '');

      lastTryTraceId = typeof j.trace_id === 'number' ? j.trace_id : null;
      const inspectBtn = document.getElementById('tryInspectTraceBtn');
      inspectBtn.classList.toggle('is-hidden', lastTryTraceId === null);
      if (lastTryTraceId !== null) {
        inspectBtn.textContent = 'Inspect Trace #' + lastTryTraceId;
      }

      document.getElementById('tryResultHeaders').innerHTML = renderKvTable(j.response_headers || {});
      let displayBody = j.error ? ('Error: ' + j.error) : (j.response_body || '(empty response body)');
      if (!j.error && displayBody && (displayBody.trim().startsWith('{') || displayBody.trim().startsWith('['))) {
        try { displayBody = JSON.stringify(JSON.parse(displayBody), null, 2); } catch {}
      }
      document.getElementById('tryResultBody').textContent = displayBody;
      refreshTraces();
    } finally {
      btn.disabled = false;
      btn.textContent = 'Send Request';
    }
  }

  document.getElementById('trySendBtn').addEventListener('click', sendTryRoute);
  document.getElementById('tryRouteCloseBtn').addEventListener('click', () => {
    document.getElementById('tryRouteDrawer').classList.add('is-hidden');
  });
  document.getElementById('tryRouteCurlBtn').addEventListener('click', () => {
    const pseudoTrace = {
      method: document.getElementById('tryMethod').value,
      path: document.getElementById('tryPath').value.trim() || '/',
      query: document.getElementById('tryQuery').value.trim(),
      headers: parseHeaderLines(document.getElementById('tryHeaders').value),
      body: document.getElementById('tryBody').value,
    };
    copyToClipboard(buildCurl(pseudoTrace), document.getElementById('tryRouteCurlBtn'), 'Copy cURL');
  });
  document.getElementById('tryInspectTraceBtn').addEventListener('click', async () => {
    if (lastTryTraceId === null) return;
    await refreshTraces();
    activateTab('requests');
    openTraceDrawer(lastTryTraceId, 'overview');
  });

  function renderRuntime(data) {
    lastRuntimeData = data;
    const php = data.php || {};
    document.getElementById('runtimeStats').innerHTML = [
      ['PHP Version', php.version || '?', php.php_8513_safe ? 'v-ok' : 'v-err'],
      ['SAPI', php.sapi || '?', ''],
      ['Memory / Peak', (php.memory_usage_mb ?? 0) + ' / ' + (php.memory_peak_mb ?? 0) + ' MB', ''],
      ['Memory Limit', php.memory_limit || '?', ''],
      ['OPcache', php.opcache_enabled ? 'Enabled' : 'Off', php.opcache_enabled ? 'v-ok' : ''],
      ['JIT', php.jit_enabled ? 'Enabled' : 'Off', php.jit_enabled ? 'v-ok' : ''],
    ].map(([k, v, cls]) => '<div class="stat"><div class="k">' + esc(k) + '</div><div class="v' + (cls ? ' ' + cls : '') + '">' + esc(v) + '</div></div>').join('');

    document.getElementById('runtimeExts').innerHTML = (data.extensions || []).map(ext =>
      '<span class="ext-chip"><span class="badge ' + (ext.loaded ? 'b-ok' : 'b-err') + '">' + (ext.loaded ? 'OK' : 'MISS') + '</span>' + esc(ext.name) + '</span>'
    ).join('');

    document.getElementById('runtimeSubsystems').innerHTML = (data.subsystems || []).map(s =>
      '<tr><td><strong>' + esc(s.name) + '</strong></td><td><span class="badge ' + (s.active ? 'b-ok' : 'b-method') + '">' + (s.active ? 'Active' : 'Off') + '</span></td><td class="muted">' + esc(s.detail || '') + '</td></tr>'
    ).join('');

    const envRows = [];
    for (const e of (data.safe_env || [])) {
      envRows.push('<tr><td><code>' + esc(e.key) + '</code></td><td>' + (e.value !== null ? '<code>' + esc(e.value) + '</code>' : '<span class="muted">(unset)</span>') + '</td></tr>');
    }
    for (const s of (data.secret_env || [])) {
      envRows.push('<tr><td><code>' + esc(s.key) + '</code></td><td><span class="badge ' + (s.configured ? 'b-ok' : 'b-method') + '">' + (s.configured ? 'Configured (hidden)' : 'Not set') + '</span></td></tr>');
    }
    document.getElementById('runtimeEnv').innerHTML = envRows.join('');
  }

  async function loadRuntime() {
    const r = await fetch(BASE + '/api/runtime', { cache: 'no-store' });
    const data = await r.json();
    runtimeLoaded = true;
    renderRuntime(data);
  }

  document.getElementById('refreshRuntimeBtn').addEventListener('click', loadRuntime);
  document.getElementById('copyRuntimeBtn').addEventListener('click', () => {
    if (!lastRuntimeData) return;
    const php = lastRuntimeData.php || {};
    const missingExts = (lastRuntimeData.extensions || []).filter(e => !e.loaded).map(e => e.name);
    const activeSubs = (lastRuntimeData.subsystems || []).filter(s => s.active).map(s => s.name);
    const configuredProviders = (lastRuntimeData.secret_env || []).filter(s => s.configured).map(s => s.key);
    const report = [
      '### Kongono Runtime Report (Scrubbed)',
      '- **PHP:** ' + (php.version || '?') + ' (' + (php.sapi || '?') + ', memory_limit=' + (php.memory_limit || '?') + ', OPcache=' + (php.opcache_enabled ? 'on' : 'off') + ', JIT=' + (php.jit_enabled ? 'on' : 'off') + ')',
      '- **Missing Extensions:** ' + (missingExts.length ? missingExts.join(', ') : 'none'),
      '- **Active Subsystems:** ' + (activeSubs.join(', ') || 'none'),
      '- **Configured Secrets/Providers (names only):** ' + (configuredProviders.join(', ') || 'none'),
    ].join('\n');
    copyToClipboard(report, document.getElementById('copyRuntimeBtn'), 'Copy Runtime Report');
  });

  function renderGraph() {
    const q = (document.getElementById('graphSearch')?.value || '').trim().toLowerCase();
    const match = (s) => !q || String(s).toLowerCase().includes(q);
    const mws = (GRAPH.middleware || []).filter(match);
    const svcs = (GRAPH.services || []).map(s => '[' + s.lifetime + '] ' + s.id).filter(match);
    const views = (GRAPH.views || []).filter(match);
    document.getElementById('mwList').textContent = mws.join('\n') || '(none)';
    document.getElementById('svcList').textContent = svcs.join('\n') || '(none)';
    document.getElementById('viewList').textContent = views.join('\n') || '(none)';
  }
  document.getElementById('graphSearch').addEventListener('input', renderGraph);

  function renderCapMatrix() {
    const q = (document.getElementById('capSearch')?.value || '').trim().toLowerCase();
    const caps = ((lastProofReport && lastProofReport.route_capabilities) || []).filter(c => {
      if (!q) return true;
      return [c.signature, c.handler, c.dto || '', ...(c.attributes || [])].join(' ').toLowerCase().includes(q);
    });
    document.getElementById('capMatrixRows').innerHTML = caps.length
      ? caps.map(c => {
          const tags = (c.attributes || []).length
            ? '<div class="cap-tags">' + c.attributes.map(a => '<span class="badge b-ok">#[' + esc(a) + ']</span>').join('') + '</div>'
            : '<span class="muted">public</span>';
          const dto = c.dto ? '<span class="badge b-method">' + esc(c.dto) + '</span>' : '<span class="muted">—</span>';
          return '<tr><td><code>' + esc(c.signature) + '</code></td><td><code>' + esc(c.handler) + '</code></td><td>' + tags + '</td><td>' + dto + '</td></tr>';
        }).join('')
      : '<tr><td colspan="4" class="muted">' + (lastProofReport ? 'No matching routes.' : 'Click "Run All 5 Proofs" to load the live capability & DTO matrix.') + '</td></tr>';
  }
  document.getElementById('capSearch').addEventListener('input', renderCapMatrix);

  function renderIncidentsList(incidents) {
    const tb = document.getElementById('incidentRows');
    if (!incidents || !incidents.length) {
      tb.innerHTML = '<tr><td colspan="5" class="muted">No recorded incidents yet. Trigger an error with <code>$app-&gt;recordIncidents()</code> enabled to replay it here.</td></tr>';
      return;
    }
    tb.innerHTML = incidents.map(inc => {
      const cls = inc.status_code >= 500 ? 'b-err' : 'b-warn';
      const code = (inc.explanation && inc.explanation.code) ? inc.explanation.code : 'KGN-ERR';
      return '<tr><td><code>' + esc(inc.reference) + '</code></td><td><span class="badge ' + cls + '">' + inc.status_code + '</span></td><td><span class="badge b-method">' + esc(inc.method) + '</span> <code>' + esc(inc.path) + '</code></td><td><code>' + esc(code) + '</code></td><td><div class="actions-cell"><button type="button" class="btn btn-sm" data-inc-replay="' + esc(inc.reference) + '">Replay</button><button type="button" class="btn btn-sm" data-inc-test="' + esc(inc.reference) + '">PHPUnit Test</button></div></td></tr>';
    }).join('');
    tb.querySelectorAll('[data-inc-replay]').forEach(b => {
      b.addEventListener('click', async () => {
        const out = document.getElementById('incidentOut');
        out.classList.remove('is-hidden');
        document.getElementById('copyIncidentOutBtn').classList.remove('is-hidden');
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
        out.classList.remove('is-hidden');
        document.getElementById('copyIncidentOutBtn').classList.remove('is-hidden');
        out.textContent = 'Generating PHPUnit 13 regression test...';
        const r = await fetch(BASE + '/api/incidents/' + encodeURIComponent(b.dataset.incTest) + '/test');
        const j = await r.json();
        out.textContent = j.error ? j.error : ('// ' + (j.relative_path || '') + '\n\n' + (j.content || ''));
      });
    });
  }

  document.getElementById('copyIncidentOutBtn').addEventListener('click', () => {
    copyToClipboard(document.getElementById('incidentOut').textContent || '', document.getElementById('copyIncidentOutBtn'), 'Copy Output');
  });

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
      const proofTabBadge = document.getElementById('countProofs');
      if (proofTabBadge) {
        proofTabBadge.textContent = report.grade || '';
        proofTabBadge.classList.remove('is-hidden');
      }
      document.getElementById('proofHeadline').textContent = 'Kongono Readiness Certificate — ' + report.overall_score + '/100 (' + (report.passed ? 'PASS' : 'FAILED') + ')';
      sub.textContent = 'Completed in ' + report.elapsed_ms + 'ms · ' + report.total_errors + ' error(s), ' + report.total_warnings + ' warning(s), ' + report.fixable_count + ' auto-fixable';

      const pOrder = ['diagnose', 'policy', 'isolation', 'perf', 'upgrade'];
      document.getElementById('pillarGrid').innerHTML = pOrder.map(key => {
        const p = report.pillars[key];
        if (!p) return '';
        const bCls = p.passed ? 'b-ok' : 'b-err';
        return '<div class="pillar-card"><div class="pillar-top"><span class="pillar-name">' + esc(p.label) + '</span><span class="badge ' + bCls + '">' + (p.passed ? 'PASS' : 'FAIL') + '</span></div><div class="pillar-score">' + p.score + '/100</div><div class="pillar-meta">' + esc(p.summary) + '</div></div>';
      }).join('');

      const structured = [];
      const rawLines = [];
      for (const k of ['diagnose', 'policy', 'isolation', 'perf']) {
        const list = (report.pillars[k] && report.pillars[k].findings) || [];
        for (const f of list) {
          structured.push({ pillar: k, finding: f });
          rawLines.push('[' + String(f.severity || 'error').toUpperCase() + '] ' + f.code + ' (' + f.where + ')\n  What: ' + f.what + '\n  Why:  ' + f.why + (f.how_to_fix && f.how_to_fix[0] ? '\n  Fix:  ' + f.how_to_fix[0] : ''));
        }
      }
      const fCard = document.getElementById('proofFindingsCard');
      const fList = document.getElementById('proofFindingsList');
      const fRaw = document.getElementById('proofFindingsOut');
      if (structured.length) {
        fCard.classList.remove('is-hidden');
        fRaw.textContent = rawLines.join('\n\n');
        fList.innerHTML = structured.map((entry, idx) => {
          const f = entry.finding;
          const sev = String(f.severity || 'error').toUpperCase();
          const sevCls = sev === 'ERROR' || sev === 'CRITICAL' ? 'b-err' : 'b-warn';
          const fixes = Array.isArray(f.how_to_fix) ? f.how_to_fix : [];
          return '<div class="finding-card"><div class="finding-head"><div class="finding-meta">'
            + '<span class="badge ' + sevCls + '">' + esc(sev) + '</span>'
            + '<span class="badge b-method">' + esc(f.code || 'KGN') + '</span>'
            + '<code>' + esc(f.where || '') + '</code>'
            + '</div>'
            + (fixes.length ? '<button type="button" class="btn btn-sm" data-copy-fix="' + idx + '">Copy Fix</button>' : '')
            + '</div>'
            + '<div class="finding-row"><strong>What:</strong> ' + esc(f.what || '') + '</div>'
            + '<div class="finding-row"><strong>Why:</strong> ' + esc(f.why || '') + '</div>'
            + (fixes.length ? '<div class="finding-row"><strong>How to Fix:</strong> ' + fixes.map(x => esc(x)).join(' &middot; ') + '</div>' : '')
            + '</div>';
        }).join('');
        fList.querySelectorAll('[data-copy-fix]').forEach(btn => {
          btn.addEventListener('click', () => {
            const entry = structured[Number(btn.dataset.copyFix)];
            const fixes = (entry && entry.finding && entry.finding.how_to_fix) || [];
            copyToClipboard(fixes.join('\n'), btn, 'Copy Fix');
          });
        });
      } else {
        fCard.classList.add('is-hidden');
      }

      renderCapMatrix();
      renderIncidentsList(report.incidents || []);
    } finally {
      btn.disabled = false;
    }
  }

  document.getElementById('toggleRawFindingsBtn').addEventListener('click', () => {
    document.getElementById('proofFindingsList').classList.toggle('is-hidden');
    document.getElementById('proofFindingsOut').classList.toggle('is-hidden');
  });
  document.getElementById('runProofsBtn').addEventListener('click', runAllProofs);
  document.getElementById('copyProofJsonBtn').addEventListener('click', () => {
    if (!lastProofReport) return;
    copyToClipboard(JSON.stringify(lastProofReport, null, 2), document.getElementById('copyProofJsonBtn'), 'Copy JSON');
  });
  document.getElementById('refreshIncidentsBtn').addEventListener('click', async () => {
    const r = await fetch(BASE + '/api/incidents');
    const j = await r.json();
    renderIncidentsList(j.incidents || []);
  });

  function extractEmailTokens(msg) {
    const raw = (msg.text || '') + '\n' + (msg.html || '').replace(/<[^>]+>/g, ' ');
    const urlSet = new Set();
    const urlRe = /https?:\/\/[^\s"'<>()]+/g;
    let m;
    while ((m = urlRe.exec((msg.text || '') + ' ' + (msg.html || ''))) !== null) {
      const cleaned = m[0].replace(/[.,;!?]+$/, '');
      if (urlSet.size < 4) urlSet.add(cleaned);
    }
    const codeSet = new Set();
    const codeRe = /\b(\d{6})\b/g;
    while ((m = codeRe.exec(raw)) !== null) {
      if (codeSet.size < 3) codeSet.add(m[1]);
    }
    return { urls: Array.from(urlSet), codes: Array.from(codeSet) };
  }

  function renderMailPreview(msg) {
    const box = document.getElementById('mailboxPreview');
    if (!msg) {
      box.innerHTML = '<div class="empty-box">No email selected.</div>';
      return;
    }
    const atts = Array.isArray(msg.attachments) ? msg.attachments : [];
    const attHtml = atts.length
      ? '<div class="mail-atts"><span class="mail-meta">Attachments:</span>'
        + atts.map(a => '<span class="badge b-method">' + esc(a.filename || 'file') + ' (' + esc(String(a.bytes ?? 0)) + ' B)</span>').join('')
        + '</div>'
      : '';
    const extraRecips = [
      (msg.cc && msg.cc.length) ? 'Cc: ' + msg.cc.join(', ') : '',
      (msg.bcc && msg.bcc.length) ? 'Bcc: ' + msg.bcc.join(', ') : '',
      msg.reply_to ? 'Reply-To: ' + msg.reply_to : '',
    ].filter(Boolean).join(' · ');

    const meta = '<div class="card mail-meta-card">'
      + '<div class="mail-title">' + esc(msg.subject || '(no subject)') + '</div>'
      + '<div class="mail-meta">From: ' + esc(msg.from || '—') + ' &middot; To: ' + esc((msg.to || []).join(', ') || '—') + '</div>'
      + (extraRecips ? '<div class="mail-meta">' + esc(extraRecips) + '</div>' : '')
      + '<div class="mail-meta">Sent: ' + esc(msg.sent_at || '') + ' &middot; ID: ' + esc(msg.id || '') + '</div>'
      + attHtml
      + '</div>';

    const tokens = extractEmailTokens(msg);
    const tokenChips = [];
    tokens.codes.forEach((code, idx) => {
      tokenChips.push('<button type="button" class="btn btn-sm" data-copy-token-code="' + idx + '">OTP: ' + esc(code) + '</button>');
    });
    tokens.urls.forEach((url, idx) => {
      const shortUrl = url.length > 46 ? url.slice(0, 43) + '...' : url;
      tokenChips.push('<button type="button" class="btn btn-sm" data-copy-token-url="' + idx + '">Copy Link: ' + esc(shortUrl) + '</button>');
    });
    const tokenStrip = tokenChips.length
      ? '<div class="token-strip"><span class="token-label">Detected</span>' + tokenChips.join('') + '</div>'
      : '';

    const hasHtml = Boolean(msg.html);
    if (!hasHtml && (mailViewMode === 'html-desktop' || mailViewMode === 'html-mobile')) {
      mailViewMode = 'text';
    }

    const modeBar = '<div class="toolbar toolbar-between">'
      + '<div class="filter-pills" id="mailViewModes">'
      + (hasHtml ? '<button type="button" class="chip' + (mailViewMode === 'html-desktop' ? ' active' : '') + '" data-mail-mode="html-desktop">HTML (Desktop)</button>' : '')
      + (hasHtml ? '<button type="button" class="chip' + (mailViewMode === 'html-mobile' ? ' active' : '') + '" data-mail-mode="html-mobile">Mobile (390px)</button>' : '')
      + '<button type="button" class="chip' + (mailViewMode === 'text' ? ' active' : '') + '" data-mail-mode="text">Plain Text</button>'
      + '<button type="button" class="chip' + (mailViewMode === 'raw' ? ' active' : '') + '" data-mail-mode="raw">Raw &amp; Headers</button>'
      + '</div>'
      + '<button type="button" class="btn btn-sm" id="copyMailBodyBtn">Copy Body</button>'
      + '</div>';

    let bodyHtml = '';
    if (hasHtml && (mailViewMode === 'html-desktop' || mailViewMode === 'html-mobile')) {
      const cls = mailViewMode === 'html-mobile' ? 'mail-frame mail-frame-mobile' : 'mail-frame';
      bodyHtml = '<div class="mail-frame-wrap"><iframe class="' + cls + '" sandbox="" id="mailHtmlFrame"></iframe></div>';
    } else if (mailViewMode === 'raw') {
      const headersDump = JSON.stringify({
        id: msg.id, sent_at: msg.sent_at, from: msg.from, to: msg.to, cc: msg.cc, bcc: msg.bcc, reply_to: msg.reply_to, subject: msg.subject, attachments: msg.attachments,
      }, null, 2);
      bodyHtml = '<div class="drawer-box-title">Headers &amp; Metadata</div><pre>' + esc(headersDump) + '</pre>'
        + (msg.html ? '<div class="drawer-box-title mt-md">Raw HTML Source</div><pre>' + esc(msg.html) + '</pre>' : '');
    } else {
      bodyHtml = '<pre>' + esc(msg.text || '(no plain-text body)') + '</pre>';
    }

    box.innerHTML = meta + tokenStrip + modeBar + bodyHtml;

    const frame = document.getElementById('mailHtmlFrame');
    if (frame && msg.html) frame.srcdoc = msg.html;

    box.querySelectorAll('[data-mail-mode]').forEach(btn => {
      btn.addEventListener('click', () => {
        mailViewMode = btn.dataset.mailMode;
        renderMailPreview(msg);
      });
    });
    box.querySelectorAll('[data-copy-token-code]').forEach(btn => {
      btn.addEventListener('click', () => {
        const code = tokens.codes[Number(btn.dataset.copyTokenCode)];
        copyToClipboard(code, btn, 'OTP: ' + code);
      });
    });
    box.querySelectorAll('[data-copy-token-url]').forEach(btn => {
      btn.addEventListener('click', () => {
        const url = tokens.urls[Number(btn.dataset.copyTokenUrl)];
        copyToClipboard(url, btn);
      });
    });
    const copyBodyBtn = document.getElementById('copyMailBodyBtn');
    if (copyBodyBtn) {
      copyBodyBtn.addEventListener('click', () => {
        copyToClipboard(mailViewMode === 'text' ? (msg.text || '') : (msg.html || msg.text || ''), copyBodyBtn, 'Copy Body');
      });
    }
  }

  function renderMailboxList() {
    const listEl = document.getElementById('mailboxList');
    const q = (document.getElementById('mailboxSearch')?.value || '').trim().toLowerCase();
    const filtered = mailboxMessages.filter(m => {
      if (!q) return true;
      return [m.subject, m.from, ...(m.to || []), m.text || ''].join(' ').toLowerCase().includes(q);
    });
    const badge = document.getElementById('countMailbox');
    if (badge) {
      badge.textContent = String(mailboxMessages.length);
      badge.classList.toggle('is-hidden', mailboxMessages.length === 0);
    }
    if (!filtered.length) {
      listEl.innerHTML = '<div class="muted">' + (mailboxMessages.length ? 'No captured emails match your search.' : 'No emails captured yet. Send mail using <code>DevMailboxTransport</code> to inspect it here.') + '</div>';
      renderMailPreview(null);
      return;
    }
    if (selectedMailIndex >= filtered.length) selectedMailIndex = 0;
    listEl.innerHTML = filtered.map((m, idx) => {
      const attCount = Array.isArray(m.attachments) ? m.attachments.length : 0;
      return '<button type="button" class="mail-item' + (idx === selectedMailIndex ? ' active' : '') + '" data-mail-idx="' + idx + '">'
        + '<div class="mail-subj">' + esc(m.subject || '(no subject)') + '</div>'
        + '<div class="mail-meta">To: ' + esc((m.to || []).join(', ')) + '</div>'
        + '<div class="mail-meta">' + esc(m.sent_at || '') + (attCount ? ' · ' + attCount + ' file' + (attCount === 1 ? '' : 's') : '') + '</div>'
        + '</button>';
    }).join('');
    listEl.querySelectorAll('[data-mail-idx]').forEach(btn => {
      btn.addEventListener('click', () => {
        selectedMailIndex = Number(btn.dataset.mailIdx);
        listEl.querySelectorAll('.mail-item').forEach(el => el.classList.remove('active'));
        btn.classList.add('active');
        renderMailPreview(filtered[selectedMailIndex]);
      });
    });
    renderMailPreview(filtered[selectedMailIndex]);
  }

  async function loadMailbox() {
    const listEl = document.getElementById('mailboxList');
    listEl.textContent = 'Loading captured emails...';
    const r = await fetch(BASE + '/api/mailbox', { cache: 'no-store' });
    const j = await r.json();
    mailboxMessages = j.messages || [];
    mailboxLoaded = true;
    selectedMailIndex = 0;
    renderMailboxList();
  }
  document.getElementById('mailboxSearch').addEventListener('input', renderMailboxList);

  function renderSchema() {
    const grid = document.getElementById('schemaGrid');
    const q = (document.getElementById('schemaSearch')?.value || '').trim().toLowerCase();
    const list = schemaTables.filter(t => {
      if (!q) return true;
      return [t.name, ...(t.columns || []).map(c => c.name + ' ' + (c.type || ''))].join(' ').toLowerCase().includes(q);
    });
    if (!list.length) {
      grid.innerHTML = '<div class="muted">' + (schemaTables.length ? 'No tables match your filter.' : 'No database tables found yet — run <code>kongo migrate</code>.') + '</div>';
      return;
    }
    grid.innerHTML = list.map(t => {
      const cols = (t.columns || []).map(c =>
        '<tr><td><code>' + esc(c.name) + '</code>' + (c.primary ? ' <span class="badge b-method">PK</span>' : '') + '</td><td><code>' + esc(c.type || '') + '</code></td><td>' + (c.nullable ? 'NULL' : 'NOT NULL') + '</td></tr>'
      ).join('');
      const idxHtml = (t.indexes && t.indexes.length)
        ? '<div class="mail-meta mt-md">Indexes: ' + t.indexes.map(i => '<code>' + esc(i) + '</code>').join(', ') + '</div>'
        : '';
      return '<div class="card schema-card"><div class="schema-table-name"><code>' + esc(t.name) + '</code></div><table><thead><tr><th>Column</th><th>Type</th><th>Null</th></tr></thead><tbody>' + cols + '</tbody></table>' + idxHtml + '</div>';
    }).join('');
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
    schemaTables = Array.isArray(rawTables)
      ? rawTables
      : Object.entries(rawTables).map(([name, info]) => ({
          name,
          columns: (info && info.columns) || [],
          indexes: (info && info.indexes) || [],
        }));
    if (!connected || !schemaTables.length) {
      meta.textContent = connected ? ('Connected (' + (j.dialect || 'sql') + ') · 0 tables') : 'No ConnectionInterface registered in container';
      grid.innerHTML = '<div class="muted">' + (connected ? 'No database tables found yet — run <code>kongo migrate</code>.' : 'Bind a <code>ConnectionInterface</code> in <code>app/bootstrap.php</code> to inspect your live schema here.') + '</div>';
      return;
    }
    meta.textContent = 'Dialect: ' + (j.dialect || 'sql') + ' · ' + schemaTables.length + ' table(s)';
    renderSchema();
  }
  document.getElementById('schemaSearch').addEventListener('input', renderSchema);

  document.getElementById('refreshMailboxBtn').addEventListener('click', loadMailbox);
  document.getElementById('clearMailboxBtn').addEventListener('click', async () => {
    await fetch(BASE + '/api/mailbox/clear', { method: 'POST', headers: { 'X-Kongono-Dev': '1' } });
    mailboxMessages = [];
    loadMailbox();
  });
  document.getElementById('refreshSchemaBtn').addEventListener('click', loadSchema);

  document.querySelectorAll('nav.tabs button').forEach(btn => {
    btn.addEventListener('click', () => activateTab(btn.dataset.tab));
  });

  document.getElementById('refreshBtn').addEventListener('click', refreshTraces);
  document.getElementById('clearBtn').addEventListener('click', async () => {
    await fetch(BASE + '/api/clear', { method: 'POST', headers: { 'X-Kongono-Dev': '1' } });
    traces = [];
    selectedTraceId = null;
    document.getElementById('traceDrawer').classList.add('is-hidden');
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

  const shortcutsModal = document.getElementById('shortcutsModal');
  function toggleShortcutsModal(force) {
    if (!shortcutsModal) return;
    if (typeof force === 'boolean') {
      shortcutsModal.classList.toggle('is-hidden', !force);
    } else {
      shortcutsModal.classList.toggle('is-hidden');
    }
  }
  document.getElementById('shortcutsBtn').addEventListener('click', () => toggleShortcutsModal());
  document.getElementById('closeShortcutsBtn').addEventListener('click', () => toggleShortcutsModal(false));
  shortcutsModal.addEventListener('click', (e) => {
    if (e.target === shortcutsModal) toggleShortcutsModal(false);
  });

  const tabOrder = ['requests', 'mailbox', 'runtime', 'proofs', 'schema', 'routes', 'graph', 'ai'];
  const searchByTab = {
    requests: 'traceSearch',
    proofs: 'capSearch',
    mailbox: 'mailboxSearch',
    schema: 'schemaSearch',
    routes: 'routeSearch',
    graph: 'graphSearch',
  };

  document.addEventListener('keydown', (e) => {
    if (e.metaKey || e.ctrlKey || e.altKey) return;
    if (e.key === 'Escape') {
      if (!shortcutsModal.classList.contains('is-hidden')) {
        toggleShortcutsModal(false);
      } else if (!document.getElementById('tryRouteDrawer').classList.contains('is-hidden')) {
        document.getElementById('tryRouteDrawer').classList.add('is-hidden');
      } else if (selectedTraceId !== null) {
        selectedTraceId = null;
        document.getElementById('traceDrawer').classList.add('is-hidden');
        renderTraces();
      }
      return;
    }
    const tag = (e.target && e.target.tagName) ? e.target.tagName.toLowerCase() : '';
    if (tag === 'input' || tag === 'textarea' || tag === 'select') return;
    if (e.key >= '1' && e.key <= '8') {
      e.preventDefault();
      activateTab(tabOrder[Number(e.key) - 1]);
    } else if (e.key === '/') {
      const searchId = searchByTab[activeTab];
      const input = searchId ? document.getElementById(searchId) : null;
      if (input) {
        e.preventDefault();
        input.focus();
      }
    } else if (e.key === 'r') {
      e.preventDefault();
      if (activeTab === 'requests') refreshTraces();
      else if (activeTab === 'proofs') runAllProofs();
      else if (activeTab === 'mailbox') loadMailbox();
      else if (activeTab === 'schema') loadSchema();
      else if (activeTab === 'runtime') loadRuntime();
    } else if (e.key === 'l') {
      e.preventDefault();
      activateTab('requests');
      toggleLiveTail();
    } else if (e.key === 'p') {
      e.preventDefault();
      activateTab('proofs');
      runAllProofs();
    } else if (e.key === 't') {
      e.preventDefault();
      toggleTheme();
    } else if (e.key === '?') {
      e.preventDefault();
      toggleShortcutsModal();
    }
  });

  renderTraces();
  renderRoutes();
  renderGraph();

  // Deep-linking from Floating Dev HUD (`/_kongono#trace=12` or `/_kongono#tab=runtime`)
  (async () => {
    const hash = (window.location.hash || '').replace(/^#/, '');
    if (hash.startsWith('trace=')) {
      const targetId = Number(hash.slice(6));
      if (Number.isFinite(targetId) && targetId > 0) {
        activateTab('requests');
        if (!traces.some(x => x.id === targetId)) {
          await refreshTraces();
        }
        openTraceDrawer(targetId, 'overview');
      }
    } else if (hash.startsWith('tab=')) {
      const targetTab = hash.slice(4);
      if (tabOrder.includes(targetTab)) {
        activateTab(targetTab);
      }
    }
  })();
})();
JS;
    }

    public function devHudStylesheet(): string
    {
        return <<<'CSS'
.kgn-hud {
  position: fixed;
  right: 16px;
  bottom: 16px;
  z-index: 2147483640;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 6px 10px;
  border-radius: 999px;
  background: rgba(15, 16, 20, 0.94);
  color: #f4f4f5;
  border: 1px solid rgba(255, 255, 255, 0.14);
  box-shadow: 0 10px 28px rgba(0, 0, 0, 0.42);
  font-family: ui-monospace, "JetBrains Mono", SFMono-Regular, Menlo, monospace;
  font-size: 12px;
  line-height: 1.2;
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
}
.kgn-hud.kgn-hud-light {
  background: rgba(252, 252, 252, 0.95);
  color: #09090b;
  border-color: rgba(0, 0, 0, 0.14);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.14);
}
.kgn-hud-brand {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 20px;
  height: 20px;
  border-radius: 5px;
  background: linear-gradient(135deg, #4a4f5c, #21242c);
  color: #f8fafc;
  font-weight: 700;
  font-size: 11px;
  border: 0;
  cursor: pointer;
  padding: 0;
}
.kgn-hud-sep {
  color: rgba(161, 161, 170, 0.45);
}
.kgn-hud-badge {
  display: inline-block;
  padding: 2px 6px;
  border-radius: 4px;
  font-weight: 600;
  font-size: 11px;
}
.kgn-hud-ok {
  background: rgba(74, 222, 128, 0.14);
  color: #4ade80;
}
.kgn-hud-warn {
  background: rgba(251, 191, 36, 0.16);
  color: #fbbf24;
}
.kgn-hud-err {
  background: rgba(229, 83, 75, 0.18);
  color: #e5534b;
}
.kgn-hud-light .kgn-hud-ok { color: #15803d; }
.kgn-hud-light .kgn-hud-warn { color: #b45309; }
.kgn-hud-light .kgn-hud-err { color: #d12e26; }
.kgn-hud-metric {
  color: #a1a1aa;
  white-space: nowrap;
}
.kgn-hud-light .kgn-hud-metric {
  color: #52525b;
}
.kgn-hud-link {
  color: #f4f4f5;
  text-decoration: none;
  padding: 2px 7px;
  border-radius: 5px;
  border: 1px solid rgba(255, 255, 255, 0.16);
  font-weight: 600;
  font-size: 11px;
  white-space: nowrap;
}
.kgn-hud-link:hover {
  background: rgba(255, 255, 255, 0.1);
}
.kgn-hud-light .kgn-hud-link {
  color: #09090b;
  border-color: rgba(0, 0, 0, 0.16);
}
.kgn-hud-light .kgn-hud-link:hover {
  background: rgba(0, 0, 0, 0.06);
}
.kgn-hud-toggle {
  background: transparent;
  border: 0;
  color: #a1a1aa;
  cursor: pointer;
  font-size: 13px;
  padding: 0 2px;
  line-height: 1;
}
.kgn-hud-toggle:hover {
  color: #f4f4f5;
}
.kgn-hud-light .kgn-hud-toggle:hover {
  color: #09090b;
}
.kgn-hud.kgn-hud-collapsed .kgn-hud-detail {
  display: none;
}
CSS;
    }

    public function devHudScript(): string
    {
        return <<<'JS'
(() => {
  if (document.getElementById('kgn-dev-hud')) return;
  const script = document.currentScript || document.querySelector('script[src*="/assets/dev-hud.js"]');
  if (!script) return;
  const ds = script.dataset || {};
  const base = (ds.base || '/_kongono').replace(/\/+$/, '');
  const traceId = Number(ds.traceId || 0);
  const status = Number(ds.status || 200);
  const ms = ds.ms || '0';
  const mem = Number(ds.mem || 0);
  const sql = Number(ds.sql || 0);
  const n1 = Number(ds.n1 || 0);

  const hud = document.createElement('aside');
  hud.id = 'kgn-dev-hud';
  hud.className = 'kgn-hud';
  hud.setAttribute('aria-label', 'Kongono Dev HUD');

  const STORAGE_KEY = 'kongono-dev-hud-collapsed';
  const THEME_KEY = 'kongono-dev-theme';
  try {
    const savedTheme = localStorage.getItem(THEME_KEY);
    const prefersLight = window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches;
    if (savedTheme === 'light' || (!savedTheme && prefersLight)) {
      hud.classList.add('kgn-hud-light');
    }
    if (localStorage.getItem(STORAGE_KEY) === '1') {
      hud.classList.add('kgn-hud-collapsed');
    }
  } catch {}

  const brandBtn = document.createElement('button');
  brandBtn.type = 'button';
  brandBtn.className = 'kgn-hud-brand';
  brandBtn.textContent = 'K';
  brandBtn.title = 'Toggle Kongono Dev HUD';

  const statusSpan = document.createElement('span');
  const stCls = status >= 500 ? 'kgn-hud-err' : (status >= 400 ? 'kgn-hud-warn' : 'kgn-hud-ok');
  statusSpan.className = 'kgn-hud-badge kgn-hud-detail ' + stCls;
  statusSpan.textContent = String(status);

  const msSpan = document.createElement('span');
  msSpan.className = 'kgn-hud-metric kgn-hud-detail';
  msSpan.textContent = ms + ' ms';

  const memSpan = document.createElement('span');
  memSpan.className = 'kgn-hud-metric kgn-hud-detail';
  memSpan.textContent = (mem > 0 ? '+' : '') + mem.toFixed(1) + ' KB';

  const sqlSpan = document.createElement('span');
  sqlSpan.className = (n1 > 0 ? 'kgn-hud-badge kgn-hud-err' : 'kgn-hud-metric') + ' kgn-hud-detail';
  sqlSpan.textContent = sql + ' SQL' + (n1 > 0 ? ' · ' + n1 + ' N+1' : '');

  const link = document.createElement('a');
  link.className = 'kgn-hud-link kgn-hud-detail';
  link.href = traceId > 0 ? (base + '#trace=' + encodeURIComponent(String(traceId))) : base;
  link.target = '_blank';
  link.rel = 'noopener';
  link.textContent = 'Studio ↗';

  const closeBtn = document.createElement('button');
  closeBtn.type = 'button';
  closeBtn.className = 'kgn-hud-toggle kgn-hud-detail';
  closeBtn.textContent = '×';
  closeBtn.title = 'Collapse Dev HUD';

  function toggleCollapsed() {
    const collapsed = hud.classList.toggle('kgn-hud-collapsed');
    try { localStorage.setItem(STORAGE_KEY, collapsed ? '1' : '0'); } catch {}
  }
  brandBtn.addEventListener('click', toggleCollapsed);
  closeBtn.addEventListener('click', toggleCollapsed);

  hud.appendChild(brandBtn);
  hud.appendChild(statusSpan);
  hud.appendChild(msSpan);
  hud.appendChild(memSpan);
  hud.appendChild(sqlSpan);
  hud.appendChild(link);
  hud.appendChild(closeBtn);
  document.body.appendChild(hud);
})();
JS;
    }

    /**
     * Build lightweight per-route parameter and DTO JSON sample templates using OpenApiGenerator.
     *
     * @return array<string, array{body: mixed}>
     */
    private function buildRouteTemplates(App $app): array
    {
        try {
            $spec = (new OpenApiGenerator())->generate($app);
        } catch (Throwable) {
            return [];
        }

        $paths = is_array($spec['paths'] ?? null) ? $spec['paths'] : [];
        $components = is_array($spec['components'] ?? null) ? $spec['components'] : [];
        $schemas = is_array($components['schemas'] ?? null) ? $components['schemas'] : [];
        $templates = [];

        foreach ($paths as $openApiPath => $operations) {
            if (!is_string($openApiPath) || !is_array($operations)) {
                continue;
            }
            foreach ($operations as $method => $op) {
                if (!is_string($method) || !is_array($op)) {
                    continue;
                }
                $reqBody = is_array($op['requestBody'] ?? null) ? $op['requestBody'] : null;
                $content = is_array($reqBody['content'] ?? null) ? $reqBody['content'] : null;
                $jsonContent = is_array($content['application/json'] ?? null) ? $content['application/json'] : null;
                $schema = is_array($jsonContent['schema'] ?? null) ? $jsonContent['schema'] : null;
                if (null === $schema) {
                    continue;
                }
                $sample = $this->sampleFromSchema($schema, $schemas, 0);
                if (null !== $sample) {
                    $templates[strtoupper($method) . ' ' . $openApiPath] = ['body' => $sample];
                }
            }
        }

        return $templates;
    }

    /**
     * @param array<array-key, mixed> $schema
     * @param array<array-key, mixed> $schemas
     */
    private function sampleFromSchema(array $schema, array $schemas, int $depth): mixed
    {
        if ($depth > 4) {
            return null;
        }
        if (is_string($schema['$ref'] ?? null)) {
            $ref = $schema['$ref'];
            $prefix = '#/components/schemas/';
            if (str_starts_with($ref, $prefix)) {
                $name = substr($ref, strlen($prefix));
                if (isset($schemas[$name]) && is_array($schemas[$name])) {
                    return $this->sampleFromSchema($schemas[$name], $schemas, $depth + 1);
                }
            }

            return [];
        }
        if (is_array($schema['enum'] ?? null) && [] !== $schema['enum']) {
            return $schema['enum'][0];
        }
        $type = $schema['type'] ?? null;
        if (is_array($type)) {
            $nonNull = array_values(array_filter($type, static fn(mixed $t): bool => is_string($t) && 'null' !== $t));
            $type = $nonNull[0] ?? 'string';
        }
        if ('object' === $type || is_array($schema['properties'] ?? null)) {
            $props = is_array($schema['properties'] ?? null) ? $schema['properties'] : [];
            $obj = [];
            foreach ($props as $propName => $propSchema) {
                if (is_string($propName) && is_array($propSchema)) {
                    $obj[$propName] = $this->sampleFromSchema($propSchema, $schemas, $depth + 1);
                }
            }

            return $obj;
        }
        if ('array' === $type) {
            $items = is_array($schema['items'] ?? null) ? $schema['items'] : null;

            return null !== $items ? [$this->sampleFromSchema($items, $schemas, $depth + 1)] : [];
        }
        if ('integer' === $type) {
            return is_int($schema['minimum'] ?? null) ? $schema['minimum'] : 1;
        }
        if ('number' === $type) {
            return 1.0;
        }
        if ('boolean' === $type) {
            return true;
        }
        $format = is_string($schema['format'] ?? null) ? $schema['format'] : '';

        return match ($format) {
            'email' => 'dev@example.com',
            'uuid' => '00000000-0000-4000-8000-000000000000',
            'date' => '2026-01-01',
            'date-time' => '2026-01-01T00:00:00Z',
            'uri', 'url' => 'https://example.com',
            'ipv4' => '203.0.113.1',
            default => '',
        };
    }
}
