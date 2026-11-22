<?php

declare(strict_types=1);

namespace Kongono\Mail;

use SensitiveParameter;

/** Pure PHP 8.5 SMTP client supporting STARTTLS and AUTH PLAIN (zero Symfony Mailer). */
final readonly class SmtpTransport implements MailTransportInterface
{
    public function __construct(
        private string $host,
        private int $port = 587,
        private ?string $username = null,
        #[SensitiveParameter]
        private ?string $password = null,
        private bool $startTls = true,
        private int $timeoutSeconds = 15,
    ) {}

    public function send(Email $email): string
    {
        if (null === $email->from) {
            throw new MailException('SMTP send requires a From address.');
        }

        $mime = $email->toMimeString();
        $socket = @stream_socket_client(
            sprintf('tcp://%s:%d', $this->host, $this->port),
            $errno,
            $errstr,
            $this->timeoutSeconds,
        );
        if (false === $socket) {
            throw new MailException(sprintf('SMTP connection failed (%d): %s', $errno, $errstr));
        }

        stream_set_timeout($socket, $this->timeoutSeconds);

        try {
            $this->expectCode($socket, 220);
            $this->command($socket, 'EHLO kongono.local', 250);

            if ($this->startTls) {
                $this->command($socket, 'STARTTLS', 220);
                $crypto = stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT);
                if (true !== $crypto) {
                    throw new MailException('SMTP STARTTLS negotiation failed.');
                }
                $this->command($socket, 'EHLO kongono.local', 250);
            }

            if (null !== $this->username && null !== $this->password) {
                $auth = base64_encode("\0" . $this->username . "\0" . $this->password);
                $this->command($socket, 'AUTH PLAIN ' . $auth, 235);
            }

            $this->command($socket, sprintf('MAIL FROM:<%s>', $email->from->email), 250);
            foreach ([...$email->to, ...$email->cc, ...$email->bcc] as $recipient) {
                $this->command($socket, sprintf('RCPT TO:<%s>', $recipient->email), 250);
            }

            $this->command($socket, 'DATA', 354);
            // Dot-stuff per RFC 5321 Section 4.5.2
            $stuffed = str_replace("\r\n.", "\r\n..", $mime);
            fwrite($socket, $stuffed . "\r\n.\r\n");
            $this->expectCode($socket, 250);
            $this->command($socket, 'QUIT', 221);

            return 'smtp_' . bin2hex(random_bytes(8));
        } finally {
            fclose($socket);
        }
    }

    /** @param resource $socket */
    private function command(mixed $socket, string $line, int $expectedCode): void
    {
        if (false !== strpbrk($line, "\r\n\0")) {
            throw new MailException('SMTP command injection rejected: command contains CR, LF, or NUL.');
        }
        fwrite($socket, $line . "\r\n");
        $this->expectCode($socket, $expectedCode);
    }

    /** @param resource $socket */
    private function expectCode(mixed $socket, int $expectedCode): void
    {
        $response = '';
        while (false !== ($line = fgets($socket, 1024))) {
            $response .= $line;
            if (strlen($line) >= 4 && ' ' === $line[3]) {
                break;
            }
        }
        $code = (int) substr($response, 0, 3);
        if ($code !== $expectedCode) {
            throw new MailException(sprintf('Unexpected SMTP response (expected %d, got %d).', $expectedCode, $code));
        }
    }
}
