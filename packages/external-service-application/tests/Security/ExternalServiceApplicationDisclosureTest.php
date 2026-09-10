<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceApplication\Tests\Security;

use Kongono\ExternalServiceApplication\Diagnostic\ExternalServiceApplicationDiagnostic;
use Kongono\ExternalServiceApplication\Exception\ExternalServiceApplicationOperationException;
use Kongono\ExternalServiceApplication\Execution\ExternalServiceApplicationResult;
use Kongono\ExternalServiceApplication\Internal\OperationMaterialConsumer;
use Kongono\ExternalServiceApplication\Runtime\ExternalServiceApplication;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;

final class ExternalServiceApplicationDisclosureTest extends TestCase
{
    public function testSecretBearingPropertyDoesNotExistOnPackageControlledRuntimeEvidenceOrPresentationTypes(): void
    {
        foreach ([
            ExternalServiceApplication::class,
            OperationMaterialConsumer::class,
            ExternalServiceApplicationResult::class,
            ExternalServiceApplicationOperationException::class,
            ExternalServiceApplicationDiagnostic::class,
        ] as $class) {
            foreach ((new ReflectionClass($class))->getProperties() as $property) {
                $name = strtolower($property->getName());
                self::assertStringNotContainsString('material', $name, $class . '::$' . $name);
                self::assertStringNotContainsString('secret', $name, $class . '::$' . $name);
                self::assertStringNotContainsString('credentialvalue', $name, $class . '::$' . $name);
            }
        }
    }

    public function testHighEntropyForeignAndRequestSentinelsAreAbsentFromExceptionPresentation(): void
    {
        $sentinels = [
            'secret-N7vka0I4p9Ys5x2d',
            'https://host-sentinel.invalid/private?token=sentinel',
            'Authorization: Bearer sentinel',
            'C:\\sensitive\\credential.txt',
        ];
        $foreign = new RuntimeException(implode('|', $sentinels), 9137);
        $failure = ExternalServiceApplicationOperationException::failed('credential_runtime', $foreign);
        $presentation = $failure->getMessage() . '|' . (string) $failure;

        self::assertSame($foreign, $failure->getPrevious());
        foreach ($sentinels as $sentinel) {
            self::assertStringNotContainsString($sentinel, $presentation);
        }
        self::assertStringNotContainsString('9137', $presentation);
    }
}
