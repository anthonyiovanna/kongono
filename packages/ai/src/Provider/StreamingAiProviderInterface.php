<?php

declare(strict_types=1);

namespace Kongono\Ai\Provider;

use Closure;
use Kongono\Ai\ChatRequest;
use Kongono\Ai\ChatResponse;

interface StreamingAiProviderInterface extends AiProviderInterface
{
    /**
     * Stream token deltas to $onDelta as they arrive and return the final aggregated ChatResponse.
     *
     * @param Closure(string): void $onDelta
     */
    public function chatStream(ChatRequest $request, Closure $onDelta): ChatResponse;
}
