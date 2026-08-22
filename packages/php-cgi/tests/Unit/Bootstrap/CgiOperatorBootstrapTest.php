<?php

declare(strict_types=1);

namespace Kongono\PhpCgi\Tests\Unit\Bootstrap;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DiagnosticActivation\TrustedDeveloperBootstrapPermission;
use Kongono\PhpCgi\Bootstrap\CgiOperatorBootstrap;
use Kongono\PhpCgi\Bootstrap\CgiOperatorProfile;
use Kongono\PhpCgi\Exception\CgiOperatorBootstrapException;
use Kongono\PhpCgi\Internal\CgiOperatorBootstrapValidation;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionEnum;
use ReflectionMethod;

final class CgiOperatorBootstrapTest extends TestCase
{
    public function testProfileShapeStatusCasesAndMappingAreExact(): void
    {
        $reflection = new ReflectionEnum(CgiOperatorProfile::class);

        self::assertNull($reflection->getBackingType());
        self::assertSame(
            ['Production', 'LoopbackDevelopment'],
            array_map(static fn(CgiOperatorProfile $profile): string => $profile->name, CgiOperatorProfile::cases()),
        );
        self::assertSame(ApiStatus::Experimental, $reflection->getAttributes(Api::class)[0]->newInstance()->status);
        self::assertSame(['bootstrapPermission'], $this->declaredPublicMethodNames($reflection));
        $properties = $reflection->getProperties();
        self::assertSame(['name'], array_map(static fn(\ReflectionProperty $property): string => $property->getName(), $properties));
        self::assertTrue($properties[0]->isReadOnly());
        self::assertNull($reflection->getConstructor());
        self::assertFalse($reflection->hasMethod('from'));
        self::assertFalse($reflection->hasMethod('tryFrom'));
        self::assertFalse($reflection->hasMethod('__toString'));
        self::assertSame(
            TrustedDeveloperBootstrapPermission::Denied,
            CgiOperatorProfile::Production->bootstrapPermission(),
        );
        self::assertSame(
            TrustedDeveloperBootstrapPermission::Allowed,
            CgiOperatorProfile::LoopbackDevelopment->bootstrapPermission(),
        );
    }

    public function testBootstrapAndValidatorDeclarationShapesAreExact(): void
    {
        $bootstrap = new ReflectionClass(CgiOperatorBootstrap::class);
        $bootstrapProperties = $bootstrap->getProperties();
        $bootstrapConstructor = $bootstrap->getConstructor();

        self::assertTrue($bootstrap->isFinal());
        self::assertTrue($bootstrap->isReadOnly());
        self::assertSame(ApiStatus::Experimental, $bootstrap->getAttributes(Api::class)[0]->newInstance()->status);
        self::assertCount(1, $bootstrapProperties);
        self::assertSame('bootstrapPermission', $bootstrapProperties[0]->getName());
        self::assertTrue($bootstrapProperties[0]->isPrivate());
        self::assertTrue($bootstrapProperties[0]->isReadOnly());
        self::assertNotNull($bootstrapConstructor);
        self::assertTrue($bootstrapConstructor->isPrivate());
        self::assertSame(['bootstrapPermission', 'load'], $this->declaredPublicMethodNames($bootstrap));

        $validation = new ReflectionClass(CgiOperatorBootstrapValidation::class);
        $validationConstructor = $validation->getConstructor();
        self::assertTrue($validation->isFinal());
        self::assertSame(ApiStatus::Internal, $validation->getAttributes(Api::class)[0]->newInstance()->status);
        self::assertSame([], $validation->getProperties());
        self::assertNotNull($validationConstructor);
        self::assertTrue($validationConstructor->isPrivate());
        self::assertSame(['captureAndValidate', 'validate'], $this->declaredPublicMethodNames($validation));
        foreach ($validation->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($validation->getName() === $method->getDeclaringClass()->getName()) {
                self::assertTrue($method->isStatic());
            }
        }
    }

    public function testEveryAcceptedEmptyOrZeroRepresentationValidates(): void
    {
        CgiOperatorBootstrapValidation::validate('cgi-fcgi', $this->validSnapshot());
        self::addToAssertionCount(1);

        foreach ([
            'enable_post_data_reading',
            'zlib.output_compression',
            'display_errors',
            'display_startup_errors',
            'html_errors',
            'expose_php',
            'cgi.rfc2616_headers',
        ] as $name) {
            $snapshot = $this->validSnapshot();
            $snapshot[$name] = '0';
            CgiOperatorBootstrapValidation::validate('cgi-fcgi', $snapshot);
            self::addToAssertionCount(1);
        }

        $reordered = array_reverse($this->validSnapshot(), true);
        CgiOperatorBootstrapValidation::validate('cgi-fcgi', $reordered);
        self::addToAssertionCount(1);
    }

    public function testSapiAndEveryMalformedSnapshotFailClosedWithoutCoercion(): void
    {
        $this->assertViolation('sapi_mismatch', static fn() => CgiOperatorBootstrapValidation::validate(
            'cli',
            ['output_buffering' => '1'],
        ));

        $missing = $this->validSnapshot();
        unset($missing['output_buffering']);
        $extra = $this->validSnapshot();
        $extra['unapproved'] = '0';
        $nonStringKey = $this->validSnapshot();
        unset($nonStringKey['output_buffering']);
        $nonStringKey[0] = '0';
        $falseValue = $this->validSnapshot();
        $falseValue['display_errors'] = false;
        $integerValue = $this->validSnapshot();
        $integerValue['display_errors'] = 0;

        foreach ([$missing, $extra, $nonStringKey, $falseValue, $integerValue] as $snapshot) {
            $this->assertViolation(
                'ini_snapshot_invalid',
                static fn() => CgiOperatorBootstrapValidation::validate('cgi-fcgi', $snapshot),
            );
        }

        foreach (['off', 'false', ' ', '00'] as $rawValue) {
            $snapshot = $this->validSnapshot();
            $snapshot['display_errors'] = $rawValue;
            $this->assertViolation(
                'display_errors_enabled',
                static fn() => CgiOperatorBootstrapValidation::validate('cgi-fcgi', $snapshot),
            );
        }
    }

    public function testEachDirectiveUsesItsExactFirstFailureToken(): void
    {
        foreach ([
            'enable_post_data_reading' => ['1', 'enable_post_data_reading_enabled'],
            'output_buffering' => ['', 'output_buffering_enabled'],
            'zlib.output_compression' => ['1', 'zlib_output_compression_enabled'],
            'display_errors' => ['1', 'display_errors_enabled'],
            'display_startup_errors' => ['1', 'display_startup_errors_enabled'],
            'html_errors' => ['1', 'html_errors_enabled'],
            'expose_php' => ['1', 'expose_php_enabled'],
            'auto_prepend_file' => ['raw-prepend-sentinel', 'auto_prepend_file_configured'],
            'auto_append_file' => ['raw-append-sentinel', 'auto_append_file_configured'],
            'cgi.rfc2616_headers' => ['1', 'cgi_rfc2616_headers_enabled'],
        ] as $name => [$invalidValue, $token]) {
            $snapshot = $this->validSnapshot();
            $snapshot[$name] = $invalidValue;
            $this->assertViolation(
                $token,
                static fn() => CgiOperatorBootstrapValidation::validate('cgi-fcgi', $snapshot),
            );
        }
    }

    public function testNativeLoadFailsUnderCliAndCallsRemainIsolated(): void
    {
        self::assertSame('cli', PHP_SAPI);

        foreach ([CgiOperatorProfile::Production, CgiOperatorProfile::LoopbackDevelopment] as $profile) {
            $this->assertViolation(
                'sapi_mismatch',
                static fn(): CgiOperatorBootstrap => CgiOperatorBootstrap::load($profile),
            );
        }

        CgiOperatorBootstrapValidation::validate('cgi-fcgi', $this->validSnapshot());
        $invalid = $this->validSnapshot();
        $invalid['output_buffering'] = '1';
        $this->assertViolation(
            'output_buffering_enabled',
            static fn() => CgiOperatorBootstrapValidation::validate('cgi-fcgi', $invalid),
        );
        CgiOperatorBootstrapValidation::validate('cgi-fcgi', $this->validSnapshot());

        self::assertSame(TrustedDeveloperBootstrapPermission::Denied, CgiOperatorProfile::Production->bootstrapPermission());
        self::assertSame(TrustedDeveloperBootstrapPermission::Allowed, CgiOperatorProfile::LoopbackDevelopment->bootstrapPermission());
        self::assertSame(TrustedDeveloperBootstrapPermission::Denied, CgiOperatorProfile::Production->bootstrapPermission());
    }

    /** @return array<string, string> */
    private function validSnapshot(): array
    {
        return [
            'enable_post_data_reading' => '',
            'output_buffering' => '0',
            'zlib.output_compression' => '',
            'display_errors' => '',
            'display_startup_errors' => '',
            'html_errors' => '',
            'expose_php' => '',
            'auto_prepend_file' => '',
            'auto_append_file' => '',
            'cgi.rfc2616_headers' => '',
        ];
    }

    /** @param callable(): mixed $operation */
    private function assertViolation(string $expected, callable $operation): void
    {
        try {
            $operation();
            self::fail('Expected PHP CGI bootstrap validation to fail closed.');
        } catch (CgiOperatorBootstrapException $exception) {
            self::assertSame($expected, $exception->diagnosticContext()->fields()[0]->value());
        }
    }

    /**
     * @param ReflectionClass<covariant object> $reflection
     * @return list<string>
     */
    private function declaredPublicMethodNames(ReflectionClass $reflection): array
    {
        $names = array_map(
            static fn(ReflectionMethod $method): string => $method->getName(),
            array_filter(
                $reflection->getMethods(ReflectionMethod::IS_PUBLIC),
                static fn(ReflectionMethod $method): bool => $reflection->getName() === $method->getDeclaringClass()->getName()
                    && 'cases' !== $method->getName(),
            ),
        );
        sort($names);

        return $names;
    }
}
