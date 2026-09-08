<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressCurl\Tests\Unit\Transport;

use Kongono\NetworkEgressContract\Capability\TransportCapability;
use Kongono\NetworkEgressCurl\Tests\Fixture\FakeCurlExecutor;
use Kongono\NetworkEgressCurl\Transport\CurlEgressTransport;
use Kongono\NetworkEgressRuntime\Transport\EgressTransport;
use PHPUnit\Framework\TestCase;

final class CurlEgressTransportTest extends TestCase
{
    public function testCapabilitiesAreExactStableAndOpeningHasNoExecution(): void
    {
        $transport = new CurlEgressTransport(FakeCurlExecutor::profile());
        self::assertInstanceOf(EgressTransport::class, $transport);
        self::assertSame([
            TransportCapability::Http11,
            TransportCapability::Tls,
            TransportCapability::RequestStreaming,
        ], $transport->capabilities()->all());
        self::assertSame($transport->capabilities(), $transport->capabilities());

        $session = $transport->open(FakeCurlExecutor::request());
        $session->close();
        self::assertNull($session->next());
    }
}
