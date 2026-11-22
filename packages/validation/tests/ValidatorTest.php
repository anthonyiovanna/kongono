<?php

declare(strict_types=1);

namespace Kongono\Validation\Tests;

use Kongono\Validation\Attributes\Between;
use Kongono\Validation\Attributes\Confirmed;
use Kongono\Validation\Attributes\Email;
use Kongono\Validation\Attributes\In;
use Kongono\Validation\Attributes\Max;
use Kongono\Validation\Attributes\Min;
use Kongono\Validation\Attributes\Required;
use Kongono\Validation\Attributes\Url;
use Kongono\Validation\Attributes\Uuid;
use Kongono\Validation\ValidationException;
use Kongono\Validation\Validator;
use PHPUnit\Framework\TestCase;

enum Priority: string
{
    case Low = 'low';
    case High = 'high';
}

final readonly class AddressDto
{
    public function __construct(
        #[Required, Min(2)]
        public string $city,
        #[Required, Between(5, 10)]
        public string $postalCode,
    ) {}
}

final readonly class OrderDto
{
    public function __construct(
        #[Required, Uuid]
        public string $requestId,
        public Priority $priority,
        #[Between(1, 100)]
        public int $quantity,
        #[Required, Min(8)]
        public string $password,
        #[Required, Confirmed('password')]
        public string $passwordConfirmation,
        public AddressDto $shipping,
    ) {}
}

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

final readonly class WebhookTargetDto
{
    public function __construct(
        #[Required, Url]
        public string $endpoint,
    ) {}
}

final readonly class BoundedPayloadDto
{
    /** @param list<string> $tags */
    public function __construct(
        #[Min(1), Max(3)]
        public array $tags,
        public int $count,
        public float $ratio,
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

    public function testValidatesBackedEnumsNestedDtosUuidBetweenAndConfirmed(): void
    {
        $validator = new Validator();
        $dto = $validator->validate(OrderDto::class, [
            'requestId' => '550e8400-e29b-41d4-a716-446655440000',
            'priority' => 'high',
            'quantity' => '10',
            'password' => 'super-secret-pass',
            'passwordConfirmation' => 'super-secret-pass',
            'shipping' => [
                'city' => 'Tokyo',
                'postalCode' => '100-0001',
            ],
        ]);

        self::assertSame(Priority::High, $dto->priority);
        self::assertSame(10, $dto->quantity);
        self::assertSame('Tokyo', $dto->shipping->city);

        try {
            $validator->validate(OrderDto::class, [
                'requestId' => 'not-a-uuid',
                'priority' => 'urgent',
                'quantity' => 500,
                'password' => 'super-secret-pass',
                'passwordConfirmation' => 'mismatch',
                'shipping' => [
                    'city' => 'A',
                    'postalCode' => '12',
                ],
            ]);
            self::fail('Expected ValidationException.');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('requestId', $e->errors);
            self::assertArrayHasKey('priority', $e->errors);
            self::assertArrayHasKey('quantity', $e->errors);
            self::assertArrayHasKey('passwordConfirmation', $e->errors);
            self::assertArrayHasKey('shipping.city', $e->errors);
            self::assertArrayHasKey('shipping.postalCode', $e->errors);
        }
    }

    public function testUrlAttributeBlocksJavascriptFileGopherAndDataSchemes(): void
    {
        $validator = new Validator();
        $ok = $validator->validate(WebhookTargetDto::class, ['endpoint' => 'https://api.kongono.dev/hooks']);
        self::assertSame('https://api.kongono.dev/hooks', $ok->endpoint);

        foreach (['javascript://%0aalert(1)', 'file:///etc/passwd', 'gopher://127.0.0.1:6379/', 'data:text/html,<script>alert(1)</script>'] as $hostile) {
            try {
                $validator->validate(WebhookTargetDto::class, ['endpoint' => $hostile]);
                self::fail('Expected #[Url] to reject hostile scheme: ' . $hostile);
            } catch (ValidationException $e) {
                self::assertArrayHasKey('endpoint', $e->errors);
            }
        }
    }

    public function testRound3RejectsArraySizeOverflowNonFiniteFloatsAndIntOverflow(): void
    {
        $validator = new Validator();
        $ok = $validator->validate(BoundedPayloadDto::class, [
            'tags' => ['a', 'b'],
            'count' => '42',
            'ratio' => '0.5',
        ]);
        self::assertSame(['a', 'b'], $ok->tags);
        self::assertSame(42, $ok->count);
        self::assertSame(0.5, $ok->ratio);

        try {
            $validator->validate(BoundedPayloadDto::class, [
                'tags' => ['a', 'b', 'c', 'd'],
                'count' => '999999999999999999999999999999',
                'ratio' => '1e999',
            ]);
            self::fail('Expected array Max, integer overflow, and INF float to fail validation.');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('tags', $e->errors);
            self::assertArrayHasKey('count', $e->errors);
            self::assertArrayHasKey('ratio', $e->errors);
        }
    }
}
