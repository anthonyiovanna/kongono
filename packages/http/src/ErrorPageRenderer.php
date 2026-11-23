<?php

declare(strict_types=1);

namespace Kongono\Http;

use Kongono\Core\Diagnostics\SecretScrubber;

/**
 * Minimalist, zero-JS, strict-CSP default HTML error page renderer for HTTP errors (400–503)
 * and production 500 Internal Server Error responses.
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
    ): string {
        $meta = self::statusMeta($statusCode);
        $title = $meta['title'];
        $tone = $meta['tone'];
        $safeMessage = '' !== trim($message) ? SecretScrubber::scrub($message) : $meta['default_message'];

        $escapedTitle = self::esc($title);
        $escapedMsg = self::esc($safeMessage);
        $escapedApp = self::esc($appName);

        // Structured validation field error list for 422 responses
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
                $validationHtml = '<div class="inset"><div class="inset-head">Fields requiring attention</div><ul class="val-list">' . implode('', $items) . '</ul></div>';
            }
        }

        // Safe Diagnostic block (strictly Disclosure::Safe fields only)
        $diagHtml = '';
        if (null !== $safeDiagnostic) {
            $remediation = '' !== trim($safeDiagnostic['remediation'])
                ? sprintf('<p class="diag-rem">%s</p>', self::esc($safeDiagnostic['remediation']))
                : '';
            $diagHtml = sprintf(
                '<div class="inset"><div class="inset-head"><span>Diagnostic reference</span><code>%s</code></div><div class="diag-body"><div class="diag-title">%s</div>%s</div></div>',
                self::esc($safeDiagnostic['id']),
                self::esc($safeDiagnostic['title']),
                $remediation,
            );
        }

        // Optional local-dev helper footer (ONLY when APP_DEBUG=true — completely omitted in production!)
        $devHintHtml = '';
        if ($debug && null !== $request) {
            $hint = match ($statusCode) {
                404 => 'Inspect registered routes with <code>php vendor/bin/kongo routes</code> or open <a href="/_kongono">/_kongono</a>.',
                405 => 'Verify the controller attribute method (<code>#[Get]</code>, <code>#[Post]</code>, <code>#[Put]</code>, <code>#[Patch]</code>, <code>#[Delete]</code>) matches this request.',
                401 => 'Sign in via <code>SessionAuth</code> or send a valid <code>Authorization: Bearer kgn_pat_...</code> header.',
                403 => 'Check your <code>Gate::define()</code> / <code>#[Can]</code> policy or CSRF token (<code>_csrf</code> / <code>X-CSRF-Token</code>).',
                422 => 'Inspect the DTO validation attributes or submit from a page with active <code>Session</code> for automatic PRG redirect.',
                default => 'Inspect live traces and ask Oshie or Hantei inside <a href="/_kongono">/_kongono</a>.',
            };
            $devHintHtml = sprintf(
                '<footer class="dev-hint"><div class="dev-hint-head"><span>Debug hint · APP_DEBUG=true</span><code>%s %s</code></div><p>%s</p></footer>',
                self::esc($request->method()),
                self::esc(SecretScrubber::scrub($request->path())),
                $hint,
            );
        }

        $dotDark = match ($tone) {
            'danger' => '#e86a65',
            'warn' => '#e0a458',
            default => '#8a93a6',
        };
        $dotLight = match ($tone) {
            'danger' => '#c93b37',
            'warn' => '#b7791f',
            default => '#64748b',
        };

        $reloadHref = null !== $request ? self::esc(SecretScrubber::scrub($request->path())) : '/';

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
      --bg: #0b0c0e;
      --surface: #111317;
      --surface-2: #16191f;
      --border: rgba(255, 255, 255, 0.08);
      --border-strong: rgba(255, 255, 255, 0.14);
      --text: #eceef2;
      --text-2: #9aa1ae;
      --muted: #68707d;
      --dot: {$dotDark};
      --btn-bg: #eceef2;
      --btn-fg: #0b0c0e;
      --btn-hover: #ffffff;
      --mono: ui-monospace, "JetBrains Mono", "SFMono-Regular", Menlo, Consolas, monospace;
      --sans: ui-sans-serif, "Inter", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }
    @media (prefers-color-scheme: light) {
      :root {
        --bg: #f9fafb;
        --surface: #ffffff;
        --surface-2: #f4f5f7;
        --border: rgba(15, 23, 42, 0.085);
        --border-strong: rgba(15, 23, 42, 0.16);
        --text: #111827;
        --text-2: #4b5563;
        --muted: #6b7280;
        --dot: {$dotLight};
        --btn-bg: #111827;
        --btn-fg: #ffffff;
        --btn-hover: #1f2937;
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
      padding: 2rem 1.25rem;
      line-height: 1.55;
      -webkit-font-smoothing: antialiased;
      text-rendering: optimizeLegibility;
    }
    .shell {
      width: 100%;
      max-width: 540px;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 1.85rem 2rem 1.75rem;
    }
    .top-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      padding-bottom: 1.25rem;
      margin-bottom: 1.35rem;
      border-bottom: 1px solid var(--border);
    }
    .brand {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      font-size: 0.84rem;
      font-weight: 500;
      color: var(--text-2);
      letter-spacing: -0.01em;
    }
    .status-pill {
      display: inline-flex;
      align-items: center;
      gap: 0.45rem;
      font-family: var(--mono);
      font-size: 0.75rem;
      font-weight: 500;
      font-variant-numeric: tabular-nums;
      padding: 0.2rem 0.58rem;
      border-radius: 999px;
      background: var(--surface-2);
      border: 1px solid var(--border);
      color: var(--text-2);
    }
    .status-dot {
      width: 6px;
      height: 6px;
      border-radius: 50%;
      background: var(--dot);
      flex-shrink: 0;
    }
    h1 {
      margin: 0 0 0.5rem;
      font-size: 1.48rem;
      font-weight: 600;
      letter-spacing: -0.025em;
      line-height: 1.25;
      color: var(--text);
    }
    .lead {
      margin: 0;
      color: var(--text-2);
      font-size: 0.94rem;
      line-height: 1.6;
      word-break: break-word;
    }
    .inset {
      margin-top: 1.35rem;
      background: var(--surface-2);
      border: 1px solid var(--border);
      border-radius: 10px;
      overflow: hidden;
    }
    .inset-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.75rem;
      padding: 0.58rem 0.9rem;
      border-bottom: 1px solid var(--border);
      font-size: 0.72rem;
      font-weight: 600;
      letter-spacing: 0.03em;
      text-transform: uppercase;
      color: var(--muted);
    }
    .inset-head code {
      font-family: var(--mono);
      font-size: 0.74rem;
      text-transform: none;
      letter-spacing: 0;
      color: var(--text-2);
    }
    .val-list {
      list-style: none;
      margin: 0;
      padding: 0;
    }
    .val-list li {
      display: grid;
      grid-template-columns: minmax(90px, auto) 1fr;
      align-items: baseline;
      gap: 0.75rem;
      padding: 0.62rem 0.9rem;
      font-size: 0.86rem;
      border-bottom: 1px solid var(--border);
    }
    .val-list li:last-child { border-bottom: 0; }
    .field-key {
      font-family: var(--mono);
      font-size: 0.78rem;
      font-weight: 500;
      color: var(--text);
    }
    .field-msg { color: var(--text-2); }
    .diag-body { padding: 0.8rem 0.9rem; }
    .diag-title { font-weight: 500; font-size: 0.9rem; color: var(--text); }
    .diag-rem { margin: 0.3rem 0 0; font-size: 0.84rem; color: var(--text-2); }
    .actions {
      display: flex;
      flex-wrap: wrap;
      gap: 0.6rem;
      margin-top: 1.55rem;
    }
    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.4rem;
      padding: 0.52rem 0.92rem;
      border-radius: 8px;
      font-size: 0.84rem;
      font-weight: 500;
      text-decoration: none;
      border: 1px solid var(--border-strong);
      color: var(--text);
      background: transparent;
      transition: background-color 140ms ease, border-color 140ms ease;
    }
    .btn:hover { background: var(--surface-2); border-color: var(--border-strong); }
    .btn-primary {
      background: var(--btn-bg);
      border-color: var(--btn-bg);
      color: var(--btn-fg);
    }
    .btn-primary:hover { background: var(--btn-hover); border-color: var(--btn-hover); }
    .dev-hint {
      margin-top: 1.5rem;
      padding-top: 1.15rem;
      border-top: 1px solid var(--border);
      font-size: 0.82rem;
      color: var(--text-2);
    }
    .dev-hint-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.5rem;
      flex-wrap: wrap;
      margin-bottom: 0.4rem;
      font-family: var(--mono);
      font-size: 0.72rem;
      color: var(--muted);
    }
    .dev-hint p { margin: 0; line-height: 1.6; }
    .dev-hint code {
      font-family: var(--mono);
      font-size: 0.76rem;
      background: var(--surface-2);
      border: 1px solid var(--border);
      padding: 0.08rem 0.36rem;
      border-radius: 5px;
      color: var(--text);
    }
    .dev-hint a { color: var(--text); text-decoration: underline; text-underline-offset: 2px; }
    @media (max-width: 480px) {
      .shell { padding: 1.5rem 1.35rem; }
      .val-list li { grid-template-columns: 1fr; gap: 0.2rem; }
    }
  </style>
</head>
<body>
  <main class="shell" role="main">
    <div class="top-row">
      <div class="brand">
        <svg width="16" height="16" viewBox="0 0 32 32" fill="none" aria-hidden="true">
          <polygon points="16,2 30,16 16,30 2,16" stroke="currentColor" stroke-width="2" />
          <polygon points="16,9 23,16 16,23 9,16" fill="currentColor" opacity="0.6" />
        </svg>
        <span>{$escapedApp}</span>
      </div>
      <span class="status-pill"><span class="status-dot"></span>HTTP {$statusCode}</span>
    </div>

    <h1>{$escapedTitle}</h1>
    <p class="lead">{$escapedMsg}</p>

    {$validationHtml}
    {$diagHtml}

    <div class="actions">
      <a class="btn btn-primary" href="/">← Return home</a>
      <a class="btn" href="{$reloadHref}">Reload</a>
    </div>

    {$devHintHtml}
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

    private static function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
