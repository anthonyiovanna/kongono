<?php

declare(strict_types=1);

namespace Kongono\CacheBuild\Tests\Security;

use Kongono\CacheBuild\Exception\InvalidCacheProfileException;
use Kongono\CacheBuild\Profile\CacheProfileName;
use PHPUnit\Framework\TestCase;

final class CacheBuildDisclosureTest extends TestCase
{
    public function testRejectedInputAndAmbientAuthorityAreNotDisclosed(): void
    {
        $secret = 'SECRET-profile/C:\\private\\token';
        try {
            new CacheProfileName($secret);
            self::fail('Invalid profile name was accepted.');
        } catch (InvalidCacheProfileException $failure) {
            self::assertStringNotContainsString($secret, (string) $failure);
            self::assertSame('KGN-CACHE-BUILD-2011', $failure->diagnosticId());
        }

        $source = '';
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(dirname(__DIR__, 2) . '/src')) as $entry) {
            if ($entry instanceof \SplFileInfo && $entry->isFile() && 'php' === $entry->getExtension()) {
                $source .= (string) file_get_contents($entry->getPathname());
            }
        }
        foreach (['getenv(', '$_ENV', '$_SERVER', 'serialize(', 'unserialize(', 'proc_open(', 'curl_', 'eval(', 'ReflectionClass', 'Psr\\Http'] as $denied) {
            self::assertStringNotContainsString($denied, $source, $denied);
        }
    }
}
