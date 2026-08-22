<?php

declare(strict_types=1);

namespace Kongono\PhpCgi\Tests\Unit\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DiagnosticActivation\AdapterControlledDeliveryPermission;
use Kongono\PhpCgi\Internal\CgiTrustedPresentationPermissionEvaluator;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

final class CgiTrustedPresentationPermissionEvaluatorTest extends TestCase
{
    public function testDeclarationShapeAndStatusAreExact(): void
    {
        $reflection = new ReflectionClass(CgiTrustedPresentationPermissionEvaluator::class);
        $constructor = $reflection->getConstructor();
        $methods = array_values(array_filter(
            $reflection->getMethods(ReflectionMethod::IS_PUBLIC),
            static fn(ReflectionMethod $method): bool => $reflection->getName() === $method->getDeclaringClass()->getName(),
        ));

        self::assertTrue($reflection->isFinal());
        self::assertSame(ApiStatus::Internal, $reflection->getAttributes(Api::class)[0]->newInstance()->status);
        self::assertSame([], $reflection->getProperties());
        self::assertNotNull($constructor);
        self::assertTrue($constructor->isPrivate());
        self::assertCount(1, $methods);
        self::assertSame('evaluate', $methods[0]->getName());
        self::assertTrue($methods[0]->isStatic());
    }

    public function testExactCanonicalIpv4AndIpv6LoopbackAreAllowed(): void
    {
        foreach (['127.0.0.1', '::1'] as $remoteAddress) {
            $server = $this->validServer();
            $server['REMOTE_ADDR'] = $remoteAddress;
            self::assertSame(
                AdapterControlledDeliveryPermission::Allowed,
                CgiTrustedPresentationPermissionEvaluator::evaluate('cgi-fcgi', $server),
            );
        }
    }

    public function testEveryRequiredEvidenceComponentFailsClosedWhenMissingNonStringOrWrong(): void
    {
        foreach ([
            'GATEWAY_INTERFACE' => 'CGI/1.0',
            'FCGI_ROLE' => 'AUTHORIZER',
            'REMOTE_ADDR' => '127.0.0.2',
        ] as $name => $wrong) {
            $missing = $this->validServer();
            unset($missing[$name]);
            self::assertSame(AdapterControlledDeliveryPermission::Denied, CgiTrustedPresentationPermissionEvaluator::evaluate('cgi-fcgi', $missing));

            $nonString = $this->validServer();
            $nonString[$name] = 1;
            self::assertSame(AdapterControlledDeliveryPermission::Denied, CgiTrustedPresentationPermissionEvaluator::evaluate('cgi-fcgi', $nonString));

            $wrongValue = $this->validServer();
            $wrongValue[$name] = $wrong;
            self::assertSame(AdapterControlledDeliveryPermission::Denied, CgiTrustedPresentationPermissionEvaluator::evaluate('cgi-fcgi', $wrongValue));
        }

        self::assertSame(AdapterControlledDeliveryPermission::Denied, CgiTrustedPresentationPermissionEvaluator::evaluate('cli', $this->validServer()));
    }

    public function testNoncanonicalAndNonloopbackAddressesAreDenied(): void
    {
        foreach ([
            '8.8.8.8', '10.0.0.1', '169.254.1.1', '0:0:0:0:0:0:0:1',
            '::ffff:127.0.0.1', ' 127.0.0.1', 'localhost', '127.0.0.1 ',
        ] as $remoteAddress) {
            $server = $this->validServer();
            $server['REMOTE_ADDR'] = $remoteAddress;
            self::assertSame(
                AdapterControlledDeliveryPermission::Denied,
                CgiTrustedPresentationPermissionEvaluator::evaluate('cgi-fcgi', $server),
            );
        }
    }

    public function testEveryForwardingMarkerDeniesRegardlessOfValue(): void
    {
        foreach ([
            'HTTP_FORWARDED' => '',
            'HTTP_X_FORWARDED' => 'raw-forwarded-sentinel',
            'HTTP_X_FORWARDED_FOR' => null,
            'HTTP_X_FORWARDED_HOST' => 123,
            'HTTP_X_FORWARDED_PROTO' => ['https'],
        ] as $name => $value) {
            $server = $this->validServer();
            $server[$name] = $value;
            self::assertSame(
                AdapterControlledDeliveryPermission::Denied,
                CgiTrustedPresentationPermissionEvaluator::evaluate('cgi-fcgi', $server),
            );
        }
    }

    public function testHeterogeneousUnrelatedEvidenceIsTotalIsolatedAndOrderIndependent(): void
    {
        $withNumericKey = $this->validServer();
        $withNumericKey[0] = 'unrelated';
        self::assertSame(
            AdapterControlledDeliveryPermission::Denied,
            CgiTrustedPresentationPermissionEvaluator::evaluate('cgi-fcgi', $withNumericKey),
        );

        $unrelated = $this->validServer();
        $unrelated['HTTP_USER_AGENT'] = new \stdClass();
        $unrelated['UNRELATED'] = ['raw-unrelated-sentinel'];
        self::assertSame(
            AdapterControlledDeliveryPermission::Allowed,
            CgiTrustedPresentationPermissionEvaluator::evaluate('cgi-fcgi', $unrelated),
        );

        $denied = $this->validServer();
        $denied['REMOTE_ADDR'] = '192.168.1.10';
        self::assertSame(AdapterControlledDeliveryPermission::Allowed, CgiTrustedPresentationPermissionEvaluator::evaluate('cgi-fcgi', $this->validServer()));
        self::assertSame(AdapterControlledDeliveryPermission::Denied, CgiTrustedPresentationPermissionEvaluator::evaluate('cgi-fcgi', $denied));
        self::assertSame(AdapterControlledDeliveryPermission::Allowed, CgiTrustedPresentationPermissionEvaluator::evaluate('cgi-fcgi', array_reverse($this->validServer(), true)));
    }

    /** @return array<string, string> */
    private function validServer(): array
    {
        return [
            'GATEWAY_INTERFACE' => 'CGI/1.1',
            'FCGI_ROLE' => 'RESPONDER',
            'REMOTE_ADDR' => '127.0.0.1',
        ];
    }
}
