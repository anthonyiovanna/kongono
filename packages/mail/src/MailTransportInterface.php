<?php

declare(strict_types=1);

namespace Kongono\Mail;

interface MailTransportInterface
{
    /** Send one validated Email and return a provider/transport message ID. */
    public function send(Email $email): string;
}
