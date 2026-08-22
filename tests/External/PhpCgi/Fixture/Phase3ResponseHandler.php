<?php

declare(strict_types=1);

namespace Kongono\Tests\External\PhpCgi\Fixture;

use Kongono\Http\Message\Factory\StreamFactory;
use Kongono\Http\Message\Message\Response;
use Kongono\Http\Server\RoutingRequestHandler;
use Kongono\Routing\Match\RouteMatch;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

/** Produces only the closed Phase 3 response-boundary cases. */
final class Phase3ResponseHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $match = $request->getAttribute(RoutingRequestHandler::ROUTE_MATCH_ATTRIBUTE);
        if (!$match instanceof RouteMatch || 'phase3.response' !== $match->route()->name()) {
            throw new RuntimeException('phase3_response_route_invalid');
        }
        $encodedCase = $match->parameters()['case'] ?? null;
        if (!is_string($encodedCase)) {
            throw new RuntimeException('phase3_response_case_invalid');
        }
        $case = rawurldecode($encodedCase);

        return match ($case) {
            'status-199' => $this->response(199),
            'status-200' => $this->response(200),
            'status-599' => $this->response(599),
            'status-600' => $this->response(600),
            'protocol-1-0' => $this->response(protocol: '1.0'),
            'protocol-1-1' => $this->response(protocol: '1.1'),
            'protocol-2' => $this->response(protocol: '2'),
            'protocol-invalid' => $this->response(protocol: '9.9'),
            'ordered-fields' => $this->response(headers: [
                'X-Phase3-First' => ['one', 'two'],
                'X-Phase3-Second' => ['three'],
            ]),
            'forbidden-field' => $this->response(headers: ['Connection' => ['close']]),
            'fields-127' => $this->response(headers: self::distinctHeaders(126)),
            'fields-128' => $this->response(headers: self::distinctHeaders(127)),
            'fields-129' => $this->response(headers: self::distinctHeaders(128)),
            'values-31' => $this->response(headers: ['X-Phase3-Values' => array_fill(0, 31, 'v')]),
            'values-32' => $this->response(headers: ['X-Phase3-Values' => array_fill(0, 32, 'v')]),
            'values-33' => $this->response(headers: ['X-Phase3-Values' => array_fill(0, 33, 'v')]),
            'value-8191' => $this->response(headers: ['X-Phase3-Value' => [str_repeat('v', 8191)]]),
            'value-8192' => $this->response(headers: ['X-Phase3-Value' => [str_repeat('v', 8192)]]),
            'value-8193' => $this->response(headers: ['X-Phase3-Value' => [str_repeat('v', 8193)]]),
            'total-65535' => $this->response(headers: self::totalHeaders(65535 - self::traceHeaderBytes())),
            'total-65536' => $this->response(headers: self::totalHeaders(65536 - self::traceHeaderBytes())),
            'total-65537' => $this->response(headers: self::totalHeaders(65537 - self::traceHeaderBytes())),
            'body-1048575' => $this->response(body: str_repeat('b', 1_048_575)),
            'body-1048576' => $this->response(body: str_repeat('b', 1_048_576)),
            'body-1048577' => $this->response(body: str_repeat('b', 1_048_577)),
            'body' => $this->response(body: "Phase 3 response\n"),
            default => throw new RuntimeException('phase3_response_case_invalid'),
        };
    }

    /**
     * @param array<string, list<string>> $headers
     */
    private function response(
        int $status = 200,
        array $headers = ['Content-Type' => ['text/plain; charset=utf-8']],
        string $body = "Phase 3 response\n",
        string $protocol = '1.1',
    ): ResponseInterface {
        return new Response(
            $status,
            $headers,
            (new StreamFactory())->createStream($body),
            $protocol,
        );
    }

    /** @return array<string, list<string>> */
    private static function distinctHeaders(int $count): array
    {
        $headers = [];
        for ($index = 0; $index < $count; ++$index) {
            $headers['X-Phase3-' . sprintf('%03d', $index)] = ['v'];
        }

        return $headers;
    }

    /** @return array<string, list<string>> */
    private static function totalHeaders(int $bytes): array
    {
        $headers = [];
        $remaining = $bytes;
        for ($index = 0; $remaining > 0; ++$index) {
            $name = 'X-Pad-' . sprintf('%03d', $index);
            $valueLength = min(8192, $remaining - strlen($name));
            if ($valueLength < 0) {
                throw new RuntimeException('phase3_response_total_invalid');
            }
            $headers[$name] = [str_repeat('v', $valueLength)];
            $remaining -= strlen($name) + $valueLength;
        }

        return $headers;
    }

    private static function traceHeaderBytes(): int
    {
        $name = 'X-Kongono-Fixture-Trace';

        return (2 * strlen($name)) + strlen('inner-after') + strlen('outer-after');
    }
}
