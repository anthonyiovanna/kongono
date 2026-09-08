<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressApplication\Tests\Security;

use Kongono\NetworkEgressApplication\Diagnostic\NetworkEgressApplicationDiagnostic;
use Kongono\NetworkEgressApplication\Exception\InvalidGeneratedNetworkEgressWiringException;
use Kongono\NetworkEgressApplication\Exception\InvalidNetworkEgressApplicationProfileException;
use Kongono\NetworkEgressApplication\Exception\NetworkEgressApplicationLifecycleException;
use Kongono\NetworkEgressApplication\Exception\NetworkEgressApplicationOperationException;
use Kongono\NetworkEgressApplication\Exception\NetworkEgressApplicationWorkflowException;
use Kongono\NetworkEgressApplication\Exception\NetworkEgressWiringLoadException;
use Kongono\NetworkEgressApplication\Exception\NetworkEgressWiringPublicationException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class NetworkEgressApplicationDisclosureTest extends TestCase
{
    public function testDiagnosticsAreClosedSafeAndGloballyReserved(): void
    {
        self::assertSame(array_map(static fn(int $number): string => 'KGN-NET-' . $number, range(1540, 1548)), array_column(NetworkEgressApplicationDiagnostic::cases(), 'value'));
        foreach (NetworkEgressApplicationDiagnostic::cases() as $diagnostic) {
            foreach ($diagnostic->contextSchema()->fields() as $field) {
                self::assertSame('safe', $field->disclosure()->value);
            }
        }
    }

    public function testExceptionPresentationNeverRendersForeignOrCallerValues(): void
    {
        $secret = 'uri-header-token-cookie-ca-ip-path-and-foreign-message';
        $previous = new RuntimeException($secret);
        $exceptions = [
            InvalidNetworkEgressApplicationProfileException::invalid($secret, $previous),
            InvalidGeneratedNetworkEgressWiringException::invalid($secret, $previous),
            NetworkEgressWiringPublicationException::failed($secret, $previous),
            NetworkEgressWiringLoadException::failed($secret, $previous),
            NetworkEgressApplicationWorkflowException::failed($secret, $previous),
            NetworkEgressApplicationLifecycleException::failed($secret, $previous),
            NetworkEgressApplicationOperationException::request($previous),
            NetworkEgressApplicationOperationException::operation($previous),
            NetworkEgressApplicationOperationException::invariant(),
        ];

        foreach ($exceptions as $exception) {
            self::assertStringNotContainsString($secret, $exception->getMessage());
            self::assertStringNotContainsString($secret, (string) $exception);
            self::assertMatchesRegularExpression('/\A[a-zA-Z0-9_\\\\]+: \[KGN-NET-15(?:4[0-8])\] [^.]+\.\z/D', (string) $exception);
        }
    }
}
