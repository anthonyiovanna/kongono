<?php

declare(strict_types=1);

namespace Kongono\Http\Message\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Http\Message\Diagnostic\HttpMessageDiagnostic;
use Kongono\Http\Message\Exception\InvalidMessageArgumentException;

/** Stores validated headers with case-insensitive identity and preserved spelling. */
#[Api(ApiStatus::Internal)]
final readonly class HeaderCollection
{
    /** @var array<string, array{name: string, values: list<string>}> */
    private array $headers;

    /** @param array<array-key, mixed> $headers */
    public function __construct(array $headers = [])
    {
        $normalized = [];
        foreach ($headers as $name => $value) {
            if (!is_string($name)) {
                throw new InvalidMessageArgumentException(HttpMessageDiagnostic::InvalidHeaderName, ['name_length' => strlen((string) $name)]);
            }
            HttpMessageValidation::token($name, HttpMessageDiagnostic::InvalidHeaderName);
            $lower = strtolower($name);
            $values = HttpMessageValidation::headerValues($value);
            if (isset($normalized[$lower])) {
                $normalized[$lower]['values'] = [...$normalized[$lower]['values'], ...$values];
            } else {
                $normalized[$lower] = ['name' => $name, 'values' => $values];
            }
        }
        $this->headers = $normalized;
    }

    /** @return array<string, list<string>> */
    public function all(): array
    {
        $result = [];
        foreach ($this->headers as $entry) {
            $result[$entry['name']] = [...$entry['values']];
        }
        return $result;
    }

    public function has(string $name): bool
    {
        return HttpMessageValidation::isToken($name) && isset($this->headers[strtolower($name)]);
    }

    /** @return list<string> */
    public function get(string $name): array
    {
        if (!HttpMessageValidation::isToken($name)) {
            return [];
        }
        return [...($this->headers[strtolower($name)]['values'] ?? [])];
    }

    public function line(string $name): string
    {
        return implode(',', $this->get($name));
    }

    public function with(string $name, mixed $value): self
    {
        HttpMessageValidation::token($name, HttpMessageDiagnostic::InvalidHeaderName);
        $headers = $this->headers;
        $headers[strtolower($name)] = ['name' => $name, 'values' => HttpMessageValidation::headerValues($value)];
        return self::fromNormalized($headers);
    }

    public function withAdded(string $name, mixed $value): self
    {
        HttpMessageValidation::token($name, HttpMessageDiagnostic::InvalidHeaderName);
        $lower = strtolower($name);
        $headers = $this->headers;
        $values = HttpMessageValidation::headerValues($value);
        if (isset($headers[$lower])) {
            $headers[$lower]['values'] = [...$headers[$lower]['values'], ...$values];
        } else {
            $headers[$lower] = ['name' => $name, 'values' => $values];
        }
        return self::fromNormalized($headers);
    }

    public function without(string $name): self
    {
        $headers = $this->headers;
        if (HttpMessageValidation::isToken($name)) {
            unset($headers[strtolower($name)]);
        }
        return self::fromNormalized($headers);
    }

    /** @param array<string, array{name: string, values: list<string>}> $headers */
    private static function fromNormalized(array $headers): self
    {
        $flat = [];
        foreach ($headers as $entry) {
            $flat[$entry['name']] = $entry['values'];
        }
        return new self($flat);
    }
}
