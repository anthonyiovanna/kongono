<?php

declare(strict_types=1);

namespace Kongono\Persistence;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Persistence\Exception\InvalidPersistenceValueException;

/** Records one value-free entity change for deterministic later inspection. */
#[Api(ApiStatus::Experimental)]
final readonly class PersistenceChange
{
    /** @var list<string> */
    private array $changedFields;

    /** @param list<string> $changedFields */
    public function __construct(
        private object $entity,
        private ChangeKind $kind,
        private ?EntityIdentity $identity,
        array $changedFields,
        private ?ConcurrencyToken $expectedConcurrencyToken,
    ) {
        $detachedFields = [];
        foreach ($changedFields as $field) {
            $detachedFields[] = $this->requireField($field);
        }

        if (count($detachedFields) > 256) {
            throw InvalidPersistenceValueException::validation();
        }

        $seen = [];
        foreach ($detachedFields as $field) {
            if (
                '' === $field
                || strlen($field) > 128
                || 1 !== preg_match('//u', $field)
                || 1 !== preg_match('/^[A-Za-z_][A-Za-z0-9_.-]*$/D', $field)
                || array_key_exists($field, $seen)
            ) {
                throw InvalidPersistenceValueException::validation();
            }

            $seen[$field] = true;
        }

        if (null !== $identity && $identity->entityType() !== $entity::class) {
            throw InvalidPersistenceValueException::validation();
        }

        $validShape = match ($kind) {
            ChangeKind::Insert => null === $expectedConcurrencyToken,
            ChangeKind::Update => null !== $identity && [] !== $detachedFields,
            ChangeKind::Delete => null !== $identity && [] === $detachedFields,
        };

        if (!$validShape) {
            throw InvalidPersistenceValueException::validation();
        }

        $this->changedFields = $detachedFields;
    }

    public function entity(): object
    {
        return $this->entity;
    }

    public function kind(): ChangeKind
    {
        return $this->kind;
    }

    public function identity(): ?EntityIdentity
    {
        return $this->identity;
    }

    /** @return list<string> */
    public function changedFields(): array
    {
        return $this->changedFields;
    }

    public function expectedConcurrencyToken(): ?ConcurrencyToken
    {
        return $this->expectedConcurrencyToken;
    }

    private function requireField(mixed $field): string
    {
        if (!is_string($field)) {
            throw InvalidPersistenceValueException::validation();
        }

        return $field;
    }
}
