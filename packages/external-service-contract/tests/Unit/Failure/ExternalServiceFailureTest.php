<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceContract\Tests\Unit\Failure;

use Kongono\ExternalServiceContract\Exception\InvalidExternalServiceContractException;
use Kongono\ExternalServiceContract\Failure\ExternalServiceFailure;
use Kongono\ExternalServiceContract\Failure\ExternalServiceFailureKind;
use PHPUnit\Framework\TestCase;

final class ExternalServiceFailureTest extends TestCase
{
    public function testKindVocabularyAndSafeReasonAreExact(): void
    {
        self::assertSame(['binding', 'resolution', 'credential', 'authority', 'policy', 'transport', 'service', 'cancelled', 'cleanup', 'invariant'], array_column(ExternalServiceFailureKind::cases(), 'value'));
        $failure = new ExternalServiceFailure(ExternalServiceFailureKind::Credential, 'reference_unavailable');
        self::assertSame(ExternalServiceFailureKind::Credential, $failure->kind());
        self::assertSame('reference_unavailable', $failure->reason());
        self::assertTrue($failure->equals(new ExternalServiceFailure(ExternalServiceFailureKind::Credential, 'reference_unavailable')));
    }

    public function testReasonBoundsFailClosed(): void
    {
        foreach ([
            ['', 'KGN-EXTERNAL-SERVICE-CONTRACT-1607'],
            ['Upper', 'KGN-EXTERNAL-SERVICE-CONTRACT-1607'],
            ['under__score', 'KGN-EXTERNAL-SERVICE-CONTRACT-1607'],
            [str_repeat('a', 65), 'KGN-EXTERNAL-SERVICE-CONTRACT-1608'],
        ] as [$invalid, $diagnosticId]) {
            try {
                new ExternalServiceFailure(ExternalServiceFailureKind::Invariant, $invalid);
                self::fail('Expected failure reason rejection.');
            } catch (InvalidExternalServiceContractException $exception) {
                self::assertSame($diagnosticId, $exception->diagnosticId());
            }
        }
    }
}
