<?php

declare(strict_types=1);

namespace Kongono\Ai\Provider;

use Kongono\Ai\ChatRequest;
use Kongono\Ai\ChatResponse;

interface AiProviderInterface
{
    public function name(): string;

    public function defaultModel(): string;

    public function chat(ChatRequest $request): ChatResponse;
}
