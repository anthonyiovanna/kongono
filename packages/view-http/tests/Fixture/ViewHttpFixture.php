<?php

declare(strict_types=1);

namespace Kongono\ViewHttp\Tests\Fixture;

use Kongono\Http\Message\Factory\ResponseFactory;
use Kongono\Http\Message\Factory\StreamFactory;
use Kongono\ViewContract\ContractVersion;
use Kongono\ViewContract\Identity\CompiledViewIdentity;
use Kongono\ViewContract\Identity\LogicalViewIdentity;
use Kongono\ViewContract\Identity\RenderOperationIdentity;
use Kongono\ViewContract\Render\RenderedOutput;
use Kongono\ViewContract\Render\RenderLimits;
use Kongono\ViewContract\Render\RenderMetadata;
use Kongono\ViewContract\Render\RenderResult;
use Kongono\ViewHttp\Http\CompletedViewResponseAdapter;
use Kongono\ViewHttp\Policy\ViewContentType;
use Kongono\ViewHttp\Policy\ViewResponseHeader;
use Kongono\ViewHttp\Policy\ViewResponsePolicy;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

final class ViewHttpFixture
{
    public static function result(string $bytes = '<h1>Hello</h1>'): RenderResult
    {
        $limits = new RenderLimits(max(1024, strlen($bytes)), 64);
        $output = new RenderedOutput($bytes, $limits);

        return new RenderResult(
            ContractVersion::Version1,
            new RenderOperationIdentity('view-http-operation'),
            new LogicalViewIdentity('account.profile'),
            new CompiledViewIdentity(str_repeat('a', 64)),
            $output,
            RenderMetadata::from($output, $limits, 3, 2),
        );
    }

    /** @param list<ViewResponseHeader> $headers */
    public static function policy(int $status = 200, string $reason = 'OK', array $headers = []): ViewResponsePolicy
    {
        return new ViewResponsePolicy($status, $reason, ViewContentType::HtmlUtf8, $headers);
    }

    public static function adapter(): CompletedViewResponseAdapter
    {
        return new CompletedViewResponseAdapter(new ResponseFactory(), new StreamFactory());
    }
}

final readonly class AdversarialResponse implements ResponseInterface
{
    public function __construct(private ResponseInterface $inner, private string $mode) {}

    public function getProtocolVersion(): string
    {
        return $this->inner->getProtocolVersion();
    }

    public function withProtocolVersion(string $version): ResponseInterface
    {
        return new self($this->inner->withProtocolVersion($version), $this->mode);
    }

    public function getHeaders(): array
    {
        if ('headers_throw' === $this->mode) {
            throw new RuntimeException('private-response-headers');
        }
        $headers = $this->inner->getHeaders();
        if ('unexpected_header' === $this->mode) {
            $headers['X-Injected'] = ['private-response-header'];
        }

        return $headers;
    }

    public function hasHeader(string $name): bool
    {
        return $this->inner->hasHeader($name);
    }

    public function getHeader(string $name): array
    {
        if ('header_mismatch' === $this->mode) {
            return ['private-altered-value'];
        }

        return $this->inner->getHeader($name);
    }

    public function getHeaderLine(string $name): string
    {
        return $this->inner->getHeaderLine($name);
    }

    public function withHeader(string $name, $value): ResponseInterface
    {
        if ('with_header_throw' === $this->mode) {
            throw new RuntimeException('private-response-header-mutation');
        }

        return new self($this->inner->withHeader($name, $value), $this->mode);
    }

    public function withAddedHeader(string $name, $value): ResponseInterface
    {
        return new self($this->inner->withAddedHeader($name, $value), $this->mode);
    }

    public function withoutHeader(string $name): ResponseInterface
    {
        return new self($this->inner->withoutHeader($name), $this->mode);
    }

    public function getBody(): StreamInterface
    {
        if ('body_getter_throw' === $this->mode) {
            throw new RuntimeException('private-response-body');
        }

        return $this->inner->getBody();
    }

    public function withBody(StreamInterface $body): ResponseInterface
    {
        if ('with_body_throw' === $this->mode) {
            throw new RuntimeException('private-response-body-mutation');
        }
        if ('body_mismatch' === $this->mode) {
            return $this;
        }

        return new self($this->inner->withBody($body), $this->mode);
    }

    public function getStatusCode(): int
    {
        if ('status_throw' === $this->mode) {
            throw new RuntimeException('private-response-status');
        }

        return $this->inner->getStatusCode() + ('status_mismatch' === $this->mode ? 1 : 0);
    }

    public function withStatus(int $code, string $reasonPhrase = ''): ResponseInterface
    {
        return new self($this->inner->withStatus($code, $reasonPhrase), $this->mode);
    }

    public function getReasonPhrase(): string
    {
        if ('reason_throw' === $this->mode) {
            throw new RuntimeException('private-response-reason');
        }

        return 'reason_mismatch' === $this->mode ? 'private-altered-reason' : $this->inner->getReasonPhrase();
    }
}

final readonly class AdversarialStream implements StreamInterface
{
    public function __construct(private StreamInterface $inner, private string $mode) {}

    public function __toString(): string
    {
        return (string) $this->inner;
    }

    public function close(): void
    {
        $this->inner->close();
    }

    public function detach()
    {
        return $this->inner->detach();
    }

    public function getSize(): ?int
    {
        $size = $this->inner->getSize();

        return 'size_mismatch' === $this->mode ? ($size ?? 0) + 1 : $size;
    }

    public function tell(): int
    {
        return $this->inner->tell() + ('position_mismatch' === $this->mode ? 1 : 0);
    }

    public function eof(): bool
    {
        return $this->inner->eof();
    }

    public function isSeekable(): bool
    {
        return 'not_seekable' !== $this->mode && $this->inner->isSeekable();
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        $this->inner->seek($offset, $whence);
    }

    public function rewind(): void
    {
        if ('rewind_throw' === $this->mode) {
            throw new RuntimeException('private-stream-rewind');
        }
        $this->inner->rewind();
    }

    public function isWritable(): bool
    {
        return $this->inner->isWritable();
    }

    public function write(string $string): int
    {
        return $this->inner->write($string);
    }

    public function isReadable(): bool
    {
        return 'not_readable' !== $this->mode && $this->inner->isReadable();
    }

    public function read(int $length): string
    {
        return $this->inner->read($length);
    }

    public function getContents(): string
    {
        if ('contents_throw' === $this->mode) {
            throw new RuntimeException('private-stream-content');
        }

        return 'content_mismatch' === $this->mode ? 'private-altered-content' : $this->inner->getContents();
    }

    public function getMetadata(?string $key = null)
    {
        return $this->inner->getMetadata($key);
    }
}
