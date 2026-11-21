<?php

declare(strict_types=1);

namespace Kongono\Ai\Embedding;

/** Contract for AI providers capable of generating dense vector embeddings. */
interface EmbeddingProviderInterface
{
    /**
     * Generate embedding vectors for one or more texts in order.
     *
     * @param non-empty-list<string> $texts
     * @return list<list<float>>
     */
    public function embed(array $texts, ?string $model = null): array;
}
