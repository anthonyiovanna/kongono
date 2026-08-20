<?php

declare(strict_types=1);

namespace Kongono\Http\Message\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the bounded structural metadata for first-party HTTP message failures. */
#[Api(ApiStatus::Internal)]
enum HttpMessageDiagnostic: string
{
    case InvalidProtocolVersion = 'KGN-HTTP-1001';
    case InvalidHeaderName = 'KGN-HTTP-1002';
    case HeaderValuesNotList = 'KGN-HTTP-1003';
    case HeaderValueNotString = 'KGN-HTTP-1004';
    case UnsafeHeaderValue = 'KGN-HTTP-1005';
    case InvalidMethod = 'KGN-HTTP-1006';
    case InvalidRequestTarget = 'KGN-HTTP-1007';
    case InvalidStatusCode = 'KGN-HTTP-1008';
    case InvalidReasonPhrase = 'KGN-HTTP-1009';
    case InvalidUriSyntax = 'KGN-HTTP-1010';
    case InvalidUriComponent = 'KGN-HTTP-1011';
    case InvalidUriPort = 'KGN-HTTP-1012';
    case InvalidStreamResource = 'KGN-HTTP-1013';
    case DetachedStream = 'KGN-HTTP-1014';
    case StreamNotReadable = 'KGN-HTTP-1015';
    case StreamNotWritable = 'KGN-HTTP-1016';
    case StreamNotSeekable = 'KGN-HTTP-1017';
    case StreamOperationFailed = 'KGN-HTTP-1018';
    case InvalidStreamReadLength = 'KGN-HTTP-1019';
    case InvalidStreamMode = 'KGN-HTTP-1020';
    case InvalidUploadError = 'KGN-HTTP-1021';
    case InvalidUploadSize = 'KGN-HTTP-1022';
    case UploadedStreamUnavailable = 'KGN-HTTP-1023';
    case InvalidUploadTarget = 'KGN-HTTP-1024';
    case UploadedFileAlreadyMoved = 'KGN-HTTP-1025';
    case UploadedFileMoveFailed = 'KGN-HTTP-1026';
    case InvalidUploadedFilesTree = 'KGN-HTTP-1027';
    case InvalidParsedBody = 'KGN-HTTP-1028';

    public function contextSchema(): DiagnosticContextSchema
    {
        [$operation, $invariant] = $this->schemaIdentity();
        $integer = DiagnosticValueKind::Integer;
        $text = DiagnosticValueKind::Text;

        $fields = match ($this) {
            self::InvalidProtocolVersion, self::InvalidMethod => [['actual_length', 'Actual length', $integer]],
            self::InvalidHeaderName => [['name_length', 'Name length', $integer]],
            self::HeaderValuesNotList => [
                ['entry_count', 'Entry count', $integer],
                ['first_unexpected_key_kind', 'First unexpected key kind', $text],
            ],
            self::HeaderValueNotString => [
                ['value_position', 'Value position', $integer],
                ['actual_value_kind', 'Actual value kind', $text],
            ],
            self::UnsafeHeaderValue => [
                ['value_position', 'Value position', $integer],
                ['violation', 'Violation', $text],
            ],
            self::InvalidRequestTarget, self::InvalidReasonPhrase, self::InvalidUriSyntax => [
                ['actual_length', 'Actual length', $integer],
                ['violation', 'Violation', $text],
            ],
            self::InvalidStatusCode => [['status_code', 'Status code', $integer]],
            self::InvalidUriComponent => [
                ['component', 'Component', $text],
                ['component_length', 'Component length', $integer],
                ['violation', 'Violation', $text],
            ],
            self::InvalidUriPort => [['port', 'Port', $integer]],
            self::InvalidStreamResource, self::InvalidParsedBody => [['actual_value_kind', 'Actual value kind', $text]],
            self::DetachedStream, self::StreamNotReadable, self::StreamNotWritable,
            self::StreamNotSeekable, self::StreamOperationFailed, self::UploadedFileMoveFailed => [
                ['operation', 'Operation', $text],
            ],
            self::InvalidStreamReadLength => [['requested_length', 'Requested length', $integer]],
            self::InvalidStreamMode => [
                ['mode_length', 'Mode length', $integer],
                ['violation', 'Violation', $text],
            ],
            self::InvalidUploadError => [['upload_error', 'Upload error', $integer]],
            self::InvalidUploadSize => [['upload_size', 'Upload size', $integer]],
            self::UploadedStreamUnavailable => [['state', 'State', $text]],
            self::InvalidUploadTarget => [
                ['target_length', 'Target length', $integer],
                ['violation', 'Violation', $text],
            ],
            self::UploadedFileAlreadyMoved => [],
            self::InvalidUploadedFilesTree => [
                ['tree_depth', 'Tree depth', $integer],
                ['actual_value_kind', 'Actual value kind', $text],
            ],
        };

        return new DiagnosticContextSchema($operation, $invariant, array_map(
            static fn(array $field): DiagnosticFieldSchema => new DiagnosticFieldSchema(
                $field[0],
                $field[1],
                $field[2],
                DiagnosticDisclosure::Safe,
            ),
            $fields,
        ));
    }

    /** @param array<string, string|int|bool> $values */
    public function context(array $values): DiagnosticContext
    {
        return new DiagnosticContext($this->contextSchema(), $values);
    }

    public function title(): string
    {
        return match ($this) {
            self::InvalidProtocolVersion => 'Invalid HTTP protocol version',
            self::InvalidHeaderName => 'Invalid HTTP header name',
            self::HeaderValuesNotList => 'Header values are not a zero-based consecutive list',
            self::HeaderValueNotString => 'A header value is not a string',
            self::UnsafeHeaderValue => 'A header value contains an unsafe byte',
            self::InvalidMethod => 'Invalid HTTP method',
            self::InvalidRequestTarget => 'Invalid HTTP request target',
            self::InvalidStatusCode => 'Invalid HTTP status code',
            self::InvalidReasonPhrase => 'Invalid HTTP reason phrase',
            self::InvalidUriSyntax => 'Invalid URI syntax',
            self::InvalidUriComponent => 'Invalid URI component',
            self::InvalidUriPort => 'Invalid URI port',
            self::InvalidStreamResource => 'Invalid stream resource',
            self::DetachedStream => 'Stream is detached',
            self::StreamNotReadable => 'Stream is not readable',
            self::StreamNotWritable => 'Stream is not writable',
            self::StreamNotSeekable => 'Stream is not seekable',
            self::StreamOperationFailed => 'Stream operation failed',
            self::InvalidStreamReadLength => 'Invalid stream read length',
            self::InvalidStreamMode => 'Invalid stream mode',
            self::InvalidUploadError => 'Invalid upload error code',
            self::InvalidUploadSize => 'Invalid upload size',
            self::UploadedStreamUnavailable => 'Uploaded stream is unavailable',
            self::InvalidUploadTarget => 'Invalid upload target',
            self::UploadedFileAlreadyMoved => 'Uploaded file has already been moved',
            self::UploadedFileMoveFailed => 'Uploaded file move failed',
            self::InvalidUploadedFilesTree => 'Invalid uploaded-files tree',
            self::InvalidParsedBody => 'Invalid parsed body',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidProtocolVersion => 'Use one of the supported HTTP protocol version spellings',
            self::InvalidHeaderName => 'Supply a non-empty HTTP token as the header name',
            self::HeaderValuesNotList => 'Supply one string or a zero-based consecutive list of strings',
            self::HeaderValueNotString => 'Replace the reported header value with a string',
            self::UnsafeHeaderValue => 'Remove control, DEL, carriage-return, line-feed, or NUL bytes',
            self::InvalidMethod => 'Supply a non-empty HTTP token as the method',
            self::InvalidRequestTarget => 'Supply a non-empty request target without whitespace or control bytes',
            self::InvalidStatusCode => 'Supply an integer status code from 100 through 599',
            self::InvalidReasonPhrase => 'Remove control, DEL, carriage-return, or line-feed bytes',
            self::InvalidUriSyntax => 'Supply a syntactically valid RFC 3986 URI reference',
            self::InvalidUriComponent => 'Correct the reported RFC 3986 component',
            self::InvalidUriPort => 'Supply a port from 1 through 65535 or null',
            self::InvalidStreamResource => 'Supply an open PHP stream resource',
            self::DetachedStream => 'Use an attached stream or create a replacement stream',
            self::StreamNotReadable => 'Use a stream mode that permits reading',
            self::StreamNotWritable => 'Use a stream mode that permits writing',
            self::StreamNotSeekable => 'Use a seekable stream for this operation',
            self::StreamOperationFailed => 'Inspect the stream state and retry the requested operation',
            self::InvalidStreamReadLength => 'Supply a non-negative read length',
            self::InvalidStreamMode => 'Supply a valid PHP fopen mode',
            self::InvalidUploadError => 'Supply a defined PHP upload error value',
            self::InvalidUploadSize => 'Supply null or a non-negative upload size',
            self::UploadedStreamUnavailable => 'Inspect the upload error or moved state before reading',
            self::InvalidUploadTarget => 'Supply a non-empty target without NUL bytes',
            self::UploadedFileAlreadyMoved => 'Do not move the same uploaded file more than once',
            self::UploadedFileMoveFailed => 'Inspect target stream capability and local I/O state',
            self::InvalidUploadedFilesTree => 'Use arrays whose leaves implement UploadedFileInterface',
            self::InvalidParsedBody => 'Supply null, an array, or an object',
        };
    }

    /** @return array{string, string} */
    private function schemaIdentity(): array
    {
        return match ($this) {
            self::InvalidProtocolVersion => ['Set protocol version', 'Protocol version must use a supported exact spelling'],
            self::InvalidHeaderName => ['Set header name', 'Header name must be a valid HTTP token'],
            self::HeaderValuesNotList => ['Set header values', 'Header value arrays must be PHP lists'],
            self::HeaderValueNotString => ['Set header values', 'Every header value must be a string'],
            self::UnsafeHeaderValue => ['Set header values', 'Header values must not contain unsafe bytes'],
            self::InvalidMethod => ['Set request method', 'Method must be a valid HTTP token'],
            self::InvalidRequestTarget => ['Set request target', 'Request target must be non-empty and free of whitespace and control bytes'],
            self::InvalidStatusCode => ['Set response status', 'Status code must be from 100 through 599'],
            self::InvalidReasonPhrase => ['Set response reason phrase', 'Reason phrase must not contain unsafe control bytes'],
            self::InvalidUriSyntax => ['Parse URI', 'URI reference must have valid RFC 3986 syntax'],
            self::InvalidUriComponent => ['Set URI component', 'URI component must have valid RFC 3986 syntax'],
            self::InvalidUriPort => ['Set URI port', 'URI port must be from 1 through 65535'],
            self::InvalidStreamResource => ['Attach stream resource', 'Value must be an open PHP stream resource'],
            self::DetachedStream => ['Use stream', 'Stream must remain attached'],
            self::StreamNotReadable => ['Read stream', 'Stream mode must permit reading'],
            self::StreamNotWritable => ['Write stream', 'Stream mode must permit writing'],
            self::StreamNotSeekable => ['Seek stream', 'Stream metadata must permit seeking'],
            self::StreamOperationFailed => ['Operate on stream', 'Native stream operation must succeed'],
            self::InvalidStreamReadLength => ['Read stream', 'Requested read length must be non-negative'],
            self::InvalidStreamMode => ['Open stream', 'Mode must follow PHP fopen grammar'],
            self::InvalidUploadError => ['Create uploaded file', 'Upload error must be a defined PHP upload error'],
            self::InvalidUploadSize => ['Create uploaded file', 'Upload size must be null or non-negative'],
            self::UploadedStreamUnavailable => ['Read uploaded file', 'Upload must be successful and not moved'],
            self::InvalidUploadTarget => ['Move uploaded file', 'Target must be non-empty and contain no NUL byte'],
            self::UploadedFileAlreadyMoved => ['Move uploaded file', 'Uploaded file may be moved only once'],
            self::UploadedFileMoveFailed => ['Move uploaded file', 'Target copy operation must complete successfully'],
            self::InvalidUploadedFilesTree => ['Set uploaded files', 'Every uploaded-files tree leaf must implement UploadedFileInterface'],
            self::InvalidParsedBody => ['Set parsed body', 'Parsed body must be null, an array, or an object'],
        };
    }
}
