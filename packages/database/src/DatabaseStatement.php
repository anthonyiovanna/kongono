<?php

declare(strict_types=1);

namespace Kongono\Database;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Database\Exception\InvalidDatabaseValueException;

/** Retains immutable raw SQL, ordered exact parameters, and an explicit result shape. */
#[Api(ApiStatus::Experimental)]
final readonly class DatabaseStatement
{
    /** @var list<DatabaseParameter> */
    private array $parameters;

    /** @param array<array-key, mixed> $parameters */
    public function __construct(
        private string $sql,
        array $parameters,
        private ResultExpectation $expectation,
    ) {
        if ('' === $sql || strlen($sql) > 1_048_576 || str_contains($sql, "\0") || 1 !== preg_match('//u', $sql)) {
            throw InvalidDatabaseValueException::validation();
        }
        if (!array_is_list($parameters) || count($parameters) > 65_535) {
            throw InvalidDatabaseValueException::validation();
        }

        $detached = [];
        $names = [];
        foreach ($parameters as $parameter) {
            if (!$parameter instanceof DatabaseParameter || isset($names[$parameter->name()])) {
                throw InvalidDatabaseValueException::validation();
            }
            $names[$parameter->name()] = true;
            $detached[] = $parameter;
        }
        $this->parameters = $detached;
    }

    public function sql(): string
    {
        return $this->sql;
    }

    /** @return list<DatabaseParameter> */
    public function parameters(): array
    {
        return [...$this->parameters];
    }

    public function expectation(): ResultExpectation
    {
        return $this->expectation;
    }
}
