<?php

declare(strict_types=1);

namespace Kongono\AgentApplication\Tests\Unit\Profile;

use Kongono\AgentApplication\Exception\InvalidAgentApplicationProfileException;
use Kongono\AgentApplication\Profile\AgentApplicationProfileFactory;
use Kongono\AgentApplication\Tests\Support\AgentApplicationFactory;
use Kongono\AgentDeclarationBuild\Artifact\AgentDeclarationArtifactBuilder;
use Kongono\AgentRuntime\Tests\Support\AgentRuntimeFactory;
use PHPUnit\Framework\TestCase;

final class AgentApplicationProfileTest extends TestCase
{
    public function testIdenticalExactInputsYieldIdenticalProfileIdentity(): void
    {
        $declaration = AgentApplicationFactory::declaration();
        $bytes = (new AgentDeclarationArtifactBuilder())->build($declaration)->canonicalBytes();
        $plan = AgentRuntimeFactory::plan($declaration);
        $factory = new AgentApplicationProfileFactory();

        $first = $factory->create($declaration, $bytes, $plan);
        $second = $factory->create($declaration, $bytes, $plan);

        self::assertTrue($first->identity()->equals($second->identity()));
        self::assertSame($bytes, $first->declarationArtifact()->canonicalBytes());
        self::assertSame(['calendar.read'], array_map(static fn($identity): string => $identity->value(), $first->permittedCapabilityIdentities()));
    }

    public function testWrongDeclarationArtifactOrPlanFailsClosed(): void
    {
        $declaration = AgentApplicationFactory::declaration();
        $other = AgentApplicationFactory::declaration('calendar.write');
        $factory = new AgentApplicationProfileFactory();

        try {
            $factory->create($declaration, (new AgentDeclarationArtifactBuilder())->build($other)->canonicalBytes(), AgentRuntimeFactory::plan($declaration));
            self::fail('Mismatched artifact must fail.');
        } catch (InvalidAgentApplicationProfileException $failure) {
            self::assertSame('KGN-AGENT-APPLICATION-1340', $failure->diagnosticId());
        }

        $this->expectException(InvalidAgentApplicationProfileException::class);
        $factory->create($declaration, (new AgentDeclarationArtifactBuilder())->build($declaration)->canonicalBytes(), AgentRuntimeFactory::plan($other));
    }
}
