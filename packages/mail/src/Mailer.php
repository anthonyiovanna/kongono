<?php

declare(strict_types=1);

namespace Kongono\Mail;

use Closure;
use Kongono\View\ViewEngine;

/** High-level Mailer supporting default From address and compiled `.kgn.html` email templates. */
final readonly class Mailer
{
    public function __construct(
        private MailTransportInterface $transport,
        private ?Address $defaultFrom = null,
        private ?ViewEngine $views = null,
    ) {}

    public function send(Email $email): string
    {
        if (null === $email->from && null !== $this->defaultFrom) {
            $email = $email->from($this->defaultFrom->email, $this->defaultFrom->name);
        }

        return $this->transport->send($email);
    }

    /**
     * Render a `.kgn.html` template as the HTML body of an Email and send it.
     *
     * @param array<string, mixed>  $data
     * @param Closure(Email): Email $configure
     */
    public function sendView(string $template, array $data, Closure $configure): string
    {
        if (null === $this->views) {
            throw new MailException('ViewEngine is not configured on Mailer; cannot render email template.');
        }

        $html = $this->views->render($template, $data);
        $email = $configure(Email::create()->html($html));

        return $this->send($email);
    }

    public function transport(): MailTransportInterface
    {
        return $this->transport;
    }
}
