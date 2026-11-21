<?php

declare(strict_types=1);

namespace Kongono\Database;

/**
 * Immutable paginated query result.
 *
 * @template T
 */
final readonly class Page
{
    /** @param list<T> $items */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $perPage,
    ) {}

    public function lastPage(): int
    {
        return max(1, (int) ceil($this->total / max(1, $this->perPage)));
    }

    public function hasMore(): bool
    {
        return $this->page < $this->lastPage();
    }

    /** @return array{items: list<T>, total: int, page: int, per_page: int, last_page: int, has_more: bool} */
    public function toArray(): array
    {
        return [
            'items' => $this->items,
            'total' => $this->total,
            'page' => $this->page,
            'per_page' => $this->perPage,
            'last_page' => $this->lastPage(),
            'has_more' => $this->hasMore(),
        ];
    }
}
