<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressCurl\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressContract\Origin\CanonicalOrigin;
use Kongono\NetworkEgressContract\Origin\OriginScheme;
use Kongono\NetworkEgressCurl\Exception\InvalidCurlTransportProfileException;
use Kongono\NetworkEgressCurl\Profile\CurlTransportProfile;
use Kongono\NetworkEgressRuntime\Transport\EgressTransportRequest;
use SensitiveParameter;

/** Admits one canonical bounded request before native handle construction. */
#[Api(ApiStatus::Internal)]
final class CurlRequestGuard
{
    private const array PROHIBITED_HEADERS = [
        'connection', 'expect', 'keep-alive', 'proxy-authorization', 'proxy-connection',
        'trailer', 'transfer-encoding', 'upgrade',
    ];

    public static function admit(
        #[SensitiveParameter]
        EgressTransportRequest $transportRequest,
        #[SensitiveParameter]
        CurlTransportProfile $profile,
    ): CurlRequestBodyReader {
        $operation = $transportRequest->operation();
        $request = $operation->request();
        $origin = $operation->origin();
        if (!$origin->equals(CanonicalOrigin::fromUri($request->getUri()))) {
            throw InvalidCurlTransportProfileException::request('operation_origin');
        }
        $scheme = $request->getUri()->getScheme();
        if (!in_array($scheme, ['http', 'https'], true)) {
            throw InvalidCurlTransportProfileException::request('scheme');
        }
        if (OriginScheme::Http === $origin->scheme() && !$profile->allowsPlainHttp()) {
            throw InvalidCurlTransportProfileException::request('plain_http_disabled');
        }
        if ('1.1' !== $request->getProtocolVersion()) {
            throw InvalidCurlTransportProfileException::request('protocol');
        }

        $target = $request->getRequestTarget();
        if (
            !str_starts_with($target, '/')
            || str_starts_with($target, '//')
            || str_contains($target, '#')
            || 1 === preg_match('/[\x00-\x20\x7F]/', $target)
            || 1 === preg_match('/\A[a-z][a-z0-9+.-]*:/i', $target)
        ) {
            throw InvalidCurlTransportProfileException::request('request_target');
        }
        $method = $request->getMethod();
        if (1 !== preg_match("/\A[!#$%&'*+.^_`|~0-9A-Za-z-]+\z/D", $method) || in_array(strtoupper($method), ['CONNECT', 'TRACE'], true)) {
            throw InvalidCurlTransportProfileException::request('method');
        }

        $expectedHost = self::hostHeader($origin);
        $hostValues = $request->getHeader('Host');
        if (1 !== count($hostValues) || $expectedHost !== $hostValues[0]) {
            throw InvalidCurlTransportProfileException::request('host_header');
        }

        $budget = $operation->budget();
        $headerCount = 0;
        $headerBytes = 0;
        foreach ($request->getHeaders() as $name => $values) {
            if (1 !== preg_match("/\A[!#$%&'*+.^_`|~0-9A-Za-z-]+\z/D", $name) || in_array(strtolower($name), self::PROHIBITED_HEADERS, true)) {
                throw InvalidCurlTransportProfileException::request('header_grammar');
            }
            foreach ($values as $value) {
                if (1 === preg_match('/[\r\n\x00]/', $value)) {
                    throw InvalidCurlTransportProfileException::request('header_grammar');
                }
                ++$headerCount;
                $headerBytes = self::add($headerBytes, strlen($name) + 2 + strlen($value) + 2, 'header_budget');
            }
        }
        if ($headerCount > $budget->maximumHeaderCount() || $headerBytes > $budget->maximumAggregateHeaderBytes()) {
            throw InvalidCurlTransportProfileException::request('header_budget', max($headerCount, $headerBytes), max($budget->maximumHeaderCount(), $budget->maximumAggregateHeaderBytes()));
        }

        $body = $request->getBody();
        if (!$body->isReadable()) {
            throw InvalidCurlTransportProfileException::request('stream_state');
        }
        try {
            $size = $body->getSize();
            $position = $body->tell();
        } catch (\Throwable) {
            throw InvalidCurlTransportProfileException::request('body_cursor');
        }
        if (null === $size || $position < 0 || $position > $size) {
            throw InvalidCurlTransportProfileException::request('body_cursor');
        }
        $remaining = $size - $position;
        $contentLength = $request->getHeader('Content-Length');
        if ([] !== $contentLength) {
            if (1 !== count($contentLength) || 1 !== preg_match('/\A(?:0|[1-9][0-9]*)\z/D', $contentLength[0]) || (int) $contentLength[0] !== $remaining) {
                throw InvalidCurlTransportProfileException::request('content_length');
            }
        }

        $requestBytes = self::add(strlen($method) + 1 + strlen($target) + 11, $headerBytes + 2, 'request_budget');
        $requestBytes = self::add($requestBytes, $remaining, 'request_budget');
        if ($requestBytes > $budget->maximumRequestBytes()) {
            throw InvalidCurlTransportProfileException::request('request_budget', $requestBytes, $budget->maximumRequestBytes());
        }
        if ($budget->maximumResponseBytes() > $budget->maximumBufferedBytes()) {
            throw InvalidCurlTransportProfileException::request('buffer_budget', $budget->maximumResponseBytes(), $budget->maximumBufferedBytes());
        }

        return new CurlRequestBodyReader($body, $remaining, $requestBytes - $remaining);
    }

    public static function hostHeader(CanonicalOrigin $origin): string
    {
        $host = str_contains($origin->host(), ':') ? '[' . $origin->host() . ']' : $origin->host();
        $defaultPort = (OriginScheme::Http === $origin->scheme() ? 80 : 443) === $origin->port();

        return $defaultPort ? $host : $host . ':' . $origin->port();
    }

    private static function add(int $left, int $right, string $violation): int
    {
        if ($right < 0 || $left > PHP_INT_MAX - $right) {
            throw InvalidCurlTransportProfileException::request($violation);
        }

        return $left + $right;
    }

    private function __construct() {}
}
