<?php

declare(strict_types=1);

namespace Kongono\Diagnostics\Tests\Unit;

use BackedEnum;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class DiagnosticDisclosureTest extends TestCase
{
    public function testDisclosureVocabularyIsExactAndBackedByStrings(): void
    {
        self::assertSame(
            [['Safe', 'safe'], ['Protected', 'protected']],
            array_map(
                static fn(DiagnosticDisclosure $case): array => [$case->name, $case->value],
                DiagnosticDisclosure::cases(),
            ),
        );
        self::assertInstanceOf(BackedEnum::class, DiagnosticDisclosure::Safe);
        self::assertSame(DiagnosticDisclosure::Safe, DiagnosticDisclosure::from('safe'));
        self::assertSame(DiagnosticDisclosure::Protected, DiagnosticDisclosure::tryFrom('protected'));
        self::assertCount(2, DiagnosticDisclosure::cases());
    }

    public function testDisclosureVocabularyIsPublic(): void
    {
        $attributes = (new ReflectionClass(DiagnosticDisclosure::class))->getAttributes(Api::class);

        self::assertCount(1, $attributes);
        self::assertSame(ApiStatus::Public, $attributes[0]->newInstance()->status);
    }
}
