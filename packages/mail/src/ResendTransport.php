<?php

declare(strict_types=1);

namespace Kongono\Mail;

use Closure;
use JsonException;
use Kongono\Core\Diagnostics\SecretScrubber;
use SensitiveParameter;

/** Modern HTTPS transactional email transport (Resend API compatible, pure ext-curl). */
final readonly class ResendTransport implements MailTransportInterface
{
    private const int MAX_RESPONSE_BYTES = 2_097_152;

    /** @param (Closure(string, array<string, string>, string): array{status: int, body: string})|null $httpSender */
    public function __construct(
        #[SensitiveParameter]
        private string $apiKey,
        private string $endpoint = 'https://api.resend.com/emails',
        private ?Closure $httpSender = null,
    ) {
        if (false !== strpbrk($apiKey, "\r\n\0")) {
            throw new MailException('Invalid Resend API key: contains CR, LF, or NUL.');
        }
        $parts = parse_url($endpoint);
        $scheme = is_array($parts) && is_string($parts['scheme'] ?? null) ? strtolower($parts['scheme']) : '';
        if (
            false === $parts
            || 'https' !== $scheme
            || !is_string($parts['host'] ?? null)
            || '' === $parts['host']
            || isset($parts['user'])
            || isset($parts['pass'])
            || false !== strpbrk($endpoint, "\r\n\0")
        ) {
            throw new MailException('ResendTransport endpoint must be a valid https:// URL without userinfo or control characters.');
        }
    }

    public function send(Email $email): string
    {
        if (null === $email->from) {
            throw new MailException('Email requires a From address.');
        }
        if ([] === $email->to) {
            throw new MailException('Email requires at least one To recipient.');
        }

        $payload = [
            'from' => $email->from->format(),
            'to' => array_map(static fn(Address $a): string => $a->format(), $email->to),
            'subject' => $email->subject,
        ];
        if ([] !== $email->cc) {
            $payload['cc'] = array_map(static fn(Address $a): string => $a->format(), $email->cc);
        }
        if ([] !== $email->bcc) {
            $payload['bcc'] = array_map(static fn(Address $a): string => $a->format(), $email->bcc);
        }
        if (null !== $email->replyTo) {
            $payload['reply_to'] = $email->replyTo->format();
        }
        if (null !== $email->htmlBody) {
            $payload['html'] = $email->htmlBody;
        }
        if (null !== $email->textBody) {
            $payload['text'] = $email->textBody;
        }

        $json = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $headers = [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ];

        $response = null !== $this->httpSender
            ? ($this->httpSender)($this->endpoint, $headers, $json)
            : $this->sendCurl($this->endpoint, $headers, $json);

        if ($response['status'] < 200 || $response['status'] >= 300) {
            throw new MailException(sprintf(
                'Email API returned HTTP %d: %s',
                $response['status'],
                SecretScrubber::scrub(substr($response['body'], 0, 300)),
            ));
        }

        try {
            $decoded = json_decode($response['body'], true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new MailException('Email API returned invalid JSON.', previous: $e);
        }

        return is_array($decoded) && is_string($decoded['id'] ?? null)
            ? $decoded['id']
            : 'resend_' . bin2hex(random_bytes(8));
    }

    /**
     * @param array<string, string> $headers
     * @return array{status: int, body: string}
     */
    private function sendCurl(string $url, array $headers, string $json): array
    {
        $ch = curl_init($url);
        if (false === $ch) {
            throw new MailException('Unable to initialize cURL for email transport.');
        }

        $lines = [];
        foreach ($headers as $k => $v) {
            $lines[] = $k . ': ' . $v;
        }

        $body = '';
        $overflowed = false;
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_HTTPHEADER => $lines,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROTOCOLS_STR => 'https',
            CURLOPT_REDIR_PROTOCOLS_STR => 'https',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_WRITEFUNCTION => static function (mixed $_ch, string $chunk) use (&$body, &$overflowed): int {
                if (strlen($body) + strlen($chunk) > self::MAX_RESPONSE_BYTES) {
                    $overflowed = true;

                    return 0;
                }
                $body .= $chunk;

                return strlen($chunk);
            },
        ]);

        $ok = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $err = curl_error($ch);

        if ($overflowed) {
            throw new MailException(sprintf('Email API response exceeded maximum bound of %d bytes.', self::MAX_RESPONSE_BYTES));
        }
        if (false === $ok) {
            throw new MailException('Email HTTP transport failed: ' . SecretScrubber::scrub($err));
        }

        return ['status' => $status, 'body' => $body];
    }
}
