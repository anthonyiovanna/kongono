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
        /** @var array<string, string> $maskedPhpBlocks */
        $maskedPhpBlocks = [];
        $maskBlock = static function (string $php) use (&$maskedPhpBlocks): string {
            $token = '__KGN_PHP_BLOCK_' . count($maskedPhpBlocks) . '_' . bin2hex(random_bytes(4)) . '__';
            $maskedPhpBlocks[$token] = $php;

            return $token;
        };

        // 1. Strip template comments
        $output = $this->replace('/\{\{--.*?--\}\}/s', '', $output);

        // 1b. Mask raw @php ... @endphp blocks so directives/echoes inside PHP strings are never mangled
        $output = $this->replaceCallback('/(?<![a-zA-Z0-9_])@php\b(.*?)(?<![a-zA-Z0-9_])@endphp\b/s', static fn(array $m): string => $maskBlock('<?php' . $m[1] . '?>'), $output);

        // 2. Compile self-closing component tags first so paired-tag parsing never scans past them
        $output = $this->compileSelfClosingComponents($output, $maskBlock);

        // 3. Compile paired component tags from innermost outward: <x-name ...>...</x-name>
        $output = $this->compilePairedComponents($output, $maskBlock);

        // 4. Layout directives
        $output = $this->replaceCallback('/(?<![a-zA-Z0-9_])@extends\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/', static function (array $m): string {
            return sprintf('<?php $__env->extend(%s); ?>', var_export($m[1], true));
        }, $output);

        $output = $this->replaceCallback('/(?<![a-zA-Z0-9_])@section\s*\(\s*[\'"]([^\'"]+)[\'"]\s*,\s*((?:[^()]|\((?:[^()]|\([^()]*\))*\))+)\s*\)/', static function (array $m): string {
            return sprintf('<?php $__env->setSection(%s, $__env->escape(%s)); ?>', var_export($m[1], true), $m[2]);
        }, $output);

        $output = $this->replaceCallback('/(?<![a-zA-Z0-9_])@section\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/', static function (array $m): string {
            return sprintf('<?php $__env->startSection(%s); ?>', var_export($m[1], true));
        }, $output);

        $output = $this->replace('/(?<![a-zA-Z0-9_])@endsection\b/', '<?php $__env->endSection(); ?>', $output);

        $output = $this->replaceCallback('/(?<![a-zA-Z0-9_])@yield\s*\(\s*[\'"]([^\'"]+)[\'"](?:\s*,\s*((?:[^()]|\((?:[^()]|\([^()]*\))*\))+))?\s*\)/', static function (array $m): string {
            $default = isset($m[2]) && '' !== $m[2] ? sprintf('$__env->escape(%s)', $m[2]) : "''";

            return sprintf('<?php echo $__env->yieldSection(%s, %s); ?>', var_export($m[1], true), $default);
        }, $output);

        // 5. Includes
        $output = $this->replaceCallback('/(?<![a-zA-Z0-9_])@include\s*\(\s*[\'"]([^\'"]+)[\'"](?:\s*,\s*((?:[^()]|\((?:[^()]|\([^()]*\))*\))+))?\s*\)/', static function (array $m): string {
            $data = isset($m[2]) && '' !== $m[2] ? $m[2] : '[]';

            return sprintf('<?php echo $__env->renderPartial(%s, %s); ?>', var_export($m[1], true), $data);
        }, $output);

        // 6. Control flow directives (word-bounded & balanced parentheses so emails like `user@elsewhere.com` are untouched and nested `@forelse` works)
        $bal = '((?:[^()]|\((?1)\))+)';
        $output = $this->replaceCallback('/(?<![a-zA-Z0-9_])@forelse\s*\(\s*((?:[^()]|\((?:[^()]|\([^()]*\))*\))+?)\s+as\s+((?:[^()]|\((?:[^()]|\([^()]*\))*\))+)\s*\)/', static function (array $m): string {
            return sprintf('<?php $__empty_stack[] = true; foreach (%s as %s): $__empty_stack[array_key_last($__empty_stack)] = false; ?>', $m[1], $m[2]);
        }, $output);
        $output = $this->replace('/(?<![a-zA-Z0-9_])@empty\b/', '<?php endforeach; if (array_pop($__empty_stack)): ?>', $output);
        $output = $this->replace('/(?<![a-zA-Z0-9_])@endforelse\b/', '<?php endif; ?>', $output);

        $output = $this->replaceCallback('/(?<![a-zA-Z0-9_])@foreach\s*\(' . $bal . '\)/', static fn(array $m): string => sprintf('<?php foreach (%s): ?>', $m[1]), $output);
        $output = $this->replace('/(?<![a-zA-Z0-9_])@endforeach\b/', '<?php endforeach; ?>', $output);

        $output = $this->replaceCallback('/(?<![a-zA-Z0-9_])@elseif\s*\(' . $bal . '\)/', static fn(array $m): string => sprintf('<?php elseif (%s): ?>', $m[1]), $output);
        $output = $this->replaceCallback('/(?<![a-zA-Z0-9_])@if\s*\(' . $bal . '\)/', static fn(array $m): string => sprintf('<?php if (%s): ?>', $m[1]), $output);
        $output = $this->replaceCallback('/(?<![a-zA-Z0-9_])@unless\s*\(' . $bal . '\)/', static fn(array $m): string => sprintf('<?php if (!(%s)): ?>', $m[1]), $output);
        $output = $this->replace('/(?<![a-zA-Z0-9_])@endunless\b/', '<?php endif; ?>', $output);
        $output = $this->replaceCallback('/(?<![a-zA-Z0-9_])@isset\s*\(' . $bal . '\)/', static fn(array $m): string => sprintf('<?php if (isset(%s)): ?>', $m[1]), $output);
        $output = $this->replace('/(?<![a-zA-Z0-9_])@endisset\b/', '<?php endif; ?>', $output);
        $output = $this->replace('/(?<![a-zA-Z0-9_])@else\b/', '<?php else: ?>', $output);
        $output = $this->replace('/(?<![a-zA-Z0-9_])@endif\b/', '<?php endif; ?>', $output);

        // 6b. @t('key', [...]) auto-escaped translation, @old('field', $default), @error('field')...@enderror, @vite(...), @liveScript, @safeHtml($html), @json($value)
        $output = $this->replaceCallback('/(?<![a-zA-Z0-9_])@t\s*\(' . $bal . '\)/', static fn(array $m): string => sprintf('<?php echo $__env->translate(%s); ?>', $m[1]), $output);
        $output = $this->replaceCallback('/(?<![a-zA-Z0-9_])@old\s*\(' . $bal . '\)/', static fn(array $m): string => sprintf('<?php echo $__env->escape($__env->old(%s)); ?>', $m[1]), $output);
        $output = $this->replaceCallback(
            '/(?<![a-zA-Z0-9_])@error\s*\(' . $bal . '\)/',
            static fn(array $m): string => sprintf(
                '<?php $__err_msg_stack[] = [isset($message), $message ?? null]; if (null !== ($message = $__env->firstError(%s))): ?>',
                $m[1],
            ),
            $output,
        );
        $output = $this->replace(
            '/(?<![a-zA-Z0-9_])@enderror\b/',
            '<?php endif; [$__had_msg, $__prev_msg] = array_pop($__err_msg_stack); if ($__had_msg) { $message = $__prev_msg; } else { unset($message); } ?>',
            $output,
        );
        $output = $this->replaceCallback('/(?<![a-zA-Z0-9_])@vite\s*\(' . $bal . '\)/', static fn(array $m): string => sprintf('<?php echo $__env->vite(%s); ?>', $m[1]), $output);
        $output = $this->replaceCallback('/(?<![a-zA-Z0-9_])@liveScript\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/', static fn(array $m): string => sprintf('<script src="%s" defer></script>', htmlspecialchars($m[1], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')), $output);
        $output = $this->replace('/(?<![a-zA-Z0-9_])@liveScript\b/', '<script src="/_kongono/live.js?v=2.5.0" defer></script>', $output);
        $output = $this->replaceCallback('/(?<![a-zA-Z0-9_])@bridgeScript\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/', static fn(array $m): string => sprintf('<script src="%s" defer></script>', htmlspecialchars($m[1], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')), $output);
        $output = $this->replace('/(?<![a-zA-Z0-9_])@bridgeScript\b/', '<script src="/_kongono/bridge.js?v=1.10.1" defer></script>', $output);
        $output = $this->replaceCallback('/(?<![a-zA-Z0-9_])@state\s*\(' . $bal . '\)/', static fn(array $m): string => sprintf('<?php echo $__env->stateAttr(%s); ?>', $m[1]), $output);
        $output = $this->replaceCallback('/(?<![a-zA-Z0-9_])@island\s*\(' . $bal . '\)/', static fn(array $m): string => sprintf('<?php echo $__env->island(%s); ?>', $m[1]), $output);
        $output = $this->replaceCallback('/(?<![a-zA-Z0-9_])@safeHtml\s*\(' . $bal . '\)/', static fn(array $m): string => sprintf('<?php echo $__env->safeHtml(%s); ?>', $m[1]), $output);
        $output = $this->replaceCallback('/(?<![a-zA-Z0-9_])@json\s*\(' . $bal . '\)/', static fn(array $m): string => sprintf('<?php echo json_encode(%s, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>', $m[1]), $output);

        // 7. Raw echo {!! $expr !!}
        $output = $this->replaceCallback('/\{!!\s*(.+?)\s*!!\}/s', static fn(array $m): string => sprintf('<?php echo (string) (%s); ?>', $m[1]), $output);

        // 8. Escaped echo {{ $expr }}
        $output = $this->replaceCallback('/\{\{\s*(.+?)\s*\}\}/s', static fn(array $m): string => sprintf('<?php echo $__env->escape(%s); ?>', $m[1]), $output);

        // 9. Restore masked @php and component attribute PHP blocks
        if ([] !== $maskedPhpBlocks) {
            $output = strtr($output, $maskedPhpBlocks);
        }

        return "<?php declare(strict_types=1);\n"
            . "return static function (array \$__vars, \\Kongono\\View\\RenderState \$__env): void {\n"
            . "    \$__empty_stack = [];\n"
            . "    \$__err_msg_stack = [];\n"
            . "    extract(\$__vars, EXTR_SKIP);\n"
            . "?>\n"
            . $output
            . "<?php };\n";
    }

    /** @param \Closure(string): string $maskBlock */
    private function compileSelfClosingComponents(string $source, \Closure $maskBlock): string
    {
        return $this->replaceCallback(
            '/<x-([a-zA-Z0-9._-]+)((?:\s+(?:"[^"]*"|\'[^\']*\'|[^<>\/"\']++)++)?)\s*\/>/s',
            function (array $m) use ($maskBlock): string {
                $component = 'components/' . str_replace('.', '/', $m[1]);
                $propsPhp = $this->compileComponentAttributes($m[2]);

                return $maskBlock(sprintf('<?php echo $__env->renderComponent(%s, %s, \'\'); ?>', var_export($component, true), $propsPhp));
            },
            $source,
        );
    }

    /** @param \Closure(string): string $maskBlock */
    private function compilePairedComponents(string $source, \Closure $maskBlock): string
    {
        // Match innermost paired component (never skipping across a nested opening tag of the same component name)
        $pattern = '/<x-([a-zA-Z0-9._-]+)((?:\s+(?:"[^"]*"|\'[^\']*\'|[^<>\/"\']++)++)?)>((?:(?!<x-\1(?:\s|>)).)*?)<\/x-\1>/s';
        $previous = null;
        $current = $source;

        while ($previous !== $current) {
            $previous = $current;
            $current = $this->replaceCallback(
                $pattern,
                function (array $m) use ($maskBlock): string {
                    $component = 'components/' . str_replace('.', '/', $m[1]);
                    $propsPhp = $this->compileComponentAttributes($m[2]);
                    $slot = $m[3];

                    return $maskBlock('<?php $__env->startSlot(); ?>')
                        . $slot
                        . $maskBlock(sprintf('<?php echo $__env->renderComponent(%s, %s, $__env->endSlot()); ?>', var_export($component, true), $propsPhp));
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
        $count = preg_match_all('/(:?)([a-zA-Z_][a-zA-Z0-9_-]*)\s*=\s*(?:"([^"]*)"|\'([^\']*)\')/', $attrString, $matches, PREG_SET_ORDER);
        if (false === $count || 0 === $count) {
            return '[]';
        }

        foreach ($matches as $match) {
            $isExpr = ':' === $match[1];
            $key = $this->camelCase($match[2]);
            $rawVal = isset($match[4]) && '' !== $match[4] ? $match[4] : ($match[3] ?? '');
            // Protect static prop string literals from later directive/echo passes (`@endphp`, `{{`, `{!!`)
            $value = $isExpr ? $rawVal : self::exportSafeTemplateString($rawVal);
            $entries[] = sprintf('%s => %s', var_export($key, true), $value);
        }

        return '[' . implode(', ', $entries) . ']';
    }

    /** Export a PHP string literal while neutralizing `@`, `{`, and `}` so subsequent compiler passes cannot break out. */
    private static function exportSafeTemplateString(string $raw): string
    {
        $exported = var_export($raw, true);

        return str_replace(['@', '{', '}'], ["' . \"\\x40\" . '", "' . \"\\x7b\" . '", "' . \"\\x7d\" . '"], $exported);
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
