<?php

declare(strict_types=1);

namespace Kongono\StorageApplication\Tests\Unit;

use Kongono\StorageApplication\Exception\InvalidStorageApplicationException;
use Kongono\StorageApplication\Source\StorageProfileSourceLoader;
use PHPUnit\Framework\TestCase;

final class StorageProfileSourceLoaderTest extends TestCase
{
    public function testEquivalentProfileOrderHasOneCanonicalIdentity(): void
    {
        $one = self::row('alpha', 'alpha-objects');
        $two = self::row('beta', 'beta-objects');
        $loader = new StorageProfileSourceLoader();
        $first = $loader->load(self::document([$one, $two]));
        $second = $loader->load(self::document([$two, $one]));
        self::assertTrue($first->equals($second));
        self::assertSame($first->identity()->value(), $second->identity()->value());
    }

    public function testRejectsDuplicateNestedMembersAndWrongShapes(): void
    {
        $valid = self::document([self::row('alpha', 'alpha-objects')]);
        $bad = [
            str_replace('"schema":', '"schema":"kongono/storage-application-profile-source/1","schema":', $valid),
            str_replace('"profiles":', '"extra":true,"profiles":', $valid),
            str_replace('"schema":"kongono/storage-application-profile-source/1",', '', $valid),
            str_replace('"namespace":"alpha-objects",', '', $valid),
            str_replace('"max_chunk_bytes":1024', '"max_chunk_bytes":1024,"max_chunk_bytes":1024', $valid),
            str_replace('"limits":{', '"limits":{"0":0,', $valid),
            str_replace('"capabilities":["read"]', '"capabilities":{"0":"read"}', $valid),
            str_replace('"profiles":[', '"profiles":{"0":', str_replace(']}', '}}', $valid)),
            str_replace('"contract_version":1', '"contract_version":"1"', $valid),
            str_replace('"contract_version":1', '"contract_version":1.0', $valid),
            str_replace('"max_object_bytes":4096', '"max_object_bytes":true', $valid),
            str_replace('"max_object_bytes":4096', '"max_object_bytes":999999999999999999999', $valid),
            str_replace('"read"', '"not-a-capability"', $valid),
            str_replace('"read"', '"read","read"', $valid),
            str_replace('"name":"alpha"', '"name":"alpha","extra":1', $valid),
            self::document([self::row('alpha', 'alpha-objects'), self::row('alpha', 'alpha-objects')]),
            str_replace('"digest_algorithm":null', '"digest_algorithm":"sha256"', $valid),
            "\xEF\xBB\xBF" . $valid,
            "{\"schema\":\"kongono/storage-application-profile-source/1\",\"profiles\":[]}",
            str_repeat(' ', 1_048_577),
            str_repeat('[', 10) . $valid . str_repeat(']', 10),
            "{\"schema\":\"kongono/storage-application-profile-source/1\",\"profiles\":[{\"name\":\"\xFF\"}]}",
        ];
        $loader = new StorageProfileSourceLoader();
        $rejected = 0;
        foreach ($bad as $position => $document) {
            try {
                $loader->load($document);
                self::fail('Invalid source was accepted at case ' . $position . '.');
            } catch (InvalidStorageApplicationException) {
                ++$rejected;
            }
        }
        self::assertCount($rejected, $bad);
    }

    /** @param list<array<string, mixed>> $profiles */
    private static function document(array $profiles): string
    {
        return json_encode(['schema' => 'kongono/storage-application-profile-source/1', 'profiles' => $profiles], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }

    /** @return array<string, mixed> */
    private static function row(string $name, string $namespace): array
    {
        return [
            'name' => $name,
            'contract_version' => 1,
            'namespace' => $namespace,
            'capabilities' => ['read'],
            'digest_algorithm' => null,
            'limits' => ['max_chunk_bytes' => 1024, 'max_object_bytes' => 4096, 'max_list_page_size' => 10],
        ];
    }
}
