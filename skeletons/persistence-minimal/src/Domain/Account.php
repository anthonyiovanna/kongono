<?php

declare(strict_types=1);

namespace KongonoPersistenceMinimal\Domain;

/** Demonstrates one aggregate root with optimistic-concurrency evidence. */
final readonly class Account
{
    public function __construct(private int $id, private string $name, private int $version) {}

    public function id(): int
    {
        return $this->id;
    }
    public function name(): string
    {
        return $this->name;
    }
    public function version(): int
    {
        return $this->version;
    }
}
