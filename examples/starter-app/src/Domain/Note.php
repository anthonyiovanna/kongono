<?php

declare(strict_types=1);

namespace StarterApp\Domain;

final readonly class Note
{
    public function __construct(
        public int $id,
        public string $title,
        public string $body,
        public string $createdAt,
        public ?string $ownerId = null,
    ) {}

    /** @return array{id: int, title: string, body: string, created_at: string, owner_id: ?string} */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'body' => $this->body,
            'created_at' => $this->createdAt,
            'owner_id' => $this->ownerId,
        ];
    }
}
