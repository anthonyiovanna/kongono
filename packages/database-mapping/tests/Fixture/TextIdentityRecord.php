<?php

declare(strict_types=1);

namespace Kongono\DatabaseMapping\Tests\Fixture;

final readonly class TextIdentityRecord
{
    public function __construct(private string $key, private ?string $note) {}

    public function key(): string
    {
        return $this->key;
    }

    public function note(): ?string
    {
        return $this->note;
    }
}
