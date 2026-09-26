<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressContract\Tests\Architecture;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use SplFileInfo;

final class NetworkEgressContractBoundaryTest extends TestCase
{
    private const array INTERNAL_TYPES = [
        'Kongono\\NetworkEgressContract\\Diagnostic\\NetworkEgressContractDiagnostic',
        'Kongono\\NetworkEgressContract\\Exception\\NetworkEgressContractException',
        'Kongono\\NetworkEgressContract\\Internal\\ContractGuard',
    ];

    public function testManifestInventoryDependenciesAndReverseDependenciesAreExact(): void
    {
        $package = dirname(__DIR__, 2);
        $root = dirname($package, 2);
        $manifest = json_decode((string) file_get_contents($package . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame([
            'name' => 'kongono/network-egress-contract',
            'description' => 'Neutral versioned network-egress contracts for Kongono Framework.',
            'type' => 'library',
            'require' => [
                'kongono/api-metadata' => '*',
                'kongono/diagnostics' => '*',
                'php' => '>=8.5.0 <8.6.0',
                'psr/http-message' => '^2.0',
            ],
            'require-dev' => ['phpunit/phpunit' => '^13.0'],
            'autoload' => ['psr-4' => ['Kongono\\NetworkEgressContract\\' => 'src/']],
            'autoload-dev' => ['psr-4' => ['Kongono\\NetworkEgressContract\\Tests\\' => 'tests/']],
        ], $manifest);
        self::assertCount(28, self::phpFiles($package . '/src'));
        self::assertCount(16, self::phpFiles($package . '/tests'));

        $manifests = glob($root . '/packages/*/composer.json');
        self::assertIsArray($manifests);
        self::assertCount(116, $manifests);
        foreach ($manifests as $path) {
            $candidate = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($candidate);
            self::assertIsString($candidate['name']);
            self::assertIsArray($candidate['require']);
            if (!in_array($candidate['name'], ['kongono/external-service-application', 'kongono/external-service-binding-build', 'kongono/external-service-contract', 'kongono/external-service-inspection', 'kongono/external-service-resolver-system', 'kongono/model-provider-openai-responses', 'kongono/network-egress-application', 'kongono/network-egress-contract', 'kongono/network-egress-curl', 'kongono/network-egress-inspection', 'kongono/network-egress-policy-build', 'kongono/network-egress-runtime'], true)) {
                self::assertArrayNotHasKey('kongono/network-egress-contract', $candidate['require']);
            }
        }
        self::assertSame(['kongono/api-metadata', 'kongono/diagnostics'], array_values(array_filter(array_keys($manifest['require']), static fn(string $name): bool => str_starts_with($name, 'kongono/'))));
    }

    public function testEveryProductionFileOwnsOneExactStatusedDeclaration(): void
    {
        $types = [];
        foreach (self::phpFiles(dirname(__DIR__, 2) . '/src') as $path) {
            $source = (string) file_get_contents($path);
            self::assertSame(1, preg_match_all('/#\[Api\(ApiStatus::(?:Experimental|Internal|Public)\)\]/', $source));
            self::assertSame(1, preg_match_all('/\b(?:final readonly class|final class|abstract class|interface|enum)\s+[A-Za-z_][A-Za-z0-9_]*/', $source));
            if (1 !== preg_match('/namespace\s+([^;]+);/', $source, $namespace)) {
                self::fail('Expected one exact namespace declaration.');
            }
            $types[] = $namespace[1] . '\\' . pathinfo($path, PATHINFO_FILENAME);
        }

        self::assertCount(28, $types);
        foreach ($types as $type) {
            if (!class_exists($type) && !interface_exists($type) && !enum_exists($type)) {
                self::fail('Expected discovered production type to exist.');
            }
            $reflection = new ReflectionClass($type);
            $attributes = $reflection->getAttributes(Api::class);
            self::assertCount(1, $attributes);
            $expected = 'Kongono\\NetworkEgressContract\\Exception\\ExceptionInterface' === $type
                ? ApiStatus::Public
                : (in_array($type, self::INTERNAL_TYPES, true) ? ApiStatus::Internal : ApiStatus::Experimental);
            self::assertSame($expected, $attributes[0]->newInstance()->status);
            if (!$reflection->isInterface() && !$reflection->isEnum() && !$reflection->isAbstract()) {
                self::assertTrue($reflection->isFinal());
                $nonReadonly = in_array($type, ['Kongono\\NetworkEgressContract\\Exception\\InvalidNetworkEgressContractException', 'Kongono\\NetworkEgressContract\\Internal\\ContractGuard'], true);
                self::assertSame(!$nonReadonly, $reflection->isReadOnly());
            }
        }
    }

    public function testSourceHasNoAmbientAuthoritySerializationOrEffectSurface(): void
    {
        $source = implode('', array_map(static fn(string $path): string => self::withoutComments((string) file_get_contents($path)), self::phpFiles(dirname(__DIR__, 2) . '/src')));
        foreach (['file_get_contents(', 'file_put_contents(', 'fopen(', 'glob(', 'scandir(', 'getenv(', 'putenv(', 'Reflection', 'serialize(', 'json_encode(', 'PDO', 'curl_', 'fsockopen(', 'stream_socket_', 'socket_connect(', 'dns_get_record(', 'gethostbyname(', 'Psr\\Http\\Client', 'Guzzle', 'Symfony\\Component\\HttpClient', 'proc_open(', 'shell_exec(', '$GLOBALS', '$_ENV', '$_SERVER', 'callable', 'Closure', 'Fiber'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
        foreach (['authorize(', 'isAuthorized(', 'permit(', 'send(', 'execute(', 'resolve(', 'connect(', 'retry(', '__serialize(', 'JsonSerializable', 'Stringable'] as $forbiddenSurface) {
            self::assertStringNotContainsString($forbiddenSurface, $source);
        }
        foreach (['getBody(', 'read(', 'rewind(', 'seek(', 'write(', 'getContents('] as $bodyMethod) {
            self::assertStringNotContainsString($bodyMethod, $source);
        }
    }

    private static function withoutComments(string $source): string
    {
        $code = '';
        foreach (token_get_all($source) as $token) {
            if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }
            $code .= is_array($token) ? $token[1] : $token;
        }

        return $code;
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
