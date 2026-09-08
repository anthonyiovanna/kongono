<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressPolicyBuild\Tests\Unit\Policy;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressPolicyBuild\Policy\AddressScope;
use Kongono\NetworkEgressPolicyBuild\Policy\CookieMode;
use Kongono\NetworkEgressPolicyBuild\Policy\CredentialMode;
use Kongono\NetworkEgressPolicyBuild\Policy\ProxyMode;
use Kongono\NetworkEgressPolicyBuild\Policy\RedirectMode;
use Kongono\NetworkEgressPolicyBuild\Policy\TransportSecurity;
use Kongono\NetworkEgressPolicyBuild\Version\EgressPolicyArtifactVersion;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class PolicyModeTest extends TestCase
{
    public function testClosedValuesAndApiStatusAreExact(): void
    {
        self::assertSame(['public_only', 'explicit_private_allowed'], array_column(AddressScope::cases(), 'value'));
        self::assertSame(['https_only', 'explicit_http_allowed'], array_column(TransportSecurity::cases(), 'value'));
        self::assertSame(['deny', 'same_origin'], array_column(RedirectMode::cases(), 'value'));
        self::assertSame(['deny', 'explicit_only'], array_column(ProxyMode::cases(), 'value'));
        self::assertSame(['deny', 'explicit_only'], array_column(CookieMode::cases(), 'value'));
        self::assertSame(['none', 'operation_scoped'], array_column(CredentialMode::cases(), 'value'));
        self::assertSame([1], array_column(EgressPolicyArtifactVersion::cases(), 'value'));
        foreach ([AddressScope::class, TransportSecurity::class, RedirectMode::class, ProxyMode::class, CookieMode::class, CredentialMode::class, EgressPolicyArtifactVersion::class] as $class) {
            $attributes = (new ReflectionClass($class))->getAttributes(Api::class);
            self::assertCount(1, $attributes);
            self::assertSame(ApiStatus::Experimental, $attributes[0]->newInstance()->status);
        }
    }
}
