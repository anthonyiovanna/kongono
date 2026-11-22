<?php

declare(strict_types=1);

namespace Kongono\View;

/** Operation-local rendering context for one template evaluation (zero shared mutable state). */
final class RenderState
{
    private ?string $layout = null;

    /** @var array<string, string> */
    private array $sections = [];

    /** @var list<string> */
    private array $sectionStack = [];

    private int $slotDepth = 0;

    /**
     * @param array<string, list<string>> $validationErrors
     * @param array<string, mixed>        $oldInput
     */
    public function __construct(
        private readonly ViewEngine $engine,
        private readonly array $validationErrors = [],
        private readonly array $oldInput = [],
    ) {}

    /** Retrieve a previously submitted form value (supports dot-notation like `shipping.city`). */
    public function old(string $field, mixed $default = ''): mixed
    {
        if (array_key_exists($field, $this->oldInput)) {
            return $this->oldInput[$field];
        }
        if (!str_contains($field, '.')) {
            return $default;
        }

        $cursor = $this->oldInput;
        foreach (explode('.', $field) as $segment) {
            if (!is_array($cursor) || !array_key_exists($segment, $cursor)) {
                return $default;
            }
            $cursor = $cursor[$segment];
        }

        return $cursor;
    }

    /** Return the first validation error message for `$field`, or `null`. */
    public function firstError(string $field): ?string
    {
        return $this->validationErrors[$field][0] ?? null;
    }

    public function hasError(string $field): bool
    {
        return null !== $this->firstError($field);
    }

    /** @return list<string> */
    public function fieldErrors(string $field): array
    {
        return $this->validationErrors[$field] ?? [];
    }

    public function escape(mixed $value): string
    {
        if (null === $value) {
            return '';
        }
        if (is_bool($value)) {
            return $value ? '1' : '';
        }
        if (!is_scalar($value) && !($value instanceof \Stringable)) {
            throw new ViewException(sprintf('Cannot interpolate non-stringable value of type %s in template.', get_debug_type($value)));
        }

        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /** Render untrusted/Markdown HTML strictly through the WHATWG HTML5 allowlist sanitizer. */
    public function safeHtml(mixed $value): string
    {
        if (null === $value) {
            return '';
        }
        if (!is_scalar($value) && !($value instanceof \Stringable)) {
            throw new ViewException(sprintf('Cannot render non-stringable value of type %s in @safeHtml.', get_debug_type($value)));
        }

        return HtmlSanitizer::sanitize((string) $value);
    }

    /** Encode an associative state array/DTO safely into a CSP-strict Kongono Live 2.5 `k-state="..."` attribute. */
    public function stateAttr(mixed $state): string
    {
        $json = json_encode(
            $state,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT,
        );

        return 'k-state="' . htmlspecialchars($json, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
    }

    /**
     * Render an Astro-style Hybrid Bridge Island mount node (`@island('PriceChart', $props, 'visible')`)
     * protected by `k-ignore` so Kongono Live 2.5 morphs push updated props without remounting React/Vue/Svelte.
     */
    public function island(string $component, mixed $props = [], string $hydrate = 'load', ?string $id = null): string
    {
        $clean = trim($component);
        if (
            '' === $clean
            || str_contains($clean, '..')
            || 1 !== preg_match('/\A[A-Za-z0-9][A-Za-z0-9_\/\-]{0,127}\z/', $clean)
        ) {
            throw new ViewException(sprintf('Invalid Bridge island component name "%s".', $component));
        }

        $strategy = strtolower(trim($hydrate));
        if (!in_array($strategy, ['load', 'visible', 'idle'], true)) {
            throw new ViewException(sprintf('Invalid island hydration strategy "%s". Allowed: load, visible, idle.', $hydrate));
        }

        $json = json_encode(
            $props,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT,
        );

        $idAttr = '';
        if (null !== $id && '' !== trim($id)) {
            $idAttr = ' id="' . htmlspecialchars(trim($id), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
        }

        return sprintf(
            '<div%s data-k-island="%s" data-k-props="%s" data-k-hydrate="%s" k-ignore=""></div>',
            $idAttr,
            htmlspecialchars($clean, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($json, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            $strategy,
        );
    }

    /** @param array<string, string|int|float> $params */
    public function translate(string $key, array $params = [], ?string $locale = null): string
    {
        return $this->escape($this->engine->translate($key, $params, $locale));
    }

    /** @param string|list<string> $entries */
    public function vite(string|array $entries): string
    {
        return $this->engine->viteTags($entries);
    }

    public function extend(string $layout): void
    {
        $this->layout = $layout;
    }

    public function layout(): ?string
    {
        return $this->layout;
    }

    public function setSection(string $name, string $content): void
    {
        $this->sections[$name] = $content;
    }

    public function startSection(string $name): void
    {
        $this->sectionStack[] = $name;
        ob_start();
    }

    public function endSection(): void
    {
        $name = array_pop($this->sectionStack);
        if (null === $name) {
            throw new ViewException('Unexpected @endsection without matching @section.');
        }
        $content = ob_get_clean();
        $this->sections[$name] = false === $content ? '' : $content;
    }

    public function yieldSection(string $name, string $default = ''): string
    {
        return $this->sections[$name] ?? $default;
    }

    public function startSlot(): void
    {
        ++$this->slotDepth;
        ob_start();
    }

    public function endSlot(): string
    {
        if ($this->slotDepth <= 0) {
            throw new ViewException('Unexpected component slot close.');
        }
        --$this->slotDepth;
        $content = ob_get_clean();

        return false === $content ? '' : $content;
    }

    /** @param array<string, mixed> $data */
    public function renderPartial(string $template, array $data = []): string
    {
        if (!array_key_exists('errors', $data) && [] !== $this->validationErrors) {
            $data['errors'] = $this->validationErrors;
        }
        if (!array_key_exists('old', $data) && [] !== $this->oldInput) {
            $data['old'] = $this->oldInput;
        }

        return $this->engine->render($template, $data);
    }

    /** @param array<string, mixed> $props */
    public function renderComponent(string $template, array $props, string $slot): string
    {
        $props['slot'] = $slot;
        if (!array_key_exists('errors', $props) && [] !== $this->validationErrors) {
            $props['errors'] = $this->validationErrors;
        }
        if (!array_key_exists('old', $props) && [] !== $this->oldInput) {
            $props['old'] = $this->oldInput;
        }

        return $this->engine->render($template, $props);
    }

    /** @return array<string, string> */
    public function sections(): array
    {
        return $this->sections;
    }

    /** @param array<string, string> $sections */
    public function inheritSections(array $sections): void
    {
        $this->sections = $sections;
    }
}
