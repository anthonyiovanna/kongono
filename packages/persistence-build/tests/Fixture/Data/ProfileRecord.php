<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Tests\Fixture\Data;

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
