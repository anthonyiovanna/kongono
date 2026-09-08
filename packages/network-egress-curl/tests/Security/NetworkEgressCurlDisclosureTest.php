<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressCurl\Tests\Security;

use Kongono\NetworkEgressContract\Failure\EgressFailureKind;
use Kongono\NetworkEgressCurl\Exception\CurlTransportOperationException;
use Kongono\NetworkEgressCurl\Exception\InvalidCurlTransportProfileException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

final class NetworkEgressCurlDisclosureTest extends TestCase
{
    /** @return iterable<string, array{string}> */
    public static function adversarialValues(): iterable
    {
        yield 'credential' => ['Bearer fake-secret-token'];
        yield 'url' => ['https://secret.example/private'];
        yield 'address' => ['10.0.0.7'];
        yield 'pem' => ['-----BEGIN CERTIFICATE-----PRIVATE'];
        yield 'header' => ['X-Secret: hidden'];
        yield 'path' => ['C:\\private\\ca.pem'];
    }

    #[DataProvider('adversarialValues')]
    public function testExceptionMessagesNeverRetainAdversarialInput(string $value): void
    {
        $invalid = InvalidCurlTransportProfileException::invalid($value);
        $foreign = new RuntimeException($value);
        $operation = CurlTransportOperationException::transfer(EgressFailureKind::UncertainEffect, 9999, $foreign);
        foreach ([$invalid->getMessage(), (string) $invalid, $operation->getMessage(), (string) $operation] as $evidence) {
            self::assertStringNotContainsString($value, $evidence);
        }
        self::assertSame($foreign, $operation->getPrevious());
    }

    public function testOnlyNativeExecutorContainsCurlExec(): void
    {
        $source = dirname(__DIR__, 2) . '/src';
        $matches = [];
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source)) as $file) {
            if (!$file instanceof SplFileInfo) {
                throw new RuntimeException('cURL disclosure inventory entry is not a file descriptor.');
            }
            if ($file->isFile() && 'php' === $file->getExtension() && str_contains((string) file_get_contents($file->getPathname()), 'curl_exec(')) {
                $matches[] = str_replace('\\', '/', $file->getPathname());
            }
        }
        self::assertCount(1, $matches);
        self::assertStringEndsWith('/Internal/NativeCurlExecutor.php', $matches[0]);
    }
}
