<?php

declare(strict_types=1);

namespace Kongono\View;

/**
 * Compiles `.kgn.html` templates into fast, auto-escaped PHP 8.5 render scripts.
 *
 * Supported syntax:
 *   - Comments:     {{-- ... --}}
 *   - Escaped echo: {{ $expr }}
 *   - Raw echo:     {!! $expr !!}
 *   - Layouts:      @extends('layouts/app'), @section('name')...@endsection, @yield('name')
 *   - Includes:     @include('partials/nav', [...])
 *   - Control flow: @if/@elseif/@else/@endif, @foreach/@endforeach, @forelse/@empty/@endforelse
 *   - Components:   <x-alert type="info" :title="$t">...</x-alert> and <x-badge :label="$l" />
 */
final class TemplateCompiler
{
    public function compile(string $source): string
    {
        $output = $source;

        // 1. Strip template comments
        $output = $this->replace('/\{\{--.*?--\}\}/s', '', $output);

        // 2. Compile paired component tags: <x-name ...>...</x-name>
        $output = $this->compilePairedComponents($output);

        // 3. Compile self-closing component tags: <x-name ... />
        $output = $this->compileSelfClosingComponents($output);

        // 4. Layout directives
        $output = $this->replaceCallback('/@extends\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/', static function (array $m): string {
            return sprintf('<?php $__env->extend(%s); ?>', var_export($m[1], true));
        }, $output);

        $output = $this->replaceCallback('/@section\s*\(\s*[\'"]([^\'"]+)[\'"]\s*,\s*(.+?)\s*\)/', static function (array $m): string {
            return sprintf('<?php $__env->setSection(%s, $__env->escape(%s)); ?>', var_export($m[1], true), $m[2]);
        }, $output);

        $output = $this->replaceCallback('/@section\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/', static function (array $m): string {
            return sprintf('<?php $__env->startSection(%s); ?>', var_export($m[1], true));
        }, $output);

        $output = str_replace('@endsection', '<?php $__env->endSection(); ?>', $output);

        $output = $this->replaceCallback('/@yield\s*\(\s*[\'"]([^\'"]+)[\'"](?:\s*,\s*(.+?))?\s*\)/', static function (array $m): string {
            $default = isset($m[2]) && '' !== $m[2] ? sprintf('$__env->escape(%s)', $m[2]) : "''";

            return sprintf('<?php echo $__env->yieldSection(%s, %s); ?>', var_export($m[1], true), $default);
        }, $output);

        // 5. Includes
        $output = $this->replaceCallback('/@include\s*\(\s*[\'"]([^\'"]+)[\'"](?:\s*,\s*(.+?))?\s*\)/', static function (array $m): string {
            $data = isset($m[2]) && '' !== $m[2] ? $m[2] : '[]';

            return sprintf('<?php echo $__env->renderPartial(%s, %s); ?>', var_export($m[1], true), $data);
        }, $output);

        // 6. Control flow directives
        $output = $this->replaceCallback('/@forelse\s*\(\s*(.+?)\s+as\s+(.+?)\s*\)/', static function (array $m): string {
            return sprintf('<?php $__empty_flag = true; foreach (%s as %s): $__empty_flag = false; ?>', $m[1], $m[2]);
        }, $output);
        $output = str_replace('@empty', '<?php endforeach; if ($__empty_flag): ?>', $output);
        $output = str_replace('@endforelse', '<?php endif; ?>', $output);

        $output = $this->replaceCallback('/@foreach\s*\((.+)\)/', static fn(array $m): string => sprintf('<?php foreach (%s): ?>', $m[1]), $output);
        $output = str_replace('@endforeach', '<?php endforeach; ?>', $output);

        $output = $this->replaceCallback('/@elseif\s*\((.+)\)/', static fn(array $m): string => sprintf('<?php elseif (%s): ?>', $m[1]), $output);
        $output = $this->replaceCallback('/@if\s*\((.+)\)/', static fn(array $m): string => sprintf('<?php if (%s): ?>', $m[1]), $output);
        $output = $this->replaceCallback('/@unless\s*\((.+)\)/', static fn(array $m): string => sprintf('<?php if (!(%s)): ?>', $m[1]), $output);
        $output = str_replace('@endunless', '<?php endif; ?>', $output);
        $output = $this->replaceCallback('/@isset\s*\((.+)\)/', static fn(array $m): string => sprintf('<?php if (isset(%s)): ?>', $m[1]), $output);
        $output = str_replace('@endisset', '<?php endif; ?>', $output);
        $output = str_replace('@else', '<?php else: ?>', $output);
        $output = str_replace('@endif', '<?php endif; ?>', $output);

        // 6b. @t('key', [...]) auto-escaped translation, @vite(...) asset tags, @liveScript, @safeHtml($html), @json($value) & @php ... @endphp
        $output = $this->replaceCallback('/@t\s*\((.+?)\)/', static fn(array $m): string => sprintf('<?php echo $__env->translate(%s); ?>', $m[1]), $output);
        $output = $this->replaceCallback('/@vite\s*\((.+?)\)/', static fn(array $m): string => sprintf('<?php echo $__env->vite(%s); ?>', $m[1]), $output);
        $output = $this->replaceCallback('/@liveScript\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/', static fn(array $m): string => sprintf('<script src="%s" defer></script>', htmlspecialchars($m[1], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')), $output);
        $output = str_replace('@liveScript', '<script src="/_kongono/live.js" defer></script>', $output);
        $output = $this->replaceCallback('/@safeHtml\s*\((.+)\)/', static fn(array $m): string => sprintf('<?php echo $__env->safeHtml(%s); ?>', $m[1]), $output);
        $output = $this->replaceCallback('/@json\s*\((.+)\)/', static fn(array $m): string => sprintf('<?php echo json_encode(%s, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>', $m[1]), $output);
        $output = str_replace(['@php', '@endphp'], ['<?php', '?>'], $output);

        // 7. Raw echo {!! $expr !!}
        $output = $this->replaceCallback('/\{!!\s*(.+?)\s*!!\}/s', static fn(array $m): string => sprintf('<?php echo (string) (%s); ?>', $m[1]), $output);

        // 8. Escaped echo {{ $expr }}
        $output = $this->replaceCallback('/\{\{\s*(.+?)\s*\}\}/s', static fn(array $m): string => sprintf('<?php echo $__env->escape(%s); ?>', $m[1]), $output);

        return "<?php declare(strict_types=1);\n"
            . "return static function (array \$__vars, \\Kongono\\View\\RenderState \$__env): void {\n"
            . "    extract(\$__vars, EXTR_SKIP);\n"
            . "?>\n"
            . $output
            . "<?php };\n";
    }

    private function compileSelfClosingComponents(string $source): string
    {
        return $this->replaceCallback(
            '/<x-([a-zA-Z0-9._-]+)((?:\s+(?:"[^"]*"|\'[^\']*\'|[^<>\/"\'])*)?)\s*\/>/s',
            function (array $m): string {
                $component = 'components/' . str_replace('.', '/', $m[1]);
                $propsPhp = $this->compileComponentAttributes($m[2]);

                return sprintf('<?php echo $__env->renderComponent(%s, %s, \'\'); ?>', var_export($component, true), $propsPhp);
            },
            $source,
        );
    }

    private function compilePairedComponents(string $source): string
    {
        // Loop to support nested components from innermost outward
        $pattern = '/<x-([a-zA-Z0-9._-]+)((?:\s+(?:"[^"]*"|\'[^\']*\'|[^<>"\'])*)?)>(.*?)<\/x-\1>/s';
        $previous = null;
        $current = $source;

        while ($previous !== $current) {
            $previous = $current;
            $current = $this->replaceCallback(
                $pattern,
                function (array $m): string {
                    $component = 'components/' . str_replace('.', '/', $m[1]);
                    $propsPhp = $this->compileComponentAttributes($m[2]);
                    $slot = $m[3];

                    return sprintf(
                        '<?php $__env->startSlot(); ?>%s<?php echo $__env->renderComponent(%s, %s, $__env->endSlot()); ?>',
                        $slot,
                        var_export($component, true),
                        $propsPhp,
                    );
                },
                $current,
            );
        }

        return $current;
    }

    private function compileComponentAttributes(string $attrString): string
    {
        $attrString = trim($attrString);
        if ('' === $attrString) {
            return '[]';
        }

        $entries = [];
        $count = preg_match_all('/(:?)([a-zA-Z_][a-zA-Z0-9_-]*)\s*=\s*"([^"]*)"/', $attrString, $matches, PREG_SET_ORDER);
        if (false === $count || 0 === $count) {
            return '[]';
        }

        foreach ($matches as $match) {
            $isExpr = ':' === $match[1];
            $key = $this->camelCase($match[2]);
            $value = $isExpr ? $match[3] : var_export($match[3], true);
            $entries[] = sprintf('%s => %s', var_export($key, true), $value);
        }

        return '[' . implode(', ', $entries) . ']';
    }

    private function camelCase(string $name): string
    {
        $parts = explode('-', $name);
        $first = array_shift($parts);
        foreach ($parts as &$part) {
            $part = ucfirst($part);
        }

        return $first . implode('', $parts);
    }

    private function replace(string $pattern, string $replacement, string $subject): string
    {
        $result = preg_replace($pattern, $replacement, $subject);
        if (!is_string($result)) {
            throw new ViewException('Template compilation failed during regex replacement.');
        }

        return $result;
    }

    /** @param callable(array<int, string>): string $callback */
    private function replaceCallback(string $pattern, callable $callback, string $subject): string
    {
        $result = preg_replace_callback($pattern, $callback, $subject);
        if (!is_string($result)) {
            throw new ViewException('Template compilation failed during directive parsing.');
        }

        return $result;
    }
}
