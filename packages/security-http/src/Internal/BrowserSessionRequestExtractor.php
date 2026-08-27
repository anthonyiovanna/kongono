<?php

declare(strict_types=1);

namespace Kongono\SecurityHttp\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\SecurityHttp\BrowserSessionCsrfProof;
use Kongono\SecurityHttp\BrowserSessionIdentifier;
use Kongono\SecurityHttp\BrowserSessionProfile;
use Kongono\SecurityHttp\Exception\BrowserSessionRequestException;
use Kongono\SecurityHttp\TrustedOrigin;
use Psr\Http\Message\ServerRequestInterface;

/** Parses only the fixed bounded request fields used by the selected profile. */
#[Api(ApiStatus::Internal)]
final readonly class BrowserSessionRequestExtractor
{
    private const int MAX_COOKIE_BYTES = 4096;
    private const int MAX_COOKIE_PAIRS = 64;
    private const int MAX_METHOD_BYTES = 32;

    public function __construct(private TrustedOrigin $trustedOrigin) {}

    public function extract(ServerRequestInterface $request): BrowserSessionRequestProfile
    {
        $method = $request->getMethod();
        if (
            '' === $method
            || strlen($method) > self::MAX_METHOD_BYTES
            || 1 !== preg_match("/\\A[!#$%&'*+.^_`|~0-9A-Za-z-]+\\z/D", $method)
        ) {
            throw BrowserSessionRequestException::transport();
        }

        $sessionIdentifier = $this->sessionIdentifier(array_values($request->getHeader('Cookie')));
        if (null === $sessionIdentifier) {
            return BrowserSessionRequestProfile::anonymous();
        }

        if ($this->requestOrigin($request) !== $this->trustedOrigin->value()) {
            throw BrowserSessionRequestException::transport();
        }

        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            return new BrowserSessionRequestProfile($sessionIdentifier, null);
        }

        $originHeaders = $request->getHeader('Origin');
        if (1 !== count($originHeaders) || $originHeaders[0] !== $this->trustedOrigin->value()) {
            throw BrowserSessionRequestException::csrf();
        }
        $csrfHeaders = $request->getHeader(BrowserSessionProfile::CSRF_HEADER_NAME);
        if (1 !== count($csrfHeaders)) {
            throw BrowserSessionRequestException::csrf();
        }

        try {
            $csrfProof = new BrowserSessionCsrfProof($csrfHeaders[0]);
        } catch (\Throwable) {
            throw BrowserSessionRequestException::csrf();
        }

        return new BrowserSessionRequestProfile($sessionIdentifier, $csrfProof);
    }

    /** @param list<string> $headers */
    private function sessionIdentifier(array $headers): ?BrowserSessionIdentifier
    {
        if ([] === $headers) {
            return null;
        }
        if (1 !== count($headers)) {
            throw BrowserSessionRequestException::cookie();
        }

        $raw = $headers[0];
        if (strlen($raw) > self::MAX_COOKIE_BYTES) {
            throw BrowserSessionRequestException::cookie();
        }
        $pairs = explode(';', $raw);
        if (count($pairs) > self::MAX_COOKIE_PAIRS) {
            throw BrowserSessionRequestException::cookie();
        }

        $reserved = null;
        foreach ($pairs as $pair) {
            if (1 !== preg_match("/\\A[ \\t]*([!#$%&'*+.^_`|~0-9A-Za-z-]+)=([!#$%&'()*+.\\-\\/:<=>?@\\[\\]^_`{|}~0-9A-Za-z]*)[ \\t]*\\z/D", $pair, $matches)) {
                throw BrowserSessionRequestException::cookie();
            }
            if (BrowserSessionProfile::COOKIE_NAME !== $matches[1]) {
                continue;
            }
            if (null !== $reserved) {
                throw BrowserSessionRequestException::cookie();
            }
            try {
                $reserved = new BrowserSessionIdentifier($matches[2]);
            } catch (\Throwable) {
                throw BrowserSessionRequestException::cookie();
            }
        }

        return $reserved;
    }

    private function requestOrigin(ServerRequestInterface $request): string
    {
        $uri = $request->getUri();
        if ('https' !== $uri->getScheme() || '' === $uri->getHost() || '' !== $uri->getUserInfo()) {
            throw BrowserSessionRequestException::transport();
        }

        $host = $uri->getHost();
        if (str_contains($host, ':') && !str_starts_with($host, '[')) {
            $host = '[' . $host . ']';
        }
        $port = $uri->getPort();

        return 'https://' . $host . (null === $port ? '' : ':' . $port);
    }
}
