<?php

declare(strict_types=1);

namespace Kongono\PhpCgi\Delivery;

use Closure;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DiagnosticActivation\AdapterControlledDeliveryPermission;
use Kongono\PhpCgi\Emission\CgiResponseEmitter;
use Kongono\PhpCgi\Exception\CgiDeliveryCleanupException;
use Kongono\PhpCgi\Exception\CgiRequestReadException;
use Kongono\PhpCgi\Inbound\CgiServerRequestConverter;
use Kongono\PhpCgi\Internal\CgiTrustedPresentationPermissionEvaluator;
use Kongono\Web\Runtime\Contract\WebDeliveryInterface;
use LogicException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Throwable;

/** Owns one bounded native CGI capture, conversion, emission, and cleanup lifecycle. */
#[Api(ApiStatus::Internal)]
final class CgiWebDelivery implements WebDeliveryInterface
{
    private const array SERVER_KEYS = [
        'GATEWAY_INTERFACE', 'FCGI_ROLE', 'REQUEST_METHOD', 'SERVER_PROTOCOL',
        'REQUEST_URI', 'QUERY_STRING', 'HTTP_HOST', 'REMOTE_ADDR',
        'CONTENT_LENGTH', 'CONTENT_TYPE', 'HTTPS', 'REMOTE_PORT', 'SERVER_ADDR',
        'SERVER_NAME', 'SERVER_PORT', 'SERVER_SOFTWARE', 'SCRIPT_NAME',
    ];

    private ?string $sapi;
    /** @var null|array<array-key, mixed> */
    private ?array $server;
    private ?bool $postEmpty;
    private ?bool $filesEmpty;
    /** @var null|Closure(): mixed */
    private ?Closure $openInput;
    /** @var null|Closure(mixed): bool */
    private ?Closure $closeInput;
    private mixed $input = null;
    private ?ServerRequestInterface $request = null;
    private ?StreamInterface $responseBody = null;
    private string $effectiveMethod;
    private bool $permissionEvaluated = false;
    private bool $requestAttempted = false;
    private bool $sendAttempted = false;
    private bool $closed = false;

    /**
     * @param array<array-key, mixed> $server
     * @param Closure(): mixed $openInput
     * @param null|Closure(mixed): bool $closeInput
     */
    private function __construct(
        string $sapi,
        array $server,
        bool $postEmpty,
        bool $filesEmpty,
        Closure $openInput,
        ?Closure $closeInput,
    ) {
        $this->sapi = $sapi;
        $this->server = $server;
        $this->postEmpty = $postEmpty;
        $this->filesEmpty = $filesEmpty;
        $this->openInput = $openInput;
        $this->closeInput = $closeInput;
        $this->effectiveMethod = 'HEAD' === ($server['REQUEST_METHOD'] ?? null) ? 'HEAD' : 'GET';
    }

    private function __clone(): void {}

    public static function capture(): self
    {
        return self::fromSnapshot(
            PHP_SAPI,
            self::projectServer($_SERVER),
            [] === $_POST,
            [] === $_FILES,
            static fn(): mixed => @fopen('php://input', 'rb'),
        );
    }

    /**
     * @param array<array-key, mixed> $server
     * @param Closure(): mixed $openInput
     * @param null|Closure(mixed): bool $closeInput
     */
    public static function fromSnapshot(
        string $sapi,
        array $server,
        bool $postEmpty,
        bool $filesEmpty,
        Closure $openInput,
        ?Closure $closeInput = null,
    ): self {
        return new self(
            $sapi,
            self::projectServer($server),
            $postEmpty,
            $filesEmpty,
            $openInput,
            $closeInput,
        );
    }

    public function evaluateTrustedPresentationPermission(): AdapterControlledDeliveryPermission
    {
        $this->requireOpen();
        if ($this->permissionEvaluated) {
            throw new LogicException('PHP CGI delivery permission was already evaluated.');
        }
        if ($this->requestAttempted) {
            throw new LogicException('PHP CGI delivery permission evaluation is out of order.');
        }
        $this->permissionEvaluated = true;

        return CgiTrustedPresentationPermissionEvaluator::evaluate($this->sapi ?? '', $this->server ?? []);
    }

    public function request(): ServerRequestInterface
    {
        $this->requireOpen();
        if ($this->requestAttempted) {
            throw new LogicException('PHP CGI delivery request was already attempted.');
        }
        $this->requestAttempted = true;

        $sapi = $this->sapi ?? '';
        $server = $this->server ?? [];
        $post = true === $this->postEmpty ? [] : [0 => null];
        $files = true === $this->filesEmpty ? [] : [0 => null];
        $opener = $this->openInput;

        try {
            if (null === $opener) {
                throw new LogicException('PHP CGI delivery input opener is unavailable.');
            }
            $input = $opener();
            if (!is_resource($input)) {
                throw new CgiRequestReadException('input_open_failed');
            }
            $this->input = $input;
            $request = (new CgiServerRequestConverter())->convert($sapi, $server, $post, $files, $input);
            $this->request = $request;
            $this->effectiveMethod = $request->getMethod();

            return $request;
        } finally {
            $this->sapi = null;
            $this->server = null;
            $this->postEmpty = null;
            $this->filesEmpty = null;
            $this->openInput = null;
        }
    }

    public function send(ResponseInterface $response): void
    {
        $this->requireOpen();
        if ($this->sendAttempted) {
            throw new LogicException('PHP CGI delivery response was already attempted.');
        }
        if (!$this->requestAttempted) {
            throw new LogicException('PHP CGI delivery response emission is out of order.');
        }
        $this->sendAttempted = true;
        $this->responseBody = $response->getBody();
        (new CgiResponseEmitter())->emit($response, $this->effectiveMethod);
    }

    public function close(): void
    {
        if ($this->closed) {
            throw new LogicException('PHP CGI delivery was already closed.');
        }
        $this->closed = true;

        $request = $this->request;
        $responseBody = $this->responseBody;
        $input = $this->input;
        $closer = $this->closeInput;
        $this->request = null;
        $this->responseBody = null;
        $this->input = null;
        $this->closeInput = null;
        $this->sapi = null;
        $this->server = null;
        $this->postEmpty = null;
        $this->filesEmpty = null;
        $this->openInput = null;
        $this->effectiveMethod = '';

        $firstFailure = null;
        $requestBody = null;
        if (null !== $request) {
            try {
                $requestBody = $request->getBody();
                $requestBody->close();
            } catch (Throwable $failure) {
                $firstFailure = $failure;
            }
        }
        if (null !== $responseBody && $responseBody !== $requestBody) {
            try {
                $responseBody->close();
            } catch (Throwable $failure) {
                $firstFailure ??= $failure;
            }
        }
        if (is_resource($input)) {
            try {
                $closed = null === $closer ? @fclose($input) : $closer($input);
                if (!$closed) {
                    throw new CgiDeliveryCleanupException('input_close_failed');
                }
            } catch (Throwable $failure) {
                $firstFailure ??= $failure;
            }
        }

        if (null !== $firstFailure) {
            throw $firstFailure;
        }
    }

    /**
     * @param array<array-key, mixed> $server
     * @return array<array-key, mixed>
     */
    private static function projectServer(array $server): array
    {
        $projected = [];
        foreach ($server as $key => $value) {
            if (!is_string($key)) {
                $projected[0] = null;
                continue;
            }
            if (in_array($key, self::SERVER_KEYS, true) || str_starts_with($key, 'HTTP_')) {
                $projected[$key] = $value;
            }
        }

        return $projected;
    }

    private function requireOpen(): void
    {
        if ($this->closed) {
            throw new LogicException('PHP CGI delivery is closed.');
        }
    }
}
