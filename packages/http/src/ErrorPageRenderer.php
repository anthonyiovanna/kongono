<?php

declare(strict_types=1);

namespace Kongono\Http;

use Kongono\Core\Diagnostics\ErrorExplanation;
use Kongono\Core\Diagnostics\RequestTrace;
use Kongono\Core\Diagnostics\SecretScrubber;
use Kongono\Http\Brand\KongonoBrand;

/**
 * Kongo Atelier — timeless monochrome, zero-JS, strict-CSP default HTML error page renderer
 * for HTTP errors (400–503) and production 500 Internal Server Error responses.
 *
 * Nondisclosing by default: never leaks stack traces, file paths, or secrets in production.
 * Apps may override any status by creating `views/errors/{status}.kgn.html` or `views/errors/error.kgn.html`.
 */
final readonly class ErrorPageRenderer
{
    /**
     * @param array<string, list<string>>                                                            $validationErrors
     * @param array{id: string, title: string, remediation: string, fields: array<string, mixed>}|null $safeDiagnostic
     */
    public function render(
        int $statusCode,
        string $message,
        ?Request $request = null,
        array $validationErrors = [],
        ?array $safeDiagnostic = null,
        bool $debug = false,
        string $appName = 'Kongono',
        ?ErrorExplanation $explanation = null,
        ?string $referenceId = null,
        ?RequestTrace $trace = null,
    ): string {
        $meta = self::statusMeta($statusCode);
        $title = $meta['title'];
        $safeMessage = '' !== trim($message) ? SecretScrubber::scrub($message) : $meta['default_message'];

        $escapedTitle = self::esc($title);
        $escapedMsg = self::esc($safeMessage);
        $escapedApp = self::esc($appName);

        // Opaque incident reference pill (safe in production — gives support a log key with zero internal leakage)
        $refHtml = null !== $referenceId && '' !== trim($referenceId)
            ? sprintf('<div class="ref-line"><span>Reference</span><code>%s</code></div>', self::esc($referenceId))
            : '';

        // Hairline validation list for 422 responses
        $validationHtml = '';
        if ([] !== $validationErrors) {
            $items = [];
            foreach ($validationErrors as $field => $messages) {
                foreach ($messages as $msg) {
                    $items[] = sprintf(
                        '<li><span class="field-key">%s</span><span class="field-msg">%s</span></li>',
                        self::esc($field),
                        self::esc(SecretScrubber::scrub($msg)),
                    );
                }
            }
            if ([] !== $items) {
                $validationHtml = '<div class="section-block"><div class="section-label">Fields requiring attention</div><ul class="val-list">' . implode('', $items) . '</ul></div>';
            }
        }

        // Safe Diagnostic block (strictly Disclosure::Safe fields only)
        $diagHtml = '';
        if (null !== $safeDiagnostic) {
            $remediation = '' !== trim($safeDiagnostic['remediation'])
                ? sprintf('<p class="diag-rem">%s</p>', self::esc($safeDiagnostic['remediation']))
                : '';
            $diagHtml = sprintf(
                '<div class="section-block"><div class="section-label"><span>Diagnostic reference</span><code>%s</code></div><div class="diag-title">%s</div>%s</div>',
                self::esc($safeDiagnostic['id']),
                self::esc($safeDiagnostic['title']),
                $remediation,
            );
        }

        // Optional local-dev 5W1H plain-English diagnosis (ONLY when APP_DEBUG=true — completely omitted in production!)
        $devHintHtml = '';
        if ($debug && null !== $explanation) {
            $exp = $explanation->toArray();
            $dymHtml = '';
            if ([] !== $exp['did_you_mean']) {
                $pills = array_map(
                    static fn(string $s): string => sprintf('<code>%s</code>', self::esc($s)),
                    $exp['did_you_mean'],
                );
                $dymHtml = sprintf('<div class="w5-row"><span class="w5-k">Did you mean</span><span class="w5-v">%s</span></div>', implode(' ', $pills));
            }
            $stepsHtml = '';
            if ([] !== $exp['how_to_fix']) {
                $li = array_map(
                    static fn(string $step): string => sprintf('<li>%s</li>', self::formatInlineCode($step)),
                    $exp['how_to_fix'],
                );
                $stepsHtml = '<ol class="w5-steps">' . implode('', $li) . '</ol>';
            }
            $cmdHtml = null !== $exp['command'] && '' !== $exp['command']
                ? sprintf('<pre class="w5-code">$ %s</pre>', self::esc($exp['command']))
                : '';
            $codeHtml = null !== $exp['code_fix'] && '' !== $exp['code_fix']
                ? sprintf('<pre class="w5-code">%s</pre>', self::esc($exp['code_fix']))
                : '';
            $traceLine = '';
            if (null !== $trace) {
                $t = $trace->toArray();
                $pipe = [] !== $t['middleware'] ? implode(' → ', $t['middleware']) : 'direct';
                $traceLine = sprintf(
                    '<div class="w5-row"><span class="w5-k">Pipeline</span><span class="w5-v mono">%s (%.2f ms)</span></div>',
                    self::esc($pipe),
                    $t['elapsed_ms'],
                );
            }

            $devHintHtml = sprintf(
                '<div class="dev-hint"><div class="dev-hint-head"><span>Plain-English Diagnosis · %s</span><code>APP_DEBUG=true</code></div>'
                . '<div class="w5-grid">'
                . '<div class="w5-row"><span class="w5-k">What</span><span class="w5-v w5-strong">%s</span></div>'
                . '<div class="w5-row"><span class="w5-k">Why</span><span class="w5-v">%s</span></div>'
                . '<div class="w5-row"><span class="w5-k">Who</span><span class="w5-v">%s</span></div>'
                . '<div class="w5-row"><span class="w5-k">Where</span><span class="w5-v mono">%s</span></div>'
                . '<div class="w5-row"><span class="w5-k">When</span><span class="w5-v mono">%s</span></div>'
                . '%s%s'
                . '</div>'
                . '<div class="w5-fix-label">How to fix</div>%s%s%s</div>',
                self::esc($exp['code']),
                self::formatInlineCode($exp['what']),
                self::formatInlineCode($exp['why']),
                self::formatInlineCode($exp['who']),
                self::esc($exp['where']),
                self::esc($exp['when']),
                $traceLine,
                $dymHtml,
                $stepsHtml,
                $cmdHtml,
                $codeHtml,
            );
        } elseif ($debug && null !== $request) {
            $hint = match ($statusCode) {
                404 => 'Inspect registered routes with <code>php vendor/bin/kongo routes</code> or open <a href="/_kongono">/_kongono</a>.',
                405 => 'Verify the controller attribute method (<code>#[Get]</code>, <code>#[Post]</code>, <code>#[Put]</code>, <code>#[Patch]</code>, <code>#[Delete]</code>) matches this request.',
                401 => 'Sign in via <code>SessionAuth</code> or send a valid <code>Authorization: Bearer kgn_pat_...</code> header.',
                403 => 'Check your <code>Gate::define()</code> / <code>#[Can]</code> policy or CSRF token (<code>_csrf</code> / <code>X-CSRF-Token</code>).',
                422 => 'Inspect the DTO validation attributes or submit from a page with active <code>Session</code> for automatic PRG redirect.',
                default => 'Inspect live traces and ask Oshie or Hantei inside <a href="/_kongono">/_kongono</a>.',
            };
            $devHintHtml = sprintf(
                '<div class="dev-hint"><div class="dev-hint-head"><span>Debug hint · APP_DEBUG=true</span><code>%s %s</code></div><p>%s</p></div>',
                self::esc($request->method()),
                self::esc(SecretScrubber::scrub($request->path())),
                $hint,
            );
        }

        $rawReloadPath = null !== $request ? SecretScrubber::scrub($request->path()) : '/';
        $safeReloadPath = (
            str_starts_with($rawReloadPath, '/')
            && !str_starts_with($rawReloadPath, '//')
            && 1 !== preg_match('/[\x00-\x1F\x7F\\\\]/', $rawReloadPath)
        ) ? $rawReloadPath : '/';
        $reloadHref = self::esc($safeReloadPath);
        $footerRight = $debug && null !== $request
            ? sprintf('APP_DEBUG · %s %s', self::esc($request->method()), self::esc(SecretScrubber::scrub($request->path())))
            : sprintf('HTTP %d · Nondisclosing', $statusCode);
        $brandSvg = KongonoBrand::markSvg(18, 'kgn-ep-gm');

        return <<<HTML
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{$statusCode} {$escapedTitle} — {$escapedApp}</title>
  <style>
    :root {
      color-scheme: dark light;
      --bg: #0c0c0d;
      --surface: #131315;
      --border: rgba(255, 255, 255, 0.085);
      --border-strong: rgba(255, 255, 255, 0.16);
      --text: #f4f4f5;
      --text-2: #a1a1aa;
      --muted: #63636b;
      --mono: ui-monospace, "JetBrains Mono", "SFMono-Regular", Menlo, Consolas, monospace;
      --sans: ui-sans-serif, "Inter", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }
    @media (prefers-color-scheme: light) {
      :root {
        --bg: #fcfcfc;
        --surface: #f4f4f5;
        --border: rgba(0, 0, 0, 0.085);
        --border-strong: rgba(0, 0, 0, 0.16);
        --text: #09090b;
        --text-2: #52525b;
        --muted: #8a8a94;
      }
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      min-height: 100vh;
      font-family: var(--sans);
      background: var(--bg);
      color: var(--text);
      display: grid;
      place-items: center;
      padding: 2.5rem 1.5rem;
      line-height: 1.6;
      -webkit-font-smoothing: antialiased;
      text-rendering: optimizeLegibility;
    }
    .sheet {
      width: 100%;
      max-width: 680px;
      display: grid;
      gap: 1.4rem;
    }
    .row-head {
      display: flex;
      align-items: baseline;
      gap: 1.15rem;
      padding-bottom: 1.2rem;
      border-bottom: 1px solid var(--border);
    }
    .status-num {
      font-family: var(--mono);
      font-size: 1.5rem;
      font-weight: 500;
      font-variant-numeric: tabular-nums;
      color: var(--muted);
      flex-shrink: 0;
    }
    h1 {
      margin: 0;
      font-size: 1.72rem;
      font-weight: 550;
      letter-spacing: -0.03em;
      line-height: 1.18;
      color: var(--text);
    }
    .lead {
      margin: 0;
      color: var(--text-2);
      font-size: 0.96rem;
      line-height: 1.65;
      word-break: break-word;
    }
    .section-block {
      display: grid;
      gap: 0.45rem;
    }
    .section-label {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.75rem;
      font-family: var(--mono);
      font-size: 0.68rem;
      font-weight: 500;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: var(--muted);
    }
    .section-label code {
      font-family: var(--mono);
      font-size: 0.72rem;
      text-transform: none;
      letter-spacing: 0;
      color: var(--text-2);
    }
    .val-list {
      list-style: none;
      margin: 0;
      padding: 0;
      border-top: 1px solid var(--border);
    }
    .val-list li {
      display: grid;
      grid-template-columns: minmax(105px, auto) 1fr;
      align-items: baseline;
      gap: 0.85rem;
      padding: 0.62rem 0;
      border-bottom: 1px solid var(--border);
      font-size: 0.87rem;
    }
    .field-key {
      font-family: var(--mono);
      font-size: 0.78rem;
      font-weight: 500;
      color: var(--text);
    }
    .field-msg { color: var(--text-2); }
    .diag-title { font-weight: 500; font-size: 0.92rem; color: var(--text); }
    .diag-rem { margin: 0.25rem 0 0; font-size: 0.86rem; color: var(--text-2); }
    .actions {
      display: flex;
      flex-wrap: wrap;
      gap: 0.65rem;
      margin-top: 0.2rem;
    }
    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.4rem;
      padding: 0.52rem 1rem;
      border-radius: 7px;
      font-size: 0.84rem;
      font-weight: 500;
      text-decoration: none;
      border: 1px solid var(--border);
      color: var(--text);
      background: transparent;
      transition: background-color 130ms ease, border-color 130ms ease, opacity 130ms ease;
    }
    .btn:hover { background: var(--surface); border-color: var(--border-strong); }
    .btn-primary {
      background: var(--text);
      border-color: var(--text);
      color: var(--bg);
    }
    .btn-primary:hover { background: var(--text); border-color: var(--text); opacity: 0.9; }
    .dev-hint {
      padding: 0.8rem 0.95rem;
      border-radius: 8px;
      background: var(--surface);
      border: 1px solid var(--border);
      font-size: 0.81rem;
      color: var(--text-2);
    }
    .dev-hint-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.5rem;
      flex-wrap: wrap;
      margin-bottom: 0.35rem;
      font-family: var(--mono);
      font-size: 0.7rem;
      color: var(--muted);
    }
    .dev-hint p { margin: 0; line-height: 1.55; }
    .dev-hint code {
      font-family: var(--mono);
      font-size: 0.75rem;
      background: var(--bg);
      border: 1px solid var(--border);
      padding: 0.08rem 0.36rem;
      border-radius: 5px;
      color: var(--text);
    }
    .dev-hint a { color: var(--text); text-decoration: underline; text-underline-offset: 2px; }
    .w5-grid { display: grid; gap: 0.42rem; margin-top: 0.45rem; }
    .w5-row { display: grid; grid-template-columns: 92px minmax(0, 1fr); gap: 0.65rem; align-items: baseline; font-size: 0.81rem; }
    .w5-k { font-family: var(--mono); font-size: 0.69rem; text-transform: uppercase; letter-spacing: 0.06em; color: var(--muted); }
    .w5-v { color: var(--text-2); word-break: break-word; }
    .w5-strong { color: var(--text); font-weight: 550; }
    .mono { font-family: var(--mono); font-size: 0.77rem; }
    .w5-fix-label { margin-top: 0.75rem; padding-top: 0.65rem; border-top: 1px solid var(--border); font-family: var(--mono); font-size: 0.69rem; text-transform: uppercase; letter-spacing: 0.06em; color: var(--muted); }
    .w5-steps { margin: 0.38rem 0 0; padding-left: 1.2rem; display: grid; gap: 0.28rem; color: var(--text); font-size: 0.81rem; }
    .w5-code { margin: 0.5rem 0 0; padding: 0.55rem 0.75rem; border-radius: 6px; background: var(--bg); border: 1px solid var(--border); font-family: var(--mono); font-size: 0.76rem; color: var(--text); overflow-x: auto; white-space: pre; }
    .ref-line { display: inline-flex; align-items: center; gap: 0.5rem; font-family: var(--mono); font-size: 0.74rem; color: var(--muted); }
    .ref-line code { color: var(--text-2); background: var(--surface); border: 1px solid var(--border); padding: 0.12rem 0.48rem; border-radius: 5px; }
    .foot {
      padding-top: 1rem;
      border-top: 1px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      flex-wrap: wrap;
      font-family: var(--mono);
      font-size: 0.72rem;
      color: var(--muted);
    }
    .foot-brand {
      display: inline-flex;
      align-items: center;
      gap: 0.45rem;
    }
    @media (max-width: 480px) {
      .val-list li { grid-template-columns: 1fr; gap: 0.2rem; }
    }
  </style>
</head>
<body>
  <main class="sheet" role="main">
    <div class="row-head">
      <span class="status-num">{$statusCode}</span>
      <h1>{$escapedTitle}</h1>
    </div>

    <p class="lead">{$escapedMsg}</p>

    {$refHtml}
    {$validationHtml}
    {$diagHtml}

    <div class="actions">
      <a class="btn btn-primary" href="/">← Return home</a>
      <a class="btn" href="{$reloadHref}">Reload</a>
    </div>

    {$devHintHtml}

    <footer class="foot">
      <span class="foot-brand">{$brandSvg}<span>{$escapedApp}</span></span>
      <span>{$footerRight}</span>
    </footer>
  </main>
</body>
</html>
HTML;
    }

    /** @return array{title: string, default_message: string, tone: 'danger'|'warn'|'neutral'} */
    public static function statusMeta(int $statusCode): array
    {
        return match ($statusCode) {
            400 => [
                'title' => 'Bad Request',
                'default_message' => 'The request could not be understood or contained malformed parameters.',
                'tone' => 'warn',
            ],
            401 => [
                'title' => 'Authentication Required',
                'default_message' => 'Please sign in or provide valid credentials to access this resource.',
                'tone' => 'warn',
            ],
            403 => [
                'title' => 'Access Denied',
                'default_message' => 'You do not have permission to perform this action.',
                'tone' => 'warn',
            ],
            404 => [
                'title' => 'Page Not Found',
                'default_message' => 'The page or resource you requested does not exist or may have moved.',
                'tone' => 'neutral',
            ],
            405 => [
                'title' => 'Method Not Allowed',
                'default_message' => 'This HTTP method is not supported for the requested endpoint.',
                'tone' => 'warn',
            ],
            413 => [
                'title' => 'Payload Too Large',
                'default_message' => 'The request body exceeds the maximum allowed size.',
                'tone' => 'warn',
            ],
            419 => [
                'title' => 'Session Expired',
                'default_message' => 'Your security token has expired. Refresh the page and try again.',
                'tone' => 'warn',
            ],
            422 => [
                'title' => 'Validation Failed',
                'default_message' => 'Please review the highlighted fields and try again.',
                'tone' => 'warn',
            ],
            429 => [
                'title' => 'Too Many Requests',
                'default_message' => 'You have sent too many requests in a short period. Please wait a moment and try again.',
                'tone' => 'warn',
            ],
            503 => [
                'title' => 'Service Unavailable',
                'default_message' => 'The application is temporarily unavailable. Please try again shortly.',
                'tone' => 'danger',
            ],
            default => [
                'title' => $statusCode >= 500 ? 'Internal Server Error' : 'Request Error',
                'default_message' => $statusCode >= 500
                    ? 'An internal server error occurred.'
                    : 'The request could not be completed.',
                'tone' => $statusCode >= 500 ? 'danger' : 'neutral',
            ],
        };
    }

    private static function formatInlineCode(string $value): string
    {
        $escaped = self::esc($value);

        return preg_replace('/`([^`]+)`/', '<code>$1</code>', $escaped) ?? $escaped;
    }

    private static function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
