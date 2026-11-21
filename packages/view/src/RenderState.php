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

    public function __construct(
        private readonly ViewEngine $engine,
    ) {}

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
        return $this->engine->render($template, $data);
    }

    /** @param array<string, mixed> $props */
    public function renderComponent(string $template, array $props, string $slot): string
    {
        $props['slot'] = $slot;

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
