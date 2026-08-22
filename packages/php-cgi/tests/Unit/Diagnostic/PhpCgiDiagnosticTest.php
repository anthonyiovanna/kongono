<?php

declare(strict_types=1);

namespace Kongono\PhpCgi\Tests\Unit\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticValueKind;
use Kongono\PhpCgi\Diagnostic\PhpCgiDiagnostic;
use PHPUnit\Framework\TestCase;

final class PhpCgiDiagnosticTest extends TestCase
{
    public function testCatalogIdentityProseSchemasAndTokensAreExact(): void
    {
        $expected = [
            'InvalidHostProfile' => ['KGN-PHP-CGI-1001', 'Unsupported PHP CGI host profile', 'Run only under PHP cgi-fcgi with CGI/1.1 in the FastCGI RESPONDER role', 'Admit PHP CGI request', 'The host must match the selected cgi-fcgi CGI/1.1 RESPONDER profile', ['sapi_mismatch', 'gateway_interface_mismatch', 'fastcgi_role_mismatch']],
            'InvalidServerSnapshot' => ['KGN-PHP-CGI-1002', 'Invalid CGI server snapshot', 'Supply only the required bounded string CGI fields and authorized optional fields', 'Validate CGI server snapshot', 'Server parameters must match the closed bounded profile projection', ['server_key_not_string', 'server_key_too_long', 'server_key_not_allowed', 'http_key_suffix_invalid', 'required_server_key_missing', 'server_value_not_string', 'remote_address_invalid', 'remote_port_invalid', 'server_address_invalid', 'server_name_invalid', 'server_port_invalid', 'server_software_invalid', 'script_name_invalid']],
            'InvalidRequestMethod' => ['KGN-PHP-CGI-1003', 'Invalid CGI request method', 'Supply an uppercase ASCII HTTP token no longer than 32 bytes', 'Validate CGI request method', 'The request method must use the bounded uppercase HTTP token grammar', ['method_empty', 'method_too_long', 'method_not_uppercase_token']],
            'InvalidRequestTarget' => ['KGN-PHP-CGI-1004', 'Invalid CGI request target', 'Supply a bounded origin-form target, matching query string, supported protocol, and valid host', 'Construct CGI request target', 'Protocol, target, scheme, authority, and query must satisfy the selected profile', ['protocol_unsupported', 'target_empty', 'target_too_long', 'target_not_origin_form', 'target_fragment_forbidden', 'target_unsafe_byte', 'target_percent_invalid', 'query_string_too_long', 'query_string_mismatch', 'https_value_invalid', 'authority_empty', 'authority_invalid']],
            'InvalidRequestHeaders' => ['KGN-PHP-CGI-1005', 'Invalid CGI request headers', 'Supply unique bounded CGI header fields without ambiguous, proxy, or transfer-encoding entries', 'Project CGI request headers', 'Projected request headers must be unique, bounded, and safe for the first-party message', ['content_type_ambiguous', 'content_length_ambiguous', 'proxy_header_forbidden', 'transfer_encoding_forbidden', 'header_name_invalid', 'header_value_invalid', 'header_name_duplicate', 'header_count_exceeded', 'header_value_too_long', 'header_bytes_exceeded']],
            'InvalidQueryParameters' => ['KGN-PHP-CGI-1006', 'Invalid CGI query parameters', 'Supply a bounded flat query with valid UTF-8 names, values, and percent encoding', 'Parse CGI query parameters', 'Query parameters must satisfy the bounded flat unambiguous grammar', ['query_field_empty', 'query_pair_count_exceeded', 'query_percent_invalid', 'query_key_empty', 'query_key_too_long', 'query_key_utf8_invalid', 'query_key_unsafe', 'query_nesting_forbidden', 'query_value_too_long', 'query_value_utf8_invalid', 'query_value_unsafe', 'query_key_duplicate']],
            'InvalidCookieParameters' => ['KGN-PHP-CGI-1007', 'Invalid CGI cookie parameters', 'Supply a bounded flat Cookie field with unique token names and visible values', 'Parse CGI cookie parameters', 'Cookie parameters must satisfy the bounded flat unambiguous grammar', ['raw_cookie_too_long', 'cookie_pair_empty', 'cookie_pair_count_exceeded', 'cookie_pair_missing_equals', 'cookie_name_empty', 'cookie_name_too_long', 'cookie_name_invalid', 'cookie_value_too_long', 'cookie_value_invalid', 'cookie_name_duplicate']],
            'InvalidRequestBody' => ['KGN-PHP-CGI-1008', 'Invalid CGI request body', 'Supply an allowed media type and canonical body length no greater than 1048576 bytes', 'Validate CGI request body', 'Body metadata, form state, and upload state must satisfy the selected profile', ['post_data_present', 'upload_data_present', 'content_length_invalid', 'body_too_large', 'multipart_unsupported']],
            'RequestConstructionFailed' => ['KGN-PHP-CGI-1009', 'Unable to construct CGI server request', 'Correct the validated request data or first-party HTTP Message incompatibility before dispatch', 'Construct first-party server request', 'Validated CGI values must construct one first-party PSR-7 server request', ['first_party_message_rejected']],
            'InputReadFailed' => ['KGN-PHP-CGI-1010', 'Unable to read CGI request input', 'Supply an attached readable input stream containing exactly the declared body length', 'Read CGI request input', 'The caller input must yield exactly the bounded declared body bytes', ['input_not_stream', 'input_closed', 'input_metadata_failed', 'input_unreadable', 'input_read_failed', 'input_early_eof', 'body_stream_open_failed', 'body_stream_write_failed', 'body_stream_rewind_failed', 'input_open_failed']],
            'InvalidResponseStatus' => ['KGN-PHP-CGI-1011', 'Invalid CGI response status', 'Supply a response status from 200 through 599', 'Validate CGI response status', 'The response status must be within the selected CGI profile range', ['status_out_of_range']],
            'InvalidResponseProtocol' => ['KGN-PHP-CGI-1012', 'Invalid CGI response protocol', 'Supply one of the supported response protocol labels', 'Validate CGI response protocol', 'The response protocol must use the selected closed label set', ['protocol_unsupported']],
            'InvalidResponseHeaders' => ['KGN-PHP-CGI-1013', 'Invalid CGI response headers', 'Supply ordered bounded response fields without adapter-owned or hop-by-hop names', 'Validate CGI response headers', 'Response fields must be unique, bounded, safe, and adapter-compatible', ['header_name_not_string', 'header_values_not_list', 'header_value_not_string', 'header_name_invalid', 'header_name_duplicate', 'header_forbidden', 'header_count_exceeded', 'header_value_count_invalid', 'header_value_invalid', 'header_value_too_long', 'header_bytes_exceeded']],
            'InvalidResponseBody' => ['KGN-PHP-CGI-1014', 'Invalid CGI response body', 'Supply a readable non-stalling response body no larger than 1048576 bytes', 'Stage CGI response body', 'The response body must be readable and fit the selected staging bound', ['body_unreadable', 'body_stalled', 'body_read_exceeded_request', 'body_too_large']],
            'ResponsePreflightFailed' => ['KGN-PHP-CGI-1015', 'Unable to preflight CGI response', 'Correct the local response staging or prior-output condition before emission', 'Preflight CGI response emission', 'Complete bounded staging and uncommitted headers are required before emission', ['headers_already_sent', 'staging_open_failed', 'staging_write_failed', 'staging_rewind_failed']],
            'ResponseEmissionFailed' => ['KGN-PHP-CGI-1016', 'Unable to complete CGI response emission', 'Retire the uncertain delivery and correct the selected host output failure', 'Emit CGI response', 'Status, fields, and staged body must be emitted exactly once after commitment', ['status_emit_failed', 'header_emit_failed', 'content_length_emit_failed', 'staged_body_read_failed', 'output_write_failed']],
            'OperatorBootstrapFailed' => ['KGN-PHP-CGI-1017', 'Unable to bootstrap PHP CGI operator profile', 'Run the selected cgi-fcgi profile with every required PHP setting disabled before serving', 'Bootstrap PHP CGI operator profile', 'The selected cgi-fcgi process must satisfy every fixed pre-delivery prerequisite', ['sapi_mismatch', 'ini_snapshot_invalid', 'enable_post_data_reading_enabled', 'output_buffering_enabled', 'zlib_output_compression_enabled', 'display_errors_enabled', 'display_startup_errors_enabled', 'html_errors_enabled', 'expose_php_enabled', 'auto_prepend_file_configured', 'auto_append_file_configured', 'cgi_rfc2616_headers_enabled']],
            'DeliveryCleanupFailed' => ['KGN-PHP-CGI-1018', 'Unable to close PHP CGI delivery input', 'Retire the delivery and correct the selected host input-resource failure before serving again', 'Close PHP CGI delivery resources', 'Every delivery-owned native input stream must close exactly once', ['input_close_failed']],
            'EntryAndShutdownFailed' => ['KGN-PHP-CGI-1019', 'PHP CGI entry and shutdown both failed', 'Retire the process and correct both the entry failure and application shutdown failure before serving again', 'Run PHP CGI front controller', 'The primary entry failure and application shutdown failure must both remain inspectable', ['entry_and_shutdown_failed']],
        ];

        self::assertSame(array_keys($expected), array_map(
            static fn(PhpCgiDiagnostic $diagnostic): string => $diagnostic->name,
            PhpCgiDiagnostic::cases(),
        ));
        foreach (PhpCgiDiagnostic::cases() as $diagnostic) {
            [$id, $title, $remediation, $operation, $invariant, $tokens] = $expected[$diagnostic->name];
            self::assertSame($id, $diagnostic->value);
            self::assertSame($title, $diagnostic->title());
            self::assertSame($remediation, $diagnostic->remediation());
            self::assertSame($operation, $diagnostic->contextOperation());
            self::assertSame($invariant, $diagnostic->contextViolatedInvariant());
            self::assertSame($tokens, $diagnostic->violationTokens());

            $schema = $diagnostic->contextFields();
            self::assertCount(1, $schema);
            self::assertSame('violation', $schema[0]->key());
            self::assertSame('Violation', $schema[0]->label());
            self::assertSame(DiagnosticValueKind::Text, $schema[0]->kind());
            self::assertSame(DiagnosticDisclosure::Safe, $schema[0]->disclosure());
            self::assertTrue($schema[0]->isRequired());

            foreach ($tokens as $token) {
                $context = $diagnostic->context($token);
                self::assertSame($operation, $context->operation());
                self::assertSame($invariant, $context->violatedInvariant());
                self::assertCount(1, $context->fields());
                self::assertSame(['violation', 'Violation', $token], [
                    $context->fields()[0]->key(),
                    $context->fields()[0]->label(),
                    $context->fields()[0]->value(),
                ]);
            }
        }
    }

    public function testContextsAndSchemasAreFreshDetachedAndContainNoRawValueField(): void
    {
        $diagnostic = PhpCgiDiagnostic::InvalidRequestTarget;
        $firstSchema = $diagnostic->contextFields();
        $secondSchema = $diagnostic->contextFields();
        self::assertNotSame($firstSchema, $secondSchema);
        self::assertNotSame($firstSchema[0], $secondSchema[0]);

        $first = $diagnostic->context('authority_invalid');
        $fields = $first->fields();
        $fields[] = $first->fields()[0];
        $second = $diagnostic->context('query_string_mismatch');
        self::assertCount(1, $first->fields());
        self::assertSame('authority_invalid', $first->fields()[0]->value());
        self::assertSame('query_string_mismatch', $second->fields()[0]->value());

        foreach (PhpCgiDiagnostic::cases() as $case) {
            self::assertSame(['violation'], array_map(static fn($field): string => $field->key(), $case->contextFields()));
        }
    }

    public function testEnumStatusAndDeclaredShapeAreExact(): void
    {
        $reflection = new \ReflectionClass(PhpCgiDiagnostic::class);
        self::assertSame(ApiStatus::Internal, $reflection->getAttributes(Api::class)[0]->newInstance()->status);
        self::assertSame(
            ['context', 'contextFields', 'contextOperation', 'contextViolatedInvariant', 'remediation', 'title', 'violationTokens'],
            $this->declaredPublicMethodNames($reflection),
        );
        self::assertFalse($reflection->hasMethod('__toString'));
    }

    /**
     * @template T of object
     * @param \ReflectionClass<T> $reflection
     * @return list<string>
     */
    private function declaredPublicMethodNames(\ReflectionClass $reflection): array
    {
        $names = array_map(
            static fn(\ReflectionMethod $method): string => $method->getName(),
            array_filter(
                $reflection->getMethods(\ReflectionMethod::IS_PUBLIC),
                static fn(\ReflectionMethod $method): bool => $reflection->getName() === $method->getDeclaringClass()->getName()
                    && !in_array($method->getName(), ['cases', 'from', 'tryFrom'], true),
            ),
        );
        sort($names);

        return $names;
    }
}
