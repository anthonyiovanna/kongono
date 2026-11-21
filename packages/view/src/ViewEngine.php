<?php

declare(strict_types=1);

namespace Kongono\View;

use Closure;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Throwable;

/**
 * Compiled HTML template engine for Kongono v1 (`.kgn.html`).
 *
 * Contained within one views root directory, compiles templates to opcache-friendly PHP
 * closure scripts in a cache directory, and isolates every render call with zero shared
 * request state. Set `autoReload: false` in production persistent workers (FrankenPHP,
 * RoadRunner, `kongo serve`) to keep compiled closures hot in memory with zero per-request
 * filesystem checks.
 */
final class ViewEngine
{
    private const string CACHE_VERSION = 'v2';

    private readonly string $viewsPath;
    private readonly string $cachePath;
    private readonly TemplateCompiler $compiler;

    /** @var array<string, Closure(array<string, mixed>, RenderState): void> */
    private array $compiledRenderers = [];

    /**
     * @param ?Closure(string, array<string, string|int|float>, ?string): string $translator
     */
    public function __construct(
        string $viewsPath,
        ?string $cachePath = null,
        private readonly string $extension = '.kgn.html',
        private readonly ?Closure $translator = null,
        private readonly ?ViteManifest $vite = null,
        private readonly bool $autoReload = true,
    ) {
        $resolvedViews = realpath($viewsPath);
        if (false === $resolvedViews || !is_dir($resolvedViews)) {
            throw new ViewException(sprintf('Views directory does not exist: "%s".', $viewsPath));
        }
        $this->viewsPath = $resolvedViews;
        $this->cachePath = $cachePath ?? (sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kongono-views-' . hash('xxh128', $resolvedViews));
        $this->compiler = new TemplateCompiler();
    }

    /**
     * Return a new ViewEngine bound to a translation callback (e.g. `$translator->get(...)`).
     *
     * @param Closure(string, array<string, string|int|float>, ?string): string $translator
     */
    public function withTranslator(Closure $translator): self
    {
        return new self($this->viewsPath, $this->cachePath, $this->extension, $translator, $this->vite, $this->autoReload);
    }

    /** Return a new ViewEngine bound to a ViteManifest for `@vite(...)` directive rendering. */
    public function withVite(ViteManifest $vite): self
    {
        return new self($this->viewsPath, $this->cachePath, $this->extension, $this->translator, $vite, $this->autoReload);
    }

    /**
     * Return a new ViewEngine with auto-reload enabled (dev) or disabled (production workers).
     *
     * When disabled, compiled template closures are cached in memory per ViewEngine instance so
     * repeated renders skip `realpath()`, `filemtime()`, and `require` overhead.
     */
    public function withAutoReload(bool $autoReload = true): self
    {
        return new self($this->viewsPath, $this->cachePath, $this->extension, $this->translator, $this->vite, $autoReload);
    }

    public function isAutoReload(): bool
    {
        return $this->autoReload;
    }

    /** @param string|list<string> $entries */
    public function viteTags(string|array $entries): string
    {
        if (null === $this->vite) {
            throw new ViewException('ViteManifest is not configured on ViewEngine. Call $views->withVite(new ViteManifest(...)).');
        }

        return $this->vite->tags($entries);
    }

    /** @param array<string, string|int|float> $params */
    public function translate(string $key, array $params = [], ?string $locale = null): string
    {
        if (null !== $this->translator) {
            return ($this->translator)($key, $params, $locale);
        }

        return $key;
    }

    /**
     * Render a logical template (e.g. 'notes/index') into an HTML string.
     *
     * @param array<string, mixed> $data
     */
    public function render(string $template, array $data = []): string
    {
        $state = new RenderState($this);
        $content = $this->evaluate($template, $data, $state);

        $layout = $state->layout();
        if (null !== $layout) {
            $layoutState = new RenderState($this);
            $layoutState->inheritSections($state->sections());

            return $this->evaluate($layout, $data, $layoutState);
        }

        return $content;
    }

    /**
     * List all available logical template names for ApplicationGraph introspection.
     *
     * @return list<string>
     */
    public function listTemplates(): array
    {
        $templates = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->viewsPath));

        foreach ($iterator as $file) {
            if (!$file instanceof SplFileInfo || !$file->isFile()) {
                continue;
            }
            $full = $file->getPathname();
            if (!str_ends_with($full, $this->extension)) {
                continue;
            }
            $relative = substr($full, strlen($this->viewsPath) + 1);
            $relative = str_replace('\\', '/', $relative);
            $templates[] = substr($relative, 0, -strlen($this->extension));
        }

        sort($templates, SORT_STRING);

        return $templates;
    }

    /**
     * Pre-compile all templates into the cache directory for zero-cold-start deployments.
     * When `autoReload` is false, also preloads compiled closures into memory.
     */
    public function warmCache(): int
    {
        $compiled = 0;
        foreach ($this->listTemplates() as $template) {
            $sourcePath = $this->resolveNormalizedTemplatePath($template, $template);
            $compiledPath = $this->ensureCompiled($template, $sourcePath, checkFreshness: true);
            if (!$this->autoReload) {
                $this->compiledRenderers[$template] = $this->loadCompiledRenderer($compiledPath, $template);
            }
            ++$compiled;
        }

        return $compiled;
    }

    /** @param array<string, mixed> $data */
    private function evaluate(string $template, array $data, RenderState $state): string
    {
        if (!$this->autoReload) {
            $cached = $this->compiledRenderers[$template] ?? null;
            if (null !== $cached) {
                return $this->executeRenderer($cached, $data, $state);
            }
        }

        $normalized = $this->normalizeTemplateName($template);
        if (!$this->autoReload) {
            $cached = $this->compiledRenderers[$normalized] ?? null;
            if (null !== $cached) {
                return $this->executeRenderer($cached, $data, $state);
            }
        }

        $sourcePath = $this->resolveNormalizedTemplatePath($normalized, $template);
        $compiledPath = $this->ensureCompiled($normalized, $sourcePath, checkFreshness: $this->autoReload);
        $renderer = $this->loadCompiledRenderer($compiledPath, $normalized);

        if (!$this->autoReload) {
            $this->compiledRenderers[$normalized] = $renderer;
        }

        return $this->executeRenderer($renderer, $data, $state);
    }

    private function normalizeTemplateName(string $template): string
    {
        $normalized = ltrim(str_replace('\\', '/', trim($template)), '/');
        if ('' === $normalized || str_contains($normalized, '..') || str_contains($normalized, "\0")) {
            throw new ViewException(sprintf('Invalid template name "%s".', $template));
        }

        return $normalized;
    }

    private function resolveNormalizedTemplatePath(string $normalized, string $original): string
    {
        $candidate = $this->viewsPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalized) . $this->extension;
        $real = realpath($candidate);
        if (false === $real || !is_file($real)) {
            throw new ViewException(sprintf('Template "%s" not found.', $original));
        }

        $prefix = rtrim($this->viewsPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (!str_starts_with($real, $prefix)) {
            throw new ViewException(sprintf('Template "%s" escapes views directory.', $original));
        }

        return $real;
    }

    private function ensureCompiled(string $template, string $sourcePath, bool $checkFreshness = true): string
    {
        $cacheFile = $this->cachePath . DIRECTORY_SEPARATOR . hash('sha256', self::CACHE_VERSION . '|' . $template . '|' . $sourcePath) . '.php';

        $needsCompile = !is_file($cacheFile);
        if (!$needsCompile && $checkFreshness) {
            $sourceMtime = filemtime($sourcePath);
            $cacheMtime = filemtime($cacheFile);
            $needsCompile = false === $cacheMtime || false === $sourceMtime || $cacheMtime < $sourceMtime;
        }

        if ($needsCompile) {
            if (!is_dir($this->cachePath) && !@mkdir($this->cachePath, 0o775, true) && !is_dir($this->cachePath)) {
                throw new ViewException(sprintf('Unable to create view cache directory "%s".', $this->cachePath));
            }

            $source = file_get_contents($sourcePath);
            if (false === $source) {
                throw new ViewException(sprintf('Failed to read template "%s".', $template));
            }
            $compiled = $this->compiler->compile($source);
            $tmp = $cacheFile . '.' . bin2hex(random_bytes(4)) . '.tmp';
            if (false === file_put_contents($tmp, $compiled, LOCK_EX)) {
                throw new ViewException(sprintf('Failed to write compiled template cache for "%s".', $template));
            }
            if (!@rename($tmp, $cacheFile)) {
                @unlink($cacheFile);
                if (!@rename($tmp, $cacheFile)) {
                    @unlink($tmp);
                    throw new ViewException(sprintf('Failed to publish compiled template cache for "%s".', $template));
                }
            }
            if (function_exists('opcache_invalidate')) {
                @opcache_invalidate($cacheFile, true);
            }
        }

        return $cacheFile;
    }

    /** @return Closure(array<string, mixed>, RenderState): void */
    private function loadCompiledRenderer(string $compiledPath, string $template): Closure
    {
        $renderer = require $compiledPath;
        if (!$renderer instanceof Closure) {
            throw new ViewException(sprintf('Compiled template cache for "%s" is invalid.', $template));
        }

        /** @var Closure(array<string, mixed>, RenderState): void $renderer */
        return $renderer;
    }

    /**
     * @param Closure(array<string, mixed>, RenderState): void $renderer
     * @param array<string, mixed>                             $data
     */
    private function executeRenderer(Closure $renderer, array $data, RenderState $state): string
    {
        $level = ob_get_level();
        ob_start();

        try {
            $renderer($data, $state);
            $output = ob_get_clean();

            return false === $output ? '' : $output;
        } catch (Throwable $failure) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
            throw $failure;
        }
    }
}
