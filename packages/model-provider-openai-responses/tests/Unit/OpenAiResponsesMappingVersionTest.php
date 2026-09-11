<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Tests\Unit;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelProviderOpenAiResponses\OpenAiResponsesMappingVersion;
use PHPUnit\Framework\TestCase;
use ReflectionEnum;

final class OpenAiResponsesMappingVersionTest extends TestCase
{
    public function testVersionAndStatusAreExact(): void
    {
        self::assertSame([OpenAiResponsesMappingVersion::Version1, OpenAiResponsesMappingVersion::Version2], OpenAiResponsesMappingVersion::cases());
        self::assertSame('kongono-openai-responses/1', OpenAiResponsesMappingVersion::Version1->value);
        self::assertSame('kongono-openai-responses/2', OpenAiResponsesMappingVersion::Version2->value);
        $attributes = (new ReflectionEnum(OpenAiResponsesMappingVersion::class))->getAttributes(Api::class);
        self::assertCount(1, $attributes);
        self::assertSame(ApiStatus::Experimental, $attributes[0]->newInstance()->status);
    }
}
