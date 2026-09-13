<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceResolverSystem\Tests\Architecture;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class SystemResolverBoundaryTest extends TestCase
{
    public function testExactInventoryManifestAndStatuses(): void
    {
        $package = dirname(__DIR__, 2);
        self::assertSame([
            'Diagnostic/SystemResolverDiagnostic.php',
            'Exception/ExceptionInterface.php',
            'Exception/ExternalServiceResolverSystemException.php',
            'Exception/InvalidSystemResolverProfileException.php',
            'Exception/SystemResolverEnvironmentException.php',
            'Internal/AddressInfoLookup.php',
            'Internal/AddressInfoLookupFailure.php',
            'Internal/AddressInfoLookupResult.php',
            'Internal/AddressInfoNormalizer.php',
            'Internal/AddressInfoRecord.php',
            'Internal/NativeAddressInfoLookup.php',
            'Internal/ResolutionOrdering.php',
            'Internal/SystemResolverEngine.php',
            'Internal/SystemResolverEnvironment.php',
            'Profile/AddressFamilyPreference.php',
            'Profile/SystemResolverProfile.php',
            'Resolver/SystemEndpointResolver.php',
        ], self::phpFiles($package . '/src'));
        self::assertCount(13, self::phpFiles($package . '/tests'));
        $manifest = json_decode((string) file_get_contents($package . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertArrayHasKey('require', $manifest);
        self::assertIsArray($manifest['require']);
        self::assertSame([
            'php' => '>=8.5.0 <8.6.0',
            'ext-sockets' => '*',
            'kongono/api-metadata' => '*',
            'kongono/diagnostics' => '*',
            'kongono/external-service-contract' => '*',
            'kongono/network-egress-contract' => '*',
            'kongono/network-egress-runtime' => '*',
        ], $manifest['require']);

        foreach ([
            'Kongono\\ExternalServiceResolverSystem\\Exception\\ExceptionInterface' => ApiStatus::Public,
            'Kongono\\ExternalServiceResolverSystem\\Profile\\AddressFamilyPreference' => ApiStatus::Experimental,
            'Kongono\\ExternalServiceResolverSystem\\Profile\\SystemResolverProfile' => ApiStatus::Experimental,
            'Kongono\\ExternalServiceResolverSystem\\Resolver\\SystemEndpointResolver' => ApiStatus::Experimental,
            'Kongono\\ExternalServiceResolverSystem\\Exception\\InvalidSystemResolverProfileException' => ApiStatus::Experimental,
            'Kongono\\ExternalServiceResolverSystem\\Exception\\SystemResolverEnvironmentException' => ApiStatus::Experimental,
            'Kongono\\ExternalServiceResolverSystem\\Diagnostic\\SystemResolverDiagnostic' => ApiStatus::Internal,
            'Kongono\\ExternalServiceResolverSystem\\Internal\\NativeAddressInfoLookup' => ApiStatus::Internal,
        ] as $class => $status) {
            $attributes = (new ReflectionClass($class))->getAttributes(Api::class);
            self::assertCount(1, $attributes);
            self::assertSame($status, $attributes[0]->newInstance()->status);
        }
    }

    public function testTopologyIsExactAndNoPackageConsumesTheLeaf(): void
    {
        $root = dirname(__DIR__, 4);
        $paths = glob($root . '/packages/*/composer.json');
        self::assertIsArray($paths);
        self::assertCount(95, $paths);
        $edges = 0;
        foreach ($paths as $path) {
            $manifest = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($manifest);
            self::assertArrayHasKey('name', $manifest);
            self::assertIsString($manifest['name']);
            self::assertArrayHasKey('require', $manifest);
            self::assertIsArray($manifest['require']);
            foreach ($manifest['require'] as $name => $_constraint) {
                if (str_starts_with($name, 'kongono/')) {
                    ++$edges;
                }
            }
            if ('kongono/external-service-resolver-system' !== $manifest['name']) {
                self::assertArrayNotHasKey('kongono/external-service-resolver-system', $manifest['require']);
            }
        }
        self::assertSame(402, $edges);
    }

    public function testSourceContainsOnlyTheAuthorizedNativeLookupBoundary(): void
    {
        $directory = dirname(__DIR__, 2) . '/src';
        $lookupCalls = 0;
        $explainCalls = 0;
        foreach (self::phpFiles($directory) as $relative) {
            $source = (string) file_get_contents($directory . '/' . $relative);
            $lookupCalls += substr_count($source, 'socket_addrinfo_lookup(');
            $explainCalls += substr_count($source, 'socket_addrinfo_explain(');
            foreach (['gethostbyname(', 'gethostbynamel(', 'dns_get_record(', 'curl_', 'fsockopen(', 'stream_socket_', 'socket_connect(', 'proc_open(', 'shell_exec(', 'FFI', 'getenv(', 'putenv(', 'Credential', 'Provider', 'Registry', 'Command'] as $forbidden) {
                self::assertStringNotContainsString($forbidden, $source);
            }
        }
        self::assertSame(1, $lookupCalls);
        self::assertSame(1, $explainCalls);
    }

    /** @return list<string> */
    private static function phpFiles(string $directory): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            self::assertInstanceOf(\SplFileInfo::class, $file);
            if ($file->isFile() && 'php' === $file->getExtension()) {
                $files[] = str_replace('\\', '/', substr($file->getPathname(), strlen($directory) + 1));
            }
        }
        sort($files, SORT_STRING);

        return $files;
    }
}
