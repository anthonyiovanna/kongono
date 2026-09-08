<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressCurl\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressCurl\Exception\InvalidCurlTransportProfileException;
use SensitiveParameter;

/** Parses bounded callback chunks and selects one final non-informational response. */
#[Api(ApiStatus::Internal)]
final class CurlHeaderParser
{
    /** @param list<string> $chunks */
    public function parse(#[SensitiveParameter] array $chunks, int $retainedBytes): CurlResponseHeaderBlock
    {
        $blocks = [];
        $current = [];
        foreach ($chunks as $chunk) {
            if (!str_ends_with($chunk, "\r\n")) {
                throw InvalidCurlTransportProfileException::request('response_header');
            }
            if ("\r\n" === $chunk) {
                if ([] === $current) {
                    throw InvalidCurlTransportProfileException::request('response_header');
                }
                $blocks[] = $current;
                $current = [];
                continue;
            }
            $current[] = $chunk;
        }
        if ([] !== $current || [] === $blocks) {
            throw InvalidCurlTransportProfileException::request('response_header');
        }

        $final = null;
        foreach ($blocks as $lines) {
            $statusLine = array_shift($lines);
            if (1 !== preg_match('/\AHTTP\/1\.1 ([1-5][0-9]{2})(?: ([\x20-\x7E]*))?\r\n\z/D', $statusLine, $matches)) {
                throw InvalidCurlTransportProfileException::request('response_header');
            }
            $status = (int) $matches[1];
            $reason = $matches[2] ?? '';
            $headers = [];
            foreach ($lines as $line) {
                if (1 !== preg_match("/\A([!#$%&'*+.^_`|~0-9A-Za-z-]+):[ \t]*([^\r\n]*)\r\n\z/D", $line, $header)) {
                    throw InvalidCurlTransportProfileException::request('response_header');
                }
                $value = trim($header[2], " \t");
                if (1 === preg_match('/[\x00-\x08\x0A-\x1F\x7F]/', $value)) {
                    throw InvalidCurlTransportProfileException::request('response_header');
                }
                $headers[$header[1]] ??= [];
                $headers[$header[1]][] = $value;
            }
            if ($status >= 200) {
                if (null !== $final) {
                    throw InvalidCurlTransportProfileException::request('response_header');
                }
                $final = new CurlResponseHeaderBlock($status, $reason, $headers, $retainedBytes);
            }
        }
        if (null === $final) {
            throw InvalidCurlTransportProfileException::request('response_header');
        }

        return $final;
    }
}
