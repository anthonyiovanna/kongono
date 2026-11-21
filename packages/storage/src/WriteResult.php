<?php

declare(strict_types=1);

namespace Kongono\Storage;

final readonly class WriteResult
{
    public function __construct(
        public StorageOutcome $outcome,
        public ?ObjectMeta $meta = null,
    ) {}

    public function succeeded(): bool
    {
        return StorageOutcome::Created === $this->outcome || StorageOutcome::Replaced === $this->outcome;
    }
}
