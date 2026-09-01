<?php

declare(strict_types=1);

namespace Kongono\AgentDeclarationBuild\Tests\Unit\Declaration;

use Kongono\AgentDeclarationBuild\Declaration\AgentDeclaration;
use Kongono\AgentDeclarationBuild\Declaration\AgentDeclarationIdentity;
use Kongono\AgentDeclarationBuild\Exception\InvalidAgentDeclarationException;
use Kongono\AgentDeclarationBuild\Tests\Support\AgentDeclarationFactory;
use Kongono\AgentPrincipalContract\Capability\CapabilityIdentity;
use Kongono\AgentPrincipalContract\Capability\CapabilityPurpose;
use Kongono\AgentPrincipalContract\Capability\CapabilityRequest;
use Kongono\AgentPrincipalContract\Capability\CapabilityRequirement;
use Kongono\AgentPrincipalContract\Constraint\AgentConstraint;
use Kongono\AgentPrincipalContract\Constraint\ConstraintKind;
use Kongono\AgentPrincipalContract\Evidence\EvidenceIdentity;
use Kongono\AgentPrincipalContract\Evidence\EvidenceKind;
use Kongono\AgentPrincipalContract\Evidence\EvidenceReference;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class AgentDeclarationTest extends TestCase
{
    #[DataProvider('invalidIdentityProvider')]
    public function testIdentityUsesTheExactGrammar(string $value): void
    {
        $this->expectException(InvalidAgentDeclarationException::class);
        new AgentDeclarationIdentity($value);
    }

    /** @return iterable<string, array{string}> */
    public static function invalidIdentityProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'uppercase' => ['Agent'];
        yield 'leading separator' => ['-agent'];
        yield 'double separator' => ['agent..one'];
        yield 'trailing separator' => ['agent-'];
        yield 'oversize' => [str_repeat('a', 129)];
    }

    public function testIdentityEqualityAndDetachedListGetters(): void
    {
        $declaration = AgentDeclarationFactory::create();
        self::assertTrue($declaration->identity()->equals(new AgentDeclarationIdentity('assistant.primary')));
        $requests = $declaration->capabilityRequests();
        array_pop($requests);
        self::assertCount(2, $declaration->capabilityRequests());
        self::assertCount(2, $declaration->constraints());
        self::assertCount(2, $declaration->evidenceReferences());
    }

    public function testNonListWrongTypeDuplicateAndOrderFailClosed(): void
    {
        $base = AgentDeclarationFactory::create();
        $request = new CapabilityRequest(new CapabilityIdentity('calendar.read'), new CapabilityPurpose('Purpose'), CapabilityRequirement::Required);
        foreach ([[1 => $request], ['wrong'], [$request, $request]] as $values) {
            try {
                (new ReflectionClass(AgentDeclaration::class))->newInstanceArgs([
                    $base->identity(), $base->principal(), $values, [], [], $base->provenance(), $base->visibility(),
                ]);
                self::fail('Expected invalid declaration input.');
            } catch (InvalidAgentDeclarationException $failure) {
                self::assertContains($failure->diagnosticId(), ['KGN-AGENT-DECLARATION-1310', 'KGN-AGENT-DECLARATION-1314']);
            }
        }

        $late = new CapabilityRequest(new CapabilityIdentity('z.last'), new CapabilityPurpose('Purpose'), CapabilityRequirement::Required);
        $early = new CapabilityRequest(new CapabilityIdentity('a.first'), new CapabilityPurpose('Purpose'), CapabilityRequirement::Required);
        $this->expectException(InvalidAgentDeclarationException::class);
        new AgentDeclaration($base->identity(), $base->principal(), [$late, $early], [], [], $base->provenance(), $base->visibility());
    }

    public function testCapabilityBoundFailsWithLimitDiagnostic(): void
    {
        $base = AgentDeclarationFactory::create();
        $requests = [];
        for ($index = 0; $index < 33; ++$index) {
            $requests[] = new CapabilityRequest(
                new CapabilityIdentity(sprintf('capability.%02d', $index)),
                new CapabilityPurpose('Purpose'),
                CapabilityRequirement::Required,
            );
        }

        try {
            new AgentDeclaration($base->identity(), $base->principal(), $requests, [], [], $base->provenance(), $base->visibility());
            self::fail('Expected the list bound to fail.');
        } catch (InvalidAgentDeclarationException $failure) {
            self::assertSame('KGN-AGENT-DECLARATION-1314', $failure->diagnosticId());
            self::assertSame(33, $failure->diagnosticContext()->fields()[1]->value());
        }
    }

    public function testConstraintAndEvidenceListShapeTypeDuplicateOrderAndBoundsFailClosed(): void
    {
        $base = AgentDeclarationFactory::create();
        $step = new AgentConstraint(ConstraintKind::StepLimit, 1);
        $token = new AgentConstraint(ConstraintKind::TokenLimit, 2);
        foreach ([[1 => $step], ['wrong'], [$step, $step], [$token, $step], array_fill(0, 10, $step)] as $constraints) {
            $this->assertInvalidLists($base, $base->capabilityRequests(), $constraints, $base->evidenceReferences());
        }

        $authentication = new EvidenceReference(
            EvidenceKind::Authentication,
            new EvidenceIdentity('authentication.a'),
            str_repeat('a', 64),
        );
        $policy = new EvidenceReference(EvidenceKind::Policy, new EvidenceIdentity('policy.a'), str_repeat('b', 64));
        foreach ([[1 => $authentication], ['wrong'], [$authentication, $authentication], [$policy, $authentication], array_fill(0, 33, $authentication)] as $evidence) {
            $this->assertInvalidLists($base, $base->capabilityRequests(), $base->constraints(), $evidence);
        }
    }

    /**
     * @param array<array-key, mixed> $capabilityRequests
     * @param array<array-key, mixed> $constraints
     * @param array<array-key, mixed> $evidenceReferences
     */
    private function assertInvalidLists(
        AgentDeclaration $base,
        array $capabilityRequests,
        array $constraints,
        array $evidenceReferences,
    ): void {
        try {
            (new ReflectionClass(AgentDeclaration::class))->newInstanceArgs([
                $base->identity(),
                $base->principal(),
                $capabilityRequests,
                $constraints,
                $evidenceReferences,
                $base->provenance(),
                $base->visibility(),
            ]);
            self::fail('Expected invalid declaration lists.');
        } catch (InvalidAgentDeclarationException $failure) {
            self::assertContains($failure->diagnosticId(), ['KGN-AGENT-DECLARATION-1310', 'KGN-AGENT-DECLARATION-1314']);
        }
    }
}
