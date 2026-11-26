# Kongono Validation (`kongono/validation`)

> **Read-only subtree split** of [`kongono/framework`](https://github.com/kongono/framework). Open issues and pull requests in the main monorepo.

Zero-bloat, strict PHP 8.5.13+ attribute-driven readonly DTO validator supporting nested DTOs, `BackedEnum`, `#[ArrayOf]`, `#[Required]`, `#[Email]`, `#[Uuid]`, `#[Min]`, `#[Max]`, `#[Between]`, `#[Date]`, `#[Ip]`, `#[In]`, `#[NotIn]`, `#[Regex]`, `#[RequiredIf]`, `#[Confirmed]`, custom `RuleInterface`, and `ValidatableDtoInterface`.

## Installation

```bash
composer require kongono/validation
```

## Example

```php
<?php

declare(strict_types=1);

use Kongono\Validation\Attributes\{Email, Max, Min, Required};
use Kongono\Validation\Validator;

final readonly class CreateUserInput
{
    public function __construct(
        #[Required, Min(2), Max(80)]
        public string $name,
        #[Required, Email]
        public string $email,
    ) {}
}

$dto = (new Validator())->validateDto(CreateUserInput::class, [
    'name' => 'Aoi',
    'email' => 'aoi@example.com',
]);
```

## Documentation & License

- Full documentation: https://kongono.com/docs.html#validation
- Monorepo & issues: https://github.com/kongono/framework
- License: MIT
