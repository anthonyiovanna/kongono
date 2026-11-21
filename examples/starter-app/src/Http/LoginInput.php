<?php

declare(strict_types=1);

namespace StarterApp\Http;

use Kongono\Validation\Attributes\Email;
use Kongono\Validation\Attributes\Required;

final readonly class LoginInput
{
    public function __construct(
        #[Required, Email]
        public string $email,
        #[Required]
        public string $password,
    ) {}
}
