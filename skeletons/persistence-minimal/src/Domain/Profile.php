<?php

declare(strict_types=1);

namespace KongonoPersistenceMinimal\Domain;

/** Demonstrates the aggregate's one bounded, ordered relationship member. */
final readonly class Profile
{
    private function __construct(private int $id, private int $accountId, private ?string $displayName) {}

    public static function restore(int $id, int $accountId, ?string $displayName): self
    {
        return new self($id, $accountId, $displayName);
    }

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
