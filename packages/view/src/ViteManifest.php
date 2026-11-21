<?php

declare(strict_types=1);

namespace Kongono\View;

use JsonException;

/**
 * Zero-dependency Vite 5/6/7 asset resolver supporting both hot-reload dev servers (`public/hot`)
 * and hashed production manifests (`public/build/manifest.json`) with CSS & modulepreload tags.
 */
final readonly class ViteManifest
{
    public function __construct(
        private string $publicPath,
        private string $buildDirectory = 'build',
        private string $manifestFilename = 'manifest.json',
        private string $hotFilename = 'hot',
    ) {}

    /**
     * Render HTML `<link>` and `<script type="module">` tags for one or more Vite entry points.
     *
     * @param string|list<string> $entries
     */
    public function tags(string|array $entries): string
    {
        $entryList = is_array($entries) ? $entries : [$entries];
        if ([] === $entryList) {
            return '';
        }

        $hotUrl = $this->readHotUrl();
        if (null !== $hotUrl) {
            return $this->renderDevTags($hotUrl, $entryList);
        }

        return $this->renderProductionTags($entryList);
    }

    /** Resolve the public URL path for a single built Vite asset entry. */
    public function asset(string $entry): string
    {
        $hotUrl = $this->readHotUrl();
        if (null !== $hotUrl) {
            return $hotUrl . '/' . ltrim($entry, '/');
        }

        $manifest = $this->loadManifest();
        $chunk = $manifest[$entry] ?? null;
        if (!is_array($chunk) || !is_string($chunk['file'] ?? null)) {
            throw new ViewException(sprintf('Vite entry "%s" not found in manifest.', $entry));
        }

        return '/' . trim($this->buildDirectory, '/') . '/' . ltrim($chunk['file'], '/');
    }

    /** @param list<string> $entries */
    private function renderDevTags(string $hotUrl, array $entries): string
    {
        $tags = [
            sprintf('<script type="module" src="%s/@vite/client"></script>', $this->esc($hotUrl)),
        ];

        foreach ($entries as $entry) {
            $url = $hotUrl . '/' . ltrim($entry, '/');
            if ($this->isCssEntry($entry)) {
                $tags[] = sprintf('<link rel="stylesheet" href="%s">', $this->esc($url));
            } else {
                $tags[] = sprintf('<script type="module" src="%s"></script>', $this->esc($url));
            }
        }

        return implode("\n", $tags);
    }

    /** @param list<string> $entries */
    private function renderProductionTags(array $entries): string
    {
        $manifest = $this->loadManifest();
        $prefix = '/' . trim($this->buildDirectory, '/') . '/';
        $styles = [];
        $preloads = [];
        $scripts = [];

        foreach ($entries as $entry) {
            $chunk = $manifest[$entry] ?? null;
            if (!is_array($chunk) || !is_string($chunk['file'] ?? null)) {
                throw new ViewException(sprintf('Vite entry "%s" not found in manifest.', $entry));
            }

            $fileUrl = $prefix . ltrim($chunk['file'], '/');
            if ($this->isCssEntry($chunk['file'])) {
                $styles[$fileUrl] = sprintf('<link rel="stylesheet" href="%s">', $this->esc($fileUrl));
            } else {
                $scripts[$fileUrl] = sprintf('<script type="module" src="%s"></script>', $this->esc($fileUrl));
            }

            if (is_array($chunk['css'] ?? null)) {
                foreach ($chunk['css'] as $cssFile) {
                    if (is_string($cssFile)) {
                        $cssUrl = $prefix . ltrim($cssFile, '/');
                        $styles[$cssUrl] = sprintf('<link rel="stylesheet" href="%s">', $this->esc($cssUrl));
                    }
                }
            }

            if (is_array($chunk['imports'] ?? null)) {
                foreach ($chunk['imports'] as $importKey) {
                    if (!is_string($importKey)) {
                        continue;
                    }
                    $imported = $manifest[$importKey] ?? null;
                    if (!is_array($imported) || !is_string($imported['file'] ?? null)) {
                        continue;
                    }
                    $impUrl = $prefix . ltrim($imported['file'], '/');
                    $preloads[$impUrl] = sprintf('<link rel="modulepreload" href="%s">', $this->esc($impUrl));
                    if (is_array($imported['css'] ?? null)) {
                        foreach ($imported['css'] as $impCss) {
                            if (is_string($impCss)) {
                                $cssUrl = $prefix . ltrim($impCss, '/');
                                $styles[$cssUrl] = sprintf('<link rel="stylesheet" href="%s">', $this->esc($cssUrl));
                            }
                        }
                    }
                }
            }
        }

        return implode("\n", [...array_values($styles), ...array_values($preloads), ...array_values($scripts)]);
    }

    private function readHotUrl(): ?string
    {
        $hotPath = rtrim($this->publicPath, '/\\') . DIRECTORY_SEPARATOR . $this->hotFilename;
        if (!is_file($hotPath)) {
            return null;
        }
        $raw = file_get_contents($hotPath);
        if (false === $raw) {
            return null;
        }
        $trimmed = rtrim(trim($raw), '/');

        return '' !== $trimmed ? $trimmed : null;
    }

    /** @return array<string, mixed> */
    private function loadManifest(): array
    {
        $base = rtrim($this->publicPath, '/\\') . DIRECTORY_SEPARATOR . trim($this->buildDirectory, '/\\');
        $candidates = [
            $base . DIRECTORY_SEPARATOR . $this->manifestFilename,
            $base . DIRECTORY_SEPARATOR . '.vite' . DIRECTORY_SEPARATOR . $this->manifestFilename,
        ];

        $manifestPath = null;
        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                $manifestPath = $candidate;
                break;
            }
        }

        if (null === $manifestPath) {
            throw new ViewException(sprintf('Vite manifest not found in "%s". Run `npm run build` or start Vite dev server.', $base));
        }

        $raw = file_get_contents($manifestPath);
        if (false === $raw) {
            throw new ViewException(sprintf('Unable to read Vite manifest "%s".', $manifestPath));
        }

        try {
            $decoded = json_decode($raw, true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new ViewException('Invalid JSON in Vite manifest.', previous: $e);
        }

        if (!is_array($decoded)) {
            throw new ViewException('Unexpected Vite manifest structure.');
        }

        /** @var array<string, mixed> $typed */
        $typed = $decoded;

        return $typed;
    }

    private function isCssEntry(string $path): bool
    {
        return 1 === preg_match('/\.(css|less|sass|scss|styl|stylus|pcss|postcss)\z/i', $path);
    }

    private function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
