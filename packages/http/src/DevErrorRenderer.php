<?php

declare(strict_types=1);

namespace Kongono\Http;

use Kongono\Core\CoreException;
use Kongono\Core\Diagnostics\ErrorExplainer;
use Kongono\Core\Diagnostics\ErrorExplanation;
use Kongono\Core\Diagnostics\RequestTrace;
use Kongono\Core\Diagnostics\SecretScrubber;
use Kongono\Http\Brand\KongonoBrand;
use PhpToken;
use Throwable;

/**
 * Kongo Atelier — ultra-restrained 95% monochrome developer exception studio (`APP_DEBUG=true` only).
 *
 * Designed for timeless calm readability, zero visual noise, and instant root-cause clarity:
 *  - Architectural neutral dark (`#0c0c0d`) & light (`#fcfcfc`) surfaces with 1-click theme toggle
 *  - Native PHP 8.5 `PhpToken` syntax highlighting with restrained monochrome-forward token tones
 *  - Borderless app-first call-stack rail with pure-CSS framework frame toggle
 *  - Minimal inline Suggested Quick Fix strip with 1-click copyable CLI command
 *  - Hairline pure-CSS tabbed inspector dock (Hantei AI live SSE, Safe Diagnostic, Request, LLM Packet)
 *  - Nondisclosing secret scrubbing across messages, headers, source context, and LLM Markdown export
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

    /** @var array<int, true> */
    private const array KEYWORD_TOKENS = [
        T_ABSTRACT => true,
        T_AS => true,
        T_BREAK => true,
        T_CASE => true,
        T_CATCH => true,
        T_CLASS => true,
        T_CONST => true,
        T_CONTINUE => true,
        T_DECLARE => true,
        T_DEFAULT => true,
        T_DO => true,
        T_ECHO => true,
        T_ELSE => true,
        T_ELSEIF => true,
        T_ENUM => true,
        T_EXTENDS => true,
        T_FINAL => true,
        T_FINALLY => true,
        T_FN => true,
        T_FOR => true,
        T_FOREACH => true,
        T_FUNCTION => true,
        T_IF => true,
        T_IMPLEMENTS => true,
        T_INSTANCEOF => true,
        T_INTERFACE => true,
        T_MATCH => true,
        T_NAMESPACE => true,
        T_NEW => true,
        T_PRIVATE => true,
        T_PROTECTED => true,
        T_PUBLIC => true,
        T_READONLY => true,
        T_RETURN => true,
        T_STATIC => true,
        T_SWITCH => true,
        T_THROW => true,
        T_TRAIT => true,
        T_TRY => true,
        T_USE => true,
        T_WHILE => true,
        T_YIELD => true,
        T_YIELD_FROM => true,
        T_OPEN_TAG => true,
        T_OPEN_TAG_WITH_ECHO => true,
        T_CLOSE_TAG => true,
    ];

    /** @var array<string, true> */
    private const array BUILTIN_TYPE_WORDS = [
        'null' => true,
        'true' => true,
        'false' => true,
        'void' => true,
        'never' => true,
        'string' => true,
        'int' => true,
        'float' => true,
        'bool' => true,
        'array' => true,
        'mixed' => true,
        'object' => true,
        'callable' => true,
        'iterable' => true,
        'self' => true,
        'parent' => true,
        'static' => true,
    ];

    /**
     * @param list<string> $availableRoutes
     */
    public function render(
        Request $request,
        Throwable $exception,
        ?string $basePath = null,
        string $appName = 'Kongono',
        string $devStudioPrefix = '/_kongono',
        array $availableRoutes = [],
        ?RequestTrace $trace = null,
        ?ErrorExplanation $explanation = null,
    ): string {
        $class = $this->esc($exception::class);
        $shortClass = $this->esc($this->shortClassName($exception::class));
        $safeMessage = SecretScrubber::scrub($exception->getMessage());
        $escapedMsg = $this->esc($safeMessage);
        $file = $exception->getFile();
        $line = $exception->getLine();
        $relFile = $this->relativePath($file, $basePath);
        $shortRel = $this->shortPath($relFile);
        $escapedRel = $this->esc($relFile);
        $escapedShortRel = $this->esc($shortRel);
        $escapedApp = $this->esc($appName);
        $normalizedBase = '/' . trim($devStudioPrefix, '/');
        $assetBase = '/' === $normalizedBase ? '' : $normalizedBase;
        $escapedBase = $this->esc($normalizedBase);
        $escapedAssetBase = $this->esc($assetBase);

        $kongoBin = null !== $basePath && is_file($basePath . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'kongo')
            ? 'php vendor/bin/kongo'
            : 'php bin/kongo';

        $explainer = new ErrorExplainer();
        $resolvedExplanation = $explanation ?? $explainer->explainThrowable(
            exception: $exception,
            method: $request->method(),
            path: $request->path(),
            basePath: $basePath,
            kongoBin: $kongoBin,
            availableRoutes: $availableRoutes,
        );

        $frames = $this->collectFrames($exception, $basePath, $explainer);
        $llmPrompt = $this->buildLlmPrompt($request, $exception, $basePath, $resolvedExplanation, $trace);

        $hanteiSummary = $this->shellSafeSummary(sprintf(
            '%s: %s at %s:%d (%s %s)',
            $exception::class,
            $safeMessage,
            $relFile,
            $line,
            $request->method(),
            SecretScrubber::scrub($request->path()),
        ));
        $hanteiDebugCmd = sprintf('%s hantei debug %s', $kongoBin, $this->shellQuote($hanteiSummary));
        $hanteiFixCmd = sprintf('%s hantei fix %s --apply --verify', $kongoBin, $this->shellQuote($hanteiSummary));

        $escapedHanteiDebug = $this->esc($hanteiDebugCmd);
        $escapedHanteiFix = $this->esc($hanteiFixCmd);
        $escapedLlm = $this->esc($llmPrompt);

        $ideHref = $this->ideUri($frames[0]['file'] ?? $file, $frames[0]['line'] ?? $line);
        $previousHtml = $this->renderPreviousChain($exception, $basePath);
        $quickFixHtml = $this->renderExplanationCard($resolvedExplanation, $trace, $ideHref);
        $traceHtml = null !== $trace ? $this->renderTraceCard($trace) : '';
        $diagnosticData = $this->extractSafeDiagnostic($exception);
        $diagnosticHtml = $this->renderDiagnosticTable($diagnosticData);
        $framesHtml = $this->renderInteractiveFrames($frames);
        $requestHtml = $this->renderRequestTable($request);

        $frameCss = $this->buildFrameCss(count($frames));
        $inspectorDockHtml = $this->renderInspectorDock(
            $escapedHanteiDebug,
            $escapedHanteiFix,
            $diagnosticData,
            $diagnosticHtml,
            $requestHtml,
            $escapedLlm,
        );

        $escapedMethod = $this->esc($request->method());
        $escapedPath = $this->esc(SecretScrubber::scrub($request->path()));
        $escapedPhp = $this->esc(PHP_VERSION);
        $memMb = number_format(memory_get_peak_usage(true) / 1048576, 1);
        $brandSvg = KongonoBrand::markSvg(20, 'kgn-err-gm');

        $html = <<<HTML
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{$shortClass}: {$escapedMsg} — {$escapedApp}</title>
  <link rel="icon" type="image/svg+xml" href="{$escapedAssetBase}/assets/favicon.svg">
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
      --err: #e5534b;
      --err-line: rgba(229, 83, 75, 0.11);
      --ok: #4ade80;
      --topbar-bg: rgba(12, 12, 13, 0.88);
      /* Calm monochrome-forward Atelier syntax palette */
      --tk-kw: #d4d4d8;
      --tk-str: #a7c9b3;
      --tk-var: #e4d4b7;
      --tk-fn: #f4f4f5;
      --tk-ty: #b4becd;
      --tk-num: #d9b69a;
      --tk-cmt: #52525b;
      --mono: ui-monospace, "JetBrains Mono", "SFMono-Regular", Menlo, Consolas, monospace;
      --sans: ui-sans-serif, "Inter", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }
    @media (prefers-color-scheme: light) {
      :root:not([data-theme="dark"]) {
        --bg: #fcfcfc;
        --surface: #f5f5f6;
        --surface-2: #ebebee;
        --border: rgba(0, 0, 0, 0.08);
        --border-strong: rgba(0, 0, 0, 0.16);
        --text: #09090b;
        --text-2: #52525b;
        --muted: #8a8a94;
        --err: #d12e26;
        --err-line: rgba(209, 46, 38, 0.07);
        --ok: #15803d;
        --topbar-bg: rgba(252, 252, 252, 0.90);
        --tk-kw: #18181b;
        --tk-str: #2f6b48;
        --tk-var: #7a5420;
        --tk-fn: #09090b;
        --tk-ty: #3f4a5c;
        --tk-num: #9a4a1c;
        --tk-cmt: #a1a1aa;
      }
    }
    :root[data-theme="light"] {
      color-scheme: light;
      --bg: #fcfcfc;
      --surface: #f5f5f6;
      --surface-2: #ebebee;
      --border: rgba(0, 0, 0, 0.08);
      --border-strong: rgba(0, 0, 0, 0.16);
      --text: #09090b;
      --text-2: #52525b;
      --muted: #8a8a94;
      --err: #d12e26;
      --err-line: rgba(209, 46, 38, 0.07);
      --ok: #15803d;
      --topbar-bg: rgba(252, 252, 252, 0.90);
      --tk-kw: #18181b;
      --tk-str: #2f6b48;
      --tk-var: #7a5420;
      --tk-fn: #09090b;
      --tk-ty: #3f4a5c;
      --tk-num: #9a4a1c;
      --tk-cmt: #a1a1aa;
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: var(--sans);
      background: var(--bg);
      color: var(--text);
      line-height: 1.55;
      -webkit-font-smoothing: antialiased;
      text-rendering: optimizeLegibility;
    }

    /* Ultra-Restrained Single Centered Header */
    .topbar {
      position: sticky;
      top: 0;
      z-index: 30;
      background: var(--topbar-bg);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      border-bottom: 1px solid var(--border);
    }
    .topbar-inner {
      max-width: 1180px;
      margin: 0 auto;
      padding: 0.62rem 1.5rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.75rem;
      flex-wrap: wrap;
    }
    .brand {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      font-size: 0.84rem;
      font-weight: 600;
      color: var(--text);
      letter-spacing: -0.01em;
      white-space: nowrap;
    }
    .mid-meta {
      display: inline-flex;
      align-items: center;
      gap: 0.45rem;
      font-family: var(--mono);
      font-size: 0.73rem;
      font-variant-numeric: tabular-nums;
      color: var(--muted);
      white-space: nowrap;
    }
    .dot {
      width: 6px;
      height: 6px;
      border-radius: 50%;
      flex-shrink: 0;
    }
    .dot-err { background: var(--err); }
    .dot-ok { background: var(--ok); }
    .top-actions {
      display: flex;
      align-items: center;
      gap: 0.38rem;
      flex-wrap: wrap;
    }
    .btn {
      display: inline-flex;
      align-items: center;
      gap: 0.38rem;
      padding: 0.34rem 0.68rem;
      border-radius: 6px;
      font-family: var(--sans);
      font-size: 0.76rem;
      font-weight: 500;
      line-height: 1.25;
      text-decoration: none;
      border: 1px solid var(--border);
      background: transparent;
      color: var(--text-2);
      cursor: pointer;
      transition: background-color 120ms ease, border-color 120ms ease, color 120ms ease, opacity 120ms ease;
    }
    .btn:hover { color: var(--text); border-color: var(--border-strong); background: var(--surface); }
    .btn-solid { background: var(--text); color: var(--bg); border-color: var(--text); }
    .btn-solid:hover { background: var(--text); color: var(--bg); border-color: var(--text); opacity: 0.9; }
    .btn-icon { padding: 0.34rem 0.5rem; }

    /* Main Layout */
    .wrap {
      max-width: 1180px;
      margin: 0 auto;
      padding: 1.5rem 1.5rem 4rem;
      display: grid;
      grid-template-columns: minmax(0, 1fr);
      gap: 1.55rem;
    }
    .wrap > * { min-width: 0; }

    /* Open Monochrome Hero */
    .hero {
      display: grid;
      gap: 0.52rem;
    }
    .hero-eyebrow {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.75rem;
      flex-wrap: wrap;
      font-family: var(--mono);
      font-size: 0.75rem;
      color: var(--muted);
    }
    .hero h1 {
      margin: 0;
      max-width: 940px;
      font-size: clamp(1.38rem, 2.4vw, 1.78rem);
      font-weight: 550;
      line-height: 1.25;
      letter-spacing: -0.032em;
      color: var(--text);
      word-break: break-word;
    }
    .prev-chain {
      display: grid;
      gap: 0.3rem;
      margin-top: 0.15rem;
    }
    .prev-item {
      font-size: 0.84rem;
      color: var(--text-2);
      line-height: 1.5;
    }
    .prev-tag {
      font-family: var(--mono);
      font-size: 0.72rem;
      color: var(--muted);
      margin-right: 0.35rem;
    }

    /* 5W1H Plain-English Diagnosis Card (Who · What · Why · Where · When · How to Fix) */
    .w5-card {
      margin-top: 0.45rem;
      padding: 0.95rem 1.1rem;
      border-radius: 10px;
      background: var(--surface);
      border: 1px solid var(--border-strong);
      display: grid;
      gap: 0.72rem;
    }
    .w5-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.75rem;
      flex-wrap: wrap;
      padding-bottom: 0.55rem;
      border-bottom: 1px solid var(--border);
    }
    .w5-head-left {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      font-size: 0.82rem;
      font-weight: 600;
      color: var(--text);
    }
    .w5-code-tag {
      font-family: var(--mono);
      font-size: 0.7rem;
      color: var(--muted);
      background: var(--bg);
      border: 1px solid var(--border);
      padding: 0.12rem 0.48rem;
      border-radius: 5px;
    }
    .w5-grid {
      display: grid;
      gap: 0.4rem;
    }
    .w5-row {
      display: grid;
      grid-template-columns: 98px minmax(0, 1fr);
      gap: 0.7rem;
      align-items: baseline;
      font-size: 0.83rem;
    }
    .w5-k {
      font-family: var(--mono);
      font-size: 0.69rem;
      letter-spacing: 0.06em;
      text-transform: uppercase;
      color: var(--muted);
    }
    .w5-v { color: var(--text-2); word-break: break-word; }
    .w5-what { color: var(--text); font-weight: 560; }
    .w5-dym {
      display: inline-flex;
      gap: 0.35rem;
      flex-wrap: wrap;
    }
    .w5-pill {
      font-family: var(--mono);
      font-size: 0.76rem;
      color: var(--text);
      background: var(--bg);
      border: 1px solid var(--border-strong);
      padding: 0.1rem 0.48rem;
      border-radius: 5px;
    }
    .w5-fix {
      padding-top: 0.62rem;
      border-top: 1px solid var(--border);
      display: grid;
      gap: 0.5rem;
    }
    .w5-fix-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.6rem;
      flex-wrap: wrap;
      font-family: var(--mono);
      font-size: 0.69rem;
      letter-spacing: 0.07em;
      text-transform: uppercase;
      color: var(--muted);
    }
    .w5-steps {
      margin: 0;
      padding-left: 1.25rem;
      display: grid;
      gap: 0.3rem;
      font-size: 0.83rem;
      color: var(--text);
    }
    .w5-snippet {
      margin: 0;
      padding: 0.62rem 0.85rem;
      border-radius: 7px;
      background: var(--bg);
      border: 1px solid var(--border);
      font-family: var(--mono);
      font-size: 0.78rem;
      line-height: 1.55;
      color: var(--text);
      overflow-x: auto;
      white-space: pre;
    }
    .w5-inline {
      font-family: var(--mono);
      font-size: 0.77rem;
      color: var(--text);
      background: var(--bg);
      border: 1px solid var(--border);
      padding: 0.04rem 0.36rem;
      border-radius: 5px;
    }
    .w5-head-actions {
      display: inline-flex;
      align-items: center;
      gap: 0.38rem;
      flex-wrap: wrap;
    }
    .w5-code-wrap {
      display: grid;
      gap: 0.35rem;
    }
    .w5-code-bar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      font-family: var(--mono);
      font-size: 0.68rem;
      color: var(--muted);
    }
    .w5-trace {
      padding: 0.78rem 1rem;
      border-radius: 9px;
      background: var(--surface);
      border: 1px solid var(--border);
      display: grid;
      gap: 0.5rem;
    }
    .w5-trace-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.65rem;
      flex-wrap: wrap;
      font-family: var(--mono);
      font-size: 0.69rem;
      letter-spacing: 0.06em;
      text-transform: uppercase;
      color: var(--muted);
    }
    .w5-pipe {
      display: flex;
      align-items: center;
      gap: 0.32rem;
      flex-wrap: wrap;
      font-family: var(--mono);
      font-size: 0.74rem;
    }
    .w5-pipe-node {
      padding: 0.14rem 0.5rem;
      border-radius: 5px;
      background: var(--bg);
      border: 1px solid var(--border);
      color: var(--text-2);
    }
    .w5-pipe-node.is-handler {
      border-color: var(--border-strong);
      color: var(--text);
      font-weight: 560;
    }
    .w5-pipe-arrow { color: var(--muted); }
    .w5-trace-meta {
      display: flex;
      align-items: center;
      gap: 1rem;
      flex-wrap: wrap;
      font-family: var(--mono);
      font-size: 0.73rem;
      color: var(--text-2);
    }
    @media (max-width: 640px) {
      .w5-row { grid-template-columns: 1fr; gap: 0.15rem; }
    }

    /* Borderless Stack Rail | Spacious Hairline Code Canvas */
    .workbench {
      padding-top: 1.35rem;
      border-top: 1px solid var(--border);
    }
    .workbench-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.75rem;
      flex-wrap: wrap;
      margin-bottom: 0.95rem;
      font-family: var(--mono);
      font-size: 0.69rem;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: var(--muted);
    }
    .workbench-head-right {
      text-transform: none;
      letter-spacing: 0;
      font-size: 0.72rem;
    }
    .frame-radio, .vendor-toggle, .tab-radio {
      position: absolute;
      opacity: 0;
      pointer-events: none;
    }
    .frames-shell {
      display: grid;
      grid-template-columns: 245px minmax(0, 1fr);
      gap: 2rem;
      align-items: start;
    }
    @media (max-width: 880px) {
      .frames-shell { grid-template-columns: 1fr; gap: 1.25rem; }
    }
    .frame-rail {
      display: grid;
      gap: 0.15rem;
      align-content: start;
      min-width: 0;
      overflow: hidden;
    }
    .frame-rail-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.5rem;
      margin-bottom: 0.45rem;
      font-family: var(--mono);
      font-size: 0.69rem;
      color: var(--muted);
    }
    .vendor-toggle-btn {
      font-family: var(--mono);
      font-size: 0.68rem;
      color: var(--text-2);
      padding: 0.12rem 0.45rem;
      border-radius: 5px;
      border: 1px solid var(--border);
      cursor: pointer;
      user-select: none;
    }
    .vendor-toggle-btn:hover { background: var(--surface); color: var(--text); }
    .vendor-toggle:checked ~ .frames-shell .vendor-toggle-btn {
      background: var(--surface);
      border-color: var(--border-strong);
      color: var(--text);
    }
    .vendor-toggle:not(:checked) ~ .frames-shell .frame-tab.frame-vendor {
      display: none;
    }
    .frame-list {
      display: grid;
      gap: 0.12rem;
      max-height: 460px;
      overflow-y: auto;
      min-width: 0;
    }
    .frame-tab {
      position: relative;
      display: block;
      padding: 0.48rem 0.65rem 0.48rem 0.85rem;
      border-radius: 6px;
      cursor: pointer;
      color: var(--text-2);
      min-width: 0;
      overflow: hidden;
      transition: background-color 110ms ease, color 110ms ease;
    }
    .frame-tab::before {
      content: "";
      position: absolute;
      left: 0;
      top: 0.55rem;
      bottom: 0.55rem;
      width: 2px;
      background: var(--err);
      opacity: 0;
      border-radius: 2px;
    }
    .frame-tab:hover { background: var(--surface); color: var(--text); }
    .frame-vendor { opacity: 0.54; }
    .frame-call {
      font-family: var(--mono);
      font-size: 0.77rem;
      font-weight: 500;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    .frame-loc {
      font-family: var(--mono);
      font-size: 0.69rem;
      color: var(--muted);
      margin-top: 0.1rem;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    .code-stage { min-width: 0; overflow: hidden; }
    .code-pane { display: none; }
    .code-pane-head {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 0.75rem;
      padding-bottom: 0.62rem;
      margin-bottom: 0.45rem;
      border-bottom: 1px solid var(--border);
      font-family: var(--mono);
      font-size: 0.72rem;
      color: var(--muted);
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
      padding: 0.2rem 0;
      overflow-x: auto;
      font-family: var(--mono);
      font-size: 0.83rem;
      line-height: 1.74;
      tab-size: 4;
    }
    .code-line {
      display: flex;
      padding: 0 0.6rem;
      border-left: 2px solid transparent;
    }
    .code-line.highlight {
      background: var(--err-line);
      border-left-color: var(--err);
    }
    .ln {
      width: 2.9rem;
      color: var(--muted);
      opacity: 0.78;
      user-select: none;
      text-align: right;
      padding-right: 1.15rem;
      font-variant-numeric: tabular-nums;
      flex-shrink: 0;
    }
    .code-line.highlight .ln { color: var(--err); opacity: 1; font-weight: 700; }
    .src { white-space: pre; color: var(--text); }
    .tk-kw { color: var(--tk-kw); font-weight: 600; }
    .tk-str { color: var(--tk-str); }
    .tk-var { color: var(--tk-var); }
    .tk-fn { color: var(--tk-fn); }
    .tk-ty { color: var(--tk-ty); }
    .tk-num { color: var(--tk-num); }
    .tk-cmt { color: var(--tk-cmt); font-style: italic; }
    .no-source { padding: 1.8rem 0.5rem; color: var(--muted); font-size: 0.86rem; }
    {$frameCss}

    /* Hairline Pure-CSS Tabbed Inspector Dock */
    .dock {
      padding-top: 1.25rem;
      border-top: 1px solid var(--border);
    }
    .dock-tabs {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.75rem;
      border-bottom: 1px solid var(--border);
      flex-wrap: wrap;
    }
    .dock-tab-list {
      display: flex;
      align-items: center;
      gap: 0.25rem;
      overflow-x: auto;
    }
    .dock-tab {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      padding: 0.55rem 0.72rem;
      font-size: 0.78rem;
      font-weight: 500;
      color: var(--muted);
      border-bottom: 2px solid transparent;
      cursor: pointer;
      user-select: none;
      white-space: nowrap;
      transition: color 120ms ease, border-color 120ms ease;
    }
    .dock-tab:hover { color: var(--text); }
    .dock-count {
      font-family: var(--mono);
      font-size: 0.67rem;
      color: var(--muted);
      background: var(--surface);
      border: 1px solid var(--border);
      padding: 0.04rem 0.34rem;
      border-radius: 999px;
    }
    .dock-meta {
      font-family: var(--mono);
      font-size: 0.7rem;
      color: var(--muted);
    }
    .dock-pane { display: none; padding-top: 1rem; }
    #kgn-tab-hantei:checked ~ .dock-tabs label[for="kgn-tab-hantei"],
    #kgn-tab-diag:checked ~ .dock-tabs label[for="kgn-tab-diag"],
    #kgn-tab-request:checked ~ .dock-tabs label[for="kgn-tab-request"],
    #kgn-tab-llm:checked ~ .dock-tabs label[for="kgn-tab-llm"] {
      color: var(--text);
      border-bottom-color: var(--text);
    }
    #kgn-tab-hantei:checked ~ .dock-body .dock-pane-hantei,
    #kgn-tab-diag:checked ~ .dock-body .dock-pane-diag,
    #kgn-tab-request:checked ~ .dock-body .dock-pane-request,
    #kgn-tab-llm:checked ~ .dock-body .dock-pane-llm {
      display: block;
    }

    .hantei-stack { display: grid; grid-template-columns: minmax(0, 1fr); gap: 0.65rem; }
    .hantei-hint { margin: 0; font-size: 0.81rem; color: var(--muted); }
    .cmd-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.75rem;
      min-width: 0;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 7px;
      padding: 0.45rem 0.55rem 0.45rem 0.8rem;
    }
    .cmd-row code {
      min-width: 0;
      flex: 1;
      font-family: var(--mono);
      font-size: 0.79rem;
      color: var(--text);
      overflow-x: auto;
      white-space: pre;
    }
    .cmd-row .btn { flex-shrink: 0; }
    .hantei-live-out {
      margin: 0;
      padding: 0.9rem 1rem;
      border-radius: 8px;
      background: var(--surface);
      border: 1px solid var(--border);
      border-left: 2px solid var(--text);
      font-family: var(--mono);
      font-size: 0.79rem;
      line-height: 1.65;
      color: var(--text);
      white-space: pre-wrap;
      word-break: break-word;
      display: none;
    }
    .hantei-live-out.is-visible { display: block; }

    .llm-toolbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.75rem;
      margin-bottom: 0.65rem;
      font-size: 0.78rem;
      color: var(--muted);
      flex-wrap: wrap;
    }
    textarea.llm-box {
      width: 100%;
      min-height: 230px;
      background: var(--surface);
      color: var(--text-2);
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 0.85rem 1rem;
      font-family: var(--mono);
      font-size: 0.77rem;
      line-height: 1.6;
      resize: vertical;
    }

    table.kv { width: 100%; border-collapse: collapse; font-size: 0.8rem; }
    table.kv th, table.kv td {
      text-align: left;
      padding: 0.52rem 0.2rem;
      border-bottom: 1px solid var(--border);
      vertical-align: top;
    }
    table.kv tr:last-child th, table.kv tr:last-child td { border-bottom: 0; }
    table.kv th { color: var(--muted); font-weight: 500; width: 210px; white-space: nowrap; }
    table.kv td { color: var(--text); word-break: break-word; }
    .mono { font-family: var(--mono); }
    .redacted-tag {
      font-family: var(--mono);
      font-size: 0.73rem;
      color: var(--muted);
      background: var(--surface);
      border: 1px solid var(--border);
      padding: 0.08rem 0.42rem;
      border-radius: 5px;
    }
  </style>
</head>
<body data-dev-base="{$escapedAssetBase}">
  <header class="topbar">
    <div class="topbar-inner">
      <span class="brand">{$brandSvg}<span>{$escapedApp}</span></span>
      <span class="mid-meta"><span class="dot dot-err"></span>500 · {$escapedMethod} {$escapedPath} · PHP {$escapedPhp}</span>
      <div class="top-actions">
        <button class="btn btn-solid" type="button" id="kgn-ask-hantei">Diagnose Live with Hantei</button>
        <button class="btn" type="button" id="kgn-copy-llm" data-copy-target="kgn-llm-prompt">Copy for LLM</button>
        <button class="btn btn-icon" type="button" id="kgn-theme-toggle" title="Toggle light/dark theme" aria-label="Toggle light/dark theme">◐</button>
        <a class="btn" href="{$escapedBase}">Dev Studio</a>
      </div>
    </div>
  </header>

  <main class="wrap">
    <section class="hero">
      <div class="hero-eyebrow">
        <span title="{$escapedRel}:{$line}">{$class} · {$escapedShortRel}:{$line}</span>
        <span>Kongono Dev Exception · {$memMb} MB</span>
      </div>
      <h1>{$escapedMsg}</h1>
      {$previousHtml}
      {$quickFixHtml}
      {$traceHtml}
    </section>

    <section class="workbench">
      <div class="workbench-head">
        <span>Stack Trace &amp; Source Context</span>
        <span class="workbench-head-right" title="{$escapedRel}:{$line}">{$escapedShortRel}:{$line}</span>
      </div>
      {$framesHtml}
    </section>

    {$inspectorDockHtml}
  </main>

  <script src="{$escapedAssetBase}/assets/dev-error.js" defer></script>
</body>
</html>
HTML;

        return $this->scrubRequestSecrets($request, $html);
    }

    /**
     * Build a compact, secret-scrubbed Markdown Context Packet ready to paste into Claude, Antigravity,
     * Codex, or `php bin/kongo hantei debug`.
     */
    public function buildLlmPrompt(
        Request $request,
        Throwable $exception,
        ?string $basePath = null,
        ?ErrorExplanation $explanation = null,
        ?RequestTrace $trace = null,
    ): string {
        $safeMessage = SecretScrubber::scrub($exception->getMessage());
        $relFile = $this->relativePath($exception->getFile(), $basePath);
        $line = $exception->getLine();

        $resolved = $explanation ?? (new ErrorExplainer())->explainThrowable(
            exception: $exception,
            method: $request->method(),
            path: $request->path(),
            basePath: $basePath,
        );

        $aiPayload = $resolved->toArray();
        if (null !== $trace) {
            $aiPayload['execution_trace'] = $trace->toArray();
        }

        $lines = [
            '# Kongono Exception Report',
            '',
            $resolved->toMarkdown(),
        ];

        if (null !== $trace) {
            $lines[] = '';
            $lines[] = $trace->toMarkdown();
        }

        $lines[] = '';
        $lines[] = '## Structured AI Diagnostic JSON';
        $lines[] = '```json';
        $lines[] = json_encode($aiPayload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
        $lines[] = '```';
        $lines[] = '';
        $lines[] = '## Raw Exception Context';
        $lines[] = sprintf('- **Exception:** `%s`', $exception::class);
        $lines[] = sprintf('- **Message:** %s', $safeMessage);
        $lines[] = sprintf('- **Location:** `%s:%d`', $relFile, $line);
        $lines[] = sprintf('- **Request:** `%s %s`', $request->method(), SecretScrubber::scrub($request->path()));
        $lines[] = sprintf('- **Runtime:** PHP %s (%s)', PHP_VERSION, PHP_SAPI);

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
            $lines[] = '## Kongono Diagnostic (Safe Context)';
            $lines[] = sprintf('- **ID:** `%s`', $safe['id']);
            $lines[] = sprintf('- **Title:** %s', $safe['title']);
            if ('' !== trim($safe['remediation'])) {
                $lines[] = sprintf('- **Remediation:** %s', $safe['remediation']);
            }
            foreach ($safe['fields'] as $key => $val) {
                $rendered = is_bool($val) ? ($val ? 'true' : 'false') : (string) $val;
                $lines[] = sprintf('- **%s:** `%s`', $key, $rendered);
            }
        }

        $snippet = $this->readRawSnippet($exception->getFile(), $line, 6);
        if ('' !== $snippet) {
            $lines[] = '';
            $lines[] = sprintf('## Failing Code (`%s:%d`)', $relFile, $line);
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

    private function renderExplanationCard(
        ErrorExplanation $explanation,
        ?RequestTrace $trace = null,
        ?string $ideHref = null,
    ): string {
        $exp = $explanation->toArray();

        $dymRow = '';
        if ([] !== $exp['did_you_mean']) {
            $pills = array_map(
                fn(string $item): string => sprintf('<span class="w5-pill">%s</span>', $this->esc($item)),
                $exp['did_you_mean'],
            );
            $dymRow = sprintf(
                '<div class="w5-row"><span class="w5-k">Did you mean</span><span class="w5-v w5-dym">%s</span></div>',
                implode('', $pills),
            );
        }

        $stepsItems = array_map(
            fn(string $step): string => sprintf('<li>%s</li>', $this->formatInlineCode($step)),
            $exp['how_to_fix'],
        );
        $stepsHtml = [] !== $stepsItems ? '<ol class="w5-steps">' . implode('', $stepsItems) . '</ol>' : '';

        $cmdHtml = '';
        if (null !== $exp['command'] && '' !== $exp['command']) {
            $escCmd = $this->esc($exp['command']);
            $cmdHtml = sprintf(
                '<div class="cmd-row"><code>$ %s</code><button class="btn" type="button" data-copy-text="%s">Copy command</button></div>',
                $escCmd,
                $escCmd,
            );
        }

        $codeHtml = '';
        if (null !== $exp['code_fix'] && '' !== $exp['code_fix']) {
            $escCode = $this->esc($exp['code_fix']);
            $codeHtml = sprintf(
                '<div class="w5-code-wrap"><div class="w5-code-bar"><span>Suggested Code Change</span><button class="btn" type="button" data-copy-text="%s">Copy code fix</button></div><pre class="w5-snippet">%s</pre></div>',
                $escCode,
                $escCode,
            );
        }

        $aiPayload = $exp;
        if (null !== $trace) {
            $aiPayload['execution_trace'] = $trace->toArray();
        }
        $aiJson = $this->esc(json_encode($aiPayload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE));

        $ideBtn = null !== $ideHref
            ? sprintf('<a class="btn" href="%s">Open in VS Code</a>', $this->esc($ideHref))
            : '';

        return sprintf(
            '<div class="w5-card">'
            . '<div class="w5-head">'
            . '<span class="w5-head-left"><span class="dot dot-ok"></span>Plain-English Diagnosis <span class="w5-code-tag">%s</span></span>'
            . '<span class="w5-head-actions">%s<button class="btn" type="button" data-copy-text="%s">Copy AI JSON</button></span>'
            . '</div>'
            . '<div class="w5-grid">'
            . '<div class="w5-row"><span class="w5-k">What</span><span class="w5-v w5-what">%s</span></div>'
            . '<div class="w5-row"><span class="w5-k">Why</span><span class="w5-v">%s</span></div>'
            . '<div class="w5-row"><span class="w5-k">Who</span><span class="w5-v">%s</span></div>'
            . '<div class="w5-row"><span class="w5-k">Where</span><span class="w5-v mono">%s</span></div>'
            . '<div class="w5-row"><span class="w5-k">When</span><span class="w5-v mono">%s</span></div>'
            . '%s'
            . '</div>'
            . '<div class="w5-fix">'
            . '<div class="w5-fix-head"><span>How to fix</span><span>Human + AI Ready · APP_DEBUG=true</span></div>'
            . '%s%s%s'
            . '</div>'
            . '</div>',
            $this->esc($exp['code']),
            $ideBtn,
            $aiJson,
            $this->formatInlineCode($exp['what']),
            $this->formatInlineCode($exp['why']),
            $this->formatInlineCode($exp['who']),
            $this->esc($exp['where']),
            $this->esc($exp['when']),
            $dymRow,
            $stepsHtml,
            $cmdHtml,
            $codeHtml,
        );
    }

    private function renderTraceCard(RequestTrace $trace): string
    {
        $t = $trace->toArray();
        $nodes = [];
        foreach ($t['middleware'] as $mw) {
            $nodes[] = sprintf('<span class="w5-pipe-node">%s</span>', $this->esc($mw));
        }
        $handlerLabel = $t['matched_handler'] ?? ($t['matched_route'] ?? 'No route matched');
        $nodes[] = sprintf('<span class="w5-pipe-node is-handler">%s</span>', $this->esc($handlerLabel));
        $pipelineHtml = implode('<span class="w5-pipe-arrow">→</span>', $nodes);

        $meta = [
            sprintf('<span>Ref: <strong>%s</strong></span>', $this->esc($t['reference_id'])),
            sprintf('<span>Route: <strong>%s</strong></span>', $this->esc($t['matched_route'] ?? 'unmatched')),
            sprintf('<span>Elapsed: <strong>%.2f ms</strong></span>', $t['elapsed_ms']),
        ];
        if ([] !== $t['query_keys']) {
            $meta[] = sprintf('<span>Query keys: <strong>%s</strong></span>', $this->esc(implode(', ', $t['query_keys'])));
        }
        if ([] !== $t['input_keys']) {
            $meta[] = sprintf('<span>Input keys: <strong>%s</strong></span>', $this->esc(implode(', ', $t['input_keys'])));
        }

        return sprintf(
            '<div class="w5-trace">'
            . '<div class="w5-trace-head"><span>Execution Trace · How We Got Here</span><span>Ref %s · %.2f ms</span></div>'
            . '<div class="w5-pipe">%s</div>'
            . '<div class="w5-trace-meta">%s</div>'
            . '</div>',
            $this->esc($t['reference_id']),
            $t['elapsed_ms'],
            $pipelineHtml,
            implode('', $meta),
        );
    }

    private function formatInlineCode(string $text): string
    {
        $escaped = $this->esc($text);

        return preg_replace('/`([^`]+)`/', '<code class="w5-inline">$1</code>', $escaped) ?? $escaped;
    }

    private function ideUri(string $file, int $line): ?string
    {
        if ('' === $file || '[internal]' === $file || !is_file($file)) {
            return null;
        }
        $norm = str_replace('\\', '/', $file);
        $prefix = str_starts_with($norm, '/') ? 'vscode://file' : 'vscode://file/';

        return sprintf('%s%s:%d', $prefix, $norm, max(1, $line));
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
            $rel = $this->relativePath($prev->getFile(), $basePath);
            $items[] = sprintf(
                '<div class="prev-item"><span class="prev-tag">Caused by #%d</span><span class="mono">%s</span> — %s <span class="mono" style="color:var(--muted)" title="%s:%d">(%s:%d)</span></div>',
                $depth,
                $this->esc($prev::class),
                $this->esc(SecretScrubber::scrub($prev->getMessage())),
                $this->esc($rel),
                $prev->getLine(),
                $this->esc($this->shortPath($rel)),
                $prev->getLine(),
            );
            $prev = $prev->getPrevious();
        }

        return '<div class="prev-chain">' . implode('', $items) . '</div>';
    }

    /**
     * @return list<array{
     *     file: string,
     *     rel_file: string,
     *     short_file: string,
     *     line: int,
     *     call: string,
     *     is_vendor: bool
     * }>
     */
    private function collectFrames(Throwable $exception, ?string $basePath, ?ErrorExplainer $explainer = null): array
    {
        $frames = [];

        // If the crash occurred inside a compiled `.kgn.html` view cache file, prepend the original template as Frame #0
        if (null !== $explainer && null !== $basePath) {
            $sourceMap = $explainer->resolveCompiledViewSource($exception->getFile());
            if (null !== $sourceMap) {
                $templateAbs = rtrim($basePath, '\\/') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $sourceMap['template_path']);
                if (is_file($templateAbs)) {
                    $frames[] = [
                        'file' => $templateAbs,
                        'rel_file' => $sourceMap['template_path'],
                        'short_file' => $this->shortPath($sourceMap['template_path']),
                        'line' => 1,
                        'call' => 'template ' . $sourceMap['template_name'],
                        'is_vendor' => false,
                    ];
                }
            }
        }

        $frames[] = [
            'file' => $exception->getFile(),
            'rel_file' => $this->relativePath($exception->getFile(), $basePath),
            'short_file' => $this->shortPath($this->relativePath($exception->getFile(), $basePath)),
            'line' => $exception->getLine(),
            'call' => 'throw ' . $this->shortClassName($exception::class),
            'is_vendor' => $this->isVendorPath($exception->getFile()),
        ];

        foreach (array_slice($exception->getTrace(), 0, 17) as $frame) {
            $file = $frame['file'] ?? '[internal]';
            $line = $frame['line'] ?? 0;
            $class = isset($frame['class']) ? $this->shortClassName($frame['class']) : '';
            $type = $frame['type'] ?? '';
            $func = $frame['function'];
            $rel = '[internal]' === $file ? '[internal]' : $this->relativePath($file, $basePath);
            $frames[] = [
                'file' => $file,
                'rel_file' => $rel,
                'short_file' => '[internal]' === $rel ? '[internal]' : $this->shortPath($rel),
                'line' => $line,
                'call' => $class . $type . $func . '()',
                'is_vendor' => '[internal]' === $file || $this->isVendorPath($file),
            ];
        }

        return $frames;
    }

    /**
     * Pure-CSS interactive multi-frame stack trace & PhpToken syntax-highlighted source viewer.
     *
     * @param list<array{file: string, rel_file: string, short_file: string, line: int, call: string, is_vendor: bool}> $frames
     */
    private function renderInteractiveFrames(array $frames): string
    {
        $radios = [];
        $tabs = [];
        $panes = [];
        /** @var array<string, array<int, string>|null> $tokenLineCache */
        $tokenLineCache = [];

        $appCount = 0;
        $vendorCount = 0;
        foreach ($frames as $frame) {
            if ($frame['is_vendor']) {
                ++$vendorCount;
            } else {
                ++$appCount;
            }
        }

        $vendorChecked = 0 === $appCount ? ' checked' : '';
        $vendorToggleInput = sprintf('<input type="checkbox" id="kgn-show-vendor" class="vendor-toggle"%s>', $vendorChecked);
        $vendorToggleLabel = $vendorCount > 0
            ? sprintf('<label for="kgn-show-vendor" class="vendor-toggle-btn">+%d framework</label>', $vendorCount)
            : '';

        foreach ($frames as $idx => $frame) {
            $checked = 0 === $idx ? ' checked' : '';
            $radios[] = sprintf('<input type="radio" name="kgn-frame" id="kgn-f-%d" class="frame-radio"%s>', $idx, $checked);

            $vendorClass = $frame['is_vendor'] ? ' frame-vendor' : '';
            $lineTag = $frame['line'] > 0 ? ':' . $frame['line'] : '';

            $tabs[] = sprintf(
                '<label for="kgn-f-%d" class="frame-tab%s" title="%s%s"><div class="frame-call">%s</div><div class="frame-loc">%s%s</div></label>',
                $idx,
                $vendorClass,
                $this->esc($frame['rel_file']),
                $lineTag,
                $this->esc($frame['call']),
                $this->esc($frame['short_file']),
                $lineTag,
            );

            $linesBefore = 4;
            $linesAfter = 0 === $idx ? 9 : 5;
            $snippetHtml = $frame['line'] > 0 && '[internal]' !== $frame['file']
                ? $this->renderSourceSnippet($frame['file'], $frame['line'], $linesBefore, $linesAfter, $tokenLineCache)
                : '<div class="no-source">PHP internal frame — no userland source file available.</div>';

            $panes[] = sprintf(
                '<div class="code-pane code-pane-%d"><div class="code-pane-head"><span title="%s%s">%s%s</span><span>%s</span></div>%s</div>',
                $idx,
                $this->esc($frame['rel_file']),
                $lineTag,
                $this->esc($frame['short_file']),
                $lineTag,
                $this->esc($frame['call']),
                $snippetHtml,
            );
        }

        $railSummary = $appCount > 0
            ? sprintf('%d app %s', $appCount, 1 === $appCount ? 'frame' : 'frames')
            : sprintf('%d %s', count($frames), 1 === count($frames) ? 'frame' : 'frames');

        return $vendorToggleInput
            . implode('', $radios)
            . '<div class="frames-shell"><aside class="frame-rail"><div class="frame-rail-head"><span>' . $railSummary . '</span>' . $vendorToggleLabel . '</div><div class="frame-list">'
            . implode('', $tabs)
            . '</div></aside><div class="code-stage">'
            . implode('', $panes)
            . '</div></div>';
    }

    private function buildFrameCss(int $count): string
    {
        $rules = [];
        for ($i = 0; $i < $count; ++$i) {
            $rules[] = sprintf('#kgn-f-%d:checked ~ .frames-shell .code-pane-%d { display: block; }', $i, $i);
            $rules[] = sprintf('#kgn-f-%d:checked ~ .frames-shell label[for="kgn-f-%d"] { color: var(--text); }', $i, $i);
            $rules[] = sprintf('#kgn-f-%d:checked ~ .frames-shell label[for="kgn-f-%d"]::before { opacity: 1; }', $i, $i);
        }

        return implode("\n    ", $rules);
    }

    /**
     * Render highlighted source lines using PHP 8.5 native `PhpToken::tokenize()`.
     *
     * @param array<string, array<int, string>|null> $tokenLineCache
     */
    private function renderSourceSnippet(string $file, int $errorLine, int $linesBefore, int $linesAfter, array &$tokenLineCache): string
    {
        if (!is_file($file) || !is_readable($file)) {
            return '<div class="no-source">Source file is not readable from this runtime.</div>';
        }

        if (!array_key_exists($file, $tokenLineCache)) {
            $tokenLineCache[$file] = $this->highlightFileLines($file);
        }

        $highlightedLines = $tokenLineCache[$file];
        if (null === $highlightedLines || [] === $highlightedLines) {
            return '<div class="no-source">Unable to read source lines.</div>';
        }

        $total = count($highlightedLines);
        $start = max(1, $errorLine - $linesBefore);
        $end = min($total, $errorLine + $linesAfter);

        $commonIndent = null;
        for ($i = $start; $i <= $end; ++$i) {
            $rawLine = $highlightedLines[$i] ?? '';
            if ('' === trim($rawLine)) {
                continue;
            }
            $leading = strspn($rawLine, ' ');
            $commonIndent = null === $commonIndent ? $leading : min($commonIndent, $leading);
            if (0 === $commonIndent) {
                break;
            }
        }
        $dedent = $commonIndent ?? 0;

        $rows = [];
        for ($i = $start; $i <= $end; ++$i) {
            $cls = $i === $errorLine ? 'code-line highlight' : 'code-line';
            $codeHtml = $highlightedLines[$i] ?? '';
            if ($dedent > 0 && strspn($codeHtml, ' ') >= $dedent) {
                $codeHtml = substr($codeHtml, $dedent);
            }
            $rows[] = sprintf(
                '<div class="%s"><span class="ln">%d</span><span class="src">%s</span></div>',
                $cls,
                $i,
                $codeHtml,
            );
        }

        return '<pre class="code">' . implode('', $rows) . '</pre>';
    }

    /**
     * Tokenize a PHP file into 1-indexed syntax-highlighted HTML lines (strict-CSP, zero JS).
     *
     * @return array<int, string>|null
     */
    private function highlightFileLines(string $file): ?array
    {
        $size = @filesize($file);
        if (false === $size || $size > 1_048_576) {
            return null;
        }

        $contents = @file_get_contents($file);
        if (false === $contents) {
            return null;
        }

        $normalized = str_replace(["\r\n", "\r"], "\n", $contents);
        $scrubbedSource = self::scrubPreservingLines($normalized);
        $scrubbedLines = explode("\n", $scrubbedSource);

        if (!str_ends_with(strtolower($file), '.php') && !str_starts_with(ltrim($normalized), '<?php')) {
            $plain = [];
            foreach ($scrubbedLines as $idx => $lineText) {
                $plain[$idx + 1] = $this->esc($lineText);
            }

            return $plain;
        }

        try {
            $tokens = array_values(PhpToken::tokenize($scrubbedSource));
        } catch (Throwable) {
            $plain = [];
            foreach ($scrubbedLines as $idx => $lineText) {
                $plain[$idx + 1] = $this->esc($lineText);
            }

            return $plain;
        }

        $lines = [1 => ''];
        $currentLine = 1;
        $tokenCount = count($tokens);

        for ($i = 0; $i < $tokenCount; ++$i) {
            $token = $tokens[$i];
            $cls = $this->tokenCssClass($token, $tokens, $i, $tokenCount);
            $scrubbed = SecretScrubber::scrub($token->text);
            $segments = explode("\n", $scrubbed);
            $segCount = count($segments);

            foreach ($segments as $segIdx => $segment) {
                if ('' !== $segment) {
                    $escapedSeg = $this->esc($segment);
                    $lines[$currentLine] = ($lines[$currentLine] ?? '')
                        . (null !== $cls ? sprintf('<span class="%s">%s</span>', $cls, $escapedSeg) : $escapedSeg);
                }

                if ($segIdx < $segCount - 1) {
                    ++$currentLine;
                    $lines[$currentLine] ??= '';
                }
            }
        }

        return $lines;
    }

    /**
     * @param list<PhpToken> $tokens
     */
    private function tokenCssClass(PhpToken $token, array $tokens, int $index, int $count): ?string
    {
        $id = $token->id;

        if (T_COMMENT === $id || T_DOC_COMMENT === $id) {
            return 'tk-cmt';
        }
        if (T_CONSTANT_ENCAPSED_STRING === $id || T_ENCAPSED_AND_WHITESPACE === $id || T_START_HEREDOC === $id || T_END_HEREDOC === $id) {
            return 'tk-str';
        }
        if (T_VARIABLE === $id) {
            return 'tk-var';
        }
        if (T_LNUMBER === $id || T_DNUMBER === $id) {
            return 'tk-num';
        }
        if (isset(self::KEYWORD_TOKENS[$id])) {
            return 'tk-kw';
        }
        if (T_NAME_QUALIFIED === $id || T_NAME_FULLY_QUALIFIED === $id || T_NAME_RELATIVE === $id) {
            return 'tk-ty';
        }
        if (T_STRING === $id) {
            $lower = strtolower($token->text);
            if (isset(self::BUILTIN_TYPE_WORDS[$lower])) {
                return 'tk-kw';
            }

            for ($next = $index + 1; $next < $count; ++$next) {
                if (T_WHITESPACE === $tokens[$next]->id) {
                    continue;
                }
                if ('(' === $tokens[$next]->text) {
                    return 'tk-fn';
                }
                break;
            }

            if ('' !== $token->text && ctype_upper($token->text[0])) {
                return 'tk-ty';
            }
        }

        return null;
    }

    private function readRawSnippet(string $file, int $errorLine, int $radius): string
    {
        if (!is_file($file) || !is_readable($file)) {
            return '';
        }
        $fileSize = @filesize($file);
        if (false === $fileSize || $fileSize > 1_048_576) {
            return '';
        }
        $contents = @file_get_contents($file);
        if (false === $contents) {
            return '';
        }

        // Scrub the full source file BEFORE slicing lines so multiline PEM private keys are never leaked
        $scrubbed = self::scrubPreservingLines(str_replace(["\r\n", "\r"], "\n", $contents));
        $lines = explode("\n", $scrubbed);

        $start = max(1, $errorLine - $radius);
        $end = min(count($lines), $errorLine + $radius);
        $out = [];
        for ($i = $start; $i <= $end; ++$i) {
            $marker = $i === $errorLine ? '>' : ' ';
            $out[] = sprintf('%s %4d | %s', $marker, $i, $lines[$i - 1]);
        }

        return implode("\n", $out);
    }

    /**
     * Redact multiline PEM private keys while preserving exact 1-to-1 line numbering, then run SecretScrubber.
     */
    private static function scrubPreservingLines(string $source): string
    {
        $pemSafe = preg_replace_callback(
            '/-----BEGIN [A-Z0-9 ]*PRIVATE KEY-----[\s\S]*?-----END [A-Z0-9 ]*PRIVATE KEY-----/',
            static fn(array $m): string => implode("\n", array_fill(0, substr_count($m[0], "\n") + 1, '[REDACTED_PRIVATE_KEY]')),
            $source,
        );

        return SecretScrubber::scrub(is_string($pemSafe) ? $pemSafe : $source);
    }

    /** @return array{id: string, title: string, remediation: string, fields: array<string, string|int|bool>}|null */
    private function extractSafeDiagnostic(Throwable $exception): ?array
    {
        if (!$exception instanceof CoreException || null === $exception->diagnostic) {
            return null;
        }

        return $exception->diagnostic->toSafeArray();
    }

    /** @param array{id: string, title: string, remediation: string, fields: array<string, string|int|bool>}|null $safe */
    private function renderDiagnosticTable(?array $safe): string
    {
        if (null === $safe) {
            return '';
        }

        $rows = [
            sprintf('<tr><th>Diagnostic ID</th><td class="mono">%s</td></tr>', $this->esc($safe['id'])),
            sprintf('<tr><th>Title</th><td>%s</td></tr>', $this->esc($safe['title'])),
            sprintf('<tr><th>Remediation</th><td>%s</td></tr>', $this->esc($safe['remediation'])),
        ];
        foreach ($safe['fields'] as $key => $val) {
            $rendered = is_bool($val) ? ($val ? 'true' : 'false') : (string) $val;
            $rows[] = sprintf('<tr><th>%s</th><td class="mono">%s</td></tr>', $this->esc($key), $this->esc($rendered));
        }

        return '<table class="kv">' . implode('', $rows) . '</table>';
    }

    private function renderRequestTable(Request $request): string
    {
        $host = $request->getUri()->getHost();
        $query = $request->getUri()->getQuery();
        $server = $request->getServerParams();
        $remoteAddr = is_string($server['REMOTE_ADDR'] ?? null) && '' !== $server['REMOTE_ADDR']
            ? $server['REMOTE_ADDR']
            : '127.0.0.1';

        $rows = [
            sprintf('<tr><th>Method &amp; Path</th><td class="mono">%s %s</td></tr>', $this->esc($request->method()), $this->esc(SecretScrubber::scrub($request->path()))),
            sprintf('<tr><th>Host</th><td class="mono">%s</td></tr>', $this->esc('' !== $host ? $host : 'localhost')),
            sprintf('<tr><th>Query</th><td class="mono">%s</td></tr>', $this->esc(SecretScrubber::scrub('' !== $query ? $query : '(none)'))),
            sprintf('<tr><th>Client IP</th><td class="mono">%s</td></tr>', $this->esc($remoteAddr)),
        ];

        foreach ($request->getHeaders() as $name => $values) {
            $isSensitive = in_array(strtolower($name), self::REDACTED_HEADERS, true) || SecretScrubber::isSensitiveKey($name);
            $valCell = $isSensitive
                ? '<span class="redacted-tag">[REDACTED]</span>'
                : $this->esc(SecretScrubber::scrub(implode(', ', $values)));
            $rows[] = sprintf('<tr><th>Header: %s</th><td class="mono">%s</td></tr>', $this->esc($name), $valCell);
        }

        return '<table class="kv">' . implode('', $rows) . '</table>';
    }

    /** @param array{id: string, title: string, remediation: string, fields: array<string, string|int|bool>}|null $diagnosticData */
    private function renderInspectorDock(
        string $escapedHanteiDebug,
        string $escapedHanteiFix,
        ?array $diagnosticData,
        string $diagnosticHtml,
        string $requestHtml,
        string $escapedLlm,
    ): string {
        $diagRadio = null !== $diagnosticData
            ? '<input type="radio" name="kgn-dock" id="kgn-tab-diag" class="tab-radio">'
            : '';
        $diagTab = null !== $diagnosticData
            ? sprintf(
                '<label for="kgn-tab-diag" class="dock-tab">Diagnostic <span class="dock-count">%d</span></label>',
                count($diagnosticData['fields']),
            )
            : '';
        $diagPane = null !== $diagnosticData
            ? '<div class="dock-pane dock-pane-diag">' . $diagnosticHtml . '</div>'
            : '';
        $hanteiIcon = KongonoBrand::hanteiSvg(16, 'kgn-err-hantei');

        return <<<HTML
    <section class="dock">
      <input type="radio" name="kgn-dock" id="kgn-tab-hantei" class="tab-radio" checked>
      {$diagRadio}
      <input type="radio" name="kgn-dock" id="kgn-tab-request" class="tab-radio">
      <input type="radio" name="kgn-dock" id="kgn-tab-llm" class="tab-radio">

      <div class="dock-tabs">
        <div class="dock-tab-list">
          <label for="kgn-tab-hantei" class="dock-tab">{$hanteiIcon}<span>Hantei AI</span></label>
          {$diagTab}
          <label for="kgn-tab-request" class="dock-tab">Request</label>
          <label for="kgn-tab-llm" class="dock-tab">LLM Packet</label>
        </div>
        <span class="dock-meta">Secret-scrubbed · PromptGuard protected</span>
      </div>

      <div class="dock-body">
        <div class="dock-pane dock-pane-hantei">
          <div class="hantei-stack">
            <pre id="kgn-hantei-output" class="hantei-live-out"></pre>
            <p class="hantei-hint">Press <strong>H</strong> or click <strong>Diagnose Live with Hantei</strong> above to stream live diagnosis, or run locally:</p>
            <div class="cmd-row">
              <code>{$escapedHanteiDebug}</code>
              <button class="btn" type="button" data-copy-text="{$escapedHanteiDebug}">Copy</button>
            </div>
            <div class="cmd-row">
              <code>{$escapedHanteiFix}</code>
              <button class="btn" type="button" data-copy-text="{$escapedHanteiFix}">Copy Auto-Fix</button>
            </div>
          </div>
        </div>

        {$diagPane}

        <div class="dock-pane dock-pane-request">
          {$requestHtml}
        </div>

        <div class="dock-pane dock-pane-llm">
          <div class="llm-toolbar">
            <span>Secret-scrubbed Markdown packet — ready to paste into Claude, Antigravity, or Codex.</span>
            <button class="btn" type="button" data-copy-target="kgn-llm-prompt">Copy Markdown</button>
          </div>
          <textarea id="kgn-llm-prompt" class="llm-box" readonly>{$escapedLlm}</textarea>
        </div>
      </div>
    </section>
HTML;
    }

    private function relativePath(string $path, ?string $basePath): string
    {
        $normalized = str_replace('\\', '/', $path);
        if (null !== $basePath && '' !== $basePath) {
            $base = rtrim(str_replace('\\', '/', $basePath), '/') . '/';
            if (str_starts_with($normalized, $base)) {
                return substr($normalized, strlen($base));
            }
        }

        return $normalized;
    }

    private function shortPath(string $relPath): string
    {
        $normalized = str_replace('\\', '/', $relPath);
        $parts = array_values(array_filter(explode('/', $normalized), static fn(string $p): bool => '' !== $p));
        if (count($parts) <= 3) {
            return $normalized;
        }

        if (!str_contains($parts[0], ':') && !str_starts_with($normalized, '/')) {
            return $normalized;
        }

        return '…/' . implode('/', array_slice($parts, -2));
    }

    private function isVendorPath(string $path): bool
    {
        $norm = str_replace('\\', '/', $path);

        return str_contains($norm, '/vendor/')
            || 1 === preg_match('#/packages/[a-z0-9_-]+/src/#i', $norm);
    }

    private function shortClassName(string $fqcn): string
    {
        $pos = strrpos($fqcn, '\\');

        return false === $pos ? $fqcn : substr($fqcn, $pos + 1);
    }

    private function shellSafeSummary(string $text): string
    {
        $clean = preg_replace('/[\x00-\x1F\x7F`$;&|<>\\\\!%]+/', ' ', $text) ?? $text;
        $clean = preg_replace('/\s+/', ' ', trim($clean)) ?? trim($clean);

        return mb_substr($clean, 0, 220);
    }

    private function shellQuote(string $arg): string
    {
        return '"' . str_replace('"', "'", $arg) . '"';
    }

    private function scrubRequestSecrets(Request $request, string $output): string
    {
        /** @var list<string> $secrets */
        $secrets = [];

        foreach ($request->getHeaders() as $name => $values) {
            $isSensitive = in_array(strtolower($name), self::REDACTED_HEADERS, true) || SecretScrubber::isSensitiveKey($name);
            if (!$isSensitive) {
                continue;
            }
            foreach ($values as $rawVal) {
                $trimmed = trim($rawVal);
                $secrets[] = $trimmed;
                if (1 === preg_match('/\A(?:Bearer|Basic|Token)\s+(.+)\z/i', $trimmed, $m)) {
                    $secrets[] = trim($m[1]);
                }
                if ('cookie' === strtolower($name)) {
                    foreach (explode(';', $rawVal) as $pair) {
                        $eqPos = strpos($pair, '=');
                        if (false !== $eqPos) {
                            $cookiePart = trim(substr($pair, $eqPos + 1));
                            if ('' !== $cookiePart) {
                                $secrets[] = $cookiePart;
                                $decodedCookie = trim(rawurldecode($cookiePart));
                                if ($decodedCookie !== $cookiePart) {
                                    $secrets[] = $decodedCookie;
                                }
                            }
                        }
                    }
                }
            }
        }

        // Also redact every individual cookie value (raw and HTML-escaped forms)
        foreach ($request->getCookieParams() as $cookieVal) {
            $secrets[] = trim($cookieVal);
        }

        foreach (array_unique($secrets) as $secret) {
            if (strlen($secret) >= 8) {
                $output = str_replace($secret, '[REDACTED]', $output);
                $escaped = $this->esc($secret);
                if ($escaped !== $secret) {
                    $output = str_replace($escaped, '[REDACTED]', $output);
                }
            }
        }

        return $output;
    }

    private function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
