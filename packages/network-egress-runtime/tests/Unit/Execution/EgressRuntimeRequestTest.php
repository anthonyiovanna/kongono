<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressRuntime\Tests\Unit\Execution;

use Kongono\NetworkEgressRuntime\Tests\Fixture\FakeAuthorityCurrentness;
use Kongono\NetworkEgressRuntime\Tests\Fixture\FakeCancellation;
use Kongono\NetworkEgressRuntime\Tests\Fixture\FakeResolver;
use Kongono\NetworkEgressRuntime\Tests\Fixture\FakeTransport;
use Kongono\NetworkEgressRuntime\Tests\Fixture\RuntimeFixture;
use PHPUnit\Framework\TestCase;

final class EgressRuntimeRequestTest extends TestCase
{
    public function testConstructionBindsValuesWithoutCallingCollaborators(): void
    {
        $authority = new FakeAuthorityCurrentness();
        $resolver = new FakeResolver(RuntimeFixture::resolution());
        $transport = new FakeTransport([RuntimeFixture::capabilities()], RuntimeFixture::session());
        $cancellation = new FakeCancellation();
        $request = RuntimeFixture::request($authority, $resolver, $transport, $cancellation);
        self::assertSame(0, $authority->calls);
        self::assertSame(0, $resolver->calls);
        self::assertSame(0, $transport->capabilityCalls);
        self::assertSame(0, $transport->openCalls);
        self::assertSame(0, $cancellation->calls);
        self::assertNotSame('', $request->policyArtifactBytes());
        self::assertSame($authority, $request->authorityCurrentness());
        self::assertSame($resolver, $request->resolver());
        self::assertSame($transport, $request->transport());
    }
}
