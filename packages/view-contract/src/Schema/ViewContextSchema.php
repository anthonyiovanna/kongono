<?php

declare(strict_types=1);

namespace Kongono\ViewContract\Schema;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewContract\Exception\InvalidViewContractException;
use Kongono\ViewContract\Value\RecordValue;
use Kongono\ViewContract\Value\ViewContext;

/** Owns one closed root record schema for a view context. */
#[Api(ApiStatus::Experimental)]
final readonly class ViewContextSchema
{
    public function __construct(private PresentationValueSchema $rootSchema)
    {
        if (PresentationValueKind::Record !== $rootSchema->kind()) {
            throw InvalidViewContractException::invalidPresentationSchema('context_root_not_record');
        }
    }

    public function rootSchema(): PresentationValueSchema
    {
        return $this->rootSchema;
    }

    public function matches(ViewContext $context): bool
    {
        return $this->rootSchema->matches(new RecordValue($context->fields()));
    }

    public function validate(ViewContext $context): void
    {
        $this->rootSchema->validate(new RecordValue($context->fields()));
    }

    public function equals(self $other): bool
    {
        return $this->rootSchema->equals($other->rootSchema);
    }
}
