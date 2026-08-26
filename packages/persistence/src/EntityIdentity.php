<?php

declare(strict_types=1);

namespace Kongono\Persistence;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Persistence\Exception\InvalidPersistenceValueException;

/** Identifies one entity exactly within an operation-scoped persistence context. */
#[Api(ApiStatus::Experimental)]
final readonly class EntityIdentity
{
    public function __construct(
        private string $entityType,
        private string $key,
    ) {
        if (
            '' === $entityType
            || strlen($entityType) > 255
            || 1 !== preg_match('/^[A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*(?:\\\\[A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*)*$/D', $entityType)
        ) {
            throw InvalidPersistenceValueException::validation();
        }

        if (!$this->isOpaqueValue($key)) {
            throw InvalidPersistenceValueException::validation();
        }
    }

    public function entityType(): string
    {
        return $this->entityType;
    }

    public function key(): string
    {
        return $this->key;
    }

    public function equals(self $other): bool
    {
        return $this->entityType === $other->entityType && $this->key === $other->key;
    }

    private function isOpaqueValue(string $value): bool
    {
        return '' !== $value
            && strlen($value) <= 512
            && 1 === preg_match('//u', $value)
            && 0 === preg_match('/[\x{0000}-\x{001F}\x{007F}-\x{009F}]/u', $value);
    }
}
