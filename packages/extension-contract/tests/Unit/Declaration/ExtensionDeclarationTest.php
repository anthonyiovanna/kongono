<?php

declare(strict_types=1);

namespace Kongono\ExtensionContract\Tests\Unit\Declaration;

use Kongono\ExtensionContract\Capability\CapabilityIdentity;
use Kongono\ExtensionContract\Capability\CapabilityPurpose;
use Kongono\ExtensionContract\Capability\CapabilityRequest;
use Kongono\ExtensionContract\Capability\CapabilityRequirement;
use Kongono\ExtensionContract\ContractVersion;
use Kongono\ExtensionContract\Contribution\ContributionDeclaration;
use Kongono\ExtensionContract\Contribution\ContributionIdentity;
use Kongono\ExtensionContract\Contribution\ContributionSchema;
use Kongono\ExtensionContract\Contribution\ContributionVisibility;
use Kongono\ExtensionContract\Declaration\ExtensionDeclaration;
use Kongono\ExtensionContract\Exception\InvalidExtensionContractException;
use Kongono\ExtensionContract\ExtensionCategory;
use Kongono\ExtensionContract\Identity\ExtensionIdentity;
use Kongono\ExtensionContract\Lifecycle\ExtensionLifecycle;
use Kongono\ExtensionContract\Provenance\ExtensionProvenance;
use Kongono\ExtensionContract\Provenance\ExtensionSourceIdentity;
use Kongono\ExtensionContract\Version\ExtensionVersion;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class ExtensionDeclarationTest extends TestCase
{
    public function testZeroAndMaximumListsAreAcceptedDetachedAndIdentityPreserving(): void
    {
        $empty = self::declaration([], []);
        self::assertSame([], $empty->capabilityRequests());
        self::assertSame([], $empty->contributions());

        $requests = [];
        for ($index = 0; $index < 64; ++$index) {
            $requests[] = self::request(sprintf('cap%03d', $index));
        }
        $contributions = [];
        for ($index = 0; $index < 128; ++$index) {
            $contributions[] = self::contribution(sprintf('item%03d', $index));
        }

        $declaration = self::declaration($requests, $contributions);
        $requests[] = self::request('z');
        $contributions = [];
        $returnedRequests = $declaration->capabilityRequests();
        $returnedContributions = $declaration->contributions();
        array_pop($returnedRequests);
        array_pop($returnedContributions);

        self::assertCount(64, $declaration->capabilityRequests());
        self::assertCount(128, $declaration->contributions());
        self::assertSame($declaration->capabilityRequests()[0], $requests[0]);
        self::assertNotSame($returnedRequests, $declaration->capabilityRequests());
        self::assertNotSame($returnedContributions, $declaration->contributions());
        self::assertTrue((new ReflectionClass($declaration))->isReadOnly());
    }

    public function testOverLimitNonListWrongTypeDuplicateAndNoncanonicalListsAreRejected(): void
    {
        $cases = [
            fn(): ExtensionDeclaration => self::declaration(array_fill(0, 65, self::request('a')), []),
            fn(): ExtensionDeclaration => self::declaration([], array_fill(0, 129, self::contribution('a'))),
            fn(): ExtensionDeclaration => self::declaration(['named' => self::request('a')], []),
            fn(): ExtensionDeclaration => self::declaration([], ['named' => self::contribution('a')]),
            fn(): ExtensionDeclaration => self::declaration(['wrong'], []),
            fn(): ExtensionDeclaration => self::declaration([], ['wrong']),
            fn(): ExtensionDeclaration => self::declaration([self::request('a'), self::request('a')], []),
            fn(): ExtensionDeclaration => self::declaration([self::request('b'), self::request('a')], []),
            fn(): ExtensionDeclaration => self::declaration([], [self::contribution('a'), self::contribution('a', 'other')]),
            fn(): ExtensionDeclaration => self::declaration([], [self::contribution('b'), self::contribution('a')]),
        ];

        foreach ($cases as $case) {
            try {
                $case();
                self::fail('Malformed declaration was accepted.');
            } catch (InvalidExtensionContractException $exception) {
                self::assertContains($exception->diagnosticId(), ['KGN-EXTENSION-1206', 'KGN-EXTENSION-1207']);
            }
        }
    }

    public function testAggregateAccessorsPreserveExactScalarAndObjectInputs(): void
    {
        $declaration = self::declaration([self::request('a')], [self::contribution('a')]);
        self::assertSame(ContractVersion::Version1, $declaration->contractVersion());
        self::assertSame('example/extension', $declaration->identity()->value());
        self::assertSame('v1', $declaration->version()->value());
        self::assertSame(ExtensionCategory::External, $declaration->category());
        self::assertSame(ExtensionLifecycle::Operation, $declaration->lifecycle());
        self::assertSame(str_repeat('1', 64), $declaration->provenance()->sourceIdentity()->value());
    }

    /**
     * @param array<array-key, CapabilityRequest|string> $requests
     * @param array<array-key, ContributionDeclaration|string> $contributions
     */
    private static function declaration(array $requests, array $contributions): ExtensionDeclaration
    {
        $declaration = (new ReflectionClass(ExtensionDeclaration::class))->newInstanceArgs([
            ContractVersion::Version1,
            new ExtensionIdentity('example/extension'),
            new ExtensionVersion('v1'),
            ExtensionCategory::External,
            new ExtensionProvenance(new ExtensionSourceIdentity(str_repeat('1', 64)), str_repeat('2', 64)),
            ExtensionLifecycle::Operation,
            $requests,
            $contributions,
        ]);
        self::assertInstanceOf(ExtensionDeclaration::class, $declaration);

        return $declaration;
    }

    private static function request(string $name): CapabilityRequest
    {
        return new CapabilityRequest(new CapabilityIdentity($name, 1), new CapabilityPurpose('purpose'), CapabilityRequirement::Optional);
    }

    private static function contribution(string $identity, string $schema = 'schema'): ContributionDeclaration
    {
        return new ContributionDeclaration(new ContributionIdentity($identity), new ContributionSchema($schema, 1), ContributionVisibility::Public, str_repeat('a', 64));
    }
}
