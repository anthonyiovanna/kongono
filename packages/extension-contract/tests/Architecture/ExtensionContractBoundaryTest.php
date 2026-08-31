<?php

declare(strict_types=1);

namespace Kongono\ExtensionContract\Tests\Architecture;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExtensionContract\Exception\ExceptionInterface;
use Kongono\ExtensionContract\Exception\InvalidExtensionContractException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

final class ExtensionContractBoundaryTest extends TestCase
{
    public function testInventoryDependenciesStatusesAndForbiddenBehaviorAreExact(): void
    {
        $root = dirname(__DIR__, 4);
        $manifest = json_decode((string) file_get_contents($root . '/packages/extension-contract/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertSame('kongono/extension-contract', $manifest['name']);
        self::assertSame(['kongono/api-metadata' => '*', 'kongono/diagnostics' => '*', 'php' => '>=8.5.0 <8.6.0'], $manifest['require']);
        self::assertSame(['phpunit/phpunit' => '^13.0'], $manifest['require-dev']);

        $files = self::phpFiles($root . '/packages/extension-contract/src');
        self::assertCount(21, $files);
        foreach ($files as $file) {
            $source = file_get_contents($file);
            self::assertIsString($source);
            self::assertSame(1, substr_count($source, 'declare(strict_types=1);'));
            self::assertSame(1, substr_count($source, '#[Api('));
            self::assertSame(0, preg_match('/\$_(?:SERVER|ENV|COOKIE|SESSION)|\$GLOBALS|\b(?:getenv|putenv|time|microtime|date|class_exists|interface_exists|trait_exists|enum_exists|spl_autoload_register|file_get_contents|file_put_contents|fopen|curl_exec|stream_socket_client|serialize|unserialize|json_encode|json_decode)\s*\(/', $source));
            self::assertSame(0, preg_match('/use Kongono\\\\(?:ApplicationGraph|Authentication|Authorization|Chie|Composition|Console|Database|DependencyInjection|Hantei|Http|IdentityAccess|Kernel|Knowledge|Metrics|Observability|OpenTelemetry|OperationalEvents|Oshie|Persistence|Routing|Security|Tracing|Web)\\\\/', $source));
            self::assertSame(0, preg_match('/\b(?:ReflectionClass|ReflectionMethod|JsonSerializable|ArrayAccess|Iterator|__destruct|__serialize|__unserialize|callable|Closure|eval|exec|shell_exec|system|passthru)\b/', $source));
            self::assertSame(0, preg_match('/\bstatic\s+\$/', $source));
        }

        $types = [
            'Kongono\\ExtensionContract\\Diagnostic\\ExtensionContractDiagnostic' => ApiStatus::Internal,
            'Kongono\\ExtensionContract\\Exception\\ExtensionContractException' => ApiStatus::Internal,
            'Kongono\\ExtensionContract\\Internal\\ContractGuard' => ApiStatus::Internal,
            ExceptionInterface::class => ApiStatus::Public,
            InvalidExtensionContractException::class => ApiStatus::Experimental,
        ];
        foreach ($types as $type => $status) {
            $attributes = (new ReflectionClass($type))->getAttributes(Api::class);
            self::assertCount(1, $attributes);
            self::assertSame($status, $attributes[0]->newInstance()->status);
        }
        self::assertFalse((new ReflectionClass(ExceptionInterface::class))->implementsInterface('Kongono\\Diagnostics\\Exception\\ExceptionInterface'));
    }

    public function testConsumerPublicSurfacesContainOnlyTheAuthorizedMethods(): void
    {
        $surfaces = [
            'Kongono\\ExtensionContract\\Identity\\ExtensionIdentity' => ['__construct', 'equals', 'value'],
            'Kongono\\ExtensionContract\\Version\\ExtensionVersion' => ['__construct', 'equals', 'value'],
            'Kongono\\ExtensionContract\\Capability\\CapabilityIdentity' => ['__construct', 'equals', 'value', 'version'],
            'Kongono\\ExtensionContract\\Capability\\CapabilityPurpose' => ['__construct', 'equals', 'value'],
            'Kongono\\ExtensionContract\\Capability\\CapabilityRequest' => ['__construct', 'identity', 'purpose', 'requirement'],
            'Kongono\\ExtensionContract\\Contribution\\ContributionIdentity' => ['__construct', 'equals', 'value'],
            'Kongono\\ExtensionContract\\Contribution\\ContributionSchema' => ['__construct', 'equals', 'name', 'version'],
            'Kongono\\ExtensionContract\\Contribution\\ContributionDeclaration' => ['__construct', 'identity', 'schema', 'sourceSha256', 'visibility'],
            'Kongono\\ExtensionContract\\Provenance\\ExtensionSourceIdentity' => ['__construct', 'equals', 'value'],
            'Kongono\\ExtensionContract\\Provenance\\ExtensionProvenance' => ['__construct', 'sourceIdentity', 'sourceSha256'],
            'Kongono\\ExtensionContract\\Declaration\\ExtensionDeclaration' => ['__construct', 'capabilityRequests', 'category', 'contractVersion', 'contributions', 'identity', 'lifecycle', 'provenance', 'version'],
        ];
        foreach ($surfaces as $type => $expected) {
            $methods = array_map(static fn(ReflectionMethod $method): string => $method->getName(), array_filter((new ReflectionClass($type))->getMethods(ReflectionMethod::IS_PUBLIC), static fn(ReflectionMethod $method): bool => $method->getDeclaringClass()->getName() === $type));
            sort($methods);
            self::assertSame($expected, $methods);
        }
    }

    /** @return list<string> */
    private static function phpFiles(string $directory): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo) {
                continue;
            }
            if ($file->isFile() && 'php' === $file->getExtension()) {
                $files[] = $file->getPathname();
            }
        }
        sort($files);

        return $files;
    }
}
