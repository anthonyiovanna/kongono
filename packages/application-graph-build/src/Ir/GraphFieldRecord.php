<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphBuild\Ir;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphBuild\Exception\InvalidGraphBuildException;
use Kongono\ApplicationGraphContract\Schema\FieldName;

/** Carries one exact text claim awaiting schema admission. */
#[Api(ApiStatus::Experimental)]
final readonly class GraphFieldRecord
{
    public const int MAX_VALUE_BYTES = 4096;

    public function __construct(private FieldName $name, private string $value)
    {
        $length = strlen($value);
        if (
            0 === $length
            || self::MAX_VALUE_BYTES < $length
            || 1 !== preg_match('//u', $value)
            || 1 === preg_match('/[\x00-\x1F\x7F]|\xC2[\x80-\x9F]/', $value)
        ) {
            throw InvalidGraphBuildException::input('field_value_grammar', 'field');
        }
    }

    public function name(): FieldName
    {
        return $this->name;
    }

    public function value(): string
    {
        return $this->value;
    }
}
