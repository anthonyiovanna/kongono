<?php

declare(strict_types=1);

namespace Kongono\Http;

use Kongono\Core\CoreException;
use Kongono\Core\Diagnostics\SecretScrubber;
use Throwable;

/**
 * Rich, self-contained HTML5 developer exception page (debug mode only).
 *
 * Displays highlighted source code frames, stack trace, Kongono structured diagnostics,
 * redacted request headers, and a ready-to-run `kongo hantei debug` command.
 */
final class DevErrorRenderer
{
    private const array REDACTED_HEADERS = ['authorization', 'cookie', 'set-cookie', 'x-api-key', 'proxy-authorization'];

    public function render(Request $request, Throwable $exception): string
    {
        $class = $this->esc($exception::class);
        $safeMessage = SecretScrubber::scrub($exception->getMessage());
        $escapedMsg = $this->esc($safeMessage);
        $file = $exception->getFile();
        $line = $exception->getLine();

        $codeFrameHtml = $this->renderCodeFrame($file, $line);
        $diagnosticHtml = $this->renderDiagnostic($exception);
        $traceHtml = $this->renderTrace($exception);
        $requestHtml = $this->renderRequestSummary($request);

        $hanteiArg = str_replace('"', '\\"', sprintf('%s: %s at %s:%d', $exception::class, substr($safeMessage, 0, 160), basename($file), $line));
        $hanteiCmd = $this->esc(sprintf('php bin/kongo hantei debug "%s"', $hanteiArg));

        return <<<HTML
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Kongono Error — {$class}</title>
  <style>
    :root { font-family: ui-sans-serif, system-ui, -apple-system, sans-serif; color: #e2e8f0; background: #0f172a; }
    body { max-width: 1040px; margin: 0 auto; padding: 2rem 1.25rem 4rem; line-height: 1.5; }
    .banner { background: linear-gradient(135deg, #450a0a, #1e1b4b); border: 1px solid #991b1b; border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; }
    .badge { display: inline-block; background: #dc2626; color: #fff; font-size: 0.75rem; font-weight: 700; padding: 0.2rem 0.6rem; border-radius: 999px; letter-spacing: 0.04em; text-transform: uppercase; }
    .class-name { font-family: ui-monospace, monospace; font-size: 0.95rem; color: #fca5a5; margin-top: 0.5rem; }
    h1 { margin: 0.35rem 0 0.75rem; font-size: 1.55rem; color: #fff; word-break: break-word; }
    .location { font-family: ui-monospace, monospace; font-size: 0.85rem; color: #cbd5e1; }
    .panel { background: #1e293b; border: 1px solid #334155; border-radius: 10px; margin-bottom: 1.25rem; overflow: hidden; }
    .panel-header { background: #0f172a; padding: 0.7rem 1rem; font-weight: 600; font-size: 0.85rem; color: #94a3b8; border-bottom: 1px solid #334155; display: flex; justify-content: space-between; }
    pre.code { margin: 0; padding: 0.75rem 0; overflow-x: auto; font-family: ui-monospace, SFMono-Regular, monospace; font-size: 0.85rem; }
    .code-line { display: flex; padding: 0 1rem; }
    .code-line.highlight { background: rgba(239, 68, 68, 0.22); border-left: 3px solid #ef4444; }
    .ln { width: 3.5rem; color: #64748b; user-select: none; text-align: right; padding-right: 1rem; flex-shrink: 0; }
    .src { white-space: pre; color: #e2e8f0; }
    .hantei-box { background: #0c4a6e; border: 1px solid #0284c7; border-radius: 10px; padding: 1rem 1.25rem; margin-bottom: 1.25rem; }
    .hantei-box code { display: block; margin-top: 0.45rem; background: #082f49; padding: 0.6rem 0.85rem; border-radius: 6px; font-family: ui-monospace, monospace; font-size: 0.82rem; color: #7dd3fc; overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; font-size: 0.84rem; }
    th, td { text-align: left; padding: 0.55rem 1rem; border-bottom: 1px solid #334155; vertical-align: top; }
    th { color: #94a3b8; font-weight: 600; width: 180px; }
    .frame-app { color: #f8fafc; }
    .frame-vendor { color: #64748b; }
    .mono { font-family: ui-monospace, monospace; }
  </style>
</head>
<body>
  <section class="banner">
    <span class="badge">Kongono Dev Exception</span>
    <div class="class-name">{$class}</div>
    <h1>{$escapedMsg}</h1>
    <div class="location">{$this->esc($file)}:{$line}</div>
  </section>

  <section class="hantei-box">
    <strong>Ask Hantei (Built-in AI Debugger) to diagnose &amp; fix this:</strong>
    <code>{$hanteiCmd}</code>
  </section>

  {$diagnosticHtml}
  {$codeFrameHtml}
  {$traceHtml}
  {$requestHtml}
</body>
</html>
HTML;
    }

    private function renderCodeFrame(string $file, int $errorLine): string
    {
        if (!is_file($file)) {
            return '';
        }
        $lines = @file($file, FILE_IGNORE_NEW_LINES);
        if (false === $lines) {
            return '';
        }

        $start = max(1, $errorLine - 6);
        $end = min(count($lines), $errorLine + 6);
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

        return sprintf(
            '<section class="panel"><div class="panel-header"><span>Source Context</span><span class="mono">%s:%d</span></div><pre class="code">%s</pre></section>',
            $this->esc(basename($file)),
            $errorLine,
            implode("\n", $rows),
        );
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

        return '<section class="panel"><div class="panel-header"><span>Structured Kongono Diagnostic (Safe Context)</span></div><table>' . implode('', $rows) . '</table></section>';
    }

    private function renderTrace(Throwable $exception): string
    {
        $rows = [];
        foreach ($exception->getTrace() as $index => $frame) {
            $file = is_string($frame['file'] ?? null) ? $frame['file'] : '[internal]';
            $line = is_int($frame['line'] ?? null) ? (string) $frame['line'] : '-';
            $call = (is_string($frame['class'] ?? null) ? $frame['class'] . ($frame['type'] ?? '::') : '')
                . $frame['function'] . '()';
            $isVendor = str_contains($file, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR);
            $cls = $isVendor ? 'frame-vendor' : 'frame-app';
            $rows[] = sprintf(
                '<tr class="%s"><td class="mono">#%d</td><td class="mono">%s</td><td class="mono">%s:%s</td></tr>',
                $cls,
                $index,
                $this->esc($call),
                $this->esc($file),
                $line,
            );
        }

        return '<section class="panel"><div class="panel-header"><span>Stack Trace</span></div><table>' . implode('', $rows) . '</table></section>';
    }

    private function renderRequestSummary(Request $request): string
    {
        $rows = [
            sprintf('<tr><th>Method &amp; Path</th><td class="mono">%s %s</td></tr>', $this->esc($request->method()), $this->esc($request->path())),
        ];
        foreach ($request->getHeaders() as $name => $values) {
            $val = in_array(strtolower($name), self::REDACTED_HEADERS, true)
                ? '[REDACTED]'
                : SecretScrubber::scrub(implode(', ', $values));
            $rows[] = sprintf('<tr><th>%s</th><td class="mono">%s</td></tr>', $this->esc($name), $this->esc($val));
        }

        return '<section class="panel"><div class="panel-header"><span>Request Context (Secrets Redacted)</span></div><table>' . implode('', $rows) . '</table></section>';
    }

    private function esc(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
