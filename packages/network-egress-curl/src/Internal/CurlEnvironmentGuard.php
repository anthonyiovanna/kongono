<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressCurl\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressCurl\Exception\InvalidCurlTransportProfileException;

/** Validates the fixed native capability profile without changing configuration. */
#[Api(ApiStatus::Internal)]
final class CurlEnvironmentGuard
{
    private const array REQUIRED_CONSTANTS = [
        'CURLOPT_CAINFO_BLOB', 'CURLOPT_PROTOCOLS_STR', 'CURLOPT_REDIR_PROTOCOLS_STR', 'CURLOPT_RESOLVE',
        'CURLOPT_XFERINFOFUNCTION', 'CURLOPT_HTTP_CONTENT_DECODING', 'CURLOPT_HTTP_TRANSFER_DECODING',
        'CURL_HTTP_VERSION_1_1', 'CURL_NETRC_IGNORED',
    ];

    public static function assertSupported(): void
    {
        if (!extension_loaded('curl') || !function_exists('curl_version')) {
            throw InvalidCurlTransportProfileException::environment();
        }
        foreach (self::REQUIRED_CONSTANTS as $constant) {
            if (!defined($constant)) {
                throw InvalidCurlTransportProfileException::environment();
            }
        }
        $version = curl_version();
        $protocols = $version['protocols'] ?? null;
        $sslVersion = $version['ssl_version'] ?? null;
        if (!is_array($protocols) || !in_array('http', $protocols, true) || !in_array('https', $protocols, true) || !is_string($sslVersion) || '' === $sslVersion) {
            throw InvalidCurlTransportProfileException::environment();
        }
    }

    private function __construct() {}
}
