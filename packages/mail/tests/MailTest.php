<?php

declare(strict_types=1);

namespace Kongono\Mail\Tests;

use Kongono\Core\Log\Logger;
use Kongono\Core\Notifications\Notification;
use Kongono\Core\Notifications\NotificationDispatcher;
use Kongono\Mail\Address;
use Kongono\Mail\Email;
use Kongono\Mail\LogTransport;
use Kongono\Mail\Mailer;
use Kongono\Mail\MailException;
use Kongono\Mail\MailNotificationChannel;
use Kongono\Mail\MemoryTransport;
use Kongono\Mail\ResendTransport;
use Kongono\Mail\SmtpTransport;
use Kongono\View\ViewEngine;
use PHPUnit\Framework\TestCase;

final class MailTest extends TestCase
{
    public function testEmailBuildsMimeRejectsInjectionAndSendsViaMemoryLogAndResend(): void
    {
        $memory = new MemoryTransport();
        $mailer = new Mailer($memory, new Address('no-reply@kongono.dev', 'Kongono'));

        $email = Email::create()
            ->to('ada@example.com', 'Ada Lovelace')
            ->subject('Welcome aboard')
            ->text('Plain text version')
            ->html('<h1>Welcome</h1>')
            ->attach('guide.txt', 'hello attachment', 'text/plain');

        $id = $mailer->send($email);
        self::assertSame('mem_1', $id);
        self::assertCount(1, $memory->sent());

        $mime = $memory->sent()[0]->toMimeString();
        self::assertStringContainsString('From: "Kongono" <no-reply@kongono.dev>', $mime);
        self::assertStringContainsString('Subject: Welcome aboard', $mime);
        self::assertStringContainsString('filename="guide.txt"', $mime);

        // LogTransport records structured log
        $logger = new Logger('mail', retainInMemory: true);
        (new Mailer(new LogTransport($logger), new Address('no-reply@kongono.dev')))->send($email);
        self::assertCount(1, $logger->records());
        self::assertSame('Welcome aboard', $logger->records()[0]['context']['subject']);

        // ResendTransport speaks HTTPS JSON wire protocol
        $captured = [];
        $resend = new ResendTransport('re_test_123', httpSender: static function (string $url, array $headers, string $body) use (&$captured): array {
            $captured = ['url' => $url, 'headers' => $headers, 'body' => $body];

            return ['status' => 200, 'body' => '{"id":"re_msg_42"}'];
        });
        $resendId = (new Mailer($resend, new Address('no-reply@kongono.dev')))->send($email);
        self::assertSame('re_msg_42', $resendId);
        self::assertSame('Bearer re_test_123', $captured['headers']['Authorization']);

        // Rejects header injection in email address
        $this->expectException(MailException::class);
        new Address("evil@example.com\r\nBcc: victim@example.com");
    }

    public function testMailerSendViewRendersCompiledTemplate(): void
    {
        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kongono-mail-view-' . bin2hex(random_bytes(5));
        mkdir($dir . '/views', 0o777, true);
        mkdir($dir . '/cache', 0o777, true);
        file_put_contents($dir . '/views/welcome.kgn.html', '<p>Hi {{ $name }}!</p>');

        try {
            $memory = new MemoryTransport();
            $mailer = new Mailer($memory, new Address('hi@kongono.dev'), new ViewEngine($dir . '/views', $dir . '/cache'));
            $mailer->sendView('welcome', ['name' => 'Grace'], static fn(Email $e): Email => $e->to('grace@example.com')->subject('Hello'));

            self::assertCount(1, $memory->sent());
            self::assertSame('<p>Hi Grace!</p>', $memory->sent()[0]->htmlBody);
        } finally {
            foreach (glob($dir . '/cache/*') ?: [] as $f) {
                @unlink($f);
            }
            @unlink($dir . '/views/welcome.kgn.html');
            @rmdir($dir . '/views');
            @rmdir($dir . '/cache');
            @rmdir($dir);
        }
    }

    public function testMailNotificationChannelDeliversThroughDispatcher(): void
    {
        $memory = new MemoryTransport();
        $mailer = new Mailer($memory, new Address('no-reply@kongono.dev', 'Kongono'));

        $dispatcher = (new NotificationDispatcher())
            ->registerChannel(new MailNotificationChannel($mailer));

        $res = $dispatcher->send(
            ['email' => 'ada@kongono.dev', 'name' => 'Ada'],
            new Notification('welcome', 'Welcome!', 'Glad you are here.'),
            ['mail'],
        );

        self::assertTrue($res['mail']['ok']);
        self::assertCount(1, $memory->sent());
        self::assertSame('Welcome!', $memory->sent()[0]->subject);
    }

    public function testRound3SmtpSmugglingMimeBreakoutAndResendTransportHardening(): void
    {
        // 1. CVE-2023-51764: bare LF / bare CR dot-smuggling sequences are canonicalized & dot-stuffed
        $smuggled = "Subject: Hi\n\nBody\n.\nMAIL FROM:<evil@attacker.test>\r.\rDATA";
        $prepared = SmtpTransport::prepareDataPayload($smuggled);
        self::assertStringNotContainsString("\r\n.\r\n", $prepared);
        self::assertStringContainsString("\r\n..\r\nMAIL FROM:", $prepared);
        self::assertStringContainsString("\r\n..\r\nDATA", $prepared);

        // NUL bytes in SMTP DATA rejected
        try {
            SmtpTransport::prepareDataPayload("Hello\0World");
            self::fail('Expected NUL byte in SMTP payload to be rejected.');
        } catch (MailException $e) {
            self::assertStringContainsString('NUL', $e->getMessage());
        }

        // 2. Address::format() escapes backslash BEFORE quote so `Support\` cannot break out of quotes
        $addr = new Address('support@example.com', 'Support\\');
        self::assertSame('"Support\\\\" <support@example.com>', $addr->format());

        // 3. Direct Email::__construct() strips CRLF in subject and validates attachment MIME types & filenames
        $direct = new Email(
            to: [new Address('ada@example.com')],
            subject: "Hello\r\nBcc: evil@attacker.test",
            textBody: 'ok',
        );
        self::assertStringNotContainsString("\r", $direct->subject);
        self::assertStringNotContainsString("\n", $direct->subject);

        try {
            Email::create()->to('ada@example.com')->subject('Hi')->attach('a.txt', 'x', "text/plain\r\nX-Evil: 1");
            self::fail('Expected invalid MIME content type to be rejected.');
        } catch (MailException $e) {
            self::assertStringContainsString('Unsafe MIME content type', $e->getMessage());
        }

        // 4. ResendTransport rejects plaintext HTTP endpoint and CRLF in API key
        try {
            new ResendTransport('re_test_123', endpoint: 'http://api.resend.com/emails');
            self::fail('Expected non-HTTPS Resend endpoint to be rejected.');
        } catch (MailException $e) {
            self::assertStringContainsString('https://', $e->getMessage());
        }

        $this->expectException(MailException::class);
        $this->expectExceptionMessage('CR, LF, or NUL');
        new ResendTransport("re_test_123\r\nX-Injected: 1");
    }
}
