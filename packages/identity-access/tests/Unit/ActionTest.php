<?php

declare(strict_types=1);

namespace Kongono\IdentityAccess\Tests\Unit;

use ArrayAccess;
use JsonSerializable;
use Kongono\IdentityAccess\Action;
use Kongono\IdentityAccess\Exception\InvalidIdentityAccessValueException;
use Kongono\IdentityAccess\ResourceType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Stringable;

final class ActionTest extends TestCase
{
    #[DataProvider('validCanonicalNames')]
    public function testActionAndResourceTypeAcceptExactCanonicalNames(string $value): void
    {
        $action = new Action($value);
        $resourceType = new ResourceType($value);

        self::assertSame($value, $action->value());
        self::assertSame($value, $resourceType->value());
        self::assertTrue($action->equals(new Action($value)));
        self::assertTrue($resourceType->equals(new ResourceType($value)));
        $differentValue = str_repeat('z', strlen($value));
        self::assertFalse($action->equals(new Action($differentValue)));
        self::assertFalse($resourceType->equals(new ResourceType($differentValue)));
    }

    /** @return iterable<string, array{string}> */
    public static function validCanonicalNames(): iterable
    {
        yield 'minimum' => ['a'];
        yield 'digits' => ['record2'];
        yield 'all separators' => ['billing.invoice_read:own-item'];
        yield 'maximum' => [str_repeat('a', 128)];
    }

    #[DataProvider('invalidCanonicalNames')]
    public function testInvalidCanonicalNamesAreRejectedWithoutDisclosure(string $value): void
    {
        foreach ([Action::class, ResourceType::class] as $class) {
            try {
                new $class($value);
                self::fail('Invalid canonical name was accepted.');
            } catch (InvalidIdentityAccessValueException $exception) {
                self::assertInstanceOf(InvalidIdentityAccessValueException::class, $exception);
                if ('' !== $value) {
                    self::assertStringNotContainsString($value, $exception->getMessage());
                    self::assertStringNotContainsString($value, (string) $exception);
                }
            }
        }
    }

    /** @return iterable<string, array{string}> */
    public static function invalidCanonicalNames(): iterable
    {
        yield 'empty' => [''];
        yield 'uppercase' => ['Invoice.Read'];
        yield 'leading digit' => ['7invoice'];
        yield 'whitespace' => ['invoice read'];
        yield 'non ascii' => ['invoïce'];
        yield 'leading separator' => [':invoice'];
        yield 'repeated separator' => ['invoice::read'];
        yield 'mixed repeated separators' => ['invoice.-read'];
        yield 'trailing separator' => ['invoice:'];
        yield 'control' => ["invoice\nread"];
        yield 'over bound' => [str_repeat('a', 129)];
    }

    public function testCanonicalTypesExposeOnlyExactValueAndEqualityApi(): void
    {
        foreach ([Action::class, ResourceType::class] as $class) {
            $reflection = new ReflectionClass($class);
            self::assertTrue($reflection->isFinal());
            self::assertTrue($reflection->isReadOnly());
            self::assertSame(['__construct', 'equals', 'value'], $this->declaredPublicMethods($reflection));
            self::assertFalse($reflection->implementsInterface(Stringable::class));
            self::assertFalse($reflection->implementsInterface(JsonSerializable::class));
            self::assertFalse($reflection->implementsInterface(ArrayAccess::class));
            foreach (['__toString', '__serialize', '__unserialize', '__debugInfo', '__get', '__set'] as $method) {
                self::assertFalse($reflection->hasMethod($method));
            }
        }
    }

    /**
     * @template T of object
     * @param ReflectionClass<T> $reflection
     * @return list<string>
     */
    private function declaredPublicMethods(ReflectionClass $reflection): array
    {
        $methods = [];
        foreach ($reflection->getMethods() as $method) {
            if ($method->getDeclaringClass()->getName() === $reflection->getName() && $method->isPublic()) {
                $methods[] = $method->getName();
            }
        }
        sort($methods);

        return $methods;
    }
}
