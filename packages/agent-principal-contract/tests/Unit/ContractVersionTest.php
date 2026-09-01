<?php

declare(strict_types=1);

namespace Kongono\AgentPrincipalContract\Tests\Unit;

use Kongono\AgentPrincipalContract\ContractVersion;
use PHPUnit\Framework\TestCase;

final class ContractVersionTest extends TestCase
{
    public function testVersionVocabularyIsExact(): void
    {
        self::assertSame([ContractVersion::Version1], ContractVersion::cases());
        self::assertSame(1, ContractVersion::Version1->value);
    }
}
