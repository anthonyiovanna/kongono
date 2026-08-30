<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphArtifact\Tests\Unit\Artifact;

use Kongono\ApplicationGraphArtifact\Artifact\ApplicationGraphArtifactBuilder;
use Kongono\ApplicationGraphArtifact\Artifact\ApplicationGraphArtifactValidator;
use Kongono\ApplicationGraphArtifact\Exception\InvalidApplicationGraphArtifactException;
use Kongono\ApplicationGraphBuild\Build\ApplicationGraphBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ApplicationGraphArtifactValidationTest extends TestCase
{
    public function testCanonicalArtifactRehydratesExactly(): void
    {
        $bytes = (new ApplicationGraphArtifactBuilder())->build((new ApplicationGraphBuilder())->build([], []))->canonicalBytes();
        $artifact = (new ApplicationGraphArtifactValidator())->validateStructure($bytes);
        self::assertSame($bytes, $artifact->canonicalBytes());
        self::assertSame(hash('sha256', $bytes), $artifact->artifactSha256());
        self::assertSame($artifact->artifactSha256(), $artifact->inspection()->artifactSha256());
        self::assertSame($artifact->graphIdentity(), $artifact->inspection()->graphIdentity());
    }

    /** @param callable(string): string $mutation */
    #[DataProvider('invalidBytes')]
    public function testMalformedAndNoncanonicalFormsFailClosed(callable $mutation): void
    {
        $bytes = (new ApplicationGraphArtifactBuilder())->build((new ApplicationGraphBuilder())->build([], []))->canonicalBytes();
        $this->expectException(InvalidApplicationGraphArtifactException::class);
        (new ApplicationGraphArtifactValidator())->validateStructure($mutation($bytes));
    }

    /** @return iterable<string,array{callable(string):string}> */
    public static function invalidBytes(): iterable
    {
        yield 'duplicate key' => [static fn(string $b): string => str_replace('{"schema":', '{"schema":"x","schema":', $b)];
        yield 'unknown builder key' => [static fn(string $b): string => str_replace('"contract_version":1}', '"contract_version":1,"implementation_sha256":"' . str_repeat('a', 64) . '"}', $b)];
        yield 'noncanonical whitespace' => [static fn(string $b): string => str_replace('{"schema"', '{ "schema"', $b)];
        yield 'wrong graph digest' => [static fn(string $b): string => preg_replace('/"graph_sha256":"[a-f0-9]{64}"/', '"graph_sha256":"' . str_repeat('0', 64) . '"', $b) ?? $b];
        yield 'float count' => [static fn(string $b): string => str_replace('"node_count":0', '"node_count":0.0', $b)];
    }
}
