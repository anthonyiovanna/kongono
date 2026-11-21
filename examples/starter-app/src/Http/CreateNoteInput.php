<?php

declare(strict_types=1);

namespace StarterApp\Http;

use Kongono\Validation\Attributes\Max;
use Kongono\Validation\Attributes\Min;
use Kongono\Validation\Attributes\Required;

final readonly class CreateNoteInput
{
    public function __construct(
        #[Required, Min(3), Max(120)]
        public string $title,
        #[Required, Min(1), Max(5000)]
        public string $body,
    ) {}
}
