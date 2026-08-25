<?php

declare(strict_types=1);

namespace Kongono\DatabaseMapping\Tests\Fixture;

final readonly class AllKindsRecord
{
    public function __construct(
        private int $id,
        private bool $active,
        private float $score,
        private ?string $name,
        private string $payload,
    ) {}

    public function id(): int
    {
        AccessorLog::record('id');

        return $this->id;
    }

    public function active(): bool
    {
        AccessorLog::record('active');

        return $this->active;
    }

    public function score(): float
    {
        AccessorLog::record('score');

        return $this->score;
    }

    public function name(): ?string
    {
        AccessorLog::record('name');

        return $this->name;
    }

    public function payload(): string
    {
        AccessorLog::record('payload');

        return $this->payload;
    }
}
