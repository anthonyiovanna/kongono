<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressCurl\Tests\Unit\Internal;

use Kongono\NetworkEgressContract\Transport\NegotiatedProtocol;
use Kongono\NetworkEgressCurl\Internal\CurlProtocolMapper;
use PHPUnit\Framework\TestCase;

final class CurlProtocolMapperTest extends TestCase
{
    public function testOnlyExactHttp11NativeEvidenceIsAccepted(): void
    {
        $mapper = new CurlProtocolMapper();
        self::assertSame(NegotiatedProtocol::Http11, $mapper->map(CURL_HTTP_VERSION_1_1));
        self::assertSame(NegotiatedProtocol::Unknown, $mapper->map(CURL_HTTP_VERSION_2_0));
        self::assertSame(NegotiatedProtocol::Unknown, $mapper->map(0));
    }
}
