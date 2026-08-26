<?php

declare(strict_types=1);

namespace Kongono\Console;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Console\Exception\InvalidConsoleValueException;

/** Stores one bounded detached structured result in ordinal field-name order. */
#[Api(ApiStatus::Experimental)]
final readonly class CommandResultRecord implements CommandResultData
{
    /** @var list<CommandResultField> */
    private array $fields;

    /** @var array<string, CommandResultField> */
    private array $fieldsByName;

    /** @param array<array-key, mixed> $fields */
    public function __construct(array $fields)
    {
        if ([] === $fields || count($fields) > 64) {
            throw InvalidConsoleValueException::result();
        }

        $detached = [];
        $byName = [];
        foreach ($fields as $field) {
            if (!$field instanceof CommandResultField) {
                throw InvalidConsoleValueException::result();
            }
            $name = $field->name()->value();
            if (array_key_exists($name, $byName)) {
                throw InvalidConsoleValueException::result();
            }
            $detached[] = $field;
            $byName[$name] = $field;
        }
        usort($detached, static fn(CommandResultField $left, CommandResultField $right): int => strcmp($left->name()->value(), $right->name()->value()));

        $this->fields = $detached;
        $this->fieldsByName = $byName;
    }

    /** @return list<CommandResultField> */
    public function fields(): array
    {
        return [...$this->fields];
    }

    public function field(CommandResultFieldName $name): ?CommandResultField
    {
        return $this->fieldsByName[$name->value()] ?? null;
    }

    public function hasField(CommandResultFieldName $name): bool
    {
        return array_key_exists($name->value(), $this->fieldsByName);
    }
}
