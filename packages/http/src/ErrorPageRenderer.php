<?php

declare(strict_types=1);

namespace Kongono\Http;

use Kongono\Core\Diagnostics\SecretScrubber;

/**
 * Polished, zero-JS, strict-CSP default HTML error page renderer for HTTP errors (401, 403, 404, 405,
 * 419, 422, 429) and production 500 Internal Server Error responses.
 *
 * Nondisclosing by default: never leaks stack traces, file paths, or secrets in production.
 * Apps may override any status by creating `views/errors/{status}.kgn.html` or `views/errors/error.kgn.html`.
 */
final readonly class ErrorPageRenderer
{
    /**
     * @param array<string, list<string>>                                                      $validationErrors
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
                        '<li><span class="field-tag">%s</span><span class="field-msg">%s</span></li>',
                        self::esc($field),
                        self::esc(SecretScrubber::scrub($msg)),
                    );
                }
            }
            if ([] !== $items) {
                $validationHtml = '<div class="detail-box"><div class="detail-label">Fields requiring attention</div><ul class="val-list">' . implode('', $items) . '</ul></div>';
            }
        }

        // Safe Diagnostic block (strictly Disclosure::Safe fields only)
        $diagHtml = '';
        if (null !== $safeDiagnostic) {
            $remediation = '' !== trim($safeDiagnostic['remediation'])
                ? sprintf('<p class="diag-rem">%s</p>', self::esc($safeDiagnostic['remediation']))
                : '';
            $diagHtml = sprintf(
                '<div class="detail-box"><div class="detail-label">Diagnostic Reference · <code>%s</code></div><div class="diag-title">%s</div>%s</div>',
                self::esc($safeDiagnostic['id']),
                self::esc($safeDiagnostic['title']),
                $remediation,
            );
        }

        // Optional local-dev helper footer (ONLY when APP_DEBUG=true — completely omitted in production!)
        $devHintHtml = '';
        if ($debug && null !== $request) {
            $hint = match ($statusCode) {
                404 => 'Check registered routes with <code>php vendor/bin/kongo routes</code> or inspect the live route table in <a href="/_kongono">/_kongono Dev Studio</a>.',
                405 => 'Verify the controller attribute method (<code>#[Get]</code>, <code>#[Post]</code>, <code>#[Put]</code>, <code>#[Patch]</code>, <code>#[Delete]</code>) matches this request.',
                401 => 'Sign in via <code>SessionAuth</code> or send a valid <code>Authorization: Bearer kgn_pat_...</code> header.',
                403 => 'Check your <code>Gate::define()</code> / <code>#[Can]</code> policy or CSRF token (<code>_csrf</code> / <code>X-CSRF-Token</code>).',
                422 => 'Inspect the DTO validation attributes or submit from a page with active <code>Session</code> for automatic PRG redirect.',
                default => 'Inspect live traces and ask Oshie/Hantei inside <a href="/_kongono">/_kongono Dev Studio</a>.',
            };
            $devHintHtml = sprintf(
                '<div class="dev-hint"><div class="dev-hint-head"><span>DEV HINT · APP_DEBUG=true</span><code>%s %s</code></div><p>%s</p></div>',
                self::esc($request->method()),
                self::esc(SecretScrubber::scrub($request->path())),
                $hint,
            );
        }

        $accent = match ($tone) {
            'danger' => '#ef4444',
            'warn' => '#f59e0b',
            default => '#6366f1',
        };
        $accentSoft = match ($tone) {
            'danger' => 'rgba(239, 68, 68, 0.14)',
            'warn' => 'rgba(245, 158, 11, 0.14)',
            default => 'rgba(99, 102, 241, 0.15)',
        };
        $accentBorder = match ($tone) {
            'danger' => 'rgba(239, 68, 68, 0.35)',
            'warn' => 'rgba(245, 158, 11, 0.35)',
            default => 'rgba(99, 102, 241, 0.38)',
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
      --bg: #090d16;
      --surface: #111827;
      --surface-2: #0d1322;
      --border: #1f2937;
      --text: #f8fafc;
      --muted: #94a3b8;
      --accent: {$accent};
      --accent-soft: {$accentSoft};
      --accent-border: {$accentBorder};
      --mono: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
      --sans: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }
    @media (prefers-color-scheme: light) {
      :root {
        --bg: #f8fafc;
        --surface: #ffffff;
        --surface-2: #f1f5f9;
        --border: #e2e8f0;
        --text: #0f172a;
        --muted: #475569;
      }
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      min-height: 100vh;
      font-family: var(--sans);
      background:
        radial-gradient(640px 320px at 50% -60px, var(--accent-soft), transparent 72%),
        var(--bg);
      color: var(--text);
      display: grid;
      place-items: center;
      padding: 2rem 1.25rem;
      line-height: 1.55;
      -webkit-font-smoothing: antialiased;
    }
    .shell {
      width: 100%;
      max-width: 580px;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 16px;
      padding: 2rem 2rem 1.75rem;
      box-shadow: 0 24px 60px -24px rgba(0, 0, 0, 0.45);
    }
    .top-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      margin-bottom: 1.5rem;
    }
    .brand {
      display: inline-flex;
      align-items: center;
      gap: 0.55rem;
      font-size: 0.85rem;
      font-weight: 600;
      color: var(--muted);
      letter-spacing: -0.01em;
    }
    .status-pill {
      display: inline-flex;
      align-items: center;
      gap: 0.45rem;
      font-family: var(--mono);
      font-size: 0.78rem;
      font-weight: 700;
      padding: 0.28rem 0.7rem;
      border-radius: 999px;
      background: var(--accent-soft);
      border: 1px solid var(--accent-border);
      color: var(--accent);
      letter-spacing: 0.04em;
    }
    .status-dot {
      width: 7px;
      height: 7px;
      border-radius: 50%;
      background: var(--accent);
    }
    h1 {
      margin: 0 0 0.55rem;
      font-size: 1.65rem;
      font-weight: 700;
      letter-spacing: -0.025em;
      line-height: 1.2;
    }
    .lead {
      margin: 0;
      color: var(--muted);
      font-size: 0.98rem;
      word-break: break-word;
    }
    .detail-box {
      margin-top: 1.35rem;
      background: var(--surface-2);
      border: 1px solid var(--border);
      border-radius: 10px;
      padding: 0.95rem 1.05rem;
    }
    .detail-label {
      font-size: 0.75rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: var(--muted);
      margin-bottom: 0.55rem;
    }
    .detail-label code {
      font-family: var(--mono);
      color: var(--accent);
    }
    .val-list {
      list-style: none;
      margin: 0;
      padding: 0;
      display: grid;
      gap: 0.5rem;
    }
    .val-list li {
      display: flex;
      align-items: baseline;
      gap: 0.6rem;
      font-size: 0.88rem;
    }
    .field-tag {
      font-family: var(--mono);
      font-size: 0.78rem;
      font-weight: 600;
      padding: 0.12rem 0.48rem;
      border-radius: 6px;
      background: var(--accent-soft);
      color: var(--accent);
      flex-shrink: 0;
    }
    .field-msg { color: var(--text); }
    .diag-title { font-weight: 600; font-size: 0.92rem; }
    .diag-rem { margin: 0.35rem 0 0; font-size: 0.86rem; color: var(--muted); }
    .actions {
      display: flex;
      flex-wrap: wrap;
      gap: 0.65rem;
      margin-top: 1.6rem;
    }
    .btn {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      text-decoration: none;
      font-size: 0.88rem;
      font-weight: 600;
      padding: 0.55rem 1rem;
      border-radius: 8px;
      border: 1px solid var(--border);
      color: var(--text);
      background: var(--surface-2);
      transition: border-color 140ms ease, background-color 140ms ease;
    }
    .btn:hover { border-color: var(--accent-border); }
    .btn-primary {
      background: var(--accent);
      border-color: var(--accent);
      color: #ffffff;
    }
    .btn-primary:hover { opacity: 0.92; }
    .dev-hint {
      margin-top: 1.4rem;
      padding-top: 1rem;
      border-top: 1px dashed var(--border);
      font-size: 0.82rem;
      color: var(--muted);
    }
    .dev-hint-head {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 0.75rem;
      font-family: var(--mono);
      font-size: 0.72rem;
      font-weight: 700;
      letter-spacing: 0.04em;
      color: var(--accent);
      margin-bottom: 0.4rem;
    }
    .dev-hint p { margin: 0; }
    .dev-hint code {
      font-family: var(--mono);
      background: var(--surface-2);
      border: 1px solid var(--border);
      padding: 0.1rem 0.35rem;
      border-radius: 5px;
      color: var(--text);
      font-size: 0.78rem;
    }
    .dev-hint a { color: var(--accent); font-weight: 600; }
  </style>
</head>
<body>
  <main class="shell" role="main">
    <div class="top-row">
      <div class="brand">
        <svg width="20" height="20" viewBox="0 0 32 32" fill="none" aria-hidden="true">
          <polygon points="16,2 30,16 16,30 2,16" stroke="currentColor" stroke-width="2.2" fill="var(--accent-soft)" />
          <polygon points="16,8 24,16 16,24 8,16" fill="var(--accent)" />
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
      <a class="btn btn-primary" href="/">&larr; Go Home</a>
      <a class="btn" href="{$reloadHref}">Try Again</a>
    </div>

    {$devHintHtml}
  </main>
</body>
</html>
HTML;
    }

    /** @return array{title: string, default_message: string, tone: 'danger'|'warn'|'info'} */
    private static function statusMeta(int $status): array
    {
        return match ($status) {
            400 => ['title' => 'Bad Request', 'default_message' => 'The request could not be understood by the server.', 'tone' => 'warn'],
            401 => ['title' => 'Authentication Required', 'default_message' => 'Please sign in to access this resource.', 'tone' => 'warn'],
            403 => ['title' => 'Access Denied', 'default_message' => 'You do not have permission to perform this action.', 'tone' => 'warn'],
            404 => ['title' => 'Page Not Found', 'default_message' => 'The page or endpoint you requested could not be found.', 'tone' => 'info'],
            405 => ['title' => 'Method Not Allowed', 'default_message' => 'This HTTP method is not supported on the requested route.', 'tone' => 'warn'],
            413 => ['title' => 'Payload Too Large', 'default_message' => 'The uploaded request body exceeds the allowed size limit.', 'tone' => 'warn'],
            419 => ['title' => 'Session Expired', 'default_message' => 'Your security token expired. Please reload and try again.', 'tone' => 'warn'],
            422 => ['title' => 'Validation Failed', 'default_message' => 'Please review the highlighted fields and try again.', 'tone' => 'info'],
            429 => ['title' => 'Too Many Requests', 'default_message' => 'You have made too many requests in a short period. Please wait a moment and try again.', 'tone' => 'warn'],
            503 => ['title' => 'Service Unavailable', 'default_message' => 'This service is temporarily unavailable. Please try again shortly.', 'tone' => 'danger'],
            default => $status >= 500
                ? ['title' => 'Internal Server Error', 'default_message' => 'Something went wrong on our end. Please try again shortly.', 'tone' => 'danger']
                : ['title' => 'Request Error', 'default_message' => 'The request could not be completed.', 'tone' => 'info'],
        };
    }

    private static function esc(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
