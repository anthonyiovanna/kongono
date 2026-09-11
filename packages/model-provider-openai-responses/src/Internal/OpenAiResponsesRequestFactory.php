<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceApplication\Operation\ExternalServiceRequestFactory;
use Kongono\ExternalServiceContract\Binding\ServiceBinding;
use Kongono\ExternalServiceContract\Operation\ExternalServiceOperationIdentity;
use Kongono\Http\Message\Factory\StreamFactory;
use Kongono\Http\Message\Message\Request;
use Kongono\ModelProviderOpenAiResponses\Exception\OpenAiResponsesInvocationException;
use Kongono\ModelProviderOpenAiResponses\Request\EncodedResponsesRequest;
use Kongono\NetworkEgressContract\Origin\CanonicalOrigin;
use Kongono\NetworkEgressContract\Origin\OriginScheme;
use Psr\Http\Message\RequestInterface;
use Throwable;

/** Constructs one exact credential-backed outbound request while material is borrowed. */
#[Api(ApiStatus::Internal)]
final class OpenAiResponsesRequestFactory implements ExternalServiceRequestFactory
{
    private ?EncodedResponsesRequest $encodedRequest;

    public function __construct(
        EncodedResponsesRequest $encodedRequest,
        private readonly ExternalServiceOperationIdentity $expectedOperationIdentity,
    ) {
        $this->encodedRequest = $encodedRequest;
    }

    public function create(
        ServiceBinding $binding,
        ExternalServiceOperationIdentity $operationIdentity,
        #[\SensitiveParameter]
        string $credentialMaterial,
    ): RequestInterface {
        $encoded = $this->encodedRequest;
        $this->encodedRequest = null;
        if (!$encoded instanceof EncodedResponsesRequest) {
            throw OpenAiResponsesInvocationException::lifecycle('session_state');
        }

        $stream = null;
        try {
            if (!$this->expectedOperationIdentity->equals($operationIdentity)) {
                throw OpenAiResponsesInvocationException::invalidContext('operation_identity');
            }
            self::validateBinding($binding);
            self::validateCredential($credentialMaterial);

            $stream = (new StreamFactory())->createStream($encoded->body());
            $request = new Request('POST', self::uri($binding->endpoint()->origin()), [
                'Accept' => $encoded->streaming() ? 'text/event-stream' : EncodedResponsesRequest::MEDIA_TYPE,
                'Content-Type' => EncodedResponsesRequest::MEDIA_TYPE,
                'Authorization' => 'Bearer ' . $credentialMaterial,
            ], $stream);
            $stream = null;

            return $request;
        } catch (OpenAiResponsesInvocationException $failure) {
            throw $failure;
        } catch (Throwable $failure) {
            throw OpenAiResponsesInvocationException::invalidContext('endpoint', $failure);
        } finally {
            if (null !== $stream) {
                try {
                    $stream->close();
                } catch (Throwable) {
                }
            }
            unset($encoded, $credentialMaterial, $request);
        }
    }

    private static function validateBinding(ServiceBinding $binding): void
    {
        if (OriginScheme::Https !== $binding->endpoint()->origin()->scheme()
            || !$binding->endpoint()->basePath()->isEmpty()
        ) {
            throw OpenAiResponsesInvocationException::invalidContext('endpoint');
        }
    }

    private static function validateCredential(#[\SensitiveParameter] string $credentialMaterial): void
    {
        if ('' === $credentialMaterial
            || strlen($credentialMaterial) > 16_384
            || 1 === preg_match('/[\x00-\x1F\x7F]/', $credentialMaterial)
        ) {
            throw OpenAiResponsesInvocationException::invalidContext('credential_material');
        }
    }

    private static function uri(CanonicalOrigin $origin): string
    {
        $host = str_contains($origin->host(), ':') ? '[' . $origin->host() . ']' : $origin->host();
        $port = 443 === $origin->port() ? '' : ':' . $origin->port();

        return 'https://' . $host . $port . EncodedResponsesRequest::RELATIVE_PATH;
    }
}
