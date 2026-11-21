<?php

declare(strict_types=1);

namespace Kongono\Mail;

/** Immutable email message builder (header-injection safe, supports text, HTML, and attachments). */
final readonly class Email
{
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
        public string $subject = '',
        public ?string $textBody = null,
        public ?string $htmlBody = null,
        public array $attachments = [],
    ) {}

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
        $clean = trim(str_replace(["\r", "\n"], ' ', $subject));

        return new self($this->from, $this->to, $this->cc, $this->bcc, $this->replyTo, $clean, $this->textBody, $this->htmlBody, $this->attachments);
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
        $cleanFilename = basename(str_replace(["\r", "\n", '"'], '', $filename));
        $attachments = [...$this->attachments, ['filename' => $cleanFilename, 'contentType' => $contentType, 'bytes' => $bytes]];

        return new self($this->from, $this->to, $this->cc, $this->bcc, $this->replyTo, $this->subject, $this->textBody, $this->htmlBody, $attachments);
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
