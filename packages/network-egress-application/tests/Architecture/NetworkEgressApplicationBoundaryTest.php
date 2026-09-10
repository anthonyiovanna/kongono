<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressApplication\Tests\Architecture;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressApplication\Diagnostic\NetworkEgressApplicationDiagnostic;
use Kongono\NetworkEgressApplication\Generated\GeneratedNetworkEgressApplicationBoundary;
use Kongono\NetworkEgressApplication\Generated\NetworkEgressWiringValidator;
use Kongono\NetworkEgressApplication\Internal\CanonicalProfile;
use Kongono\NetworkEgressApplication\Internal\GeneratedWiringEmitter;
use Kongono\NetworkEgressApplication\Internal\GeneratedWiringImplementation;
use Kongono\NetworkEgressApplication\Internal\PathGuard;
use Kongono\NetworkEgressApplication\Profile\NetworkEgressApplicationProfile;
use Kongono\NetworkEgressApplication\Runtime\NetworkEgressApplication;
use Kongono\NetworkEgressApplication\Runtime\NetworkEgressApplicationState;
use Kongono\NetworkEgressRuntime\Resolver\EgressResolver;
use Kongono\NetworkEgressRuntime\Transport\EgressTransport;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class NetworkEgressApplicationBoundaryTest extends TestCase
{
    public function testManifestAndClosedInventoriesAreExact(): void
    {
        $root = dirname(__DIR__, 4);
        $package = $root . '/packages/network-egress-application';
        $manifest = json_decode((string) file_get_contents($package . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame([
            'name' => 'kongono/network-egress-application',
            'description' => 'Exact-current single-profile application workflow for Kongono network egress.',
            'type' => 'library',
            'require' => [
                'php' => '>=8.5.0 <8.6.0',
                'kongono/api-metadata' => '*',
                'kongono/diagnostics' => '*',
                'kongono/network-egress-contract' => '*',
                'kongono/network-egress-policy-build' => '*',
                'kongono/network-egress-runtime' => '*',
            ],
            'require-dev' => ['phpunit/phpunit' => '^13.0'],
            'autoload' => ['psr-4' => ['Kongono\\NetworkEgressApplication\\' => 'src/']],
            'autoload-dev' => ['psr-4' => ['Kongono\\NetworkEgressApplication\\Tests\\' => 'tests/']],
        ], $manifest);
        self::assertCount(29, $this->phpFiles($package . '/src'));
        self::assertCount(13, $this->phpFiles($package . '/tests'));
        $consumer = json_decode((string) file_get_contents($root . '/packages/external-service-application/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($consumer);
        $requirements = $consumer['require'] ?? null;
        self::assertIsArray($requirements);
        self::assertSame('*', $requirements['kongono/network-egress-application'] ?? null);

        $production = implode("\n", array_map(static fn(string $path): string => (string) file_get_contents($path), $this->phpFiles($package . '/src')));
        foreach (['NetworkEgressCurl', 'Psr\\Http\\Client', 'gethostbyname(', 'dns_get_record(', 'fsockopen(', 'curl_exec(', 'getenv('] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $production);
        }
    }

    public function testGeneratedBoundaryAndLifecycleShapesAreExact(): void
    {
        $method = (new ReflectionClass(GeneratedNetworkEgressApplicationBoundary::class))->getMethod('start');
        self::assertSame(['profile', 'resolver', 'transport'], array_map(static fn(\ReflectionParameter $parameter): string => $parameter->getName(), $method->getParameters()));
        self::assertSame(NetworkEgressApplicationProfile::class, (string) $method->getParameters()[0]->getType());
        self::assertSame(EgressResolver::class, (string) $method->getParameters()[1]->getType());
        self::assertSame(EgressTransport::class, (string) $method->getParameters()[2]->getType());
        self::assertSame(NetworkEgressApplication::class, (string) $method->getReturnType());
        self::assertSame(['Ready', 'Active', 'Retired'], array_column(NetworkEgressApplicationState::cases(), 'name'));
        self::assertSame(array_map(static fn(int $number): string => 'KGN-NET-' . $number, range(1540, 1548)), array_column(NetworkEgressApplicationDiagnostic::cases(), 'value'));
    }

    public function testInternalAndConsumerApiStatusesAreExplicit(): void
    {
        foreach ([CanonicalProfile::class, GeneratedWiringEmitter::class, GeneratedWiringImplementation::class, PathGuard::class, NetworkEgressWiringValidator::class, NetworkEgressApplicationDiagnostic::class] as $class) {
            $attributes = (new ReflectionClass($class))->getAttributes(Api::class);
            self::assertCount(1, $attributes);
            self::assertSame(ApiStatus::Internal, $attributes[0]->newInstance()->status);
        }
        foreach ([GeneratedNetworkEgressApplicationBoundary::class, NetworkEgressApplicationProfile::class, NetworkEgressApplication::class, NetworkEgressApplicationState::class] as $class) {
            $attributes = (new ReflectionClass($class))->getAttributes(Api::class);
            self::assertCount(1, $attributes);
            self::assertSame(ApiStatus::Experimental, $attributes[0]->newInstance()->status);
        }
    }

    /** @return list<string> */
    private function phpFiles(string $directory): array
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS));
        $files = [];
        foreach ($iterator as $file) {
            if ($file instanceof \SplFileInfo && $file->isFile() && 'php' === $file->getExtension()) {
                $files[] = str_replace('\\', '/', $file->getPathname());
            }
        }
        sort($files, SORT_STRING);

        return $files;
    }
}
