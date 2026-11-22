<?php

declare(strict_types=1);

namespace Kongono\Mail;

/** Immutable email message builder (header-injection safe, supports text, HTML, and attachments). */
final readonly class Email
{
    public string $subject;

    /** @var list<array{filename: string, contentType: string, bytes: string}> */
    public array $attachments;

    /**
     * @param list<Address>                                                     $to
     * @param list<Address>                                                     $cc
     * @param list<Address>                                                     $bcc
     * @param list<array{filename: string, contentType: string, bytes: string}> $attachments
     */
    public function __construct(
        public ?Address $from = null,
        public array $to = [],
        public array $cc = [],
        public array $bcc = [],
        public ?Address $replyTo = null,
        string $subject = '',
        public ?string $textBody = null,
        public ?string $htmlBody = null,
        array $attachments = [],
    ) {
        $this->subject = self::sanitizeSubject($subject);
        $cleanAttachments = [];
        foreach ($attachments as $att) {
            $cleanAttachments[] = [
                'filename' => self::sanitizeFilename($att['filename']),
                'contentType' => self::sanitizeContentType($att['contentType']),
                'bytes' => $att['bytes'],
            ];
        }
        $this->attachments = $cleanAttachments;
    }

    public static function create(): self
    {
        return new self();
    }

    public function from(string $email, ?string $name = null): self
    {
        return new self(new Address($email, $name), $this->to, $this->cc, $this->bcc, $this->replyTo, $this->subject, $this->textBody, $this->htmlBody, $this->attachments);
    }

    public function to(string $email, ?string $name = null): self
    {
        return new self($this->from, [...$this->to, new Address($email, $name)], $this->cc, $this->bcc, $this->replyTo, $this->subject, $this->textBody, $this->htmlBody, $this->attachments);
    }

    public function cc(string $email, ?string $name = null): self
    {
        return new self($this->from, $this->to, [...$this->cc, new Address($email, $name)], $this->bcc, $this->replyTo, $this->subject, $this->textBody, $this->htmlBody, $this->attachments);
    }

    public function bcc(string $email, ?string $name = null): self
    {
        return new self($this->from, $this->to, $this->cc, [...$this->bcc, new Address($email, $name)], $this->replyTo, $this->subject, $this->textBody, $this->htmlBody, $this->attachments);
    }

    public function replyTo(string $email, ?string $name = null): self
    {
        return new self($this->from, $this->to, $this->cc, $this->bcc, new Address($email, $name), $this->subject, $this->textBody, $this->htmlBody, $this->attachments);
    }

    public function subject(string $subject): self
    {
        return new self($this->from, $this->to, $this->cc, $this->bcc, $this->replyTo, $subject, $this->textBody, $this->htmlBody, $this->attachments);
    }

    public function text(string $body): self
    {
        return new self($this->from, $this->to, $this->cc, $this->bcc, $this->replyTo, $this->subject, $body, $this->htmlBody, $this->attachments);
    }

    public function html(string $body): self
    {
        return new self($this->from, $this->to, $this->cc, $this->bcc, $this->replyTo, $this->subject, $this->textBody, $body, $this->attachments);
    }

    public function attach(string $filename, string $bytes, string $contentType = 'application/octet-stream'): self
    {
        $attachments = [...$this->attachments, [
            'filename' => self::sanitizeFilename($filename),
            'contentType' => self::sanitizeContentType($contentType),
            'bytes' => $bytes,
        ]];

        return new self($this->from, $this->to, $this->cc, $this->bcc, $this->replyTo, $this->subject, $this->textBody, $this->htmlBody, $attachments);
    }

    private static function sanitizeSubject(string $subject): string
    {
        $noCrLf = str_replace(["\r", "\n"], ' ', $subject);

        return trim((string) preg_replace('/[\x00-\x1f\x7f]/', '', $noCrLf));
    }

    private static function sanitizeFilename(string $filename): string
    {
        $normalized = str_replace('\\', '/', $filename);
        $base = basename($normalized);
        $clean = trim((string) preg_replace('/[\x00-\x1f\x7f"\\\\\/;]/', '', $base));

        return '' !== $clean ? $clean : 'attachment.bin';
    }

    private static function sanitizeContentType(string $contentType): string
    {
        $clean = trim((string) preg_replace('/[\x00-\x1f\x7f]/', '', $contentType));
        if (1 !== preg_match('/\A[a-zA-Z0-9!#$&^_.+-]+\/[a-zA-Z0-9!#$&^_.+-]+\z/', $clean)) {
            throw new MailException(sprintf('Unsafe MIME content type "%s" rejected.', $contentType));
        }

        return strtolower($clean);
    }

    /** Render an RFC 5322 / MIME wire message suitable for SMTP transmission. */
    public function toMimeString(): string
    {
        if (null === $this->from) {
            throw new MailException('Email requires a From address.');
        }
        if ([] === $this->to) {
            throw new MailException('Email requires at least one To recipient.');
        }

        $headers = [
            'From: ' . $this->from->format(),
            'To: ' . implode(', ', array_map(static fn(Address $a): string => $a->format(), $this->to)),
        ];
        if ([] !== $this->cc) {
            $headers[] = 'Cc: ' . implode(', ', array_map(static fn(Address $a): string => $a->format(), $this->cc));
        }
        if (null !== $this->replyTo) {
            $headers[] = 'Reply-To: ' . $this->replyTo->format();
        }
        $headers[] = 'Subject: ' . $this->subject;
        $headers[] = 'Date: ' . gmdate(DATE_RFC2822);
        $headers[] = 'Message-ID: <' . bin2hex(random_bytes(12)) . '@kongono.local>';
        $headers[] = 'MIME-Version: 1.0';

        if ([] === $this->attachments && null === $this->htmlBody) {
            $headers[] = 'Content-Type: text/plain; charset=utf-8';

            return implode("\r\n", $headers) . "\r\n\r\n" . ($this->textBody ?? '');
        }

        if ([] === $this->attachments && null === $this->textBody) {
            $headers[] = 'Content-Type: text/html; charset=utf-8';

            return implode("\r\n", $headers) . "\r\n\r\n" . $this->htmlBody;
        }

        $boundary = 'kgn_' . bin2hex(random_bytes(12));
        $headers[] = sprintf('Content-Type: multipart/mixed; boundary="%s"', $boundary);
        $parts = [];

        if (null !== $this->textBody) {
            $parts[] = "--{$boundary}\r\nContent-Type: text/plain; charset=utf-8\r\n\r\n" . $this->textBody;
        }
        if (null !== $this->htmlBody) {
            $parts[] = "--{$boundary}\r\nContent-Type: text/html; charset=utf-8\r\n\r\n" . $this->htmlBody;
        }
        foreach ($this->attachments as $att) {
            $encoded = chunk_split(base64_encode($att['bytes']));
            $parts[] = sprintf(
                "--%s\r\nContent-Type: %s; name=\"%s\"\r\nContent-Transfer-Encoding: base64\r\nContent-Disposition: attachment; filename=\"%s\"\r\n\r\n%s",
                $boundary,
                $att['contentType'],
                $att['filename'],
                $att['filename'],
                $encoded,
            );
        }

        return implode("\r\n", $headers) . "\r\n\r\n" . implode("\r\n", $parts) . "\r\n--{$boundary}--\r\n";
    }
}
