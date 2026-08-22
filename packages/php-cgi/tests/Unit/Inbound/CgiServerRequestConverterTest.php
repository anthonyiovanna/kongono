<?php

declare(strict_types=1);

namespace Kongono\PhpCgi\Tests\Unit\Inbound;

use Kongono\Http\Message\Message\ServerRequest;
use Kongono\Http\Message\Message\Stream;
use Kongono\PhpCgi\Diagnostic\PhpCgiDiagnostic;
use Kongono\PhpCgi\Exception\CgiRequestReadException;
use Kongono\PhpCgi\Exception\ExceptionInterface;
use Kongono\PhpCgi\Exception\InvalidCgiRequestException;
use Kongono\PhpCgi\Inbound\CgiServerRequestConverter;
use Kongono\PhpCgi\Internal\CgiQueryParser;
use PHPUnit\Framework\TestCase;

final class CgiServerRequestConverterTest extends TestCase
{
    public function testMinimalGetAndAllProtocolMappingsAreExact(): void
    {
        foreach (['HTTP/1.0' => '1.0', 'HTTP/1.1' => '1.1', 'HTTP/2' => '2', 'HTTP/2.0' => '2.0', 'HTTP/3' => '3', 'HTTP/3.0' => '3.0'] as $cgi => $psr) {
            $server = $this->server();
            $server['SERVER_PROTOCOL'] = $cgi;
            $request = $this->convert($server);
            self::assertInstanceOf(ServerRequest::class, $request);
            self::assertSame('GET', $request->getMethod());
            self::assertSame('/', $request->getRequestTarget());
            self::assertSame($psr, $request->getProtocolVersion());
            self::assertSame('http://example.test/', (string) $request->getUri());
            self::assertSame(['Host' => ['example.test']], $request->getHeaders());
            self::assertSame([], $request->getQueryParams());
            self::assertSame([], $request->getCookieParams());
            self::assertNull($request->getParsedBody());
            self::assertSame([], $request->getUploadedFiles());
            self::assertSame([], $request->getAttributes());
            self::assertInstanceOf(Stream::class, $request->getBody());
            self::assertTrue($request->getBody()->isReadable());
            self::assertTrue($request->getBody()->isSeekable());
            self::assertSame(0, $request->getBody()->tell());
            self::assertSame('', $request->getBody()->getContents());
            $request->getBody()->close();
        }
    }

    public function testSchemeAuthorityRawTargetQueryCookiesHeadersAndServerOrderAreExact(): void
    {
        foreach ([
            ['example.test', 'http', 'example.test'],
            ['example.test:80', 'http', 'example.test'],
            ['example.test:8080', 'http', 'example.test:8080'],
            ['127.0.0.1:8080', 'http', '127.0.0.1:8080'],
            ['[::1]:8443', 'https', '[::1]:8443'],
        ] as [$host, $scheme, $expectedAuthority]) {
            $server = $this->server();
            $server['HTTP_HOST'] = $host;
            if ('https' === $scheme) {
                $server['HTTPS'] = 'on';
            }
            $request = $this->convert($server);
            self::assertSame($scheme, $request->getUri()->getScheme());
            self::assertSame($expectedAuthority, $request->getUri()->getAuthority());
            $request->getBody()->close();
        }

        $server = [
            'HTTP_Z_LAST' => 'last',
            'QUERY_STRING' => 'first=one+two&encoded=%E2%9C%93&empty',
            'REMOTE_ADDR' => '2001:db8::1',
            'HTTP_COOKIE' => 'alpha=one; beta=two=three',
            'REQUEST_URI' => '/items?first=one+two&encoded=%E2%9C%93&empty',
            'HTTP_A_FIRST' => 'first',
            'CONTENT_LENGTH' => '0',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
            'HTTP_HOST' => 'example.test:8443',
            'REQUEST_METHOD' => 'POST',
            'FCGI_ROLE' => 'RESPONDER',
            'GATEWAY_INTERFACE' => 'CGI/1.1',
            'HTTPS' => 'on',
            'SERVER_SOFTWARE' => 'unit-test',
        ];
        $request = $this->convert($server);
        self::assertSame('/items?first=one+two&encoded=%E2%9C%93&empty', $request->getRequestTarget());
        self::assertSame('https://example.test:8443/items?first=one+two&encoded=%E2%9C%93&empty', (string) $request->getUri());
        self::assertSame(['first' => 'one two', 'encoded' => '✓', 'empty' => ''], $request->getQueryParams());
        self::assertSame(['alpha' => 'one', 'beta' => 'two=three'], $request->getCookieParams());
        self::assertSame(['Host', 'Content-Type', 'Content-Length', 'A-First', 'Cookie', 'Z-Last'], array_keys($request->getHeaders()));
        self::assertSame(['example.test:8443', 'application/x-www-form-urlencoded', '0', 'first', 'alpha=one; beta=two=three', 'last'], array_values(array_map(static fn(array $values): string => $values[0], $request->getHeaders())));
        self::assertSame(['GATEWAY_INTERFACE', 'FCGI_ROLE', 'REQUEST_METHOD', 'SERVER_PROTOCOL', 'REQUEST_URI', 'QUERY_STRING', 'HTTP_HOST', 'REMOTE_ADDR', 'CONTENT_LENGTH', 'CONTENT_TYPE', 'HTTPS', 'SERVER_SOFTWARE', 'HTTP_A_FIRST', 'HTTP_COOKIE', 'HTTP_Z_LAST'], array_keys($request->getServerParams()));
        self::assertNull($request->getParsedBody());
        $request->getBody()->close();

        $trailing = $this->server();
        $trailing['REQUEST_URI'] = '/path?';
        $request = $this->convert($trailing);
        self::assertSame('/path?', $request->getRequestTarget());
        self::assertSame('/path', $request->getUri()->getPath());
        self::assertSame('', $request->getUri()->getQuery());
        $request->getBody()->close();
    }

    public function testBodyOwnershipBoundsAndRawFormBehaviorAreExact(): void
    {
        $server = $this->server();
        $server['REQUEST_METHOD'] = 'POST';
        $server['CONTENT_LENGTH'] = '4';
        $server['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
        $input = $this->input('bodyEXTRA');
        try {
            $request = (new CgiServerRequestConverter())->convert('cgi-fcgi', $server, [], [], $input);
            self::assertTrue(is_resource($input));
            self::assertSame(4, ftell($input));
            self::assertSame('EXTRA', stream_get_contents($input));
            self::assertSame(0, $request->getBody()->tell());
            self::assertSame('body', $request->getBody()->getContents());
            self::assertNull($request->getParsedBody());
            $request->getBody()->close();
            self::assertTrue(is_resource($input));
        } finally {
            fclose($input);
        }

        $body = str_repeat('x', 1048576);
        $server['CONTENT_LENGTH'] = '1048576';
        $request = $this->convert($server, $body);
        self::assertSame(1048576, $request->getBody()->getSize());
        self::assertSame($body, $request->getBody()->getContents());
        $request->getBody()->close();
    }

    public function testArgumentsGetterArraysAndRepeatedCallsAreDetachedAndFresh(): void
    {
        $host = 'example.test';
        $server = $this->server();
        $server['HTTP_HOST'] = &$host;
        $server['REQUEST_URI'] = '/?a=one';
        $server['QUERY_STRING'] = 'a=one';
        $server['HTTP_COOKIE'] = 'c=one';
        $converter = new CgiServerRequestConverter();
        $first = $this->convertWith($converter, $server);
        $host = 'changed.test';
        $server['REQUEST_METHOD'] = 'DELETE';
        $server['NEW'] = 'changed';
        self::assertSame('example.test', $first->getHeaderLine('Host'));
        self::assertSame('GET', $first->getMethod());
        self::assertArrayNotHasKey('NEW', $first->getServerParams());

        $serverParams = $first->getServerParams();
        $queryParams = $first->getQueryParams();
        $cookieParams = $first->getCookieParams();
        $serverParams['HTTP_HOST'] = 'getter-change';
        $queryParams['a'] = 'getter-change';
        $cookieParams['c'] = 'getter-change';
        self::assertSame('example.test', $first->getServerParams()['HTTP_HOST']);
        self::assertSame('one', $first->getQueryParams()['a']);
        self::assertSame('one', $first->getCookieParams()['c']);

        $clean = $this->server();
        $second = $this->convertWith($converter, $clean);
        self::assertNotSame($first, $second);
        self::assertNotSame($first->getUri(), $second->getUri());
        self::assertNotSame($first->getBody(), $second->getBody());
        $this->assertRejected($clean + ['BROKEN' => 'x'], PhpCgiDiagnostic::InvalidServerSnapshot, 'server_key_not_allowed', converter: $converter);
        $third = $this->convertWith($converter, $clean);
        self::assertNotSame($second, $third);
        self::assertSame('GET', $third->getMethod());
        $first->getBody()->close();
        $second->getBody()->close();
        $third->getBody()->close();
    }

    public function testAdmissionSnapshotAndMethodRejectionsAreClosedAndDeterministic(): void
    {
        $server = $this->server();
        $this->assertRejected($server, PhpCgiDiagnostic::InvalidHostProfile, 'sapi_mismatch', sapi: 'fpm-fcgi');
        foreach ([
            ['GATEWAY_INTERFACE', 'CGI/1.0', PhpCgiDiagnostic::InvalidHostProfile, 'gateway_interface_mismatch'],
            ['FCGI_ROLE', 'AUTHORIZER', PhpCgiDiagnostic::InvalidHostProfile, 'fastcgi_role_mismatch'],
            ['REMOTE_ADDR', 'invalid', PhpCgiDiagnostic::InvalidServerSnapshot, 'remote_address_invalid'],
            ['REMOTE_PORT', '0', PhpCgiDiagnostic::InvalidServerSnapshot, 'remote_port_invalid'],
            ['SERVER_ADDR', '[::1]', PhpCgiDiagnostic::InvalidServerSnapshot, 'server_address_invalid'],
            ['SERVER_NAME', "bad\n", PhpCgiDiagnostic::InvalidServerSnapshot, 'server_name_invalid'],
            ['SERVER_PORT', '65536', PhpCgiDiagnostic::InvalidServerSnapshot, 'server_port_invalid'],
            ['SERVER_SOFTWARE', str_repeat('x', 8193), PhpCgiDiagnostic::InvalidServerSnapshot, 'server_software_invalid'],
            ['SCRIPT_NAME', "bad\0", PhpCgiDiagnostic::InvalidServerSnapshot, 'script_name_invalid'],
            ['REQUEST_METHOD', '', PhpCgiDiagnostic::InvalidRequestMethod, 'method_empty'],
            ['REQUEST_METHOD', str_repeat('A', 33), PhpCgiDiagnostic::InvalidRequestMethod, 'method_too_long'],
            ['REQUEST_METHOD', 'get', PhpCgiDiagnostic::InvalidRequestMethod, 'method_not_uppercase_token'],
        ] as [$key, $value, $diagnostic, $token]) {
            $candidate = $server;
            $candidate[$key] = $value;
            $this->assertRejected($candidate, $diagnostic, $token);
        }

        $candidate = $server;
        $candidate[0] = 'integer-key';
        $this->assertRejected($candidate, PhpCgiDiagnostic::InvalidServerSnapshot, 'server_key_not_string');
        foreach ([
            [str_repeat('A', 129), 'x', 'server_key_too_long'],
            ['UNKNOWN', 'x', 'server_key_not_allowed'],
            ['HTTP_', 'x', 'http_key_suffix_invalid'],
        ] as [$key, $value, $token]) {
            $candidate = $server;
            $candidate[$key] = $value;
            $this->assertRejected($candidate, PhpCgiDiagnostic::InvalidServerSnapshot, $token);
        }
        $candidate = $server;
        unset($candidate['QUERY_STRING']);
        $this->assertRejected($candidate, PhpCgiDiagnostic::InvalidServerSnapshot, 'required_server_key_missing');
        $candidate = $server;
        $candidate['QUERY_STRING'] = ['not-string'];
        $this->assertRejected($candidate, PhpCgiDiagnostic::InvalidServerSnapshot, 'server_value_not_string');
        $this->assertRejected($server, PhpCgiDiagnostic::InvalidRequestBody, 'post_data_present', post: [['sentinel' => new \stdClass()]]);
        $this->assertRejected($server, PhpCgiDiagnostic::InvalidRequestBody, 'upload_data_present', files: [['sentinel' => new \stdClass()]]);

        $valid = $server;
        $valid['REQUEST_METHOD'] = str_repeat('A', 32);
        $request = $this->convert($valid);
        self::assertSame(str_repeat('A', 32), $request->getMethod());
        $request->getBody()->close();
    }

    public function testTargetSchemeAuthorityAndBodyMetadataRejectionsAreExact(): void
    {
        $server = $this->server();
        foreach ([
            ['SERVER_PROTOCOL', 'HTTP/4', PhpCgiDiagnostic::InvalidRequestTarget, 'protocol_unsupported'],
            ['REQUEST_URI', '', PhpCgiDiagnostic::InvalidRequestTarget, 'target_empty'],
            ['REQUEST_URI', '/' . str_repeat('a', 8192), PhpCgiDiagnostic::InvalidRequestTarget, 'target_too_long'],
            ['REQUEST_URI', 'https://example.test/', PhpCgiDiagnostic::InvalidRequestTarget, 'target_not_origin_form'],
            ['REQUEST_URI', '/#raw-fragment', PhpCgiDiagnostic::InvalidRequestTarget, 'target_fragment_forbidden'],
            ['REQUEST_URI', "/bad\t", PhpCgiDiagnostic::InvalidRequestTarget, 'target_unsafe_byte'],
            ['REQUEST_URI', '/bad%2', PhpCgiDiagnostic::InvalidRequestTarget, 'target_percent_invalid'],
            ['QUERY_STRING', str_repeat('a', 8193), PhpCgiDiagnostic::InvalidRequestTarget, 'query_string_too_long'],
            ['QUERY_STRING', 'different', PhpCgiDiagnostic::InvalidRequestTarget, 'query_string_mismatch'],
            ['HTTPS', 'ON', PhpCgiDiagnostic::InvalidRequestTarget, 'https_value_invalid'],
            ['HTTP_HOST', '', PhpCgiDiagnostic::InvalidRequestTarget, 'authority_empty'],
            ['HTTP_HOST', 'user@example.test', PhpCgiDiagnostic::InvalidRequestTarget, 'authority_invalid'],
            ['CONTENT_LENGTH', '01', PhpCgiDiagnostic::InvalidRequestBody, 'content_length_invalid'],
            ['CONTENT_LENGTH', '1048577', PhpCgiDiagnostic::InvalidRequestBody, 'body_too_large'],
            ['CONTENT_TYPE', " \tmUlTiPaRt/FoRm-DaTa\t ; boundary=x", PhpCgiDiagnostic::InvalidRequestBody, 'multipart_unsupported'],
        ] as [$key, $value, $diagnostic, $token]) {
            $candidate = $server;
            $candidate[$key] = $value;
            $this->assertRejected($candidate, $diagnostic, $token);
        }
    }

    public function testHeaderRejectionsAndExactBoundsAreEnforced(): void
    {
        $server = $this->server();
        foreach ([
            ['HTTP_CONTENT_TYPE', 'x', 'content_type_ambiguous'],
            ['HTTP_CONTENT_LENGTH', '1', 'content_length_ambiguous'],
            ['HTTP_PROXY', 'x', 'proxy_header_forbidden'],
            ['HTTP_TRANSFER_ENCODING', 'chunked', 'transfer_encoding_forbidden'],
            ['HTTP_X_TEST', "bad\rvalue", 'header_value_invalid'],
            ['HTTP_X_TEST', str_repeat('x', 8193), 'header_value_too_long'],
        ] as [$key, $value, $token]) {
            $candidate = $server;
            $candidate[$key] = $value;
            $this->assertRejected($candidate, PhpCgiDiagnostic::InvalidRequestHeaders, $token);
        }

        $candidate = $server;
        for ($index = 0; $index < 128; ++$index) {
            $candidate[sprintf('HTTP_X_%03d', $index)] = 'x';
        }
        $this->assertRejected($candidate, PhpCgiDiagnostic::InvalidRequestHeaders, 'header_count_exceeded');

        $candidate = $server;
        for ($index = 0; $index < 9; ++$index) {
            $candidate[sprintf('HTTP_X_%02d', $index)] = str_repeat('x', 8192);
        }
        $this->assertRejected($candidate, PhpCgiDiagnostic::InvalidRequestHeaders, 'header_bytes_exceeded');

        $candidate = $server;
        for ($index = 0; $index < 127; ++$index) {
            $candidate[sprintf('HTTP_X_%03d', $index)] = '';
        }
        $request = $this->convert($candidate);
        self::assertCount(128, $request->getHeaders());
        $request->getBody()->close();
    }

    public function testQueryRejectionsAndBoundariesAreExact(): void
    {
        foreach ([
            ['a=1&&b=2', 'query_field_empty'],
            [implode('&', array_map(static fn(int $index): string => 'k' . $index, range(0, 128))), 'query_pair_count_exceeded'],
            ['=value', 'query_key_empty'],
            [str_repeat('k', 129) . '=v', 'query_key_too_long'],
            ['%FF=v', 'query_key_utf8_invalid'],
            ['a=%00', 'query_value_unsafe'],
            ['a[]=v', 'query_nesting_forbidden'],
            ['a=' . str_repeat('v', 4097), 'query_value_too_long'],
            ['a=%FF', 'query_value_utf8_invalid'],
            ['a=1&%61=2', 'query_key_duplicate'],
        ] as [$query, $token]) {
            $server = $this->server();
            $server['REQUEST_URI'] = '/?' . $query;
            $server['QUERY_STRING'] = $query;
            $this->assertRejected($server, PhpCgiDiagnostic::InvalidQueryParameters, $token);
        }
        try {
            CgiQueryParser::parse('a=%GG');
            self::fail('Malformed percent encoding must reject.');
        } catch (InvalidCgiRequestException $exception) {
            self::assertSame(PhpCgiDiagnostic::InvalidQueryParameters->value, $exception->diagnosticId());
            self::assertSame('query_percent_invalid', $exception->diagnosticContext()->fields()[0]->value());
        }
        $server = $this->server();
        $server['REQUEST_URI'] = '/?a=%GG';
        $server['QUERY_STRING'] = 'a=%GG';
        $this->assertRejected($server, PhpCgiDiagnostic::InvalidRequestTarget, 'target_percent_invalid');

        $server = $this->server();
        $server['REQUEST_URI'] = '/?%00=v';
        $server['QUERY_STRING'] = '%00=v';
        $this->assertRejected($server, PhpCgiDiagnostic::InvalidQueryParameters, 'query_key_unsafe');

        $query = str_repeat('k', 128) . '=' . str_repeat('v', 4096);
        $server = $this->server();
        $server['REQUEST_URI'] = '/?' . $query;
        $server['QUERY_STRING'] = $query;
        $request = $this->convert($server);
        self::assertSame(str_repeat('v', 4096), $request->getQueryParams()[str_repeat('k', 128)]);
        $request->getBody()->close();
    }

    public function testCookieRejectionsAndBoundariesAreExact(): void
    {
        foreach ([
            [str_repeat('x', 4097), 'raw_cookie_too_long'],
            ['a=1;;b=2', 'cookie_pair_empty'],
            [implode(';', array_map(static fn(int $index): string => 'k' . $index . '=v', range(0, 64))), 'cookie_pair_count_exceeded'],
            ['missing', 'cookie_pair_missing_equals'],
            ['=value', 'cookie_name_empty'],
            [str_repeat('n', 129) . '=v', 'cookie_name_too_long'],
            ['bad name=v', 'cookie_name_invalid'],
            ['a=' . str_repeat('v', 1025), 'cookie_value_too_long'],
            ['a=bad,value', 'cookie_value_invalid'],
            ['a=1;a=2', 'cookie_name_duplicate'],
        ] as [$cookie, $token]) {
            $server = $this->server();
            $server['HTTP_COOKIE'] = $cookie;
            $this->assertRejected($server, PhpCgiDiagnostic::InvalidCookieParameters, $token);
        }

        $server = $this->server();
        $server['HTTP_COOKIE'] = str_repeat('n', 128) . '=' . str_repeat('v', 1024);
        $request = $this->convert($server);
        self::assertSame(str_repeat('v', 1024), $request->getCookieParams()[str_repeat('n', 128)]);
        $request->getBody()->close();
    }

    public function testInputResourceRejectionsPreserveCallerOwnership(): void
    {
        $this->assertReadRejected(null, 'input_not_stream');

        $closed = $this->input('');
        fclose($closed);
        $this->assertReadRejected($closed, 'input_closed');

        $context = stream_context_create();
        try {
            $this->assertReadRejected($context, 'input_not_stream');
        } finally {
            unset($context);
        }

        $unreadable = fopen('php://output', 'wb');
        self::assertIsResource($unreadable);
        try {
            $this->assertReadRejected($unreadable, 'input_unreadable');
            self::assertTrue(is_resource($unreadable));
        } finally {
            fclose($unreadable);
        }

        $server = $this->server();
        $server['CONTENT_LENGTH'] = '2';
        $input = $this->input('x');
        try {
            $this->assertRejected($server, PhpCgiDiagnostic::InputReadFailed, 'input_early_eof', input: $input);
            self::assertTrue(is_resource($input));
            self::assertSame(1, ftell($input));
        } finally {
            fclose($input);
        }
    }

    public function testRejectedRawSentinelsNeverReachPackageOwnedDiagnosticSurfaces(): void
    {
        $sentinels = ['RAW_METHOD_4f91', 'RAW_TARGET_4f91', 'RAW_HOST_4f91', 'RAW_HEADER_4f91', 'RAW_QUERY_4f91', 'RAW_COOKIE_4f91', 'RAW_BODY_4f91', 'RAW_POST_4f91', 'RAW_FILE_4f91'];
        $cases = [];
        $server = $this->server();
        $server['REQUEST_METHOD'] = $sentinels[0] . ' lower';
        $cases[] = [$server, [], [], PhpCgiDiagnostic::InvalidRequestMethod, 'method_not_uppercase_token'];
        $server = $this->server();
        $server['REQUEST_URI'] = '/' . $sentinels[1] . "\n";
        $cases[] = [$server, [], [], PhpCgiDiagnostic::InvalidRequestTarget, 'target_unsafe_byte'];
        $server = $this->server();
        $server['HTTP_HOST'] = 'user@' . $sentinels[2] . '.test';
        $cases[] = [$server, [], [], PhpCgiDiagnostic::InvalidRequestTarget, 'authority_invalid'];
        $server = $this->server();
        $server['HTTP_X_RAW'] = $sentinels[3] . "\r";
        $cases[] = [$server, [], [], PhpCgiDiagnostic::InvalidRequestHeaders, 'header_value_invalid'];
        $server = $this->server();
        $server['REQUEST_URI'] = '/?' . $sentinels[4] . '=%00';
        $server['QUERY_STRING'] = $sentinels[4] . '=%00';
        $cases[] = [$server, [], [], PhpCgiDiagnostic::InvalidQueryParameters, 'query_value_unsafe'];
        $server = $this->server();
        $server['HTTP_COOKIE'] = 'a=' . $sentinels[5] . ',bad';
        $cases[] = [$server, [], [], PhpCgiDiagnostic::InvalidCookieParameters, 'cookie_value_invalid'];
        $server = $this->server();
        $server['CONTENT_LENGTH'] = $sentinels[6];
        $cases[] = [$server, [], [], PhpCgiDiagnostic::InvalidRequestBody, 'content_length_invalid'];
        $cases[] = [$this->server(), [$sentinels[7] => new \stdClass()], [], PhpCgiDiagnostic::InvalidRequestBody, 'post_data_present'];
        $cases[] = [$this->server(), [], [$sentinels[8] => new \stdClass()], PhpCgiDiagnostic::InvalidRequestBody, 'upload_data_present'];

        foreach ($cases as [$candidate, $post, $files, $diagnostic, $token]) {
            try {
                $this->assertRejected($candidate, $diagnostic, $token, $post, $files);
            } catch (\PHPUnit\Framework\AssertionFailedError $failure) {
                throw $failure;
            }
            try {
                $input = $this->input('');
                (new CgiServerRequestConverter())->convert('cgi-fcgi', $candidate, $post, $files, $input);
                self::fail('Expected a closed CGI rejection.');
            } catch (ExceptionInterface $exception) {
                $context = $exception->diagnosticContext();
                $surface = implode('|', [$exception->getMessage(), $exception->diagnosticId(), $exception->diagnosticTitle(), $exception->diagnosticRemediation(), $context->operation(), $context->violatedInvariant(), ...array_map(static fn($field): string => $field->key() . $field->label() . (string) $field->value(), $context->fields())]);
                foreach ($sentinels as $sentinel) {
                    self::assertStringNotContainsString($sentinel, $surface);
                }
            } finally {
                if (isset($input) && is_resource($input)) {
                    fclose($input);
                }
            }
        }
    }

    /** @return array<string, string> */
    private function server(): array
    {
        return [
            'GATEWAY_INTERFACE' => 'CGI/1.1',
            'FCGI_ROLE' => 'RESPONDER',
            'REQUEST_METHOD' => 'GET',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'REQUEST_URI' => '/',
            'QUERY_STRING' => '',
            'HTTP_HOST' => 'example.test',
            'REMOTE_ADDR' => '127.0.0.1',
        ];
    }

    /** @return resource */
    private function input(string $bytes): mixed
    {
        $input = fopen('php://temp', 'w+b');
        self::assertIsResource($input);
        self::assertSame(strlen($bytes), fwrite($input, $bytes));
        self::assertTrue(rewind($input));

        return $input;
    }

    /** @param array<array-key, mixed> $server */
    private function convert(array $server, string $bytes = ''): ServerRequest
    {
        return $this->convertWith(new CgiServerRequestConverter(), $server, $bytes);
    }

    /** @param array<array-key, mixed> $server */
    private function convertWith(CgiServerRequestConverter $converter, array $server, string $bytes = ''): ServerRequest
    {
        $input = $this->input($bytes);
        try {
            $request = $converter->convert('cgi-fcgi', $server, [], [], $input);
            self::assertInstanceOf(ServerRequest::class, $request);

            return $request;
        } finally {
            fclose($input);
        }
    }

    /**
     * @param array<array-key, mixed> $server
     * @param array<array-key, mixed> $post
     * @param array<array-key, mixed> $files
     * @param resource|null $input
     */
    private function assertRejected(
        array $server,
        PhpCgiDiagnostic $diagnostic,
        string $token,
        array $post = [],
        array $files = [],
        string $sapi = 'cgi-fcgi',
        mixed $input = null,
        ?CgiServerRequestConverter $converter = null,
    ): void {
        $ownedInput = null === $input;
        if ($ownedInput) {
            $input = $this->input('');
        }
        try {
            ($converter ?? new CgiServerRequestConverter())->convert($sapi, $server, $post, $files, $input);
            self::fail('Expected a closed CGI rejection.');
        } catch (ExceptionInterface $exception) {
            self::assertSame($diagnostic->value, $exception->diagnosticId(), $token);
            self::assertSame($token, $exception->diagnosticContext()->fields()[0]->value(), $token);
            self::assertSame($diagnostic->title(), $exception->getMessage());
        } finally {
            if ($ownedInput && is_resource($input)) {
                fclose($input);
            }
        }
    }

    private function assertReadRejected(mixed $input, string $token): void
    {
        try {
            $method = new \ReflectionMethod(CgiServerRequestConverter::class, 'convert');
            $method->invoke(new CgiServerRequestConverter(), 'cgi-fcgi', $this->server(), [], [], $input);
            self::fail('Expected a closed CGI read rejection.');
        } catch (CgiRequestReadException $exception) {
            self::assertSame(PhpCgiDiagnostic::InputReadFailed->value, $exception->diagnosticId(), $token);
            self::assertSame($token, $exception->diagnosticContext()->fields()[0]->value(), $token);
        }
    }
}
