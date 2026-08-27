<?php

declare(strict_types=1);

namespace Kongono\Authorization\Tests\Unit;

use Kongono\Authorization\AuthorizationPolicyName;
use Kongono\Authorization\AuthorizationPolicyProvenance;
use Kongono\Authorization\AuthorizationPolicyReference;
use Kongono\Authorization\AuthorizationPolicyVersion;
use Kongono\Authorization\Exception\InvalidAuthorizationValueException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AuthorizationPolicyReferenceTest extends TestCase
{
    /** @return iterable<string, array{class-string, string}> */
    public static function invalidValues(): iterable
    {
        foreach (['', 'Upper', 'two words', 'a/', 'a..b', 'é'] as $value) {
            yield 'name-' . bin2hex($value) => [AuthorizationPolicyName::class, $value];
            yield 'version-' . bin2hex($value) => [AuthorizationPolicyVersion::class, $value];
            yield 'provenance-' . bin2hex($value) => [AuthorizationPolicyProvenance::class, $value];
        }

        yield 'name-over-bound' => [AuthorizationPolicyName::class, str_repeat('a', 65)];
        yield 'version-over-bound' => [AuthorizationPolicyVersion::class, str_repeat('a', 129)];
        yield 'provenance-over-bound' => [AuthorizationPolicyProvenance::class, str_repeat('a', 129)];
    }

    #[DataProvider('invalidValues')]
    public function testCanonicalValuesRejectInvalidSyntaxAndBounds(string $type, string $value): void
    {
        $this->expectException(InvalidAuthorizationValueException::class);
        new $type($value);
    }

    public function testCanonicalValuesAcceptExactBoundsAndPreserveBytes(): void
    {
        $name = new AuthorizationPolicyName(str_repeat('a', 64));
        $version = new AuthorizationPolicyVersion(str_repeat('b', 128));
        $provenance = new AuthorizationPolicyProvenance(str_repeat('c', 128));

        self::assertSame(str_repeat('a', 64), $name->value());
        self::assertSame(str_repeat('b', 128), $version->value());
        self::assertSame(str_repeat('c', 128), $provenance->value());
        self::assertTrue((new AuthorizationPolicyName('policy.name:v1'))->equals(new AuthorizationPolicyName('policy.name:v1')));
    }

    public function testReferenceEqualityRequiresAllThreeExactFields(): void
    {
        $reference = $this->reference('policy', 'v1', 'source.one');

        self::assertTrue($reference->equals($this->reference('policy', 'v1', 'source.one')));
        self::assertFalse($reference->equals($this->reference('other', 'v1', 'source.one')));
        self::assertFalse($reference->equals($this->reference('policy', 'v2', 'source.one')));
        self::assertFalse($reference->equals($this->reference('policy', 'v1', 'source.two')));
        self::assertSame('policy', $reference->name()->value());
        self::assertSame('v1', $reference->version()->value());
        self::assertSame('source.one', $reference->provenance()->value());
    }

    private function reference(string $name, string $version, string $provenance): AuthorizationPolicyReference
    {
        return new AuthorizationPolicyReference(
            new AuthorizationPolicyName($name),
            new AuthorizationPolicyVersion($version),
            new AuthorizationPolicyProvenance($provenance),
        );
    }
}
