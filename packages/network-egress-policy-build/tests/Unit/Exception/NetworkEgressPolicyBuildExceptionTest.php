<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressPolicyBuild\Tests\Unit\Exception;

use Kongono\NetworkEgressPolicyBuild\Exception\EgressPolicyPublicationException;
use Kongono\NetworkEgressPolicyBuild\Exception\ExceptionInterface;
use Kongono\NetworkEgressPolicyBuild\Exception\InvalidEgressPolicyArtifactException;
use Kongono\NetworkEgressPolicyBuild\Exception\InvalidEgressPolicySourceException;
use Kongono\NetworkEgressPolicyBuild\Exception\StaleEgressPolicyArtifactException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class NetworkEgressPolicyBuildExceptionTest extends TestCase
{
    public function testExceptionFamiliesPairWithDiagnosticsAndStringifyDeterministically(): void
    {
        $failures = [
            InvalidEgressPolicySourceException::invalidIdentity(),
            InvalidEgressPolicySourceException::invalidSource('origin_count', 'allowed_origins', 65, limit: 64),
            InvalidEgressPolicySourceException::incompatible('tls_capability_required', 'required_capabilities'),
            InvalidEgressPolicyArtifactException::invalid('invalid_shape', 'policy'),
            InvalidEgressPolicyArtifactException::limitExceeded(65_537, 65_536),
            StaleEgressPolicyArtifactException::currentnessMismatch(),
            EgressPolicyPublicationException::failed('atomic_publish_failed'),
        ];
        foreach ($failures as $failure) {
            self::assertInstanceOf(ExceptionInterface::class, $failure);
            self::assertStringContainsString($failure->diagnosticId(), (string) $failure);
            self::assertStringNotContainsString("\n", (string) $failure);
            self::assertLessThan(1_024, strlen((string) $failure));
            self::assertSame((string) $failure, (string) $failure);
        }
    }

    public function testForeignMessageIsRetainedOnlyAsPreviousAndNeverPresented(): void
    {
        $failure = EgressPolicyPublicationException::failed('secret-path', new RuntimeException('secret-foreign-message'));
        self::assertSame('secret-foreign-message', $failure->getPrevious()?->getMessage());
        self::assertStringContainsString('unsupported_violation', (string) $failure);
        self::assertStringNotContainsString('secret-path', (string) $failure);
        self::assertStringNotContainsString('secret-foreign-message', (string) $failure);
    }
}
