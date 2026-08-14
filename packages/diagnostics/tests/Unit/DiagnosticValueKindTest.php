<?php

declare(strict_types=1);

namespace Kongono\Diagnostics\Tests\Unit;

use BackedEnum;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticValueKind;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class DiagnosticValueKindTest extends TestCase
{
    public function testValueKindVocabularyIsExactAndBackedByStrings(): void
    {
        self::assertSame(
            [['Text', 'text'], ['Integer', 'integer'], ['Boolean', 'boolean']],
            array_map(
                static fn(DiagnosticValueKind $case): array => [$case->name, $case->value],
                DiagnosticValueKind::cases(),
            ),
        );
        self::assertInstanceOf(BackedEnum::class, DiagnosticValueKind::Text);
        self::assertSame(DiagnosticValueKind::Integer, DiagnosticValueKind::from('integer'));
        self::assertSame(DiagnosticValueKind::Boolean, DiagnosticValueKind::tryFrom('boolean'));
        self::assertCount(3, DiagnosticValueKind::cases());
    }

    public function testValueKindVocabularyIsPublic(): void
    {
        $attributes = (new ReflectionClass(DiagnosticValueKind::class))->getAttributes(Api::class);

        self::assertCount(1, $attributes);
        self::assertSame(ApiStatus::Public, $attributes[0]->newInstance()->status);
    }
}
