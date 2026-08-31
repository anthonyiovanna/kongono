<?php

declare(strict_types=1);

namespace Kongono\ExtensionContract\Tests\Unit\Contribution;

use Kongono\ExtensionContract\Contribution\ContributionDeclaration;
use Kongono\ExtensionContract\Contribution\ContributionIdentity;
use Kongono\ExtensionContract\Contribution\ContributionSchema;
use Kongono\ExtensionContract\Contribution\ContributionVisibility;
use Kongono\ExtensionContract\Exception\InvalidExtensionContractException;
use PHPUnit\Framework\TestCase;

final class ContributionDeclarationTest extends TestCase
{
    public function testDeclarationPreservesMetadataWithoutPayloadOrGraphFact(): void
    {
        $identity = new ContributionIdentity('route.catalog');
        $schema = new ContributionSchema('kongono.route', 65535);
        $digest = str_repeat('a', 64);
        $declaration = new ContributionDeclaration($identity, $schema, ContributionVisibility::Application, $digest);

        self::assertSame($identity, $declaration->identity());
        self::assertSame($schema, $declaration->schema());
        self::assertSame(ContributionVisibility::Application, $declaration->visibility());
        self::assertSame($digest, $declaration->sourceSha256());
        self::assertTrue($identity->equals(new ContributionIdentity('route.catalog')));
        self::assertTrue($schema->equals(new ContributionSchema('kongono.route', 65535)));
        self::assertSame([
            ['Public', 'public'],
            ['Application', 'application'],
            ['FrameworkInternal', 'framework_internal'],
        ], array_map(static fn(ContributionVisibility $case): array => [$case->name, $case->value], ContributionVisibility::cases()));
    }

    public function testMalformedFieldsAndDigestFailWithoutDisclosure(): void
    {
        foreach (['', 'A', 'a..b', str_repeat('x', 129)] as $value) {
            try {
                new ContributionIdentity($value);
                self::fail('Malformed contribution identity was accepted.');
            } catch (InvalidExtensionContractException $exception) {
                self::assertSame('KGN-EXTENSION-1201', $exception->diagnosticId());
            }
        }

        try {
            new ContributionDeclaration(new ContributionIdentity('a'), new ContributionSchema('schema', 1), ContributionVisibility::Public, 'C:\\private\\token.txt');
            self::fail('Malformed digest was accepted.');
        } catch (InvalidExtensionContractException $exception) {
            self::assertSame('KGN-EXTENSION-1205', $exception->diagnosticId());
            self::assertStringNotContainsString('C:\\private\\token.txt', (string) $exception);
        }
    }
}
