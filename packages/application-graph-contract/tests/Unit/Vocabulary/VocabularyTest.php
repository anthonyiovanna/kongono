<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphContract\Tests\Unit\Vocabulary;

use Kongono\ApplicationGraphContract\ContractVersion;
use Kongono\ApplicationGraphContract\Provenance\DerivationVersion;
use Kongono\ApplicationGraphContract\Provenance\ProducerContract;
use Kongono\ApplicationGraphContract\Schema\FactVisibility;
use Kongono\ApplicationGraphContract\Schema\FieldDisclosure;
use Kongono\ApplicationGraphContract\Schema\FieldType;
use Kongono\ApplicationGraphContract\Vocabulary\NodeType;
use Kongono\ApplicationGraphContract\Vocabulary\RelationType;
use PHPUnit\Framework\TestCase;

final class VocabularyTest extends TestCase
{
    public function testEveryVersionOneEnumIsExactAndClosed(): void
    {
        self::assertSame([['Version1', 1]], self::cases(ContractVersion::cases()));
        self::assertSame([
            ['CompositionInput', 'composition.input'],
            ['CompositionService', 'composition.service'],
            ['CompositionContract', 'composition.contract'],
            ['CompositionRoot', 'composition.root'],
        ], self::cases(NodeType::cases()));
        self::assertSame([
            ['DependsOn', 'composition.depends_on'],
            ['ConsumesInput', 'composition.consumes_input'],
            ['ImplementsContract', 'composition.implements_contract'],
            ['ExposesRoot', 'composition.exposes_root'],
        ], self::cases(RelationType::cases()));
        self::assertSame([['Text', 'text']], self::cases(FieldType::cases()));
        self::assertSame([
            ['Safe', 'safe'],
            ['Protected', 'protected'],
        ], self::cases(FieldDisclosure::cases()));
        self::assertSame([
            ['Public', 'public'],
            ['Application', 'application'],
            ['FrameworkInternal', 'framework_internal'],
        ], self::cases(FactVisibility::cases()));
        self::assertSame([
            ['CompositionV2', 'kongono.composition/2'],
            ['ExtensionV1', 'kongono.extension/1'],
        ], self::cases(ProducerContract::cases()));
        self::assertSame([['Version1', 1]], self::cases(DerivationVersion::cases()));
    }

    /**
     * @param list<\BackedEnum> $cases
     * @return list<array{string, int|string}>
     */
    private static function cases(array $cases): array
    {
        return array_map(static fn(\BackedEnum $case): array => [$case->name, $case->value], $cases);
    }
}
