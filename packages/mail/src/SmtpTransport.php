<?php

declare(strict_types=1);

namespace Kongono\Mail;

use SensitiveParameter;

/** Pure PHP 8.5 SMTP client supporting STARTTLS and AUTH PLAIN (zero Symfony Mailer). */
final readonly class SmtpTransport implements MailTransportInterface
{
    private const int MAX_RESPONSE_LINES = 128;
    private const int MAX_RESPONSE_BYTES = 65_536;

    private string $host;

    public function __construct(
        string $host,
        private int $port = 587,
        private ?string $username = null,
        #[SensitiveParameter]
        private ?string $password = null,
        private bool $startTls = true,
        private int $timeoutSeconds = 15,
        private bool $implicitTls = false,
        private bool $allowInsecureAuth = false,
    ) {
        $this->host = self::validateHost($host);
        if ($port < 1 || $port > 65535) {
            throw new MailException(sprintf('Invalid SMTP port %d.', $port));
        }
        if ($timeoutSeconds < 1) {
            throw new MailException(sprintf('Invalid SMTP timeout %d seconds.', $timeoutSeconds));
        }
        if (null !== $username && false !== strpbrk($username, "\0\r\n")) {
            throw new MailException('SMTP username cannot contain NUL, CR, or LF bytes.');
        }
        if (null !== $password && false !== strpbrk($password, "\0\r\n")) {
            throw new MailException('SMTP password cannot contain NUL, CR, or LF bytes.');
        }
    }

    /**
     * Canonicalize line endings and dot-stuff per RFC 5321 Section 4.5.2 to block SMTP smuggling (CVE-2023-51764 class).
     */
    public static function prepareDataPayload(string $mime): string
    {
        if (str_contains($mime, "\0")) {
            throw new MailException('SMTP message payload cannot contain NUL bytes.');
        }

        // Canonicalize CRLF, bare CR, and bare LF to CRLF deterministically (no PCRE backtrack limit on large payloads)
        $normalized = str_replace(["\r\n", "\r", "\n"], "\n", $mime);
        $crlf = str_replace("\n", "\r\n", $normalized);
        $stuffed = str_replace("\r\n.", "\r\n..", $crlf);
        if (str_starts_with($stuffed, '.')) {
            $stuffed = '.' . $stuffed;
        }

        return $stuffed;
    }

    public function send(Email $email): string
    {
        if (null === $email->from) {
            throw new MailException('SMTP send requires a From address.');
        }
        if (null !== $this->username && null !== $this->password && !$this->startTls && !$this->implicitTls && !$this->allowInsecureAuth) {
            throw new MailException('Refusing cleartext SMTP AUTH PLAIN without TLS. Enable STARTTLS/implicit TLS or pass allowInsecureAuth: true explicitly.');
        }

        $mime = $email->toMimeString();
        $stuffed = self::prepareDataPayload($mime);

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_self_signed' => false,
                'peer_name' => trim($this->host, '[]'),
                'crypto_method' => STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT,
            ],
        ]);
        $transport = $this->implicitTls ? 'tls' : 'tcp';
        $socket = @stream_socket_client(
            sprintf('%s://%s:%d', $transport, $this->host, $this->port),
            $errno,
            $errstr,
            $this->timeoutSeconds,
            STREAM_CLIENT_CONNECT,
            $context,
        );
        if (false === $socket) {
            throw new MailException(sprintf('SMTP connection failed (%d): %s', $errno, $errstr));
        }

        stream_set_timeout($socket, $this->timeoutSeconds);

        try {
            $this->expectCode($socket, 220);
            $this->command($socket, 'EHLO kongono.local', 250);

            if ($this->startTls && !$this->implicitTls) {
                $this->command($socket, 'STARTTLS', 220);
                // Block STARTTLS response-injection (CVE-2011-0411 class): reject any unconsumed plaintext bytes before TLS upgrade
                $meta = stream_get_meta_data($socket);
                if ($meta['unread_bytes'] > 0) {
                    throw new MailException('SMTP STARTTLS response injection detected: unexpected plaintext bytes buffered before TLS handshake.');
                }
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
            $this->writeAll($socket, $stuffed . "\r\n.\r\n");
            $this->expectCode($socket, 250);
            // Message is already accepted (250 OK); treat QUIT failures as best-effort to prevent duplicate retries
            try {
                $this->command($socket, 'QUIT', 221);
            } catch (MailException) {
                // Ignore graceful disconnect errors after 250 acceptance
            }

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
        $this->writeAll($socket, $line . "\r\n");
        $this->expectCode($socket, $expectedCode);
    }

    /** @param resource $socket */
    private function writeAll(mixed $socket, string $data): void
    {
        $total = strlen($data);
        $written = 0;
        while ($written < $total) {
            $n = @fwrite($socket, substr($data, $written, 65536));
            if (false === $n || 0 === $n) {
                throw new MailException('SMTP stream write failed.');
            }
            $written += $n;
        }
    }

    /** @param resource $socket */
    private function expectCode(mixed $socket, int $expectedCode): void
    {
        $linesRead = 0;
        $bytesRead = 0;
        $firstCode = null;
        $finalCode = null;

        while (true) {
            $line = '';
            while (false !== ($chunk = fgets($socket, 1024))) {
                $line .= $chunk;
                $bytesRead += strlen($chunk);
                if ($bytesRead > self::MAX_RESPONSE_BYTES) {
                    throw new MailException('SMTP server response exceeded maximum safe size.');
                }
                if (str_ends_with($chunk, "\n")) {
                    break;
                }
            }
            if ('' === $line) {
                $meta = stream_get_meta_data($socket);
                if ($meta['timed_out']) {
                    throw new MailException('SMTP server response timed out.');
                }
                break;
            }
            ++$linesRead;
            if ($linesRead > self::MAX_RESPONSE_LINES) {
                throw new MailException('SMTP server response exceeded maximum safe size.');
            }
            if (strlen($line) < 3 || !ctype_digit(substr($line, 0, 3))) {
                throw new MailException('Malformed SMTP server response line.');
            }
            $lineCode = (int) substr($line, 0, 3);
            if (null === $firstCode) {
                $firstCode = $lineCode;
            } elseif ($lineCode !== $firstCode) {
                throw new MailException('Inconsistent SMTP multi-line response codes.');
            }
            $sep = $line[3] ?? '';
            if (' ' === $sep || "\r" === $sep || "\n" === $sep || '' === $sep) {
                $finalCode = $lineCode;
                break;
            }
            if ('-' !== $sep) {
                throw new MailException('Malformed SMTP multi-line response separator.');
            }
        }

        if (null === $finalCode || $finalCode !== $expectedCode) {
            throw new MailException(sprintf('Unexpected SMTP response (expected %d, got %d).', $expectedCode, $finalCode ?? 0));
        }
    }

    private static function validateHost(string $host): string
    {
        if (false !== strpbrk($host, "\r\n\0")) {
            throw new MailException('Invalid SMTP host.');
        }
        $trimmed = trim($host);
        if ('' === $trimmed || strlen($trimmed) > 255 || false !== strpbrk($trimmed, " \t/?#@%\\")) {
            throw new MailException('Invalid SMTP host.');
        }
        if (str_starts_with($trimmed, '[') && str_ends_with($trimmed, ']')) {
            $inner = substr($trimmed, 1, -1);
            if (false === filter_var($inner, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                throw new MailException('Invalid bracketed IPv6 SMTP host.');
            }

            return '[' . strtolower($inner) . ']';
        }
        if (false !== filter_var($trimmed, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return '[' . strtolower($trimmed) . ']';
        }
        if (false !== filter_var($trimmed, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $trimmed;
        }
        if (1 !== preg_match('/\A[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*\z/', $trimmed)) {
            throw new MailException('Invalid SMTP host.');
        }

        return $trimmed;
    }
}
