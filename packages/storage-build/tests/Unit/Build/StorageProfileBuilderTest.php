<?php

declare(strict_types=1);

namespace Kongono\StorageBuild\Tests\Unit\Build;

use Kongono\StorageBuild\Build\StorageProfileBuilder;
use Kongono\StorageBuild\Profile\StorageProfileDeclaration;
use Kongono\StorageBuild\Profile\StorageProfileLimits;
use Kongono\StorageBuild\Profile\StorageProfileName;
use Kongono\StorageBuild\Profile\StorageProfileSet;
use Kongono\StorageContract\Capability\StorageCapability;
use Kongono\StorageContract\Capability\StorageCapabilitySet;
use Kongono\StorageContract\ContractVersion;
use Kongono\StorageContract\Digest\StorageDigestAlgorithm;
use Kongono\StorageContract\Identity\StorageNamespace;
use PHPUnit\Framework\TestCase;

final class StorageProfileBuilderTest extends TestCase
{
    public function testBuildIsCanonicalCompleteAndExactlyVersioned(): void
    {
        $result = (new StorageProfileBuilder())->build(self::set());
        $bytes = $result->artifact()->bytes();
        $decoded = json_decode($bytes, true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);
        self::assertSame(['schema', 'source_identity', 'build_identity', 'profile_count', 'profiles'], array_keys($decoded));
        self::assertSame('kongono/storage-profile-artifact/1', $decoded['schema']);
        self::assertSame(1, $decoded['profile_count']);
        self::assertIsArray($decoded['profiles']);
        $profile = $decoded['profiles'][0];
        self::assertIsArray($profile);
        self::assertSame(['name', 'contract_version', 'namespace', 'capabilities', 'digest_algorithm', 'limits'], array_keys($profile));
        self::assertSame(1, $profile['contract_version']);
        self::assertSame(['read', 'content_digest'], $profile['capabilities']);
        self::assertSame('sha256', $profile['digest_algorithm']);
        self::assertIsArray($profile['limits']);
        self::assertSame(['max_chunk_bytes', 'max_object_bytes', 'max_list_page_size'], array_keys($profile['limits']));
        self::assertStringEndsWith("\n", $bytes);
        self::assertFalse(str_starts_with($bytes, "\xEF\xBB\xBF"));
        self::assertStringNotContainsString("\r", $bytes);
        self::assertLessThanOrEqual(1_048_576, strlen($bytes));
        self::assertSame(hash('sha256', $bytes), $result->artifactIdentity()->value());
        self::assertSame($result->sourceIdentity()->value(), $result->artifact()->sourceIdentity()->value());
        self::assertTrue($result->equals((new StorageProfileBuilder())->build(self::set())));
    }

    private static function set(): StorageProfileSet
    {
        return new StorageProfileSet([new StorageProfileDeclaration(
            ContractVersion::Version1,
            new StorageProfileName('primary'),
            new StorageNamespace('primary-store'),
            new StorageCapabilitySet([StorageCapability::ContentDigest, StorageCapability::Read]),
            StorageDigestAlgorithm::Sha256,
            new StorageProfileLimits(1024, 4096, 25),
        )]);
    }
}
