<?php

declare(strict_types=1);

namespace KongonoDataMinimal;

/** Demonstrates one application-owned mapped record with assigned identity. */
final readonly class Account
{
    public function __construct(private int $id, private string $name) {}

    public function id(): int
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }
}
