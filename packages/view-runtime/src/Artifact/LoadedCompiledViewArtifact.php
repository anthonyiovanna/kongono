<?php

declare(strict_types=1);

namespace Kongono\ViewRuntime\Artifact;

use Closure;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewBuild\Generated\RenderSink;
use Kongono\ViewContract\Identity\CompiledViewIdentity;
use Kongono\ViewContract\Identity\LogicalViewIdentity;
use Kongono\ViewContract\Schema\ViewContextSchema;
use Kongono\ViewContract\Value\ViewContext;
use Kongono\ViewRuntime\Internal\RuntimeGuard;

/** Carries one closed executable handle created only after loader authentication. */
#[Api(ApiStatus::Experimental)]
final readonly class LoadedCompiledViewArtifact
{
    /** @var array<string, string> */
    private array $viewSchemas;

    /**
     * @param Closure(LogicalViewIdentity, ViewContext, RenderSink): void $emit
     * @param array<array-key, mixed> $viewSchemas
     */
    private function __construct(
        private CompiledViewIdentity $compiledViewIdentity,
        private string $sourceSha256,
        private string $modelSha256,
        private string $generatedPhpSha256,
        private string $generatedClass,
        private int $viewCount,
        array $viewSchemas,
        private Closure $emit,
    ) {
        $validatedSchemas = [];
        foreach ($viewSchemas as $identity => $schema) {
            if (!is_string($identity) || !is_string($schema)) {
                throw new \LogicException('Loaded artifact schema evidence is invalid.');
            }
            $validatedSchemas[$identity] = $schema;
        }
        $this->viewSchemas = $validatedSchemas;
    }

    public function compiledViewIdentity(): CompiledViewIdentity
    {
        return $this->compiledViewIdentity;
    }

    public function sourceSha256(): string
    {
        return $this->sourceSha256;
    }

    public function modelSha256(): string
    {
        return $this->modelSha256;
    }

    public function generatedPhpSha256(): string
    {
        return $this->generatedPhpSha256;
    }

    public function generatedClass(): string
    {
        return $this->generatedClass;
    }

    public function viewCount(): int
    {
        return $this->viewCount;
    }

    public function accepts(LogicalViewIdentity $identity, ViewContextSchema $schema): bool
    {
        $expected = $this->viewSchemas[$identity->value()] ?? null;

        return null !== $expected && hash_equals($expected, RuntimeGuard::schemaBytes($schema));
    }

    public function hasView(LogicalViewIdentity $identity): bool
    {
        return array_key_exists($identity->value(), $this->viewSchemas);
    }

    public function emit(LogicalViewIdentity $identity, ViewContext $context, RenderSink $sink): void
    {
        ($this->emit)($identity, $context, $sink);
    }
}
