<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceBindingBuild\Tests\Security;

use Kongono\ExternalServiceBindingBuild\Artifact\ExternalServiceBindingArtifactValidator;
use Kongono\ExternalServiceBindingBuild\Exception\ExceptionInterface;
use Kongono\ExternalServiceBindingBuild\Generated\GeneratedExternalServiceBindingsValidator;
use PHPUnit\Framework\TestCase;

final class ExternalServiceBindingBuildDisclosureTest extends TestCase
{
    public function testArtifactGeneratedAndForeignFailuresDoNotDiscloseRejectedBytes(): void
    {
        $raw = 'DO-NOT-DISCLOSE-FAKE-CREDENTIAL-MATERIAL';
        foreach ([new ExternalServiceBindingArtifactValidator(), new GeneratedExternalServiceBindingsValidator()] as $validator) {
            try {
                $validator->validateStructure($raw);
                self::fail('Expected rejection.');
            } catch (ExceptionInterface $failure) {
                self::assertStringNotContainsString($raw, $failure->getMessage());
                self::assertStringNotContainsString($raw, (string) $failure);
            }
        }
    }

    public function testProductionSourceContainsNoCredentialMaterialOrAmbientEffectAccess(): void
    {
        $source = '';
        $package = dirname(__DIR__, 2);
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($package . '/src', \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            self::assertInstanceOf(\SplFileInfo::class, $file);
            if ($file->isFile() && 'php' === $file->getExtension()) {
                $source .= file_get_contents($file->getPathname());
            }
        }
        foreach (['getenv(', 'putenv(', '$_ENV', '$_SERVER', 'curl_', 'dns_', 'fsockopen(', 'stream_socket_', 'proc_open(', 'shell_exec(', 'eval(', 'include(', 'require(', 'ReflectionClass', 'CredentialLeaseSource', 'EndpointResolver'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
    }
}
