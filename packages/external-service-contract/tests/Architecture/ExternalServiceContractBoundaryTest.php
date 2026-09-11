<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceContract\Tests\Architecture;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use SplFileInfo;

final class ExternalServiceContractBoundaryTest extends TestCase
{
    private const array INTERNAL_TYPES = [
        'Kongono\\ExternalServiceContract\\Diagnostic\\ExternalServiceContractDiagnostic',
        'Kongono\\ExternalServiceContract\\Exception\\ExternalServiceContractException',
        'Kongono\\ExternalServiceContract\\Internal\\ContractGuard',
    ];

    public function testManifestInventoryDependenciesAndReverseDependenciesAreExact(): void
    {
        $package = dirname(__DIR__, 2);
        $root = dirname($package, 2);
        $manifest = json_decode((string) file_get_contents($package . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame([
            'name' => 'kongono/external-service-contract',
            'description' => 'Neutral external-service binding and credential-lease contracts for Kongono Framework.',
            'type' => 'library',
            'require' => ['kongono/api-metadata' => '*', 'kongono/diagnostics' => '*', 'kongono/network-egress-contract' => '*', 'kongono/network-egress-runtime' => '*', 'php' => '>=8.5.0 <8.6.0'],
            'require-dev' => ['phpunit/phpunit' => '^13.0'],
            'autoload' => ['psr-4' => ['Kongono\\ExternalServiceContract\\' => 'src/']],
            'autoload-dev' => ['psr-4' => ['Kongono\\ExternalServiceContract\\Tests\\' => 'tests/']],
        ], $manifest);
        self::assertCount(27, self::phpFiles($package . '/src'));
        self::assertCount(15, self::phpFiles($package . '/tests'));
        $manifests = glob($root . '/packages/*/composer.json');
        self::assertIsArray($manifests);
        self::assertCount(89, $manifests);
        foreach ($manifests as $path) {
            $candidate = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($candidate);
            $name = $candidate['name'] ?? null;
            $require = $candidate['require'] ?? null;
            self::assertIsString($name);
            self::assertIsArray($require);
            if (!in_array($name, ['kongono/external-service-application', 'kongono/external-service-binding-build', 'kongono/external-service-contract', 'kongono/external-service-credential-runtime', 'kongono/external-service-inspection', 'kongono/external-service-resolver-system', 'kongono/model-provider-openai-responses'], true)) {
                self::assertArrayNotHasKey('kongono/external-service-contract', $require);
            }
        }
    }

    public function testEveryProductionFileOwnsOneExactStatusedDeclaration(): void
    {
        foreach (self::phpFiles(dirname(__DIR__, 2) . '/src') as $path) {
            $source = (string) file_get_contents($path);
            self::assertSame(1, preg_match_all('/#\[Api\(ApiStatus::(?:Experimental|Internal|Public)\)\]/', $source));
            self::assertSame(1, preg_match_all('/\b(?:final readonly class|final class|abstract class|interface|enum)\s+[A-Za-z_][A-Za-z0-9_]*/', $source));
            if (1 !== preg_match('/namespace\s+([^;]+);/', $source, $namespace)) {
                self::fail('Expected one namespace declaration.');
            }
            $type = $namespace[1] . '\\' . pathinfo($path, PATHINFO_FILENAME);
            if (!class_exists($type) && !interface_exists($type) && !enum_exists($type)) {
                self::fail('Expected discovered production type to exist.');
            }
            $reflection = new ReflectionClass($type);
            $attributes = $reflection->getAttributes(Api::class);
            self::assertCount(1, $attributes);
            $expected = 'Kongono\\ExternalServiceContract\\Exception\\ExceptionInterface' === $type ? ApiStatus::Public : (in_array($type, self::INTERNAL_TYPES, true) ? ApiStatus::Internal : ApiStatus::Experimental);
            self::assertSame($expected, $attributes[0]->newInstance()->status);
        }
    }

    public function testNoPortImplementationOrForbiddenAmbientSurfaceExists(): void
    {
        $files = self::phpFiles(dirname(__DIR__, 2) . '/src');
        $source = implode('', array_map(static fn(string $path): string => (string) file_get_contents($path), $files));
        foreach (['implements CredentialLease', 'implements CredentialLeaseSource', 'implements CredentialMaterialConsumer', 'implements EndpointResolver', 'file_get_contents(', 'file_put_contents(', 'fopen(', 'getenv(', 'putenv(', 'serialize(', 'json_encode(', 'curl_', 'fsockopen(', 'stream_socket_', 'socket_connect(', 'dns_get_record(', 'gethostbyname(', 'proc_open(', 'shell_exec(', '$GLOBALS', '$_ENV', '$_SERVER', 'JsonSerializable', 'Stringable', '__serialize(', '__debugInfo(', '__clone(', '__destruct('] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
        self::assertSame(1, substr_count($source, '#[\\SensitiveParameter] string $material'));
    }

    /** @return list<string> */
    private static function phpFiles(string $root): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile() && 'php' === $file->getExtension()) {
                $files[] = $file->getPathname();
            }
        }
        sort($files);

        return $files;
    }
}
