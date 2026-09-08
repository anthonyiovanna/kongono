<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressCurl\Internal;

use CurlHandle;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressContract\Origin\OriginScheme;
use Kongono\NetworkEgressCurl\Profile\CurlTransportProfile;
use Kongono\NetworkEgressRuntime\Address\AddressFamily;
use Kongono\NetworkEgressRuntime\Transport\EgressTransportRequest;
use SensitiveParameter;

/** Builds the allowlisted native option posture from explicit admitted values. */
#[Api(ApiStatus::Internal)]
final class CurlOptionBuilder
{
    /** @return array<int, mixed> */
    public function build(
        #[SensitiveParameter]
        EgressTransportRequest $transportRequest,
        #[SensitiveParameter]
        CurlTransportProfile $profile,
        CurlRequestBodyReader $bodyReader,
        CurlResponseAccumulator $accumulator,
    ): array {
        $operation = $transportRequest->operation();
        $request = $operation->request();
        $origin = $operation->origin();
        $budget = $operation->budget();
        $host = str_contains($origin->host(), ':') ? '[' . $origin->host() . ']' : $origin->host();
        $defaultPort = (OriginScheme::Http === $origin->scheme() ? 80 : 443) === $origin->port();
        $url = $origin->scheme()->value . '://' . $host . ($defaultPort ? '' : ':' . $origin->port()) . $request->getRequestTarget();
        $address = $transportRequest->selectedAddress()->value();
        if (AddressFamily::Ipv6 === $transportRequest->selectedAddress()->family()) {
            $address = '[' . $address . ']';
        }
        $headers = [];
        foreach ($request->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                $headers[] = $name . ': ' . $value;
            }
        }

        return [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => $request->getMethod(),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_PROTOCOLS_STR => 'http,https',
            CURLOPT_REDIR_PROTOCOLS_STR => 'http,https',
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_MAXREDIRS => 0,
            CURLOPT_PROXY => '',
            CURLOPT_NOPROXY => '*',
            CURLOPT_NETRC => CURL_NETRC_IGNORED,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_CAINFO_BLOB => $profile->tlsTrust()->caBundlePem(),
            CURLOPT_HTTP_CONTENT_DECODING => false,
            CURLOPT_HTTP_TRANSFER_DECODING => true,
            CURLOPT_HTTP09_ALLOWED => false,
            CURLOPT_FAILONERROR => false,
            CURLOPT_CONNECTTIMEOUT_MS => $budget->connectTimeoutMilliseconds(),
            CURLOPT_TIMEOUT_MS => $budget->totalTimeoutMilliseconds(),
            CURLOPT_NOSIGNAL => true,
            CURLOPT_FRESH_CONNECT => true,
            CURLOPT_FORBID_REUSE => true,
            CURLOPT_RESOLVE => [$origin->host() . ':' . $origin->port() . ':' . $address],
            CURLOPT_UPLOAD => $bodyReader->remainingBytes() > 0,
            CURLOPT_INFILESIZE => $bodyReader->remainingBytes(),
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_HEADER => false,
            CURLOPT_NOPROGRESS => false,
            CURLOPT_READFUNCTION => static fn(CurlHandle $_handle, mixed $_stream, int $maximumBytes): string => $bodyReader->read($maximumBytes),
            CURLOPT_HEADERFUNCTION => static fn(CurlHandle $_handle, string $chunk): int => $accumulator->appendHeader($chunk),
            CURLOPT_WRITEFUNCTION => static fn(CurlHandle $_handle, string $chunk): int => $accumulator->appendBody($chunk),
            CURLOPT_XFERINFOFUNCTION => static function (CurlHandle $_handle, float $_downloadTotal, float $downloadNow, float $_uploadTotal, float $uploadNow) use ($budget): int {
                return $downloadNow > $budget->maximumResponseBytes() || $uploadNow > $budget->maximumRequestBytes() ? 1 : 0;
            },
        ];
    }
}
