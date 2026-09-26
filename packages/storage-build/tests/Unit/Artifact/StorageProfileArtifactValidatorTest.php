<?php

declare(strict_types=1);

namespace Kongono\StorageBuild\Tests\Unit\Artifact;

use Kongono\StorageBuild\Artifact\StorageProfileArtifactValidator;
use Kongono\StorageBuild\Build\StorageProfileBuilder;
use Kongono\StorageBuild\Exception\InvalidStorageProfileArtifactException;
use Kongono\StorageBuild\Exception\StaleStorageProfileArtifactException;
use Kongono\StorageBuild\Profile\StorageProfileDeclaration;
use Kongono\StorageBuild\Profile\StorageProfileLimits;
use Kongono\StorageBuild\Profile\StorageProfileName;
use Kongono\StorageBuild\Profile\StorageProfileSet;
use Kongono\StorageContract\Capability\StorageCapability;
use Kongono\StorageContract\Capability\StorageCapabilitySet;
use Kongono\StorageContract\ContractVersion;
use Kongono\StorageContract\Digest\StorageDigestAlgorithm;
use Kongono\StorageContract\Identity\StorageNamespace;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class StorageProfileArtifactValidatorTest extends TestCase
{
    public function testStructureAndCurrentnessAreExact(): void
    {
        $set = self::set('primary', 4096);
        $bytes = (new StorageProfileBuilder())->build($set)->artifact()->bytes();
        $validator = new StorageProfileArtifactValidator();
        self::assertSame($bytes, $validator->validateStructure($bytes)->bytes());
        self::assertSame($bytes, $validator->validateCurrent($set, $bytes)->bytes());

        $this->expectException(StaleStorageProfileArtifactException::class);
        $validator->validateCurrent(self::set('primary', 8192), $bytes);
    }

    #[DataProvider('invalidContractVersions')]
    public function testContractVersionAcceptsOnlyJsonIntegerOne(string $replacement): void
    {
        $bytes = (new StorageProfileBuilder())->build(self::set('primary', 4096))->artifact()->bytes();
        $this->assertInvalid(str_replace('"contract_version":1', '"contract_version":' . $replacement, $bytes));
    }

    /** @return iterable<string, array{string}> */
    public static function invalidContractVersions(): iterable
    {
        yield 'numeric-string' => ['"1"'];
        yield 'case-name' => ['"Version1"'];
        yield 'obsolete-case-name' => ['"V1"'];
        yield 'zero' => ['0'];
        yield 'two' => ['2'];
        yield 'float' => ['1.0'];
        yield 'boolean' => ['true'];
        yield 'null' => ['null'];
    }

    public function testCanonicalFramingDepthSizeAndDuplicateRulesFailClosed(): void
    {
        $bytes = (new StorageProfileBuilder())->build(self::set('primary', 4096))->artifact()->bytes();
        $this->assertInvalid("\xEF\xBB\xBF" . $bytes);
        $this->assertInvalid(str_replace("\n", "\r\n", $bytes));
        $this->assertInvalid(substr($bytes, 0, -1));
        $this->assertInvalid($bytes . "\n");
        $this->assertInvalid(substr($bytes, 0, -1) . "\xFF\n");
        $this->assertInvalid(str_replace('kongono/storage', 'kongono\\/storage', $bytes));
        $this->assertInvalid(str_replace('{"schema":', '{"schema":"kongono/storage-profile-artifact/1","schema":', $bytes));
        $this->assertInvalid(str_repeat(' ', 1_048_576) . "\n");
        $this->assertInvalid(str_repeat('{"a":', 17) . '0' . str_repeat('}', 17) . "\n");
    }

    public function testEveryIdentityCountAndProfileFieldIsAuthenticated(): void
    {
        $bytes = (new StorageProfileBuilder())->build(self::set('primary', 4096))->artifact()->bytes();
        $model = self::decode($bytes);
        $mutations = [];
        $changed = $model;
        $changed['source_identity'] = str_repeat('0', 64);
        $mutations[] = $changed;
        $changed = $model;
        $changed['build_identity'] = str_repeat('1', 64);
        $mutations[] = $changed;
        $changed = $model;
        $changed['profile_count'] = 2;
        $mutations[] = $changed;
        $changed = self::withFirstProfileField($model, 'name', 'secondary');
        $mutations[] = $changed;
        $changed = self::withFirstProfileField($model, 'namespace', 'secondary-store');
        $mutations[] = $changed;
        $changed = self::withFirstProfileField($model, 'capabilities', ['content_digest', 'read']);
        $mutations[] = $changed;
        $changed = self::withFirstProfileField($model, 'digest_algorithm', 'sha384');
        $mutations[] = $changed;
        $changed = self::withFirstProfileLimit($model, 'max_object_bytes', 8192);
        $mutations[] = $changed;
        $changed = $model;
        $changed['unknown'] = true;
        $mutations[] = $changed;
        $changed = ['source_identity' => $model['source_identity']] + $model;
        $mutations[] = $changed;

        foreach ($mutations as $mutation) {
            $this->assertInvalid(self::encode($mutation));
        }
    }

    public function testNumericStringsAndDigestRelationFailClosed(): void
    {
        $bytes = (new StorageProfileBuilder())->build(self::set('primary', 4096))->artifact()->bytes();
        $model = self::decode($bytes);
        $model = self::withFirstProfileLimit($model, 'max_chunk_bytes', '1024');
        $this->assertInvalid(self::encode($model));

        $model = self::decode($bytes);
        $model = self::withFirstProfileField($model, 'digest_algorithm', null);
        $this->assertInvalid(self::encode($model));
    }

    /** @return array<string, mixed> */
    private static function decode(string $bytes): array
    {
        $model = json_decode($bytes, true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($model);

        $result = [];
        foreach ($model as $key => $value) {
            self::assertIsString($key);
            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $model
     * @return array<string, mixed>
     */
    private static function withFirstProfileField(array $model, string $field, mixed $value): array
    {
        $profiles = $model['profiles'] ?? null;
        self::assertIsArray($profiles);
        $profile = $profiles[0] ?? null;
        self::assertIsArray($profile);
        $profile[$field] = $value;
        $profiles[0] = $profile;
        $model['profiles'] = $profiles;

        return $model;
    }

    /**
     * @param array<string, mixed> $model
     * @return array<string, mixed>
     */
    private static function withFirstProfileLimit(array $model, string $field, mixed $value): array
    {
        $profiles = $model['profiles'] ?? null;
        self::assertIsArray($profiles);
        $profile = $profiles[0] ?? null;
        self::assertIsArray($profile);
        $limits = $profile['limits'] ?? null;
        self::assertIsArray($limits);
        $limits[$field] = $value;
        $profile['limits'] = $limits;
        $profiles[0] = $profile;
        $model['profiles'] = $profiles;

        return $model;
    }

    /** @param array<string, mixed> $model */
    private static function encode(array $model): string
    {
        return json_encode($model, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    }

    private function assertInvalid(string $bytes): void
    {
        try {
            (new StorageProfileArtifactValidator())->validateStructure($bytes);
            self::fail('Invalid artifact bytes were accepted.');
        } catch (InvalidStorageProfileArtifactException) {
            $this->addToAssertionCount(1);
        }
    }

    private static function set(string $name, int $maxObjectBytes): StorageProfileSet
    {
        return new StorageProfileSet([new StorageProfileDeclaration(
            ContractVersion::Version1,
            new StorageProfileName($name),
            new StorageNamespace($name . '-store'),
            new StorageCapabilitySet([StorageCapability::Read, StorageCapability::ContentDigest]),
            StorageDigestAlgorithm::Sha256,
            new StorageProfileLimits(1024, $maxObjectBytes, 25),
        )]);
    }
}
