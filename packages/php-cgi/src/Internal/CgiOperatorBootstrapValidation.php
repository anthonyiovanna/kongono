<?php

declare(strict_types=1);

namespace Kongono\PhpCgi\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\PhpCgi\Exception\CgiOperatorBootstrapException;

/** Validates the closed native prerequisite snapshot before PHP CGI delivery can begin. */
#[Api(ApiStatus::Internal)]
final class CgiOperatorBootstrapValidation
{
    /** @var list<string> */
    private const array DIRECTIVE_NAMES = [
        'enable_post_data_reading',
        'output_buffering',
        'zlib.output_compression',
        'display_errors',
        'display_startup_errors',
        'html_errors',
        'expose_php',
        'auto_prepend_file',
        'auto_append_file',
        'cgi.rfc2616_headers',
    ];

    private function __construct() {}

    public static function captureAndValidate(): void
    {
        if ('cgi-fcgi' !== PHP_SAPI) {
            throw new CgiOperatorBootstrapException('sapi_mismatch');
        }

        $directives = [];

        foreach (self::DIRECTIVE_NAMES as $name) {
            $directives[$name] = ini_get($name);
        }

        self::validate(PHP_SAPI, $directives);
    }

    /** @param array<array-key, mixed> $directives */
    public static function validate(string $sapi, array $directives): void
    {
        if ('cgi-fcgi' !== $sapi) {
            throw new CgiOperatorBootstrapException('sapi_mismatch');
        }

        if (count(self::DIRECTIVE_NAMES) !== count($directives)) {
            throw new CgiOperatorBootstrapException('ini_snapshot_invalid');
        }

        foreach ($directives as $name => $value) {
            if (
                !is_string($name)
                || !in_array($name, self::DIRECTIVE_NAMES, true)
                || !is_string($value)
            ) {
                throw new CgiOperatorBootstrapException('ini_snapshot_invalid');
            }
        }

        foreach (self::DIRECTIVE_NAMES as $name) {
            if (!array_key_exists($name, $directives)) {
                throw new CgiOperatorBootstrapException('ini_snapshot_invalid');
            }
        }

        if (!in_array($directives['enable_post_data_reading'], ['', '0'], true)) {
            throw new CgiOperatorBootstrapException('enable_post_data_reading_enabled');
        }

        if ('0' !== $directives['output_buffering']) {
            throw new CgiOperatorBootstrapException('output_buffering_enabled');
        }

        if (!in_array($directives['zlib.output_compression'], ['', '0'], true)) {
            throw new CgiOperatorBootstrapException('zlib_output_compression_enabled');
        }

        if (!in_array($directives['display_errors'], ['', '0'], true)) {
            throw new CgiOperatorBootstrapException('display_errors_enabled');
        }

        if (!in_array($directives['display_startup_errors'], ['', '0'], true)) {
            throw new CgiOperatorBootstrapException('display_startup_errors_enabled');
        }

        if (!in_array($directives['html_errors'], ['', '0'], true)) {
            throw new CgiOperatorBootstrapException('html_errors_enabled');
        }

        if (!in_array($directives['expose_php'], ['', '0'], true)) {
            throw new CgiOperatorBootstrapException('expose_php_enabled');
        }

        if ('' !== $directives['auto_prepend_file']) {
            throw new CgiOperatorBootstrapException('auto_prepend_file_configured');
        }

        if ('' !== $directives['auto_append_file']) {
            throw new CgiOperatorBootstrapException('auto_append_file_configured');
        }

        if (!in_array($directives['cgi.rfc2616_headers'], ['', '0'], true)) {
            throw new CgiOperatorBootstrapException('cgi_rfc2616_headers_enabled');
        }
    }
}
