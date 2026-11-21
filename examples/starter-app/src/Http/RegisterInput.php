<?php

declare(strict_types=1);

namespace StarterApp\Http;

use Kongono\Validation\Attributes\Email;
use Kongono\Validation\Attributes\Max;
use Kongono\Validation\Attributes\Min;
use Kongono\Validation\Attributes\Required;

final readonly class RegisterInput
{
    public function __construct(
        #[Required, Min(2), Max(80)]
        public string $name,
        #[Required, Email]
        public string $email,
        #[Required, Min(8), Max(128)]
        public string $password,
    ) {}
}
