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
 * files in a cache directory, and isolates every render call with zero shared state.
 */
final readonly class ViewEngine
{
    private string $viewsPath;
    private string $cachePath;
    private TemplateCompiler $compiler;

    /**
     * @param ?Closure(string, array<string, string|int|float>, ?string): string $translator
     */
    public function __construct(
        string $viewsPath,
        ?string $cachePath = null,
        private string $extension = '.kgn.html',
        private ?Closure $translator = null,
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
        return new self($this->viewsPath, $this->cachePath, $this->extension, $translator);
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

    /** @param array<string, mixed> $data */
    private function evaluate(string $template, array $data, RenderState $state): string
    {
        $sourcePath = $this->resolveTemplatePath($template);
        $compiledPath = $this->ensureCompiled($template, $sourcePath);

        return $this->executeCompiled($compiledPath, $data, $state);
    }

    private function resolveTemplatePath(string $template): string
    {
        $normalized = ltrim(str_replace('\\', '/', trim($template)), '/');
        if ('' === $normalized || str_contains($normalized, '..') || str_contains($normalized, "\0")) {
            throw new ViewException(sprintf('Invalid template name "%s".', $template));
        }

        $candidate = $this->viewsPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalized) . $this->extension;
        $real = realpath($candidate);
        if (false === $real || !is_file($real)) {
            throw new ViewException(sprintf('Template "%s" not found.', $template));
        }

        $prefix = rtrim($this->viewsPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (!str_starts_with($real, $prefix)) {
            throw new ViewException(sprintf('Template "%s" escapes views directory.', $template));
        }

        return $real;
    }

    private function ensureCompiled(string $template, string $sourcePath): string
    {
        if (!is_dir($this->cachePath) && !@mkdir($this->cachePath, 0o775, true) && !is_dir($this->cachePath)) {
            throw new ViewException(sprintf('Unable to create view cache directory "%s".', $this->cachePath));
        }

        $cacheFile = $this->cachePath . DIRECTORY_SEPARATOR . hash('sha256', $template . '|' . $sourcePath) . '.php';
        $sourceMtime = filemtime($sourcePath);
        $cacheMtime = is_file($cacheFile) ? filemtime($cacheFile) : false;

        if (false === $cacheMtime || false === $sourceMtime || $cacheMtime < $sourceMtime) {
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
        }

        return $cacheFile;
    }

    /** @param array<string, mixed> $__data */
    private function executeCompiled(string $__compiledPath, array $__data, RenderState $__env): string
    {
        $level = ob_get_level();
        ob_start();

        try {
            (static function (string $__file, array $__vars, RenderState $__env): void {
                extract($__vars, EXTR_SKIP);
                require $__file;
            })($__compiledPath, $__data, $__env);

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
