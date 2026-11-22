<?php

declare(strict_types=1);

namespace Kongono\Http;

use Psr\Http\Message\ResponseInterface;

/** Emits a PSR-7 / streaming Response to SAPI output in bounded chunks. */
final class ResponseEmitter
{
    public function emit(ResponseInterface $response): void
    {
        if (!headers_sent()) {
            $proto = $response->getProtocolVersion();
            $code = $response->getStatusCode();
            $reason = $response->getReasonPhrase();
            if (1 !== preg_match('/\A\d(?:\.\d)?\z/', $proto)) {
                $proto = '1.1';
            }
            if ($code < 100 || $code > 599) {
                $code = 500;
            }
            $cleanReason = (string) preg_replace('/[\x00-\x08\x0a-\x1f\x7f]/', '', $reason);
            $statusLine = sprintf(
                'HTTP/%s %d%s',
                $proto,
                $code,
                '' !== $cleanReason ? ' ' . $cleanReason : '',
            );
            header($statusLine, true, $code);

            foreach ($response->getHeaders() as $name => $values) {
                if (1 !== preg_match('/\A[a-zA-Z0-9!#$%&\'*+.^_`|~-]+\z/', $name)) {
                    continue;
                }
                $first = 'set-cookie' !== strtolower($name);
                foreach ($values as $value) {
                    if (1 === preg_match('/[\x00-\x08\x0a-\x1f\x7f]/', $value)) {
                        continue;
                    }
                    header(sprintf('%s: %s', $name, $value), $first);
                    $first = false;
                }
            }
        }

        if ($response instanceof Response && null !== $response->streamProducer()) {
            foreach (($response->streamProducer())() as $chunk) {
                echo $chunk;
                if (function_exists('flush')) {
                    flush();
                }
            }

            return;
        }

        $body = $response->getBody();
        if ($body->isSeekable()) {
            $body->rewind();
        }
        while (!$body->eof()) {
            echo $body->read(8192);
        }
    }
}
