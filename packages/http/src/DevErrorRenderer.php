<?php

declare(strict_types=1);

namespace Kongono\Http;

use Kongono\Core\CoreException;
use Kongono\Core\Diagnostics\SecretScrubber;
use Throwable;

/**
 * Rich, strict-CSP developer exception page (`APP_DEBUG=true` only).
 *
 * Built for both human developers and LLMs:
 *  - Instant offline Quick Fix suggestions + structured Kongono Safe Diagnostics
 *  - Full previous-exception chain visibility
 *  - Pure-CSS interactive multi-frame stack trace & highlighted source viewer (works with zero JS!)
 *  - Secret-scrubbed LLM Context Packet (1-click copyable Markdown for Claude/Antigravity/Codex)
 *  - Same-origin progressive enhancement (`/_kongono/assets/dev-error.js`) for live Hantei SSE streaming
 */
final class DevErrorRenderer
{
    private const array REDACTED_HEADERS = [
        'authorization',
        'cookie',
        'set-cookie',
        'x-api-key',
        'x-csrf-token',
        'proxy-authorization',
    ];

    public function render(
        Request $request,
        Throwable $exception,
        ?string $basePath = null,
        string $appName = 'Kongono',
        string $devStudioPrefix = '/_kongono',
    ): string {
        $class = $this->esc($exception::class);
        $shortClass = $this->esc($this->shortClassName($exception::class));
        $safeMessage = SecretScrubber::scrub($exception->getMessage());
        $escapedMsg = $this->esc($safeMessage);
        $file = $exception->getFile();
        $line = $exception->getLine();
        $relFile = $this->relativePath($file, $basePath);

        $kongoBin = (null !== $basePath && is_file($basePath . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'kongo'))
            ? 'php vendor/bin/kongo'
            : 'php bin/kongo';

        $rawHantei = sprintf('%s: %s at %s:%d', $exception::class, substr($safeMessage, 0, 160), basename($file), $line);
        // Strip shell metacharacters and control characters so copy-pasting cannot execute injected shell commands
        $cleanHantei = trim((string) preg_replace('/[\x00-\x1f\x7f`$\\\\"\';|&<>(){}]/', ' ', $rawHantei));
        $hanteiDebugCmd = sprintf("%s hantei debug '%s'", $kongoBin, $cleanHantei);
        $hanteiFixCmd = sprintf("%s hantei fix '%s' --apply --verify", $kongoBin, $cleanHantei);

        $quickFix = $this->suggestQuickFix($exception, $safeMessage, $kongoBin);
        $quickFixHtml = $this->renderQuickFix($quickFix);
        $previousHtml = $this->renderPreviousChain($exception, $basePath);
        $diagnosticHtml = $this->renderDiagnostic($exception);
        [$frameCss, $framesHtml] = $this->renderInteractiveFrames($exception, $basePath);
        $requestHtml = $this->renderRequestSummary($request);
        $llmPrompt = $this->buildLlmPrompt($request, $exception, $basePath);

        $baseUrl = '/' . trim($devStudioPrefix, '/');
        $escapedBase = $this->esc($baseUrl);
        $escapedApp = $this->esc($appName);
        $escapedRel = $this->esc($relFile);
        $escapedHanteiDebug = $this->esc($hanteiDebugCmd);
        $escapedHanteiFix = $this->esc($hanteiFixCmd);
        $escapedLlm = $this->esc($llmPrompt);
        $escapedPhp = $this->esc(PHP_VERSION);
        $escapedMethod = $this->esc($request->method());
        $escapedPath = $this->esc(SecretScrubber::scrub($request->path()));
        $memMb = number_format(memory_get_peak_usage(true) / 1_048_576, 1);

        $html = <<<HTML
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{$shortClass}: {$escapedMsg} — {$escapedApp}</title>
  <style>
    :root {
      color-scheme: dark;
      --bg: #080c15;
      --surface: #0f172a;
      --surface-2: #162033;
      --surface-3: #1e293b;
      --border: #243247;
      --text: #f1f5f9;
      --muted: #94a3b8;
      --dim: #64748b;
      --red: #f87171;
      --red-bg: rgba(239, 68, 68, 0.14);
      --red-border: rgba(239, 68, 68, 0.4);
      --emerald: #34d399;
      --emerald-bg: rgba(16, 185, 129, 0.12);
      --emerald-border: rgba(16, 185, 129, 0.36);
      --sky: #38bdf8;
      --sky-bg: rgba(56, 189, 248, 0.12);
      --sky-border: rgba(56, 189, 248, 0.35);
      --indigo: #818cf8;
      --mono: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
      --sans: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: var(--sans);
      background:
        radial-gradient(900px 320px at 20% -80px, rgba(239, 68, 68, 0.14), transparent 70%),
        radial-gradient(700px 280px at 85% -60px, rgba(56, 189, 248, 0.10), transparent 70%),
        var(--bg);
      color: var(--text);
      line-height: 1.55;
      -webkit-font-smoothing: antialiased;
    }
    .topbar {
      position: sticky;
      top: 0;
      z-index: 20;
      background: rgba(8, 12, 21, 0.88);
      backdrop-filter: blur(10px);
      border-bottom: 1px solid var(--border);
    }
    .topbar-inner {
      max-width: 1240px;
      margin: 0 auto;
      padding: 0.7rem 1.25rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      flex-wrap: wrap;
    }
    .brand {
      display: inline-flex;
      align-items: center;
      gap: 0.6rem;
      font-weight: 700;
      font-size: 0.9rem;
    }
    .pill {
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
      padding: 0.2rem 0.6rem;
      border-radius: 999px;
      font-family: var(--mono);
      font-size: 0.74rem;
      font-weight: 700;
    }
    .pill-err { background: var(--red-bg); border: 1px solid var(--red-border); color: var(--red); }
    .pill-meta { background: var(--surface-2); border: 1px solid var(--border); color: var(--muted); font-weight: 600; }
    .top-actions { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }
    .btn {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      padding: 0.42rem 0.8rem;
      border-radius: 8px;
      border: 1px solid var(--border);
      background: var(--surface-2);
      color: var(--text);
      font-size: 0.8rem;
      font-weight: 600;
      text-decoration: none;
      cursor: pointer;
      font-family: var(--sans);
    }
    .btn:hover { border-color: var(--sky-border); background: var(--surface-3); }
    .btn-ai { background: var(--sky-bg); border-color: var(--sky-border); color: var(--sky); }
    .btn-llm { background: var(--emerald-bg); border-color: var(--emerald-border); color: var(--emerald); }
    .wrap {
      max-width: 1240px;
      margin: 0 auto;
      padding: 1.5rem 1.25rem 4rem;
      display: grid;
      gap: 1.25rem;
    }
    .hero {
      background: linear-gradient(145deg, rgba(69, 10, 10, 0.52), rgba(15, 23, 42, 0.96) 55%);
      border: 1px solid var(--red-border);
      border-radius: 14px;
      padding: 1.5rem 1.65rem;
    }
    .hero-top {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.75rem;
      flex-wrap: wrap;
      margin-bottom: 0.55rem;
    }
    .class-name { font-family: var(--mono); font-size: 0.9rem; color: var(--red); font-weight: 700; }
    .loc-chip {
      font-family: var(--mono);
      font-size: 0.78rem;
      color: #cbd5e1;
      background: rgba(15, 23, 42, 0.85);
      border: 1px solid var(--border);
      padding: 0.22rem 0.6rem;
      border-radius: 7px;
    }
    .hero h1 {
      margin: 0.25rem 0 0;
      font-size: 1.55rem;
      line-height: 1.3;
      color: #ffffff;
      word-break: break-word;
      letter-spacing: -0.02em;
    }
    .prev-chain {
      margin-top: 1rem;
      padding-top: 0.9rem;
      border-top: 1px solid rgba(248, 113, 113, 0.22);
      display: grid;
      gap: 0.55rem;
    }
    .prev-item {
      display: flex;
      gap: 0.6rem;
      align-items: baseline;
      flex-wrap: wrap;
      font-size: 0.85rem;
    }
    .prev-tag {
      font-family: var(--mono);
      font-size: 0.72rem;
      font-weight: 700;
      color: #fbbf24;
      background: rgba(245, 158, 11, 0.14);
      border: 1px solid rgba(245, 158, 11, 0.35);
      padding: 0.12rem 0.45rem;
      border-radius: 5px;
    }
    .quickfix {
      background: linear-gradient(145deg, rgba(6, 78, 59, 0.45), rgba(15, 23, 42, 0.96) 65%);
      border: 1px solid var(--emerald-border);
      border-radius: 14px;
      padding: 1.25rem 1.45rem;
    }
    .quickfix-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.75rem;
      margin-bottom: 0.45rem;
    }
    .quickfix-badge {
      font-family: var(--mono);
      font-size: 0.72rem;
      font-weight: 700;
      letter-spacing: 0.05em;
      text-transform: uppercase;
      color: var(--emerald);
    }
    .quickfix h2 { margin: 0 0 0.35rem; font-size: 1.05rem; color: #ecfdf5; }
    .quickfix p { margin: 0 0 0.75rem; font-size: 0.9rem; color: #cbd5e1; }
    .quickfix p:last-child { margin-bottom: 0; }
    .cmd-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.75rem;
      background: #060a12;
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 0.55rem 0.8rem;
      margin-top: 0.45rem;
    }
    .cmd-row code {
      font-family: var(--mono);
      font-size: 0.82rem;
      color: #7dd3fc;
      overflow-x: auto;
      white-space: pre;
    }
    .panel {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 12px;
      overflow: hidden;
    }
    .panel-header {
      background: var(--surface-2);
      padding: 0.72rem 1rem;
      font-weight: 700;
      font-size: 0.8rem;
      letter-spacing: 0.03em;
      text-transform: uppercase;
      color: var(--muted);
      border-bottom: 1px solid var(--border);
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 0.75rem;
      flex-wrap: wrap;
    }
    .panel-header .mono {
      text-transform: none;
      letter-spacing: 0;
      font-weight: 500;
      font-size: 0.76rem;
      color: #94a3b8;
    }
    .hantei-panel-body { padding: 1rem 1.15rem; display: grid; gap: 0.65rem; }
    .hantei-live-out {
      margin: 0.35rem 0 0;
      padding: 0.95rem 1.05rem;
      border-radius: 8px;
      background: #060a12;
      border: 1px solid var(--sky-border);
      font-family: var(--mono);
      font-size: 0.82rem;
      color: #e0f2fe;
      white-space: pre-wrap;
      word-break: break-word;
      display: none;
    }
    .hantei-live-out.is-visible { display: block; }
    /* Pure-CSS Multi-Frame Stack & Source Explorer */
    .frames-shell {
      display: grid;
      grid-template-columns: minmax(280px, 370px) minmax(0, 1fr);
      min-height: 320px;
    }
    @media (max-width: 900px) {
      .frames-shell { grid-template-columns: 1fr; }
    }
    .frame-radio { position: absolute; opacity: 0; pointer-events: none; }
    .frame-list {
      border-right: 1px solid var(--border);
      max-height: 480px;
      overflow-y: auto;
      background: #0b1120;
    }
    .frame-tab {
      display: block;
      padding: 0.68rem 0.9rem;
      border-bottom: 1px solid rgba(36, 50, 71, 0.7);
      cursor: pointer;
      transition: background-color 120ms ease;
    }
    .frame-tab:hover { background: rgba(56, 189, 248, 0.08); }
    .frame-tab-top {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.5rem;
      margin-bottom: 0.18rem;
    }
    .frame-call {
      font-family: var(--mono);
      font-size: 0.8rem;
      font-weight: 600;
      color: var(--text);
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    .frame-vendor .frame-call { color: var(--dim); font-weight: 500; }
    .frame-loc {
      font-family: var(--mono);
      font-size: 0.72rem;
      color: var(--muted);
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    .frame-vendor .frame-loc { color: #475569; }
    .frame-badge {
      font-family: var(--mono);
      font-size: 0.66rem;
      padding: 0.08rem 0.38rem;
      border-radius: 4px;
      flex-shrink: 0;
    }
    .badge-app { background: rgba(99, 102, 241, 0.2); color: #a5b4fc; }
    .badge-vendor { background: rgba(100, 116, 139, 0.16); color: #64748b; }
    .code-stage { background: #0a0f1c; overflow: hidden; }
    .code-pane { display: none; }
    .code-pane-head {
      padding: 0.6rem 1rem;
      background: #0e1627;
      border-bottom: 1px solid var(--border);
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 0.75rem;
      font-family: var(--mono);
      font-size: 0.78rem;
      color: #cbd5e1;
    }
    .code-pane-head > span:first-child {
      min-width: 0;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    .code-pane-head > span:last-child {
      flex-shrink: 0;
      white-space: nowrap;
    }
    pre.code {
      margin: 0;
      padding: 0.8rem 0;
      overflow-x: auto;
      font-family: var(--mono);
      font-size: 0.83rem;
      line-height: 1.62;
    }
    .code-line { display: flex; padding: 0 1rem; }
    .code-line.highlight {
      background: rgba(239, 68, 68, 0.20);
      border-left: 3px solid #ef4444;
    }
    .ln {
      width: 3.3rem;
      color: #475569;
      user-select: none;
      text-align: right;
      padding-right: 1rem;
      flex-shrink: 0;
    }
    .code-line.highlight .ln { color: #fca5a5; font-weight: 700; }
    .src { white-space: pre; color: #e2e8f0; }
    .no-source { padding: 2rem 1.25rem; color: var(--muted); font-size: 0.88rem; }
    {$frameCss}
    /* LLM Context Packet */
    details.llm-details > summary {
      list-style: none;
      cursor: pointer;
    }
    details.llm-details > summary::-webkit-details-marker { display: none; }
    .llm-body { padding: 0.9rem 1rem 1rem; background: #070b14; }
    textarea.llm-box {
      width: 100%;
      min-height: 220px;
      background: #050810;
      color: #cbd5e1;
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 0.85rem;
      font-family: var(--mono);
      font-size: 0.78rem;
      line-height: 1.5;
      resize: vertical;
    }
    table { width: 100%; border-collapse: collapse; font-size: 0.83rem; }
    th, td { text-align: left; padding: 0.55rem 1rem; border-bottom: 1px solid rgba(36, 50, 71, 0.75); vertical-align: top; }
    th { color: var(--muted); font-weight: 600; width: 210px; }
    .mono { font-family: var(--mono); }
  </style>
</head>
<body data-dev-base="{$escapedBase}">
  <header class="topbar">
    <div class="topbar-inner">
      <div class="brand">
        <svg width="20" height="20" viewBox="0 0 32 32" fill="none" aria-hidden="true">
          <polygon points="16,2 30,16 16,30 2,16" stroke="#f87171" stroke-width="2.2" fill="rgba(239,68,68,0.16)" />
          <polygon points="16,8 24,16 16,24 8,16" fill="#f87171" />
        </svg>
        <span>{$escapedApp}</span>
        <span class="pill pill-err">Kongono Dev Exception · 500</span>
        <span class="pill pill-meta">{$escapedMethod} {$escapedPath}</span>
        <span class="pill pill-meta">PHP {$escapedPhp} · {$memMb} MB</span>
      </div>
      <div class="top-actions">
        <button class="btn btn-ai" type="button" id="kgn-ask-hantei">Diagnose Live with Hantei</button>
        <button class="btn btn-llm" type="button" id="kgn-copy-llm" data-copy-target="kgn-llm-prompt">Copy for LLM</button>
        <a class="btn" href="{$escapedBase}">Dev Studio</a>
      </div>
    </div>
  </header>

  <main class="wrap">
    <section class="hero">
      <div class="hero-top">
        <span class="class-name">{$class}</span>
        <span class="loc-chip">{$escapedRel}:{$line}</span>
      </div>
      <h1>{$escapedMsg}</h1>
      {$previousHtml}
    </section>

    {$quickFixHtml}

    <section class="panel">
      <div class="panel-header">
        <span>Stack Trace &amp; Source Context (Click any frame)</span>
        <span class="mono">{$escapedRel}:{$line}</span>
      </div>
      {$framesHtml}
    </section>

    {$diagnosticHtml}

    <section class="panel">
      <div class="panel-header">
        <span>Hantei AI Debugger &amp; Auto-Fix</span>
        <span class="mono">Secret-scrubbed · PromptGuard protected</span>
      </div>
      <div class="hantei-panel-body">
        <div class="cmd-row">
          <code>{$escapedHanteiDebug}</code>
          <button class="btn" type="button" data-copy-text="{$escapedHanteiDebug}">Copy</button>
        </div>
        <div class="cmd-row">
          <code>{$escapedHanteiFix}</code>
          <button class="btn btn-llm" type="button" data-copy-text="{$escapedHanteiFix}">Copy Auto-Fix</button>
        </div>
        <pre id="kgn-hantei-output" class="hantei-live-out"></pre>
      </div>
    </section>

    <details class="panel llm-details">
      <summary class="panel-header">
        <span>LLM Context Packet (Secret-Scrubbed Markdown for Claude / Antigravity / Codex)</span>
        <span class="mono">Click to expand · Safe to paste</span>
      </summary>
      <div class="llm-body">
        <textarea id="kgn-llm-prompt" class="llm-box" readonly>{$escapedLlm}</textarea>
      </div>
    </details>

    {$requestHtml}
  </main>

  <script src="{$escapedBase}/assets/dev-error.js" defer></script>
</body>
</html>
HTML;

        return $this->scrubRequestSecrets($request, $html);
    }

    /** Scrub exact secret values carried in sensitive incoming request headers so they can never echo in stack/source frames. */
    private function scrubRequestSecrets(Request $request, string $output): string
    {
        foreach ($request->getHeaders() as $name => $values) {
            $isSensitive = in_array(strtolower($name), self::REDACTED_HEADERS, true) || SecretScrubber::isSensitiveKey($name);
            if (!$isSensitive) {
                continue;
            }
            foreach ($values as $rawVal) {
                $trimmed = trim($rawVal);
                $candidates = [$trimmed];
                if (1 === preg_match('/\A(?:Bearer|Basic|Token)\s+(.+)\z/i', $trimmed, $m)) {
                    $candidates[] = trim($m[1]);
                }
                foreach ($candidates as $secret) {
                    if (strlen($secret) >= 8) {
                        $output = str_replace([$secret, $this->esc($secret)], '[REDACTED]', $output);
                    }
                }
            }
        }

        return $output;
    }

    /**
     * Build a structured, secret-scrubbed Markdown prompt packet containing everything an LLM or
     * developer needs to diagnose and fix the exception immediately.
     */
    public function buildLlmPrompt(Request $request, Throwable $exception, ?string $basePath = null): string
    {
        $safeMsg = SecretScrubber::scrub($exception->getMessage());
        $relFile = $this->relativePath($exception->getFile(), $basePath);
        $lines = [
            '# Kongono Exception Report',
            '',
            '- **Exception:** `' . $exception::class . '`',
            '- **Message:** ' . $safeMsg,
            sprintf('- **Location:** `%s:%d`', $relFile, $exception->getLine()),
            sprintf('- **Request:** `%s %s`', $request->method(), SecretScrubber::scrub($request->path())),
            '- **Runtime:** PHP ' . PHP_VERSION . ' (' . PHP_SAPI . ')',
        ];

        $prev = $exception->getPrevious();
        $depth = 0;
        while (null !== $prev && $depth < 5) {
            ++$depth;
            $lines[] = sprintf(
                '- **Caused by (#%d):** `%s` — %s (`%s:%d`)',
                $depth,
                $prev::class,
                SecretScrubber::scrub($prev->getMessage()),
                $this->relativePath($prev->getFile(), $basePath),
                $prev->getLine(),
            );
            $prev = $prev->getPrevious();
        }

        if ($exception instanceof CoreException && null !== $exception->diagnostic) {
            $safe = $exception->diagnostic->toSafeArray();
            $lines[] = '';
            $lines[] = '## Kongono Diagnostic';
            $lines[] = sprintf('- **ID:** `%s` — %s', $safe['id'], $safe['title']);
            if ('' !== trim($safe['remediation'])) {
                $lines[] = '- **Remediation:** ' . $safe['remediation'];
            }
        }

        $snippet = $this->extractPlainCodeSnippet($exception->getFile(), $exception->getLine(), 8);
        if ('' !== $snippet) {
            $lines[] = '';
            $lines[] = sprintf('## Failing Code (`%s:%d`)', $relFile, $exception->getLine());
            $lines[] = '```php';
            $lines[] = $snippet;
            $lines[] = '```';
        }

        $lines[] = '';
        $lines[] = '## Stack Frames (Top 10)';
        foreach (array_slice($this->collectFrames($exception, $basePath), 0, 10) as $idx => $frame) {
            $lines[] = sprintf(
                '%d. `%s` at `%s:%s`%s',
                $idx,
                $frame['call'],
                $frame['rel_file'],
                $frame['line'],
                $frame['is_vendor'] ? ' *(vendor)*' : '',
            );
        }

        $lines[] = '';
        $lines[] = '## Request Headers (Redacted)';
        foreach ($request->getHeaders() as $name => $values) {
            $isSensitive = in_array(strtolower($name), self::REDACTED_HEADERS, true) || SecretScrubber::isSensitiveKey($name);
            $val = $isSensitive ? '[REDACTED]' : SecretScrubber::scrub(implode(', ', $values));
            $lines[] = sprintf('- `%s`: %s', $name, $val);
        }

        return $this->scrubRequestSecrets($request, SecretScrubber::scrub(implode("\n", $lines)));
    }

    /**
     * Suggest a deterministic offline Quick Fix based on Kongono diagnostics or well-known PHP/SQL/DI errors.
     *
     * @return array{title: string, explanation: string, command: ?string}|null
     */
    public function suggestQuickFix(Throwable $exception, string $safeMessage, string $kongoBin = 'php vendor/bin/kongo'): ?array
    {
        if ($exception instanceof CoreException && null !== $exception->diagnostic) {
            $safe = $exception->diagnostic->toSafeArray();
            if ('' !== trim($safe['remediation'])) {
                $extractedCmd = null;
                if (1 === preg_match('/`((?:php\s+[^\s`]*kongo|composer)\s+[^`]+)`/i', $safe['remediation'], $cmdMatch)) {
                    $extractedCmd = trim($cmdMatch[1]);
                } elseif (null !== $exception->getPrevious()) {
                    $prevFix = $this->suggestQuickFix(
                        $exception->getPrevious(),
                        SecretScrubber::scrub($exception->getPrevious()->getMessage()),
                        $kongoBin,
                    );
                    $extractedCmd = $prevFix['command'] ?? null;
                }

                return [
                    'title' => sprintf('Kongono Diagnostic %s — %s', $safe['id'], $safe['title']),
                    'explanation' => $safe['remediation'],
                    'command' => $extractedCmd,
                ];
            }
        }

        $lower = strtolower($safeMessage);

        if (str_contains($lower, 'no such table') || str_contains($lower, 'does not exist') && str_contains($lower, 'relation') || str_contains($lower, 'sqlstate[42p01]') || str_contains($lower, 'sqlstate[42s02]')) {
            return [
                'title' => 'Missing Database Table — Run Pending Migrations',
                'explanation' => 'The database query referenced a table that has not been created yet. Check migration status and apply pending migrations.',
                'command' => $kongoBin . ' migrate',
            ];
        }

        if (str_contains($lower, 'app_key') || str_contains($lower, 'encryption key') || str_contains($lower, '32 bytes')) {
            return [
                'title' => 'Missing or Invalid APP_KEY',
                'explanation' => 'Generate a fresh 256-bit base64 APP_KEY and add it to your local .env file.',
                'command' => $kongoBin . ' key:generate',
            ];
        }

        if (str_contains($lower, 'no ai provider configured')) {
            return [
                'title' => 'Configure an AI Provider in .env',
                'explanation' => 'Set OPENAI_API_KEY, ANTHROPIC_API_KEY, GEMINI_API_KEY, GROQ_API_KEY, or local OLLAMA_HOST=http://127.0.0.1:11434 in .env, then run doctor to verify.',
                'command' => $kongoBin . ' doctor',
            ];
        }

        if (str_contains($lower, 'template') && str_contains($lower, 'not found')) {
            return [
                'title' => 'View Template Not Found',
                'explanation' => 'Verify the `.kgn.html` file exists under `views/` matching the logical name passed to `$views->render()`, and clear stale compiled view caches.',
                'command' => $kongoBin . ' cache:clear',
            ];
        }

        if (str_contains($lower, 'not bound') || str_contains($lower, 'cannot resolve') || str_contains($lower, 'unresolvable')) {
            return [
                'title' => 'Unbound Container Service Dependency',
                'explanation' => 'Register this interface or service explicitly in `app/bootstrap.php` via `$app->singleton(MyInterface::class, fn() => new MyImpl(...))` (Kongono avoids hidden magic globals).',
                'command' => $kongoBin . ' graph',
            ];
        }

        if ($exception instanceof \TypeError) {
            return [
                'title' => 'PHP 8.5 Strict Type Mismatch',
                'explanation' => 'Kongono enforces `declare(strict_types=1)` across all routes, DTOs, and services. Check the parameter or return type highlighted on the failing line below, or run PHPStan Level 9 to inspect callers.',
                'command' => $kongoBin . ' check',
            ];
        }

        return null;
    }

    /** @param array{title: string, explanation: string, command: ?string}|null $quickFix */
    private function renderQuickFix(?array $quickFix): string
    {
        if (null === $quickFix) {
            return '';
        }

        $cmdHtml = '';
        if (null !== $quickFix['command'] && '' !== $quickFix['command']) {
            $escapedCmd = $this->esc($quickFix['command']);
            $cmdHtml = sprintf(
                '<div class="cmd-row"><code>%s</code><button class="btn btn-llm" type="button" data-copy-text="%s">Copy Command</button></div>',
                $escapedCmd,
                $escapedCmd,
            );
        }

        return sprintf(
            '<section class="quickfix"><div class="quickfix-head"><span class="quickfix-badge">Suggested Quick Fix</span></div><h2>%s</h2><p>%s</p>%s</section>',
            $this->esc($quickFix['title']),
            $this->esc($quickFix['explanation']),
            $cmdHtml,
        );
    }

    private function renderPreviousChain(Throwable $exception, ?string $basePath): string
    {
        $prev = $exception->getPrevious();
        if (null === $prev) {
            return '';
        }

        $items = [];
        $depth = 0;
        while (null !== $prev && $depth < 5) {
            ++$depth;
            $items[] = sprintf(
                '<div class="prev-item"><span class="prev-tag">Caused by #%d</span><span class="mono" style="color:#fca5a5">%s</span><span>%s</span><span class="mono" style="color:#94a3b8">(%s:%d)</span></div>',
                $depth,
                $this->esc($prev::class),
                $this->esc(SecretScrubber::scrub($prev->getMessage())),
                $this->esc($this->relativePath($prev->getFile(), $basePath)),
                $prev->getLine(),
            );
            $prev = $prev->getPrevious();
        }

        return '<div class="prev-chain">' . implode('', $items) . '</div>';
    }

    /**
     * Render pure-CSS interactive multi-frame stack list + code viewer (works with zero JS under strict CSP).
     *
     * @return array{0: string, 1: string} [cssRules, html]
     */
    private function renderInteractiveFrames(Throwable $exception, ?string $basePath): array
    {
        $frames = $this->collectFrames($exception, $basePath);
        $maxFrames = min(20, count($frames));
        $visibleFrames = array_slice($frames, 0, $maxFrames);

        // Select first non-vendor frame by default (or frame 0)
        $defaultIdx = 0;
        foreach ($visibleFrames as $idx => $f) {
            if (!$f['is_vendor'] && is_file($f['file'])) {
                $defaultIdx = $idx;
                break;
            }
        }

        $cssRules = [];
        $radios = [];
        $tabs = [];
        $panes = [];

        foreach ($visibleFrames as $idx => $frame) {
            $id = 'kgn-f-' . $idx;
            $checked = $idx === $defaultIdx ? ' checked' : '';
            $radios[] = sprintf('<input class="frame-radio" type="radio" name="kgn-frame" id="%s"%s>', $id, $checked);

            $cssRules[] = sprintf('#%s:checked ~ .frames-shell .frame-list label[for="%s"] { background: rgba(99, 102, 241, 0.18); border-left: 3px solid #818cf8; }', $id, $id);
            $cssRules[] = sprintf('#%s:checked ~ .frames-shell .code-stage .code-pane[data-frame="%d"] { display: block; }', $id, $idx);

            $vendorCls = $frame['is_vendor'] ? ' frame-vendor' : '';
            $badge = $frame['is_vendor']
                ? '<span class="frame-badge badge-vendor">vendor</span>'
                : '<span class="frame-badge badge-app">app</span>';

            $tabs[] = sprintf(
                '<label class="frame-tab%s" for="%s"><div class="frame-tab-top"><span class="frame-call">#%d %s</span>%s</div><div class="frame-loc">%s:%s</div></label>',
                $vendorCls,
                $id,
                $idx,
                $this->esc($frame['call']),
                $badge,
                $this->esc($frame['rel_file']),
                $this->esc($frame['line']),
            );

            $codeHtml = $this->renderCodeLines($frame['file'], (int) $frame['line'], 0 === $idx ? 8 : 5);
            $panes[] = sprintf(
                '<div class="code-pane" data-frame="%d"><div class="code-pane-head"><span>%s:%s</span><span>%s</span></div>%s</div>',
                $idx,
                $this->esc($frame['rel_file']),
                $this->esc($frame['line']),
                $this->esc($frame['call']),
                $codeHtml,
            );
        }

        $html = implode('', $radios)
            . '<div class="frames-shell"><div class="frame-list">' . implode('', $tabs) . '</div>'
            . '<div class="code-stage">' . implode('', $panes) . '</div></div>';

        return [implode("\n    ", $cssRules), $html];
    }

    /**
     * @return list<array{file: string, rel_file: string, line: string, call: string, is_vendor: bool}>
     */
    private function collectFrames(Throwable $exception, ?string $basePath): array
    {
        $frames = [[
            'file' => $exception->getFile(),
            'rel_file' => $this->relativePath($exception->getFile(), $basePath),
            'line' => (string) $exception->getLine(),
            'call' => 'throw ' . $this->shortClassName($exception::class),
            'is_vendor' => $this->isVendorPath($exception->getFile()),
        ]];

        foreach ($exception->getTrace() as $frame) {
            $file = is_string($frame['file'] ?? null) ? $frame['file'] : '[internal]';
            $line = is_int($frame['line'] ?? null) ? (string) $frame['line'] : '0';
            $call = (is_string($frame['class'] ?? null) ? $this->shortClassName($frame['class']) . ($frame['type'] ?? '::') : '')
                . $frame['function'] . '()';
            $frames[] = [
                'file' => $file,
                'rel_file' => '[internal]' === $file ? '[internal]' : $this->relativePath($file, $basePath),
                'line' => $line,
                'call' => $call,
                'is_vendor' => '[internal]' === $file || $this->isVendorPath($file),
            ];
        }

        return $frames;
    }

    private function renderCodeLines(string $file, int $errorLine, int $radius = 8): string
    {
        if ('' === $file || '[internal]' === $file || !is_file($file) || $errorLine <= 0) {
            return '<div class="no-source">Source code unavailable for internal PHP frame.</div>';
        }
        $lines = @file($file, FILE_IGNORE_NEW_LINES);
        if (false === $lines) {
            return '<div class="no-source">Unable to read source file.</div>';
        }

        $start = max(1, $errorLine - $radius);
        $end = min(count($lines), $errorLine + $radius);
        $rows = [];

        for ($ln = $start; $ln <= $end; ++$ln) {
            $cls = $ln === $errorLine ? 'code-line highlight' : 'code-line';
            $text = SecretScrubber::scrub($lines[$ln - 1]);
            $rows[] = sprintf(
                '<div class="%s"><span class="ln">%d</span><span class="src">%s</span></div>',
                $cls,
                $ln,
                $this->esc($text),
            );
        }

        return '<pre class="code">' . implode("\n", $rows) . '</pre>';
    }

    private function extractPlainCodeSnippet(string $file, int $errorLine, int $radius): string
    {
        if (!is_file($file) || $errorLine <= 0) {
            return '';
        }
        $lines = @file($file, FILE_IGNORE_NEW_LINES);
        if (false === $lines) {
            return '';
        }
        $start = max(1, $errorLine - $radius);
        $end = min(count($lines), $errorLine + $radius);
        $out = [];
        for ($ln = $start; $ln <= $end; ++$ln) {
            $marker = $ln === $errorLine ? '>>>' : '   ';
            $out[] = sprintf('%s %4d | %s', $marker, $ln, SecretScrubber::scrub($lines[$ln - 1]));
        }

        return implode("\n", $out);
    }

    private function renderDiagnostic(Throwable $exception): string
    {
        if (!$exception instanceof CoreException || null === $exception->diagnostic) {
            return '';
        }

        $safe = $exception->diagnostic->toSafeArray();
        $rows = [
            sprintf('<tr><th>Diagnostic ID</th><td class="mono">%s</td></tr>', $this->esc($safe['id'])),
            sprintf('<tr><th>Title</th><td>%s</td></tr>', $this->esc($safe['title'])),
            sprintf('<tr><th>Remediation</th><td>%s</td></tr>', $this->esc($safe['remediation'])),
        ];

        foreach ($safe['fields'] as $k => $v) {
            $rows[] = sprintf('<tr><th>%s</th><td class="mono">%s</td></tr>', $this->esc($k), $this->esc((string) $v));
        }

        return '<section class="panel"><div class="panel-header"><span>Structured Kongono Diagnostic (Safe Context)</span><span class="mono">Protected/Forbidden fields redacted</span></div><table>' . implode('', $rows) . '</table></section>';
    }

    private function renderRequestSummary(Request $request): string
    {
        $query = SecretScrubber::scrub($request->getUri()->getQuery());
        $rows = [
            sprintf('<tr><th>Method &amp; Path</th><td class="mono">%s %s</td></tr>', $this->esc($request->method()), $this->esc(SecretScrubber::scrub($request->path()))),
        ];
        if ('' !== $query) {
            $rows[] = sprintf('<tr><th>Query String</th><td class="mono">%s</td></tr>', $this->esc($query));
        }
        foreach ($request->getHeaders() as $name => $values) {
            $isSensitive = in_array(strtolower($name), self::REDACTED_HEADERS, true) || SecretScrubber::isSensitiveKey($name);
            $val = $isSensitive
                ? '[REDACTED]'
                : SecretScrubber::scrub(implode(', ', $values));
            $rows[] = sprintf('<tr><th>%s</th><td class="mono">%s</td></tr>', $this->esc($name), $this->esc($val));
        }

        return '<section class="panel"><div class="panel-header"><span>Request Context (Secrets Redacted)</span><span class="mono">PHP ' . $this->esc(PHP_VERSION) . '</span></div><table>' . implode('', $rows) . '</table></section>';
    }

    private function relativePath(string $path, ?string $basePath): string
    {
        if (null !== $basePath && '' !== $basePath) {
            $normPath = str_replace('\\', '/', $path);
            $normBase = rtrim(str_replace('\\', '/', $basePath), '/') . '/';
            if (str_starts_with($normPath, $normBase)) {
                return substr($normPath, strlen($normBase));
            }
        }

        return str_replace('\\', '/', $path);
    }

    private function isVendorPath(string $path): bool
    {
        $norm = str_replace('\\', '/', $path);

        return str_contains($norm, '/vendor/');
    }

    private function shortClassName(string $fqcn): string
    {
        $pos = strrpos($fqcn, '\\');

        return false === $pos ? $fqcn : substr($fqcn, $pos + 1);
    }

    private function esc(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
