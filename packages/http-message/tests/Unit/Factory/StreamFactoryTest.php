<?php

declare(strict_types=1);

namespace Kongono\Http\Message\Tests\Unit\Factory;

use Kongono\Http\Message\Exception\HttpMessageRuntimeException;
use Kongono\Http\Message\Exception\InvalidMessageArgumentException;
use Kongono\Http\Message\Factory\StreamFactory;
use Kongono\Http\Message\Message\Stream;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamFactoryInterface;

final class StreamFactoryTest extends TestCase
{
    public function testStringAndResourceFactoriesPreserveContentAndIdentity(): void
    {
        $factory = new StreamFactory();
        $stream = $factory->createStream('content');
        self::assertInstanceOf(StreamFactoryInterface::class, $factory);
        self::assertInstanceOf(Stream::class, $stream);
        self::assertSame(0, $stream->tell());
        self::assertSame('content', $stream->getContents());
        self::assertSame([], (new \ReflectionClass($factory))->getProperties());
        $resource = fopen('php://memory', 'r+b');
        self::assertIsResource($resource);
        $wrapped = $factory->createStreamFromResource($resource);
        self::assertSame($resource, $wrapped->detach());
        fclose($resource);
    }

    public function testFileModesAndFailuresAreTypedWithoutPathDisclosure(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'kgn-http-');
        self::assertIsString($path);
        file_put_contents($path, 'abc');
        $factory = new StreamFactory();
        try {
            $stream = $factory->createStreamFromFile($path, 'rb');
            self::assertSame('abc', $stream->getContents());
            $stream->close();
        } finally {
            @unlink($path);
        }
        foreach (['', "r\0", 'r ', 'rz', 'rbb', 'rbt', 'r++', 'rr'] as $mode) {
            try {
                $factory->createStreamFromFile('hidden', $mode);
                self::fail('Bad mode must fail.');
            } catch (InvalidMessageArgumentException $e) {
                self::assertSame('KGN-HTTP-1020', $e->diagnosticId());
                self::assertStringNotContainsString('hidden', $e->getMessage());
                self::assertSame(strlen($mode), $e->diagnosticContext()->fields()[0]->value());
            }
        }
        try {
            $factory->createStreamFromFile(sys_get_temp_dir() . '/definitely-missing-kgn-http', 'rb');
            self::fail('Missing file must fail.');
        } catch (HttpMessageRuntimeException $e) {
            self::assertStringNotContainsString('missing', $e->getMessage());
        }
        $writePath = tempnam(sys_get_temp_dir(), 'kgn-http-write-');
        self::assertIsString($writePath);
        $write = fopen($writePath, 'wb');
        self::assertIsResource($write);
        try {
            $factory->createStreamFromResource($write);
            self::fail('Unreadable resource must fail.');
        } catch (InvalidMessageArgumentException) {
            self::addToAssertionCount(1);
        } finally {
            fclose($write);
            @unlink($writePath);
        }
    }

    public function testNativeSupportedExtendedModesAreAcceptedWithoutNormalization(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'kgn-http-mode-');
        self::assertIsString($path);
        file_put_contents($path, 'content');
        $factory = new StreamFactory();
        try {
            foreach (['rbe', 'rb+e', 'r+be'] as $mode) {
                $native = @fopen($path, $mode);
                if (false === $native) {
                    self::assertNotSame('rbe', $mode, 'The fixed execution environment must support rbe.');
                    continue;
                }
                fclose($native);
                $stream = $factory->createStreamFromFile($path, $mode);
                self::assertSame('content', $stream->getContents());
                $stream->close();
            }

            try {
                $factory->createStreamFromFile($path . '.absent', 'rbe');
                self::fail('A native open failure must remain a runtime failure.');
            } catch (HttpMessageRuntimeException $exception) {
                self::assertSame('KGN-HTTP-1018', $exception->diagnosticId());
                self::assertStringNotContainsString($path, (string) $exception);
            }
            try {
                $factory->createStreamFromFile($path . '.absent', 'rn');
                self::fail('The structurally valid n modifier must reach native open.');
            } catch (HttpMessageRuntimeException $exception) {
                self::assertSame('KGN-HTTP-1018', $exception->diagnosticId());
            }
        } finally {
            @unlink($path);
        }
    }

    public function testEveryBaseModeReachesNativeFileBehavior(): void
    {
        $factory = new StreamFactory();
        $path = tempnam(sys_get_temp_dir(), 'kgn-http-base-mode-');
        self::assertIsString($path);
        try {
            file_put_contents($path, 'initial');
            $read = $factory->createStreamFromFile($path, 'r');
            self::assertSame('initial', $read->getContents());
            $read->close();

            foreach (['w', 'a', 'c'] as $mode) {
                $stream = $factory->createStreamFromFile($path, $mode);
                self::assertTrue($stream->isWritable());
                $stream->write($mode);
                $stream->close();
            }

            @unlink($path);
            $exclusive = $factory->createStreamFromFile($path, 'x');
            self::assertTrue($exclusive->isWritable());
            $exclusive->close();
        } finally {
            @unlink($path);
        }
    }
}
