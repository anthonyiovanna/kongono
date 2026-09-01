<?php

declare(strict_types=1);

namespace Kongono\AgentDeclarationBuild\Tests\Unit\Artifact;

use Kongono\AgentDeclarationBuild\Artifact\AgentDeclarationArtifactBuilder;
use Kongono\AgentDeclarationBuild\Artifact\AgentDeclarationArtifactValidator;
use Kongono\AgentDeclarationBuild\Exception\InvalidAgentDeclarationArtifactException;
use Kongono\AgentDeclarationBuild\Exception\StaleAgentDeclarationArtifactException;
use Kongono\AgentDeclarationBuild\Tests\Support\AgentDeclarationFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AgentDeclarationArtifactValidatorTest extends TestCase
{
    public function testStructureReconstructsAndRebuildsTypedValues(): void
    {
        $built = (new AgentDeclarationArtifactBuilder())->build(AgentDeclarationFactory::create());
        $validated = (new AgentDeclarationArtifactValidator())->validateStructure($built->canonicalBytes());
        self::assertSame($built->canonicalBytes(), $validated->canonicalBytes());
        self::assertSame($built->artifactSha256(), $validated->artifactSha256());
        self::assertSame('assistant.primary', $validated->declarationIdentity()->value());
    }

    public function testCurrentnessUsesOnlyExplicitDeclarationAndCompleteBytes(): void
    {
        $bytes = (new AgentDeclarationArtifactBuilder())->build(AgentDeclarationFactory::create())->canonicalBytes();
        self::assertSame($bytes, (new AgentDeclarationArtifactValidator())->validateCurrent(AgentDeclarationFactory::create(), $bytes)->canonicalBytes());
        $this->expectException(StaleAgentDeclarationArtifactException::class);
        (new AgentDeclarationArtifactValidator())->validateCurrent(AgentDeclarationFactory::create('assistant.changed'), $bytes);
    }

    #[DataProvider('framingProvider')]
    public function testMalformedAndNoncanonicalFramingFails(string $bytes): void
    {
        $this->expectException(InvalidAgentDeclarationArtifactException::class);
        (new AgentDeclarationArtifactValidator())->validateStructure($bytes);
    }

    /** @return iterable<string, array{string}> */
    public static function framingProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'bom' => ["\xEF\xBB\xBF{}\n"];
        yield 'crlf' => ["{}\r\n"];
        yield 'missing newline' => ['{}'];
        yield 'double newline' => ["{}\n\n"];
        yield 'trailing bytes' => ["{}\ntrailing"];
        yield 'list root' => ["[]\n"];
        yield 'float' => ["{\"value\":1.5}\n"];
        yield 'duplicate key' => ["{\"schema\":\"first\",\"schema\":\"second\"}\n"];
        yield 'leading whitespace' => [" {\"schema\":\"value\"}\n"];
        yield 'oversize' => [str_repeat('x', 131_073)];
    }

    public function testMissingExtraReorderedWrongAndDigestChangesFail(): void
    {
        $valid = (new AgentDeclarationArtifactBuilder())->build(AgentDeclarationFactory::create())->canonicalBytes();
        $data = $this->object(json_decode($valid, true, flags: JSON_THROW_ON_ERROR));
        $cases = [];
        $missing = $data;
        unset($missing['schema']);
        $cases[] = $missing;
        $extra = $data;
        $extra['extra'] = true;
        $cases[] = $extra;
        $wrong = $data;
        $wrong['artifact_version'] = '1';
        $cases[] = $wrong;
        $digest = $data;
        $digest['declaration_sha256'] = str_repeat('0', 64);
        $cases[] = $digest;
        $reordered = ['artifact_version' => 1, 'schema' => $data['schema']] + array_diff_key($data, ['artifact_version' => true, 'schema' => true]);
        $cases[] = $reordered;
        $unknownEnum = $data;
        $declaration = $this->object($unknownEnum['declaration']);
        $declaration['visibility'] = 'unknown';
        $unknownEnum['declaration'] = $declaration;
        $unknownEnum['declaration_sha256'] = $this->declarationHash($declaration);
        $cases[] = $unknownEnum;

        foreach ($cases as $case) {
            $bytes = json_encode($case, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
            try {
                (new AgentDeclarationArtifactValidator())->validateStructure($bytes);
                self::fail('Expected strict validation failure.');
            } catch (InvalidAgentDeclarationArtifactException $failure) {
                self::assertSame('KGN-AGENT-DECLARATION-1311', $failure->diagnosticId());
            }
        }
    }

    public function testInvalidIdentityListShapeTypeDuplicatesOrderAndBoundFail(): void
    {
        $valid = (new AgentDeclarationArtifactBuilder())->build(AgentDeclarationFactory::create())->canonicalBytes();
        $data = $this->object(json_decode($valid, true, flags: JSON_THROW_ON_ERROR));
        $declaration = $this->object($data['declaration']);
        $requests = $declaration['capability_requests'];
        self::assertIsArray($requests);
        self::assertIsArray($requests[0]);
        self::assertIsArray($requests[1]);

        $cases = [];
        $invalidIdentity = $declaration;
        $invalidIdentity['declaration_id'] = 'Invalid';
        $cases[] = $invalidIdentity;
        $nonList = $declaration;
        $nonList['capability_requests'] = [1 => $requests[0]];
        $cases[] = $nonList;
        $wrongType = $declaration;
        $wrongEntry = $requests[0];
        $wrongEntry['purpose'] = 42;
        $wrongType['capability_requests'] = [$wrongEntry, $requests[1]];
        $cases[] = $wrongType;
        $duplicate = $declaration;
        $duplicate['capability_requests'] = [$requests[0], $requests[0]];
        $cases[] = $duplicate;
        $reordered = $declaration;
        $reordered['capability_requests'] = [$requests[1], $requests[0]];
        $cases[] = $reordered;
        $excessive = $declaration;
        $excessive['capability_requests'] = array_fill(0, 33, $requests[0]);
        $cases[] = $excessive;
        $badDigest = $declaration;
        $evidence = $badDigest['evidence_references'];
        self::assertIsArray($evidence);
        self::assertIsArray($evidence[0]);
        $evidence[0]['sha256'] = str_repeat('A', 64);
        $badDigest['evidence_references'] = $evidence;
        $cases[] = $badDigest;

        foreach ($cases as $case) {
            $bytes = $this->artifactBytes($data, $case);
            try {
                (new AgentDeclarationArtifactValidator())->validateStructure($bytes);
                self::fail('Expected strict declaration reconstruction failure.');
            } catch (InvalidAgentDeclarationArtifactException $failure) {
                self::assertSame('KGN-AGENT-DECLARATION-1311', $failure->diagnosticId());
            }
        }
    }

    public function testSingleByteValidArtifactChangeIsStaleForExplicitCurrentDeclaration(): void
    {
        $current = AgentDeclarationFactory::create('assistant.primary');
        $changed = (new AgentDeclarationArtifactBuilder())->build(AgentDeclarationFactory::create('assistant.primarz'));
        self::assertSame(strlen((new AgentDeclarationArtifactBuilder())->build($current)->canonicalBytes()), strlen($changed->canonicalBytes()));

        $this->expectException(StaleAgentDeclarationArtifactException::class);
        (new AgentDeclarationArtifactValidator())->validateCurrent($current, $changed->canonicalBytes());
    }

    /** @param array<string, mixed> $declaration */
    private function declarationHash(array $declaration): string
    {
        return hash('sha256', json_encode($declaration, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
    }

    /**
     * @param array<string, mixed> $root
     * @param array<string, mixed> $declaration
     */
    private function artifactBytes(array $root, array $declaration): string
    {
        $root['declaration'] = $declaration;
        $root['declaration_sha256'] = $this->declarationHash($declaration);

        return json_encode($root, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    }

    /** @return array<string, mixed> */
    private function object(mixed $value): array
    {
        self::assertIsArray($value);
        $object = [];
        foreach ($value as $key => $entry) {
            self::assertIsString($key);
            $object[$key] = $entry;
        }

        return $object;
    }
}
