<?php

declare(strict_types=1);

namespace Kongono\ViewRuntime\Tests\Unit\Render;

use Kongono\ViewContract\ContractVersion;
use Kongono\ViewContract\Failure\RenderFailure;
use Kongono\ViewContract\Failure\RenderFailureKind;
use Kongono\ViewContract\Identity\CompiledViewIdentity;
use Kongono\ViewContract\Identity\LogicalViewIdentity;
use Kongono\ViewContract\Identity\RenderOperationIdentity;
use Kongono\ViewContract\Render\RenderLimits;
use Kongono\ViewContract\Render\RenderRequest;
use Kongono\ViewContract\Render\RenderResult;
use Kongono\ViewContract\Schema\PresentationFieldSchema;
use Kongono\ViewContract\Schema\PresentationValueSchema;
use Kongono\ViewContract\Schema\ViewContextSchema;
use Kongono\ViewContract\Value\StringValue;
use Kongono\ViewContract\Value\ViewContext;
use Kongono\ViewRuntime\Artifact\CompiledViewArtifactLoader;
use Kongono\ViewRuntime\Render\CompiledViewRenderer;
use Kongono\ViewRuntime\Tests\Fixture\RuntimeProbe;
use PHPUnit\Framework\TestCase;

final class CompiledViewRendererTest extends TestCase
{
    public function testReturnsExactEscapedOutputMetadataAndIdentities(): void
    {
        $fixture = RuntimeProbe::publish();
        try {
            $loaded = (new CompiledViewArtifactLoader())->load($fixture['root'], $fixture['directory'], $fixture['artifact']->compiledViewIdentity());
            $request = RuntimeProbe::alphaRequest($fixture['artifact'], '<>&amp;"\'é');
            $result = (new CompiledViewRenderer())->render($loaded, $request);
            self::assertInstanceOf(RenderResult::class, $result);
            self::assertSame('&lt;p&gt;&lt;&gt;&amp;amp;"\'é&lt;/p&gt;', $result->output()->bytes());
            self::assertSame(strlen($result->output()->bytes()), $result->metadata()->outputByteCount());
            self::assertSame(hash('sha256', $result->output()->bytes()), $result->metadata()->outputSha256());
            self::assertSame(3, $result->metadata()->renderStepCount());
            self::assertSame(1, $result->metadata()->escapedValueCount());
            self::assertTrue($request->operationIdentity()->equals($result->operationIdentity()));
        } finally {
            RuntimeProbe::cleanup($fixture);
        }
    }

    public function testMapsByteAndStepExhaustionWithoutPartialOutput(): void
    {
        $fixture = RuntimeProbe::publish();
        try {
            $loaded = (new CompiledViewArtifactLoader())->load($fixture['root'], $fixture['directory'], $fixture['artifact']->compiledViewIdentity());
            foreach ([RuntimeProbe::alphaRequest($fixture['artifact'], 'x', 8, 32, 'render.bytes'), RuntimeProbe::alphaRequest($fixture['artifact'], 'x', 1024, 2, 'render.steps')] as $request) {
                $result = (new CompiledViewRenderer())->render($loaded, $request);
                self::assertInstanceOf(RenderFailure::class, $result);
                self::assertSame(RenderFailureKind::BoundExceeded, $result->kind());
            }
        } finally {
            RuntimeProbe::cleanup($fixture);
        }
    }

    public function testMapsAnUnexpectedGeneratedThrowableWithoutDisclosingPartialOutput(): void
    {
        $fixture = RuntimeProbe::publishSource(str_replace('page.alpha', 'page.throw', RuntimeProbe::sourceBytes()));
        try {
            $generated = str_replace(
                "    {\n        \$viewIndex = match (\$view->value()) {",
                "    {\n        throw new \\LogicException('distinctive-private-generated-value');\n        \$viewIndex = match (\$view->value()) {",
                $fixture['artifact']->generatedPhpBytes(),
            );
            self::assertNotSame($fixture['artifact']->generatedPhpBytes(), $generated);
            $metadata = json_decode(substr($fixture['artifact']->metadataBytes(), 0, -1), true, flags: JSON_THROW_ON_ERROR);
            if (!is_array($metadata) || !is_array($metadata['generated_php'] ?? null)) {
                self::fail('Expected generated metadata envelope.');
            }
            $metadata['generated_php']['sha256'] = hash('sha256', $generated);
            $metadataBytes = json_encode($metadata, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
            self::assertNotFalse(file_put_contents($fixture['directory'] . '/compiled.php', $generated));
            self::assertNotFalse(file_put_contents($fixture['directory'] . '/artifact.json', $metadataBytes));
            $identity = new CompiledViewIdentity(hash('sha256', $metadataBytes));
            $renamed = $fixture['root'] . '/kongono-view-v1-' . $identity->value();
            self::assertTrue(rename($fixture['directory'], $renamed));
            $fixture['directory'] = $renamed;

            $loaded = (new CompiledViewArtifactLoader())->load($fixture['root'], $fixture['directory'], $identity);
            $base = RuntimeProbe::alphaRequest($fixture['artifact'], 'private-partial');
            $request = new RenderRequest(ContractVersion::Version1, new RenderOperationIdentity('render.throwable'), new LogicalViewIdentity('page.throw'), $identity, $base->contextSchema(), $base->context(), $base->limits());
            $result = (new CompiledViewRenderer())->render($loaded, $request);
            self::assertInstanceOf(RenderFailure::class, $result);
            self::assertSame(RenderFailureKind::RenderingFailed, $result->kind());
            self::assertStringNotContainsString('distinctive-private-generated-value', print_r($result, true));
            self::assertStringNotContainsString('private-partial', print_r($result, true));
        } finally {
            RuntimeProbe::cleanup($fixture);
        }
    }

    public function testMapsStaleUnknownViewAndSchemaMismatchNarrowly(): void
    {
        $fixture = RuntimeProbe::publish();
        try {
            $loaded = (new CompiledViewArtifactLoader())->load($fixture['root'], $fixture['directory'], $fixture['artifact']->compiledViewIdentity());
            $base = RuntimeProbe::alphaRequest($fixture['artifact'], 'ok');
            $different = new CompiledViewIdentity(('0' === $fixture['artifact']->compiledViewIdentity()->value()[0] ? '1' : '0') . substr($fixture['artifact']->compiledViewIdentity()->value(), 1));
            $stale = new RenderRequest(ContractVersion::Version1, new RenderOperationIdentity('render.stale'), $base->logicalViewIdentity(), $different, $base->contextSchema(), $base->context(), $base->limits());
            $staleResult = (new CompiledViewRenderer())->render($loaded, $stale);
            self::assertInstanceOf(RenderFailure::class, $staleResult);
            self::assertSame(RenderFailureKind::StaleArtifact, $staleResult->kind());

            $unknown = new RenderRequest(ContractVersion::Version1, new RenderOperationIdentity('render.unknown'), new LogicalViewIdentity('page.missing'), $base->compiledViewIdentity(), $base->contextSchema(), $base->context(), $base->limits());
            $unknownResult = (new CompiledViewRenderer())->render($loaded, $unknown);
            self::assertInstanceOf(RenderFailure::class, $unknownResult);
            self::assertSame(RenderFailureKind::InvalidRequest, $unknownResult->kind());

            $schema = new ViewContextSchema(PresentationValueSchema::record([new PresentationFieldSchema('message', PresentationValueSchema::string(255), true)]));
            $mismatch = new RenderRequest(ContractVersion::Version1, new RenderOperationIdentity('render.schema'), $base->logicalViewIdentity(), $base->compiledViewIdentity(), $schema, new ViewContext(['message' => new StringValue('ok')]), new RenderLimits(1024, 32));
            $mismatchResult = (new CompiledViewRenderer())->render($loaded, $mismatch);
            self::assertInstanceOf(RenderFailure::class, $mismatchResult);
            self::assertSame(RenderFailureKind::SchemaMismatch, $mismatchResult->kind());
        } finally {
            RuntimeProbe::cleanup($fixture);
        }
    }

    public function testSuccessAfterFailureAndFailureAfterSuccessAreIndependent(): void
    {
        $fixture = RuntimeProbe::publish();
        try {
            $loaded = (new CompiledViewArtifactLoader())->load($fixture['root'], $fixture['directory'], $fixture['artifact']->compiledViewIdentity());
            $renderer = new CompiledViewRenderer();
            self::assertInstanceOf(RenderFailure::class, $renderer->render($loaded, RuntimeProbe::alphaRequest($fixture['artifact'], 'late', 8, 32, 'render.first')));
            $success = $renderer->render($loaded, RuntimeProbe::alphaRequest($fixture['artifact'], 'clean', 1024, 32, 'render.second'));
            self::assertInstanceOf(RenderResult::class, $success);
            self::assertSame('&lt;p&gt;clean&lt;/p&gt;', $success->output()->bytes());
            self::assertInstanceOf(RenderFailure::class, $renderer->render($loaded, RuntimeProbe::alphaRequest($fixture['artifact'], 'late', 8, 32, 'render.third')));
        } finally {
            RuntimeProbe::cleanup($fixture);
        }
    }
}
