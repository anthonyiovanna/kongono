<?php

declare(strict_types=1);

namespace KongonoPersistenceMinimal\Data;

/** Stores one profile row without domain behavior. */
final readonly class ProfileRecord
{
    public function __construct(private int $id, private int $accountId, private ?string $displayName) {}

    public function id(): int
    {
        return $this->id;
    }
    public function accountId(): int
    {
        return $this->accountId;
    }
    public function displayName(): ?string
    {
        return $this->displayName;
    }
}
