<?php

declare(strict_types=1);

namespace Kongono\Validation\Tests;

use Kongono\Validation\Attributes\Email;
use Kongono\Validation\Attributes\In;
use Kongono\Validation\Attributes\Max;
use Kongono\Validation\Attributes\Min;
use Kongono\Validation\Attributes\Required;
use Kongono\Validation\ValidationException;
use Kongono\Validation\Validator;
use PHPUnit\Framework\TestCase;

final readonly class SignupDto
{
    public function __construct(
        #[Required, Min(3), Max(40)]
        public string $name,
        #[Required, Email]
        public string $email,
        #[Min(18), Max(120)]
        public int $age,
        #[In(['free', 'pro'])]
        public string $plan = 'free',
    ) {}
}

final class ValidatorTest extends TestCase
{
    public function testValidatesAndCoercesTypedDto(): void
    {
        $validator = new Validator();
        $dto = $validator->validate(SignupDto::class, [
            'name' => '  Ada Lovelace  ',
            'email' => 'ada@kongono.dev',
            'age' => '29',
            'plan' => 'pro',
        ]);

        self::assertSame('Ada Lovelace', $dto->name);
        self::assertSame('ada@kongono.dev', $dto->email);
        self::assertSame(29, $dto->age);
        self::assertSame('pro', $dto->plan);
    }

    public function testAggregatesMultipleFieldErrors(): void
    {
        $validator = new Validator();

        try {
            $validator->validate(SignupDto::class, [
                'name' => 'Al',
                'email' => 'not-an-email',
                'age' => '12',
                'plan' => 'enterprise',
            ]);
            self::fail('Expected ValidationException.');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('name', $e->errors);
            self::assertArrayHasKey('email', $e->errors);
            self::assertArrayHasKey('age', $e->errors);
            self::assertArrayHasKey('plan', $e->errors);
            self::assertSame('KGN-VALIDATION-422', $e->diagnostic?->id);
        }
    }
}
