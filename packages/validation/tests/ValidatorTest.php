<?php

declare(strict_types=1);

namespace Kongono\Validation\Tests;

use Attribute;
use Kongono\Validation\Attributes\ArrayOf;
use Kongono\Validation\Attributes\Between;
use Kongono\Validation\Attributes\Confirmed;
use Kongono\Validation\Attributes\Date;
use Kongono\Validation\Attributes\Email;
use Kongono\Validation\Attributes\In;
use Kongono\Validation\Attributes\Ip;
use Kongono\Validation\Attributes\Max;
use Kongono\Validation\Attributes\Min;
use Kongono\Validation\Attributes\NotIn;
use Kongono\Validation\Attributes\Required;
use Kongono\Validation\Attributes\RequiredIf;
use Kongono\Validation\Attributes\Url;
use Kongono\Validation\Attributes\Uuid;
use Kongono\Validation\RuleInterface;
use Kongono\Validation\ValidatableDtoInterface;
use Kongono\Validation\ValidationException;
use Kongono\Validation\Validator;
use PHPUnit\Framework\TestCase;

enum Priority: string
{
    case Low = 'low';
    case High = 'high';
}

enum PureUnitStatus
{
    case Active;
}

final readonly class PureEnumDto
{
    public function __construct(
        public PureUnitStatus $status,
    ) {}
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

    public function testRound4RejectsPureUnitEnumCrashAndFrameworkClassHydration(): void
    {
        $validator = new Validator();
        $this->expectException(\Kongono\Core\CoreException::class);
        $this->expectExceptionMessage('UnitEnum');
        $validator->validate(PureEnumDto::class, ['status' => ['Active']]);
    }

    public function testCustomRuleInterfaceAndValidatableDtoCrossFieldHooks(): void
    {
        $validator = new Validator();

        $ok = $validator->validate(CampaignInputDto::class, [
            'slug' => 'spring-launch-2026',
            'startDate' => '2026-04-01',
            'endDate' => '2026-04-15',
            'budget' => 500,
            'approverEmail' => null,
        ]);
        self::assertSame('spring-launch-2026', $ok->slug);

        // Invalid custom #[Slug] fails cleanly
        try {
            $validator->validate(CampaignInputDto::class, [
                'slug' => 'Invalid Slug!!',
                'startDate' => '2026-04-01',
                'endDate' => '2026-04-15',
                'budget' => 100,
            ]);
            self::fail('Expected custom SlugRule to reject invalid slug.');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('slug', $e->errors);
            self::assertStringContainsString('lowercase slug', $e->errors['slug'][0]);
        }

        // ValidatableDtoInterface cross-field check rejects high budget without approverEmail
        try {
            $validator->validate(CampaignInputDto::class, [
                'slug' => 'big-campaign',
                'startDate' => '2026-04-01',
                'endDate' => '2026-04-15',
                'budget' => 25_000,
            ]);
            self::fail('Expected ValidatableDtoInterface hook to reject budget > 10000 without approverEmail.');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('approverEmail', $e->errors);
            self::assertStringContainsString('finance approver', $e->errors['approverEmail'][0]);
        }
    }

    public function testArrayOfHydratesTypedDtoCollectionsEnumsAndItemRules(): void
    {
        $validator = new Validator();

        $cart = $validator->validate(CartCheckoutDto::class, [
            'items' => [
                ['sku' => 'LOT-001', 'qty' => '2'],
                ['sku' => 'LOT-002', 'qty' => 5],
            ],
            'priorities' => ['low', 'high'],
            'notifyEmails' => ['ops@kongono.dev', 'billing@kongono.dev'],
        ]);

        self::assertCount(2, $cart->items);
        self::assertInstanceOf(CartLineItemDto::class, $cart->items[0]);
        self::assertSame('LOT-001', $cart->items[0]->sku);
        self::assertSame(2, $cart->items[0]->qty);
        self::assertSame([Priority::Low, Priority::High], $cart->priorities);
        self::assertSame(['ops@kongono.dev', 'billing@kongono.dev'], $cart->notifyEmails);

        // Indexed error keys on nested DTO items, BackedEnum items, and scalar item rules
        try {
            $validator->validate(CartCheckoutDto::class, [
                'items' => [
                    ['sku' => 'OK-1', 'qty' => 1],
                    ['sku' => 'X', 'qty' => 0],
                ],
                'priorities' => ['low', 'invalid-priority'],
                'notifyEmails' => ['good@kongono.dev', 'not-an-email'],
            ]);
            self::fail('Expected ArrayOf validation errors.');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('items.1.sku', $e->errors);
            self::assertArrayHasKey('items.1.qty', $e->errors);
            self::assertArrayHasKey('priorities.1', $e->errors);
            self::assertArrayHasKey('notifyEmails.1', $e->errors);
        }

        // maxItems DoS cap fails closed
        try {
            $validator->validate(CartCheckoutDto::class, [
                'items' => [
                    ['sku' => 'A-1', 'qty' => 1],
                    ['sku' => 'A-2', 'qty' => 1],
                    ['sku' => 'A-3', 'qty' => 1],
                    ['sku' => 'A-4', 'qty' => 1],
                ],
                'priorities' => ['low'],
                'notifyEmails' => ['ops@kongono.dev'],
            ]);
            self::fail('Expected ArrayOf maxItems cap to reject > 3 items.');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('items', $e->errors);
            self::assertStringContainsString('at most 3 items', $e->errors['items'][0]);
        }
    }

    public function testDateIpNotInAndRequiredIfAttributes(): void
    {
        $validator = new Validator();

        // Valid payload (pickup -> shippingCity not required; corporate=false -> companyVat not required)
        $ok = $validator->validate(ShipmentScheduleDto::class, [
            'handle' => 'anthony',
            'dispatchDate' => '2026-06-10',
            'arrivalDate' => '2026-06-15',
            'customDate' => '15/06/2026',
            'clientIp' => '198.51.100.42',
            'ipv6Gateway' => '2001:db8::1',
            'deliveryMethod' => 'pickup',
            'isCorporate' => false,
        ]);
        self::assertSame('anthony', $ok->handle);
        self::assertNull($ok->shippingCity);
        self::assertNull($ok->companyVat);

        // Trigger RequiredIf (deliveryMethod='ship', isCorporate='true') + test Date/Ip/NotIn rejections
        try {
            $validator->validate(ShipmentScheduleDto::class, [
                'handle' => 'admin',
                'dispatchDate' => '2026-02-30', // Impossible calendar date
                'arrivalDate' => '2026-06-05',
                'customDate' => '2026-06-15', // Wrong format (expected d/m/Y)
                'clientIp' => '10.0.0.5', // Private IP blocked when allowPrivate: false
                'ipv6Gateway' => '198.51.100.1', // IPv4 rejected by v6 rule
                'deliveryMethod' => 'ship',
                'isCorporate' => 'true',
            ]);
            self::fail('Expected Date, Ip, NotIn, and RequiredIf validation errors.');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('handle', $e->errors);
            self::assertArrayHasKey('dispatchDate', $e->errors);
            self::assertArrayHasKey('customDate', $e->errors);
            self::assertArrayHasKey('clientIp', $e->errors);
            self::assertArrayHasKey('ipv6Gateway', $e->errors);
            self::assertArrayHasKey('shippingCity', $e->errors);
            self::assertArrayHasKey('companyVat', $e->errors);
        }

        // Date sibling `after: 'dispatchDate'` rejects arrivalDate <= dispatchDate
        try {
            $validator->validate(ShipmentScheduleDto::class, [
                'handle' => 'kongo',
                'dispatchDate' => '2026-06-20',
                'arrivalDate' => '2026-06-15',
                'customDate' => '15/06/2026',
                'clientIp' => '198.51.100.42',
                'ipv6Gateway' => '2001:db8::1',
                'deliveryMethod' => 'courier',
                'shippingCity' => 'Osaka',
                'isCorporate' => false,
            ]);
            self::fail('Expected Date(after: dispatchDate) to reject earlier arrivalDate.');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('arrivalDate', $e->errors);
            self::assertStringContainsString('after dispatchDate', $e->errors['arrivalDate'][0]);
        }
    }
}

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
final readonly class SlugRule implements RuleInterface
{
    public function validate(mixed $value, string $field, array $allInput): ?string
    {
        if (!is_string($value) || 1 !== preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', $value)) {
            return sprintf('The %s field must be a lowercase slug.', $field);
        }

        return null;
    }
}

final readonly class CampaignInputDto implements ValidatableDtoInterface
{
    public function __construct(
        #[Required, SlugRule]
        public string $slug,
        #[Required, Date]
        public string $startDate,
        #[Required, Date(after: 'startDate')]
        public string $endDate,
        #[Required, Min(1)]
        public int $budget,
        #[Email]
        public ?string $approverEmail = null,
    ) {}

    public function validate(): array
    {
        if ($this->budget > 10_000 && null === $this->approverEmail) {
            return ['approverEmail' => 'Campaigns over 10000 require a finance approver email.'];
        }

        return [];
    }
}

final readonly class CartLineItemDto
{
    public function __construct(
        #[Required, Min(3), Max(32)]
        public string $sku,
        #[Required, Min(1), Max(99)]
        public int $qty,
    ) {}
}

final readonly class CartCheckoutDto
{
    /**
     * @param list<CartLineItemDto> $items
     * @param list<Priority>        $priorities
     * @param list<string>          $notifyEmails
     */
    public function __construct(
        #[Required, Min(1), ArrayOf(CartLineItemDto::class, maxItems: 3)]
        public array $items,
        #[Required, ArrayOf(Priority::class)]
        public array $priorities,
        #[Required, ArrayOf('string', rules: [new Email()])]
        public array $notifyEmails,
    ) {}
}

final readonly class ShipmentScheduleDto
{
    public function __construct(
        #[Required, NotIn(['admin', 'root', 'system'])]
        public string $handle,
        #[Required, Date]
        public string $dispatchDate,
        #[Required, Date(after: 'dispatchDate')]
        public string $arrivalDate,
        #[Required, Date(format: 'd/m/Y')]
        public string $customDate,
        #[Required, Ip(version: 'v4', allowPrivate: false, allowReserved: false)]
        public string $clientIp,
        #[Required, Ip(version: 'v6')]
        public string $ipv6Gateway,
        #[Required, In(['pickup', 'ship', 'courier'])]
        public string $deliveryMethod,
        #[RequiredIf('deliveryMethod', in: ['ship', 'courier'])]
        public ?string $shippingCity = null,
        public bool $isCorporate = false,
        #[RequiredIf('isCorporate', equals: true)]
        public ?string $companyVat = null,
    ) {}
}
