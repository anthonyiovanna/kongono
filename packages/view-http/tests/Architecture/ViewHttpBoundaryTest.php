<?php

declare(strict_types=1);

namespace Kongono\ViewHttp\Tests\Architecture;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class ViewHttpBoundaryTest extends TestCase
{
    public function testPackageManifestHasOnlyAuthorizedDependencies(): void
    {
        $manifest = json_decode((string) file_get_contents(__DIR__ . '/../../composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertIsArray($manifest['require']);
        self::assertIsArray($manifest['require-dev']);
        self::assertIsArray($manifest['autoload']);
        self::assertIsArray($manifest['autoload']['psr-4']);
        self::assertSame('kongono/view-http', $manifest['name']);
        self::assertSame(['php', 'kongono/api-metadata', 'kongono/diagnostics', 'kongono/view-contract', 'psr/http-factory', 'psr/http-message'], array_keys($manifest['require']));
        self::assertSame(['kongono/http-message', 'phpunit/phpunit'], array_keys($manifest['require-dev']));
        self::assertSame(['Kongono\\ViewHttp\\' => 'src/'], $manifest['autoload']['psr-4']);
    }

    public function testProductionDeclarationInventoryIsExact(): void
    {
        $matches = glob(__DIR__ . '/../../src/*/*.php');
        self::assertIsArray($matches);
        $files = array_map(static fn(string $path): string => str_replace('\\', '/', substr($path, strlen(__DIR__ . '/../../'))), $matches);
        sort($files, SORT_STRING);
        self::assertCount(11, $files);
        self::assertContains('src/Http/CompletedViewResponseAdapter.php', $files);
        self::assertContains('src/Internal/ResponseAssembler.php', $files);
        self::assertContains('src/Internal/ViewHttpGuard.php', $files);
    }

    public function testApiStatusesAreExact(): void
    {
        $expectations = [
            \Kongono\ViewHttp\Exception\ExceptionInterface::class => ApiStatus::Public,
            \Kongono\ViewHttp\Http\CompletedViewResponseAdapter::class => ApiStatus::Experimental,
            \Kongono\ViewHttp\Policy\ViewContentType::class => ApiStatus::Experimental,
            \Kongono\ViewHttp\Policy\ViewResponseHeader::class => ApiStatus::Experimental,
            \Kongono\ViewHttp\Policy\ViewResponsePolicy::class => ApiStatus::Experimental,
            \Kongono\ViewHttp\Exception\InvalidViewHttpPolicyException::class => ApiStatus::Experimental,
            \Kongono\ViewHttp\Exception\ViewHttpAdaptationException::class => ApiStatus::Experimental,
            \Kongono\ViewHttp\Diagnostic\ViewHttpDiagnostic::class => ApiStatus::Internal,
            \Kongono\ViewHttp\Exception\ViewHttpException::class => ApiStatus::Internal,
            \Kongono\ViewHttp\Internal\ViewHttpGuard::class => ApiStatus::Internal,
            \Kongono\ViewHttp\Internal\ResponseAssembler::class => ApiStatus::Internal,
        ];
        foreach ($expectations as $class => $status) {
            $attributes = (new ReflectionClass($class))->getAttributes(Api::class);
            self::assertCount(1, $attributes);
            self::assertSame($status, $attributes[0]->newInstance()->status);
        }
    }

    public function testProductionSurfaceUsesOnlyPsrHttpInterfaces(): void
    {
        $source = $this->productionSource();
        self::assertStringContainsString('Psr\\Http\\Message\\ResponseFactoryInterface', $source);
        self::assertStringContainsString('Psr\\Http\\Message\\ResponseInterface', $source);
        self::assertStringContainsString('Psr\\Http\\Message\\StreamFactoryInterface', $source);
        self::assertStringContainsString('Psr\\Http\\Message\\StreamInterface', $source);
        self::assertStringNotContainsString('Kongono\\Http\\Message', $source);
        self::assertStringNotContainsString('RenderFailure', $source);
    }

    public function testProductionSurfaceContainsNoUnauthorizedCapability(): void
    {
        $source = $this->productionSource();
        foreach (['ServerRequestInterface', 'RequestInterface', 'ContainerInterface', 'getenv(', 'putenv(', '$_SERVER', '$_ENV', '$_SESSION', 'session_start(', 'setcookie(', 'curl_', 'PDO', 'ReflectionClass', 'eval(', 'file_get_contents(', 'file_put_contents(', 'fopen('] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source, $forbidden);
        }
    }

    public function testViewContractRemainsPsrFreeAndReverseConsumersAreExact(): void
    {
        $root = dirname(__DIR__, 4);
        $contractSource = '';
        $contractFiles = glob($root . '/packages/view-contract/src/*/*.php');
        self::assertIsArray($contractFiles);
        foreach ($contractFiles as $path) {
            $contractSource .= (string) file_get_contents($path);
        }
        self::assertStringNotContainsString('Psr\\Http', $contractSource);
        self::assertStringNotContainsString('psr/http', (string) file_get_contents($root . '/packages/view-contract/composer.json'));

        $consumers = [];
        $manifests = glob($root . '/packages/*/composer.json');
        self::assertIsArray($manifests);
        foreach ($manifests as $path) {
            $manifest = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($manifest);
            self::assertIsArray($manifest['require']);
            if (array_key_exists('kongono/view-contract', $manifest['require'])) {
                self::assertIsString($manifest['name']);
                $consumers[] = $manifest['name'];
            }
        }
        sort($consumers, SORT_STRING);
        self::assertSame(['kongono/view-application', 'kongono/view-build', 'kongono/view-http', 'kongono/view-inspection', 'kongono/view-runtime'], $consumers);
    }

    private function productionSource(): string
    {
        $files = glob(__DIR__ . '/../../src/*/*.php');
        self::assertIsArray($files);
        $source = '';
        foreach ($files as $path) {
            $source .= (string) file_get_contents($path);
        }

        return $source;
    }
}
