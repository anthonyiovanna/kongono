<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationBuild\Tests\Unit\Exception;

use Kongono\ModelInvocationBuild\Exception\ExceptionInterface;
use Kongono\ModelInvocationBuild\Exception\InvalidInvocationPlanArtifactException;
use Kongono\ModelInvocationBuild\Exception\InvalidInvocationPlanSourceException;
use Kongono\ModelInvocationBuild\Exception\InvocationPlanPublicationException;
use Kongono\ModelInvocationBuild\Exception\StaleInvocationPlanArtifactException;
use PHPUnit\Framework\TestCase;

final class ModelInvocationBuildExceptionTest extends TestCase
{
    public function testConcreteFailuresArePackageOwnedDeterministicAndClosed(): void
    {
        $failures = [
            InvalidInvocationPlanSourceException::invalidPlanIdentity('identity_grammar'),
            InvalidInvocationPlanSourceException::invalidAdapterIdentity('identity_grammar'),
            InvalidInvocationPlanSourceException::invalidBudget('duration_range', 'max_duration_milliseconds'),
            InvalidInvocationPlanSourceException::invalidSource('contract_version'),
            InvalidInvocationPlanArtifactException::invalid('invalid_json'),
            StaleInvocationPlanArtifactException::currentnessMismatch(),
            InvocationPlanPublicationException::failed('invalid_output_directory'),
        ];
        self::assertSame([
            'KGN-MODEL-BUILD-1410', 'KGN-MODEL-BUILD-1411', 'KGN-MODEL-BUILD-1412',
            'KGN-MODEL-BUILD-1413', 'KGN-MODEL-BUILD-1414', 'KGN-MODEL-BUILD-1415',
            'KGN-MODEL-BUILD-1416',
        ], array_map(static fn(ExceptionInterface $failure): string => $failure->diagnosticId(), $failures));
        foreach ($failures as $failure) {
            self::assertInstanceOf(ExceptionInterface::class, $failure);
            self::assertStringContainsString($failure->diagnosticId(), (string) $failure);
            self::assertStringNotContainsString("\n", (string) $failure);
        }
    }
}
