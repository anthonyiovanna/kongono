<?php

declare(strict_types=1);

namespace Kongono\ViewHttp\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewContract\Render\RenderResult;
use Kongono\ViewHttp\Exception\ViewHttpAdaptationException;
use Kongono\ViewHttp\Policy\ViewResponsePolicy;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Throwable;

/** Creates and verifies one exact immutable PSR-7 response. */
#[Api(ApiStatus::Internal)]
final class ResponseAssembler
{
    public static function assemble(ResponseFactoryInterface $responses, StreamFactoryInterface $streams, RenderResult $result, ViewResponsePolicy $policy): ResponseInterface
    {
        $bytes = $result->output()->bytes();
        $body = self::body($streams, $bytes);
        try {
            $response = $responses->createResponse($policy->statusCode(), $policy->reasonPhrase());
        } catch (Throwable $exception) {
            throw ViewHttpAdaptationException::response('factory_throwable', $policy->statusCode(), $exception);
        }
        try {
            if ($response->getStatusCode() !== $policy->statusCode()) {
                throw ViewHttpAdaptationException::response('status_mismatch', $policy->statusCode());
            }
            if ($response->getReasonPhrase() !== $policy->reasonPhrase()) {
                throw ViewHttpAdaptationException::response('reason_mismatch', $policy->statusCode());
            }
        } catch (ViewHttpAdaptationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw ViewHttpAdaptationException::response('verification_throwable', $policy->statusCode(), $exception);
        }

        $expected = ['Content-Type' => [$policy->contentType()->value], 'Content-Length' => [(string) strlen($bytes)]];
        foreach ($policy->headers() as $header) {
            $expected[$header->name()] = $header->values();
        }
        try {
            $response = $response->withBody($body);
            foreach ($expected as $name => $values) {
                $response = $response->withHeader($name, $values);
            }
        } catch (Throwable $exception) {
            throw ViewHttpAdaptationException::assembly('mutation_throwable', $policy->statusCode(), count($expected), $exception);
        }

        try {
            self::verify($response, $body, $policy, $expected);
        } catch (ViewHttpAdaptationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw ViewHttpAdaptationException::assembly('verification_throwable', $policy->statusCode(), count($expected), $exception);
        }

        return $response;
    }

    private static function body(StreamFactoryInterface $streams, string $bytes): \Psr\Http\Message\StreamInterface
    {
        try {
            $body = $streams->createStream($bytes);
            if (!$body->isReadable()) {
                throw ViewHttpAdaptationException::stream('not_readable', strlen($bytes));
            }
            if (!$body->isSeekable()) {
                throw ViewHttpAdaptationException::stream('not_seekable', strlen($bytes));
            }
            if ($body->getSize() !== strlen($bytes)) {
                throw ViewHttpAdaptationException::stream('size_mismatch', strlen($bytes));
            }
            $body->rewind();
            if ($body->getContents() !== $bytes) {
                throw ViewHttpAdaptationException::stream('content_mismatch', strlen($bytes));
            }
            $body->rewind();
            if (0 !== $body->tell()) {
                throw ViewHttpAdaptationException::stream('position_mismatch', strlen($bytes));
            }

            return $body;
        } catch (ViewHttpAdaptationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw ViewHttpAdaptationException::stream('factory_throwable', strlen($bytes), $exception);
        }
    }

    /** @param array<string, list<string>> $expected */
    private static function verify(ResponseInterface $response, \Psr\Http\Message\StreamInterface $body, ViewResponsePolicy $policy, array $expected): void
    {
        if ($response->getStatusCode() !== $policy->statusCode()) {
            throw ViewHttpAdaptationException::assembly('status_mismatch', $policy->statusCode(), count($expected));
        }
        if ($response->getReasonPhrase() !== $policy->reasonPhrase()) {
            throw ViewHttpAdaptationException::assembly('reason_mismatch', $policy->statusCode(), count($expected));
        }
        if ($response->getBody() !== $body) {
            throw ViewHttpAdaptationException::assembly('body_mismatch', $policy->statusCode(), count($expected));
        }
        $actual = $response->getHeaders();
        if (count($actual) !== count($expected)) {
            throw ViewHttpAdaptationException::assembly('unexpected_header', $policy->statusCode(), count($expected));
        }
        if (array_keys($actual) !== array_keys($expected)) {
            throw ViewHttpAdaptationException::assembly('header_mismatch', $policy->statusCode(), count($expected));
        }
        foreach ($expected as $name => $values) {
            if (!$response->hasHeader($name) || $response->getHeader($name) !== $values) {
                throw ViewHttpAdaptationException::assembly('header_mismatch', $policy->statusCode(), count($expected));
            }
        }
    }
}
