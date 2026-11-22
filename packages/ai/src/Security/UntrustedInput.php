<?php

declare(strict_types=1);

namespace Kongono\Ai\Security;

/**
 * Explicitly marks text originating from outside the trusted application boundary
 * (user input, emails, webhooks, scraped HTML, tool outputs) so PromptGuard can
 * isolate it from trusted system instructions.
 */
final readonly class UntrustedInput
{
    public function __construct(
        public string $content,
        public string $source = 'external',
        public ?string $origin = null,
    ) {}

    /**
     * Neutralize control characters and XML/boundary breakout attempts so untrusted
     * content cannot close or forge system prompt delimiters.
     */
    public function sanitized(): string
    {
        $clean = str_replace("\0", '', $this->content);

        return (string) preg_replace(
            '#<\s*/?\s*(untrusted_data|system|instructions|assistant|developer)\b[^>]*>#i',
            '[neutralized-tag]',
            $clean,
        );
    }
}
