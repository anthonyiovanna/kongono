<?php

declare(strict_types=1);

namespace Kongono\PhpCgi\Inbound;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Http\Message\Exception\ExceptionInterface as HttpMessageExceptionInterface;
use Kongono\Http\Message\Message\ServerRequest;
use Kongono\Http\Message\Message\Stream;
use Kongono\PhpCgi\Diagnostic\PhpCgiDiagnostic;
use Kongono\PhpCgi\Exception\CgiRequestReadException;
use Kongono\PhpCgi\Exception\InvalidCgiRequestException;
use Kongono\PhpCgi\Internal\CgiCookieParser;
use Kongono\PhpCgi\Internal\CgiHeaderProjector;
use Kongono\PhpCgi\Internal\CgiQueryParser;
use Kongono\PhpCgi\Internal\CgiRequestValidation;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/** Converts one explicit bounded CGI snapshot without retaining per-call state. */
#[Api(ApiStatus::Experimental)]
final class CgiServerRequestConverter
{
    /**
     * @param array<array-key, mixed> $server
     * @param array<array-key, mixed> $post
     * @param array<array-key, mixed> $files
     * @param resource                $input
     *
     * @throws InvalidCgiRequestException
     * @throws CgiRequestReadException
     * @throws \Throwable When external stream behavior or another unexpected
     *                    non-HTTP-Message failure throws.
     */
    public function convert(
        string $sapi,
        array $server,
        array $post,
        array $files,
        mixed $input,
    ): ServerRequestInterface {
        $snapshot = CgiRequestValidation::snapshot($sapi, $server, $post, $files);
        $method = CgiRequestValidation::method($snapshot['REQUEST_METHOD']);
        $protocol = CgiRequestValidation::protocol($snapshot['SERVER_PROTOCOL']);
        [$target, $path, $queryString] = CgiRequestValidation::target(
            $snapshot['REQUEST_URI'],
            $snapshot['QUERY_STRING'],
        );
        $scheme = CgiRequestValidation::scheme($snapshot['HTTPS'] ?? null);
        $authority = CgiRequestValidation::authority($snapshot['HTTP_HOST']);
        $contentLength = CgiRequestValidation::contentLength($snapshot['CONTENT_LENGTH'] ?? null);
        CgiRequestValidation::rejectMultipart($snapshot['CONTENT_TYPE'] ?? null);
        $headers = CgiHeaderProjector::project($snapshot);
        $query = CgiQueryParser::parse($queryString);
        $cookies = CgiCookieParser::parse($headers['Cookie'][0] ?? null);

        try {
            $uri = CgiRequestValidation::uri($scheme, $authority, $path, $queryString);
        } catch (HttpMessageExceptionInterface $failure) {
            throw new InvalidCgiRequestException(
                PhpCgiDiagnostic::InvalidRequestTarget,
                'authority_invalid',
                $failure,
            );
        }

        $bodyResource = CgiRequestValidation::bodyResource($input, $contentLength);
        $body = null;

        try {
            $body = new Stream($bodyResource);
            $request = new ServerRequest($method, $uri, $snapshot, $headers, $body, $protocol);
            $request = $request->withQueryParams($query);
            $request = $request->withCookieParams($cookies);
            $request = $request->withRequestTarget($target);

            return $request;
        } catch (HttpMessageExceptionInterface $failure) {
            self::closeOwnedBody($body, $bodyResource);
            throw new InvalidCgiRequestException(
                PhpCgiDiagnostic::RequestConstructionFailed,
                'first_party_message_rejected',
                $failure,
            );
        } catch (Throwable $failure) {
            self::closeOwnedBody($body, $bodyResource);
            throw $failure;
        }
    }

    /** @param resource $resource */
    private static function closeOwnedBody(?Stream $body, mixed $resource): void
    {
        try {
            if (null !== $body) {
                $body->close();
            } elseif (is_resource($resource)) {
                @fclose($resource);
            }
        } catch (Throwable) {
        }
    }
}
