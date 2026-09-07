<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationBuild\Tests\Unit\Artifact;

use Kongono\ModelInvocationBuild\Artifact\InvocationPlanArtifactBuilder;
use Kongono\ModelInvocationBuild\Tests\Support\InvocationPlanSourceFactory;
use PHPUnit\Framework\TestCase;

final class InvocationPlanArtifactBuilderTest extends TestCase
{
    public function testCanonicalBytesDigestsOrderAndPrivacyAreExact(): void
    {
        $source = InvocationPlanSourceFactory::create('literal-secret-message');
        $first = (new InvocationPlanArtifactBuilder())->build($source);
        $second = (new InvocationPlanArtifactBuilder())->build(InvocationPlanSourceFactory::create('literal-secret-message'));
        self::assertSame($first->canonicalBytes(), $second->canonicalBytes());
        self::assertSame(hash('sha256', $first->canonicalBytes()), $first->artifactSha256());
        self::assertStringEndsWith("\n", $first->canonicalBytes());
        self::assertStringNotContainsString("\r", $first->canonicalBytes());
        self::assertStringNotContainsString('literal-secret-message', $first->canonicalBytes());
        $data = json_decode($first->canonicalBytes(), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($data);
        self::assertSame(['schema', 'artifact_version', 'contract_version', 'builder', 'plan', 'source_sha256'], array_keys($data));
        self::assertIsArray($data['plan']);
        self::assertSame(['plan_id', 'adapter_id', 'invocation_id', 'profile', 'request', 'budget'], array_keys($data['plan']));
        self::assertSame($data['source_sha256'], $first->sourceSha256());
        self::assertIsArray($data['plan']['request']);
        self::assertSame($data['plan']['request']['request_sha256'], $first->requestSha256());
    }

    public function testRequestAndSourceDigestsAreIndependentlyReproduced(): void
    {
        $artifact = (new InvocationPlanArtifactBuilder())->build(InvocationPlanSourceFactory::create('literal-secret-message'));
        $request = [
            'contract_version' => 1,
            'invocation_id' => 'invocation.primary',
            'profile' => [
                'profile_id' => 'profile.primary',
                'provider_id' => 'provider.primary',
                'model_id' => 'model.primary',
                'capabilities' => ['text_result', 'streaming', 'usage_evidence', 'cost_evidence'],
            ],
            'messages' => [
                ['role' => 'system', 'visibility' => 'protected', 'content' => 'bounded system context'],
                ['role' => 'user', 'visibility' => 'private', 'content' => 'literal-secret-message'],
            ],
            'result_mode' => 'text',
            'streaming' => true,
            'limits' => ['max_output_bytes' => 4096, 'max_stream_events' => 64, 'max_usage_entries' => 2],
        ];
        $budget = [
            'max_duration_milliseconds' => 30000,
            'max_attempts' => 1,
            'max_retained_evidence_bytes' => 65536,
            'usage_limits' => [
                ['metric' => 'input_tokens', 'unit' => 'tokens', 'maximum_quantity' => 4096],
                ['metric' => 'output_tokens', 'unit' => 'tokens', 'maximum_quantity' => 2048],
            ],
            'cost_limit' => ['currency' => 'USD', 'maximum_amount' => '1.25'],
        ];
        $encode = static fn(array $data): string => json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
        self::assertSame(hash('sha256', $encode($request)), $artifact->requestSha256());
        self::assertSame(hash('sha256', $encode(['plan_id' => 'plan.primary', 'adapter_id' => 'adapter.primary', 'request' => $request, 'budget' => $budget])), $artifact->sourceSha256());
    }

    public function testFreshProcessBuildIsByteIdentical(): void
    {
        $expected = base64_encode((new InvocationPlanArtifactBuilder())->build(InvocationPlanSourceFactory::create())->canonicalBytes());
        $autoload = dirname(__DIR__, 5) . '/vendor/autoload.php';
        $script = 'require ' . var_export($autoload, true) . '; echo base64_encode((new Kongono\\ModelInvocationBuild\\Artifact\\InvocationPlanArtifactBuilder())->build(Kongono\\ModelInvocationBuild\\Tests\\Support\\InvocationPlanSourceFactory::create())->canonicalBytes());';
        $process = proc_open([PHP_BINARY, '-r', $script], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        self::assertIsResource($process);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        self::assertSame(0, proc_close($process), (string) $stderr);
        self::assertSame($expected, $stdout);
    }
}
