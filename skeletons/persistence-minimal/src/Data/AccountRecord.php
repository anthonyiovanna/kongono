<?php

declare(strict_types=1);

namespace KongonoPersistenceMinimal\Data;

/** Stores one account row without domain behavior. */
final readonly class AccountRecord
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
