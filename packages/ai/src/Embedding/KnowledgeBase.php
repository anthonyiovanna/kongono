<?php

declare(strict_types=1);

namespace Kongono\Ai\Embedding;

use Kongono\Ai\AiClient;
use Kongono\Ai\AiException;
use Kongono\Core\Diagnostics\SecretScrubber;

/**
 * 5-line Retrieval-Augmented Generation (RAG) pipeline combining text chunking,
 * dense embeddings, vector search, secret scrubbing, and grounded LLM citations.
 */
final readonly class KnowledgeBase
{
    public function __construct(
        private EmbeddingProviderInterface $embedder,
        private VectorStoreInterface $store,
        private TextChunker $chunker = new TextChunker(),
        private ?string $embeddingModel = null,
    ) {}

    /**
     * Chunk, scrub, embed, and index a document into the vector store.
     *
     * @param array<string, mixed> $metadata
     * @return int Number of indexed chunks
     */
    public function ingest(string $documentId, string $text, array $metadata = []): int
    {
        $chunks = $this->chunker->split(SecretScrubber::scrub($text));
        if ([] === $chunks) {
            return 0;
        }

        $vectors = $this->embedder->embed($chunks, $this->embeddingModel);
        if (count($vectors) !== count($chunks)) {
            throw new AiException(sprintf('Embedding provider returned %d vectors for %d chunks.', count($vectors), count($chunks)));
        }

        foreach ($chunks as $index => $chunk) {
            $chunkId = 1 === count($chunks) ? $documentId : sprintf('%s#chunk-%d', $documentId, $index);
            $this->store->upsert(new VectorDocument(
                id: $chunkId,
                content: $chunk,
                vector: $vectors[$index],
                metadata: [...$metadata, 'document_id' => $documentId, 'chunk_index' => $index],
            ));
        }

        return count($chunks);
    }

    /**
     * Semantic search over indexed chunks.
     *
     * @param positive-int         $topK
     * @param array<string, mixed> $metadataFilter
     * @return list<SearchHit>
     */
    public function search(string $query, int $topK = 5, array $metadataFilter = []): array
    {
        $vectors = $this->embedder->embed([SecretScrubber::scrub($query)], $this->embeddingModel);
        if ([] === $vectors) {
            return [];
        }

        return $this->store->search($vectors[0], $topK, $metadataFilter);
    }

    /**
     * Answer a question grounded strictly in retrieved knowledge chunks, returning answer + source citations.
     *
     * @param positive-int         $topK
     * @param array<string, mixed> $metadataFilter
     * @return array{answer: string, sources: list<array{id: string, score: float, excerpt: string}>}
     */
    public function ask(string $question, AiClient $ai, int $topK = 4, array $metadataFilter = []): array
    {
        $hits = $this->search($question, $topK, $metadataFilter);
        $contextBlocks = [];
        $sources = [];

        foreach ($hits as $i => $hit) {
            $n = $i + 1;
            $contextBlocks[] = sprintf('[%d] (id: %s, score: %.3f)\n%s', $n, $hit->document->id, $hit->score, $hit->document->content);
            $sources[] = [
                'id' => $hit->document->id,
                'score' => round($hit->score, 4),
                'excerpt' => mb_substr($hit->document->content, 0, 200),
            ];
        }

        $system = 'Answer the user question accurately using ONLY the provided reference excerpts. Cite sources inline as [1], [2], etc. If the excerpts do not contain the answer, say so clearly.';
        $prompt = "Reference Excerpts:\n" . implode("\n\n", $contextBlocks) . "\n\nQuestion: " . SecretScrubber::scrub($question);

        return [
            'answer' => $ai->ask($prompt, $system),
            'sources' => $sources,
        ];
    }
}
