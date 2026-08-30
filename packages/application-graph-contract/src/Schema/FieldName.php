<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphContract\Schema;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphContract\Exception\InvalidGraphContractException;

/** Holds one exact lowercase ASCII schema field name of at most 64 bytes. */
#[Api(ApiStatus::Experimental)]
final readonly class FieldName
{
    public function __construct(private string $value)
    {
        if (1 !== preg_match('/\A[a-z][a-z0-9_]{0,63}\z/D', $value)) {
            throw InvalidGraphContractException::fieldName('field_name_grammar');
        }
    }

    public function value(): string
    {
        return $this->value;
    }
}
