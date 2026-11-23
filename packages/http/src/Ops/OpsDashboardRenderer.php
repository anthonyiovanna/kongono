<?php

declare(strict_types=1);

namespace Kongono\Http\Ops;

use Kongono\Core\Diagnostics\SecretScrubber;
use Kongono\Http\Brand\KongonoBrand;

/**
 * Zero-npm, zero-eval, strict-CSP HTML & Kongono Live 2.5 fragment renderer for OpsDashboard.
 *
 * @phpstan-type OpsSnapshot array{
 *     app: string,
 *     timestamp: string,
 *     health: array{status: string, healthy: bool, checks: array<string, array{ok: bool, duration_ms: float, error?: string}>}|null,
 *     queues: array<string, array{pending: int, reserved: int, completed: int, failed: int, dead: int}>,
 *     failed_jobs: list<array{id: string, queue: string, type: string, status: string, attempt: int, max_attempts: int, last_error: ?string}>,
 *     batches: list<array{id: string, name: string, total_jobs: int, pending_jobs: int, processed_jobs: int, failed_jobs: int, cancelled: bool, progress: float}>,
 *     metrics: array{
 *         counters: list<array{name: string, labels: array<string, string>, value: float}>,
 *         gauges: list<array{name: string, labels: array<string, string>, value: float}>,
 *         timings: list<array{name: string, labels: array<string, string>, count: int, sum: float, max: float, avg: float}>
 *     }|null,
 *     recent_traces: list<array{id: int, method: string, path: string, status: int, duration_ms: float}>
 * }
 */
final readonly class OpsDashboardRenderer
{
    /** @param OpsSnapshot $snapshot */
    public function renderPage(array $snapshot, string $basePath, ?string $csrfToken = null, ?string $notice = null, bool $canDiagnose = false): string
    {
        $title = self::e($snapshot['app']) . ' — Kongono Ops Dashboard';
        $metaCsrf = null !== $csrfToken && '' !== $csrfToken
            ? sprintf('<meta name="csrf-token" content="%s">', self::e($csrfToken))
            : '';
        $panel = $this->renderPanel($snapshot, $basePath, $csrfToken, $notice, $canDiagnose);

        return <<<HTML
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
{$metaCsrf}
<title>{$title}</title>
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
  body { margin: 0; background: var(--bg); color: var(--text); line-height: 1.55; -webkit-font-smoothing: antialiased; }
  .wrap { max-width: 1160px; margin: 0 auto; padding: 28px 24px 56px; }
  .hdr { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 22px; border-bottom: 1px solid var(--border); padding-bottom: 16px; }
  .hdr h1 { margin: 0; font-size: 1.28rem; font-weight: 560; letter-spacing: -0.025em; display: inline-flex; align-items: center; gap: 0.55rem; }
  .meta { color: var(--muted); font-size: 0.78rem; font-family: ui-monospace, "JetBrains Mono", Menlo, monospace; margin-top: 3px; }
  .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; margin-bottom: 22px; }
  .card { background: var(--surface); border: 1px solid var(--border); border-radius: 9px; padding: 16px 18px; }
  .card h2 { margin: 0 0 12px; font-size: 0.74rem; font-family: ui-monospace, Menlo, monospace; color: var(--muted); text-transform: uppercase; letter-spacing: 0.08em; font-weight: 500; }
  .pill { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 0.72rem; font-weight: 600; font-family: ui-monospace, Menlo, monospace; border: 1px solid var(--border); }
  .pill-ok { background: rgba(74, 222, 128, 0.10); color: var(--ok); border-color: rgba(74, 222, 128, 0.25); }
  .pill-warn { background: rgba(251, 191, 36, 0.10); color: var(--warn); border-color: rgba(251, 191, 36, 0.25); }
  .pill-bad { background: rgba(229, 83, 75, 0.12); color: var(--err); border-color: rgba(229, 83, 75, 0.28); }
  .notice { background: var(--surface); border: 1px solid var(--border); border-left: 2px solid var(--text); padding: 10px 14px; border-radius: 6px; margin-bottom: 16px; font-size: 0.85rem; color: var(--text-2); }
  table { width: 100%; border-collapse: collapse; font-size: 0.83rem; }
  th, td { text-align: left; padding: 9px 10px; border-bottom: 1px solid var(--border); vertical-align: top; }
  th { color: var(--muted); font-weight: 500; font-size: 0.73rem; text-transform: uppercase; letter-spacing: 0.05em; }
  code { font-family: ui-monospace, "JetBrains Mono", Menlo, monospace; font-size: 0.79rem; color: var(--text); }
  .btn { background: var(--text); color: var(--bg); border: 1px solid var(--text); border-radius: 6px; padding: 5px 11px; font-size: 0.77rem; cursor: pointer; font-weight: 500; text-decoration: none; }
  .btn-danger { background: rgba(229, 83, 75, 0.14); color: var(--err); border-color: rgba(229, 83, 75, 0.32); }
  .btn-ghost { background: transparent; color: var(--text-2); border: 1px solid var(--border); }
  .btn-ghost:hover { background: var(--surface-2); color: var(--text); border-color: var(--border-strong); }
  .bar { height: 6px; background: var(--surface-2); border-radius: 999px; overflow: hidden; margin-top: 6px; }
  .bar > span { display: block; height: 100%; background: var(--text); }
  .actions { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
  .section { margin-bottom: 22px; }
  form.inline { display: inline; }
</style>
</head>
<body>
<div class="wrap">
{$panel}
</div>
</body>
</html>
HTML;
    }

    /** @param OpsSnapshot $snapshot */
    public function renderPanel(array $snapshot, string $basePath, ?string $csrfToken = null, ?string $notice = null, bool $canDiagnose = false): string
    {
        $csrfInput = null !== $csrfToken && '' !== $csrfToken
            ? sprintf('<input type="hidden" name="_csrf" value="%s">', self::e($csrfToken))
            : '';

        $parts = [];
        $parts[] = '<div id="ops-dashboard-root">';
        $parts[] = sprintf(
            '<div class="hdr"><div><h1>%s<span>%s — Ops Dashboard</span></h1><div class="meta">Updated %s</div></div>'
            . '<div class="actions"><a class="btn btn-ghost" href="%s" kgn-get="%s" kgn-target="#ops-dashboard-root">Refresh</a></div></div>',
            KongonoBrand::markSvg(24, 'kgn-ops-gm'),
            self::e($snapshot['app']),
            self::e($snapshot['timestamp']),
            self::e($basePath),
            self::e($basePath),
        );

        if (null !== $notice && '' !== $notice) {
            $parts[] = sprintf('<div class="notice">%s</div>', self::e($notice));
        }

        // 1. Health Checks
        if (null !== $snapshot['health']) {
            $health = $snapshot['health'];
            $overallClass = $health['healthy'] ? 'pill-ok' : 'pill-bad';
            $parts[] = '<div class="section card">';
            $parts[] = sprintf('<h2>System Health <span class="pill %s">%s</span></h2>', $overallClass, self::e(strtoupper($health['status'])));
            $parts[] = '<table><thead><tr><th>Check</th><th>Status</th><th>Latency</th><th>Detail</th></tr></thead><tbody>';
            foreach ($health['checks'] as $name => $check) {
                $parts[] = sprintf(
                    '<tr><td><code>%s</code></td><td><span class="pill %s">%s</span></td><td>%.2f ms</td><td>%s</td></tr>',
                    self::e($name),
                    $check['ok'] ? 'pill-ok' : 'pill-bad',
                    $check['ok'] ? 'OK' : 'FAIL',
                    $check['duration_ms'],
                    self::e(SecretScrubber::scrub($check['error'] ?? 'Healthy')),
                );
            }
            $parts[] = '</tbody></table></div>';
        }

        // 2. Queue Depths + Bulk Actions
        if ([] !== $snapshot['queues']) {
            $parts[] = '<div class="grid">';
            foreach ($snapshot['queues'] as $qName => $counts) {
                $deadTotal = $counts['failed'] + $counts['dead'];
                $parts[] = '<div class="card">';
                $parts[] = sprintf('<h2>Queue: %s</h2>', self::e($qName));
                $parts[] = sprintf(
                    '<div class="meta">pending=%d · reserved=%d · completed=%d · failed/dead=%d</div>',
                    $counts['pending'],
                    $counts['reserved'],
                    $counts['completed'],
                    $deadTotal,
                );
                if ($deadTotal > 0) {
                    $parts[] = sprintf(
                        '<div class="actions" style="margin-top:10px">'
                        . '<form class="inline" method="POST" action="%s/queues/retry" kgn-post="%s/queues/retry" kgn-target="#ops-dashboard-root">%s<input type="hidden" name="queue" value="%s"><input type="hidden" name="job_id" value="all"><button class="btn" type="submit">Retry All (%d)</button></form>'
                        . '<form class="inline" method="POST" action="%s/queues/flush" kgn-post="%s/queues/flush" kgn-target="#ops-dashboard-root">%s<input type="hidden" name="queue" value="%s"><button class="btn btn-danger" type="submit">Flush Dead</button></form>'
                        . '</div>',
                        self::e($basePath),
                        self::e($basePath),
                        $csrfInput,
                        self::e($qName),
                        $deadTotal,
                        self::e($basePath),
                        self::e($basePath),
                        $csrfInput,
                        self::e($qName),
                    );
                }
                $parts[] = '</div>';
            }
            $parts[] = '</div>';
        }

        // 3. Failed / Dead-Letter Jobs Table
        $parts[] = '<div class="section card"><h2>Failed &amp; Dead-Letter Jobs</h2>';
        if ([] === $snapshot['failed_jobs']) {
            $parts[] = '<div class="meta">Zero failed or dead-lettered jobs.</div>';
        } else {
            $parts[] = '<table><thead><tr><th>Job ID</th><th>Queue</th><th>Type</th><th>Status</th><th>Attempts</th><th>Last Error</th><th>Action</th></tr></thead><tbody>';
            foreach ($snapshot['failed_jobs'] as $job) {
                $diagnoseForm = $canDiagnose
                    ? sprintf(
                        ' <form class="inline" method="POST" action="%s/queues/diagnose" kgn-post="%s/queues/diagnose" kgn-target="#ops-dashboard-root">%s'
                        . '<input type="hidden" name="queue" value="%s"><input type="hidden" name="job_id" value="%s">'
                        . '<button class="btn btn-ghost" type="submit">Diagnose</button></form>',
                        self::e($basePath),
                        self::e($basePath),
                        $csrfInput,
                        self::e($job['queue']),
                        self::e($job['id']),
                    )
                    : '';
                $parts[] = sprintf(
                    '<tr><td><code>%s</code></td><td><code>%s</code></td><td><code>%s</code></td>'
                    . '<td><span class="pill pill-bad">%s</span></td><td>%d/%d</td><td>%s</td>'
                    . '<td><div class="actions"><form class="inline" method="POST" action="%s/queues/retry" kgn-post="%s/queues/retry" kgn-target="#ops-dashboard-root">%s'
                    . '<input type="hidden" name="queue" value="%s"><input type="hidden" name="job_id" value="%s">'
                    . '<button class="btn" type="submit">Retry</button></form>%s</div></td></tr>',
                    self::e($job['id']),
                    self::e($job['queue']),
                    self::e($job['type']),
                    self::e(strtoupper($job['status'])),
                    $job['attempt'],
                    $job['max_attempts'],
                    self::e(SecretScrubber::scrub($job['last_error'] ?? '-')),
                    self::e($basePath),
                    self::e($basePath),
                    $csrfInput,
                    self::e($job['queue']),
                    self::e($job['id']),
                    $diagnoseForm,
                );
            }
            $parts[] = '</tbody></table>';
        }
        $parts[] = '</div>';

        // 4. Parallel Job Batches
        if ([] !== $snapshot['batches']) {
            $parts[] = '<div class="section card"><h2>Job Batches</h2><table><thead><tr><th>Batch</th><th>Progress</th><th>Processed</th><th>Failed</th><th>State</th></tr></thead><tbody>';
            foreach ($snapshot['batches'] as $batch) {
                $pct = max(0.0, min(100.0, $batch['progress']));
                $stateClass = $batch['cancelled'] ? 'pill-bad' : ($batch['pending_jobs'] === 0 ? 'pill-ok' : 'pill-warn');
                $stateLabel = $batch['cancelled'] ? 'CANCELLED' : ($batch['pending_jobs'] === 0 ? 'DONE' : 'RUNNING');
                $parts[] = sprintf(
                    '<tr><td><strong>%s</strong><div class="meta">%s</div></td>'
                    . '<td>%.1f%%<div class="bar"><span style="width:%.1f%%"></span></div></td>'
                    . '<td>%d / %d</td><td>%d</td><td><span class="pill %s">%s</span></td></tr>',
                    self::e($batch['name']),
                    self::e($batch['id']),
                    $pct,
                    $pct,
                    $batch['processed_jobs'],
                    $batch['total_jobs'],
                    $batch['failed_jobs'],
                    $stateClass,
                    $stateLabel,
                );
            }
            $parts[] = '</tbody></table></div>';
        }

        // 5. Metrics Counters & Timings
        if (null !== $snapshot['metrics'] && ([] !== $snapshot['metrics']['counters'] || [] !== $snapshot['metrics']['gauges'] || [] !== $snapshot['metrics']['timings'])) {
            $parts[] = '<div class="section card"><h2>Runtime Metrics</h2><table><thead><tr><th>Metric</th><th>Kind</th><th>Value</th></tr></thead><tbody>';
            foreach ($snapshot['metrics']['counters'] as $c) {
                $parts[] = sprintf('<tr><td><code>%s</code></td><td>counter</td><td>%s</td></tr>', self::e($c['name']), self::e((string) $c['value']));
            }
            foreach ($snapshot['metrics']['gauges'] as $g) {
                $parts[] = sprintf('<tr><td><code>%s</code></td><td>gauge</td><td>%s</td></tr>', self::e($g['name']), self::e((string) $g['value']));
            }
            foreach ($snapshot['metrics']['timings'] as $t) {
                $parts[] = sprintf(
                    '<tr><td><code>%s</code></td><td>timing</td><td>count=%d avg=%.2f max=%.2f</td></tr>',
                    self::e($t['name']),
                    $t['count'],
                    $t['avg'],
                    $t['max'],
                );
            }
            $parts[] = '</tbody></table></div>';
        }

        // 6. Recent Request Traces (from DevInspector if attached)
        if ([] !== $snapshot['recent_traces']) {
            $parts[] = '<div class="section card"><h2>Recent Requests</h2><table><thead><tr><th>Method</th><th>Path</th><th>Status</th><th>Duration</th></tr></thead><tbody>';
            foreach ($snapshot['recent_traces'] as $tr) {
                $parts[] = sprintf(
                    '<tr><td><code>%s</code></td><td><code>%s</code></td><td>%d</td><td>%.2f ms</td></tr>',
                    self::e($tr['method']),
                    self::e($tr['path']),
                    $tr['status'],
                    $tr['duration_ms'],
                );
            }
            $parts[] = '</tbody></table></div>';
        }

        $parts[] = '</div>';

        return implode("\n", $parts);
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
