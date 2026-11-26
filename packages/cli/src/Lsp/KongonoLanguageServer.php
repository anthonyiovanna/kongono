<?php

declare(strict_types=1);

namespace Kongono\Cli\Lsp;

use Kongono\Cli\Context\ProjectContext;
use Kongono\Cli\Diagnostics\AppAutoFixer;
use Kongono\Cli\Diagnostics\AppFinding;
use Kongono\Cli\Diagnostics\KongonoAppInspector;
use Kongono\Http\App;
use Throwable;

/**
 * Zero-dependency Kongono Language Server Protocol (LSP 3.17) server (`php bin/kongo lsp`).
 *
 * Powers live red/yellow squiggles, plain-English 5W1H hover tooltips, and one-click Quick Fixes
 * in VS Code, Cursor, Neovim, Zed, and PhpStorm directly from `KongonoAppInspector` + `AppAutoFixer`,
 * keeping 100% of Kongono's framework intelligence inside Kongono itself.
 */
final class KongonoLanguageServer
{
    private const int MAX_MESSAGE_BYTES = 4_194_304;
    private const int MAX_HEADER_LINE_BYTES = 8192;
    private const int MAX_HEADER_COUNT = 32;
    private readonly string $appRoot;
    private ?App $loadedApp;
    private readonly KongonoAppInspector $inspector;
    private readonly AppAutoFixer $fixer;
    private bool $shutdownRequested = false;

    /** @var array<string, array{rel: string, text: string, findings: list<AppFinding>}> */
    private array $documents = [];

    public function __construct(string $appRoot, ?App $loadedApp = null, ?bool $allowBootstrap = null)
    {
        $real = realpath($appRoot);
        $this->appRoot = rtrim(false !== $real ? $real : $appRoot, '\\/');
        $bootstrapAllowed = $allowBootstrap ?? !ProjectContext::isSafeModeEnabled();
        $this->loadedApp = $loadedApp ?? ($bootstrapAllowed ? $this->tryLoadApp($this->appRoot) : null);
        $this->inspector = new KongonoAppInspector();
        $this->fixer = new AppAutoFixer();
    }

    /**
     * Run the LSP Content-Length framed stdio loop on arbitrary readable/writable streams.
     *
     * @param resource $input
     * @param resource $output
     */
    public function serveStreams($input, $output): int
    {
        while (!feof($input)) {
            $payload = $this->readMessage($input);
            if (null === $payload) {
                break;
            }

            $decoded = json_decode($payload, true);
            if (!is_array($decoded)) {
                continue;
            }

            /** @var array<string, mixed> $decoded */
            $method = is_string($decoded['method'] ?? null) ? $decoded['method'] : '';
            if ('exit' === $method) {
                return $this->shutdownRequested ? 0 : 1;
            }

            foreach ($this->handleMessage($decoded) as $reply) {
                $this->writeMessage($output, $reply);
            }
        }

        return 0;
    }

    /**
     * Handle a single decoded JSON-RPC 2.0 LSP message and return outbound responses/notifications.
     *
     * @param array<string, mixed> $message
     * @return list<array<string, mixed>>
     */
    public function handleMessage(array $message): array
    {
        $method = is_string($message['method'] ?? null) ? $message['method'] : '';
        $hasId = array_key_exists('id', $message);
        $id = $message['id'] ?? null;
        $params = is_array($message['params'] ?? null) ? $message['params'] : [];

        return match ($method) {
            'initialize' => [[
                'jsonrpc' => '2.0',
                'id' => $id,
                'result' => [
                    'capabilities' => [
                        'textDocumentSync' => [
                            'openClose' => true,
                            'change' => 1, // Full document sync
                            'save' => ['includeText' => true],
                        ],
                        'hoverProvider' => true,
                        'codeActionProvider' => [
                            'codeActionKinds' => ['quickfix'],
                        ],
                    ],
                    'serverInfo' => [
                        'name' => 'kongono-lsp',
                        'version' => '1.22.0',
                    ],
                ],
            ]],
            'initialized' => [],
            'shutdown' => $this->handleShutdown($id),
            'textDocument/didOpen' => $this->handleDidOpen($params),
            'textDocument/didChange' => $this->handleDidChange($params),
            'textDocument/didSave' => $this->handleDidSave($params),
            'textDocument/didClose' => $this->handleDidClose($params),
            'textDocument/hover' => $hasId ? [$this->handleHover($id, $params)] : [],
            'textDocument/codeAction' => $hasId ? [$this->handleCodeAction($id, $params)] : [],
            default => $hasId ? [[
                'jsonrpc' => '2.0',
                'id' => $id,
                'result' => null,
            ]] : [],
        };
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function handleShutdown(mixed $id): array
    {
        $this->shutdownRequested = true;

        return [[
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => null,
        ]];
    }

    /**
     * @param array<mixed> $params
     * @return list<array<string, mixed>>
     */
    private function handleDidOpen(array $params): array
    {
        $doc = is_array($params['textDocument'] ?? null) ? $params['textDocument'] : [];
        $uri = is_string($doc['uri'] ?? null) ? $doc['uri'] : '';
        $text = is_string($doc['text'] ?? null) ? $doc['text'] : '';
        if ('' === $uri) {
            return [];
        }

        return [$this->diagnoseAndPublish($uri, $text)];
    }

    /**
     * @param array<mixed> $params
     * @return list<array<string, mixed>>
     */
    private function handleDidChange(array $params): array
    {
        $doc = is_array($params['textDocument'] ?? null) ? $params['textDocument'] : [];
        $uri = is_string($doc['uri'] ?? null) ? $doc['uri'] : '';
        $changes = is_array($params['contentChanges'] ?? null) ? $params['contentChanges'] : [];
        if ('' === $uri || [] === $changes) {
            return [];
        }

        $last = $changes[count($changes) - 1];
        $text = is_array($last) && is_string($last['text'] ?? null) ? $last['text'] : null;
        if (null === $text) {
            return [];
        }

        return [$this->diagnoseAndPublish($uri, $text)];
    }

    /**
     * @param array<mixed> $params
     * @return list<array<string, mixed>>
     */
    private function handleDidSave(array $params): array
    {
        $doc = is_array($params['textDocument'] ?? null) ? $params['textDocument'] : [];
        $uri = is_string($doc['uri'] ?? null) ? $doc['uri'] : '';
        if ('' === $uri) {
            return [];
        }

        $text = is_string($params['text'] ?? null) ? $params['text'] : null;
        if (null === $text) {
            $path = $this->uriToPath($uri);
            $safePath = null !== $path ? $this->resolveContainedWorkspaceFile($path) : null;
            if (null !== $safePath) {
                $raw = file_get_contents($safePath);
                $text = false !== $raw ? $raw : '';
            } else {
                $text = $this->documents[$uri]['text'] ?? '';
            }
        }

        return [$this->diagnoseAndPublish($uri, $text)];
    }

    /**
     * @param array<mixed> $params
     * @return list<array<string, mixed>>
     */
    private function handleDidClose(array $params): array
    {
        $doc = is_array($params['textDocument'] ?? null) ? $params['textDocument'] : [];
        $uri = is_string($doc['uri'] ?? null) ? $doc['uri'] : '';
        if ('' === $uri) {
            return [];
        }

        unset($this->documents[$uri]);

        return [[
            'jsonrpc' => '2.0',
            'method' => 'textDocument/publishDiagnostics',
            'params' => [
                'uri' => $uri,
                'diagnostics' => [],
            ],
        ]];
    }

    /**
     * Returns a rich Markdown 5W1H hover tooltip when hovering over a line with Kongono findings.
     *
     * @param array<mixed> $params
     * @return array<string, mixed>
     */
    private function handleHover(mixed $id, array $params): array
    {
        $doc = is_array($params['textDocument'] ?? null) ? $params['textDocument'] : [];
        $pos = is_array($params['position'] ?? null) ? $params['position'] : [];
        $uri = is_string($doc['uri'] ?? null) ? $doc['uri'] : '';
        $line0 = is_int($pos['line'] ?? null) ? $pos['line'] : -1;

        $entry = $this->documents[$uri] ?? null;
        if (null === $entry || $line0 < 0) {
            return ['jsonrpc' => '2.0', 'id' => $id, 'result' => null];
        }

        $targetLine = $line0 + 1;
        $sections = [];
        foreach ($entry['findings'] as $f) {
            if (($f->line ?? 1) !== $targetLine) {
                continue;
            }
            $md = [
                sprintf('### Kongono `%s`', $f->code),
                sprintf('**What:** %s', $f->what),
                sprintf('**Why:** %s', $f->why),
                sprintf('**Who:** %s', $f->who),
            ];
            if ([] !== $f->didYouMean) {
                $md[] = sprintf('**Did you mean:** `%s`', implode('`, `', $f->didYouMean));
            }
            if ([] !== $f->howToFix) {
                $md[] = '**How to fix:**';
                foreach ($f->howToFix as $step) {
                    $md[] = '- ' . $step;
                }
            }
            if (null !== $f->codeFix && '' !== $f->codeFix) {
                $md[] = "```html\n" . $f->codeFix . "\n```";
            }
            if (null !== $f->command && '' !== $f->command) {
                $md[] = sprintf('**Run:** `%s`', $f->command);
            }
            $sections[] = implode("\n\n", $md);
        }

        if ([] === $sections) {
            return ['jsonrpc' => '2.0', 'id' => $id, 'result' => null];
        }

        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => [
                'contents' => [
                    'kind' => 'markdown',
                    'value' => implode("\n\n---\n\n", $sections),
                ],
            ],
        ];
    }

    /**
     * Returns LSP Quick Fix CodeActions backed directly by `AppAutoFixer`.
     *
     * @param array<mixed> $params
     * @return array<string, mixed>
     */
    private function handleCodeAction(mixed $id, array $params): array
    {
        $doc = is_array($params['textDocument'] ?? null) ? $params['textDocument'] : [];
        $range = is_array($params['range'] ?? null) ? $params['range'] : [];
        $uri = is_string($doc['uri'] ?? null) ? $doc['uri'] : '';

        $entry = $this->documents[$uri] ?? null;
        if (null === $entry) {
            return ['jsonrpc' => '2.0', 'id' => $id, 'result' => []];
        }

        $startLine = is_array($range['start'] ?? null) && is_int($range['start']['line'] ?? null)
            ? $range['start']['line'] + 1
            : 1;
        $endLine = is_array($range['end'] ?? null) && is_int($range['end']['line'] ?? null)
            ? $range['end']['line'] + 1
            : $startLine;

        $fixed = $this->fixer->fixContent($entry['rel'], $entry['text']);
        if (!$fixed['changed']) {
            return ['jsonrpc' => '2.0', 'id' => $id, 'result' => []];
        }

        $lines = explode("\n", str_replace("\r\n", "\n", $entry['text']));
        $actions = [];

        foreach ($fixed['applied'] as $fix) {
            if ($fix['line'] < $startLine || $fix['line'] > $endLine) {
                continue;
            }
            $line0 = $fix['line'] - 1;
            $origLine = $lines[$line0] ?? $fix['original'];
            $actions[] = [
                'title' => sprintf('Kongono Quick Fix [%s]: %s', $fix['code'], $fix['description']),
                'kind' => 'quickfix',
                'isPreferred' => true,
                'edit' => [
                    'changes' => [
                        $uri => [[
                            'range' => [
                                'start' => ['line' => $line0, 'character' => 0],
                                'end' => ['line' => $line0, 'character' => self::utf16Length($origLine)],
                            ],
                            'newText' => $fix['replacement'],
                        ]],
                    ],
                ],
            ];
        }

        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => $actions,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function diagnoseAndPublish(string $uri, string $text): array
    {
        $rel = $this->uriToRelativePath($uri);
        $findings = $this->inspector->inspectBuffer($this->appRoot, $rel, $text, $this->loadedApp);
        $this->documents[$uri] = [
            'rel' => $rel,
            'text' => $text,
            'findings' => $findings,
        ];

        $lines = explode("\n", str_replace("\r\n", "\n", $text));
        $diagnostics = [];
        foreach ($findings as $f) {
            $line0 = max(0, ($f->line ?? 1) - 1);
            $lineText = $lines[$line0] ?? '';
            $leadingWs = substr($lineText, 0, strlen($lineText) - strlen(ltrim($lineText)));
            $startChar = self::utf16Length($leadingWs);
            $endChar = max($startChar + 1, self::utf16Length(rtrim($lineText)));

            $msgParts = [
                $f->what,
                'Why: ' . $f->why,
            ];
            if ([] !== $f->howToFix) {
                $msgParts[] = 'Fix: ' . $f->howToFix[0];
            }
            if ([] !== $f->didYouMean) {
                $msgParts[] = 'Did you mean: ' . implode(', ', $f->didYouMean);
            }

            $diagnostics[] = [
                'range' => [
                    'start' => ['line' => $line0, 'character' => $startChar],
                    'end' => ['line' => $line0, 'character' => $endChar],
                ],
                'severity' => 'error' === $f->severity ? 1 : 2, // 1 = Error, 2 = Warning
                'code' => $f->code,
                'source' => 'Kongono',
                'message' => implode("\n", $msgParts),
            ];
        }

        return [
            'jsonrpc' => '2.0',
            'method' => 'textDocument/publishDiagnostics',
            'params' => [
                'uri' => $uri,
                'diagnostics' => $diagnostics,
            ],
        ];
    }

    /** @param resource $input */
    private function readMessage($input): ?string
    {
        $contentLength = null;
        $headerLines = 0;
        while (true) {
            $line = fgets($input, self::MAX_HEADER_LINE_BYTES);
            if (false === $line) {
                return null;
            }
            if (strlen($line) >= self::MAX_HEADER_LINE_BYTES - 1 && !str_ends_with($line, "\n")) {
                return null;
            }
            $trimmed = trim($line);
            if ('' === $trimmed) {
                break;
            }
            if (++$headerLines > self::MAX_HEADER_COUNT) {
                return null;
            }
            if (1 === preg_match('/\AContent-Length:\s*(\d+)\z/i', $trimmed, $m)) {
                $contentLength = (int) $m[1];
            }
        }

        if (null === $contentLength || $contentLength <= 0 || $contentLength > self::MAX_MESSAGE_BYTES) {
            return null;
        }

        $buffer = '';
        while (($remaining = $contentLength - strlen($buffer)) > 0) {
            $chunk = fread($input, $remaining);
            if (false === $chunk || '' === $chunk) {
                return null;
            }
            $buffer .= $chunk;
        }

        return $buffer;
    }

    /**
     * @param resource             $output
     * @param array<string, mixed> $payload
     */
    private function writeMessage($output, array $payload): void
    {
        $json = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $header = sprintf("Content-Length: %d\r\n\r\n", strlen($json));
        fwrite($output, $header . $json);
        fflush($output);
    }

    private function uriToRelativePath(string $uri): string
    {
        $path = $this->uriToPath($uri);
        if (null === $path) {
            return ltrim($uri, '/');
        }
        $norm = str_replace('\\', '/', $path);
        $base = rtrim(str_replace('\\', '/', $this->appRoot), '/') . '/';
        $matchesPrefix = '\\' === DIRECTORY_SEPARATOR
            ? str_starts_with(strtolower($norm), strtolower($base))
            : str_starts_with($norm, $base);
        if ($matchesPrefix) {
            return substr($norm, strlen($base));
        }

        return basename($norm);
    }

    private function uriToPath(string $uri): ?string
    {
        if (!str_starts_with(strtolower($uri), 'file://')) {
            return null;
        }
        $raw = rawurldecode(substr($uri, strlen('file://')));
        // Windows `file:///C:/Users/...` starts with `/C:/`
        if (1 === preg_match('#\A/([a-zA-Z]:/)#', $raw)) {
            return substr($raw, 1);
        }

        return $raw;
    }

    private function resolveContainedWorkspaceFile(string $path): ?string
    {
        if (str_contains($path, "\0") || is_link($path)) {
            return null;
        }
        $real = realpath($path);
        if (false === $real || !is_file($real)) {
            return null;
        }
        $prefix = rtrim($this->appRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $normReal = str_replace('\\', '/', $real);
        $normPrefix = str_replace('\\', '/', $prefix);
        $isContained = '\\' === DIRECTORY_SEPARATOR
            ? str_starts_with(strtolower($normReal), strtolower($normPrefix))
            : str_starts_with($normReal, $normPrefix);
        if (!$isContained) {
            return null;
        }

        $relRaw = substr($normReal, strlen($normPrefix));
        $walk = $this->appRoot;
        foreach (explode('/', $relRaw) as $seg) {
            if ('' === $seg) {
                continue;
            }
            $walk .= DIRECTORY_SEPARATOR . $seg;
            if (is_link($walk)) {
                return null;
            }
        }

        $rel = strtolower($relRaw);
        $base = strtolower(basename($normReal));
        if (
            ((str_starts_with($base, '.env') || str_ends_with($base, '.env')) && '.env.example' !== $base)
            || in_array($base, ['id_rsa', 'id_dsa', 'id_ed25519', 'id_ecdsa', 'id_ecdsa_sk', 'id_ed25519_sk', '.netrc', '.npmrc', '.pypirc', '.htpasswd', '.pgpass', 'auth.json', 'credentials.json', 'secrets.json', 'service-account.json'], true)
            || 1 === preg_match('/\.(pem|key|p12|pfx|jks|kdbx|ppk|pk8|db|sqlite3?(?:-(?:wal|shm))?)\z/', $base)
            || '.git' === $rel
            || str_starts_with($rel, '.git/')
            || str_contains($rel, '/.git/')
            || str_starts_with($rel, '.ssh/')
            || str_contains($rel, '/.ssh/')
            || str_starts_with($rel, '.aws/')
            || str_contains($rel, '/.aws/')
            || '.docker/config.json' === $rel
            || str_contains($rel, '/.docker/config.json')
            || str_starts_with($rel, 'var/cache/')
            || str_starts_with($rel, 'var/storage/')
            || str_starts_with($rel, 'storage/dev/')
            || str_starts_with($rel, 'storage/incidents/')
            || 1 === preg_match('#/(?:var/(?:cache|storage)|storage/(?:dev|incidents))/#', '/' . $rel)
        ) {
            return null;
        }

        return $real;
    }

    private function tryLoadApp(string $appRoot): ?App
    {
        $appDir = $appRoot . DIRECTORY_SEPARATOR . 'app';
        $bootstrap = $appDir . DIRECTORY_SEPARATOR . 'bootstrap.php';
        if (is_link($appDir) || is_link($bootstrap) || !is_file($bootstrap)) {
            return null;
        }
        $realBootstrap = realpath($bootstrap);
        $prefix = rtrim($appRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $isContained = false !== $realBootstrap && (
            '\\' === DIRECTORY_SEPARATOR
                ? str_starts_with(strtolower($realBootstrap), strtolower($prefix))
                : str_starts_with($realBootstrap, $prefix)
        );
        if (!$isContained) {
            return null;
        }

        try {
            $loaded = require $realBootstrap;

            return $loaded instanceof App ? $loaded : null;
        } catch (Throwable) {
            return null;
        }
    }

    /** Compute LSP 3.17 UTF-16 code-unit length for accurate character ranges on multibyte/emoji lines. */
    private static function utf16Length(string $text): int
    {
        if ('' === $text) {
            return 0;
        }

        return intdiv(strlen(mb_convert_encoding($text, 'UTF-16LE', 'UTF-8')), 2);
    }
}
