<?php

declare(strict_types=1);

namespace Kongono\Tests\BlackBox\Fixture;

use RuntimeException;

/** Detached bounded response data emitted by the Phase 2 fixture. */
final readonly class FixtureResponse
{
    public const int MAX_BODY_BYTES = 16_384;

    /** @var array<string, list<string>> */
    private array $headers;

    /** @param array<string, list<string>> $headers */
    public function __construct(
        private int $statusCode,
        array $headers,
        private string $body,
    ) {
        if ($statusCode < 100 || $statusCode > 599) {
            throw new RuntimeException('Fixture response status is invalid.');
        }

        if (count($headers) > 64) {
            throw new RuntimeException('Fixture response has too many headers.');
        }

        if (strlen($body) > self::MAX_BODY_BYTES) {
            throw new RuntimeException('Fixture response body is too large.');
        }

        $detached = [];
        foreach ($headers as $name => $values) {
            if (count($values) > 32) {
                throw new RuntimeException('Fixture response header has too many values.');
            }

            $detachedValues = [];
            foreach ($values as $value) {
                $detachedValues[] = $value;
            }
            $detached[$name] = $detachedValues;
        }

        uksort($detached, static function (string $left, string $right): int {
            $comparison = strcasecmp($left, $right);

            return 0 === $comparison ? strcmp($left, $right) : $comparison;
        });
        $this->headers = $detached;
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    /** @return array<string, list<string>> */
    public function headers(): array
    {
        $detached = [];
        foreach ($this->headers as $name => $values) {
            $detached[$name] = [...$values];
        }

        return $detached;
    }

    public function body(): string
    {
        return $this->body;
    }
}
