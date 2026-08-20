<?php

declare(strict_types=1);

namespace Kongono\Http\Message\Tests\Unit\Diagnostic;

use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticValueKind;
use Kongono\Http\Message\Diagnostic\HttpMessageDiagnostic;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class HttpMessageDiagnosticTest extends TestCase
{
    public function testCatalogMatchesTheExactAuthorizedLiteralTable(): void
    {
        $integer = DiagnosticValueKind::Integer;
        $text = DiagnosticValueKind::Text;
        $field = static fn(string $key, string $label, DiagnosticValueKind $kind): array => [$key, $label, $kind, DiagnosticDisclosure::Safe, true];
        $expected = [
            ['InvalidProtocolVersion', 'KGN-HTTP-1001', 'Invalid HTTP protocol version', 'Use one of the supported HTTP protocol version spellings', 'Set protocol version', 'Protocol version must use a supported exact spelling', [$field('actual_length', 'Actual length', $integer)]],
            ['InvalidHeaderName', 'KGN-HTTP-1002', 'Invalid HTTP header name', 'Supply a non-empty HTTP token as the header name', 'Set header name', 'Header name must be a valid HTTP token', [$field('name_length', 'Name length', $integer)]],
            ['HeaderValuesNotList', 'KGN-HTTP-1003', 'Header values are not a zero-based consecutive list', 'Supply one string or a zero-based consecutive list of strings', 'Set header values', 'Header value arrays must be PHP lists', [$field('entry_count', 'Entry count', $integer), $field('first_unexpected_key_kind', 'First unexpected key kind', $text)]],
            ['HeaderValueNotString', 'KGN-HTTP-1004', 'A header value is not a string', 'Replace the reported header value with a string', 'Set header values', 'Every header value must be a string', [$field('value_position', 'Value position', $integer), $field('actual_value_kind', 'Actual value kind', $text)]],
            ['UnsafeHeaderValue', 'KGN-HTTP-1005', 'A header value contains an unsafe byte', 'Remove control, DEL, carriage-return, line-feed, or NUL bytes', 'Set header values', 'Header values must not contain unsafe bytes', [$field('value_position', 'Value position', $integer), $field('violation', 'Violation', $text)]],
            ['InvalidMethod', 'KGN-HTTP-1006', 'Invalid HTTP method', 'Supply a non-empty HTTP token as the method', 'Set request method', 'Method must be a valid HTTP token', [$field('actual_length', 'Actual length', $integer)]],
            ['InvalidRequestTarget', 'KGN-HTTP-1007', 'Invalid HTTP request target', 'Supply a non-empty request target without whitespace or control bytes', 'Set request target', 'Request target must be non-empty and free of whitespace and control bytes', [$field('actual_length', 'Actual length', $integer), $field('violation', 'Violation', $text)]],
            ['InvalidStatusCode', 'KGN-HTTP-1008', 'Invalid HTTP status code', 'Supply an integer status code from 100 through 599', 'Set response status', 'Status code must be from 100 through 599', [$field('status_code', 'Status code', $integer)]],
            ['InvalidReasonPhrase', 'KGN-HTTP-1009', 'Invalid HTTP reason phrase', 'Remove control, DEL, carriage-return, or line-feed bytes', 'Set response reason phrase', 'Reason phrase must not contain unsafe control bytes', [$field('actual_length', 'Actual length', $integer), $field('violation', 'Violation', $text)]],
            ['InvalidUriSyntax', 'KGN-HTTP-1010', 'Invalid URI syntax', 'Supply a syntactically valid RFC 3986 URI reference', 'Parse URI', 'URI reference must have valid RFC 3986 syntax', [$field('actual_length', 'Actual length', $integer), $field('violation', 'Violation', $text)]],
            ['InvalidUriComponent', 'KGN-HTTP-1011', 'Invalid URI component', 'Correct the reported RFC 3986 component', 'Set URI component', 'URI component must have valid RFC 3986 syntax', [$field('component', 'Component', $text), $field('component_length', 'Component length', $integer), $field('violation', 'Violation', $text)]],
            ['InvalidUriPort', 'KGN-HTTP-1012', 'Invalid URI port', 'Supply a port from 1 through 65535 or null', 'Set URI port', 'URI port must be from 1 through 65535', [$field('port', 'Port', $integer)]],
            ['InvalidStreamResource', 'KGN-HTTP-1013', 'Invalid stream resource', 'Supply an open PHP stream resource', 'Attach stream resource', 'Value must be an open PHP stream resource', [$field('actual_value_kind', 'Actual value kind', $text)]],
            ['DetachedStream', 'KGN-HTTP-1014', 'Stream is detached', 'Use an attached stream or create a replacement stream', 'Use stream', 'Stream must remain attached', [$field('operation', 'Operation', $text)]],
            ['StreamNotReadable', 'KGN-HTTP-1015', 'Stream is not readable', 'Use a stream mode that permits reading', 'Read stream', 'Stream mode must permit reading', [$field('operation', 'Operation', $text)]],
            ['StreamNotWritable', 'KGN-HTTP-1016', 'Stream is not writable', 'Use a stream mode that permits writing', 'Write stream', 'Stream mode must permit writing', [$field('operation', 'Operation', $text)]],
            ['StreamNotSeekable', 'KGN-HTTP-1017', 'Stream is not seekable', 'Use a seekable stream for this operation', 'Seek stream', 'Stream metadata must permit seeking', [$field('operation', 'Operation', $text)]],
            ['StreamOperationFailed', 'KGN-HTTP-1018', 'Stream operation failed', 'Inspect the stream state and retry the requested operation', 'Operate on stream', 'Native stream operation must succeed', [$field('operation', 'Operation', $text)]],
            ['InvalidStreamReadLength', 'KGN-HTTP-1019', 'Invalid stream read length', 'Supply a non-negative read length', 'Read stream', 'Requested read length must be non-negative', [$field('requested_length', 'Requested length', $integer)]],
            ['InvalidStreamMode', 'KGN-HTTP-1020', 'Invalid stream mode', 'Supply a valid PHP fopen mode', 'Open stream', 'Mode must follow PHP fopen grammar', [$field('mode_length', 'Mode length', $integer), $field('violation', 'Violation', $text)]],
            ['InvalidUploadError', 'KGN-HTTP-1021', 'Invalid upload error code', 'Supply a defined PHP upload error value', 'Create uploaded file', 'Upload error must be a defined PHP upload error', [$field('upload_error', 'Upload error', $integer)]],
            ['InvalidUploadSize', 'KGN-HTTP-1022', 'Invalid upload size', 'Supply null or a non-negative upload size', 'Create uploaded file', 'Upload size must be null or non-negative', [$field('upload_size', 'Upload size', $integer)]],
            ['UploadedStreamUnavailable', 'KGN-HTTP-1023', 'Uploaded stream is unavailable', 'Inspect the upload error or moved state before reading', 'Read uploaded file', 'Upload must be successful and not moved', [$field('state', 'State', $text)]],
            ['InvalidUploadTarget', 'KGN-HTTP-1024', 'Invalid upload target', 'Supply a non-empty target without NUL bytes', 'Move uploaded file', 'Target must be non-empty and contain no NUL byte', [$field('target_length', 'Target length', $integer), $field('violation', 'Violation', $text)]],
            ['UploadedFileAlreadyMoved', 'KGN-HTTP-1025', 'Uploaded file has already been moved', 'Do not move the same uploaded file more than once', 'Move uploaded file', 'Uploaded file may be moved only once', []],
            ['UploadedFileMoveFailed', 'KGN-HTTP-1026', 'Uploaded file move failed', 'Inspect target stream capability and local I/O state', 'Move uploaded file', 'Target copy operation must complete successfully', [$field('operation', 'Operation', $text)]],
            ['InvalidUploadedFilesTree', 'KGN-HTTP-1027', 'Invalid uploaded-files tree', 'Use arrays whose leaves implement UploadedFileInterface', 'Set uploaded files', 'Every uploaded-files tree leaf must implement UploadedFileInterface', [$field('tree_depth', 'Tree depth', $integer), $field('actual_value_kind', 'Actual value kind', $text)]],
            ['InvalidParsedBody', 'KGN-HTTP-1028', 'Invalid parsed body', 'Supply null, an array, or an object', 'Set parsed body', 'Parsed body must be null, an array, or an object', [$field('actual_value_kind', 'Actual value kind', $text)]],
        ];

        $actual = array_map(static function (HttpMessageDiagnostic $case): array {
            $schema = $case->contextSchema();

            return [
                $case->name,
                $case->value,
                $case->title(),
                $case->remediation(),
                $schema->operation(),
                $schema->violatedInvariant(),
                array_map(static fn($field): array => [$field->key(), $field->label(), $field->kind(), $field->disclosure(), $field->isRequired()], $schema->fields()),
            ];
        }, HttpMessageDiagnostic::cases());

        self::assertSame($expected, $actual);
        $identifiers = array_map(static fn(HttpMessageDiagnostic $case): string => $case->value, HttpMessageDiagnostic::cases());
        self::assertCount(28, array_unique($identifiers));
        foreach ($identifiers as $identifier) {
            self::assertMatchesRegularExpression('/^KGN-HTTP-1\d{3}$/D', $identifier);
        }
    }

    public function testCatalogDeclaresOnlyAuthorizedMethods(): void
    {
        $methods = array_map(static fn($method): string => $method->getName(), array_filter(
            (new ReflectionClass(HttpMessageDiagnostic::class))->getMethods(),
            static fn($method): bool => $method->isPublic() && $method->isUserDefined() && HttpMessageDiagnostic::class === $method->getDeclaringClass()->getName(),
        ));
        sort($methods);
        self::assertSame(['context', 'contextSchema', 'remediation', 'title'], $methods);
    }
}
