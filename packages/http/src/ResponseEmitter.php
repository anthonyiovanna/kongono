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
            $statusLine = sprintf(
                'HTTP/%s %d%s',
                $response->getProtocolVersion(),
                $response->getStatusCode(),
                '' !== $response->getReasonPhrase() ? ' ' . $response->getReasonPhrase() : '',
            );
            header($statusLine, true, $response->getStatusCode());

            foreach ($response->getHeaders() as $name => $values) {
                $first = 'set-cookie' !== strtolower($name);
                foreach ($values as $value) {
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
