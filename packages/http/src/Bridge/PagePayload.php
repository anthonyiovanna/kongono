<?php

declare(strict_types=1);

namespace Kongono\Http\Bridge;

use BackedEnum;
use Closure;
use InvalidArgumentException;
use JsonSerializable;
use Kongono\Http\Request;

/**
 * Immutable Kongono Bridge page payload for first-class React, Vue, and Svelte integration.
 *
 * Supports lazy closure props (evaluated only when rendered or requested via partial reload),
 * PHP 8.5 readonly DTO / BackedEnum normalization, and strict component path validation.
 */
final readonly class PagePayload
{
    public string $component;

    /**
     * @param array<string, mixed>         $props
     * @param array<string, list<string>>  $errors
     */
    public function __construct(
        string $component,
        public array $props = [],
        public string $url = '/',
        public string $version = '1',
        public array $errors = [],
        public ?string $csrfToken = null,
    ) {
        $this->component = self::validateComponent($component);
    }

    public static function validateComponent(string $component): string
    {
        $clean = trim($component);
        if ('' === $clean || str_contains($clean, '..') || 1 !== preg_match('/\A[A-Za-z0-9][A-Za-z0-9_\/\-]{0,127}\z/', $clean)) {
            throw new InvalidArgumentException(sprintf(
                'Unsafe Bridge component name "%s": only alphanumeric, "/", "_", and "-" segments are allowed.',
                $component,
            ));
        }

        return $clean;
    }

    /**
     * Resolve lazy closures & PHP 8.5 readonly DTOs into JSON-safe arrays,
     * honoring partial reload headers (`X-Kongono-Bridge-Partial-Data`) to skip unrequested props.
     *
     * @param array<string, mixed> $sharedProps
     * @return array{
     *     component: string,
     *     props: array<string, mixed>,
     *     url: string,
     *     version: string,
     *     errors: array<string, list<string>>,
     *     csrfToken: ?string
     * }
     */
    public function resolve(?Request $request = null, array $sharedProps = [], ?string $versionOverride = null): array
    {
        $merged = [...$sharedProps, ...$this->props];
        $partialOnly = [];
        if (
            null !== $request
            && $request->isBridge()
            && $request->bridgePartialComponent() === $this->component
        ) {
            $partialOnly = $request->bridgePartialData();
        }

        $resolvedProps = [];
        foreach ($merged as $key => $value) {
            if ([] !== $partialOnly && !in_array($key, $partialOnly, true)) {
                continue;
            }
            $evaluated = $value instanceof Closure ? $value($request) : $value;
            $resolvedProps[$key] = self::normalizeValue($evaluated);
        }

        return [
            'component' => $this->component,
            'props' => $resolvedProps,
            'url' => null !== $request ? $request->path() . ('' !== $request->getUri()->getQuery() ? '?' . $request->getUri()->getQuery() : '') : $this->url,
            'version' => $versionOverride ?? $this->version,
            'errors' => $this->errors,
            'csrfToken' => $this->csrfToken,
        ];
    }

    /**
     * Normalize PHP 8.5 readonly DTOs, BackedEnums, and nested arrays into strict JSON-serializable values.
     */
    public static function normalizeValue(mixed $value, int $depth = 0): mixed
    {
        if ($depth > 16 || null === $value || is_scalar($value)) {
            return $value;
        }
        if ($value instanceof BackedEnum) {
            return $value->value;
        }
        if ($value instanceof JsonSerializable) {
            return self::normalizeValue($value->jsonSerialize(), $depth + 1);
        }
        if (is_array($value)) {
            $out = [];
            foreach ($value as $k => $v) {
                $out[$k] = self::normalizeValue($v, $depth + 1);
            }

            return $out;
        }
        if (is_object($value)) {
            $out = [];
            foreach (get_object_vars($value) as $prop => $propVal) {
                $out[$prop] = self::normalizeValue($propVal, $depth + 1);
            }

            return $out;
        }

        return null;
    }
}
