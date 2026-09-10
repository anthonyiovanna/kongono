<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceContract\Tests\Security;

use Kongono\ExternalServiceContract\Endpoint\EndpointBasePath;
use Kongono\ExternalServiceContract\Exception\InvalidExternalServiceContractException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ExternalServiceContractDisclosureTest extends TestCase
{
    public function testAdversarialValuesNeverReachVisibleFailureSurfaces(): void
    {
        $values = ['https://user@service.test/path?credential=private', 'C:\\private\\credential.file', "ENV_NAME\r\nAuthorization: private", 'foreign exception private text'];
        foreach ($values as $raw) {
            try {
                new EndpointBasePath($raw);
                self::fail('Expected adversarial value rejection.');
            } catch (InvalidExternalServiceContractException $exception) {
                self::assertStringNotContainsString($raw, $exception->getMessage());
                self::assertStringNotContainsString($raw, (string) $exception);
            }
            $exception = InvalidExternalServiceContractException::invalidBinding($raw, new RuntimeException($raw));
            self::assertStringNotContainsString($raw, (string) $exception);
        }
    }
}
