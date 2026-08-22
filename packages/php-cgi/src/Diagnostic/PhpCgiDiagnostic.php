<?php

declare(strict_types=1);

namespace Kongono\PhpCgi\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;
use LogicException;

/** Owns the closed, non-sensitive failure vocabulary for bounded PHP CGI behavior. */
#[Api(ApiStatus::Internal)]
enum PhpCgiDiagnostic: string
{
    case InvalidHostProfile = 'KGN-PHP-CGI-1001';
    case InvalidServerSnapshot = 'KGN-PHP-CGI-1002';
    case InvalidRequestMethod = 'KGN-PHP-CGI-1003';
    case InvalidRequestTarget = 'KGN-PHP-CGI-1004';
    case InvalidRequestHeaders = 'KGN-PHP-CGI-1005';
    case InvalidQueryParameters = 'KGN-PHP-CGI-1006';
    case InvalidCookieParameters = 'KGN-PHP-CGI-1007';
    case InvalidRequestBody = 'KGN-PHP-CGI-1008';
    case RequestConstructionFailed = 'KGN-PHP-CGI-1009';
    case InputReadFailed = 'KGN-PHP-CGI-1010';
    case InvalidResponseStatus = 'KGN-PHP-CGI-1011';
    case InvalidResponseProtocol = 'KGN-PHP-CGI-1012';
    case InvalidResponseHeaders = 'KGN-PHP-CGI-1013';
    case InvalidResponseBody = 'KGN-PHP-CGI-1014';
    case ResponsePreflightFailed = 'KGN-PHP-CGI-1015';
    case ResponseEmissionFailed = 'KGN-PHP-CGI-1016';
    case OperatorBootstrapFailed = 'KGN-PHP-CGI-1017';
    case DeliveryCleanupFailed = 'KGN-PHP-CGI-1018';
    case EntryAndShutdownFailed = 'KGN-PHP-CGI-1019';

    public function context(string $violation): DiagnosticContext
    {
        if (!in_array($violation, $this->violationTokens(), true)) {
            throw new LogicException('Unsupported PHP CGI diagnostic violation token.');
        }

        return new DiagnosticContext(
            new DiagnosticContextSchema(
                $this->contextOperation(),
                $this->contextViolatedInvariant(),
                $this->contextFields(),
            ),
            ['violation' => $violation],
        );
    }

    /** @return list<DiagnosticFieldSchema> */
    public function contextFields(): array
    {
        return [new DiagnosticFieldSchema(
            'violation',
            'Violation',
            DiagnosticValueKind::Text,
            DiagnosticDisclosure::Safe,
        )];
    }

    public function contextOperation(): string
    {
        return match ($this) {
            self::InvalidHostProfile => 'Admit PHP CGI request',
            self::InvalidServerSnapshot => 'Validate CGI server snapshot',
            self::InvalidRequestMethod => 'Validate CGI request method',
            self::InvalidRequestTarget => 'Construct CGI request target',
            self::InvalidRequestHeaders => 'Project CGI request headers',
            self::InvalidQueryParameters => 'Parse CGI query parameters',
            self::InvalidCookieParameters => 'Parse CGI cookie parameters',
            self::InvalidRequestBody => 'Validate CGI request body',
            self::RequestConstructionFailed => 'Construct first-party server request',
            self::InputReadFailed => 'Read CGI request input',
            self::InvalidResponseStatus => 'Validate CGI response status',
            self::InvalidResponseProtocol => 'Validate CGI response protocol',
            self::InvalidResponseHeaders => 'Validate CGI response headers',
            self::InvalidResponseBody => 'Stage CGI response body',
            self::ResponsePreflightFailed => 'Preflight CGI response emission',
            self::ResponseEmissionFailed => 'Emit CGI response',
            self::OperatorBootstrapFailed => 'Bootstrap PHP CGI operator profile',
            self::DeliveryCleanupFailed => 'Close PHP CGI delivery resources',
            self::EntryAndShutdownFailed => 'Run PHP CGI front controller',
        };
    }

    public function contextViolatedInvariant(): string
    {
        return match ($this) {
            self::InvalidHostProfile => 'The host must match the selected cgi-fcgi CGI/1.1 RESPONDER profile',
            self::InvalidServerSnapshot => 'Server parameters must match the closed bounded profile projection',
            self::InvalidRequestMethod => 'The request method must use the bounded uppercase HTTP token grammar',
            self::InvalidRequestTarget => 'Protocol, target, scheme, authority, and query must satisfy the selected profile',
            self::InvalidRequestHeaders => 'Projected request headers must be unique, bounded, and safe for the first-party message',
            self::InvalidQueryParameters => 'Query parameters must satisfy the bounded flat unambiguous grammar',
            self::InvalidCookieParameters => 'Cookie parameters must satisfy the bounded flat unambiguous grammar',
            self::InvalidRequestBody => 'Body metadata, form state, and upload state must satisfy the selected profile',
            self::RequestConstructionFailed => 'Validated CGI values must construct one first-party PSR-7 server request',
            self::InputReadFailed => 'The caller input must yield exactly the bounded declared body bytes',
            self::InvalidResponseStatus => 'The response status must be within the selected CGI profile range',
            self::InvalidResponseProtocol => 'The response protocol must use the selected closed label set',
            self::InvalidResponseHeaders => 'Response fields must be unique, bounded, safe, and adapter-compatible',
            self::InvalidResponseBody => 'The response body must be readable and fit the selected staging bound',
            self::ResponsePreflightFailed => 'Complete bounded staging and uncommitted headers are required before emission',
            self::ResponseEmissionFailed => 'Status, fields, and staged body must be emitted exactly once after commitment',
            self::OperatorBootstrapFailed => 'The selected cgi-fcgi process must satisfy every fixed pre-delivery prerequisite',
            self::DeliveryCleanupFailed => 'Every delivery-owned native input stream must close exactly once',
            self::EntryAndShutdownFailed => 'The primary entry failure and application shutdown failure must both remain inspectable',
        };
    }

    public function title(): string
    {
        return match ($this) {
            self::InvalidHostProfile => 'Unsupported PHP CGI host profile',
            self::InvalidServerSnapshot => 'Invalid CGI server snapshot',
            self::InvalidRequestMethod => 'Invalid CGI request method',
            self::InvalidRequestTarget => 'Invalid CGI request target',
            self::InvalidRequestHeaders => 'Invalid CGI request headers',
            self::InvalidQueryParameters => 'Invalid CGI query parameters',
            self::InvalidCookieParameters => 'Invalid CGI cookie parameters',
            self::InvalidRequestBody => 'Invalid CGI request body',
            self::RequestConstructionFailed => 'Unable to construct CGI server request',
            self::InputReadFailed => 'Unable to read CGI request input',
            self::InvalidResponseStatus => 'Invalid CGI response status',
            self::InvalidResponseProtocol => 'Invalid CGI response protocol',
            self::InvalidResponseHeaders => 'Invalid CGI response headers',
            self::InvalidResponseBody => 'Invalid CGI response body',
            self::ResponsePreflightFailed => 'Unable to preflight CGI response',
            self::ResponseEmissionFailed => 'Unable to complete CGI response emission',
            self::OperatorBootstrapFailed => 'Unable to bootstrap PHP CGI operator profile',
            self::DeliveryCleanupFailed => 'Unable to close PHP CGI delivery input',
            self::EntryAndShutdownFailed => 'PHP CGI entry and shutdown both failed',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidHostProfile => 'Run only under PHP cgi-fcgi with CGI/1.1 in the FastCGI RESPONDER role',
            self::InvalidServerSnapshot => 'Supply only the required bounded string CGI fields and authorized optional fields',
            self::InvalidRequestMethod => 'Supply an uppercase ASCII HTTP token no longer than 32 bytes',
            self::InvalidRequestTarget => 'Supply a bounded origin-form target, matching query string, supported protocol, and valid host',
            self::InvalidRequestHeaders => 'Supply unique bounded CGI header fields without ambiguous, proxy, or transfer-encoding entries',
            self::InvalidQueryParameters => 'Supply a bounded flat query with valid UTF-8 names, values, and percent encoding',
            self::InvalidCookieParameters => 'Supply a bounded flat Cookie field with unique token names and visible values',
            self::InvalidRequestBody => 'Supply an allowed media type and canonical body length no greater than 1048576 bytes',
            self::RequestConstructionFailed => 'Correct the validated request data or first-party HTTP Message incompatibility before dispatch',
            self::InputReadFailed => 'Supply an attached readable input stream containing exactly the declared body length',
            self::InvalidResponseStatus => 'Supply a response status from 200 through 599',
            self::InvalidResponseProtocol => 'Supply one of the supported response protocol labels',
            self::InvalidResponseHeaders => 'Supply ordered bounded response fields without adapter-owned or hop-by-hop names',
            self::InvalidResponseBody => 'Supply a readable non-stalling response body no larger than 1048576 bytes',
            self::ResponsePreflightFailed => 'Correct the local response staging or prior-output condition before emission',
            self::ResponseEmissionFailed => 'Retire the uncertain delivery and correct the selected host output failure',
            self::OperatorBootstrapFailed => 'Run the selected cgi-fcgi profile with every required PHP setting disabled before serving',
            self::DeliveryCleanupFailed => 'Retire the delivery and correct the selected host input-resource failure before serving again',
            self::EntryAndShutdownFailed => 'Retire the process and correct both the entry failure and application shutdown failure before serving again',
        };
    }

    /** @return list<string> */
    public function violationTokens(): array
    {
        return match ($this) {
            self::InvalidHostProfile => ['sapi_mismatch', 'gateway_interface_mismatch', 'fastcgi_role_mismatch'],
            self::InvalidServerSnapshot => [
                'server_key_not_string', 'server_key_too_long', 'server_key_not_allowed',
                'http_key_suffix_invalid', 'required_server_key_missing', 'server_value_not_string',
                'remote_address_invalid', 'remote_port_invalid', 'server_address_invalid',
                'server_name_invalid', 'server_port_invalid', 'server_software_invalid',
                'script_name_invalid',
            ],
            self::InvalidRequestMethod => ['method_empty', 'method_too_long', 'method_not_uppercase_token'],
            self::InvalidRequestTarget => [
                'protocol_unsupported', 'target_empty', 'target_too_long', 'target_not_origin_form',
                'target_fragment_forbidden', 'target_unsafe_byte', 'target_percent_invalid',
                'query_string_too_long', 'query_string_mismatch', 'https_value_invalid',
                'authority_empty', 'authority_invalid',
            ],
            self::InvalidRequestHeaders => [
                'content_type_ambiguous', 'content_length_ambiguous', 'proxy_header_forbidden',
                'transfer_encoding_forbidden', 'header_name_invalid', 'header_value_invalid',
                'header_name_duplicate', 'header_count_exceeded', 'header_value_too_long',
                'header_bytes_exceeded',
            ],
            self::InvalidQueryParameters => [
                'query_field_empty', 'query_pair_count_exceeded', 'query_percent_invalid',
                'query_key_empty', 'query_key_too_long', 'query_key_utf8_invalid',
                'query_key_unsafe', 'query_nesting_forbidden', 'query_value_too_long',
                'query_value_utf8_invalid', 'query_value_unsafe', 'query_key_duplicate',
            ],
            self::InvalidCookieParameters => [
                'raw_cookie_too_long', 'cookie_pair_empty', 'cookie_pair_count_exceeded',
                'cookie_pair_missing_equals', 'cookie_name_empty', 'cookie_name_too_long',
                'cookie_name_invalid', 'cookie_value_too_long', 'cookie_value_invalid',
                'cookie_name_duplicate',
            ],
            self::InvalidRequestBody => [
                'post_data_present', 'upload_data_present', 'content_length_invalid',
                'body_too_large', 'multipart_unsupported',
            ],
            self::RequestConstructionFailed => ['first_party_message_rejected'],
            self::InputReadFailed => [
                'input_not_stream', 'input_closed', 'input_metadata_failed', 'input_unreadable',
                'input_read_failed', 'input_early_eof', 'body_stream_open_failed',
                'body_stream_write_failed', 'body_stream_rewind_failed', 'input_open_failed',
            ],
            self::InvalidResponseStatus => ['status_out_of_range'],
            self::InvalidResponseProtocol => ['protocol_unsupported'],
            self::InvalidResponseHeaders => [
                'header_name_not_string', 'header_values_not_list', 'header_value_not_string',
                'header_name_invalid', 'header_name_duplicate', 'header_forbidden',
                'header_count_exceeded', 'header_value_count_invalid', 'header_value_invalid',
                'header_value_too_long', 'header_bytes_exceeded',
            ],
            self::InvalidResponseBody => [
                'body_unreadable', 'body_stalled', 'body_read_exceeded_request', 'body_too_large',
            ],
            self::ResponsePreflightFailed => [
                'headers_already_sent', 'staging_open_failed', 'staging_write_failed',
                'staging_rewind_failed',
            ],
            self::ResponseEmissionFailed => [
                'status_emit_failed', 'header_emit_failed', 'content_length_emit_failed',
                'staged_body_read_failed', 'output_write_failed',
            ],
            self::OperatorBootstrapFailed => [
                'sapi_mismatch', 'ini_snapshot_invalid', 'enable_post_data_reading_enabled',
                'output_buffering_enabled', 'zlib_output_compression_enabled',
                'display_errors_enabled', 'display_startup_errors_enabled',
                'html_errors_enabled', 'expose_php_enabled', 'auto_prepend_file_configured',
                'auto_append_file_configured', 'cgi_rfc2616_headers_enabled',
            ],
            self::DeliveryCleanupFailed => ['input_close_failed'],
            self::EntryAndShutdownFailed => ['entry_and_shutdown_failed'],
        };
    }
}
